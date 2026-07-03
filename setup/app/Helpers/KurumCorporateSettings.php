<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Kurum;

/**
 * Kuruma özel operasyonel ayarlar (#__kurumlar.ayarlar_json).
 *
 * Kurumsal metin alanları kök düzeyde; harita/planlama/nöbet operational altında.
 */
final class KurumCorporateSettings
{
    /** @var list<string> */
    public const KURUM_OPERATIONAL_SECTIONS = ['map', 'planning', 'durations', 'field_visit', 'uhds_telehealth', 'nobet', 'vardiya', 'sms'];

    /** @var list<string> */
    public const CORPORATE_FLAT_KEYS = ['esh_app_name', 'ek3_form_baslik', 'hekim_degerlendirme_form_baslik'];

    /** @var array<int, array<string, mixed>> */
    private static array $cache = [];

    public static function isKurumOperationalSection(string $section): bool
    {
        return $section === 'corporate'
            || in_array($section, self::KURUM_OPERATIONAL_SECTIONS, true);
    }

    /** Okuma: oturum / kurum filtresi kapsamı (config bootstrap'ta null → platform varsayılanı). */
    public static function readKurumId(): ?int
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        if (!class_exists(AuthHelper::class, false)) {
            $kid = TenantContext::sessionKurumId();

            return ($kid !== null && $kid > 0) ? $kid : null;
        }
        $kid = TenantContext::filterKurumId();

        return ($kid !== null && $kid > 0) ? $kid : null;
    }

    /** Yazma: kurum admin → oturum kurumu; süper yönetici → navbar filtresi (yoksa global). */
    public static function writeKurumId(): ?int
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !class_exists(AuthHelper::class, false)) {
            return null;
        }
        if (AuthHelper::sessionIsSuperAdmin()) {
            return TenantContext::sessionKurumFilter();
        }
        $kid = TenantContext::sessionKurumId();

        return ($kid !== null && $kid > 0) ? $kid : null;
    }

    public static function get(string $key): ?string
    {
        $kid = self::readKurumId();
        if ($kid === null) {
            return null;
        }
        $arr = self::loadForKurum($kid);
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
        $kid = self::readKurumId();
        if ($kid === null) {
            return null;
        }
        $arr = self::loadForKurum($kid);
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

    public static function getModulesMap(?int $kurumId = null): array
    {
        $kid = $kurumId ?? self::readKurumId();
        if ($kid === null || $kid <= 0) {
            return [];
        }
        $arr = self::loadForKurum($kid);
        $mods = $arr['modules'] ?? [];

        return is_array($mods) ? $mods : [];
    }

    /**
     * @param array<string, bool> $data
     * @return true|string
     */
    public static function saveModules(int $kurumId, array $data)
    {
        if ($kurumId <= 0) {
            return 'Geçersiz kurum.';
        }
        if (!Kurum::tableExists()) {
            return 'Kurum tablosu bulunamadı.';
        }
        $k = new Kurum();
        if (!$k->load($kurumId)) {
            return 'Kurum bulunamadı.';
        }
        $arr = $k->ayarlarArray();
        $existing = is_array($arr['modules'] ?? null) ? $arr['modules'] : [];
        $normalized = [];
        foreach ($data as $key => $value) {
            $normalized[(string) $key] = (bool) $value;
        }
        $arr['modules'] = array_merge($existing, $normalized);
        $k->setAyarlarArray($arr);
        if (!$k->store()) {
            return 'Kurum modül ayarları kaydedilemedi.';
        }
        unset(self::$cache[$kurumId]);

        return true;
    }

    public static function getIslemIdsMap(?int $kurumId = null): array
    {
        $kid = $kurumId ?? self::readKurumId();
        if ($kid === null || $kid <= 0) {
            return [];
        }
        $arr = self::loadForKurum($kid);
        $ids = $arr['islem_ids'] ?? [];

        return is_array($ids) ? $ids : [];
    }

    /**
     * @param array<string, mixed> $data
     * @return true|string
     */
    public static function saveIslemIds(int $kurumId, array $data)
    {
        if ($kurumId <= 0) {
            return 'Geçersiz kurum.';
        }
        if (!Kurum::tableExists()) {
            return 'Kurum tablosu bulunamadı.';
        }
        $k = new Kurum();
        if (!$k->load($kurumId)) {
            return 'Kurum bulunamadı.';
        }
        $arr = $k->ayarlarArray();
        $existing = is_array($arr['islem_ids'] ?? null) ? $arr['islem_ids'] : [];
        $arr['islem_ids'] = array_merge($existing, $data);
        $k->setAyarlarArray($arr);
        if (!$k->store()) {
            return 'Kurum işlem id ayarları kaydedilemedi.';
        }
        unset(self::$cache[$kurumId]);

        return true;
    }

    /**
     * @param array<string, mixed> $data
     * @return true|string
     */
    public static function saveSection(int $kurumId, string $section, array $data)
    {
        if (!self::isKurumOperationalSection($section)) {
            return 'Bu bölüm kurum kapsamında kaydedilemez.';
        }
        if (!Kurum::tableExists()) {
            return 'Kurum tablosu bulunamadı.';
        }
        $k = new Kurum();
        if (!$k->load($kurumId)) {
            return 'Kurum bulunamadı.';
        }
        $arr = $k->ayarlarArray();

        if ($section === 'corporate') {
            foreach ($data as $key => $value) {
                if (!in_array((string) $key, self::CORPORATE_FLAT_KEYS, true)) {
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

        $k->setAyarlarArray($arr);
        if (!$k->store()) {
            return 'Kurum ayarları kaydedilemedi.';
        }
        unset(self::$cache[$kurumId]);

        return true;
    }

    public static function displayName(int $kurumId): string
    {
        return self::kurumName($kurumId);
    }

    /**
     * Ayar paneli üst bilgi şeridi.
     *
     * @return array{mode:string,label:string,hint:string,kurum_id?:int,bolge_id?:int}|null
     */
    public static function adminScopeBanner(): ?array
    {
        return SettingsWriteScope::adminScopeBanner();
    }

    /** @return array<string, mixed> */
    private static function loadForKurum(int $kurumId): array
    {
        if (isset(self::$cache[$kurumId])) {
            return self::$cache[$kurumId];
        }
        if (!Kurum::tableExists()) {
            self::$cache[$kurumId] = [];

            return [];
        }
        $k = new Kurum();
        if (!$k->load($kurumId)) {
            self::$cache[$kurumId] = [];

            return [];
        }
        self::$cache[$kurumId] = $k->ayarlarArray();

        return self::$cache[$kurumId];
    }

    private static function kurumName(int $kurumId): string
    {
        if (!Kurum::tableExists()) {
            return '';
        }
        $k = new Kurum();
        if (!$k->load($kurumId)) {
            return '';
        }

        return trim((string) ($k->ad ?? ''));
    }
}
