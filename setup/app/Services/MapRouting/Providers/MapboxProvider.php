<?php

declare(strict_types=1);

namespace App\Services\MapRouting\Providers;

use App\Services\MapRouting\AbstractMapRoutingProvider;
use App\Services\MapRouting\PolylineHelper;

final class MapboxProvider extends AbstractMapRoutingProvider
{
    public function code(): string
    {
        return 'mapbox';
    }

    public function mapSdk(): string
    {
        return 'mapbox';
    }

    public function isConfigured(): bool
    {
        return self::apiKey() !== '';
    }

    public static function apiKey(): string
    {
        return self::configKey('MAPBOX_TOKEN', 'mapbox_token', 'MAPBOX_TOKEN');
    }

    public function geocode(string $query): array
    {
        $token = self::apiKey();
        $query = trim($query);
        if ($token === '') {
            return ['ok' => false, 'error' => 'not_configured'];
        }
        if ($query === '') {
            return ['ok' => false, 'error' => 'empty_query'];
        }

        $url = 'https://api.mapbox.com/geocoding/v5/mapbox.places/' . rawurlencode($query) . '.json'
            . '?access_token=' . rawurlencode($token)
            . '&country=tr&limit=1&language=tr';

        $response = self::httpGet($url, [], 'ESH/4.0 MapboxProvider');
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
            $center = $feat['center'] ?? null;
            if (!is_array($center) || count($center) < 2) {
                continue;
            }
            $lon = (float) $center[0];
            $lat = (float) $center[1];
            $label = (string) ($feat['place_name'] ?? $query);
            $results[] = ['lat' => $lat, 'lon' => $lon, 'label' => $label];
        }

        return ['ok' => true, 'results' => $results];
    }

    public function calculateRoute(array $lngLatPoints): array
    {
        $token = self::apiKey();
        if ($token === '') {
            return ['ok' => false, 'error' => 'not_configured'];
        }
        if (count($lngLatPoints) < 2) {
            return ['ok' => false, 'error' => 'insufficient_points'];
        }

        $parts = [];
        foreach ($lngLatPoints as $pt) {
            if (!is_array($pt) || count($pt) < 2) {
                return ['ok' => false, 'error' => 'invalid_point'];
            }
            $parts[] = (float) $pt[0] . ',' . (float) $pt[1];
        }

        $coordPath = implode(';', $parts);
        $url = 'https://api.mapbox.com/directions/v5/mapbox/driving-traffic/' . $coordPath
            . '?geometries=geojson&overview=full&access_token=' . rawurlencode($token);

        $response = self::httpGet($url, [], 'ESH/4.0 MapboxProvider');
        if ($response === null) {
            return ['ok' => false, 'error' => 'request_failed'];
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['routes'][0])) {
            return ['ok' => false, 'error' => 'no_route'];
        }

        $route = $data['routes'][0];
        $geometry = PolylineHelper::geoJsonCoordsToLonLat(
            is_array($route['geometry']['coordinates'] ?? null) ? $route['geometry']['coordinates'] : []
        );

        return self::routeOk(
            $geometry,
            (int) round((float) ($route['duration'] ?? self::DEFAULT_TRAVEL_SEC)),
            (int) round((float) ($route['distance'] ?? self::DEFAULT_LENGTH_M))
        );
    }

    public function matrixFromOrigin(float $originLat, float $originLon, array $destLatLon): array
    {
        $token = self::apiKey();
        if ($token === '') {
            return ['ok' => false, 'error' => 'not_configured'];
        }
        if ($destLatLon === []) {
            return ['ok' => false, 'error' => 'no_destinations'];
        }

        $parts = [$originLon . ',' . $originLat];
        foreach ($destLatLon as $pt) {
            if (!is_array($pt) || count($pt) < 2) {
                return ['ok' => false, 'error' => 'invalid_point'];
            }
            $parts[] = (float) $pt[1] . ',' . (float) $pt[0];
        }

        $coordPath = implode(';', $parts);
        $sources = '0';
        $destinations = implode(';', range(1, count($parts) - 1));
        $url = 'https://api.mapbox.com/directions-matrix/v1/mapbox/driving-traffic/' . $coordPath
            . '?sources=' . $sources
            . '&destinations=' . $destinations
            . '&annotations=duration,distance'
            . '&access_token=' . rawurlencode($token);

        $response = self::httpGet($url, [], 'ESH/4.0 MapboxProvider');
        if ($response === null) {
            return ['ok' => false, 'error' => 'request_failed'];
        }

        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['durations'][0]) || !is_array($data['durations'][0])) {
            return ['ok' => false, 'error' => 'invalid_response'];
        }

        $cells = [];
        $distRow = is_array($data['distances'][0] ?? null) ? $data['distances'][0] : [];
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
        $token = self::apiKey();
        if ($token === '') {
            return ['ok' => false, 'error' => 'not_configured'];
        }

        return [
            'ok' => true,
            'provider' => $this->code(),
            'mapSdk' => $this->mapSdk(),
            'key' => $token,
        ];
    }
}
