<?php

declare(strict_types=1);

namespace App\Services\MapRouting\Contracts;

/**
 * Harita/rota sağlayıcı sözleşmesi — geocode, rota ve matrix.
 */
interface MapRoutingProviderInterface
{
    public function code(): string;

    /** Harita SDK kodu: tomtom | maplibre_osm | mapbox | google */
    public function mapSdk(): string;

    public function isConfigured(): bool;

    /**
     * @return array{ok:bool,results?:list<array{lat:float,lon:float,label:string}>,error?:string}
     */
    public function geocode(string $query): array;

    /**
     * @param list<array{0:float|int,1:float|int}> $lngLatPoints [lon, lat]
     * @return array{ok:bool,geometry?:list<array{0:float,1:float}>,summary?:array{travelTimeSeconds:int,distanceMeters:int},route?:array<string,mixed>,error?:string}
     */
    public function calculateRoute(array $lngLatPoints): array;

    /**
     * @param list<array{0:float,1:float}> $destLatLon [lat, lon]
     * @return array{ok:bool,cells?:list<array{travelTimeInSeconds:int,lengthInMeters:int}>,error?:string}
     */
    public function matrixFromOrigin(float $originLat, float $originLon, array $destLatLon): array;

    /**
     * Harita istemcisi için yapılandırma (anahtar/token proxy).
     *
     * @return array{ok:bool,provider?:string,mapSdk?:string,key?:string,error?:string}
     */
    public function mapClientConfig(): array;
}
