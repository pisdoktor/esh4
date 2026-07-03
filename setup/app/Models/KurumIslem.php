<?php



declare(strict_types=1);



namespace App\Models;



use App\Core\Database;

use App\Core\DbSqlHelper;

use App\Helpers\CatalogStoreHelper;



/**

 * Kurum ↔ platform işlem ataması — #__kurum_islem.

 */

class KurumIslem extends BaseModel

{

    public function __construct()

    {

        parent::__construct('#__kurum_islem', 'kurum_id');

    }



    public static function tableExists(): bool

    {

        try {

            $db = Database::getInstance();

            $tbl = $db->replacePrefix('#__kurum_islem');



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



    public function assign(int $kurumId, int $islemId): bool

    {

        if ($kurumId <= 0 || $islemId <= 0 || !self::tableExists()) {

            return false;

        }

        if (!$this->isPlatformIslem($islemId)) {

            return false;

        }

        if ($this->isAssigned($kurumId, $islemId)) {

            return true;

        }



        return $this->db->insertPrepared('#__kurum_islem', [

            'kurum_id' => $kurumId,

            'islem_id' => $islemId,

        ]) !== false;

    }



    public function unassign(int $kurumId, int $islemId): bool

    {

        if ($kurumId <= 0 || $islemId <= 0 || !self::tableExists()) {

            return false;

        }



        return $this->db->executePrepared(

            'DELETE FROM #__kurum_islem WHERE kurum_id = ? AND islem_id = ?',

            [$kurumId, $islemId]

        );

    }



    public function isAssigned(int $kurumId, int $islemId): bool

    {

        if ($kurumId <= 0 || $islemId <= 0 || !self::tableExists()) {

            return false;

        }



        return (int) $this->db->loadResultPrepared(

            'SELECT COUNT(*) FROM #__kurum_islem WHERE kurum_id = ? AND islem_id = ?',

            [$kurumId, $islemId]

        ) > 0;

    }



    /** @return list<int> */

    public function getAssignedIds(int $kurumId): array

    {

        if ($kurumId <= 0 || !self::tableExists()) {

            return [];

        }



        $rows = $this->db->fetchColumnListPrepared(

            'SELECT islem_id FROM #__kurum_islem WHERE kurum_id = ? ORDER BY islem_id ASC',

            [$kurumId]

        );



        if (!is_array($rows)) {

            return [];

        }



        return array_values(array_filter(array_map('intval', $rows)));

    }



    /** @param list<int> $islemIds */

    public function syncSelection(int $kurumId, array $islemIds): int

    {

        if ($kurumId <= 0 || !self::tableExists()) {

            return 0;

        }



        $islemIds = array_values(array_unique(array_filter(array_map('intval', $islemIds))));

        $current = $this->getAssignedIds($kurumId);



        foreach (array_diff($current, $islemIds) as $removeId) {

            $this->unassign($kurumId, (int) $removeId);

        }



        $assigned = 0;

        foreach ($islemIds as $iid) {

            if ($iid > 0 && $this->assign($kurumId, $iid)) {

                $assigned++;

            }

        }



        return $assigned;

    }



    private function isPlatformIslem(int $islemId): bool

    {

        return (int) $this->db->loadResultPrepared(

            'SELECT COUNT(*) FROM #__islemler WHERE id = ? AND kurum_id = ?',

            [$islemId, CatalogStoreHelper::PLATFORM_KURUM_ID]

        ) > 0;

    }

}

