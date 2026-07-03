<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Services\MapRouting\MapRoutingProviderFactory;
use App\Services\MapRouting\Providers\TomTomMapRoutingProvider;

/**
 * TomTom REST API — geriye uyumluluk delegasyonu.
 * Aktif sağlayıcı OperationalSettings::mapProvider() ile belirlenir.
 */
final class TomTomClient
{
    public static function apiKey(): string
    {
        return TomTomMapRoutingProvider::apiKey();
    }

    /**
     * @return array{ok:bool,results?:list<mixed>,error?:string,status?:int}
     */
    public static function geocode(string $query): array
    {
        $provider = MapRoutingProviderFactory::active();
        $result = $provider->geocode($query);
        if (empty($result['ok'])) {
            return $result;
        }

        $legacy = [];
        foreach ($result['results'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $legacy[] = [
                'position' => [
                    'lat' => (float) ($row['lat'] ?? 0),
                    'lon' => (float) ($row['lon'] ?? 0),
                ],
                'address' => [
                    'freeformAddress' => (string) ($row['label'] ?? $query),
                ],
            ];
        }

        return ['ok' => true, 'results' => $legacy];
    }

    /**
     * @param list<array{0:float|int,1:float|int}> $lngLatPoints [lon, lat]
     * @return array{ok:bool,route?:array<string,mixed>,geometry?:list<array{0:float,1:float}>,summary?:array{travelTimeSeconds:int,distanceMeters:int},error?:string}
     */
    public static function calculateRoute(array $lngLatPoints): array
    {
        return MapRoutingProviderFactory::active()->calculateRoute($lngLatPoints);
    }

    /**
     * @param list<array{0:float,1:float}> $destLatLon [lat, lon]
     * @return array{ok:bool,cells?:list<array{travelTimeInSeconds:int,lengthInMeters:int}>,error?:string}
     */
    public static function matrixFromOrigin(float $originLat, float $originLon, array $destLatLon): array
    {
        return MapRoutingProviderFactory::active()->matrixFromOrigin($originLat, $originLon, $destLatLon);
    }
}
