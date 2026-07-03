<?php

declare(strict_types=1);

namespace App\Services\MapRouting\Providers;

use App\Services\MapRouting\AbstractMapRoutingProvider;
use App\Services\MapRouting\PolylineHelper;

final class GoogleMapsProvider extends AbstractMapRoutingProvider
{
    public function code(): string
    {
        return 'google';
    }

    public function mapSdk(): string
    {
        return 'google';
    }

    public function isConfigured(): bool
    {
        return self::apiKey() !== '';
    }

    public static function apiKey(): string
    {
        return self::configKey('GOOGLE_MAPS_KEY', 'google_maps_key', 'GOOGLE_MAPS_KEY');
    }

    public function geocode(string $query): array
    {
        $apiKey = self::apiKey();
        $query = trim($query);
        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'not_configured'];
        }
        if ($query === '') {
            return ['ok' => false, 'error' => 'empty_query'];
        }

        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . rawurlencode($query)
            . '&key=' . rawurlencode($apiKey)
            . '&region=tr&language=tr';

        $response = self::httpGet($url, [], 'ESH/4.0 GoogleMapsProvider');
        if ($response === null) {
            return ['ok' => false, 'error' => 'request_failed'];
        }

        $data = json_decode($response, true);
        if (!is_array($data) || ($data['status'] ?? '') !== 'OK') {
            return ['ok' => false, 'error' => 'invalid_response'];
        }

        $results = [];
        foreach ($data['results'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $loc = $row['geometry']['location'] ?? null;
            if (!is_array($loc)) {
                continue;
            }
            $lat = (float) ($loc['lat'] ?? 0);
            $lon = (float) ($loc['lng'] ?? 0);
            $label = (string) ($row['formatted_address'] ?? $query);
            $results[] = ['lat' => $lat, 'lon' => $lon, 'label' => $label];
        }

        return ['ok' => true, 'results' => $results];
    }

    public function calculateRoute(array $lngLatPoints): array
    {
        $apiKey = self::apiKey();
        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'not_configured'];
        }
        if (count($lngLatPoints) < 2) {
            return ['ok' => false, 'error' => 'insufficient_points'];
        }

        $origin = $this->latLonFromPoint($lngLatPoints[0]);
        $destination = $this->latLonFromPoint($lngLatPoints[count($lngLatPoints) - 1]);
        if ($origin === null || $destination === null) {
            return ['ok' => false, 'error' => 'invalid_point'];
        }

        $waypoints = [];
        for ($i = 1, $n = count($lngLatPoints) - 1; $i < $n; $i++) {
            $wp = $this->latLonFromPoint($lngLatPoints[$i]);
            if ($wp === null) {
                return ['ok' => false, 'error' => 'invalid_point'];
            }
            $waypoints[] = $wp['lat'] . ',' . $wp['lon'];
        }

        $url = 'https://maps.googleapis.com/maps/api/directions/json'
            . '?origin=' . rawurlencode($origin['lat'] . ',' . $origin['lon'])
            . '&destination=' . rawurlencode($destination['lat'] . ',' . $destination['lon'])
            . '&mode=driving&key=' . rawurlencode($apiKey)
            . '&language=tr&region=tr';
        if ($waypoints !== []) {
            $url .= '&waypoints=' . rawurlencode(implode('|', $waypoints));
        }

        $response = self::httpGet($url, [], 'ESH/4.0 GoogleMapsProvider');
        if ($response === null) {
            return ['ok' => false, 'error' => 'request_failed'];
        }

        $data = json_decode($response, true);
        if (!is_array($data) || ($data['status'] ?? '') !== 'OK' || empty($data['routes'][0])) {
            return ['ok' => false, 'error' => 'no_route'];
        }

        $route = $data['routes'][0];
        $geometry = [];
        foreach ($route['legs'] ?? [] as $leg) {
            if (!is_array($leg)) {
                continue;
            }
            foreach ($leg['steps'] ?? [] as $step) {
                if (!is_array($step) || !isset($step['polyline']['points'])) {
                    continue;
                }
                $segment = PolylineHelper::decodeToLonLat((string) $step['polyline']['points']);
                if ($geometry !== [] && $segment !== []) {
                    array_shift($segment);
                }
                $geometry = array_merge($geometry, $segment);
            }
        }

        $travelSec = 0;
        $distanceM = 0;
        foreach ($route['legs'] ?? [] as $leg) {
            if (!is_array($leg)) {
                continue;
            }
            $travelSec += (int) ($leg['duration']['value'] ?? 0);
            $distanceM += (int) ($leg['distance']['value'] ?? 0);
        }

        return self::routeOk($geometry, $travelSec, $distanceM);
    }

    public function matrixFromOrigin(float $originLat, float $originLon, array $destLatLon): array
    {
        $apiKey = self::apiKey();
        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'not_configured'];
        }
        if ($destLatLon === []) {
            return ['ok' => false, 'error' => 'no_destinations'];
        }

        $destParts = [];
        foreach ($destLatLon as $pt) {
            if (!is_array($pt) || count($pt) < 2) {
                return ['ok' => false, 'error' => 'invalid_point'];
            }
            $destParts[] = (float) $pt[0] . ',' . (float) $pt[1];
        }

        $url = 'https://maps.googleapis.com/maps/api/distancematrix/json'
            . '?origins=' . rawurlencode($originLat . ',' . $originLon)
            . '&destinations=' . rawurlencode(implode('|', $destParts))
            . '&mode=driving&key=' . rawurlencode($apiKey)
            . '&language=tr&region=tr';

        $response = self::httpGet($url, [], 'ESH/4.0 GoogleMapsProvider');
        if ($response === null) {
            return ['ok' => false, 'error' => 'request_failed'];
        }

        $data = json_decode($response, true);
        if (!is_array($data) || ($data['status'] ?? '') !== 'OK') {
            return ['ok' => false, 'error' => 'invalid_response'];
        }

        $cells = [];
        foreach ($data['rows'][0]['elements'] ?? [] as $el) {
            if (!is_array($el) || ($el['status'] ?? '') !== 'OK') {
                $cells[] = self::defaultCell();
                continue;
            }
            $cells[] = [
                'travelTimeInSeconds' => (int) ($el['duration']['value'] ?? self::DEFAULT_TRAVEL_SEC),
                'lengthInMeters' => (int) ($el['distance']['value'] ?? self::DEFAULT_LENGTH_M),
            ];
        }

        return ['ok' => true, 'cells' => $cells];
    }

    public function mapClientConfig(): array
    {
        $key = self::apiKey();
        if ($key === '') {
            return ['ok' => false, 'error' => 'not_configured'];
        }

        return [
            'ok' => true,
            'provider' => $this->code(),
            'mapSdk' => $this->mapSdk(),
            'key' => $key,
        ];
    }

    /**
     * @param array{0:float|int,1:float|int} $pt
     * @return array{lat:float,lon:float}|null
     */
    private function latLonFromPoint(array $pt): ?array
    {
        if (count($pt) < 2) {
            return null;
        }
        $lon = (float) $pt[0];
        $lat = (float) $pt[1];
        if (!is_finite($lon) || !is_finite($lat)) {
            return null;
        }

        return ['lat' => $lat, 'lon' => $lon];
    }
}
