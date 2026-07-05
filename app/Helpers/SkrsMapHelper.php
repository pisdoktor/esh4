<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Database;

/**
 * SKRS sidecar eşleme — operasyonel tablolara dokunmadan resmi kod çözümlemesi.
 */
final class SkrsMapHelper
{
    private const SIDEcar_PROBE_TABLE = 'skrs_guvence_map';

    /** @var bool|null */
    private static ?bool $tablesReady = null;

    /** @var array<string, mixed> */
    private static array $cache = [];

    public static function resetCache(): void
    {
        self::$cache = [];
    }

    public static function tablesReady(): bool
    {
        if (self::$tablesReady !== null) {
            return self::$tablesReady;
        }
        try {
            $db = Database::getInstance();
            $tbl = $db->replacePrefix('#__' . self::SIDEcar_PROBE_TABLE);
            $row = $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
                [$tbl]
            );
            self::$tablesReady = $row !== null && $row !== false && $row !== '';
        } catch (\Throwable $e) {
            self::$tablesReady = false;
        }

        return self::$tablesReady;
    }

    public static function enabled(): bool
    {
        return self::tablesReady();
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>|null
     */
    public static function enrichGuvence(?array $payload): ?array
    {
        if ($payload === null || !self::enabled()) {
            return $payload;
        }
        $id = (int) ($payload['id'] ?? 0);
        if ($id < 1) {
            return $payload;
        }
        $map = self::fetchGuvenceMap($id);
        if ($map === null) {
            return $payload;
        }

        return array_merge($payload, $map);
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>|null
     */
    public static function enrichAdres(?array $payload): ?array
    {
        if ($payload === null || !self::enabled()) {
            return $payload;
        }
        $id = trim((string) ($payload['id'] ?? ''));
        if ($id === '') {
            return $payload;
        }
        $map = self::fetchAdresMap($id);
        if ($map === null) {
            return $payload;
        }

        return array_merge($payload, $map);
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>|null
     */
    public static function enrichUser(?array $payload): ?array
    {
        if ($payload === null || !self::enabled()) {
            return $payload;
        }
        $unvanKod = trim((string) ($payload['unvan'] ?? ''));
        if ($unvanKod === '') {
            return $payload;
        }
        $map = self::fetchUnvanMap($unvanKod);
        if ($map === null) {
            return $payload;
        }

        return array_merge($payload, $map);
    }

    /**
     * USBS/ESYS export meta — kurum + varsayılan birim SKRS/ÇKYS bağlamı.
     *
     * @return array<string, mixed>|null
     */
    public static function exportContextForKurum(?int $kurumId): ?array
    {
        if (!self::enabled() || $kurumId === null || $kurumId < 1) {
            return null;
        }
        $cacheKey = 'kurum_ctx:' . $kurumId;
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }
        try {
            $db = Database::getInstance();
            $kurum = $db->fetchObjectPrepared(
                'SELECT kurum_id, ckys_kodu, skrs_kurum_kod, kurum_adi_resmi, il_kodu, ilce_kodu
                 FROM #__skrs_kurum_map WHERE kurum_id = ? AND aktif = 1 LIMIT 1',
                [$kurumId]
            );
            if (!$kurum) {
                self::$cache[$cacheKey] = null;

                return null;
            }
            $birim = $db->fetchObjectPrepared(
                'SELECT esh_birim_kod, esh_birim_adi, ckys_birim_kodu, skrs_birim_kod
                 FROM #__skrs_birim_map
                 WHERE kurum_id = ? AND aktif = 1
                 ORDER BY id ASC LIMIT 1',
                [$kurumId]
            );
            $ctx = [
                'kurum_id' => $kurumId,
                'ckys_kodu' => trim((string) ($kurum->ckys_kodu ?? '')),
                'skrs_kurum_kod' => self::nullableTrim($kurum->skrs_kurum_kod ?? null),
                'kurum_adi_resmi' => self::nullableTrim($kurum->kurum_adi_resmi ?? null),
                'il_kodu' => self::nullableTrim($kurum->il_kodu ?? null),
                'ilce_kodu' => self::nullableTrim($kurum->ilce_kodu ?? null),
            ];
            if ($birim) {
                $ctx['birim'] = [
                    'esh_birim_kod' => trim((string) ($birim->esh_birim_kod ?? '')),
                    'esh_birim_adi' => trim((string) ($birim->esh_birim_adi ?? '')),
                    'ckys_birim_kodu' => trim((string) ($birim->ckys_birim_kodu ?? '')),
                    'skrs_birim_kod' => self::nullableTrim($birim->skrs_birim_kod ?? null),
                ];
            }
            self::$cache[$cacheKey] = $ctx;

            return $ctx;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array{skrs_kod: string, skrs_ad: string|null}|null
     */
    private static function fetchGuvenceMap(int $guvenceId): ?array
    {
        $cacheKey = 'guvence:' . $guvenceId;
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }
        try {
            $db = Database::getInstance();
            $row = $db->fetchObjectPrepared(
                'SELECT skrs_kod, skrs_ad FROM #__skrs_guvence_map
                 WHERE guvence_id = ? AND aktif = 1 LIMIT 1',
                [$guvenceId]
            );
            if (!$row) {
                self::$cache[$cacheKey] = null;

                return null;
            }
            $out = [
                'skrs_kod' => trim((string) ($row->skrs_kod ?? '')),
                'skrs_ad' => self::nullableTrim($row->skrs_ad ?? null),
            ];
            self::$cache[$cacheKey] = $out['skrs_kod'] !== '' ? $out : null;

            return self::$cache[$cacheKey];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array{skrs_kod: string, skrs_ad: string|null, csbm_tip_kod: string|null}|null
     */
    private static function fetchAdresMap(string $adresId): ?array
    {
        $cacheKey = 'adres:' . $adresId;
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }
        try {
            $db = Database::getInstance();
            $row = $db->fetchObjectPrepared(
                'SELECT skrs_kod, skrs_ad, csbm_tip_kod FROM #__skrs_adres_map
                 WHERE adres_id = ? AND aktif = 1 LIMIT 1',
                [$adresId]
            );
            if (!$row) {
                self::$cache[$cacheKey] = null;

                return null;
            }
            $out = [
                'skrs_kod' => trim((string) ($row->skrs_kod ?? '')),
                'skrs_ad' => self::nullableTrim($row->skrs_ad ?? null),
                'csbm_tip_kod' => self::nullableTrim($row->csbm_tip_kod ?? null),
            ];
            self::$cache[$cacheKey] = $out['skrs_kod'] !== '' ? $out : null;

            return self::$cache[$cacheKey];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array{skrs_unvan_kod: string, skrs_unvan_ad: string|null, skrs_meslek_kod: string|null, skrs_meslek_ad: string|null}|null
     */
    private static function fetchUnvanMap(string $unvanKod): ?array
    {
        $cacheKey = 'unvan:' . $unvanKod;
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }
        try {
            $db = Database::getInstance();
            $row = $db->fetchObjectPrepared(
                'SELECT skrs_unvan_kod, skrs_unvan_ad, skrs_meslek_kod, skrs_meslek_ad
                 FROM #__skrs_unvan_map WHERE unvan_kod = ? AND aktif = 1 LIMIT 1',
                [$unvanKod]
            );
            if (!$row) {
                self::$cache[$cacheKey] = null;

                return null;
            }
            $out = [
                'skrs_unvan_kod' => trim((string) ($row->skrs_unvan_kod ?? '')),
                'skrs_unvan_ad' => self::nullableTrim($row->skrs_unvan_ad ?? null),
                'skrs_meslek_kod' => self::nullableTrim($row->skrs_meslek_kod ?? null),
                'skrs_meslek_ad' => self::nullableTrim($row->skrs_meslek_ad ?? null),
            ];
            self::$cache[$cacheKey] = $out['skrs_unvan_kod'] !== '' ? $out : null;

            return self::$cache[$cacheKey];
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function nullableTrim(mixed $value): ?string
    {
        $v = trim((string) $value);

        return $v === '' ? null : $v;
    }
}
