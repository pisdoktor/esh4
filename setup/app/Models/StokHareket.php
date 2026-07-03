<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Helpers\TenantSqlHelper;

/**
 * Stok hareket defteri.
 */
class StokHareket extends BaseModel
{
    public $id = null;
    public $kurum_id = 1;
    public $malzeme_id = 0;
    public $hareket_tipi = 'giris';
    public $miktar = 0;
    public $hareket_tarihi = null;
    public $hasta_id = null;
    public $ekip_id = null;
    public $kullanici_id = 0;
    public $aciklama = null;
    public $created_at = null;

    public function __construct()
    {
        parent::__construct('#__stok_hareket', 'id');
    }

    public static function tableReady(): bool
    {
        return StokMalzeme::tableReady();
    }

    /**
     * @return array{0: list<string>, 1: list<mixed>}
     */
    private function buildFilterParts(array $filters): array
    {
        $where = ['1=1'];
        $params = [];
        TenantSqlHelper::mergeParts($where, 'h', 'kurum_id');

        $malzemeId = (int) ($filters['malzeme_id'] ?? 0);
        if ($malzemeId > 0) {
            $where[] = 'h.malzeme_id = ?';
            $params[] = $malzemeId;
        }

        $tipIn = $filters['hareket_tipi_in'] ?? null;
        if (is_array($tipIn) && $tipIn !== []) {
            $validTips = array_values(array_filter($tipIn, static function ($t): bool {
                return is_string($t) && array_key_exists($t, \App\Helpers\StokHelper::hareketTipiOptions());
            }));
            if ($validTips !== []) {
                $placeholders = implode(', ', array_fill(0, count($validTips), '?'));
                $where[] = 'h.hareket_tipi IN (' . $placeholders . ')';
                foreach ($validTips as $t) {
                    $params[] = $t;
                }
            }
        } else {
            $tip = trim((string) ($filters['hareket_tipi'] ?? ''));
            if ($tip !== '' && array_key_exists($tip, \App\Helpers\StokHelper::hareketTipiOptions())) {
                $where[] = 'h.hareket_tipi = ?';
                $params[] = $tip;
            }
        }

        $hastaId = (int) ($filters['hasta_id'] ?? 0);
        if ($hastaId > 0) {
            $where[] = 'h.hasta_id = ?';
            $params[] = $hastaId;
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $where[] = 'h.hareket_tarihi >= ?';
            $params[] = $dateFrom;
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $where[] = 'h.hareket_tarihi <= ?';
            $params[] = $dateTo;
        }

        return [$where, $params];
    }

    /**
     * @return list<object>
     */
    public function listFiltered(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        if (!self::tableReady()) {
            return [];
        }

        [$where, $params] = $this->buildFilterParts($filters);

        $lim = max(1, min(200, $limit));
        $off = max(0, $offset);

        $sql = 'SELECT h.*,
                       m.ad AS malzeme_adi, m.birim AS malzeme_birim, m.kod AS malzeme_kod,
                       p.isim AS hasta_isim, p.soyisim AS hasta_soyisim,
                       u.name AS kullanici_adi,
                       e.tarih AS ekip_tarih, e.ekip_no AS ekip_no
                FROM #__stok_hareket AS h
                INNER JOIN #__stok_malzeme AS m ON m.id = h.malzeme_id
                LEFT JOIN #__hastalar AS p ON p.id = h.hasta_id
                LEFT JOIN #__users AS u ON u.id = h.kullanici_id
                LEFT JOIN #__ekipler AS e ON e.id = h.ekip_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY h.hareket_tarihi DESC, h.id DESC
                LIMIT ' . $lim . ' OFFSET ' . $off;

        $list = $this->db->fetchObjectListPrepared($sql, $params);

        return is_array($list) ? $list : [];
    }

    public function countFiltered(array $filters = []): int
    {
        if (!self::tableReady()) {
            return 0;
        }

        [$where, $params] = $this->buildFilterParts($filters);

        $sql = 'SELECT COUNT(*) FROM #__stok_hareket AS h WHERE ' . implode(' AND ', $where);
        $n = $this->db->loadResultPrepared($sql, $params);

        return (int) ($n ?? 0);
    }

    /**
     * Son N gün çıkış toplamı (kurum kapsamı).
     */
    public function sumCikisSince(?int $kurumId, int $days = 30): float
    {
        if (!self::tableReady() || $days < 1) {
            return 0.0;
        }
        $where = ['h.hareket_tipi = ?', 'h.hareket_tarihi >= DATE_SUB(CURDATE(), INTERVAL ' . (int) $days . ' DAY)'];
        $params = ['cikis'];
        if ($kurumId !== null && $kurumId > 0) {
            $where[] = 'h.kurum_id = ?';
            $params[] = $kurumId;
        } else {
            TenantSqlHelper::mergeParts($where, 'h', 'kurum_id');
        }
        $sql = 'SELECT COALESCE(SUM(h.miktar), 0) FROM #__stok_hareket AS h WHERE ' . implode(' AND ', $where);
        $val = $this->db->loadResultPrepared($sql, $params);

        return (float) ($val ?? 0);
    }

    /**
     * Son 30 gün ekipleri — çıkış formu seçimi.
     *
     * @return list<object>
     */
    public function listRecentEkipler(?int $kurumId = null): array
    {
        $db = Database::getInstance();
        $where = ['e.tarih >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)'];
        $params = [];
        if ($kurumId !== null && $kurumId > 0) {
            $where[] = 'e.kurum_id = ?';
            $params[] = $kurumId;
        } else {
            TenantSqlHelper::mergeParts($where, 'e', 'kurum_id');
        }
        $sql = 'SELECT e.id, e.tarih, e.ekip_no, e.vardiya, e.baslangic_saati
                FROM #__ekipler AS e
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY e.tarih DESC, e.ekip_no ASC
                LIMIT 100';
        $list = $db->fetchObjectListPrepared($sql, $params);

        return is_array($list) ? $list : [];
    }
}
