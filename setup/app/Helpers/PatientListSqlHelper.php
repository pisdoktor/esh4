<?php

declare(strict_types=1);



namespace App\Helpers;



use App\Core\Database;

use App\Helpers\IdHelper;



/**

 * Hasta listelerinde izlem/plan özetleri — deferred join + aggregate (correlated alt sorgu yerine).

 */

final class PatientListSqlHelper

{

    /** Patient birleşik/legacy listeler (a1–a4, ilce_adi …). */

    public static function addressJoinDefault(): string

    {

        return 'LEFT JOIN #__adrestablosu a1 ON h.ilce = a1.id

            LEFT JOIN #__adrestablosu a2 ON h.mahalle = a2.id

            LEFT JOIN #__adrestablosu a3 ON h.sokak = a3.id

            LEFT JOIN #__adrestablosu a4 ON h.kapino = a4.id';

    }



    /** İstatistik adres filtresi (ilc, m, s, k). */

    public static function addressJoinStatsAdres(): string

    {

        return 'LEFT JOIN #__adrestablosu AS ilc ON ilc.id = h.ilce

            LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle

            LEFT JOIN #__adrestablosu AS s ON s.id = h.sokak

            LEFT JOIN #__adrestablosu AS k ON k.id = h.kapino';

    }



    /**

     * Son izlem tarihi (yapıldı filtresi yok).

     *

     * @param string $tcInSubquery Boş = tüm izlemler; dolu = yalnızca alt küme (harita için).

     */

    public static function lastIzlemDateJoinSql(string $tcInSubquery = ''): string

    {

        if (!TenantSqlHelper::izlemScopedToPatientKurum()) {

            if ($tcInSubquery === '') {

                return " LEFT JOIN (

                    SELECT hastatckimlik, MAX(izlemtarihi) AS sonizlem

                    FROM #__izlemler

                    GROUP BY hastatckimlik

                ) iz_last ON iz_last.hastatckimlik = h.tckimlik";

            }



            return " LEFT JOIN (

                SELECT hastatckimlik, MAX(izlemtarihi) AS sonizlem

                FROM #__izlemler

                WHERE hastatckimlik IN ({$tcInSubquery})

                GROUP BY hastatckimlik

            ) iz_last ON iz_last.hastatckimlik = h.tckimlik";

        }



        if ($tcInSubquery === '') {

            return " LEFT JOIN (

                SELECT i.hastatckimlik, h_iz.kurum_id, MAX(i.izlemtarihi) AS sonizlem

                FROM #__izlemler i

                INNER JOIN #__hastalar h_iz ON h_iz.tckimlik = i.hastatckimlik AND h_iz.kurum_id = i.kurum_id

                GROUP BY i.hastatckimlik, h_iz.kurum_id

            ) iz_last ON iz_last.hastatckimlik = h.tckimlik AND iz_last.kurum_id = h.kurum_id";

        }



        return " LEFT JOIN (

            SELECT i.hastatckimlik, h_iz.kurum_id, MAX(i.izlemtarihi) AS sonizlem

            FROM #__izlemler i

            INNER JOIN #__hastalar h_iz ON h_iz.tckimlik = i.hastatckimlik AND h_iz.kurum_id = i.kurum_id

            WHERE h_iz.tckimlik IN ({$tcInSubquery})

            GROUP BY i.hastatckimlik, h_iz.kurum_id

        ) iz_last ON iz_last.hastatckimlik = h.tckimlik AND iz_last.kurum_id = h.kurum_id";

    }



    /** Mama/bez rapor listesi (il, m). */

    public static function addressJoinIlMah(): string

    {

        return 'LEFT JOIN #__adrestablosu il ON il.id = h.ilce

            LEFT JOIN #__adrestablosu m ON m.id = h.mahalle';

    }



    /**

     * @param string $hastaScopeWhere Örn. h_iz.id IN (1,2,3)

     */

    public static function izlemPlanAggregateJoins(

        string $hastaScopeWhere,

        bool $fullStats,

        bool $planPendingOnly,

        bool $includeSonIzlemDate = true,

        bool $matchIzlemToPatientKurum = false

    ): string {

        if (!TenantSqlHelper::scopeIzlemToPatientKurum($matchIzlemToPatientKurum)) {

            return self::izlemPlanAggregateJoinsAllKurum(

                $hastaScopeWhere,

                $fullStats,

                $planPendingOnly,

                $includeSonIzlemDate

            );

        }



        if (!$fullStats) {

            return " LEFT JOIN (

                SELECT i.hastatckimlik, h_iz.kurum_id,

                    MAX(CASE WHEN i.yapildimi = 1 THEN i.izlemtarihi END) AS sonizlemtarihi

                FROM #__izlemler i

                INNER JOIN #__hastalar h_iz ON h_iz.tckimlik = i.hastatckimlik AND h_iz.kurum_id = i.kurum_id

                WHERE {$hastaScopeWhere}

                GROUP BY i.hastatckimlik, h_iz.kurum_id

            ) iz ON iz.hastatckimlik = h.tckimlik AND iz.kurum_id = h.kurum_id";

        }



        $planWhere = $planPendingOnly ? ' AND p.durum = 0' : '';

        $sonSelect = $includeSonIzlemDate

            ? 'MAX(CASE WHEN i.yapildimi = 1 THEN i.izlemtarihi END) AS sonizlemtarihi,'

            : '';



        return " LEFT JOIN (

                SELECT i.hastatckimlik, h_iz.kurum_id,

                    {$sonSelect}

                    SUM(i.yapildimi = 1) AS izlemsayisi,

                    SUM(i.yapildimi = 0) AS yizlemsayisi

                FROM #__izlemler i

                INNER JOIN #__hastalar h_iz ON h_iz.tckimlik = i.hastatckimlik AND h_iz.kurum_id = i.kurum_id

                WHERE {$hastaScopeWhere}

                GROUP BY i.hastatckimlik, h_iz.kurum_id

            ) iz ON iz.hastatckimlik = h.tckimlik AND iz.kurum_id = h.kurum_id

            LEFT JOIN (

                SELECT p.hastatckimlik, h_pl.kurum_id, COUNT(*) AS totalplanli

                FROM #__pizlemler p

                INNER JOIN #__hastalar h_pl ON h_pl.tckimlik = p.hastatckimlik AND h_pl.kurum_id = p.kurum_id

                WHERE " . str_replace('h_iz.', 'h_pl.', $hastaScopeWhere) . "{$planWhere}

                GROUP BY p.hastatckimlik, h_pl.kurum_id

            ) pl ON pl.hastatckimlik = h.tckimlik AND pl.kurum_id = h.kurum_id";

    }



    /**

     * Süper yönetici: TC bazlı tüm kurum izlem/plan özetleri.

     *

     * @param string $tcInSubquery SELECT tckimlik FROM #__hastalar WHERE id IN (...)

     */

    private static function izlemPlanAggregateJoinsAllKurum(

        string $tcInSubquery,

        bool $fullStats,

        bool $planPendingOnly,

        bool $includeSonIzlemDate = true

    ): string {

        if (!$fullStats) {

            return " LEFT JOIN (

                SELECT hastatckimlik,

                    MAX(CASE WHEN yapildimi = 1 THEN izlemtarihi END) AS sonizlemtarihi

                FROM #__izlemler

                WHERE hastatckimlik IN ({$tcInSubquery})

                GROUP BY hastatckimlik

            ) iz ON iz.hastatckimlik = h.tckimlik";

        }



        $planWhere = $planPendingOnly ? ' AND durum = 0' : '';

        $sonSelect = $includeSonIzlemDate

            ? 'MAX(CASE WHEN yapildimi = 1 THEN izlemtarihi END) AS sonizlemtarihi,'

            : '';



        return " LEFT JOIN (

                SELECT hastatckimlik,

                    {$sonSelect}

                    SUM(yapildimi = 1) AS izlemsayisi,

                    SUM(yapildimi = 0) AS yizlemsayisi

                FROM #__izlemler

                WHERE hastatckimlik IN ({$tcInSubquery})

                GROUP BY hastatckimlik

            ) iz ON iz.hastatckimlik = h.tckimlik

            LEFT JOIN (

                SELECT hastatckimlik, COUNT(*) AS totalplanli

                FROM #__pizlemler

                WHERE hastatckimlik IN ({$tcInSubquery}){$planWhere}

                GROUP BY hastatckimlik

            ) pl ON pl.hastatckimlik = h.tckimlik";

    }



    public static function izlemPlanSelectColumns(

        bool $fullStats,

        bool $includeSonIzlemDate = true

    ): string {

        if (!$fullStats) {

            return 'iz.sonizlemtarihi';

        }



        $parts = [];

        if ($includeSonIzlemDate) {

            $parts[] = 'iz.sonizlemtarihi';

        }

        $parts[] = 'COALESCE(iz.izlemsayisi, 0) AS izlemsayisi';

        $parts[] = 'COALESCE(iz.yizlemsayisi, 0) AS yizlemsayisi';

        $parts[] = 'COALESCE(pl.totalplanli, 0) AS totalplanli';



        return implode(",\n            ", $parts);

    }



    /**

     * Sayfalı liste: önce id, sonra yalnızca sayfa hastaları için izlem/plan agregası.

     *

     * @param string $where `WHERE ...` ile başlamalı

     * @param string $orderBy `ORDER BY ...` (tam ifade)

     * @param string $patientSelectSql h.* veya seçili sütunlar + adres aliasları (izlem sütunları hariç)

     *

     * @return list<object>

     */

    public static function fetchPage(

        Database $db,

        string $where,

        string $addressJoinSql,

        string $patientSelectSql,

        string $orderBy,

        int $limit,

        int $offset,

        bool $fullStats = true,

        bool $planPendingOnly = false,

        bool $includeSonIzlemDate = true,

        bool $matchIzlemToPatientKurum = false

    ): array {

        $limit = max(1, (int) $limit);

        $offset = max(0, (int) $offset);

        $where = trim($where);

        if ($where !== '' && stripos($where, 'WHERE') !== 0) {

            $where = 'WHERE ' . $where;

        }

        $orderBy = self::resolveOrderByForIdPage(trim($orderBy), $matchIzlemToPatientKurum);

        if ($orderBy === '') {

            $orderBy = 'ORDER BY h.id ASC';

        } elseif (stripos($orderBy, 'ORDER BY') !== 0) {

            $orderBy = 'ORDER BY ' . $orderBy;

        }



        $pageSql = "SELECT h.id FROM #__hastalar h {$addressJoinSql} {$where} {$orderBy} LIMIT {$offset}, {$limit}";

        $ids = $db->fetchColumnListPrepared($pageSql);

        if ($ids === []) {

            return [];

        }



        $idList = self::quotedEntityIdInList($db, $ids);

        if ($idList === null) {

            return [];

        }

        $tcSub = "SELECT tckimlik FROM #__hastalar WHERE id IN ({$idList})";

        $hastaScope = TenantSqlHelper::scopeIzlemToPatientKurum($matchIzlemToPatientKurum)

            ? "h_iz.id IN ({$idList})"

            : $tcSub;

        $selectExtra = self::izlemPlanSelectColumns($fullStats, $includeSonIzlemDate);

        $aggJoins = self::izlemPlanAggregateJoins($hastaScope, $fullStats, $planPendingOnly, $includeSonIzlemDate, $matchIzlemToPatientKurum);



        $sql = "SELECT {$patientSelectSql},

            {$selectExtra}

            FROM #__hastalar h

            {$addressJoinSql}

            {$aggJoins}

            WHERE h.id IN ({$idList})

            ORDER BY FIELD(h.id, {$idList})";



        return $db->fetchObjectListPrepared($sql);

    }



    /**
     * ID sayfalama sorgusu: iz join olmadan sıralanabilir son izlem ifadesi.

     */

    private static function resolveOrderByForIdPage(string $orderBy, bool $matchIzlemToPatientKurum = false): string

    {

        if ($orderBy === '') {

            return '';

        }

        $expr = QueryHelper::patientSonIzlemDateSqlExpr($matchIzlemToPatientKurum);

        $hasPrefix = (bool) preg_match('/^\s*ORDER\s+BY\b/i', $orderBy);

        $body = $hasPrefix ? preg_replace('/^\s*ORDER\s+BY\s+/i', '', $orderBy, 1) : $orderBy;

        $body = preg_replace('/\biz\.sonizlemtarihi\b/i', $expr, $body);

        $body = preg_replace('/(?<!\.)\bsonizlemtarihi\b/i', $expr, $body);

        $body = preg_replace('/\biz\.sonizlem\b/i', $expr, $body);

        $body = preg_replace('/(?<!\.)\bsonizlem\b/i', $expr, $body);



        return $hasPrefix ? 'ORDER BY ' . $body : $body;

    }



    /**

     * @param list<mixed> $ids

     */

    private static function quotedEntityIdInList(Database $db, array $ids): ?string

    {

        $quoted = [];

        foreach ($ids as $id) {

            $norm = IdHelper::normalizeRequestId($id);

            if ($norm !== null) {

                $quoted[] = $db->quote($norm);

            }

        }

        if ($quoted === []) {

            return null;

        }



        return implode(',', $quoted);

    }

}


