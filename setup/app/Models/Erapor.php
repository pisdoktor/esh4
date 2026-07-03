<?php
namespace App\Models;

use App\Helpers\TenantContext;
use App\Helpers\TenantSqlHelper;

/**
 * Elektronik Rapor (e-Rapor) Modeli
 */
class Erapor extends BaseModel {
    
    // Veritabanı sütunları
    public $id = null;
    public $kurum_id = 1;
    public $hastatckimlik = null;
    public $isim = null;
    public $soyisim = null;
    public $ceptel1 = null;
    public $basvurutarihi = null;
    public $brans = null;
    /** JOIN ile doldurulur (#__branslar.bransadi) */
    public $bransadi = null;
    public $kayitlimi = 0;
    public $yenilendimi = 0;
    public $neden = null;
    public $esys_erapor_ref = null;

    public function __construct() {
        parent::__construct('#__erapor', 'id');
    }

    /** Kayıt öncesi TC normalizasyonu (trim; indeks dostu). */
    public static function normalizeHastatckimlik(?string $tc): ?string
    {
        if ($tc === null) {
            return null;
        }
        $tc = trim($tc);
        if ($tc === '') {
            return '';
        }
        $digits = preg_replace('/\D+/', '', $tc);

        return strlen($digits) === 11 ? $digits : $tc;
    }

    public function bind($data, $trackDirty = true)
    {
        parent::bind($data, $trackDirty);
        if ($this->hastatckimlik !== null) {
            $normalized = self::normalizeHastatckimlik((string) $this->hastatckimlik);
            $this->hastatckimlik = $normalized;
            if ($trackDirty && array_key_exists('hastatckimlik', $this->_dirty)) {
                $this->_dirty['hastatckimlik'] = $normalized;
            }
        }
    }

    private function bransJoinSql(): string
    {
        return 'LEFT JOIN #__branslar b ON (e.brans = b.id OR e.brans = b.bransadi)';
    }

    private function tcHavuzAggregateJoinSql(): string
    {
        $subParts = ['hastatckimlik IS NOT NULL', "hastatckimlik <> ''"];
        TenantSqlHelper::mergeParts($subParts, '', 'kurum_id');

        return 'LEFT JOIN (
            SELECT hastatckimlik, COUNT(*) AS tc_havuz_adet
            FROM #__erapor
            WHERE ' . implode(' AND ', $subParts) . '
            GROUP BY hastatckimlik
        ) tc_agg ON tc_agg.hastatckimlik = e.hastatckimlik';
    }

    /**
     * Tüm e-raporları tarih sırasına göre getirir
     */
    public function getAllReports() {
        return $this->db->fetchObjectListPrepared('SELECT * FROM #__erapor ORDER BY id DESC');
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{0: string, 1: list<mixed>}
     */
    private function buildFiltersWherePrepared(array $filters): array
    {
        $where = ['1=1'];
        $params = [];

        $bransId = isset($filters['bransId']) ? (int) $filters['bransId'] : 0;
        if ($bransId > 0) {
            $where[] = '(e.brans = ? OR e.brans = (SELECT bransadi FROM #__branslar WHERE id = ? LIMIT 1))';
            $params[] = $bransId;
            $params[] = $bransId;
        }

        if (isset($filters['kayitlimi']) && $filters['kayitlimi'] !== '' && $filters['kayitlimi'] !== null) {
            $where[] = 'CAST(COALESCE(e.kayitlimi, 0) AS SIGNED) = ?';
            $params[] = (int) $filters['kayitlimi'];
        }
        if (isset($filters['yenilendimi']) && $filters['yenilendimi'] !== '' && $filters['yenilendimi'] !== null) {
            $where[] = 'CAST(COALESCE(e.yenilendimi, 0) AS SIGNED) = ?';
            $params[] = (int) $filters['yenilendimi'];
        }

        $dateFrom = trim((string) ($filters['dateFrom'] ?? ''));
        $dateTo = trim((string) ($filters['dateTo'] ?? ''));
        if ($dateFrom !== '') {
            $where[] = 'DATE(e.basvurutarihi) >= ?';
            $params[] = $dateFrom;
        }
        if ($dateTo !== '') {
            $where[] = 'DATE(e.basvurutarihi) <= ?';
            $params[] = $dateTo;
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $where[] = "(e.hastatckimlik LIKE ?
                OR CONCAT_WS(' ', TRIM(e.isim), TRIM(e.soyisim)) LIKE ?
                OR CONCAT_WS(' ', TRIM(e.soyisim), TRIM(e.isim)) LIKE ?
                OR CAST(e.brans AS CHAR) LIKE ?
                OR CAST(COALESCE(b.bransadi, '') AS CHAR) LIKE ?)";
            $params = array_merge($params, [$like, $like, $like, $like, $like]);
        }

        TenantSqlHelper::mergeParts($where, 'e', 'kurum_id');

        return [implode(' AND ', $where), $params];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function buildOrderSql(array $filters): string
    {
        $orderby = trim((string) ($filters['orderby'] ?? 'basvurutarihi'));
        $orderdir = strtoupper(trim((string) ($filters['orderdir'] ?? 'DESC')));
        $dir = $orderdir === 'ASC' ? 'ASC' : 'DESC';
        $map = [
            'hastatckimlik' => 'e.hastatckimlik',
            'adsoyad' => 'e.isim',
            'basvurutarihi' => 'e.basvurutarihi',
            'brans' => 'COALESCE(NULLIF(TRIM(CAST(b.bransadi AS CHAR)), \'\'), TRIM(CAST(e.brans AS CHAR)))',
            'kayitlimi' => 'CAST(COALESCE(e.kayitlimi, 0) AS SIGNED)',
            'yenilendimi' => 'CAST(COALESCE(e.yenilendimi, 0) AS SIGNED)',
            'neden' => 'e.neden',
        ];
        $col = $map[$orderby] ?? 'e.basvurutarihi';
        if ($orderby === 'adsoyad') {
            return "ORDER BY e.isim {$dir}, e.soyisim {$dir}, e.id DESC";
        }
        return "ORDER BY {$col} {$dir}, e.id DESC";
    }

    /**
     * Filtreye göre toplam e-rapor sayısı
     */
    public function countReports($filters = []): int {
        if (!is_array($filters)) {
            $filters = ['bransId' => (int) $filters];
        }
        [$where, $params] = $this->buildFiltersWherePrepared($filters);
        $search = trim((string) ($filters['search'] ?? ''));
        $join = $search !== '' ? ' ' . $this->bransJoinSql() : '';
        $sql = "SELECT COUNT(*) FROM #__erapor e{$join} WHERE {$where}";
        return (int) $this->db->loadResultPrepared($sql, $params);
    }

    /**
     * Tek kayıt; branş adı JOIN ile (id veya bransadi eşleşmesi).
     */
    public function loadWithBrans($id): bool {
        $id = (int) $id;
        if ($id < 1) {
            return false;
        }
        $sql = "SELECT e.*, b.bransadi
            FROM #__erapor AS e
            {$this->bransJoinSql()}
            WHERE e.{$this->_tbl_key} = ?";
        $res = $this->db->fetchObjectPrepared($sql, [$id]);
        if (!$res) {
            return false;
        }
        $this->_dirty = [];
        $this->bind($res, false);

        return true;
    }

    /**
     * Sayfalı liste; bransadi JOIN ile (id veya isim eşleşmesi).
     */
    public function getReportsPage(int $limit, int $offset, $filters = []) {
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);
        if (!is_array($filters)) {
            $filters = ['bransId' => (int) $filters];
        }
        [$where, $params] = $this->buildFiltersWherePrepared($filters);
        $orderBy = $this->buildOrderSql($filters);
        $sql = "SELECT e.*, b.bransadi, COALESCE(tc_agg.tc_havuz_adet, 1) AS tc_havuz_adet
            FROM #__erapor e
            {$this->bransJoinSql()}
            {$this->tcHavuzAggregateJoinSql()}
            WHERE {$where}
            {$orderBy}
            LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        return $this->db->fetchObjectListPrepared($sql, $params);
    }

    /**
     * Belirli TC için filtreli tüm e-rapor kayıtları (grup genişletme).
     *
     * @param array<string, mixed> $filters
     * @return list<object>
     */
    public function getReportsByTc(string $tc, array $filters = [], ?int $excludeId = null): array {
        $tc = self::normalizeHastatckimlik($tc) ?? '';
        if ($tc === '') {
            return [];
        }
        if (!is_array($filters)) {
            $filters = ['bransId' => (int) $filters];
        }
        [$where, $params] = $this->buildFiltersWherePrepared($filters);
        $where .= ' AND e.hastatckimlik = ?';
        $params[] = $tc;
        if ($excludeId !== null && $excludeId > 0) {
            $where .= ' AND e.id <> ?';
            $params[] = $excludeId;
        }
        $orderBy = $this->buildOrderSql($filters);
        $sql = "SELECT e.*, b.bransadi
            FROM #__erapor e
            {$this->bransJoinSql()}
            WHERE {$where}
            {$orderBy}";
        return $this->db->fetchObjectListPrepared($sql, $params);
    }

    /**
     * e-Rapor havuzunda aynı TC ile kayıt sayısı (düzenlemede mevcut kayıt hariç tutulabilir).
     */
    public function countByTc(string $tc, ?int $excludeId = null): int {
        $tc = self::normalizeHastatckimlik($tc) ?? '';
        if ($tc === '') {
            return 0;
        }
        $params = [$tc];
        $where = 'hastatckimlik = ?';
        if ($excludeId !== null && $excludeId > 0) {
            $where .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $parts = [$where];
        TenantSqlHelper::mergeParts($parts, '', 'kurum_id');
        $sql = 'SELECT COUNT(*) FROM #__erapor WHERE ' . implode(' AND ', $parts);
        return (int) $this->db->loadResultPrepared($sql, $params);
    }

    /**
     * Gelen raporun T.C. Kimlik numarasının sistemde (#__hastalar)
     * kayıtlı olup olmadığını kontrol eder.
     */
    public function matchWithSystem() {
    if (!empty($this->hastatckimlik)) {
        $tc = self::normalizeHastatckimlik((string) $this->hastatckimlik);
        $kurumId = isset($this->kurum_id) && (int) $this->kurum_id > 0
            ? (int) $this->kurum_id
            : TenantContext::filterKurumId();
        $params = [$tc];
        $sql = 'SELECT id FROM #__hastalar WHERE tckimlik = ?';
        if ($kurumId !== null && $kurumId > 0) {
            $sql .= ' AND kurum_id = ?';
            $params[] = $kurumId;
        }
        $exists = $this->db->loadResultPrepared($sql, $params);
        
        // Varsa kayitlimi alanını 1 yap ve kaydet
        $this->kayitlimi = $exists ? 1 : 0;
        return $this->store(); // BaseModel'deki store metodunu kullanır
    }
    return false;
}
    
    // Erapor.php içine eklenebilir
public function getReportsWithBrans() {
    $sql = "SELECT e.*, b.bransadi
            FROM #__erapor e
            {$this->bransJoinSql()}
            ORDER BY e.basvurutarihi DESC";
    return $this->db->fetchObjectListPrepared($sql);
}
}
