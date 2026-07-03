<?php

declare(strict_types=1);

namespace App\Services\MapRouting\Providers;

use App\Services\MapRouting\AbstractMapRoutingProvider;
use App\Services\MapRouting\PolylineHelper;

final class OpenRouteServiceProvider extends AbstractMapRoutingProvider
{
    public function code(): string
    {
        return 'openrouteservice';
    }

    public function mapSdk(): string
    {
        return 'maplibre_osm';
    }

    public function isConfigured(): bool
    {
        return self::apiKey() !== '';
    }

    public static function apiKey(): string
    {
        return self::configKey('OPENROUTESERVICE_KEY', 'openrouteservice_key', 'OPENROUTESERVICE_KEY');
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

        $url = 'https://api.openrouteservice.org/geocode/search?api_key=' . rawurlencode($apiKey)
            . '&text=' . rawurlencode($query)
            . '&size=1&boundary.country=TR';

        $response = self::httpGet($url, [], 'ESH/4.0 ORSProvider');
        if ($response === null) {
            return ['ok' => false, 'error' => 'request_failed'];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'invalid_response'];
        }

        $results = [];
        foreach ($data['features'] ?? [] as $feat) {
            if (!is_array($feat)) {
                continue;
            }
            $coords = $feat['geometry']['coordinates'] ?? null;
            if (!is_array($coords) || count($coords) < 2) {
                continue;
            }
            $lon = (float) $coords[0];
            $lat = (float) $coords[1];
            $label = (string) ($feat['properties']['label'] ?? $query);
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

        $coordinates = [];
        foreach ($lngLatPoints as $pt) {
            if (!is_array($pt) || count($pt) < 2) {
                return ['ok' => false, 'error' => 'invalid_point'];
            }
            $coordinates[] = [(float) $pt[0], (float) $pt[1]];
        }

        $payload = json_encode(['coordinates' => $coordinates], JSON_UNESCAPED_UNICODE);
        if (!is_string($payload)) {
            return ['ok' => false, 'error' => 'encode_failed'];
        }

        $url = 'https://api.openrouteservice.org/v2/directions/driving-car/geojson';
        $response = self::httpPost($url, $payload, [
            'Authorization: ' . $apiKey,
        ], 'ESH/4.0 ORSProvider');
        if ($response === null) {
            return ['ok' => false, 'error' => 'request_failed'];
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['features'][0])) {
            return ['ok' => false, 'error' => 'no_route'];
        }

        $feat = $data['features'][0];
        $geometry = PolylineHelper::geoJsonCoordsToLonLat(
            is_array($feat['geometry']['coordinates'] ?? null) ? $feat['geometry']['coordinates'] : []
        );
        $summary = is_array($feat['properties']['summary'] ?? null) ? $feat['properties']['summary'] : [];

        return self::routeOk(
            $geometry,
            (int) round((float) ($summary['duration'] ?? self::DEFAULT_TRAVEL_SEC)),
            (int) round((float) ($summary['distance'] ?? self::DEFAULT_LENGTH_M))
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

        $locations = [[$originLon, $originLat]];
        foreach ($destLatLon as $pt) {
            if (!is_array($pt) || count($pt) < 2) {
                return ['ok' => false, 'error' => 'invalid_point'];
            }
            $locations[] = [(float) $pt[1], (float) $pt[0]];
        }

        $payload = json_encode([
            'locations' => $locations,
            'sources' => [0],
            'destinations' => range(1, count($locations) - 1),
            'metrics' => ['duration', 'distance'],
        ], JSON_UNESCAPED_UNICODE);
        if (!is_string($payload)) {
            return ['ok' => false, 'error' => 'encode_failed'];
        }

        $url = 'https://api.openrouteservice.org/v2/matrix/driving-car';
        $response = self::httpPost($url, $payload, [
            'Authorization: ' . $apiKey,
        ], 'ESH/4.0 ORSProvider');
        if ($response === null) {
            return ['ok' => false, 'error' => 'request_failed'];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'invalid_response'];
        }

        $durations = $data['durations'][0] ?? null;
        $distances = $data['distances'][0] ?? null;
        if (!is_array($durations)) {
            return ['ok' => false, 'error' => 'invalid_response'];
        }

        $cells = [];
        $distRow = is_array($distances) ? $distances : [];
        foreach ($durations as $i => $dur) {
            if ($dur === null || !is_numeric($dur)) {
                $cells[] = self::defaultCell();
                continue;
            }
            $dist = $distRow[$i] ?? self::DEFAULT_LENGTH_M;
            $cells[] = [
                'travelTimeInSeconds' => (int) round((float) $dur),
                'lengthInMeters' => (int) round((float) $dist),
            ];
        }

        return ['ok' => true, 'cells' => $cells];
    }

    public function mapClientConfig(): array
    {
        if (!self::apiKey()) {
            return ['ok' => false, 'error' => 'not_configured'];
        }

        return [
            'ok' => true,
            'provider' => $this->code(),
            'mapSdk' => $this->mapSdk(),
            'key' => '',
        ];
    }
}
