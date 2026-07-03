<?php
namespace App\Helpers;

/**
 * @deprecated GeocodeQuotaHelper kullanın.
 */
class TomtomGeocodeQuotaHelper {

    public const DAILY_LIMIT = GeocodeQuotaHelper::DAILY_LIMIT;

    public static function getTodayCount(): int {
        return GeocodeQuotaHelper::getTodayCount();
    }

    public static function getRemainingToday(): int {
        return GeocodeQuotaHelper::getRemainingToday();
    }

    public static function canMakeRequest(): bool {
        return GeocodeQuotaHelper::canMakeRequest();
    }

    public static function incrementToday(int $amount = 1): int {
        return GeocodeQuotaHelper::incrementToday($amount);
    }

    public static function recordKapinoCoordsPersisted(): void {
        GeocodeQuotaHelper::recordKapinoCoordsPersisted();
    }

    /**
     * @return array{date: string, used: int, limit: int, remaining: int}
     */
    public static function getSummary(): array {
        $s = GeocodeQuotaHelper::getSummary();
        return [
            'date' => $s['date'],
            'used' => $s['used'],
            'limit' => $s['limit'],
            'remaining' => $s['remaining'],
        ];
    }
}
