<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Cache;
use App\Services\MapRouting\Contracts\MapRoutingProviderInterface;
use App\Services\MapRouting\MapRoutingProviderFactory;

/**
 * Aktif harita sağlayıcısı ile matrix / tekil rota süreleri (önbellekli).
 */
final class RoutingMatrixHelper
{
    private const BATCH_SIZE = 25;
    private const DEFAULT_TRAVEL_SEC = 900;
    private const DEFAULT_LENGTH_M = 5000;

    /**
     * @param list<object> $hastalar coords özelliği olan hasta nesneleri
     * @return array<int, array{travelTimeInSeconds:int, lengthInMeters:int}>
     */
    public static function travelTimesFromOrigin(
        float $startLat,
        float $startLon,
        array $hastalar,
        ?MapRoutingProviderInterface $provider = null,
        bool $useBatch = true
    ): array {
        $provider = $provider ?? MapRoutingProviderFactory::active();
        $providerCode = $provider->code();
        $results = [];
        $originStr = $startLat . ',' . $startLon;
        $cacheModel = new Cache();
        $pending = [];

        foreach ($hastalar as $k => $h) {
            $destStr = self::normalizeCoords((string) ($h->coords ?? ''));
            if ($destStr === '') {
                $results[$k] = self::defaultCell();
                continue;
            }
            $hash = md5($providerCode . '|' . $originStr . '|' . $destStr);
            $cache = $cacheModel->getCache($hash);
            if ($cache) {
                $results[$k] = [
                    'travelTimeInSeconds' => (int) $cache->sure,
                    'lengthInMeters' => (int) $cache->mesafe,
                ];
                continue;
            }
            $pending[$k] = $destStr;
        }

        if ($pending === []) {
            return $results;
        }

        if ($useBatch && $provider->isConfigured()) {
            self::fillViaMatrixBatch($provider, $startLat, $startLon, $originStr, $providerCode, $pending, $cacheModel, $results);
        }

        foreach ($pending as $k => $destStr) {
            if (isset($results[$k])) {
                continue;
            }
            $cell = self::fetchSingleRoute($provider, $startLat, $startLon, $destStr);
            if ($cell !== null) {
                $hash = md5($providerCode . '|' . $originStr . '|' . $destStr);
                $cacheModel->saveCache(
                    $hash,
                    $originStr,
                    $destStr,
                    $cell['travelTimeInSeconds'],
                    $cell['lengthInMeters']
                );
                $results[$k] = $cell;
            } else {
                $results[$k] = self::defaultCell();
            }
            usleep(50000);
        }

        return $results;
    }

    /**
     * @param array<int, string> $pending
     * @param array<int, array{travelTimeInSeconds:int, lengthInMeters:int}> $results
     */
    private static function fillViaMatrixBatch(
        MapRoutingProviderInterface $provider,
        float $startLat,
        float $startLon,
        string $originStr,
        string $providerCode,
        array &$pending,
        Cache $cacheModel,
        array &$results
    ): void {
        $chunks = array_chunk($pending, self::BATCH_SIZE, true);
        foreach ($chunks as $chunk) {
            $destPoints = [];
            $indexOrder = [];
            foreach ($chunk as $idx => $destStr) {
                $parsed = self::parseLatLon($destStr);
                if ($parsed === null) {
                    $results[$idx] = self::defaultCell();
                    unset($pending[$idx]);
                    continue;
                }
                $indexOrder[] = $idx;
                $destPoints[] = $parsed;
            }
            if ($destPoints === []) {
                continue;
            }

            $matrix = $provider->matrixFromOrigin($startLat, $startLon, $destPoints);
            if (!$matrix['ok'] || empty($matrix['cells'])) {
                continue;
            }

            foreach ($indexOrder as $i => $idx) {
                $cell = $matrix['cells'][$i] ?? null;
                if (!is_array($cell)) {
                    continue;
                }
                $destStr = $chunk[$idx];
                $hash = md5($providerCode . '|' . $originStr . '|' . $destStr);
                $cacheModel->saveCache(
                    $hash,
                    $originStr,
                    $destStr,
                    $cell['travelTimeInSeconds'],
                    $cell['lengthInMeters']
                );
                $results[$idx] = $cell;
                unset($pending[$idx]);
            }
        }
    }

    /**
     * @return array{travelTimeInSeconds:int, lengthInMeters:int}|null
     */
    private static function fetchSingleRoute(
        MapRoutingProviderInterface $provider,
        float $startLat,
        float $startLon,
        string $destStr
    ): ?array {
        if (!$provider->isConfigured()) {
            return null;
        }
        $dest = self::parseLatLon($destStr);
        if ($dest === null) {
            return null;
        }

        $route = $provider->calculateRoute([
            [$startLon, $startLat],
            [$dest[1], $dest[0]],
        ]);
        if (empty($route['ok'])) {
            return null;
        }
        $summary = is_array($route['summary'] ?? null) ? $route['summary'] : [];

        return [
            'travelTimeInSeconds' => (int) ($summary['travelTimeSeconds'] ?? self::DEFAULT_TRAVEL_SEC),
            'lengthInMeters' => (int) ($summary['distanceMeters'] ?? self::DEFAULT_LENGTH_M),
        ];
    }

    private static function normalizeCoords(string $coords): string
    {
        return str_replace(' ', '', trim($coords));
    }

    /**
     * @return array{0:float,1:float}|null [lat, lon]
     */
    private static function parseLatLon(string $coords): ?array
    {
        $coords = self::normalizeCoords($coords);
        if ($coords === '' || !str_contains($coords, ',')) {
            return null;
        }
        $parts = explode(',', $coords, 2);
        $lat = (float) trim($parts[0]);
        $lon = (float) trim($parts[1]);
        if (!is_finite($lat) || !is_finite($lon)) {
            return null;
        }

        return [$lat, $lon];
    }

    /** @return array{travelTimeInSeconds:int, lengthInMeters:int} */
    private static function defaultCell(): array
    {
        return [
            'travelTimeInSeconds' => self::DEFAULT_TRAVEL_SEC,
            'lengthInMeters' => self::DEFAULT_LENGTH_M,
        ];
    }
}
