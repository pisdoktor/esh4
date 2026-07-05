<?php



namespace App\Models;

use App\Helpers\IdHelper;






/**

 * Hasta ilaç listesi (`#__hasta_ilaclar`).

 */

class HastaIlac extends BaseModel

{

    public $id = null;

    public $hasta_id = null;

    public $ilac_adi = null;

    public $etken_madde = null;

    public $recete_turu = null;

    public $not = null;

    public $hastalikicd = null;

    public $sira = 0;

    public $created_at = null;

    public $updated_at = null;



    public function __construct()

    {

        parent::__construct('#__hasta_ilaclar', 'id');

    }



    public function ensureTable(): void

    {

        $path = ROOT_PATH . '/database/migrate_esh_hasta_ilaclar_create.sql';

        if (is_readable($path)) {

            $sql = file_get_contents($path);

            if ($sql !== false) {

                $sql = preg_replace('/--[^\n]*\n/', "\n", $sql) ?? $sql;

                foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {

                    if ($stmt !== '' && preg_match('/^CREATE\s+TABLE/i', $stmt)) {

                        $this->db->execLogged($stmt);

                    }

                }

            }

        }



        if (!$this->columnExists('etken_madde')) {

            $this->db->execLogged('ALTER TABLE #__hasta_ilaclar ADD COLUMN etken_madde VARCHAR(512) NULL AFTER ilac_adi');

        }

        if (!$this->columnExists('recete_turu')) {

            $this->db->execLogged('ALTER TABLE #__hasta_ilaclar ADD COLUMN recete_turu VARCHAR(128) NULL AFTER etken_madde');

        }

        if ($this->columnExists('hastalikid') && !$this->columnExists('hastalikicd')) {

            $this->db->execLogged('ALTER TABLE #__hasta_ilaclar ADD COLUMN hastalikicd VARCHAR(32) NULL AFTER `not`');

            $this->db->execLogged(
                'UPDATE #__hasta_ilaclar i
                 INNER JOIN #__hastaliklar h ON h.id = i.hastalikid
                 SET i.hastalikicd = TRIM(h.icd)
                 WHERE i.hastalikid IS NOT NULL AND i.hastalikid > 0 AND TRIM(COALESCE(h.icd, \'\')) <> \'\''
            );

            $this->db->execLogged('ALTER TABLE #__hasta_ilaclar DROP COLUMN hastalikid');

        }

    }



    /**

     * @return array<int, object>

     */

    public function getByHastaId(string $hastaId): array

    {

        return $this->db->fetchObjectListPrepared(

            'SELECT * FROM #__hasta_ilaclar WHERE hasta_id = :hasta ORDER BY sira ASC, id ASC',

            [':hasta' => $hastaId]

        );

    }



    public function findByIdForHasta(int|string|null $id, int|string|null $hastaId): bool
    {
        $iid = IdHelper::normalizeRequestId($id);
        $hid = IdHelper::normalizeRequestId($hastaId);
        if ($iid === null || $hid === null) {
            return false;
        }
        $row = $this->db->fetchObjectPrepared(
            'SELECT * FROM #__hasta_ilaclar WHERE id = :id AND hasta_id = :hasta LIMIT 1',
            [':id' => $iid, ':hasta' => $hid]
        );

        if (!$row) {

            return false;

        }

        $this->_dirty = [];

        $this->bind((array) $row, false);



        return true;

    }



    public function nextSiraForHasta(string $hastaId): int

    {

        return (int) $this->db->loadResultPrepared(

            'SELECT COALESCE(MAX(sira), -1) + 1 FROM #__hasta_ilaclar WHERE hasta_id = :hasta',

            [':hasta' => $hastaId]

        );

    }



    private function columnExists(string $column): bool

    {

        return (bool) $this->db->loadResultPrepared(

            'SELECT 1 FROM information_schema.COLUMNS

             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tbl AND COLUMN_NAME = :col LIMIT 1',

            [':tbl' => $this->db->replacePrefix('#__hasta_ilaclar'), ':col' => $column]

        );

    }

}

