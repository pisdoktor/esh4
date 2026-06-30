<?php

namespace App\Models;



use App\Helpers\CatalogStoreHelper;

use App\Helpers\TenantContext;



/**

 * Tıbbi Branşlar Modeli — platform kataloğu (kurum_id=0); kurum seçimi #__kurum_brans.

 */

class Brans extends BaseModel {

    

    public $id = null;

    public $kurum_id = 0;

    public $bransadi = null;

    /** @var int|null Junction'dan gelir; platform satırında kullanılmaz */

    public $hasta_kotasi = null;



    public function __construct() {

        parent::__construct('#__branslar', 'id');

    }



    /**

     * Platform geneli katalog listesi (süper yönetici CRUD).

     *

     * @return list<object>

     */

    public function getCatalogList(string $orderFragment = 'bransadi ASC'): array {

        $orderFragment = trim($orderFragment) !== '' ? $orderFragment : 'bransadi ASC';
        $sql = 'SELECT * FROM #__branslar

            WHERE kurum_id = ?

            ORDER BY ' . $orderFragment;

        $rows = $this->db->fetchObjectListPrepared($sql, [CatalogStoreHelper::PLATFORM_KURUM_ID]);

        if (!is_array($rows)) {
            $rows = [];
        }
        if ($rows === []) {
            $legacy = $this->db->fetchObjectListPrepared('SELECT * FROM #__branslar ORDER BY bransadi ASC');
            if (is_array($legacy)) {
                $rows = $legacy;
            }
        }

        return $rows;

    }



    /**

     * Kuruma atanmış branşlar (operasyonel formlar).

     *

     * @return list<object>

     */

    public function getList() {

        $kid = TenantContext::filterKurumId();

        if ($kid === null || $kid <= 0) {

            return [];

        }

        if (!KurumBrans::tableExists()) {

            return $this->getCatalogList();

        }



        $sql = 'SELECT b.id, b.kurum_id, b.bransadi, kb.hasta_kotasi

            FROM #__branslar AS b

            INNER JOIN #__kurum_brans AS kb ON kb.brans_id = b.id AND kb.kurum_id = ?

            WHERE b.kurum_id = ?

            ORDER BY b.bransadi ASC';



        $rows = $this->db->fetchObjectListPrepared($sql, [(int) $kid, CatalogStoreHelper::PLATFORM_KURUM_ID]);



        return is_array($rows) ? $rows : [];

    }



    /**

     * Seçim ekranı: tüm katalog + atama durumu.

     *

     * @return list<object>

     */

    public function getListWithAssignmentState(int $kurumId): array {

        $catalog = $this->getCatalogList();

        if ($kurumId <= 0 || !KurumBrans::tableExists()) {

            foreach ($catalog as $row) {

                $row->assigned = false;

                $row->hasta_kotasi = null;

            }



            return $catalog;

        }



        $kb = new KurumBrans();

        $assignedIds = array_flip($kb->getAssignedIds($kurumId));

        $kotaMap = [];

        foreach (array_keys($assignedIds) as $bid) {

            $kotaMap[(int) $bid] = $kb->getHastaKotasi($kurumId, (int) $bid);

        }



        foreach ($catalog as $row) {

            $id = (int) ($row->id ?? 0);

            $row->assigned = isset($assignedIds[$id]);

            $row->hasta_kotasi = $kotaMap[$id] ?? null;

        }



        return $catalog;

    }



    /** @param mixed $raw */

    public static function normalizeHastaKotasi($raw): ?int {

        if ($raw === null || $raw === '') {

            return null;

        }

        $n = (int) $raw;



        return $n > 0 ? $n : null;

    }



    /**

     * Kurum junction kotası (picker); platform satırı güncellenmez.

     */

    public function updateHastaKotasi(int $kurumId, int $bransId, ?int $kota): bool {

        if ($kurumId <= 0 || $bransId <= 0) {

            return false;

        }

        $kb = new KurumBrans();

        if (!$kb->isAssigned($kurumId, $bransId)) {

            return false;

        }

        return $this->db->updatePrepared(
            '#__kurum_brans',
            ['hasta_kotasi' => $kota],
            'kurum_id = ? AND brans_id = ?',
            [$kurumId, $bransId]
        );

    }



    public function getHastaKotasiForKurum(int $kurumId, int $bransId): ?int

    {

        if ($kurumId <= 0 || $bransId <= 0) {

            return null;

        }

        if (!KurumBrans::tableExists()) {

            return null;

        }



        return (new KurumBrans())->getHastaKotasi($kurumId, $bransId);

    }



    /**

     * Kota özeti (seçilen gün için kullanılan sayı ile).

     *

     * @return array{kota: ?int, used: int, remaining: ?int, full: bool, unlimited: bool}

     */

    public static function kotaInfo(?int $hastaKotasi, int $usedCount): array {

        $kota = ($hastaKotasi !== null && $hastaKotasi > 0) ? $hastaKotasi : null;

        $unlimited = ($kota === null);

        $used = max(0, $usedCount);

        $remaining = $unlimited ? null : max(0, $kota - $used);

        $full = !$unlimited && $used >= $kota;



        return [

            'kota' => $kota,

            'used' => $used,

            'remaining' => $remaining,

            'full' => $full,

            'unlimited' => $unlimited,

        ];

    }

}

