<?php
namespace App\Models;

use App\Helpers\CatalogStoreHelper;
use App\Helpers\TenantContext;

/**
 * Hastalık kütüphanesi — platform kataloğu (kurum_id=0); kurum seçimi #__kurum_hastalik.
 */
class Hastalik extends BaseModel {

    public $id = null;
    public $kurum_id = 0;
    public $cat = 0;
    public $hastalikadi = null;
    public $icd = null;
    public $parent_icd = null;
    public $seviye = null;

    public function __construct() {
        parent::__construct('#__hastaliklar', 'id');
    }

    /** @return list<object> */
    public function getCatalogList(string $orderFragment = 'hastalikadi ASC'): array {
        $orderFragment = $this->sanitizeOrderFragment($orderFragment, 'hastalikadi ASC');
        $sql = 'SELECT * FROM #__hastaliklar WHERE kurum_id = ? ORDER BY ' . $orderFragment;
        $rows = $this->db->fetchObjectListPrepared($sql, [CatalogStoreHelper::PLATFORM_KURUM_ID]);
        if (is_array($rows) && $rows !== []) {
            return $rows;
        }
        $legacy = $this->db->fetchObjectListPrepared('SELECT * FROM #__hastaliklar ORDER BY hastalikadi ASC');

        return is_array($legacy) ? $legacy : [];
    }

    /** @return list<object> */
    public function getAssignedListForKurum(int $kurumId): array {
        if ($kurumId <= 0) {
            return [];
        }
        if (!KurumHastalik::tableExists()) {
            return $this->getCatalogList();
        }
        $sql = 'SELECT h.*
            FROM #__hastaliklar AS h
            INNER JOIN #__kurum_hastalik AS kh ON kh.hastalik_id = h.id AND kh.kurum_id = ?
            WHERE h.kurum_id = ?
            ORDER BY h.hastalikadi ASC';
        $rows = $this->db->fetchObjectListPrepared($sql, [$kurumId, CatalogStoreHelper::PLATFORM_KURUM_ID]);

        return is_array($rows) ? $rows : [];
    }

    /** @return list<object> */
    public function getList(?int $kurumId = null): array {
        $kid = ($kurumId !== null && $kurumId > 0) ? $kurumId : TenantContext::filterKurumId();
        if ($kid === null || $kid <= 0) {
            return [];
        }

        return $this->getAssignedListForKurum((int) $kid);
    }

    /** @return list<object> */
    public function getListWithAssignmentState(int $kurumId, array $catalogRows): array {
        if ($kurumId <= 0 || !KurumHastalik::tableExists()) {
            foreach ($catalogRows as $row) {
                $row->assigned = false;
            }

            return $catalogRows;
        }
        $assigned = array_flip((new KurumHastalik())->getAssignedIds($kurumId));
        foreach ($catalogRows as $row) {
            $row->assigned = isset($assigned[(int) ($row->id ?? 0)]);
        }

        return $catalogRows;
    }

    /** @param list<object> $rows @param list<int|string> $ids @return list<object> */
    public function ensureIdsInList(array $rows, array $ids): array
    {
        $icds = [];
        foreach ($ids as $id) {
            if (is_int($id) || (is_string($id) && preg_match('/^\d+$/', $id))) {
                continue;
            }
            $norm = Patient::normalizeHastalikIcd((string) $id);
            if ($norm !== '') {
                $icds[] = $norm;
            }
        }

        return $this->ensureIcdsInList($rows, array_merge($icds, $this->resolveIdsToIcdsForEnsure($ids)));
    }

    /**
     * @param list<object> $rows
     * @param list<int|string> $ids
     * @return list<string>
     */
    private function resolveIdsToIcdsForEnsure(array $ids): array
    {
        $legacyIds = [];
        foreach ($ids as $id) {
            if (is_int($id) || (is_string($id) && preg_match('/^\d+$/', (string) $id))) {
                $n = (int) $id;
                if ($n > 0) {
                    $legacyIds[$n] = $n;
                }
            }
        }
        if ($legacyIds === []) {
            return [];
        }
        [$inSql, $inParams] = $this->db->whereInClause(array_values($legacyIds));
        $rows = $this->db->fetchColumnListPrepared(
            "SELECT icd FROM #__hastaliklar WHERE id IN ({$inSql}) AND TRIM(COALESCE(icd, '')) <> ''",
            $inParams,
            0
        );
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $icd) {
            $norm = Patient::normalizeHastalikIcd((string) $icd);
            if ($norm !== '') {
                $out[] = $norm;
            }
        }

        return $out;
    }

    /** @param list<object> $rows @param list<string> $icds @return list<object> */
    public function ensureIcdsInList(array $rows, array $icds): array
    {
        $existing = [];
        foreach ($rows as $row) {
            $icd = Patient::normalizeHastalikIcd((string) ($row->icd ?? ''));
            if ($icd !== '') {
                $existing[$icd] = true;
            }
        }
        $missing = [];
        foreach ($icds as $icd) {
            $icd = Patient::normalizeHastalikIcd((string) $icd);
            if ($icd !== '' && !isset($existing[$icd])) {
                $missing[] = $icd;
            }
        }
        if ($missing === []) {
            return $rows;
        }
        [$inSql, $inParams] = $this->db->whereInClause($missing);
        $params = array_merge([CatalogStoreHelper::PLATFORM_KURUM_ID], $inParams);
        $extra = $this->db->fetchObjectListPrepared(
            "SELECT * FROM #__hastaliklar WHERE kurum_id = ? AND icd IN ({$inSql}) ORDER BY hastalikadi ASC",
            $params
        );
        if (!is_array($extra) || $extra === []) {
            return $rows;
        }

        return array_merge($rows, $extra);
    }

    /** @return list<object> */
    public function searchCatalog(string $q, ?int $catId = null, int $limit = 100, int $offset = 0): array {
        $q = trim($q);
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);
        $where = ['h.kurum_id = ?'];
        $params = [CatalogStoreHelper::PLATFORM_KURUM_ID];
        if ($catId !== null && $catId > 0) {
            $where[] = 'h.cat = ?';
            $params[] = $catId;
        }
        if ($q !== '') {
            $where[] = '(h.icd LIKE ? OR h.hastalikadi LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }
        $sql = 'SELECT h.* FROM #__hastaliklar h WHERE ' . implode(' AND ', $where)
            . ' ORDER BY h.icd ASC, h.hastalikadi ASC LIMIT ' . $limit . ' OFFSET ' . $offset;
        $list = $this->db->fetchObjectListPrepared($sql, $params);

        return is_array($list) ? $list : [];
    }

    /** @return list<object> */
    public function searchAssignedForKurum(int $kurumId, string $q, int $limit = 30, array $ensureIcds = []): array {
        if ($kurumId <= 0) {
            return [];
        }
        $limit = max(1, min(100, $limit));
        $q = trim($q);
        if (!KurumHastalik::tableExists()) {
            return $this->searchCatalog($q, null, $limit);
        }
        $rows = [];
        $ensureIcds = array_values(array_unique(array_filter(array_map(
            static fn ($v) => Patient::normalizeHastalikIcd((string) $v),
            $ensureIcds
        ), static fn ($v) => $v !== '')));
        if ($ensureIcds !== []) {
            [$inSql, $inParams] = $this->db->whereInClause($ensureIcds);
            $params = array_merge([$kurumId, CatalogStoreHelper::PLATFORM_KURUM_ID], $inParams);
            $extra = $this->db->fetchObjectListPrepared(
                "SELECT h.* FROM #__hastaliklar h
                 INNER JOIN #__kurum_hastalik kh ON kh.hastalik_id = h.id AND kh.kurum_id = ?
                 WHERE h.kurum_id = ? AND h.icd IN ({$inSql})
                 ORDER BY h.hastalikadi ASC",
                $params
            );
            if (is_array($extra)) {
                $rows = $extra;
            }
        }
        if ($q === '' && count($rows) >= $limit) {
            return array_slice($rows, 0, $limit);
        }
        $where = ['h.kurum_id = ?'];
        $params = [CatalogStoreHelper::PLATFORM_KURUM_ID];
        if ($q !== '') {
            $where[] = '(h.icd LIKE ? OR h.hastalikadi LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }
        $sql = 'SELECT h.* FROM #__hastaliklar h
            INNER JOIN #__kurum_hastalik kh ON kh.hastalik_id = h.id AND kh.kurum_id = ?
            WHERE ' . implode(' AND ', $where)
            . ' ORDER BY h.icd ASC, h.hastalikadi ASC LIMIT ' . $limit;
        $searchRows = $this->db->fetchObjectListPrepared($sql, array_merge([$kurumId], $params));
        if (!is_array($searchRows)) {
            return $rows;
        }
        $seen = [];
        foreach ($rows as $r) {
            $seen[(int) ($r->id ?? 0)] = true;
        }
        foreach ($searchRows as $r) {
            $id = (int) ($r->id ?? 0);
            if ($id > 0 && !isset($seen[$id])) {
                $rows[] = $r;
                $seen[$id] = true;
            }
        }

        return array_slice($rows, 0, $limit);
    }

    public function countCatalog(?int $catId = null, string $q = ''): int {
        $where = ['kurum_id = ?'];
        $params = [CatalogStoreHelper::PLATFORM_KURUM_ID];
        if ($catId !== null && $catId > 0) {
            $where[] = 'cat = ?';
            $params[] = $catId;
        }
        $q = trim($q);
        if ($q !== '') {
            $where[] = '(icd LIKE ? OR hastalikadi LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }

        return (int) $this->db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__hastaliklar WHERE ' . implode(' AND ', $where),
            $params
        );
    }

    /**
     * ICD-10 ağaç düğümleri (lazy). Arama ≥2 karakterde düz liste döner.
     *
     * @return list<object>
     */
    public function getTreeNodes(?string $parentIcd, ?int $catId = null, string $q = '', int $limit = 200): array
    {
        $q = trim($q);
        if (mb_strlen($q) >= 2) {
            return $this->getTreeSearchRows($q, $catId, $limit);
        }

        return $this->getTreeChildren($parentIcd, $catId, $limit);
    }

    /** @return list<object> */
    private function getTreeSearchRows(string $q, ?int $catId, int $limit): array
    {
        $limit = max(1, min(500, $limit));
        $where = ['h.kurum_id = ?'];
        $params = [CatalogStoreHelper::PLATFORM_KURUM_ID];
        if ($catId !== null && $catId > 0) {
            $where[] = 'h.cat = ?';
            $params[] = $catId;
        } elseif ($catId === 0) {
            $where[] = '(h.cat IS NULL OR h.cat = 0)';
        }
        $where[] = '(h.icd LIKE ? OR h.hastalikadi LIKE ?)';
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $sql = 'SELECT h.*
            FROM #__hastaliklar h
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY h.icd ASC, h.hastalikadi ASC
            LIMIT ' . $limit;
        $list = $this->db->fetchObjectListPrepared($sql, $params);

        return is_array($list) ? $this->attachTreeChildCounts($list) : [];
    }

    /**
     * @param list<object> $rows
     * @return list<object>
     */
    private function attachTreeChildCounts(array $rows): array
    {
        if ($rows === []) {
            return [];
        }
        $icds = [];
        foreach ($rows as $row) {
            $icd = trim((string) ($row->icd ?? ''));
            if ($icd !== '') {
                $icds[$icd] = true;
            }
        }
        if ($icds === []) {
            foreach ($rows as $row) {
                $row->child_count = 0;
            }

            return $rows;
        }
        $platformId = CatalogStoreHelper::PLATFORM_KURUM_ID;
        $counts = [];
        $chunks = array_chunk(array_keys($icds), 200);
        foreach ($chunks as $chunk) {
            [$inSql, $inParams] = $this->db->whereInClause($chunk);
            $agg = $this->db->fetchObjectListPrepared(
                "SELECT parent_icd, COUNT(*) AS child_count
                 FROM #__hastaliklar
                 WHERE kurum_id = ? AND parent_icd IN ({$inSql})
                 GROUP BY parent_icd",
                array_merge([$platformId], $inParams)
            );
            if (!is_array($agg)) {
                continue;
            }
            foreach ($agg as $a) {
                $p = trim((string) ($a->parent_icd ?? ''));
                if ($p !== '') {
                    $counts[$p] = (int) ($a->child_count ?? 0);
                }
            }
        }
        foreach ($rows as $row) {
            $icd = trim((string) ($row->icd ?? ''));
            $row->child_count = $icd !== '' ? (int) ($counts[$icd] ?? 0) : 0;
        }

        return $rows;
    }

    /** @return list<object> */
    public function getTreeChildren(?string $parentIcd, ?int $catId = null, int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        if ($parentIcd === null || $parentIcd === '') {
            if ($this->hasSkrsTreeHierarchy()) {
                return $this->getTreeRangeRoots($catId, $limit);
            }

            return $this->getTreeLegacyRoots($catId, $limit);
        }

        $where = ['h.kurum_id = ?'];
        $params = [CatalogStoreHelper::PLATFORM_KURUM_ID];
        if (str_contains($parentIcd, '-')) {
            $where[] = 'h.parent_icd = ?';
            $where[] = 'h.seviye = 2';
            $params[] = $parentIcd;
        } else {
            $where[] = 'h.parent_icd = ?';
            $params[] = $parentIcd;
        }
        if ($catId !== null && $catId > 0) {
            $where[] = 'h.cat = ?';
            $params[] = $catId;
        } elseif ($catId === 0) {
            $where[] = '(h.cat IS NULL OR h.cat = 0)';
        }
        $sql = 'SELECT h.*
            FROM #__hastaliklar h
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY h.icd ASC, h.hastalikadi ASC
            LIMIT ' . $limit;
        $list = $this->db->fetchObjectListPrepared($sql, $params);

        return is_array($list) ? $this->attachTreeChildCounts($list) : [];
    }

    private function hasSkrsTreeHierarchy(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $n = (int) $this->db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__hastaliklar WHERE kurum_id = ? AND seviye = 2 AND parent_icd LIKE \'%-%\' LIMIT 1',
            [CatalogStoreHelper::PLATFORM_KURUM_ID]
        );
        $cached = $n > 0;

        return $cached;
    }

    /** SKRS 1. seviye: A00-A09 gibi blok aralıkları (kapalı kök). */
    /** @return list<object> */
    private function getTreeRangeRoots(?int $catId, int $limit): array
    {
        $where = ['h.kurum_id = ?', 'h.seviye = 2', "h.parent_icd LIKE '%-%'"];
        $params = [CatalogStoreHelper::PLATFORM_KURUM_ID];
        if ($catId !== null && $catId > 0) {
            $where[] = 'h.cat = ?';
            $params[] = $catId;
        } elseif ($catId === 0) {
            $where[] = '(h.cat IS NULL OR h.cat = 0)';
        }
        $sql = 'SELECT h.parent_icd AS icd, MIN(h.cat) AS cat, COUNT(*) AS child_count
            FROM #__hastaliklar h
            WHERE ' . implode(' AND ', $where) . '
            GROUP BY h.parent_icd
            ORDER BY h.parent_icd ASC
            LIMIT ' . $limit;
        $rows = $this->db->fetchObjectListPrepared($sql, $params);
        if (!is_array($rows)) {
            return [];
        }
        foreach ($rows as $row) {
            $row->id = 0;
            $row->hastalikadi = (string) ($row->icd ?? '');
            $row->seviye = 1;
            $row->virtual = true;
        }

        return $rows;
    }

    /** parent_icd/seviye yokken eski kök mantığı. */
    /** @return list<object> */
    private function getTreeLegacyRoots(?int $catId, int $limit): array
    {
        $where = ['h.kurum_id = ?'];
        $params = [CatalogStoreHelper::PLATFORM_KURUM_ID];
        $where[] = '(h.parent_icd LIKE \'%-%\'
            OR h.seviye = 2
            OR (
                h.seviye IS NULL AND (h.parent_icd IS NULL OR TRIM(h.parent_icd) = \'\')
                AND EXISTS (
                    SELECT 1 FROM #__hastaliklar c
                    WHERE c.kurum_id = 0 AND c.parent_icd = h.icd
                )
            )
            OR (
                h.seviye IS NOT NULL AND h.seviye <= 2
                AND h.parent_icd IS NOT NULL AND TRIM(h.parent_icd) != \'\'
                AND NOT EXISTS (
                    SELECT 1 FROM #__hastaliklar p
                    WHERE p.kurum_id = 0 AND p.icd = h.parent_icd
                )
            ))';
        if ($catId !== null && $catId > 0) {
            $where[] = 'h.cat = ?';
            $params[] = $catId;
        } elseif ($catId === 0) {
            $where[] = '(h.cat IS NULL OR h.cat = 0)';
        }
        $sql = 'SELECT h.*
            FROM #__hastaliklar h
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY h.icd ASC, h.hastalikadi ASC
            LIMIT ' . $limit;
        $list = $this->db->fetchObjectListPrepared($sql, $params);

        return is_array($list) ? $this->attachTreeChildCounts($list) : [];
    }

    /** @return array<string,mixed> */
    public static function mapRowToTreeNode(object $row, bool $assigned = false, bool $pickerMode = false): array
    {
        $id = (int) ($row->id ?? 0);
        $icd = trim((string) ($row->icd ?? ''));
        $name = trim((string) ($row->hastalikadi ?? ''));
        $childCount = (int) ($row->child_count ?? 0);
        $seviye = isset($row->seviye) && $row->seviye !== null && $row->seviye !== '' ? (int) $row->seviye : null;
        $isVirtual = !empty($row->virtual);
        $hasChildren = $childCount > 0;
        $selectable = $pickerMode || (!$isVirtual && !$pickerMode);

        return [
            'id' => $id,
            'icd' => $icd,
            'name' => $name,
            'label' => ($icd !== '' ? $icd . ' — ' : '') . $name,
            'seviye' => $seviye,
            'has_children' => $hasChildren,
            'assigned' => $assigned,
            'selectable' => $selectable,
            'virtual' => $isVirtual,
        ];
    }

    /**
     * Kurum seçimi: ağaç düğümüne karşılık gelen platform hastalik id listesi.
     * Sanal 1. seviye (A00-A09) → blok altındaki tüm tanılar; 2./3. seviye → tek kayıt.
     *
     * @return list<int>
     */
    public function resolvePickerIds(string $icd, bool $isVirtual = false, int $selfId = 0): array
    {
        $icd = trim($icd);
        if ($icd === '') {
            return [];
        }
        $platformId = CatalogStoreHelper::PLATFORM_KURUM_ID;
        if ($isVirtual && str_contains($icd, '-')) {
            $ids = $this->db->fetchColumnListPrepared(
                'SELECT h.id FROM #__hastaliklar h WHERE h.kurum_id = ? AND (
                    h.parent_icd = ?
                    OR h.parent_icd IN (
                        SELECT p.icd FROM #__hastaliklar p
                        WHERE p.kurum_id = ? AND p.parent_icd = ?
                    )
                ) ORDER BY h.id ASC',
                [$platformId, $icd, $platformId, $icd]
            );

            return array_values(array_filter(array_map('intval', is_array($ids) ? $ids : [])));
        }
        if ($selfId > 0) {
            return [$selfId];
        }
        $id = (int) $this->db->loadResultPrepared(
            'SELECT id FROM #__hastaliklar WHERE kurum_id = ? AND icd = ? LIMIT 1',
            [$platformId, $icd]
        );

        return $id > 0 ? [$id] : [];
    }

    /** @param list<int> $pickerIds @param array<int, true> $assignedFlip */
    public function isPickerNodeFullyAssigned(array $pickerIds, array $assignedFlip): bool
    {
        if ($pickerIds === []) {
            return false;
        }
        foreach ($pickerIds as $id) {
            if (!isset($assignedFlip[(int) $id])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sanal blok kökleri için tam atama durumu (tek sorgu).
     *
     * @return array<string, bool> range_icd => tamamı atanmış mı
     */
    public function getVirtualRangeAssignmentState(int $kurumId): array
    {
        if ($kurumId <= 0 || !KurumHastalik::tableExists()) {
            return [];
        }
        if ((new KurumHastalik())->countAssigned($kurumId) === 0) {
            return [];
        }
        $platformId = CatalogStoreHelper::PLATFORM_KURUM_ID;
        $sql = 'SELECT range_icd,
                COUNT(*) AS total_n,
                SUM(assigned_flag) AS assigned_n
            FROM (
                SELECT
                    CASE
                        WHEN h.parent_icd LIKE \'%-%\' THEN h.parent_icd
                        ELSE p.parent_icd
                    END AS range_icd,
                    CASE WHEN kh.hastalik_id IS NOT NULL THEN 1 ELSE 0 END AS assigned_flag
                FROM #__hastaliklar h
                LEFT JOIN #__hastaliklar p ON p.kurum_id = ? AND p.icd = h.parent_icd
                LEFT JOIN #__kurum_hastalik kh ON kh.hastalik_id = h.id AND kh.kurum_id = ?
                WHERE h.kurum_id = ?
                  AND (
                      h.parent_icd LIKE \'%-%\'
                      OR (p.id IS NOT NULL AND p.parent_icd LIKE \'%-%\')
                  )
            ) x
            WHERE range_icd IS NOT NULL AND range_icd != \'\'
            GROUP BY range_icd';
        $rows = $this->db->fetchObjectListPrepared($sql, [$platformId, $kurumId, $platformId]);
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $range = trim((string) ($row->range_icd ?? ''));
            if ($range === '') {
                continue;
            }
            $total = (int) ($row->total_n ?? 0);
            $assigned = (int) ($row->assigned_n ?? 0);
            $out[$range] = $total > 0 && $assigned === $total;
        }

        return $out;
    }

    /** @param list<int> $ids @return list<array{id:int,label:string}> */
    public function getPickerItemsByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }
        [$inSql, $inParams] = $this->db->whereInClause($ids);
        $rows = $this->db->fetchObjectListPrepared(
            "SELECT * FROM #__hastaliklar WHERE id IN ({$inSql}) ORDER BY icd ASC, hastalikadi ASC",
            $inParams
        );

        return self::mapRowsToPickerLabels(is_array($rows) ? $rows : []);
    }

    public function getUserHastaliklar($hastaliklar) {
        $icds = Patient::parseHastalikCsvToIcds($hastaliklar ?? null);
        if ($icds === []) {
            return [];
        }
        [$inSql, $inParams] = $this->db->whereInClause($icds);
        $params = array_merge([CatalogStoreHelper::PLATFORM_KURUM_ID], $inParams);
        $queryHastalik = "SELECT CONCAT(h.icd, '-', h.hastalikadi) AS ad 
                          FROM #__hastaliklar AS h
                          WHERE h.kurum_id = ? AND h.icd IN ({$inSql}) ORDER BY ad";

        return $this->db->fetchColumnListPrepared($queryHastalik, $params);
    }

    /** @return list<object{id:int,icd:string,ad:string}> */
    public function getUserHastaliklarWithIds($hastaliklar): array {
        $icds = Patient::parseHastalikCsvToIcds($hastaliklar ?? null);
        if ($icds === []) {
            return [];
        }
        [$inSql, $inParams] = $this->db->whereInClause($icds);
        $params = array_merge([CatalogStoreHelper::PLATFORM_KURUM_ID], $inParams);
        $queryHastalik = "SELECT h.id AS id, h.icd AS icd, CONCAT(h.icd, '-', h.hastalikadi) AS ad
                          FROM #__hastaliklar AS h
                          WHERE h.kurum_id = ? AND h.icd IN ({$inSql}) ORDER BY ad";
        $list = $this->db->fetchObjectListPrepared($queryHastalik, $params);

        return is_array($list) ? $list : [];
    }

    public function getListWithCategory() {
        $query = "SELECT h.*, c.name as kategori_adi 
                  FROM #__hastaliklar h
                  LEFT JOIN #__hastalikcat c ON h.cat = c.id
                  WHERE h.kurum_id = ?
                  ORDER BY c.name, h.hastalikadi ASC";

        return $this->db->fetchObjectListPrepared($query, [CatalogStoreHelper::PLATFORM_KURUM_ID]);
    }

    public function getDetailedList(?int $catId = null, string $orderFragment = 'c.id ASC, h.hastalikadi ASC', int $limit = 200, int $offset = 0, string $searchQ = '') {
        $orderFragment = $this->sanitizeDetailedOrder($orderFragment);
        $limit = max(1, min(1000, $limit));
        $offset = max(0, $offset);
        $where = ['h.kurum_id = ?'];
        $params = [CatalogStoreHelper::PLATFORM_KURUM_ID];
        if ($catId !== null) {
            if ($catId === 0) {
                $where[] = '(h.cat IS NULL OR h.cat = 0)';
            } else {
                $where[] = 'h.cat = ?';
                $params[] = (int) $catId;
            }
        }
        $searchQ = trim($searchQ);
        if ($searchQ !== '') {
            $where[] = '(h.icd LIKE ? OR h.hastalikadi LIKE ?)';
            $like = '%' . $searchQ . '%';
            $params[] = $like;
            $params[] = $like;
        }
        $whereSql = ' WHERE ' . implode(' AND ', $where);
        $sql = "SELECT h.*, c.name AS kategori_adi, c.icd_range
                FROM #__hastaliklar h
                LEFT JOIN #__hastalikcat c ON h.cat = c.id
                {$whereSql}
                ORDER BY {$orderFragment}
                LIMIT {$limit} OFFSET {$offset}";

        return $this->db->fetchObjectListPrepared($sql, $params);
    }

    /** @return list<array{id:int,label:string}> */
    public static function mapRowsToPickerLabels(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $id = (int) ($row->id ?? 0);
            if ($id <= 0) {
                continue;
            }
            $icd = trim((string) ($row->icd ?? ''));
            $name = trim((string) ($row->hastalikadi ?? ''));
            $label = ($icd !== '' ? $icd . ' — ' : '') . ($name !== '' ? $name : ('#' . $id));
            $out[] = ['id' => $id, 'label' => $label];
        }

        return $out;
    }

    /** @return list<array{id:string,text:string}> */
    public static function mapRowsToTomSelectOptions(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $icd = Patient::normalizeHastalikIcd((string) ($row->icd ?? ''));
            if ($icd === '') {
                continue;
            }
            $name = trim((string) ($row->hastalikadi ?? ''));
            $out[] = ['id' => $icd, 'text' => $icd . ' — ' . $name];
        }

        return $out;
    }

    private function sanitizeOrderFragment(string $orderFragment, string $fallback): string
    {
        $orderFragment = trim($orderFragment);
        if ($orderFragment === '' || !preg_match('/^[a-zA-Z0-9_.,\s]+$/', $orderFragment)) {
            return $fallback;
        }

        return $orderFragment;
    }

    private function sanitizeDetailedOrder(string $orderFragment): string
    {
        $orderFragment = trim($orderFragment);
        $allowed = [
            'h.icd ASC' => true, 'h.icd DESC' => true,
            'h.hastalikadi ASC' => true, 'h.hastalikadi DESC' => true,
            'c.name ASC' => true, 'c.name DESC' => true,
            'c.id ASC, h.hastalikadi ASC' => true,
        ];
        if (isset($allowed[$orderFragment])) {
            return $orderFragment;
        }

        return 'c.id ASC, h.hastalikadi ASC';
    }
}
