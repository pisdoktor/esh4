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
}
