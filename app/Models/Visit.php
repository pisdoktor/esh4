<?php
namespace App\Models;

use App\Helpers\VisitIslemHelper;
use App\Helpers\ZamanDilimiHelper;
use App\Helpers\TenantSqlHelper;

/**
 * Ziyaret Modeli
 * Gerçekleşen ziyaretlerin (#__izlemler) kaydı için kullanılır.
 */
class Visit extends BaseModel {
    
    // Veritabanı sütunları
    public $id = null;
    public $kurum_id = 1;
    public $hastatckimlik = null;
    public $izlemtarihi = null;
    public $yapilan = null;
    public $yapildimi = 0;
    public $neden = null;
    /** @var string|null virgüllü #__users.id */
    public $izlemiyapan = null;
    public $zaman = null;
    public $aciklama = null;
    /** @var int|null #__araclar.id */
    public $arac = null;
    /** @var string|null virgülle #__branslar.id (konsültasyon EK-3) */
    public $brans = null;
    /** @var string|null virgülle #__istekler.id */
    public $kons_istekler = null;
    /** @var string|null JSON brans_id => [istek_id] (konsültasyon EK-3) */
    public $kons_brans_istek = null;

    public function __construct() {
        parent::__construct('#__izlemler', 'id');
    }

    /**
     * Eski ESH getIzlemList ile uyumlu: tarih aralığı, işlem (FIND_IN_SET), yapıldı/yapılmadı,
     * çoklu yapilan / izlemiyapan için alt sorgular (virgüllü ID).
     *
     * @param string $dateFrom Y-m-d veya boş
     * @param string $dateTo   Y-m-d veya boş
     * @param int    $islemId  0 = tüm işlemler, aksi halde #__islemler.id
     */
    public function getAllVisits(
        $limit = 20,
        $offset = 0,
        $search = '',
        $filterYapildi = '',
        $ordering = '',
        $dateFrom = '',
        $dateTo = '',
        $islemId = 0
    ) {
        [$whereSql, $params] = $this->buildIzlemListWherePrepared($search, $filterYapildi, $dateFrom, $dateTo, $islemId);
        $orderSql = $this->izlemOrderClause($ordering);

        $query = "SELECT i.*, h.id AS hid, h.isim, h.soyisim, h.cinsiyet, h.gecici, h.tckimlik,
                  a1.adi AS ilce, a2.adi AS mahalle, a3.adi AS sokakadi,
                  ar.plaka AS aracplaka, ar.arac_bilgisi AS arac_bilgisi,
                  (SELECT GROUP_CONCAT(isl2.islemadi ORDER BY isl2.id SEPARATOR ', ')
                     FROM #__islemler isl2
                     WHERE FIND_IN_SET(isl2.id, REPLACE(i.yapilan, ' ', ''))) AS yapilanlar,
                  (SELECT GROUP_CONCAT(u.name ORDER BY u.id SEPARATOR ', ')
                     FROM #__users u
                     WHERE FIND_IN_SET(u.id, REPLACE(CAST(i.izlemiyapan AS CHAR), ' ', ''))) AS yapanlar,
                  (SELECT COUNT(*) FROM #__izlemler i2 WHERE i2.yapildimi = 1 AND i2.hastatckimlik = h.tckimlik) AS izlemsayisi,
                  (SELECT COUNT(*) FROM #__izlemler i2 WHERE i2.yapildimi = 0 AND i2.hastatckimlik = h.tckimlik) AS yizlemsayisi,
                  (SELECT COUNT(*) FROM #__pizlemler p WHERE p.hastatckimlik = h.tckimlik) AS totalplanli
                  FROM #__izlemler AS i
                  LEFT JOIN #__hastalar AS h ON h.tckimlik = i.hastatckimlik
                  LEFT JOIN #__araclar AS ar ON ar.id = i.arac
                  LEFT JOIN #__adrestablosu AS a1 ON h.ilce = a1.id
                  LEFT JOIN #__adrestablosu AS a2 ON h.mahalle = a2.id
                  LEFT JOIN #__adrestablosu AS a3 ON h.sokak = a3.id
                  $whereSql
                  $orderSql
                  LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        return $this->db->fetchObjectListPrepared($query, $params);
    }

    public function countAllVisits(
        $search = '',
        $filterYapildi = '',
        $dateFrom = '',
        $dateTo = '',
        $islemId = 0
    ) {
        [$whereSql, $params] = $this->buildIzlemListWherePrepared($search, $filterYapildi, $dateFrom, $dateTo, $islemId);
        $query = "SELECT COUNT(i.id) FROM #__izlemler AS i
                  LEFT JOIN #__hastalar AS h ON h.tckimlik = i.hastatckimlik
                  $whereSql";
        return $this->db->loadResultPrepared($query, $params);
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function buildIzlemListWherePrepared($search, $filterYapildi, $dateFrom, $dateTo, $islemId): array {
        $where = [];
        $params = [];

        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $where[] = "(i.hastatckimlik LIKE ? OR h.isim LIKE ? OR h.soyisim LIKE ?
                OR CONCAT(COALESCE(h.isim,''), ' ', COALESCE(h.soyisim,'')) LIKE ?)";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($filterYapildi !== '') {
            $where[] = 'i.yapildimi = ?';
            $params[] = (int) $filterYapildi;
        }
        $df = $this->normalizeYmd($dateFrom);
        $dt = $this->normalizeYmd($dateTo);
        if ($df !== null) {
            $where[] = 'i.izlemtarihi >= ?';
            $params[] = $df;
        }
        if ($dt !== null) {
            $where[] = 'i.izlemtarihi <= ?';
            $params[] = $dt;
        }
        if ((int)$islemId > 0) {
            $where[] = 'FIND_IN_SET(?, REPLACE(i.yapilan, \' \', \'\'))';
            $params[] = (int) $islemId;
        }
        TenantSqlHelper::mergeParts($where, 'h', 'kurum_id');

        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        return [$whereSql, $params];
    }

    private function normalizeYmd($d) {
        if ($d === null || $d === '') {
            return null;
        }
        $d = trim((string)$d);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
            return $d;
        }
        return null;
    }

    /**
     * Eski sistem: ordering = "i.izlemtarihi-DESC" (son tire ayırır).
     */
    private function izlemOrderClause($ordering) {
        $fallback = 'ORDER BY h.isim ASC, h.soyisim ASC, i.id DESC';
        if ($ordering === '' || $ordering === null) {
            return $fallback;
        }
        $pos = strrpos($ordering, '-');
        if ($pos === false) {
            return $fallback;
        }
        $col = substr($ordering, 0, $pos);
        $dir = strtoupper(substr($ordering, $pos + 1));
        $dir = ($dir === 'DESC') ? 'DESC' : 'ASC';
        $allowed = [
            'i.izlemtarihi' => true,
            'i.zaman' => true,
            'h.isim' => true,
            'h.soyisim' => true,
            'h.tckimlik' => true,
            'i.yapilan' => true,
            'a2.adi' => true,
        ];
        if (!isset($allowed[$col])) {
            return $fallback;
        }
        if ($col === 'h.isim') {
            return "ORDER BY h.isim $dir, h.soyisim $dir, i.id DESC";
        }
        if ($col === 'h.soyisim') {
            return "ORDER BY h.soyisim $dir, h.isim $dir, i.id DESC";
        }
        return "ORDER BY $col $dir, i.id DESC";
    }

    public function getPatientVisits($tc, $limit = 20, $offset = 0, $filterYapildi = '', $ordering = 'i.izlemtarihi DESC') {
        $tc = preg_replace('/\D/', '', trim((string) $tc));
        if ($tc === '' || strlen($tc) !== 11) {
            return [];
        }

        $where = [];
        $params = [$tc];
        $where[] = 'i.hastatckimlik = ?';

        if ($filterYapildi !== '') {
            $where[] = 'i.yapildimi = ?';
            $params[] = (int) $filterYapildi;
        }

        $whereSql = count($where) ? ' WHERE ' . implode(' AND ', $where) : '';

        $query = "SELECT i.*, h.id AS hid, h.isim, h.soyisim, h.cinsiyet,
                  i.kurum_id, ku.ad AS kurum_adi,
                  ar.plaka AS aracplaka, ar.arac_bilgisi AS arac_bilgisi,
                  (SELECT GROUP_CONCAT(isl2.islemadi ORDER BY isl2.id SEPARATOR ', ')
                     FROM #__islemler isl2
                     WHERE FIND_IN_SET(isl2.id, REPLACE(i.yapilan, ' ', ''))) AS yapilanlar,
                  (SELECT GROUP_CONCAT(u.name ORDER BY u.id SEPARATOR ', ')
                     FROM #__users u
                     WHERE FIND_IN_SET(u.id, REPLACE(CAST(i.izlemiyapan AS CHAR), ' ', ''))) AS yapanlar,
                  (SELECT GROUP_CONCAT(b.bransadi ORDER BY b.bransadi ASC SEPARATOR ', ')
                     FROM #__branslar b
                     WHERE FIND_IN_SET(b.id, REPLACE(COALESCE(i.brans, ''), ' ', '')) > 0) AS brans_adlari,
                  (SELECT GROUP_CONCAT(ist.istek_adi ORDER BY ist.istek_adi ASC SEPARATOR ', ')
                     FROM #__istekler ist
                     WHERE FIND_IN_SET(ist.id, REPLACE(COALESCE(i.kons_istekler, ''), ' ', '')) > 0) AS kons_istekler_adlari
                  FROM #__izlemler AS i
                  LEFT JOIN #__hastalar AS h ON h.tckimlik = i.hastatckimlik
                  LEFT JOIN #__kurumlar AS ku ON ku.id = i.kurum_id
                  LEFT JOIN #__araclar AS ar ON ar.id = i.arac
                  $whereSql
                  ORDER BY $ordering
                  LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        return $this->db->fetchObjectListPrepared($query, $params);
    }
    
    public function countPatientVisits($tc, $filterYapildi = '') {
        $tc = preg_replace('/\D/', '', trim((string) $tc));
        if ($tc === '' || strlen($tc) !== 11) {
            return 0;
        }

        $where = [];
        $params = [$tc];
        $where[] = 'hastatckimlik = ?';
        
        if ($filterYapildi !== '') {
            $where[] = 'yapildimi = ?';
            $params[] = (int) $filterYapildi;
        }

        $whereSql = count($where) ? ' WHERE ' . implode(' AND ', $where) : '';

        $query = "SELECT COUNT(*) FROM #__izlemler{$whereSql}";
        
        return $this->db->loadResultPrepared($query, $params);
    }
    /**
     * Ziyarette yapılan işlemleri (tek veya çoklu) isim olarak getirir.
     * Veritabanında '1,4,7' gibi saklandığını varsayar.
     */
    public function getYapilanIslemler() {
        if (empty($this->yapilan)) {
            return "İşlem belirtilmemiş";
        }

        $ids = VisitIslemHelper::yapilanCsvToIntIds($this->yapilan);
        if ($ids === []) {
            return "İşlem belirtilmemiş";
        }
        [$inSql, $inParams] = $this->db->whereInClause($ids);
        $sql = "SELECT GROUP_CONCAT(islemadi SEPARATOR ', ') as islem_listesi 
                FROM #__islemler 
                WHERE id IN ({$inSql})";
                
        return $this->db->loadResultPrepared($sql, $inParams);
    }

    public function getYapilanIslemIsimleri() {
        if (empty($this->yapilan)) return "İşlem Yok";
        $ids = VisitIslemHelper::yapilanCsvToIntIds($this->yapilan);
        if ($ids === []) {
            return "İşlem Yok";
        }
        [$inSql, $inParams] = $this->db->whereInClause($ids);
        $sql = "SELECT GROUP_CONCAT(islemadi SEPARATOR ' + ') FROM #__islemler WHERE id IN ({$inSql})";
        return $this->db->loadResultPrepared($sql, $inParams);
    }

    public function getYapanPersonel() {
        if (empty($this->izlemiyapan)) {
            return '';
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', str_replace(' ', '', (string) $this->izlemiyapan))))));
        if ($ids === []) {
            return '';
        }
        [$inSql, $inParams] = $this->db->whereInClause($ids);
        $fieldOrder = implode(',', $ids);
        $sql = "SELECT GROUP_CONCAT(name ORDER BY FIELD(id, {$fieldOrder}) SEPARATOR ', ') AS n FROM #__users WHERE id IN ({$inSql})";
        return $this->db->loadResultPrepared($sql, $inParams) ?: '';
    }

    /**
     * Belirtilen gün (Y-m-d) için hastanın mevcut izlem satırı sayısı.
     */
    public function countForPatientOnDate(string $tc, string $ymd, int $excludeId = 0): int
    {
        $tc = trim($tc);
        if ($tc === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
            return 0;
        }
        $params = [$tc, $ymd];
        $excludeSql = '';
        if ($excludeId > 0) {
            $excludeSql = ' AND id <> ?';
            $params[] = $excludeId;
        }
        $sql = "SELECT COUNT(*) FROM #__izlemler WHERE hastatckimlik = ? AND izlemtarihi = ?{$excludeSql}";

        return (int) $this->db->loadResultPrepared($sql, $params);
    }

    /**
     * Aynı gün + aynı zaman diliminde mevcut izlemlerde çakışan işlem id'leri.
     *
     * @param int[] $requestedIslemIds
     * @return int[]
     */
    public function overlappingIslemIdsForPatientOnDate(
        string $tc,
        string $ymd,
        array $requestedIslemIds,
        int $requestedZaman,
        int $excludeId = 0
    ): array {
        $requestedIslemIds = array_values(array_unique(array_filter(array_map('intval', $requestedIslemIds))));
        $tc = trim($tc);
        if (
            $tc === ''
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)
            || $requestedIslemIds === []
            || !ZamanDilimiHelper::isValid($requestedZaman)
        ) {
            return [];
        }

        $zNorm = (int) $requestedZaman;
        $zExpr = ZamanDilimiHelper::sqlNormalizeCaseExpr('zaman');
        $params = [$tc, $ymd, $zNorm];
        $excludeSql = '';
        if ($excludeId > 0) {
            $excludeSql = ' AND id <> ?';
            $params[] = $excludeId;
        }
        $sql = "SELECT yapilan FROM #__izlemler
                WHERE hastatckimlik = ? AND izlemtarihi = ?
                  AND {$zExpr} = ?{$excludeSql}";
        $rows = $this->db->fetchObjectListPrepared($sql, $params);
        if (!$rows) {
            return [];
        }

        $overlap = [];
        foreach ($rows as $row) {
            $existing = VisitIslemHelper::yapilanCsvToIntIds($row->yapilan ?? null);
            $overlap = array_merge($overlap, array_intersect($requestedIslemIds, $existing));
        }

        return array_values(array_unique($overlap));
    }
}
