<?php
namespace App\Models;

use App\Helpers\IdHelper;
use App\Helpers\TenantSqlHelper;

/**
 * Branş / poliklinik randevu takvimi (`#__kons_randevu`).
 * Aynı günde aynı hastaya farklı branşlarda ayrı randevu verilebilir; aynı gün + aynı branş + aynı hasta tekil.
 */
class KonsRandevu extends BaseModel
{
    public $id = null;
    public $kurum_id = 1;
    public $randevu_tarihi = null;
    public $zaman = 0;
    /** @var string Virgüllü #__istekler.id listesi */
    public $kons_istekler = '';
    public $brans_id = null;
    public $hastatckimlik = null;
    public $notlar = null;
    /** @var int|null 1=geldi, 0=gelmedi, null=belirtilmedi */
    public $hasta_geldi = null;
    public $olusturan_id = null;
    public $created_at = null;

    public function __construct()
    {
        parent::__construct('#__kons_randevu', 'id');
    }

    public static function zamanLabel(int $z): string
    {
        return \App\Helpers\ZamanDilimiHelper::label($z);
    }

    /**
     * Katılım durumu (liste / select etiketi).
     *
     * @param int|string|null $v DB’den gelen değer
     */
    public static function hastaGeldiLabel($v): string
    {
        if ($v === null || $v === '') {
            return 'Belirtilmedi';
        }
        $i = (int) $v;

        return match ($i) {
            1 => 'Geldi',
            0 => 'Gelmedi',
            default => 'Belirtilmedi',
        };
    }

    /**
     * @return array<string, int> Y-m-d => adet
     */
    public function countByDayInMonth(int $year, int $month): array
    {
        if ($year < 1970 || $year > 2100 || $month < 1 || $month > 12) {
            return [];
        }
        $start = sprintf('%04d-%02d-01', $year, $month);
        $t = strtotime($start . ' +1 month');
        if ($t === false) {
            return [];
        }
        $endExcl = date('Y-m-d', $t);
        $sql = 'SELECT DATE_FORMAT(randevu_tarihi, \'%Y-%m-%d\') AS d, COUNT(*) AS c
                FROM ' . $this->_tbl . '
                WHERE randevu_tarihi >= ?
                  AND randevu_tarihi < ?'
                . TenantSqlHelper::andBare('kurum_id') . '
                GROUP BY d';
        $rows = $this->db->fetchObjectListPrepared($sql, [$start, $endExcl]);
        $out = [];
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $d = (string) ($r->d ?? '');
                if ($d !== '') {
                    $out[$d] = (int) ($r->c ?? 0);
                }
            }
        }

        return $out;
    }

    /**
     * @return array<int, object>
     */
    public function getByDate(string $ymd): array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
            return [];
        }
        $sql = "SELECT r.*, b.bransadi,
                       (SELECT GROUP_CONCAT(ist.istek_adi ORDER BY ist.istek_adi SEPARATOR ', ')
                        FROM #__istekler ist
                        WHERE FIND_IN_SET(ist.id, REPLACE(r.kons_istekler, ' ', '')) > 0) AS kons_istekler_adlari,
                       h.id AS hasta_id, h.isim AS hasta_isim, h.soyisim AS hasta_soyisim,
                       u.name AS olusturan_ad
                FROM {$this->_tbl} r
                INNER JOIN #__branslar b ON b.id = r.brans_id
                LEFT JOIN #__hastalar h ON h.tckimlik = r.hastatckimlik AND h.kurum_id = r.kurum_id
                LEFT JOIN #__users u ON u.id = r.olusturan_id
                WHERE r.randevu_tarihi = ?"
                . TenantSqlHelper::andEquals('r', 'kurum_id') . "
                ORDER BY r.zaman ASC, b.bransadi ASC, r.id ASC";
        $list = $this->db->fetchObjectListPrepared($sql, [$ymd]);

        return is_array($list) ? $list : [];
    }

    /**
     * Seçilen günde branş başına kayıtlı randevu (hasta) sayısı.
     *
     * @return array<int, int> brans_id => adet
     */
    public function countByBransForDate(string $ymd): array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
            return [];
        }
        $sql = 'SELECT brans_id, COUNT(*) AS c FROM ' . $this->_tbl . '
                WHERE randevu_tarihi = ?'
                . TenantSqlHelper::andBare('kurum_id') . '
                GROUP BY brans_id';
        $rows = $this->db->fetchObjectListPrepared($sql, [$ymd]);
        $out = [];
        if (is_array($rows)) {
            foreach ($rows as $r) {
                $bid = (int) ($r->brans_id ?? 0);
                if ($bid > 0) {
                    $out[$bid] = (int) ($r->c ?? 0);
                }
            }
        }

        return $out;
    }

    public function countForBransOnDate(int $bransId, string $ymd): int
    {
        if ($bransId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
            return 0;
        }
        $sql = 'SELECT COUNT(*) FROM ' . $this->_tbl . '
                WHERE randevu_tarihi = ?
                  AND brans_id = ?'
                . TenantSqlHelper::andBare('kurum_id');

        return (int) $this->db->loadResultPrepared($sql, [$ymd, $bransId]);
    }

    public function existsDuplicate(string $ymd, int $bransId, string $tc): bool
    {
        if (!preg_match('/^\d{11}$/', $tc) || $bransId <= 0) {
            return true;
        }
        $sql = 'SELECT id FROM ' . $this->_tbl . '
                WHERE randevu_tarihi = ?
                  AND brans_id = ?
                  AND hastatckimlik = ?'
                . TenantSqlHelper::andBare('kurum_id') . '
                LIMIT 1';

        return (bool) $this->db->loadResultPrepared($sql, [$ymd, $bransId, $tc]);
    }

    public function deleteById(int|string $id): bool
    {
        $rid = IdHelper::normalizeRequestId($id);
        if ($rid === null) {
            return false;
        }

        return $this->db->executePrepared(
            'DELETE FROM ' . $this->_tbl . ' WHERE id = ?' . TenantSqlHelper::andBare('kurum_id'),
            [$rid]
        );
    }

    public function load($id)
    {
        $rid = IdHelper::normalizeRequestId($id);
        if ($rid === null) {
            return false;
        }
        $query = 'SELECT * FROM ' . $this->_tbl . ' WHERE ' . $this->_tbl_key . ' = ?'
            . TenantSqlHelper::andBare('kurum_id');
        $res = $this->db->fetchObjectPrepared($query, [$rid]);
        if ($res) {
            $this->_dirty = [];
            $this->bind($res, false);

            return true;
        }

        return false;
    }
}
