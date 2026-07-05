<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Helpers\IdHelper;
use App\Helpers\AuthHelper;
use App\Core\Database;
use App\Models\Patient;

/**
 * USBS / ESYS köprü export — mapping lookup alanlarını çözümler.
 */
final class BridgeLookupResolver
{
    /** @var array<string, mixed> */
    private static array $cache = [];

    public static function resetCache(): void
    {
        self::$cache = [];
        SkrsMapHelper::resetCache();
    }

    /**
     * @return mixed scalar, array veya null
     */
    public static function resolve(mixed $raw, string $lookup, string $eshKey = '')
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if ($eshKey === 'cinsiyet') {
            return CinsiyetHelper::normalize($raw);
        }

        if ($eshKey === 'kons_brans_istek' && is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            return $raw;
        }

        return match ($lookup) {
            'icd10_skrs' => self::resolveIcd10List($raw),
            'esh_islemler' => self::resolveIdLabelList($raw, 'islemler', 'islemadi'),
            'esh_branslar' => self::resolveIdLabelList($raw, 'branslar', 'bransadi'),
            'esh_istekler' => self::resolveIdLabelList($raw, 'istekler', 'istek_adi'),
            'esh_guvence' => self::resolveSingleIdLabel($raw, 'guvence', 'guvenceadi'),
            'esh_users' => self::resolveUsers($raw),
            'esh_araclar' => self::resolveArac($raw),
            'adres_agaci' => self::resolveAdresNode($raw),
            default => is_scalar($raw) ? $raw : (string) $raw,
        };
    }

    /**
     * @return array{ids: string, items: list<array<string, mixed>>}|null
     */
    private static function resolveIcd10List(mixed $raw): ?array
    {
        $ids = self::csvToIntIds($raw);
        if ($ids === []) {
            return null;
        }
        $items = self::fetchHastalikItems($ids);

        return [
            'ids' => implode(',', $ids),
            'items' => $items,
            'icd' => array_values(array_filter(array_map(
                static fn (array $row): string => (string) ($row['icd'] ?? ''),
                $items
            ))),
        ];
    }

    /**
     * @return array{ids: string, items: list<array<string, mixed>>}|null
     */
    private static function resolveIdLabelList(mixed $raw, string $tableSuffix, string $labelCol): ?array
    {
        $ids = self::csvToIntIds($raw);
        if ($ids === []) {
            return null;
        }
        $items = self::fetchIdLabelRows($tableSuffix, $labelCol, $ids);

        return [
            'ids' => implode(',', $ids),
            'items' => $items,
        ];
    }

    /**
     * @return array{id: int, label: string}|null
     */
    private static function resolveSingleIdLabel(mixed $raw, string $tableSuffix, string $labelCol): ?array
    {
        $id = (int) (is_scalar($raw) ? $raw : 0);
        if ($id === null) {
            return null;
        }
        $rows = self::fetchIdLabelRows($tableSuffix, $labelCol, [$id]);
        if ($rows === []) {
            return SkrsMapHelper::enrichGuvence(['id' => $id, 'label' => '']);
        }

        return SkrsMapHelper::enrichGuvence([
            'id' => $id,
            'label' => (string) ($rows[0]['label'] ?? ''),
        ]);
    }

    /**
     * @return array{id: int, label: string, unvan?: string}|array{ids: string, items: list<array<string, mixed>>}|null
     */
    private static function resolveUsers(mixed $raw): ?array
    {
        $ids = self::csvToIntIds($raw);
        if ($ids === []) {
            $id = (int) (is_scalar($raw) ? $raw : 0);
            if ($id === null) {
                return null;
            }
            $ids = [$id];
        }
        if (count($ids) === 1) {
            $row = self::fetchUserRow($ids[0]);
            if ($row === null) {
                return ['id' => $ids[0], 'label' => ''];
            }

            return SkrsMapHelper::enrichUser($row);
        }
        $items = [];
        foreach ($ids as $id) {
            $row = self::fetchUserRow($id);
            $items[] = SkrsMapHelper::enrichUser($row ?? ['id' => $id, 'label' => '']);
        }

        return [
            'ids' => implode(',', $ids),
            'items' => $items,
        ];
    }

    /**
     * @return array{id: int, label: string, plaka?: string}|null
     */
    private static function resolveArac(mixed $raw): ?array
    {
        $id = (int) (is_scalar($raw) ? $raw : 0);
        if ($id === null) {
            return null;
        }
        $cacheKey = 'arac:' . $id;
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }
        try {
            $db = Database::getInstance();
            $row = $db->fetchObjectPrepared(
                'SELECT id, plaka, arac_bilgisi FROM #__araclar WHERE id = ? LIMIT 1',
                [$id]
            );
            if (!$row) {
                self::$cache[$cacheKey] = ['id' => $id, 'label' => '', 'plaka' => ''];

                return self::$cache[$cacheKey];
            }
            $label = trim((string) ($row->plaka ?? ''));
            if ($label === '') {
                $label = trim((string) ($row->arac_bilgisi ?? ''));
            }
            self::$cache[$cacheKey] = [
                'id' => $id,
                'label' => $label,
                'plaka' => trim((string) ($row->plaka ?? '')),
            ];

            return self::$cache[$cacheKey];
        } catch (\Throwable $e) {
            return ['id' => $id, 'label' => ''];
        }
    }

    /**
     * @return array{id: string, label: string, tip?: string}|null
     */
    private static function resolveAdresNode(mixed $raw): ?array
    {
        $id = trim((string) (is_scalar($raw) ? $raw : ''));
        if ($id === '') {
            return null;
        }
        $cacheKey = 'adres:' . $id;
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }
        try {
            $db = Database::getInstance();
            $row = $db->fetchObjectPrepared(
                'SELECT id, adi, tip FROM #__adrestablosu WHERE id = ? LIMIT 1',
                [$id]
            );
            if (!$row) {
                self::$cache[$cacheKey] = SkrsMapHelper::enrichAdres(['id' => $id, 'label' => '']);

                return self::$cache[$cacheKey];
            }
            self::$cache[$cacheKey] = SkrsMapHelper::enrichAdres([
                'id' => (string) ($row->id ?? $id),
                'label' => (string) ($row->adi ?? ''),
                'tip' => (string) ($row->tip ?? ''),
            ]);

            return self::$cache[$cacheKey];
        } catch (\Throwable $e) {
            return SkrsMapHelper::enrichAdres(['id' => $id, 'label' => '']);
        }
    }

    /**
     * @param int[] $ids
     * @return list<array{id: int, icd: string, ad: string}>
     */
    private static function fetchHastalikItems(array $ids): array
    {
        $cacheKey = 'hastalik:' . implode(',', $ids);
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }
        try {
            $db = Database::getInstance();
            $inSql = implode(',', array_fill(0, count($ids), '?'));
            $rows = $db->fetchObjectListPrepared(
                "SELECT id, icd, hastalikadi FROM #__hastaliklar WHERE id IN ({$inSql}) ORDER BY id ASC",
                $ids
            );
            if (!is_array($rows)) {
                self::$cache[$cacheKey] = [];

                return [];
            }
            $out = [];
            foreach ($rows as $row) {
                $out[] = [
                    'id' => (string) ($row->id ?? ''),
                    'icd' => trim((string) ($row->icd ?? '')),
                    'ad' => (string) ($row->hastalikadi ?? ''),
                ];
            }
            self::$cache[$cacheKey] = $out;

            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @param int[] $ids
     * @return list<array{id: int, label: string}>
     */
    private static function fetchIdLabelRows(string $tableSuffix, string $labelCol, array $ids): array
    {
        $cacheKey = $tableSuffix . ':' . implode(',', $ids);
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }
        try {
            $db = Database::getInstance();
            $inSql = implode(',', array_fill(0, count($ids), '?'));
            $rows = $db->fetchObjectListPrepared(
                "SELECT id, {$labelCol} AS lbl FROM #__{$tableSuffix} WHERE id IN ({$inSql}) ORDER BY id ASC",
                $ids
            );
            if (!is_array($rows)) {
                self::$cache[$cacheKey] = [];

                return [];
            }
            $out = [];
            foreach ($rows as $row) {
                $out[] = [
                    'id' => (string) ($row->id ?? ''),
                    'label' => (string) ($row->lbl ?? ''),
                ];
            }
            self::$cache[$cacheKey] = $out;

            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array{id: int, label: string, unvan: string}|null
     */
    private static function fetchUserRow(int $id): ?array
    {
        $cacheKey = 'user:' . $id;
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }
        try {
            $db = Database::getInstance();
            $row = $db->fetchObjectPrepared(
                'SELECT id, name, unvan FROM #__users WHERE id = ? LIMIT 1',
                [$id]
            );
            if (!$row) {
                return null;
            }
            self::$cache[$cacheKey] = [
                'id' => $id,
                'label' => trim((string) ($row->name ?? '')),
                'unvan' => trim((string) ($row->unvan ?? '')),
            ];

            return self::$cache[$cacheKey];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return int[]
     */
    private static function csvToIntIds(mixed $raw): array
    {
        if (is_array($raw)) {
            $out = [];
            foreach ($raw as $p) {
                $n = (int) trim((string) $p);
                if ($n > 0) {
                    $out[$n] = $n;
                }
            }

            return array_values($out);
        }

        $csv = trim((string) $raw);
        if ($csv === '') {
            return [];
        }

        if ($csv === (string) (int) $csv && (int) $csv > 0) {
            return [(int) $csv];
        }

        return Patient::parseHastalikCsvToIntIds($csv);
    }
}
