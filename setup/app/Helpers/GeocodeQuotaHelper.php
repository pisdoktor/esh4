<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Services\MapRouting\MapRoutingProviderFactory;

/**
 * Geocode günlük kota takibi (dosya tabanlı, sağlayıcı başına).
 */
final class GeocodeQuotaHelper
{
    public const DAILY_LIMIT = 2500;

    private static function usageFilePath(string $providerCode): string
    {
        $code = MapRoutingProviderFactory::normalizeCode($providerCode);

        return ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'geocode_usage_' . $code . '.json';
    }

    private static function todayKey(): string
    {
        return date('Y-m-d');
    }

    /**
     * @return array{date: string, count: int}
     */
    private static function readState(string $providerCode): array
    {
        $path = self::usageFilePath($providerCode);
        $today = self::todayKey();
        if (!is_file($path)) {
            return ['date' => $today, 'count' => 0];
        }
        $raw = (string) file_get_contents($path);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['date' => $today, 'count' => 0];
        }
        $date = isset($data['date']) ? (string) $data['date'] : $today;
        $count = isset($data['count']) ? (int) $data['count'] : 0;
        if ($date !== $today) {
            return ['date' => $today, 'count' => 0];
        }

        return ['date' => $today, 'count' => max(0, $count)];
    }

    /**
     * @param array{date: string, count: int} $state
     */
    private static function writeState(string $providerCode, array $state): void
    {
        $path = self::usageFilePath($providerCode);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        file_put_contents(
            $path,
            json_encode($state, JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    public static function getTodayCount(?string $providerCode = null): int
    {
        $providerCode = $providerCode ?? MapRoutingProviderFactory::activeCode();

        return self::readState($providerCode)['count'];
    }

    public static function getRemainingToday(?string $providerCode = null): int
    {
        return max(0, self::DAILY_LIMIT - self::getTodayCount($providerCode));
    }

    public static function canMakeRequest(?string $providerCode = null): bool
    {
        return self::getTodayCount($providerCode) < self::DAILY_LIMIT;
    }

    public static function incrementToday(int $amount = 1, ?string $providerCode = null): int
    {
        $providerCode = $providerCode ?? MapRoutingProviderFactory::activeCode();
        $amount = max(0, $amount);
        $state = self::readState($providerCode);
        $state['count'] = min(self::DAILY_LIMIT, $state['count'] + $amount);
        self::writeState($providerCode, $state);

        return $state['count'];
    }

    public static function recordKapinoCoordsPersisted(?string $providerCode = null): void
    {
        self::incrementToday(1, $providerCode);
    }

    /**
     * @return array{date: string, used: int, limit: int, remaining: int, provider: string, provider_label: string}
     */
    public static function getSummary(?string $providerCode = null): array
    {
        $providerCode = $providerCode ?? MapRoutingProviderFactory::activeCode();
        $used = self::getTodayCount($providerCode);

        return [
            'date' => self::todayKey(),
            'used' => $used,
            'limit' => self::DAILY_LIMIT,
            'remaining' => max(0, self::DAILY_LIMIT - $used),
            'provider' => $providerCode,
            'provider_label' => MapRoutingProviderFactory::LABELS[$providerCode] ?? $providerCode,
        ];
    }
}
