<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * @deprecated RoutingMatrixHelper kullanın.
 */
final class TomTomMatrixHelper
{
    /**
     * @param list<object> $hastalar
     * @return array<int, array{travelTimeInSeconds:int, lengthInMeters:int}>
     */
    public static function travelTimesFromOrigin(
        float $startLat,
        float $startLon,
        array $hastalar,
        string $apiKey,
        bool $useBatch = true
    ): array {
        unset($apiKey);

        return RoutingMatrixHelper::travelTimesFromOrigin($startLat, $startLon, $hastalar, null, $useBatch);
    }
}
