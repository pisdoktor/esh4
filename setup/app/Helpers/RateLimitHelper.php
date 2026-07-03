<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Dosya tabanlı basit rate limit (brute-force / enumeration koruması).
 */
final class RateLimitHelper
{
    private static function storageDir(): string
    {
        $dir = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2)) . '/storage/rate_limit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }

        return $dir;
    }

    private static function safeKey(string $bucket, string $key): string
    {
        $bucket = preg_replace('/[^a-z0-9_-]/i', '_', $bucket) ?: 'default';
        $hash = hash('sha256', $bucket . '|' . $key);

        return $bucket . '_' . $hash;
    }

    private static function filePath(string $bucket, string $key): string
    {
        return self::storageDir() . DIRECTORY_SEPARATOR . self::safeKey($bucket, $key) . '.json';
    }

    /**
     * @return array{count: int, first_at: int}
     */
    private static function readState(string $path, int $windowSeconds): array
    {
        if (!is_file($path)) {
            return ['count' => 0, 'first_at' => time()];
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return ['count' => 0, 'first_at' => time()];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['count' => 0, 'first_at' => time()];
        }
        $firstAt = (int) ($data['first_at'] ?? 0);
        $count = (int) ($data['count'] ?? 0);
        if ($firstAt <= 0 || (time() - $firstAt) >= $windowSeconds) {
            return ['count' => 0, 'first_at' => time()];
        }

        return ['count' => max(0, $count), 'first_at' => $firstAt];
    }

    /**
     * @param array{count: int, first_at: int} $state
     */
    private static function writeState(string $path, array $state): void
    {
        @file_put_contents($path, json_encode($state), LOCK_EX);
    }

    public static function tooManyAttempts(string $bucket, string $key, int $maxAttempts, int $windowSeconds): bool
    {
        if ($maxAttempts < 1 || $windowSeconds < 1 || $key === '') {
            return false;
        }
        $path = self::filePath($bucket, $key);
        $state = self::readState($path, $windowSeconds);

        return $state['count'] >= $maxAttempts;
    }

    public static function hit(string $bucket, string $key, int $windowSeconds): void
    {
        if ($windowSeconds < 1 || $key === '') {
            return;
        }
        $path = self::filePath($bucket, $key);
        $state = self::readState($path, $windowSeconds);
        if ($state['count'] === 0) {
            $state['first_at'] = time();
        }
        $state['count']++;
        self::writeState($path, $state);
    }

    public static function clear(string $bucket, string $key): void
    {
        $path = self::filePath($bucket, $key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function retryAfterSeconds(string $bucket, string $key, int $windowSeconds): int
    {
        $path = self::filePath($bucket, $key);
        $state = self::readState($path, $windowSeconds);
        if ($state['count'] === 0) {
            return 0;
        }
        $elapsed = time() - $state['first_at'];

        return max(0, $windowSeconds - $elapsed);
    }

    public static function clientIp(): string
    {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return 'unknown';
    }
}
