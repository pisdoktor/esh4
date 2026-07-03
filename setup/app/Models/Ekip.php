<?php
namespace App\Models;

use App\Helpers\TenantSqlHelper;

class Ekip extends BaseModel {
    public $id = null;
    public $kurum_id = 1;
    public $tarih = null;
    public $vardiya = null;
    public $ekip_no = null;
    public $user_ids = null;
    public $baslangic_saati = null;
    public $kayit_tarihi = null;

    public function __construct() {
        parent::__construct('#__ekipler', 'id');
    }

    /**
     * Ana liste: tarih bazında özet (legacy listeEkipler — son 30 gün).
     *
     * @return array<int, object>
     */
    public function getDailyTeamsList(string $orderFragment = 'tarih DESC') {
        $orderFragment = trim($orderFragment) !== '' ? $orderFragment : 'tarih DESC';
        $sql = 'SELECT tarih, COUNT(id) AS ekip_sayisi,
            GROUP_CONCAT(DISTINCT baslangic_saati SEPARATOR \' / \') AS saatler
            FROM #__ekipler
            WHERE 1=1' . TenantSqlHelper::andBare() . '
            GROUP BY tarih
            ORDER BY ' . $orderFragment . '
            LIMIT 30';
        return $this->db->fetchObjectListPrepared($sql);
    }

    /**
     * Liste satırı için kısa personel özeti (legacy: JOIN + LIMIT 5).
     */
    public function getPersonnelPreviewForDate($ymd) {
        $ymd = (string) $ymd;
        if ($ymd === '') {
            return '';
        }
        $sql = 'SELECT u.name FROM #__ekipler AS e
            INNER JOIN #__users AS u ON FIND_IN_SET(u.id, e.user_ids)
            WHERE e.tarih = ?' . TenantSqlHelper::andEquals('e') . '
            LIMIT 5';
        $names = $this->db->fetchColumnListPrepared($sql, [$ymd]);
        if (empty($names)) {
            return '';
        }
        $txt = implode(', ', $names);
        if (count($names) > 4) {
            $txt .= '...';
        }
        return $txt;
    }

    /**
     * Virgüllü ID listesini isimlere çevirir (yardımcı).
     */
    public function getTeamMemberNames() {
        if (empty($this->user_ids)) {
            return 'Personel Atanmamış';
        }
        $ids = array_values(array_unique(array_filter(
            array_map('intval', explode(',', preg_replace('/[^0-9,]/', '', (string) $this->user_ids)))
        )));
        if ($ids === []) {
            return 'Personel Atanmamış';
        }
        [$inSql, $inParams] = $this->db->whereInClause($ids);
        $sql = "SELECT GROUP_CONCAT(name SEPARATOR ', ') AS names FROM #__users WHERE id IN ({$inSql})";
        $r = $this->db->loadResultPrepared($sql, $inParams);
        return $r ? (string) $r : 'Personel Atanmamış';
    }

    /**
     * @return array<int, object>
     */
    public function getEkipler($date) {
        $sql = 'SELECT * FROM #__ekipler
            WHERE tarih = ?' . TenantSqlHelper::andBare() . '
            ORDER BY ekip_no ASC';
        return $this->db->fetchObjectListPrepared($sql, [(string) $date]);
    }
}
