<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * ESYS / AHBS entegrasyon köprüsü — JSON paket doğrulama ve alan eşlemesi.
 */
final class EsysBridgeHelper
{
    public const BUNDLE_VERSION = 1;

    public static function isReady(): bool
    {
        return EsysComplianceHelper::enabled() && OperationalSettings::esysBridgeEnabled();
    }

    /**
     * @param object $row
     * @return array<string, mixed>
     */
    /**
     * @param string|null $entityFilter basvuru gibi çok kaynaklı entity'lerde alan filtresi (patient|erapor)
     */
    public static function mapRowToEsysPayload(object $row, string $entityKey, ?string $entityFilter = null): array
    {
        $sections = EsysComplianceHelper::entitySections();
        $fields = [];
        foreach ($sections as $section) {
            if (($section['key'] ?? '') === $entityKey) {
                $fields = is_array($section['data']['fields'] ?? null) ? $section['data']['fields'] : [];
                break;
            }
        }

        $payload = [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $fieldEntity = (string) ($field['entity'] ?? '');
            if ($entityFilter !== null && $fieldEntity !== '' && $fieldEntity !== $entityFilter) {
                continue;
            }
            if ($entityFilter === null && $fieldEntity !== '' && $entityKey !== 'basvuru') {
                continue;
            }
            $eshKey = (string) ($field['esh'] ?? '');
            $esysKey = (string) ($field['esys'] ?? '');
            if ($eshKey === '' || $esysKey === '') {
                continue;
            }
            if (!property_exists($row, $eshKey) && !isset($row->{$eshKey})) {
                continue;
            }
            $raw = $row->{$eshKey} ?? null;
            if ($raw === null || $raw === '') {
                $payload[$esysKey] = null;
                continue;
            }
            $lookup = (string) ($field['lookup'] ?? '');
            if ($lookup !== '' || $eshKey === 'cinsiyet' || $eshKey === 'kons_brans_istek') {
                $payload[$esysKey] = BridgeLookupResolver::resolve($raw, $lookup, $eshKey);
                continue;
            }
            $format = (string) ($field['format'] ?? '');
            if ($format === 'date' && is_string($raw)) {
                $payload[$esysKey] = self::formatDateForEsys($raw);
            } else {
                $payload[$esysKey] = is_scalar($raw) ? $raw : (string) $raw;
            }
        }

        return $payload;
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
        $allowed = ['esys_to_esh', 'ahbs_to_esh'];
        if (!in_array($direction, $allowed, true)) {
            return ['ok' => false, 'error' => 'direction esys_to_esh veya ahbs_to_esh olmalı.'];
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
        } catch (\Throwable $e) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    public static function normalizeRef(?string $value): ?string
    {
        return EsysComplianceHelper::normalizeRef($value);
    }

    /**
     * @param array<string, mixed> $item
     */
    public static function extractPatientRefs(array $item): array
    {
        $refs = is_array($item['refs'] ?? null) ? $item['refs'] : $item;
        $out = [];
        foreach (['esys_hasta_ref', 'esys_basvuru_ref'] as $key) {
            if (array_key_exists($key, $refs)) {
                $out[$key] = self::normalizeRef(is_scalar($refs[$key]) ? (string) $refs[$key] : null);
            } elseif (array_key_exists($key, $item)) {
                $out[$key] = self::normalizeRef(is_scalar($item[$key]) ? (string) $item[$key] : null);
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $item
     */
    public static function extractVisitRefs(array $item): array
    {
        $refs = is_array($item['refs'] ?? null) ? $item['refs'] : $item;
        $out = [];
        foreach (['esys_izlem_ref', 'esys_konsultasyon_ref'] as $key) {
            if (array_key_exists($key, $refs)) {
                $out[$key] = self::normalizeRef(is_scalar($refs[$key]) ? (string) $refs[$key] : null);
            } elseif (array_key_exists($key, $item)) {
                $out[$key] = self::normalizeRef(is_scalar($item[$key]) ? (string) $item[$key] : null);
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $item
     */
    public static function extractPlanRef(array $item): ?string
    {
        $refs = is_array($item['refs'] ?? null) ? $item['refs'] : $item;
        if (array_key_exists('esys_plan_ref', $refs)) {
            return self::normalizeRef(is_scalar($refs['esys_plan_ref']) ? (string) $refs['esys_plan_ref'] : null);
        }
        if (array_key_exists('esys_plan_ref', $item)) {
            return self::normalizeRef(is_scalar($item['esys_plan_ref']) ? (string) $item['esys_plan_ref'] : null);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $item
     */
    public static function extractEraporRef(array $item): ?string
    {
        $refs = is_array($item['refs'] ?? null) ? $item['refs'] : $item;
        if (array_key_exists('esys_erapor_ref', $refs)) {
            return self::normalizeRef(is_scalar($refs['esys_erapor_ref']) ? (string) $refs['esys_erapor_ref'] : null);
        }
        if (array_key_exists('esys_erapor_ref', $item)) {
            return self::normalizeRef(is_scalar($item['esys_erapor_ref']) ? (string) $item['esys_erapor_ref'] : null);
        }

        return null;
    }

    public static function apiConfigured(): bool
    {
        $mode = OperationalSettings::esysBridgeApiMode();
        if ($mode === 'stub') {
            return true;
        }
        if ($mode === 'http') {
            return OperationalSettings::esysBridgeApiBaseUrl() !== '';
        }

        return false;
    }

    private static function formatDateForEsys(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }
        $ymd = DateHelper::trDateToYmd($raw);
        if ($ymd !== null) {
            return $ymd;
        }

        return $raw;
    }
}
