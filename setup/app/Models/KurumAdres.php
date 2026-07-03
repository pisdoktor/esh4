<?php



declare(strict_types=1);



namespace App\Models;



use App\Core\Database;

use App\Core\DbSqlHelper;



/**

 * Kurum ↔ ilçe/mahalle/sokak ataması — #__kurum_adres.

 */

class KurumAdres extends BaseModel

{

    private const VALID_TIPS = ['ilce', 'mahalle', 'sokak'];



    public function __construct()

    {

        parent::__construct('#__kurum_adres', 'kurum_id');

    }



    public static function tableExists(): bool

    {

        try {

            $db = Database::getInstance();

            $tbl = $db->replacePrefix('#__kurum_adres');



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



    /** @return list<object> */

    public function getForKurum(int $kurumId): array

    {

        if ($kurumId <= 0 || !self::tableExists()) {

            return [];

        }



        $sql = 'SELECT ka.kurum_id, ka.adres_id, ka.tip, a.adi

            FROM #__kurum_adres AS ka

            INNER JOIN #__adrestablosu AS a ON a.id = ka.adres_id

            WHERE ka.kurum_id = ?

            ORDER BY FIELD(ka.tip, ?, ?, ?), a.adi ASC';

        $list = $this->db->fetchObjectListPrepared($sql, [$kurumId, 'ilce', 'mahalle', 'sokak']);



        if (!is_array($list)) {

            return [];

        }



        $addr = new Address();

        foreach ($list as $row) {

            $row->yol = $this->buildPathLabel($addr, (string) ($row->adres_id ?? ''), (string) ($row->tip ?? ''));

        }



        return $list;

    }



    public function hasAssignments(int $kurumId): bool

    {

        if ($kurumId <= 0 || !self::tableExists()) {

            return false;

        }



        return (int) $this->db->loadResultPrepared(

            'SELECT COUNT(*) FROM #__kurum_adres WHERE kurum_id = ?',

            [$kurumId]

        ) > 0;

    }



    public function assign(int $kurumId, string $adresId): bool

    {

        if ($kurumId <= 0 || !self::tableExists()) {

            return false;

        }



        $adresId = trim($adresId);

        if ($adresId === '') {

            return false;

        }



        $row = (new Address())->adminGetRowById($adresId);

        if (!$row) {

            return false;

        }



        $tip = (string) ($row->tip ?? '');

        if (!in_array($tip, self::VALID_TIPS, true)) {

            return false;

        }



        if ((int) $this->db->loadResultPrepared(

            'SELECT COUNT(*) FROM #__kurum_adres WHERE kurum_id = ? AND adres_id = ?',

            [$kurumId, $adresId]

        ) > 0) {

            return true;

        }



        return $this->db->insertPrepared('#__kurum_adres', [

            'kurum_id' => $kurumId,

            'adres_id' => $adresId,

            'tip' => $tip,

        ]) !== false;

    }



    public function unassign(int $kurumId, string $adresId): bool

    {

        if ($kurumId <= 0 || !self::tableExists()) {

            return false;

        }



        $adresId = trim($adresId);

        if ($adresId === '') {

            return false;

        }



        return $this->db->executePrepared(

            'DELETE FROM #__kurum_adres WHERE kurum_id = ? AND adres_id = ?',

            [$kurumId, $adresId]

        );

    }



    /** @return list<string> */

    public function getDirectAssignmentIds(int $kurumId, ?string $tip = null): array

    {

        if ($kurumId <= 0 || !self::tableExists()) {

            return [];

        }



        $params = [$kurumId];

        $sql = 'SELECT adres_id FROM #__kurum_adres WHERE kurum_id = ?';

        if ($tip !== null && in_array($tip, self::VALID_TIPS, true)) {

            $sql .= ' AND tip = ?';

            $params[] = $tip;

        }

        $rows = $this->db->fetchColumnListPrepared($sql, $params);

        if (!is_array($rows)) {

            return [];

        }



        return array_values(array_filter(array_map('strval', $rows)));

    }



    private function buildPathLabel(Address $addr, string $adresId, string $tip): string

    {

        $parts = [];

        $current = $addr->adminGetRowById($adresId);

        $guard = 0;

        while ($current && $guard < 8) {

            $parts[] = (string) ($current->adi ?? '');

            $ust = trim((string) ($current->ust_id ?? ''));

            if ($ust === '' || $ust === '0') {

                break;

            }

            $current = $addr->adminGetRowById($ust);

            $guard++;

        }



        $parts = array_reverse($parts);



        return $parts !== [] ? implode(' › ', $parts) : $adresId;

    }

}

