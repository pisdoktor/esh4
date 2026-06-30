<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * KPS / TC Kimlik Paylaşım Sistemi — tek giriş noktası.
 */
final class KpsTcKimlikClient
{
    private const MODULE_KEY = 'kps_tc_sorgu';

    public static function isEnabled(): bool
    {
        return AppSettings::isModuleEnabled(self::MODULE_KEY);
    }

    public static function isConfigured(): bool
    {
        if (!KpsCredentialsStore::isConfigured()) {
            return false;
        }

        return OperationalSettings::kpsFirmaKoduStatusForAdmin()['configured'];
    }

    /**
     * @return array{
     *   ok:bool,
     *   status:string,
     *   message:string,
     *   data:array<string, string>|null
     * }
     */
    public static function lookupByTc(string $tc): array
    {
        $tc = ValidationHelper::tcDigitsOnly($tc);

        if (!self::isEnabled()) {
            $result = self::result(false, 'disabled', 'KPS TC sorgusu modülü kapalı. Uygulama modüllerinden açabilirsiniz.');
            KpsQueryLog::write($tc, $result['status'], $result['message'], 'identity');

            return $result;
        }

        if (!ValidationHelper::isTcLength11($tc)) {
            $result = self::result(false, 'invalid_tc', 'TC kimlik numarası 11 haneli olmalıdır.');
            KpsQueryLog::write($tc, $result['status'], $result['message'], 'identity');

            return $result;
        }

        if (!ValidationHelper::isTc($tc)) {
            $result = self::result(false, 'invalid_tc', 'TC kimlik numarası kontrol hanesi geçersiz.');
            KpsQueryLog::write($tc, $result['status'], $result['message'], 'identity');

            return $result;
        }

        if (!self::isConfigured()) {
            $missing = [];
            if (!KpsCredentialsStore::isConfigured()) {
                $missing[] = 'web servis kullanıcı adı/şifre';
            }
            if (!OperationalSettings::kpsFirmaKoduStatusForAdmin()['configured']) {
                $missing[] = 'firma kodu (config.local.php)';
            }
            $result = self::result(
                false,
                'not_configured',
                'KPS yapılandırması eksik: ' . implode(', ', $missing) . '.'
            );
            KpsQueryLog::write($tc, $result['status'], $result['message'], 'identity');

            return $result;
        }

        $creds = KpsCredentialsStore::read();
        $firma = OperationalSettings::kpsFirmaKoduStatusForAdmin();

        $transport = KpsSoapTransport::query($tc, [
            'wsdl_url' => OperationalSettings::kpsWsdlUrl(),
            'timeout_seconds' => OperationalSettings::kpsTimeoutSeconds(),
            'username' => $creds['username'],
            'password' => $creds['password'],
            'firma_kodu' => (string) ($firma['value'] ?? ''),
        ]);

        $result = [
            'ok' => (bool) ($transport['ok'] ?? false),
            'status' => (string) ($transport['status'] ?? 'error'),
            'message' => (string) ($transport['message'] ?? ''),
            'data' => is_array($transport['data'] ?? null) ? $transport['data'] : null,
        ];

        KpsQueryLog::write($tc, $result['status'], $result['message'], 'identity');

        return $result;
    }

    /**
     * @return array{
     *   ok:bool,
     *   status:string,
     *   deceased:bool,
     *   olumTarihi:?string,
     *   yasamDurumu:string,
     *   message:string,
     *   data:array<string, string>|null
     * }
     */
    public static function checkDeathByTc(string $tc): array
    {
        $tc = ValidationHelper::tcDigitsOnly($tc);

        if (!self::isEnabled()) {
            $result = self::deathResult(false, 'disabled', false, null, '', 'KPS TC sorgusu modülü kapalı.');
            KpsQueryLog::write($tc, $result['status'], $result['message'], 'death');

            return $result;
        }

        if (!ValidationHelper::isTcLength11($tc)) {
            $result = self::deathResult(false, 'invalid_tc', false, null, '', 'TC kimlik numarası 11 haneli olmalıdır.');
            KpsQueryLog::write($tc, $result['status'], $result['message'], 'death');

            return $result;
        }

        if (!ValidationHelper::isTc($tc)) {
            $result = self::deathResult(false, 'invalid_tc', false, null, '', 'TC kimlik numarası kontrol hanesi geçersiz.');
            KpsQueryLog::write($tc, $result['status'], $result['message'], 'death');

            return $result;
        }

        if (!self::isConfigured()) {
            $missing = [];
            if (!KpsCredentialsStore::isConfigured()) {
                $missing[] = 'web servis kullanıcı adı/şifre';
            }
            if (!OperationalSettings::kpsFirmaKoduStatusForAdmin()['configured']) {
                $missing[] = 'firma kodu (config.local.php)';
            }
            $result = self::deathResult(
                false,
                'not_configured',
                false,
                null,
                '',
                'KPS yapılandırması eksik: ' . implode(', ', $missing) . '.'
            );
            KpsQueryLog::write($tc, $result['status'], $result['message'], 'death');

            return $result;
        }

        $creds = KpsCredentialsStore::read();
        $firma = OperationalSettings::kpsFirmaKoduStatusForAdmin();

        $transport = KpsSoapTransport::queryDeath($tc, [
            'wsdl_url' => OperationalSettings::kpsWsdlUrl(),
            'timeout_seconds' => OperationalSettings::kpsTimeoutSeconds(),
            'username' => $creds['username'],
            'password' => $creds['password'],
            'firma_kodu' => (string) ($firma['value'] ?? ''),
        ]);

        $status = (string) ($transport['status'] ?? 'error');
        $deceased = (bool) ($transport['deceased'] ?? false);
        $olumTarihi = self::normalizeDeathDate($transport['olumTarihi'] ?? null, $transport['data'] ?? null);

        if ($status === 'success' || ($transport['ok'] ?? false)) {
            if ($deceased && $olumTarihi !== null) {
                $status = 'deceased';
            } else {
                $status = 'alive';
                $deceased = false;
            }
        }

        $result = self::deathResult(
            $status === 'deceased',
            $status,
            $deceased,
            $olumTarihi,
            (string) ($transport['yasamDurumu'] ?? ''),
            (string) ($transport['message'] ?? ''),
            is_array($transport['data'] ?? null) ? $transport['data'] : null
        );

        KpsQueryLog::write($tc, $result['status'], $result['message'], 'death');

        return $result;
    }

    /**
     * @return array{ok:bool,status:string,message:string,details:array<string, mixed>}
     */
    public static function testConnection(): array
    {
        if (!self::isEnabled()) {
            return [
                'ok' => false,
                'status' => 'disabled',
                'message' => 'KPS modülü kapalı.',
                'details' => ['module' => self::MODULE_KEY],
            ];
        }

        $credStatus = KpsCredentialsStore::statusForAdmin();
        $firmaStatus = OperationalSettings::kpsFirmaKoduStatusForAdmin();

        $details = [
            'module_enabled' => true,
            'credentials_configured' => $credStatus['configured'],
            'username' => $credStatus['username'] !== '' ? $credStatus['username'] : '—',
            'firma_kodu_configured' => $firmaStatus['configured'],
            'firma_kodu_source' => (string) ($firmaStatus['source'] ?? ''),
            'wsdl_url' => OperationalSettings::kpsWsdlUrl(),
            'timeout_seconds' => OperationalSettings::kpsTimeoutSeconds(),
        ];

        if (!$credStatus['configured']) {
            return [
                'ok' => false,
                'status' => 'not_configured',
                'message' => 'Web servis kullanıcı adı ve şifre girilmemiş.',
                'details' => $details,
            ];
        }

        if (!$firmaStatus['configured']) {
            return [
                'ok' => false,
                'status' => 'not_configured',
                'message' => 'Firma kodu tanımlı değil (config.local.php → kps_firma_kodu).',
                'details' => $details,
            ];
        }

        return [
            'ok' => false,
            'status' => 'stub',
            'message' => 'Yapılandırma tamam. SOAP bağlantısı yetki sonrası etkinleştirilecek.',
            'details' => $details,
        ];
    }

    /**
     * @param array<string, string>|null $data
     * @return array{ok:bool,status:string,message:string,data:array<string, string>|null}
     */
    private static function result(bool $ok, string $status, string $message, ?array $data = null): array
    {
        return [
            'ok' => $ok,
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * @param array<string, string>|null $data
     */
    private static function deathResult(
        bool $ok,
        string $status,
        bool $deceased,
        ?string $olumTarihi,
        string $yasamDurumu,
        string $message,
        ?array $data = null
    ): array {
        return [
            'ok' => $ok,
            'status' => $status,
            'deceased' => $deceased,
            'olumTarihi' => $olumTarihi,
            'yasamDurumu' => $yasamDurumu,
            'message' => $message,
            'data' => $data,
        ];
    }

    /**
     * @param array<string, string>|null $data
     */
    private static function normalizeDeathDate($raw, ?array $data): ?string
    {
        $candidate = '';
        if (is_string($raw) && trim($raw) !== '') {
            $candidate = trim($raw);
        } elseif (is_array($data) && trim((string) ($data['olumTarihi'] ?? '')) !== '') {
            $candidate = trim((string) $data['olumTarihi']);
        }
        if ($candidate === '') {
            return null;
        }

        $ts = strtotime($candidate);
        if ($ts === false) {
            return null;
        }

        return date('d-m-Y', $ts);
    }
}
