<?php



declare(strict_types=1);



namespace App\Models;



use App\Core\Database;

use App\Core\DbSqlHelper;



/**

 * Kurumlar arası hasta nakil talepleri — #__hasta_nakil.

 */

class HastaNakil extends BaseModel

{

    public const DURUM_BEKLEMEDE = 'beklemede';

    public const DURUM_ONAYLANDI = 'onaylandi';

    public const DURUM_REDDEDILDI = 'reddedildi';

    public const DURUM_IPTAL = 'iptal';



    public const TIP_KURUM_ICI = 'kurum_ici';

    public const TIP_IL_DISI = 'il_disi';

    public const TIP_GERI_NAKIL = 'geri_nakil';



    public $id = null;

    public $kaynak_hasta_id = null;

    public $kaynak_kurum_id = null;

    public $hedef_kurum_id = null;

    public $hedef_hasta_id = null;

    public $onceki_nakil_id = null;

    public $orijinal_kaynak_hasta_id = null;

    public $tip = self::TIP_KURUM_ICI;

    public $durum = self::DURUM_BEKLEMEDE;

    public $talep_eden_user_id = null;

    public $talep_tarihi = null;

    public $onaylayan_user_id = null;

    public $onay_tarihi = null;

    public $red_nedeni = null;



    public function __construct()

    {

        parent::__construct('#__hasta_nakil', 'id');

    }



    public static function tableExists(): bool

    {

        try {

            $db = Database::getInstance();

            $tbl = $db->replacePrefix('#__hasta_nakil');



            if (DbSqlHelper::isSqlSrv()) {

                return (int) $db->loadResultPrepared(

                    'SELECT COUNT(*) FROM sys.tables WHERE name = ?',

                    [$tbl]

                ) > 0;

            }



            return (int) $db->loadResultPrepared(

                'SELECT COUNT(*) FROM information_schema.TABLES

                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',

                [$tbl]

            ) > 0;

        } catch (\Throwable) {

            return false;

        }

    }



    public function findPendingByHastaId(int $hastaId): ?object

    {

        if ($hastaId <= 0 || !self::tableExists()) {

            return null;

        }

        $row = $this->db->fetchObjectPrepared(

            'SELECT * FROM #__hasta_nakil WHERE kaynak_hasta_id = ? AND durum = ? ORDER BY id DESC LIMIT 1',

            [$hastaId, self::DURUM_BEKLEMEDE]

        );



        return $row ?: null;

    }



    public function findLatestByKaynakHastaId(int $hastaId): ?object

    {

        if ($hastaId <= 0 || !self::tableExists()) {

            return null;

        }

        $row = $this->db->fetchObjectPrepared(

            'SELECT * FROM #__hasta_nakil WHERE kaynak_hasta_id = ? ORDER BY id DESC LIMIT 1',

            [$hastaId]

        );



        return $row ?: null;

    }



    public function findApprovedInboundByHedefHastaId(int $hedefHastaId): ?object

    {

        if ($hedefHastaId <= 0 || !self::tableExists()) {

            return null;

        }

        $row = $this->db->fetchObjectPrepared(

            'SELECT * FROM #__hasta_nakil WHERE hedef_hasta_id = ? AND durum = ? AND tip = ? ORDER BY id DESC LIMIT 1',

            [$hedefHastaId, self::DURUM_ONAYLANDI, self::TIP_KURUM_ICI]

        );



        return $row ?: null;

    }



    public function countPendingForTargetKurum(int $kurumId): int

    {

        if ($kurumId <= 0 || !self::tableExists()) {

            return 0;

        }



        return (int) $this->db->loadResultPrepared(

            'SELECT COUNT(*) FROM #__hasta_nakil WHERE hedef_kurum_id = ? AND durum = ? AND tip IN (?, ?)',

            [$kurumId, self::DURUM_BEKLEMEDE, self::TIP_KURUM_ICI, self::TIP_GERI_NAKIL]

        );

    }



    /** @return list<object> */

    public function getIncomingForTargetKurum(?int $kurumId, bool $superAdminAll = false): array

    {

        if (!self::tableExists()) {

            return [];

        }

        $params = [self::DURUM_BEKLEMEDE, self::TIP_KURUM_ICI, self::TIP_GERI_NAKIL];

        $sql = 'SELECT n.*, h.isim AS hasta_isim, h.soyisim AS hasta_soyisim, h.tckimlik AS hasta_tckimlik,

                k.ad AS kaynak_kurum_ad

                FROM #__hasta_nakil n

                INNER JOIN #__hastalar h ON h.id = n.kaynak_hasta_id

                INNER JOIN #__kurumlar k ON k.id = n.kaynak_kurum_id

                WHERE n.durum = ? AND n.tip IN (?, ?)';

        if (!$superAdminAll && $kurumId !== null && $kurumId > 0) {

            $sql .= ' AND n.hedef_kurum_id = ?';

            $params[] = $kurumId;

        } elseif (!$superAdminAll) {

            return [];

        }

        $sql .= ' ORDER BY n.talep_tarihi ASC';

        $list = $this->db->fetchObjectListPrepared($sql, $params);



        return is_array($list) ? $list : [];

    }



    public function hasPendingInboundForHastaAtKurum(int $hastaId, int $kurumId): bool

    {

        if ($hastaId <= 0 || $kurumId <= 0 || !self::tableExists()) {

            return false;

        }



        return (int) $this->db->loadResultPrepared(

            'SELECT COUNT(*) FROM #__hasta_nakil

             WHERE kaynak_hasta_id = ? AND hedef_kurum_id = ? AND durum = ?

             AND tip IN (?, ?)',

            [$hastaId, $kurumId, self::DURUM_BEKLEMEDE, self::TIP_KURUM_ICI, self::TIP_GERI_NAKIL]

        ) > 0;

    }



    public function loadPendingById(int $id): bool

    {

        if ($id <= 0 || !self::tableExists()) {

            return false;

        }

        $row = $this->db->fetchOnePrepared(

            'SELECT * FROM #__hasta_nakil WHERE id = ? AND durum = ? LIMIT 1',

            [$id, self::DURUM_BEKLEMEDE]

        );

        if (!$row) {

            return false;

        }

        $this->_dirty = [];

        $this->bind($row, false);



        return true;

    }

}

