<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\FederationRegion;
use App\Models\Kurum;

/**
 * Bölge düzeyi operasyonel ayarlar (#__federation_regions.ayarlar_json).
 */
final class BolgeCorporateSettings
{
    /** @var array<int, array<string, mixed>> */
    private static array $cache = [];

    public static function columnsReady(): bool
    {
        if (!FederationRegion::tableExists()) {
            return false;
        }

        return FederationRegion::ayarlarColumnReady();
    }

    public static function bolgeIdForKurum(int $kurumId): ?int
    {
        if ($kurumId <= 0 || !Kurum::tableExists()) {
            return null;
        }
        $k = new Kurum();
        if (!$k->load($kurumId)) {
            return null;
        }
        $bid = isset($k->bolge_id) && $k->bolge_id !== null ? (int) $k->bolge_id : 0;

        return $bid > 0 ? $bid : null;
    }

    public static function displayName(int $bolgeId): string
    {
        if (!FederationRegion::tableExists()) {
            return '';
        }
        $b = new FederationRegion();
        if (!$b->load($bolgeId)) {
            return '';
        }

        return trim((string) ($b->ad ?? ''));
    }

    public static function get(string $key): ?string
    {
        $bid = SettingsWriteScope::resolveBolgeIdForRead();
        if ($bid === null) {
            return null;
        }
        $arr = self::loadForBolge($bid);
        if (!array_key_exists($key, $arr)) {
            return null;
        }
        $v = trim((string) $arr[$key]);

        return $v !== '' ? $v : null;
    }

    /**
     * @return mixed|null
     */
    public static function getSectionValue(string $section, string $key)
    {
        if ($section === 'corporate') {
            return self::get($key);
        }
        $bid = SettingsWriteScope::resolveBolgeIdForRead();
        if ($bid === null) {
            return null;
        }
        $arr = self::loadForBolge($bid);
        $op = $arr['operational'] ?? null;
        if (!is_array($op)) {
            return null;
        }
        $sec = $op[$section] ?? null;
        if (!is_array($sec) || !array_key_exists($key, $sec)) {
            return null;
        }

        return $sec[$key];
    }

    public static function getModulesMap(?int $bolgeId = null): array
    {
        $bid = $bolgeId ?? SettingsWriteScope::resolveBolgeIdForRead();
        if ($bid === null || $bid <= 0) {
            return [];
        }
        $arr = self::loadForBolge($bid);
        $mods = $arr['modules'] ?? [];

        return is_array($mods) ? $mods : [];
    }

    /**
     * @param array<string, bool> $data
     * @return true|string
     */
    public static function saveModules(int $bolgeId, array $data)
    {
        if ($bolgeId <= 0) {
            return 'Geçersiz bölge.';
        }
        if (!self::columnsReady()) {
            return 'Bölge ayarları tablosu hazır değil; migrate_esh_federation_regions_ayarlar.sql çalıştırın.';
        }
        $b = new FederationRegion();
        if (!$b->load($bolgeId)) {
            return 'Bölge bulunamadı.';
        }
        $arr = $b->ayarlarArray();
        $existing = is_array($arr['modules'] ?? null) ? $arr['modules'] : [];
        $normalized = [];
        foreach ($data as $key => $value) {
            $normalized[(string) $key] = (bool) $value;
        }
        $arr['modules'] = array_merge($existing, $normalized);
        $b->setAyarlarArray($arr);
        if (!$b->store()) {
            return 'Bölge modül ayarları kaydedilemedi.';
        }
        unset(self::$cache[$bolgeId]);

        return true;
    }

    public static function getIslemIdsMap(?int $bolgeId = null): array
    {
        $bid = $bolgeId ?? SettingsWriteScope::resolveBolgeIdForRead();
        if ($bid === null || $bid <= 0) {
            return [];
        }
        $arr = self::loadForBolge($bid);
        $ids = $arr['islem_ids'] ?? [];

        return is_array($ids) ? $ids : [];
    }

    /**
     * @param array<string, mixed> $data
     * @return true|string
     */
    public static function saveIslemIds(int $bolgeId, array $data)
    {
        if ($bolgeId <= 0) {
            return 'Geçersiz bölge.';
        }
        if (!self::columnsReady()) {
            return 'Bölge ayarları tablosu hazır değil; migrate_esh_federation_regions_ayarlar.sql çalıştırın.';
        }
        $b = new FederationRegion();
        if (!$b->load($bolgeId)) {
            return 'Bölge bulunamadı.';
        }
        $arr = $b->ayarlarArray();
        $existing = is_array($arr['islem_ids'] ?? null) ? $arr['islem_ids'] : [];
        $arr['islem_ids'] = array_merge($existing, $data);
        $b->setAyarlarArray($arr);
        if (!$b->store()) {
            return 'Bölge işlem id ayarları kaydedilemedi.';
        }
        unset(self::$cache[$bolgeId]);

        return true;
    }

    /**
     * @param array<string, mixed> $data
     * @return true|string
     */
    public static function saveSection(int $bolgeId, string $section, array $data)
    {
        if (!KurumCorporateSettings::isKurumOperationalSection($section)) {
            return 'Bu bölüm bölge kapsamında kaydedilemez.';
        }
        if (!self::columnsReady()) {
            return 'Bölge ayarları tablosu hazır değil; migrate_esh_federation_regions_ayarlar.sql çalıştırın.';
        }
        $b = new FederationRegion();
        if (!$b->load($bolgeId)) {
            return 'Bölge bulunamadı.';
        }
        $arr = $b->ayarlarArray();

        if ($section === 'corporate') {
            foreach ($data as $key => $value) {
                if (!in_array((string) $key, KurumCorporateSettings::CORPORATE_FLAT_KEYS, true)) {
                    continue;
                }
                $arr[(string) $key] = is_scalar($value) || $value === null
                    ? (string) $value
                    : json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        } else {
            if (!isset($arr['operational']) || !is_array($arr['operational'])) {
                $arr['operational'] = [];
            }
            $existing = is_array($arr['operational'][$section] ?? null)
                ? $arr['operational'][$section]
                : [];
            $arr['operational'][$section] = array_merge($existing, $data);
        }

        $b->setAyarlarArray($arr);
        if (!$b->store()) {
            return 'Bölge ayarları kaydedilemedi.';
        }
        unset(self::$cache[$bolgeId]);

        return true;
    }

    /** @return array<string, mixed> */
    private static function loadForBolge(int $bolgeId): array
    {
        if (isset(self::$cache[$bolgeId])) {
            return self::$cache[$bolgeId];
        }
        if (!self::columnsReady()) {
            self::$cache[$bolgeId] = [];

            return [];
        }
        $b = new FederationRegion();
        if (!$b->load($bolgeId)) {
            self::$cache[$bolgeId] = [];

            return [];
        }
        self::$cache[$bolgeId] = $b->ayarlarArray();

        return self::$cache[$bolgeId];
    }
}
