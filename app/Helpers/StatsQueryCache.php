<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * İstatistik / harita özet sorguları — kısa ömürlü dosya önbelleği (tekrarlayan XHR).
 */
final class StatsQueryCache
{
    private const DIR_REL = 'storage/cache/stats';

    /** @var int */
    private static $defaultTtl = 300;

    public static function get(string $key, int $ttlSeconds = 0)
    {
        $ttl = $ttlSeconds > 0 ? $ttlSeconds : self::$defaultTtl;
        if ($ttl <= 0) {
            return null;
        }
        $path = self::pathForKey($key);
        if (!is_readable($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return null;
        }
        $payload = json_decode($raw, true);
        if (!is_array($payload) || !isset($payload['exp'], $payload['data'])) {
            return null;
        }
        if ((int) $payload['exp'] < time()) {
            @unlink($path);

            return null;
        }

        return $payload['data'];
    }

    /**
     * @param mixed $data JSON-serializable
     */
    public static function set(string $key, $data, int $ttlSeconds = 0): void
    {
        $ttl = $ttlSeconds > 0 ? $ttlSeconds : self::$defaultTtl;
        if ($ttl <= 0) {
            return;
        }
        $dir = self::cacheDir();
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return;
        }
        $path = self::pathForKey($key);
        $json = json_encode(
            ['exp' => time() + $ttl, 'data' => $data],
            JSON_UNESCAPED_UNICODE
        );
        if (!is_string($json)) {
            return;
        }
        $tmp = $path . '.' . getmypid() . '.tmp';
        if (@file_put_contents($tmp, $json, LOCK_EX) !== false) {
            @rename($tmp, $path);
        }
    }

    public static function forget(string $key): void
    {
        $path = self::pathForKey($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function forgetPrefix(string $prefix): void
    {
        $dir = self::cacheDir();
        if (!is_dir($dir)) {
            return;
        }
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) ?? '';
        if ($safe === '') {
            return;
        }
        foreach (glob($dir . '/' . $safe . '_*.json') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private static function cacheDir(): string
    {
        return rtrim((string) ROOT_PATH, '/\\') . '/' . self::DIR_REL;
    }

    private static function pathForKey(string $key): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', $key) ?? 'key';

        return self::cacheDir() . '/' . $safe . '.json';
    }
}
