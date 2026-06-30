<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * KPS web servis kullanıcı adı / şifre — storage/kps/credentials.json (public dışı).
 */
final class KpsCredentialsStore
{
    private const REL = 'storage/kps/credentials.json';

    /**
     * @return array{username:string,password:string,updated_at?:string}
     */
    public static function read(): array
    {
        $path = self::path();
        if (!is_readable($path)) {
            return ['username' => '', 'password' => ''];
        }
        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return ['username' => '', 'password' => ''];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return ['username' => '', 'password' => ''];
        }

        return [
            'username' => trim((string) ($decoded['username'] ?? '')),
            'password' => (string) ($decoded['password'] ?? ''),
            'updated_at' => isset($decoded['updated_at']) ? (string) $decoded['updated_at'] : '',
        ];
    }

    public static function isConfigured(): bool
    {
        $data = self::read();

        return $data['username'] !== '' && $data['password'] !== '';
    }

    /**
     * @return array{configured:bool,username:string,masked_password:string,updated_at:string}
     */
    public static function statusForAdmin(): array
    {
        $data = self::read();
        $username = $data['username'];
        $password = $data['password'];

        return [
            'configured' => $username !== '' && $password !== '',
            'username' => $username,
            'masked_password' => self::maskPassword($password),
            'updated_at' => (string) ($data['updated_at'] ?? ''),
        ];
    }

    /**
     * Boş şifre gönderilirse mevcut şifre korunur.
     *
     * @return true|string
     */
    public static function save(string $username, string $password)
    {
        $username = trim($username);
        if ($username === '') {
            return 'KPS kullanıcı adı boş bırakılamaz.';
        }

        $existing = self::read();
        $finalPassword = trim($password);
        if ($finalPassword === '') {
            $finalPassword = $existing['password'];
        }
        if ($finalPassword === '') {
            return 'İlk kayıtta web servis şifresi zorunludur.';
        }

        $dir = dirname(self::path());
        if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
            return 'KPS kimlik bilgisi dizini oluşturulamadı.';
        }

        $payload = [
            'username' => $username,
            'password' => $finalPassword,
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

    public static function maskPassword(string $password): string
    {
        $len = strlen($password);
        if ($len === 0) {
            return '';
        }
        if ($len <= 4) {
            return str_repeat('•', $len);
        }

        return substr($password, 0, 2) . str_repeat('•', max(4, $len - 4)) . substr($password, -1);
    }

    private static function path(): string
    {
        return rtrim((string) ROOT_PATH, '/\\') . '/' . self::REL;
    }
}
