<?php

declare(strict_types=1);

namespace App\Services\MapRouting;

use App\Services\MapRouting\Contracts\MapRoutingProviderInterface;

abstract class AbstractMapRoutingProvider implements MapRoutingProviderInterface
{
    protected const DEFAULT_TRAVEL_SEC = 900;
    protected const DEFAULT_LENGTH_M = 5000;

    protected static function httpGet(string $url, array $headers = [], string $userAgent = 'ESH/4.0 MapRouting'): ?string
    {
        $ch = curl_init();
        if ($ch === false) {
            return null;
        }
        $hdr = $headers !== [] ? $headers : ['Accept: application/json'];
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $hdr);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !is_string($response) || $response === '') {
            return null;
        }

        return $response;
    }

    protected static function httpPost(string $url, string $body, array $headers = [], string $userAgent = 'ESH/4.0 MapRouting'): ?string
    {
        $ch = curl_init();
        if ($ch === false) {
            return null;
        }
        $hdr = array_merge(['Content-Type: application/json', 'Accept: application/json'], $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $hdr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !is_string($response) || $response === '') {
            return null;
        }

        return $response;
    }

    /** @return array{travelTimeInSeconds:int,lengthInMeters:int} */
    protected static function defaultCell(): array
    {
        return [
            'travelTimeInSeconds' => self::DEFAULT_TRAVEL_SEC,
            'lengthInMeters' => self::DEFAULT_LENGTH_M,
        ];
    }

    /**
     * @param list<array{0:float,1:float}> $geometry [lon,lat]
     * @return array{ok:bool,geometry:list<array{0:float,1:float}>,summary:array{travelTimeSeconds:int,distanceMeters:int}}
     */
    protected static function routeOk(array $geometry, int $travelSec, int $distanceM, ?array $legacyRoute = null): array
    {
        $out = [
            'ok' => true,
            'geometry' => $geometry,
            'summary' => [
                'travelTimeSeconds' => max(0, $travelSec),
                'distanceMeters' => max(0, $distanceM),
            ],
        ];
        if ($legacyRoute !== null) {
            $out['route'] = $legacyRoute;
        }

        return $out;
    }

    protected static function configKey(string $envName, string $localKey, ?string $constant = null): string
    {
        $env = getenv($envName);
        if (is_string($env) && trim($env) !== '') {
            return trim($env);
        }
        if (function_exists('esh_config_local')) {
            $local = esh_config_local($localKey, null);
            if (is_string($local) && trim($local) !== '') {
                return trim($local);
            }
        }
        if ($constant !== null && defined($constant)) {
            $v = trim((string) constant($constant));
            if ($v !== '') {
                return $v;
            }
        }

        return '';
    }
}
