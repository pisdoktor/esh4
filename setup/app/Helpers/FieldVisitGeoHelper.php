<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Address;

/**
 * Saha izlem GPS — mesafe, geofence, doğruluk.
 */
final class FieldVisitGeoHelper
{
    private const EARTH_RADIUS_M = 6371000.0;

    /**
     * @return array{lat: float, lon: float}|null
     */
    public static function parseCoordsString(string $coords): ?array
    {
        $normalized = Address::normalizeCoordsString($coords);
        if ($normalized === '') {
            return null;
        }
        $parts = explode(',', $normalized, 2);
        if (count($parts) !== 2) {
            return null;
        }

        return ['lat' => (float) $parts[0], 'lon' => (float) $parts[1]];
    }

    /**
     * @return array{lat: float, lon: float}|null
     */
    public static function patientCoords(object $patient): ?array
    {
        return self::parseCoordsString(Address::resolveCoordsForPatient($patient));
    }

    public static function haversineDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos($lat1Rad) * cos($lat2Rad) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_M * $c;
    }

    /**
     * @return array{outside: bool, distance_m: float|null, patient_has_coords: bool}
     */
    public static function geofenceStatus(
        ?float $checkinLat,
        ?float $checkinLon,
        ?array $patientCoords,
        int $radiusM
    ): array {
        if ($checkinLat === null || $checkinLon === null) {
            return ['outside' => false, 'distance_m' => null, 'patient_has_coords' => false];
        }
        if ($patientCoords === null || $radiusM <= 0) {
            return [
                'outside' => false,
                'distance_m' => null,
                'patient_has_coords' => $patientCoords !== null,
            ];
        }

        $distance = self::haversineDistanceMeters(
            $checkinLat,
            $checkinLon,
            $patientCoords['lat'],
            $patientCoords['lon']
        );

        return [
            'outside' => $distance > (float) $radiusM,
            'distance_m' => round($distance, 1),
            'patient_has_coords' => true,
        ];
    }

    public static function isAccuracyAcceptable(?float $accuracyM, int $maxAccuracyM): bool
    {
        if ($accuracyM === null || $accuracyM <= 0) {
            return true;
        }
        if ($maxAccuracyM <= 0) {
            return true;
        }

        return $accuracyM <= (float) $maxAccuracyM;
    }

    /**
     * @param array<string, mixed> $checkin
     * @return array{lat: float, lon: float, accuracy: float|null}|null
     */
    public static function normalizeCheckinPayload(array $checkin): ?array
    {
        $lat = isset($checkin['checkin_lat']) && is_numeric($checkin['checkin_lat'])
            ? (float) $checkin['checkin_lat'] : null;
        $lon = isset($checkin['checkin_lon']) && is_numeric($checkin['checkin_lon'])
            ? (float) $checkin['checkin_lon'] : null;
        if ($lat === null || $lon === null || $lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return null;
        }
        $accuracy = null;
        if (isset($checkin['checkin_accuracy']) && is_numeric($checkin['checkin_accuracy'])) {
            $accuracy = max(0.0, (float) $checkin['checkin_accuracy']);
        }

        return [
            'lat' => round($lat, 7),
            'lon' => round($lon, 7),
            'accuracy' => $accuracy,
        ];
    }
}
