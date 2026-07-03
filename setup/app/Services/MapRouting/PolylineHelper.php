<?php

declare(strict_types=1);

namespace App\Services\MapRouting;

/**
 * Google/Mapbox encoded polyline çözümü.
 */
final class PolylineHelper
{
    /**
     * @return list<array{0:float,1:float}> [lon, lat]
     */
    public static function decodeToLonLat(string $encoded, int $precision = 5): array
    {
        $factor = 10 ** $precision;
        $len = strlen($encoded);
        $index = 0;
        $lat = 0;
        $lng = 0;
        $coords = [];

        while ($index < $len) {
            $result = 1;
            $shift = 0;
            do {
                if ($index >= $len) {
                    break 2;
                }
                $b = ord($encoded[$index++]) - 63;
                $result += ($b & 0x1f) << $shift;
                $shift += 5;
            } while ($b >= 0x20);
            $dlat = ($result & 1) ? ~(($result >> 1)) : ($result >> 1);
            $lat += $dlat;

            $result = 1;
            $shift = 0;
            do {
                if ($index >= $len) {
                    break 2;
                }
                $b = ord($encoded[$index++]) - 63;
                $result += ($b & 0x1f) << $shift;
                $shift += 5;
            } while ($b >= 0x20);
            $dlng = ($result & 1) ? ~(($result >> 1)) : ($result >> 1);
            $lng += $dlng;

            $coords[] = [round($lng / $factor, 6), round($lat / $factor, 6)];
        }

        return $coords;
    }

    /**
     * GeoJSON koordinat dizisinden [lon,lat] listesi.
     *
     * @param list<mixed> $coords
     * @return list<array{0:float,1:float}>
     */
    public static function geoJsonCoordsToLonLat(array $coords): array
    {
        $out = [];
        foreach ($coords as $pt) {
            if (!is_array($pt) || count($pt) < 2) {
                continue;
            }
            $lon = (float) $pt[0];
            $lat = (float) $pt[1];
            if (!is_finite($lon) || !is_finite($lat)) {
                continue;
            }
            $out[] = [$lon, $lat];
        }

        return $out;
    }
}
