<?php

namespace App\Models;



use App\Helpers\CatalogStoreHelper;

use App\Helpers\TenantContext;



/**

 * Konsültasyon EK-3 başvuru amacı — platform kataloğu; kurum seçimi #__kurum_istek.

 */

class Istek extends BaseModel {



    public $id = null;

    public $kurum_id = 0;

    public $istek_adi = null;



    public function __construct() {

        parent::__construct('#__istekler', 'id');

    }



    /**

     * @return list<object>

     */

    public function getCatalogList(string $orderFragment = 'istek_adi ASC'): array {

        $orderFragment = trim($orderFragment) !== '' ? $orderFragment : 'istek_adi ASC';
        $sql = 'SELECT * FROM #__istekler

            WHERE kurum_id = ?

            ORDER BY ' . $orderFragment;

        $rows = $this->db->fetchObjectListPrepared($sql, [CatalogStoreHelper::PLATFORM_KURUM_ID]);



        return is_array($rows) ? $rows : [];

    }



    /**

     * @return list<object>

     */

    public function getList(): array {

        $kid = TenantContext::filterKurumId();

        if ($kid === null || $kid <= 0) {

            return [];

        }

        if (!KurumIstek::tableExists()) {

            return $this->getCatalogList();

        }



        $sql = 'SELECT i.*

            FROM #__istekler AS i

            INNER JOIN #__kurum_istek AS ki ON ki.istek_id = i.id AND ki.kurum_id = ?

            WHERE i.kurum_id = ?

            ORDER BY i.istek_adi ASC';



        $rows = $this->db->fetchObjectListPrepared($sql, [(int) $kid, CatalogStoreHelper::PLATFORM_KURUM_ID]);



        return is_array($rows) ? $rows : [];

    }



    /**

     * @return list<object>

     */

    public function getListWithAssignmentState(int $kurumId): array {

        $catalog = $this->getCatalogList();

        if ($kurumId <= 0 || !KurumIstek::tableExists()) {

            foreach ($catalog as $row) {

                $row->assigned = false;

            }



            return $catalog;

        }



        $assigned = array_flip((new KurumIstek())->getAssignedIds($kurumId));

        foreach ($catalog as $row) {

            $row->assigned = isset($assigned[(int) ($row->id ?? 0)]);

        }



        return $catalog;

    }



    /**

     * @param int[] $ids

     * @return string virgülle birleştirilmiş istek adları

     */

    public function namesForIds(array $ids): string {

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {

            return '';

        }

        [$inSql, $inParams] = $this->db->whereInClause($ids);

        $sql = "SELECT istek_adi FROM #__istekler WHERE id IN ({$inSql}) ORDER BY istek_adi ASC";

        $rows = $this->db->fetchColumnListPrepared($sql, $inParams);

        return $rows ? implode(', ', array_map('strval', $rows)) : '';

    }

}

