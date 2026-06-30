<?php
namespace App\Helpers;

use App\Models\Brans;
use App\Models\Istek;

/**
 * Konsültasyon EK-3: branş id → istek id listesi eşlemesi (JSON / legacy CSV).
 */
final class KonsBransIstekHelper {

    /** @var array<int, string> */
    private static array $bransNameCache = [];

    /** @var array<int, string> */
    private static array $istekNameCache = [];

    /**
     * @return array<int, int[]>
     */
    public static function decode(?string $json): array {
        $json = trim((string) $json);
        if ($json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        return self::normalizeMap($decoded);
    }

    /**
     * @param array<int, int[]> $map
     */
    public static function encode(array $map): string {
        $map = self::normalizeMap($map);
        if ($map === []) {
            return '';
        }
        $json = json_encode($map, JSON_UNESCAPED_UNICODE);
        return is_string($json) ? $json : '';
    }

    /**
     * Eski kayıt: brans + kons_istekler CSV → her branşa aynı istek listesi.
     *
     * @return array<int, int[]>
     */
    public static function fromLegacyCsv(string $bransCsv, string $istekCsv): array {
        $bransIds = self::parseCsvIds($bransCsv);
        $istekIds = self::parseCsvIds($istekCsv);
        if ($bransIds === [] || $istekIds === []) {
            return [];
        }
        $map = [];
        foreach ($bransIds as $bid) {
            $map[$bid] = $istekIds;
        }
        return $map;
    }

    /**
     * JSON boşsa legacy CSV'den harita üret.
     *
     * @return array<int, int[]>
     */
    public static function resolveMap(?string $konsBransIstekJson, string $bransCsv, string $istekCsv): array {
        $map = self::decode($konsBransIstekJson);
        if ($map !== []) {
            return $map;
        }
        return self::fromLegacyCsv($bransCsv, $istekCsv);
    }

    /**
     * @param int[] $bransArr
     * @param array<int|string, mixed> $istekByBrans POST istek[bransId][]
     * @return array<int, int[]>
     */
    public static function fromPost(array $bransArr, array $istekByBrans): array {
        $bransArr = array_values(array_unique(array_filter(array_map('intval', $bransArr))));
        $map = [];
        foreach ($bransArr as $bid) {
            if ($bid < 1) {
                continue;
            }
            $raw = $istekByBrans[$bid] ?? $istekByBrans[(string) $bid] ?? [];
            if (!is_array($raw)) {
                $raw = [];
            }
            $ids = array_values(array_unique(array_filter(array_map('intval', $raw))));
            if ($ids !== []) {
                $map[$bid] = $ids;
            }
        }
        return self::normalizeMap($map);
    }

    /**
     * @param array<int, int[]> $map
     * @return int[]
     */
    public static function unionIstekIds(array $map): array {
        $all = [];
        foreach ($map as $ids) {
            foreach ($ids as $iid) {
                $all[] = (int) $iid;
            }
        }
        return array_values(array_unique(array_filter($all)));
    }

    /**
     * @param array<int, int[]> $map
     * @return int[]
     */
    public static function bransIds(array $map): array {
        return array_values(array_unique(array_filter(array_map('intval', array_keys($map)))));
    }

    /**
     * @param array<int, int[]> $map
     */
    public static function bransCsv(array $map): string {
        $ids = self::bransIds($map);
        return $ids !== [] ? implode(',', $ids) : '';
    }

    /**
     * @param array<int, int[]> $map
     */
    public static function istekCsv(array $map): string {
        $ids = self::unionIstekIds($map);
        return $ids !== [] ? implode(',', $ids) : '';
    }

    /**
     * Seçili her branşta en az bir istek olmalı.
     *
     * @param int[] $selectedBrans POST brans[]
     * @param array<int, int[]> $map
     */
    public static function validateSelectedBranchesHaveIstek(array $selectedBrans, array $map): bool {
        $selectedBrans = array_values(array_unique(array_filter(array_map('intval', $selectedBrans))));
        if ($selectedBrans === []) {
            return false;
        }
        foreach ($selectedBrans as $bid) {
            if ($bid < 1 || empty($map[$bid])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Görüntü metni: «Nöroloji: MAMA RAPORU YENİLEME, Üroloji: BEZ RAPORU YENİLEME»
     */
    public static function pairedDisplayText(
        ?string $konsBransIstekJson,
        ?string $bransCsv,
        ?string $istekCsv
    ): string {
        $map = self::resolveMap($konsBransIstekJson, (string) $bransCsv, (string) $istekCsv);
        if ($map === []) {
            return '';
        }

        $segments = [];
        foreach ($map as $bransId => $istekIds) {
            $bransName = self::resolveBransName((int) $bransId);
            if ($bransName === '') {
                continue;
            }
            $istekNames = [];
            foreach ($istekIds as $iid) {
                $name = self::resolveIstekName((int) $iid);
                if ($name !== '') {
                    $istekNames[] = $name;
                }
            }
            if ($istekNames === []) {
                continue;
            }
            $segments[] = [
                'brans' => $bransName,
                'text' => $bransName . ': ' . implode(', ', $istekNames),
            ];
        }
        if ($segments === []) {
            return '';
        }

        usort($segments, static function (array $a, array $b): int {
            return strcasecmp($a['brans'], $b['brans']);
        });

        return implode(', ', array_column($segments, 'text'));
    }

    /**
     * Haritadan «Branş: istek» etiket listesi (istatistik sayımı için).
     *
     * @param array<int, int[]> $map
     * @param array<int, string> $bransNameMap
     * @param array<int, string> $istekNameMap
     * @return list<string>
     */
    public static function pairedLabelsFromMap(array $map, array $bransNameMap, array $istekNameMap): array {
        if ($map === []) {
            return [];
        }

        $labels = [];
        foreach ($map as $bransId => $istekIds) {
            $bid = (int) $bransId;
            if ($bid < 1) {
                continue;
            }
            $bransName = trim((string) ($bransNameMap[$bid] ?? ''));
            if ($bransName === '') {
                $bransName = 'Branş #' . $bid;
            }
            foreach ($istekIds as $iid) {
                $iid = (int) $iid;
                if ($iid < 1) {
                    continue;
                }
                $istekName = trim((string) ($istekNameMap[$iid] ?? ''));
                if ($istekName === '') {
                    $istekName = 'İstek #' . $iid;
                }
                $labels[] = $bransName . ': ' . $istekName;
            }
        }

        return $labels;
    }

    private static function resolveBransName(int $bransId): string {
        if ($bransId < 1) {
            return '';
        }
        if (array_key_exists($bransId, self::$bransNameCache)) {
            return self::$bransNameCache[$bransId];
        }
        $m = new Brans();
        $name = ($m->load($bransId) && trim((string) $m->bransadi) !== '')
            ? trim((string) $m->bransadi)
            : '';
        self::$bransNameCache[$bransId] = $name;

        return $name;
    }

    private static function resolveIstekName(int $istekId): string {
        if ($istekId < 1) {
            return '';
        }
        if (array_key_exists($istekId, self::$istekNameCache)) {
            return self::$istekNameCache[$istekId];
        }
        $m = new Istek();
        $name = ($m->load($istekId) && trim((string) $m->istek_adi) !== '')
            ? trim((string) $m->istek_adi)
            : '';
        self::$istekNameCache[$istekId] = $name;

        return $name;
    }

    /**
     * @return int[]
     */
    private static function parseCsvIds(string $csv): array {
        return array_values(array_unique(array_filter(array_map('intval', preg_split(
            '/\s*,\s*/',
            str_replace(' ', '', $csv),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: []))));
    }

    /**
     * @param array<mixed, mixed> $raw
     * @return array<int, int[]>
     */
    private static function normalizeMap(array $raw): array {
        $map = [];
        foreach ($raw as $bransKey => $istekList) {
            $bid = (int) $bransKey;
            if ($bid < 1) {
                continue;
            }
            if (!is_array($istekList)) {
                continue;
            }
            $ids = array_values(array_unique(array_filter(array_map('intval', $istekList))));
            if ($ids !== []) {
                $map[$bid] = $ids;
            }
        }
        ksort($map);
        return $map;
    }
}
