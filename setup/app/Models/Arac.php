<?php
namespace App\Models;

use App\Helpers\TenantSqlHelper;

/**
 * Araç tanımları (plaka / marka-model) — #__araclar
 */
class Arac extends BaseModel {

    public $id = null;
    public $kurum_id = 1;
    public $plaka = null;
    public $arac_bilgisi = null;
    public $kapasite = 4;

    public function __construct() {
        parent::__construct('#__araclar', 'id');
    }

    /**
     * @return object[]
     */
    public function getList(string $orderFragment = 'a.plaka ASC, a.id ASC') {
        $orderFragment = trim($orderFragment) !== '' ? $orderFragment : 'a.plaka ASC, a.id ASC';
        $parts = [];
        TenantSqlHelper::mergeParts($parts, 'a', 'kurum_id');
        $where = $parts !== [] ? ' WHERE ' . implode(' AND ', $parts) : '';
        $sql = 'SELECT a.*, k.ad AS kurum_ad, k.kod AS kurum_slug'
            . ' FROM #__araclar AS a'
            . ' LEFT JOIN #__kurumlar AS k ON k.id = a.kurum_id'
            . $where
            . ' ORDER BY ' . $orderFragment;

        return $this->db->fetchObjectListPrepared($sql);
    }

    /**
     * @param list<int> $ids
     * @return array<int, int>
     */
    public function getKapasiteMap(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }
        [$inSql, $params] = $this->db->whereInClause($ids);
        $rows = $this->db->fetchObjectListPrepared(
            "SELECT id, kapasite FROM #__araclar WHERE id IN ($inSql)",
            $params
        );
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->id] = max(1, (int) ($row->kapasite ?? 4));
        }

        return $map;
    }
}
