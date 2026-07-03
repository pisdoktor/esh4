<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Services\MapRouting\MapRoutingProviderFactory;

/**
 * Harita/rota AJAX uç noktaları için ortak yanıt üretimi.
 */
final class MapRoutingAjaxHelper
{
  /**
   * @return array{ok:bool,provider?:string,mapSdk?:string,key?:string,error?:string}
   */
    public static function mapConfigPayload(): array
    {
        $provider = MapRoutingProviderFactory::active();
        $config = $provider->mapClientConfig();
        if (empty($config['ok'])) {
            return ['ok' => false, 'error' => (string) ($config['error'] ?? 'not_configured')];
        }

        return $config;
    }

    /**
     * @param list<array{0:float|int,1:float|int}> $locations
   * @return array<string,mixed>
     */
    public static function routePayload(array $locations): array
    {
        return MapRoutingProviderFactory::active()->calculateRoute($locations);
    }

    /**
     * @return array<string,mixed>
     */
    public static function geocodePayload(string $query): array
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
     * mapConfig yanıtını eski tomtomMapKeyAjax formatına uyarlar.
     *
     * @return array{ok:bool,key?:string,provider?:string,mapSdk?:string,error?:string}
     */
    public static function legacyMapKeyPayload(): array
    {
        $config = self::mapConfigPayload();
        if (empty($config['ok'])) {
            return ['ok' => false, 'error' => (string) ($config['error'] ?? 'not_configured')];
        }

        return [
            'ok' => true,
            'key' => (string) ($config['key'] ?? ''),
            'provider' => (string) ($config['provider'] ?? ''),
            'mapSdk' => (string) ($config['mapSdk'] ?? ''),
        ];
    }
}
