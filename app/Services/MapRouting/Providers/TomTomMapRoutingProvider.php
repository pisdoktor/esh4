<?php

declare(strict_types=1);

namespace App\Services\MapRouting\Providers;

use App\Services\MapRouting\AbstractMapRoutingProvider;
use App\Services\MapRouting\PolylineHelper;

final class TomTomMapRoutingProvider extends AbstractMapRoutingProvider
{
    public function code(): string
    {
        return 'tomtom';
    }

    public function mapSdk(): string
    {
        return 'tomtom';
    }

    public function isConfigured(): bool
    {
        return self::apiKey() !== '';
    }

    public static function apiKey(): string
    {
        return self::configKey('TOMTOM_KEY', 'tomtom_key', 'TOMTOM_KEY');
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

        $url = 'https://api.tomtom.com/search/2/geocode/' . rawurlencode($query) . '.json'
            . '?key=' . rawurlencode($apiKey)
            . '&limit=1&language=tr-TR&countrySet=TR';

        $response = self::httpGet($url, [], 'ESH/4.0 TomTomProvider');
        if ($response === null) {
            return ['ok' => false, 'error' => 'request_failed'];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'invalid_response'];
        }

        $results = [];
        foreach ($data['results'] ?? [] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $pos = $row['position'] ?? null;
            if (!is_array($pos)) {
                continue;
            }
            $lat = (float) ($pos['lat'] ?? 0);
            $lon = (float) ($pos['lon'] ?? 0);
            if (!is_finite($lat) || !is_finite($lon)) {
                continue;
            }
            $label = (string) ($row['address']['freeformAddress'] ?? $query);
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

        $segments = [];
        foreach ($lngLatPoints as $pt) {
            if (!is_array($pt) || count($pt) < 2) {
                return ['ok' => false, 'error' => 'invalid_point'];
            }
            $lon = (float) $pt[0];
            $lat = (float) $pt[1];
            if (!is_finite($lon) || !is_finite($lat)) {
                return ['ok' => false, 'error' => 'invalid_point'];
            }
            $segments[] = $lat . ',' . $lon;
        }

        $path = implode(':', $segments);
        $url = 'https://api.tomtom.com/routing/1/calculateRoute/' . $path . '/json'
            . '?key=' . rawurlencode($apiKey)
            . '&travelMode=car&traffic=true';

        $response = self::httpGet($url, [], 'ESH/4.0 TomTomProvider');
        if ($response === null) {
            return ['ok' => false, 'error' => 'request_failed'];
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['routes'][0])) {
            return ['ok' => false, 'error' => 'no_route'];
        }

        $route = $data['routes'][0];
        $geometry = [];
        foreach ($route['legs'] ?? [] as $leg) {
            if (!is_array($leg)) {
                continue;
            }
            foreach ($leg['points'] ?? [] as $p) {
                if (!is_array($p)) {
                    continue;
                }
                $geometry[] = [(float) ($p['longitude'] ?? 0), (float) ($p['latitude'] ?? 0)];
            }
        }

        $summary = is_array($route['summary'] ?? null) ? $route['summary'] : [];

        return self::routeOk(
            $geometry,
            (int) ($summary['travelTimeInSeconds'] ?? self::DEFAULT_TRAVEL_SEC),
            (int) ($summary['lengthInMeters'] ?? self::DEFAULT_LENGTH_M),
            $route
        );
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

        $destinations = [];
        foreach ($destLatLon as $pt) {
            if (!is_array($pt) || count($pt) < 2) {
                return ['ok' => false, 'error' => 'invalid_point'];
            }
            $lat = (float) $pt[0];
            $lon = (float) $pt[1];
            if (!is_finite($lat) || !is_finite($lon)) {
                return ['ok' => false, 'error' => 'invalid_point'];
            }
            $destinations[] = ['point' => ['latitude' => $lat, 'longitude' => $lon]];
        }

        $payload = json_encode([
            'origins' => [['point' => ['latitude' => $originLat, 'longitude' => $originLon]]],
            'destinations' => $destinations,
            'options' => ['travelMode' => 'car', 'traffic' => 'true'],
        ], JSON_UNESCAPED_UNICODE);

        if (!is_string($payload)) {
            return ['ok' => false, 'error' => 'encode_failed'];
        }

        $url = 'https://api.tomtom.com/routing/1/matrix/sync/json?key=' . rawurlencode($apiKey);
        $response = self::httpPost($url, $payload, [], 'ESH/4.0 TomTomProvider');
        if ($response === null) {
            return ['ok' => false, 'error' => 'request_failed'];
        }

        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['matrix'][0]) || !is_array($data['matrix'][0])) {
            return ['ok' => false, 'error' => 'invalid_response'];
        }

        $cells = [];
        foreach ($data['matrix'][0] as $cell) {
            if (!is_array($cell)) {
                $cells[] = self::defaultCell();
                continue;
            }
            $summary = $cell['response']['routeSummary'] ?? $cell['routeSummary'] ?? null;
            if (!is_array($summary)) {
                $cells[] = self::defaultCell();
                continue;
            }
            $cells[] = [
                'travelTimeInSeconds' => (int) ($summary['travelTimeInSeconds'] ?? self::DEFAULT_TRAVEL_SEC),
                'lengthInMeters' => (int) ($summary['lengthInMeters'] ?? self::DEFAULT_LENGTH_M),
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
}
