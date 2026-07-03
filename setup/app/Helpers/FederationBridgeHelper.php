<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\FederationRegion;
use App\Models\Kurum;

/**
 * Federasyon JSON paket doğrulama.
 */
final class FederationBridgeHelper
{
    public const BUNDLE_VERSION = 1;

    public static function isReady(): bool
    {
        return FederationHelper::isReady();
    }

    /**
     * @param array<string, mixed> $bundle
     * @return array{ok:bool,error?:string,direction?:string}
     */
    public static function validateImportBundle(array $bundle): array
    {
        $version = (int) ($bundle['bundle_version'] ?? 0);
        if ($version !== self::BUNDLE_VERSION) {
            return ['ok' => false, 'error' => 'Desteklenmeyen paket sürümü.'];
        }
        $direction = strtolower(trim((string) ($bundle['direction'] ?? '')));
        if ($direction !== 'hub_to_node') {
            return ['ok' => false, 'error' => 'direction hub_to_node olmalı.'];
        }

        return ['ok' => true, 'direction' => $direction];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function parseUploadedJson(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public static function extractRegionItem(array $item): array
    {
        return [
            'kod' => FederationRegion::normalizeKod((string) ($item['kod'] ?? '')),
            'ad' => trim((string) ($item['ad'] ?? '')),
            'il_adi' => trim((string) ($item['il_adi'] ?? '')),
            'hub_node_ref' => FederationHelper::normalizeRef(is_scalar($item['hub_node_ref'] ?? null) ? (string) $item['hub_node_ref'] : null),
            'aktif' => !empty($item['aktif']) ? 1 : 0,
            'aciklama' => trim((string) ($item['aciklama'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public static function extractKurumItem(array $item): array
    {
        return [
            'esh_id' => (int) ($item['esh_id'] ?? 0),
            'kod' => Kurum::normalizeKod((string) ($item['kod'] ?? '')),
            'federation_ref' => FederationHelper::normalizeRef(is_scalar($item['federation_ref'] ?? null) ? (string) $item['federation_ref'] : null),
            'bolge_kod' => FederationRegion::normalizeKod((string) ($item['bolge_kod'] ?? '')),
        ];
    }

    public static function apiConfigured(): bool
    {
        return OperationalSettings::federationHubApiEnabled()
            && OperationalSettings::federationHubBaseUrl() !== '';
    }
}
