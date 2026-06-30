<?php



declare(strict_types=1);



namespace App\Models;



use App\Core\Database;

use App\Core\DbSqlHelper;

use App\Helpers\CatalogStoreHelper;



/**

 * Kurum ↔ platform branş ataması — #__kurum_brans.

 */

class KurumBrans extends BaseModel

{

    public function __construct()

    {

        parent::__construct('#__kurum_brans', 'kurum_id');

    }



    public static function tableExists(): bool

    {

        try {

            $db = Database::getInstance();

            $tbl = $db->replacePrefix('#__kurum_brans');



            if (DbSqlHelper::isSqlSrv()) {

                return (int) $db->loadResultPrepared(

                    'SELECT COUNT(*) FROM sys.tables WHERE name = ?',

                    [$tbl]

                ) > 0;

            }



            return (int) $db->loadResultPrepared(

                'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',

                [$tbl]

            ) > 0;

        } catch (\Throwable) {

            return false;

        }

    }



    public function assign(int $kurumId, int $bransId, ?int $hastaKotasi = null): bool

    {

        if ($kurumId <= 0 || $bransId <= 0 || !self::tableExists()) {

            return false;

        }

        if (!$this->isPlatformBrans($bransId)) {

            return false;

        }



        $kotaVal = Brans::normalizeHastaKotasi($hastaKotasi);



        if ($this->isAssigned($kurumId, $bransId)) {

            return $this->db->updatePrepared(

                '#__kurum_brans',

                ['hasta_kotasi' => $kotaVal],

                'kurum_id = ? AND brans_id = ?',

                [$kurumId, $bransId]

            );

        }



        return $this->db->insertPrepared('#__kurum_brans', [

            'kurum_id' => $kurumId,

            'brans_id' => $bransId,

            'hasta_kotasi' => $kotaVal,

        ]) !== false;

    }



    public function unassign(int $kurumId, int $bransId): bool

    {

        if ($kurumId <= 0 || $bransId <= 0 || !self::tableExists()) {

            return false;

        }



        return $this->db->executePrepared(

            'DELETE FROM #__kurum_brans WHERE kurum_id = ? AND brans_id = ?',

            [$kurumId, $bransId]

        );

    }



    public function isAssigned(int $kurumId, int $bransId): bool

    {

        if ($kurumId <= 0 || $bransId <= 0 || !self::tableExists()) {

            return false;

        }



        return (int) $this->db->loadResultPrepared(

            'SELECT COUNT(*) FROM #__kurum_brans WHERE kurum_id = ? AND brans_id = ?',

            [$kurumId, $bransId]

        ) > 0;

    }



    /** @return list<int> */

    public function getAssignedIds(int $kurumId): array

    {

        if ($kurumId <= 0 || !self::tableExists()) {

            return [];

        }



        $rows = $this->db->fetchColumnListPrepared(

            'SELECT brans_id FROM #__kurum_brans WHERE kurum_id = ? ORDER BY brans_id ASC',

            [$kurumId]

        );



        if (!is_array($rows)) {

            return [];

        }



        return array_values(array_filter(array_map('intval', $rows)));

    }



    /**

     * @param array<int|string, mixed> $kotaMap brans_id => kota

     */

    public function saveKotas(int $kurumId, array $kotaMap): int

    {

        if ($kurumId <= 0 || !self::tableExists()) {

            return 0;

        }



        $ok = 0;

        foreach ($kotaMap as $id => $val) {

            $bid = (int) $id;

            if ($bid <= 0 || !$this->isAssigned($kurumId, $bid)) {

                continue;

            }

            $kotaVal = Brans::normalizeHastaKotasi($val);

            if ($this->db->updatePrepared(

                '#__kurum_brans',

                ['hasta_kotasi' => $kotaVal],

                'kurum_id = ? AND brans_id = ?',

                [$kurumId, $bid]

            )) {

                $ok++;

            }

        }



        return $ok;

    }



    /**

     * @param list<int> $bransIds

     * @param array<int|string, mixed> $kotaMap

     */

    public function syncSelection(int $kurumId, array $bransIds, array $kotaMap = []): int

    {

        if ($kurumId <= 0 || !self::tableExists()) {

            return 0;

        }



        $bransIds = array_values(array_unique(array_filter(array_map('intval', $bransIds))));

        $current = $this->getAssignedIds($kurumId);



        foreach (array_diff($current, $bransIds) as $removeId) {

            $this->unassign($kurumId, (int) $removeId);

        }



        $assigned = 0;

        foreach ($bransIds as $bid) {

            if ($bid <= 0 || !$this->isPlatformBrans($bid)) {

                continue;

            }

            $kota = $kotaMap[$bid] ?? $kotaMap[(string) $bid] ?? null;

            if ($this->assign($kurumId, $bid, Brans::normalizeHastaKotasi($kota))) {

                $assigned++;

            }

        }



        return $assigned;

    }



    public function getHastaKotasi(int $kurumId, int $bransId): ?int

    {

        if ($kurumId <= 0 || $bransId <= 0 || !self::tableExists()) {

            return null;

        }



        $val = $this->db->loadResultPrepared(

            'SELECT hasta_kotasi FROM #__kurum_brans WHERE kurum_id = ? AND brans_id = ?',

            [$kurumId, $bransId]

        );



        return Brans::normalizeHastaKotasi($val);

    }



    private function isPlatformBrans(int $bransId): bool

    {

        if ($bransId <= 0) {

            return false;

        }

        $row = $this->db->fetchObjectPrepared(

            'SELECT kurum_id FROM #__branslar WHERE id = ?',

            [$bransId]

        );

        if (!$row) {

            return false;

        }

        $kid = (int) ($row->kurum_id ?? -1);

        if ($kid === CatalogStoreHelper::PLATFORM_KURUM_ID) {

            return true;

        }



        return !self::tableExists();

    }

}

