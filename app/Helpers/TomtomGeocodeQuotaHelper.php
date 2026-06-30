<?php
namespace App\Helpers;

/**
 * TomTom geocode günlük kota takibi (dosya tabanlı).
 */
class TomtomGeocodeQuotaHelper {

    public const DAILY_LIMIT = 2500;

    private static function usageFilePath(): string {
        return ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tomtom_geocode_usage.json';
    }

    private static function todayKey(): string {
        return date('Y-m-d');
    }

    /**
     * @return array{date: string, count: int}
     */
    private static function readState(): array {
        $path = self::usageFilePath();
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
    private static function writeState(array $state): void {
        $dir = dirname(self::usageFilePath());
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        file_put_contents(
            self::usageFilePath(),
            json_encode($state, JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    public static function getTodayCount(): int {
        return self::readState()['count'];
    }

    public static function getRemainingToday(): int {
        return max(0, self::DAILY_LIMIT - self::getTodayCount());
    }

    public static function canMakeRequest(): bool {
        return self::getTodayCount() < self::DAILY_LIMIT;
    }

    public static function incrementToday(int $amount = 1): int {
        $amount = max(0, $amount);
        $state = self::readState();
        $state['count'] = min(self::DAILY_LIMIT, $state['count'] + $amount);
        self::writeState($state);
        return $state['count'];
    }

    /**
     * #__adrestablosu (kapı) coords alanına yeni veya değişmiş koordinat yazıldığında.
     * TomTom API çağrısı, hasta formu veya yönetim adres ekranından gelen tüm yazımlar tek yerden sayılır.
     */
    public static function recordKapinoCoordsPersisted(): void {
        self::incrementToday(1);
    }

    /**
     * @return array{date: string, used: int, limit: int, remaining: int}
     */
    public static function getSummary(): array {
        $used = self::getTodayCount();
        return [
            'date' => self::todayKey(),
            'used' => $used,
            'limit' => self::DAILY_LIMIT,
            'remaining' => max(0, self::DAILY_LIMIT - $used),
        ];
    }
}
