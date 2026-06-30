<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\KurumIslem;

/**
 * Kurumsal işlem id eşlemeleri: public/assets/data/islem-idleri.json + public/assets/data/app-settings.json.
 */
final class IslemIdSettings
{
    private const CATALOG_REL = 'public/assets/data/islem-idleri.json';

    /** @var bool */
    private static $booted = false;

    /** @var list<array<string, mixed>>|null */
    private static $catalog = null;

    /** @var array<string, string|int>|null */
    private static $resolved = null;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$booted = true;
        self::loadCatalog();
        self::$resolved = self::buildResolvedMap();
    }

    public static function catalogPath(): string
    {
        return rtrim((string) ROOT_PATH, '/\\') . '/' . self::CATALOG_REL;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function catalogItems(): array
    {
        self::boot();

        return self::$catalog ?? [];
    }

    /**
     * Ayar paneli: tanım + mevcut değer.
     *
     * @return list<array{key:string,label:string,description:string,type:string,value:string,default:string,min?:int}>
     */
    public static function allForAdmin(): array
    {
        self::boot();
        $kurumId = KurumCorporateSettings::writeKurumId();
        $rows = [];
        foreach (self::$catalog ?? [] as $item) {
            $key = (string) ($item['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $type = (string) ($item['type'] ?? 'int');
            $value = self::formatValueForAdmin($key, $type);
            if ($kurumId !== null && $kurumId > 0) {
                $filtered = self::filterMapToKurumAssignments($kurumId, [$key => $value]);
                $value = self::formatStoredValueForAdmin($type, $filtered[$key] ?? '');
            }
            $row = [
                'key' => $key,
                'label' => (string) ($item['label'] ?? $key),
                'description' => (string) ($item['description'] ?? ''),
                'type' => $type,
                'value' => $value,
                'default' => self::formatDefaultForAdmin($item, $type),
            ];
            if ($type === 'int' && array_key_exists('min', $item)) {
                $row['min'] = (int) $item['min'];
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public static function resolvedInt(string $key): int
    {
        self::boot();

        return (int) self::resolveRaw($key);
    }

    public static function resolvedCsv(string $key): string
    {
        self::boot();

        return self::normalizeCsvString((string) self::resolveRaw($key));
    }

    /**
     * @param array<string, string> $posted key => value (form)
     * @return true|string
     */
    public static function saveValues(array $posted)
    {
        self::boot();
        $toSave = [];
        foreach (self::$catalog ?? [] as $item) {
            $key = (string) ($item['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $type = (string) ($item['type'] ?? 'int');
            if (!array_key_exists($key, $posted)) {
                if ($type === 'csv') {
                    $toSave[$key] = '';
                }
                continue;
            }
            $rawPosted = $posted[$key];
            if (is_array($rawPosted)) {
                $parts = [];
                foreach ($rawPosted as $p) {
                    $i = (int) $p;
                    if ($i > 0) {
                        $parts[] = (string) $i;
                    }
                }
                $rawStr = implode(',', $parts);
            } else {
                $rawStr = (string) $rawPosted;
            }
            if ($type === 'int' && trim($rawStr) === '') {
                $min = array_key_exists('min', $item) ? (int) $item['min'] : 1;
                if ($min > 0) {
                    continue;
                }
            }
            if ($type === 'csv') {
                $csvErr = self::normalizePostedCsvError($rawStr, $item);
                if ($csvErr !== null) {
                    return $csvErr;
                }
                $ids = VisitIslemHelper::parseConfiguredIslemIdList($rawStr);
                $toSave[$key] = implode(',', $ids);
                continue;
            }
            $normalized = self::normalizePostedValue($key, $type, $rawStr, $item);
            if (is_string($normalized)) {
                return $normalized;
            }
            $toSave[$key] = $normalized;
        }
        if ($toSave === []) {
            return 'Kaydedilecek işlem id ayarı bulunamadı.';
        }

        $kurumId = KurumCorporateSettings::writeKurumId();
        if ($kurumId !== null) {
            $toSave = self::filterMapToKurumAssignments($kurumId, $toSave);
            $existing = KurumCorporateSettings::getIslemIdsMap($kurumId);
            $merged = array_merge($existing, $toSave);
            foreach (self::$catalog ?? [] as $item) {
                $key = (string) ($item['key'] ?? '');
                if ($key !== '' && !array_key_exists($key, $merged)) {
                    $merged[$key] = self::resolveGlobalRaw($key);
                }
            }
            $merged = self::filterMapToKurumAssignments($kurumId, $merged);
            $merged = self::filterIslemIdsToCatalog($merged);
            $written = KurumCorporateSettings::saveIslemIds($kurumId, $merged);
            if ($written !== true) {
                return $written;
            }
            self::$resolved = self::buildResolvedMap();

            return true;
        }

        $current = self::readRuntimeFile();
        $out = [
            'version' => (int) ($current['version'] ?? 1),
            'modules' => is_array($current['modules'] ?? null) ? $current['modules'] : [],
            'islem_ids' => array_merge(
                is_array($current['islem_ids'] ?? null) ? $current['islem_ids'] : [],
                $toSave
            ),
        ];
        foreach (self::$catalog ?? [] as $item) {
            $key = (string) ($item['key'] ?? '');
            if ($key !== '' && !array_key_exists($key, $out['islem_ids'])) {
                $out['islem_ids'][$key] = self::resolveRaw($key);
            }
        }
        $out['islem_ids'] = self::filterIslemIdsToCatalog(
            is_array($out['islem_ids']) ? $out['islem_ids'] : []
        );

        if (!isset($out['modules']) || !is_array($out['modules'])) {
            $out['modules'] = is_array($current['modules'] ?? null) ? $current['modules'] : [];
        }

        $written = self::writeRuntimeFile($out);
        if ($written !== true) {
            return $written;
        }
        self::$resolved = self::buildResolvedMap();

        return true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function loadCatalog(): void
    {
        $path = self::catalogPath();
        if (!is_readable($path)) {
            self::$catalog = [];

            return;
        }
        $raw = @file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            self::$catalog = [];

            return;
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            self::$catalog = [];

            return;
        }
        $items = $decoded['items'] ?? [];
        self::$catalog = is_array($items) ? $items : [];
    }

    /**
     * @return array<string, string|int>
     */
    private static function buildResolvedMap(): array
    {
        $map = [];
        foreach (self::$catalog ?? [] as $item) {
            $key = (string) ($item['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $map[$key] = self::resolveRaw($key);
        }

        return $map;
    }

    /**
     * @return string|int
     */
    private static function resolveRaw(string $key)
    {
        if (class_exists(KurumCorporateSettings::class)) {
            $kurumMap = KurumCorporateSettings::getIslemIdsMap();
            if (array_key_exists($key, $kurumMap)) {
                return $kurumMap[$key];
            }
        }

        return self::resolveGlobalRaw($key);
    }

    /**
     * @return string|int
     */
    private static function resolveGlobalRaw(string $key)
    {
        $runtime = self::readRuntimeIslemIds();
        if (array_key_exists($key, $runtime)) {
            return $runtime[$key];
        }
        if (function_exists('esh_config_local')) {
            $local = esh_config_local($key, null);
            if ($local !== null && $local !== '') {
                return $local;
            }
        }
        foreach (self::$catalog ?? [] as $item) {
            if ((string) ($item['key'] ?? '') === $key) {
                return $item['default'] ?? '';
            }
        }

        return '';
    }

    private static function defaultForKey(string $key): string
    {
        foreach (self::$catalog ?? [] as $item) {
            if ((string) ($item['key'] ?? '') === $key) {
                return (string) ($item['default'] ?? '');
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function normalizePostedCsvError(string $raw, array $item): ?string
    {
        $ids = VisitIslemHelper::parseConfiguredIslemIdList($raw);
        if ($ids === [] && trim($raw) !== '') {
            $label = (string) ($item['label'] ?? ($item['key'] ?? ''));

            return $label . ' alanı için geçerli işlem id seçin.';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $item
     * @return string|int
     */
    private static function normalizePostedValue(string $key, string $type, string $raw, array $item)
    {
        $min = array_key_exists('min', $item) ? (int) $item['min'] : 1;
        if ($raw === '' && $min === 0) {
            return 0;
        }
        if (!ctype_digit(ltrim($raw, '-'))) {
            return (string) ($item['label'] ?? $key) . ' alanı için geçerli bir sayı girin.';
        }
        $n = (int) $raw;
        if ($n < $min) {
            return (string) ($item['label'] ?? $key) . ' alanı en az ' . $min . ' olmalıdır.';
        }

        return $n;
    }

    private static function formatValueForAdmin(string $key, string $type): string
    {
        if ($type === 'csv') {
            $ids = VisitIslemHelper::parseConfiguredIslemIdList(self::resolvedCsv($key));

            return implode(',', $ids);
        }

        return (string) self::resolvedInt($key);
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function formatDefaultForAdmin(array $item, string $type): string
    {
        $def = $item['default'] ?? '';
        if ($type === 'csv') {
            $ids = VisitIslemHelper::parseConfiguredIslemIdList((string) $def);

            return implode(',', $ids);
        }

        return (string) $def;
    }

    private static function normalizeCsvString(string $raw): string
    {
        $ids = VisitIslemHelper::parseConfiguredIslemIdList($raw);

        return implode(',', $ids);
    }

    /**
     * @return array<string, mixed>
     */
    private static function readRuntimeFile(): array
    {
        return AppSettingsStore::read();
    }

    /**
     * @return array<string, mixed>
     */
    private static function readRuntimeIslemIds(): array
    {
        $file = self::readRuntimeFile();
        $ids = $file['islem_ids'] ?? [];
        if (!is_array($ids)) {
            return [];
        }

        return self::filterIslemIdsToCatalog($ids);
    }

    /**
     * Kurum kapsamında yalnızca #__kurum_islem ile atanmış id'leri bırakır.
     *
     * @param array<string, mixed> $map
     * @return array<string, mixed>
     */
    private static function filterMapToKurumAssignments(int $kurumId, array $map): array
    {
        if ($kurumId <= 0) {
            return $map;
        }
        if (!KurumIslem::tableExists()) {
            return $map;
        }
        $allowed = array_flip((new KurumIslem())->getAssignedIds($kurumId));
        foreach (self::$catalog ?? [] as $item) {
            $key = (string) ($item['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $type = (string) ($item['type'] ?? 'int');
            if ($allowed === []) {
                if ($type === 'csv') {
                    $map[$key] = '';
                } else {
                    $min = array_key_exists('min', $item) ? (int) $item['min'] : 1;
                    $map[$key] = $min === 0 ? 0 : '';
                }
                continue;
            }
            if (!array_key_exists($key, $map)) {
                continue;
            }
            $val = $map[$key];
            if ($type === 'csv') {
                $ids = VisitIslemHelper::parseConfiguredIslemIdList((string) $val);
                $ids = array_values(array_filter($ids, static fn (int $id): bool => isset($allowed[$id])));
                $map[$key] = implode(',', $ids);
                continue;
            }
            $id = (int) $val;
            if ($id > 0 && !isset($allowed[$id])) {
                $min = array_key_exists('min', $item) ? (int) $item['min'] : 1;
                $map[$key] = $min === 0 ? 0 : '';
            }
        }

        return $map;
    }

    private static function formatStoredValueForAdmin(string $type, mixed $raw): string
    {
        if ($type === 'csv') {
            $ids = VisitIslemHelper::parseConfiguredIslemIdList((string) $raw);

            return implode(',', $ids);
        }
        if ($raw === '' || $raw === null) {
            return '';
        }

        return (string) (int) $raw;
    }

    /**
     * Katalogda tanımlı olmayan anahtarları yoksay.
     *
     * @param array<string, mixed> $ids
     * @return array<string, mixed>
     */
    private static function filterIslemIdsToCatalog(array $ids): array
    {
        $allowed = [];
        foreach (self::$catalog ?? [] as $item) {
            $key = (string) ($item['key'] ?? '');
            if ($key !== '') {
                $allowed[$key] = true;
            }
        }
        if ($allowed === []) {
            return $ids;
        }

        return array_intersect_key($ids, $allowed);
    }

    /**
     * @param array<string, mixed> $data
     * @return true|string
     */
    private static function writeRuntimeFile(array $data)
    {
        return AppSettingsStore::write($data);
    }
}
