<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * SMS sağlayıcı API kimlik bilgileri — storage/sms/credentials.json (public dışı).
 */
final class SmsCredentialsStore
{
    private const REL = 'storage/sms/credentials.json';

    /**
     * @return array{provider:string,api_user:string,api_password:string,api_key:string,sender_id:string,test_phone:string,updated_at?:string}
     */
    public static function read(): array
    {
        $defaults = [
            'provider' => 'mock',
            'api_user' => '',
            'api_password' => '',
            'api_key' => '',
            'sender_id' => '',
            'test_phone' => '',
        ];
        $path = self::path();
        if (!is_readable($path)) {
            return $defaults;
        }
        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return $defaults;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        return [
            'provider' => trim((string) ($decoded['provider'] ?? 'mock')),
            'api_user' => trim((string) ($decoded['api_user'] ?? '')),
            'api_password' => (string) ($decoded['api_password'] ?? ''),
            'api_key' => trim((string) ($decoded['api_key'] ?? '')),
            'sender_id' => trim((string) ($decoded['sender_id'] ?? '')),
            'test_phone' => trim((string) ($decoded['test_phone'] ?? '')),
            'updated_at' => isset($decoded['updated_at']) ? (string) $decoded['updated_at'] : '',
        ];
    }

    public static function isConfiguredForProvider(string $provider): bool
    {
        $data = self::read();
        if ($provider === 'mock') {
            return true;
        }
        if ($data['sender_id'] === '') {
            return false;
        }
        if (in_array($provider, ['netgsm', 'iletimerkezi'], true)) {
            return $data['api_user'] !== '' && $data['api_password'] !== '';
        }
        if (in_array($provider, ['turkeysms', 'custom_http'], true)) {
            return $data['api_key'] !== '';
        }

        return false;
    }

    /**
     * @return array{configured:bool,provider:string,api_user:string,masked_password:string,masked_api_key:string,sender_id:string,test_phone:string,updated_at:string}
     */
    public static function statusForAdmin(): array
    {
        $data = self::read();

        return [
            'configured' => self::isConfiguredForProvider($data['provider']),
            'provider' => $data['provider'],
            'api_user' => $data['api_user'],
            'masked_password' => self::maskSecret($data['api_password']),
            'masked_api_key' => self::maskSecret($data['api_key']),
            'sender_id' => $data['sender_id'],
            'test_phone' => $data['test_phone'],
            'updated_at' => (string) ($data['updated_at'] ?? ''),
        ];
    }

    /**
     * @return true|string
     */
    public static function save(array $posted)
    {
        $existing = self::read();
        $provider = trim((string) ($posted['provider'] ?? $existing['provider']));
        if ($provider === '') {
            $provider = 'mock';
        }
        $apiUser = trim((string) ($posted['api_user'] ?? $existing['api_user']));
        $apiPassword = trim((string) ($posted['api_password'] ?? ''));
        if ($apiPassword === '') {
            $apiPassword = $existing['api_password'];
        }
        $apiKey = trim((string) ($posted['api_key'] ?? ''));
        if ($apiKey === '') {
            $apiKey = $existing['api_key'];
        }
        $senderId = trim((string) ($posted['sender_id'] ?? $existing['sender_id']));
        $testPhone = trim((string) ($posted['test_phone'] ?? $existing['test_phone']));

        $dir = dirname(self::path());
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            return 'SMS kimlik bilgisi dizini oluşturulamadı.';
        }

        $payload = [
            'provider' => $provider,
            'api_user' => $apiUser,
            'api_password' => $apiPassword,
            'api_key' => $apiKey,
            'sender_id' => $senderId,
            'test_phone' => $testPhone,
            'updated_at' => date('c'),
        ];
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (!is_string($json)) {
            return 'Kimlik bilgisi JSON üretilemedi.';
        }
        $written = @file_put_contents(self::path(), $json, LOCK_EX);
        if ($written === false) {
            return 'Kimlik bilgisi dosyasına yazılamadı.';
        }
        @chmod(self::path(), 0640);

        return true;
    }

    public static function maskSecret(string $secret): string
    {
        $len = strlen($secret);
        if ($len === 0) {
            return '';
        }
        if ($len <= 4) {
            return str_repeat('•', $len);
        }

        return substr($secret, 0, 2) . str_repeat('•', max(4, $len - 4)) . substr($secret, -1);
    }

    private static function path(): string
    {
        return rtrim((string) ROOT_PATH, '/\\') . '/' . self::REL;
    }
}
