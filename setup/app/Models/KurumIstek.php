<?php



declare(strict_types=1);



namespace App\Models;



use App\Core\Database;

use App\Core\DbSqlHelper;

use App\Helpers\CatalogStoreHelper;



/**

 * Kurum ↔ platform istek ataması — #__kurum_istek.

 */

class KurumIstek extends BaseModel

{

    public function __construct()

    {

        parent::__construct('#__kurum_istek', 'kurum_id');

    }



    public static function tableExists(): bool

    {

        try {

            $db = Database::getInstance();

            $tbl = $db->replacePrefix('#__kurum_istek');



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



    public function assign(int $kurumId, int $istekId): bool

    {

        if ($kurumId <= 0 || $istekId <= 0 || !self::tableExists()) {

            return false;

        }

        if (!$this->isPlatformIstek($istekId)) {

            return false;

        }

        if ($this->isAssigned($kurumId, $istekId)) {

            return true;

        }



        return $this->db->insertPrepared('#__kurum_istek', [

            'kurum_id' => $kurumId,

            'istek_id' => $istekId,

        ]) !== false;

    }



    public function unassign(int $kurumId, int $istekId): bool

    {

        if ($kurumId <= 0 || $istekId <= 0 || !self::tableExists()) {

            return false;

        }



        return $this->db->executePrepared(

            'DELETE FROM #__kurum_istek WHERE kurum_id = ? AND istek_id = ?',

            [$kurumId, $istekId]

        );

    }



    public function isAssigned(int $kurumId, int $istekId): bool

    {

        if ($kurumId <= 0 || $istekId <= 0 || !self::tableExists()) {

            return false;

        }



        return (int) $this->db->loadResultPrepared(

            'SELECT COUNT(*) FROM #__kurum_istek WHERE kurum_id = ? AND istek_id = ?',

            [$kurumId, $istekId]

        ) > 0;

    }



    /** @return list<int> */

    public function getAssignedIds(int $kurumId): array

    {

        if ($kurumId <= 0 || !self::tableExists()) {

            return [];

        }



        $rows = $this->db->fetchColumnListPrepared(

            'SELECT istek_id FROM #__kurum_istek WHERE kurum_id = ? ORDER BY istek_id ASC',

            [$kurumId]

        );



        if (!is_array($rows)) {

            return [];

        }



        return array_values(array_filter(array_map('intval', $rows)));

    }



    /** @param list<int> $istekIds */

    public function syncSelection(int $kurumId, array $istekIds): int

    {

        if ($kurumId <= 0 || !self::tableExists()) {

            return 0;

        }



        $istekIds = array_values(array_unique(array_filter(array_map('intval', $istekIds))));

        $current = $this->getAssignedIds($kurumId);



        foreach (array_diff($current, $istekIds) as $removeId) {

            $this->unassign($kurumId, (int) $removeId);

        }



        $assigned = 0;

        foreach ($istekIds as $iid) {

            if ($iid > 0 && $this->assign($kurumId, $iid)) {

                $assigned++;

            }

        }



        return $assigned;

    }



    private function isPlatformIstek(int $istekId): bool

    {

        return (int) $this->db->loadResultPrepared(

            'SELECT COUNT(*) FROM #__istekler WHERE id = ? AND kurum_id = ?',

            [$istekId, CatalogStoreHelper::PLATFORM_KURUM_ID]

        ) > 0;

    }

}

