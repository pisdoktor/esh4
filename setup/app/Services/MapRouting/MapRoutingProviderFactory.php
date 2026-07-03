<?php

declare(strict_types=1);

namespace App\Services\MapRouting;

use App\Helpers\OperationalSettings;
use App\Services\MapRouting\Contracts\MapRoutingProviderInterface;
use App\Services\MapRouting\Providers\GoogleMapsProvider;
use App\Services\MapRouting\Providers\MapboxProvider;
use App\Services\MapRouting\Providers\OpenRouteServiceProvider;
use App\Services\MapRouting\Providers\TomTomMapRoutingProvider;

final class MapRoutingProviderFactory
{
  /** @var list<string> */
    public const SUPPORTED = ['tomtom', 'openrouteservice', 'mapbox', 'google'];

    /** @var array<string, string> */
    public const LABELS = [
        'tomtom' => 'TomTom',
        'openrouteservice' => 'OpenRouteService',
        'mapbox' => 'Mapbox',
        'google' => 'Google Maps',
    ];

    public static function activeCode(): string
    {
        return OperationalSettings::mapProvider();
    }

    public static function active(): MapRoutingProviderInterface
    {
        return self::create(self::activeCode());
    }

    public static function create(?string $code = null): MapRoutingProviderInterface
    {
        $code = self::normalizeCode($code ?? self::activeCode());

        return match ($code) {
            'openrouteservice' => new OpenRouteServiceProvider(),
            'mapbox' => new MapboxProvider(),
            'google' => new GoogleMapsProvider(),
            default => new TomTomMapRoutingProvider(),
        };
    }

    public static function normalizeCode(string $code): string
    {
        $code = strtolower(trim($code));

        return in_array($code, self::SUPPORTED, true) ? $code : 'tomtom';
    }

    /**
     * @return array{configured:bool,masked:string,source:string,config_key:string,env_key:string}
     */
    public static function keyStatusForProvider(string $code): array
    {
        $code = self::normalizeCode($code);
        $meta = match ($code) {
            'openrouteservice' => [
                'env' => 'OPENROUTESERVICE_KEY',
                'local' => 'openrouteservice_key',
                'const' => 'OPENROUTESERVICE_KEY',
                'config_key' => 'openrouteservice_key',
                'env_key' => 'OPENROUTESERVICE_KEY',
            ],
            'mapbox' => [
                'env' => 'MAPBOX_TOKEN',
                'local' => 'mapbox_token',
                'const' => 'MAPBOX_TOKEN',
                'config_key' => 'mapbox_token',
                'env_key' => 'MAPBOX_TOKEN',
            ],
            'google' => [
                'env' => 'GOOGLE_MAPS_KEY',
                'local' => 'google_maps_key',
                'const' => 'GOOGLE_MAPS_KEY',
                'config_key' => 'google_maps_key',
                'env_key' => 'GOOGLE_MAPS_KEY',
            ],
            default => [
                'env' => 'TOMTOM_KEY',
                'local' => 'tomtom_key',
                'const' => 'TOMTOM_KEY',
                'config_key' => 'tomtom_key',
                'env_key' => 'TOMTOM_KEY',
            ],
        };

        $key = '';
        $source = '';
        $env = getenv($meta['env']);
        if (is_string($env) && trim($env) !== '') {
            $key = trim($env);
            $source = 'Ortam değişkeni ' . $meta['env'];
        } elseif (function_exists('esh_config_local')) {
            $local = esh_config_local($meta['local'], null);
            if (is_string($local) && trim($local) !== '') {
                $key = trim($local);
                $source = 'config.local.php → ' . $meta['local'];
            }
        }
        if ($key === '' && defined($meta['const'])) {
            $key = trim((string) constant($meta['const']));
            if ($key !== '' && $source === '') {
                $source = 'Yapılandırma (define ' . $meta['const'] . ')';
            }
        }

        if ($key === '') {
            return [
                'configured' => false,
                'masked' => '',
                'source' => '',
                'config_key' => $meta['config_key'],
                'env_key' => $meta['env_key'],
            ];
        }

        $len = strlen($key);

        return [
            'configured' => true,
            'masked' => $len <= 8 ? str_repeat('•', $len) : substr($key, 0, 4) . str_repeat('•', max(4, $len - 8)) . substr($key, -4),
            'source' => $source,
            'config_key' => $meta['config_key'],
            'env_key' => $meta['env_key'],
        ];
    }

    /**
     * @return list<array{code:string,label:string,configured:bool,masked:string,source:string,config_key:string,env_key:string}>
     */
    public static function allProviderStatusesForAdmin(): array
    {
        $rows = [];
        foreach (self::SUPPORTED as $code) {
            $status = self::keyStatusForProvider($code);
            $rows[] = [
                'code' => $code,
                'label' => self::LABELS[$code] ?? $code,
                'configured' => (bool) $status['configured'],
                'masked' => (string) $status['masked'],
                'source' => (string) $status['source'],
                'config_key' => (string) $status['config_key'],
                'env_key' => (string) $status['env_key'],
            ];
        }

        return $rows;
    }
}
