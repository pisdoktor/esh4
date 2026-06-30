<?php

declare(strict_types=1);



namespace App\Helpers;



/**

 * İki kırılımlı ısı tablo veri yapısı.

 */

final class StatsCrossTabMatrix {

    /**

     * @param list<string|int> $rowKeys

     * @param list<string|int> $colKeys

     * @param array<string|int, string> $rowLabels

     * @param array<string|int, string> $colLabels

     */

    public static function create(

        array $rowKeys,

        array $colKeys,

        array $rowLabels = [],

        array $colLabels = []

    ): array {

        $rowKeys = self::normalizeKeyList($rowKeys);

        $colKeys = self::normalizeKeyList($colKeys);

        $rowLabels = self::normalizeLabelMap($rowLabels);

        $colLabels = self::normalizeLabelMap($colLabels);



        $matrix = [];

        $rowTotals = [];

        $colTotals = array_fill_keys($colKeys, 0);

        foreach ($rowKeys as $rk) {

            $matrix[$rk] = array_fill_keys($colKeys, 0);

            $rowTotals[$rk] = 0;

        }

        foreach ($rowKeys as $rk) {

            if (!isset($rowLabels[$rk])) {

                $rowLabels[$rk] = $rk;

            }

        }

        foreach ($colKeys as $ck) {

            if (!isset($colLabels[$ck])) {

                $colLabels[$ck] = $ck;

            }

        }



        return [

            'row_keys' => $rowKeys,

            'col_keys' => $colKeys,

            'row_labels' => $rowLabels,

            'col_labels' => $colLabels,

            'matrix' => $matrix,

            'row_totals' => $rowTotals,

            'col_totals' => $colTotals,

            'grand_total' => 0,

        ];

    }



    public static function add(array &$pack, string|int $rowKey, string|int $colKey, int $n = 1): void {

        if ($n < 1) {

            return;

        }

        $rowKey = (string) $rowKey;

        $colKey = (string) $colKey;

        if (!isset($pack['matrix'][$rowKey][$colKey])) {

            return;

        }

        $pack['matrix'][$rowKey][$colKey] += $n;

        $pack['row_totals'][$rowKey] = (int) ($pack['row_totals'][$rowKey] ?? 0) + $n;

        $pack['col_totals'][$colKey] = (int) ($pack['col_totals'][$colKey] ?? 0) + $n;

        $pack['grand_total'] = (int) ($pack['grand_total'] ?? 0) + $n;

    }



    /** @param array<string|int, int> $counts row => total */

    public static function topKeys(array $counts, int $limit, string $otherKey = '_diger'): array {

        arsort($counts);

        $keys = array_keys($counts);

        $top = array_slice($keys, 0, max(1, $limit));

        if (count($keys) <= $limit) {

            return self::normalizeKeyList($top);

        }

        $rest = array_slice($keys, $limit);

        $otherTotal = 0;

        foreach ($rest as $k) {

            $otherTotal += (int) ($counts[$k] ?? 0);

        }

        if ($otherTotal > 0) {

            $top[] = $otherKey;

            $counts[$otherKey] = $otherTotal;

        }



        return self::normalizeKeyList($top);

    }



    /** @param list<string|int> $rowKeys */

    public static function sortRowsByTotal(array $pack, array $rowKeys): array {

        $totals = $pack['row_totals'] ?? [];

        usort($rowKeys, static function ($a, $b) use ($totals) {

            $aKey = (string) $a;

            $bKey = (string) $b;



            return ((int) ($totals[$bKey] ?? $totals[$b] ?? 0)) <=> ((int) ($totals[$aKey] ?? $totals[$a] ?? 0));

        });



        return self::normalizeKeyList($rowKeys);

    }



    /**

     * SQL pivot satırlarından matris (row_key, col_key, adet).

     *

     * @param list<object> $rows

     */

    public static function fromSqlRows(

        array $rows,

        string $rowField,

        string $colField,

        string $countField = 'adet',

        ?callable $rowMap = null,

        ?callable $colMap = null

    ): array {

        $rowSet = [];

        $colSet = [];

        $pairs = [];

        foreach ($rows as $r) {

            $rk = (string) ($r->$rowField ?? '');

            $ck = (string) ($r->$colField ?? '');

            if ($rowMap) {

                $rk = (string) $rowMap($rk, $r);

            }

            if ($colMap) {

                $ck = (string) $colMap($ck, $r);

            }

            if ($rk === '' || $ck === '') {

                continue;

            }

            $rowSet[$rk] = true;

            $colSet[$ck] = true;

            $pairs[] = [$rk, $ck, (int) ($r->$countField ?? 0)];

        }

        $rowKeys = array_keys($rowSet);

        $colKeys = array_keys($colSet);

        sort($colKeys);

        $pack = self::create($rowKeys, $colKeys);

        foreach ($pairs as [$rk, $ck, $n]) {

            self::add($pack, $rk, $ck, $n);

        }

        $pack['row_keys'] = self::sortRowsByTotal($pack, $pack['row_keys']);



        return $pack;

    }



    /** @param list<string|int> $keys @return list<string> */

    private static function normalizeKeyList(array $keys): array {

        return array_map(static fn ($key): string => (string) $key, array_values($keys));

    }



    /** @param array<string|int, string> $labels @return array<string, string> */

    private static function normalizeLabelMap(array $labels): array {

        $out = [];

        foreach ($labels as $key => $label) {

            $out[(string) $key] = (string) $label;

        }



        return $out;

    }

}

