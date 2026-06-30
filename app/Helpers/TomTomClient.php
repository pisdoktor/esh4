<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * TomTom REST API — anahtar yalnızca sunucu tarafında kullanılır.
 */
final class TomTomClient
{
    public static function apiKey(): string
    {
        return defined('TOMTOM_KEY') ? trim((string) TOMTOM_KEY) : '';
    }

    /**
     * @return array{ok:bool,results?:list<mixed>,error?:string,status?:int}
     */
    public static function geocode(string $query): array
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

        $response = self::httpGet($url);
        if ($response === null) {
            return ['ok' => false, 'error' => 'request_failed'];
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            return ['ok' => false, 'error' => 'invalid_response'];
        }

        return [
            'ok' => true,
            'results' => $data['results'] ?? [],
        ];
    }

    /**
     * Çoklu durak rota hesabı.
     *
     * @param list<array{0:float|int,1:float|int}> $lngLatPoints [lon, lat] çiftleri
     * @return array{ok:bool,route?:array<string,mixed>,error?:string}
     */
    public static function calculateRoute(array $lngLatPoints): array
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

        $response = self::httpGet($url);
        if ($response === null) {
            return ['ok' => false, 'error' => 'request_failed'];
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['routes'][0])) {
            return ['ok' => false, 'error' => 'no_route'];
        }

        return [
            'ok' => true,
            'route' => $data['routes'][0],
        ];
    }

    private static function httpGet(string $url): ?string
    {
        $ch = curl_init();
        if ($ch === false) {
            return null;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ESH/4.0 TomTomClient');
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !is_string($response) || $response === '') {
            return null;
        }

        return $response;
    }
}
