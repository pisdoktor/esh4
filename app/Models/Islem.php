<?php
namespace App\Models;

use App\Helpers\CatalogStoreHelper;
use App\Helpers\KurumCorporateSettings;
use App\Helpers\TenantContext;

/**
 * Yapılan İşlemler — platform kataloğu; kurum seçimi #__kurum_islem.
 */
class Islem extends BaseModel {
    
    public $id = null;
    public $kurum_id = 0;
    public $islemadi = null;

    public function __construct() {
        parent::__construct('#__islemler', 'id');
    }

    /**
     * @return list<object>
     */
    public function getCatalogList(string $orderFragment = 'islemadi ASC'): array {
        $orderFragment = trim($orderFragment) !== '' ? $orderFragment : 'islemadi ASC';
        $sql = 'SELECT * FROM #__islemler
            WHERE kurum_id = ?
            ORDER BY ' . $orderFragment;
        $rows = $this->db->fetchObjectListPrepared($sql, [CatalogStoreHelper::PLATFORM_KURUM_ID]);
        if (is_array($rows) && $rows !== []) {
            return $rows;
        }

        $legacy = $this->db->fetchObjectListPrepared('SELECT * FROM #__islemler ORDER BY islemadi ASC');

        return is_array($legacy) ? $legacy : [];
    }

    /**
     * Ayar paneli islem_ids sekmesi: kurum kapsamında #__kurum_islem; platformda katalog.
     *
     * @return list<object>
     */
    public function getListForSettingsPicker(): array {
        $kid = KurumCorporateSettings::writeKurumId();
        if ($kid !== null && $kid > 0) {
            return $this->getAssignedListForKurum($kid);
        }

        return $this->getCatalogList();
    }

    /**
     * Kuruma atanmış platform işlemleri (#__kurum_islem).
     *
     * @return list<object>
     */
    public function getAssignedListForKurum(int $kurumId): array {
        if ($kurumId <= 0) {
            return [];
        }
        if (!KurumIslem::tableExists()) {
            return $this->getCatalogList();
        }

        $sql = 'SELECT i.*
            FROM #__islemler AS i
            INNER JOIN #__kurum_islem AS km ON km.islem_id = i.id AND km.kurum_id = ?
            WHERE i.kurum_id = ?
            ORDER BY i.islemadi ASC';

        $rows = $this->db->fetchObjectListPrepared($sql, [$kurumId, CatalogStoreHelper::PLATFORM_KURUM_ID]);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<object>
     */
    public function getList() {
        $kid = TenantContext::filterKurumId();
        if ($kid === null || $kid <= 0) {
            return [];
        }

        return $this->getAssignedListForKurum($kid);
    }

    /**
     * @return list<object>
     */
    public function getListWithAssignmentState(int $kurumId): array {
        $catalog = $this->getCatalogList();
        if ($kurumId <= 0 || !KurumIslem::tableExists()) {
            foreach ($catalog as $row) {
                $row->assigned = false;
            }

            return $catalog;
        }

        $assigned = array_flip((new KurumIslem())->getAssignedIds($kurumId));
        foreach ($catalog as $row) {
            $row->assigned = isset($assigned[(int) ($row->id ?? 0)]);
        }

        return $catalog;
    }

    /**
     * @param int[] $ids
     */
    public function namesForIds(array $ids): string {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return '';
        }
        [$inSql, $inParams] = $this->db->whereInClause($ids);
        $sql = "SELECT islemadi FROM #__islemler WHERE id IN ({$inSql}) ORDER BY islemadi ASC";
        $rows = $this->db->fetchColumnListPrepared($sql, $inParams);

        return $rows ? implode(', ', array_map('strval', $rows)) : '';
    }
}
