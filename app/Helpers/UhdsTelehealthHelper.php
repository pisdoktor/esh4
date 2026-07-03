<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * UHDS görüntülü görüşme (Jitsi Meet) — oda adı, hasta davet bağlantısı, izlem yönlendirmesi.
 */
final class UhdsTelehealthHelper
{
  private const TOKEN_VERSION = 1;

    public static function isEnabled(): bool
    {
        return OperationalSettings::uhdsTelehealthEnabled()
            && OperationalSettings::uhdsTelehealthProvider() !== 'disabled';
    }

    public static function provider(): string
    {
        return OperationalSettings::uhdsTelehealthProvider();
    }

    public static function jitsiDomain(): string
    {
        return OperationalSettings::uhdsTelehealthJitsiDomain();
    }

    /**
     * @param object $appointment Uhds kaydı (id, kurum_id, randevu_tarihi, hastatckimlik, video_room_id)
     */
    public static function ensureRoomId(object $appointment): string
    {
        $existing = trim((string) ($appointment->video_room_id ?? ''));
        if ($existing !== '' && self::isValidRoomName($existing)) {
            return $existing;
        }

        $id = (int) ($appointment->id ?? 0);
        $kurumId = max(1, (int) ($appointment->kurum_id ?? 1));
        $prefix = self::sanitizeRoomPrefix(OperationalSettings::string('uhds_telehealth', 'room_prefix', 'esh-uhds'));
        $suffix = substr(hash('sha256', $id . '|' . ($appointment->hastatckimlik ?? '') . '|' . ($appointment->randevu_tarihi ?? '')), 0, 8);
        $room = $prefix . '-k' . $kurumId . '-r' . $id . '-' . $suffix;
        $room = self::sanitizeRoomName($room);

        if ($id > 0) {
            $model = new \App\Models\Uhds();
            if ($model->load($id)) {
                $model->bind(['video_room_id' => $room], true);
                $model->store();
            }
        }

        return $room;
    }

    public static function isValidRoomName(string $name): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{2,62}$/', $name);
    }

    /**
     * @return array{domain:string,roomName:string,displayName:string,config:array<string,mixed>,interfaceConfig:array<string,mixed>}
     */
    public static function jitsiClientConfig(string $roomName, string $displayName, bool $isModerator = true): array
    {
        $domain = self::jitsiDomain();

        return [
            'domain' => $domain,
            'roomName' => $roomName,
            'displayName' => $displayName !== '' ? $displayName : 'Katılımcı',
            'config' => [
                'startWithAudioMuted' => false,
                'startWithVideoMuted' => !$isModerator,
                'prejoinPageEnabled' => true,
                'disableDeepLinking' => true,
            ],
            'interfaceConfig' => [
                'MOBILE_APP_PROMO' => false,
                'SHOW_JITSI_WATERMARK' => false,
            ],
        ];
    }

    /**
     * Hasta / misafir katılım jetonu (HMAC imzalı).
     */
    public static function createPatientJoinToken(int $appointmentId, string $appointmentDateYmd): string
    {
        $hours = OperationalSettings::uhdsTelehealthPatientJoinHours();
        $exp = time() + ($hours * 3600);
        $payload = [
            'v' => self::TOKEN_VERSION,
            'id' => $appointmentId,
            'exp' => $exp,
            'role' => 'patient',
            'd' => $appointmentDateYmd,
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            return '';
        }
        $body = self::base64UrlEncode($json);
        $sig = self::base64UrlEncode(hash_hmac('sha256', $body, self::signingKey(), true));

        return $body . '.' . $sig;
    }

    /**
     * @return array{id:int,exp:int,role:string,d:string}|null
     */
    public static function verifyPatientJoinToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || !str_contains($token, '.')) {
            return null;
        }
        [$body, $sig] = explode('.', $token, 2);
        if ($body === '' || $sig === '') {
            return null;
        }
        $expected = self::base64UrlEncode(hash_hmac('sha256', $body, self::signingKey(), true));
        if (!hash_equals($expected, $sig)) {
            return null;
        }
        $json = self::base64UrlDecode($body);
        if ($json === null) {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data) || (int) ($data['v'] ?? 0) !== self::TOKEN_VERSION) {
            return null;
        }
        $id = (int) ($data['id'] ?? 0);
        $exp = (int) ($data['exp'] ?? 0);
        if ($id <= 0 || $exp < time()) {
            return null;
        }
        $role = (string) ($data['role'] ?? '');
        if ($role !== 'patient') {
            return null;
        }

        return [
            'id' => $id,
            'exp' => $exp,
            'role' => $role,
            'd' => (string) ($data['d'] ?? ''),
        ];
    }

    public static function patientJoinUrl(int $appointmentId, string $appointmentDateYmd): string
    {
        $token = self::createPatientJoinToken($appointmentId, $appointmentDateYmd);
        if ($token === '') {
            return '';
        }

        return esh_url('Uhds', 'patientVideo', ['token' => $token], true);
    }

    /**
     * Hasta davet bağlantısı için SMS / WhatsApp metni.
     */
    public static function patientInviteMessage(string $joinUrl, string $hastaLabel = ''): string
    {
        $joinUrl = trim($joinUrl);
        if ($joinUrl === '') {
            return '';
        }
        $label = trim($hastaLabel);
        if ($label !== '') {
            return 'Sayın hasta/bakım veren, ' . $label . ' için görüntülü görüşme davetiniz: ' . $joinUrl;
        }

        return 'Görüntülü görüşme davetiniz: ' . $joinUrl;
    }

    /**
     * wa.me bağlantısı için telefon (ülke kodu ile, yalnızca rakam).
     */
    public static function phoneForWaMe(?string $raw): string
    {
        $digits = preg_replace('/[^0-9]/', '', trim((string) $raw));
        if (!is_string($digits) || $digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            $digits = '9' . $digits;
        }

        return $digits;
    }

    /**
     * Görüşme sonrası izlem oluşturma bağlantısı.
     *
     * @param object $appointment
     */
    public static function visitCreateUrl(object $appointment, string $summary = ''): string
    {
        $tc = trim((string) ($appointment->hastatckimlik ?? ''));
        if (!ValidationHelper::isTcLength11($tc)) {
            return '';
        }
        $params = [
            'controller' => 'Visit',
            'action' => 'create',
            'tc' => $tc,
        ];
        $tarih = trim((string) ($appointment->randevu_tarihi ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tarih)) {
            $params['tarih'] = $tarih;
        }
        if (isset($appointment->zaman) && $appointment->zaman !== '' && $appointment->zaman !== null) {
            $params['zaman'] = (string) (int) $appointment->zaman;
        }
        $note = trim($summary);
        if ($note === '') {
            $note = trim((string) ($appointment->telehealth_summary ?? ''));
        }
        if ($note !== '') {
            $params['aciklama'] = $note;
        }
        $uhdsId = (int) ($appointment->id ?? 0);
        if ($uhdsId > 0) {
            $params['uhds_id'] = $uhdsId;
        }

        return UrlHelper::fromRequestParams($params);
    }

    public static function externalApiScriptUrl(): string
    {
        $domain = self::jitsiDomain();
        if ($domain === '') {
            $domain = 'meet.jit.si';
        }

        return 'https://' . $domain . '/external_api.js';
    }

    private static function signingKey(): string
    {
        $env = getenv('ESH_UHDS_JOIN_SECRET');
        if (is_string($env) && trim($env) !== '') {
            return trim($env);
        }
        if (function_exists('esh_config_local')) {
            $local = esh_config_local('uhds_join_secret', '');
            if (is_string($local) && trim($local) !== '') {
                return trim($local);
            }
        }
        $lock = ROOT_PATH . '/config/install.lock';
        if (is_file($lock)) {
            $raw = @file_get_contents($lock);

            return hash('sha256', (is_string($raw) ? $raw : '') . '|esh-uhds-join');
        }

        return hash('sha256', ROOT_PATH . '|esh-uhds-fallback');
    }

    private static function sanitizeRoomPrefix(string $prefix): string
    {
        $prefix = strtolower(trim($prefix));
        $prefix = preg_replace('/[^a-z0-9-]+/', '-', $prefix) ?? 'esh-uhds';
        $prefix = trim($prefix, '-');

        return $prefix !== '' ? $prefix : 'esh-uhds';
    }

    private static function sanitizeRoomName(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $name) ?? '';
        $name = trim($name, '-');
        if ($name === '' || strlen($name) > 64) {
            $name = 'esh-uhds-' . substr(hash('sha256', $name . microtime(true)), 0, 16);
        }

        return $name;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): ?string
    {
        $pad = 4 - (strlen($data) % 4);
        if ($pad < 4) {
            $data .= str_repeat('=', $pad);
        }
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        return is_string($decoded) ? $decoded : null;
    }
}
