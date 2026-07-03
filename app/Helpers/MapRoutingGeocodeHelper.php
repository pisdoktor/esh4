<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Services\MapRouting\MapRoutingProviderFactory;

/**
 * Aktif harita sağlayıcısı ile geocode yardımcıları.
 */
final class MapRoutingGeocodeHelper
{
    /**
     * @return array{lat: float, lon: float}|null
     */
    public static function firstPosition(string $addressQuery): ?array
    {
        $addressQuery = trim($addressQuery);
        if ($addressQuery === '') {
            return null;
        }

        $provider = MapRoutingProviderFactory::active();
        if (!$provider->isConfigured()) {
            return null;
        }

        $result = $provider->geocode($addressQuery);
        if (empty($result['ok']) || empty($result['results'][0])) {
            return null;
        }

        $row = $result['results'][0];
        if (!is_array($row)) {
            return null;
        }

        $lat = (float) ($row['lat'] ?? 0);
        $lon = (float) ($row['lon'] ?? 0);
        if (!is_finite($lat) || !is_finite($lon)) {
            return null;
        }

        return ['lat' => $lat, 'lon' => $lon];
    }

    public static function isActiveProviderConfigured(): bool
    {
        return MapRoutingProviderFactory::active()->isConfigured();
    }
}
