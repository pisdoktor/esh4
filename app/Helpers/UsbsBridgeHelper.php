<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * USBS / e-Nabız entegrasyon köprüsü — JSON paket doğrulama ve alan eşlemesi.
 */
final class UsbsBridgeHelper
{
    public const BUNDLE_VERSION = 1;

    public static function isReady(): bool
    {
        return UsbsComplianceHelper::enabled() && OperationalSettings::usbsBridgeEnabled();
    }

    /**
     * @param object $row
     * @return array<string, mixed>
     */
    public static function mapRowToUsbsPayload(object $row, string $entityKey): array
    {
        $sections = UsbsComplianceHelper::entitySections();
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
            $eshKey = (string) ($field['esh'] ?? '');
            $usbsKey = (string) ($field['usbs'] ?? '');
            if ($eshKey === '' || $usbsKey === '') {
                continue;
            }
            if (!property_exists($row, $eshKey) && !isset($row->{$eshKey})) {
                continue;
            }
            $raw = $row->{$eshKey} ?? null;
            if ($raw === null || $raw === '') {
                $payload[$usbsKey] = null;
                continue;
            }
            $format = (string) ($field['format'] ?? '');
            if ($format === 'date' && is_string($raw)) {
                $payload[$usbsKey] = self::formatDateForUsbs($raw);
            } else {
                $payload[$usbsKey] = is_scalar($raw) ? $raw : (string) $raw;
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
        if ($direction !== 'usbs_to_esh') {
            return ['ok' => false, 'error' => 'direction usbs_to_esh olmalı.'];
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

    /**
     * @param array<string, mixed> $item
     * @return array<string, string|null>
     */
    public static function extractPatientRefs(array $item): array
    {
        $refs = is_array($item['refs'] ?? null) ? $item['refs'] : $item;
        $out = [];
        foreach (['enabiz_hasta_ref', 'usbs_hasta_ref'] as $key) {
            if (array_key_exists($key, $refs)) {
                $out[$key] = UsbsComplianceHelper::normalizeRef(is_scalar($refs[$key]) ? (string) $refs[$key] : null);
            } elseif (array_key_exists($key, $item)) {
                $out[$key] = UsbsComplianceHelper::normalizeRef(is_scalar($item[$key]) ? (string) $item[$key] : null);
            }
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, string|null>
     */
    public static function extractVisitRefs(array $item): array
    {
        $refs = is_array($item['refs'] ?? null) ? $item['refs'] : $item;
        $out = [];
        foreach (['usbs_bildirim_ref', 'erecete_ref'] as $key) {
            if (array_key_exists($key, $refs)) {
                $out[$key] = UsbsComplianceHelper::normalizeRef(is_scalar($refs[$key]) ? (string) $refs[$key] : null);
            } elseif (array_key_exists($key, $item)) {
                $out[$key] = UsbsComplianceHelper::normalizeRef(is_scalar($item[$key]) ? (string) $item[$key] : null);
            }
        }
        if (array_key_exists('usbs_bildirim_durum', $refs)) {
            $out['usbs_bildirim_durum'] = UsbsComplianceHelper::normalizeBildirimDurum(
                is_scalar($refs['usbs_bildirim_durum']) ? (string) $refs['usbs_bildirim_durum'] : ''
            );
        } elseif (array_key_exists('usbs_bildirim_durum', $item)) {
            $out['usbs_bildirim_durum'] = UsbsComplianceHelper::normalizeBildirimDurum(
                is_scalar($item['usbs_bildirim_durum']) ? (string) $item['usbs_bildirim_durum'] : ''
            );
        }

        return $out;
    }

    public static function apiConfigured(): bool
    {
        $mode = OperationalSettings::usbsBridgeApiMode();
        if ($mode === 'stub') {
            return true;
        }
        if ($mode === 'http') {
            return OperationalSettings::usbsBridgeApiBaseUrl() !== '';
        }

        return false;
    }

    private static function formatDateForUsbs(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }
        $ymd = DateHelper::trDateToYmd($raw);

        return $ymd ?? $raw;
    }
}
