<?php
namespace App\Models;

use App\Helpers\PatientListSqlHelper;
use App\Helpers\TenantContext;
use App\Helpers\TenantSqlHelper;
use App\Models\HastaNakil;

/**
 * Hasta Modeli
 * BaseModel'den miras alarak bind, store ve save yeteneklerini kullanır.
 */
class Patient extends BaseModel {
    // Veritabanı tablo sütunları (Özellikler)
    // Kimlik Bilgileri
    public $id = null;
    public $kurum_id = 1;
    public $tckimlik = null;
    public $isim = null;
    public $soyisim = null;
    public $anneAdi = null;
    public $babaAdi = null;
    public $dogumtarihi = null;
    public $cinsiyet = null;

    // Fiziksel Bilgiler
    public $kilo = null;
    public $boy = null;

    // İletişim ve Adres Bilgileri
    public $kayittarihi = null;
    public $ceptel1 = null;
    public $ceptel2 = null;
    public $bakimveren_ad = null;
    public $bakimveren_tel = null;
    public $bakimveren_yakinlik = null;
    public $alerji = null;
    public $acil_not = null;
    public $guvence = null;
    public $yupasno = null;
    public $ailehekimi = null;
    public $ailehekimitel = null;
    public $sms_bilgilendirme_onay = 1;
    public $kangrubu = null;
    public $ilce = null;
    public $mahalle = null;
    public $sokak = null;
    public $kapino = null;
    public $adres_aciklama = null;
    public $diger_adres = null;
    public $coords = null;

    // Sağlık Durumu ve Bağımlılık (Barthel İndeksi vb.)
    public $bagimlilik = null;
    public $barbeslenme = null;
    public $barbanyo = null;
    public $barbakim = null;
    public $bargiyinme = null;
    public $barbarsak = null;
    public $barmesane = null;
    public $bartuvalet = null;
    public $bartransfer = null;
    public $barmobilite = null;
    public $barmerdiven = null;

    // Durum Bilgileri
    public $pasif = 0;
    public $pasiftarihi = null;
    public $pasifnedeni = null;
    public $gecici = 0;

    // Tıbbi Cihaz ve Destek Bilgileri
    public $ng = 0; // Nasogastrik tüp
    public $peg = 0; // Perkütan Endoskopik Gastrostomi
    public $port = 0;
    public $o2bagimli = 0;
    public $ventilator = 0;
    public $kolostomi = 0;
    public $trakeostomi = 0;
    public $cpap = 0;
    public $aspirasyon = 0;
    public $ileostomi = 0;
    public $urostomi = 0;
    public $picc = 0;
    public $dren = 0;
    public $diyaliz = 0;
    public $basiyarasi = 0;
    public $ivtedavi = 0;
    public $izolasyon = 0;
    public $sonda = 0;
    public $sondatarihi = null;

    // Bakım ve Sarf Malzeme
    public $pansuman = 0;
    public $pgunleri = null;
    public $pzaman = null;
    public $mama = 0;
    public $mamacesit = null;
    public $mamaraporbitis = null;
    public $mamaraporyeri = null;
    public $bez = 0;
    public $bezrapor = 0;
    public $bezraporbitis = null;
    public $yatak = 0;

    // Genel Notlar ve Randevu
    public $hastaliklar = null;
    public $erapor = null;
    public $randevutarihi = null;
    public $zaman = null;
    public $notes = null;
    public $profil_foto = null;

    public function __construct() {
        // '#__hastalar' tablosunu kullan, birincil anahtar 'id'
        parent::__construct('#__hastalar', 'id');
    }

    /**
     * Liste araması için güvenli LIKE koşulu (ilçe adı: a1 join).
     */
    private function searchWhere($search) {
        $search = is_string($search) ? trim($search) : '';
        if ($search === '') {
            return '';
        }
        $search = preg_replace('/\s+/', ' ', $search);
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
        $like = $this->db->quote('%' . $escaped . '%');
        return " AND (
            h.isim LIKE $like
            OR h.soyisim LIKE $like
            OR CONCAT_WS(' ', TRIM(h.isim), TRIM(h.soyisim)) LIKE $like
            OR CONCAT_WS(' ', TRIM(h.soyisim), TRIM(h.isim)) LIKE $like
            OR h.tckimlik LIKE $like
            OR a1.adi LIKE $like
        )";
    }

    /**
     * Sayım sorguları için prepare API uyumlu LIKE koşulu.
     *
     * @return array{0: string, 1: list<mixed>}
     */
    private function searchWherePrepared($search): array {
        $search = is_string($search) ? trim($search) : '';
        if ($search === '') {
            return ['', []];
        }
        $search = preg_replace('/\s+/', ' ', $search);
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
        $like = '%' . $escaped . '%';

        return [" AND (
            h.isim LIKE ?
            OR h.soyisim LIKE ?
            OR CONCAT_WS(' ', TRIM(h.isim), TRIM(h.soyisim)) LIKE ?
            OR CONCAT_WS(' ', TRIM(h.soyisim), TRIM(h.isim)) LIKE ?
            OR h.tckimlik LIKE ?
            OR a1.adi LIKE ?
        )", array_fill(0, 6, $like)];
    }

    /**
     * @return list<object>
     */
    private function fetchPatientListPage(
        string $where,
        string $ordering,
        int $limit,
        int $offset,
        bool $fullStats = true,
        bool $planPendingOnly = false,
        bool $useWaitingTenantScope = false
    ): array {
        if ($useWaitingTenantScope) {
            $waitingScopeParams = null;
            $where .= $this->waitingTenantScopeSql($waitingScopeParams);
        } else {
            $where = TenantSqlHelper::appendToWhere($where, 'h');
        }
        return PatientListSqlHelper::fetchPage(
            $this->db,
            $where,
            PatientListSqlHelper::addressJoinDefault(),
            'h.*, a1.adi AS ilce_adi, a2.adi AS mahalle_adi, a3.adi AS sokak_adi, a4.adi AS kapino',
            $ordering,
            $limit,
            $offset,
            $fullStats,
            $planPendingOnly
        );
    }

    private function nakilTableReady(): bool
    {
        return HastaNakil::tableExists();
    }

    /** Bekleyen liste: ön kayıt (-3) veya hedef kuruma gelen bekleyen nakil. */
    private function waitingStatusSql(): string
    {
        if (!$this->nakilTableReady()) {
            return "h.pasif = '-3'";
        }

        return "(h.pasif = '-3' OR EXISTS (
            SELECT 1 FROM #__hasta_nakil n
            WHERE n.kaynak_hasta_id = h.id
              AND n.durum = 'beklemede'
              AND n.tip IN ('kurum_ici', 'geri_nakil')
        ))";
    }

  /**
   * @param list<mixed>|null $params prepare API için; null ise kurum id gömülü
   */
    private function waitingTenantScopeSql(?array &$params): string
    {
        $kid = TenantContext::filterKurumId();
        if ($kid === null) {
            return '';
        }

        if (!$this->nakilTableReady()) {
            if ($params !== null) {
                $params[] = $kid;

                return ' AND h.kurum_id = ?';
            }

            return ' AND h.kurum_id = ' . (int) $kid;
        }

        if ($params !== null) {
            $params[] = $kid;
            $params[] = $kid;

            return " AND ((h.kurum_id = ? AND h.pasif = '-3') OR EXISTS (
                SELECT 1 FROM #__hasta_nakil n
                WHERE n.kaynak_hasta_id = h.id
                  AND n.durum = 'beklemede'
                  AND n.hedef_kurum_id = ?
                  AND n.tip IN ('kurum_ici', 'geri_nakil')
            ))";
        }

        $kidInt = (int) $kid;

        return " AND ((h.kurum_id = {$kidInt} AND h.pasif = '-3') OR EXISTS (
            SELECT 1 FROM #__hasta_nakil n
            WHERE n.kaynak_hasta_id = h.id
              AND n.durum = 'beklemede'
              AND n.hedef_kurum_id = {$kidInt}
              AND n.tip IN ('kurum_ici', 'geri_nakil')
        ))";
    }

    private function scopedWhere(string $where): string
    {
        return TenantSqlHelper::appendToWhere($where, 'h');
    }

    /** İzlem/pizlem alt sorgusu — süper yönetici hariç hasta kurumu ile eşleşir. */
    private function izSubK(string $alias = ''): string
    {
        return TenantSqlHelper::izMatchesPatientKurumSql($alias);
    }

    //aktif hastalar
    public function countAllActive($search = '') {
    $where = "WHERE h.pasif = 0";
    $params = [];
    $sql = "SELECT COUNT(h.id) FROM #__hastalar as h 
            LEFT JOIN #__adrestablosu as a1 ON h.ilce = a1.id";
    
    [$searchSql, $searchParams] = $this->searchWherePrepared($search);
    $where .= $searchSql;
    $params = array_merge($params, $searchParams);

    return $this->db->loadResultPrepared($sql . " " . $this->scopedWhere($where), $params);
}

    public function getAllActive($limit = 20, $offset = 0, $ordering = 'h.isim ASC', $search = '') {
        $where = "WHERE h.pasif = '0'";
        if (!empty($search)) {
            $where .= $this->searchWhere($search);
        }

        return $this->fetchPatientListPage($where, $ordering, $limit, $offset, true, false);
    }
    /**
     * Pasif liste sayım filtreleri (neden + pasif tarihi aralığı).
     *
     * @return array{0: string, 1: list<mixed>}
     */
    private function passiveDateReasonFilterPrepared(string $reason, string $startDate, string $endDate): array
    {
        $extra = '';
        $params = [];
        if ($reason !== '') {
            $extra .= ' AND h.pasifnedeni = ?';
            $params[] = $reason;
        }
        if ($startDate !== '' || $endDate !== '') {
            if ($startDate !== '' && $endDate === '') {
                $sDate = date('Y-m-d', strtotime(str_replace('.', '-', $startDate)));
                $extra .= ' AND h.pasiftarihi >= ?';
                $params[] = $sDate;
            } elseif ($startDate === '' && $endDate !== '') {
                $eDate = date('Y-m-d', strtotime(str_replace('.', '-', $endDate)));
                $extra .= ' AND h.pasiftarihi <= ?';
                $params[] = $eDate;
            } else {
                $sDate = date('Y-m-d', strtotime(str_replace('.', '-', $startDate)));
                $eDate = date('Y-m-d', strtotime(str_replace('.', '-', $endDate)));
                $extra .= ' AND h.pasiftarihi BETWEEN ? AND ?';
                $params[] = $sDate;
                $params[] = $eDate;
            }
        }

        return [$extra, $params];
    }

    //pasif hastaları getir
    public function countAllPassive($search = '', $reason = '', $startDate = '', $endDate = '') {
    $where = "WHERE h.pasif = 1";
    $params = [];
    $sql = "SELECT COUNT(h.id) FROM #__hastalar as h 
            LEFT JOIN #__adrestablosu as a1 ON h.ilce = a1.id";
    
    [$searchSql, $searchParams] = $this->searchWherePrepared($search);
    $where .= $searchSql;
    $params = array_merge($params, $searchParams);

    [$filterSql, $filterParams] = $this->passiveDateReasonFilterPrepared(
        is_string($reason) ? trim($reason) : '',
        is_string($startDate) ? trim($startDate) : '',
        is_string($endDate) ? trim($endDate) : ''
    );
    $where .= $filterSql;
    $params = array_merge($params, $filterParams);

    return $this->db->loadResultPrepared($sql . " " . $this->scopedWhere($where), $params);
}

    public function getAllPassive($limit = 20, $offset = 0, $ordering = 'h.isim ASC', $search = '', $reason = '', $startDate = '', $endDate = '') {
    $where = "WHERE h.pasif = '1'";
    if (!empty($search)) {
        $where .= $this->searchWhere($search);
    }
    
    if (!empty($reason)) {
        $where .= " AND h.pasifnedeni = " . $this->db->quote($reason);
    }
    
    // Tarih dönüşüm fonksiyonunu yardımcı bir değişkenle yönetelim
    if (!empty($startDate) || !empty($endDate)) {
    
    // Sadece başlangıç tarihi varsa
    if (!empty($startDate) && empty($endDate)) {
        $sDate = date('Y-m-d', strtotime(str_replace('.', '-', $startDate)));
        $where .= " AND h.pasiftarihi >= " . $this->db->quote($sDate);
    } 
    // Sadece bitiş tarihi varsa
    elseif (empty($startDate) && !empty($endDate)) {
        $eDate = date('Y-m-d', strtotime(str_replace('.', '-', $endDate)));
        $where .= " AND h.pasiftarihi <= " . $this->db->quote($eDate);
    } 
    // Her ikisi de varsa (Senin senaryon)
    else {
        $sDate = date('Y-m-d', strtotime(str_replace('.', '-', $startDate)));
        $eDate = date('Y-m-d', strtotime(str_replace('.', '-', $endDate)));
        $where .= " AND h.pasiftarihi BETWEEN " . $this->db->quote($sDate) . " AND " . $this->db->quote($eDate);
    }
}

        return $this->fetchPatientListPage($where, $ordering, $limit, $offset, true, false);
    }
    //bekleyen hastalar
    public function countAllWaiting($search = '') {
        $where = 'WHERE ' . $this->waitingStatusSql();
    $params = [];
    $sql = "SELECT COUNT(h.id) FROM #__hastalar as h 
            LEFT JOIN #__adrestablosu as a1 ON h.ilce = a1.id";
    
    [$searchSql, $searchParams] = $this->searchWherePrepared($search);
    $where .= $searchSql;
    $params = array_merge($params, $searchParams);
    $where .= $this->waitingTenantScopeSql($params);

    return $this->db->loadResultPrepared($sql . ' ' . $where, $params);
    }
    
    public function getAllWaiting($limit = 20, $offset = 0, $ordering = 'h.isim ASC', $search = '') {
        $where = 'WHERE ' . $this->waitingStatusSql();
        if (!empty($search)) {
            $where .= $this->searchWhere($search);
        }

        return $this->fetchPatientListPage($where, $ordering, $limit, $offset, false, false, true);
    }
    
    //ölen hastalar
    public function countAllDied($search = '') {
    $where = "WHERE h.pasif = '-1'";
    $params = [];
    $sql = "SELECT COUNT(h.id) FROM #__hastalar as h 
            LEFT JOIN #__adrestablosu as a1 ON h.ilce = a1.id";
    
    [$searchSql, $searchParams] = $this->searchWherePrepared($search);
    $where .= $searchSql;
    $params = array_merge($params, $searchParams);

    return $this->db->loadResultPrepared($sql . " " . $this->scopedWhere($where), $params);
    }
    
    public function getAllDied($limit = 20, $offset = 0, $ordering = 'h.isim ASC', $search = '') {
     $where = "WHERE h.pasif = '-1'";
    if (!empty($search)) {
        $where .= $this->searchWhere($search);
    }

        return $this->fetchPatientListPage($where, $ordering, $limit, $offset, true, false);
    }
    //silinen hastalar
    public function countAllDeleted($search = '') {
    $where = "WHERE h.pasif = 5";
    $params = [];
    $sql = "SELECT COUNT(h.id) FROM #__hastalar as h 
            LEFT JOIN #__adrestablosu as a1 ON h.ilce = a1.id";
    
    [$searchSql, $searchParams] = $this->searchWherePrepared($search);
    $where .= $searchSql;
    $params = array_merge($params, $searchParams);

    return $this->db->loadResultPrepared($sql . " " . $this->scopedWhere($where), $params);
    }
    
    public function getAllDeleted($limit = 20, $offset = 0, $ordering = 'h.isim ASC', $search = '') {
    $where = "WHERE h.pasif = 5";
    if (!empty($search)) {
        $where .= $this->searchWhere($search);
    }

        return $this->fetchPatientListPage($where, $ordering, $limit, $offset, true, false);
    }
    //muhtemel ölenler
    public function countAllAraf($search = '') {
    $where = "WHERE h.pasif = 4";
    $params = [];
    $sql = "SELECT COUNT(h.id) FROM #__hastalar as h 
            LEFT JOIN #__adrestablosu as a1 ON h.ilce = a1.id";
    
    [$searchSql, $searchParams] = $this->searchWherePrepared($search);
    $where .= $searchSql;
    $params = array_merge($params, $searchParams);

    return $this->db->loadResultPrepared($sql . " " . $this->scopedWhere($where), $params);
    }
    
    public function getAllAraf($limit = 20, $offset = 0, $ordering = 'h.isim ASC', $search = '') {
    $where = "WHERE h.pasif = 4";
    if (!empty($search)) {
        $where .= $this->searchWhere($search);
    }

        return $this->fetchPatientListPage($where, $ordering, $limit, $offset, true, false);
    }

    /**
     * Birleşik hasta listesi için geçerli durum anahtarı.
     */
    public static function normalizeUnifiedStatus($raw) {
        $allowed = ['all', 'active', 'passive', 'waiting', 'died', 'deleted', 'araf', 'probable'];
        $s = is_string($raw) ? strtolower(trim($raw)) : 'active';
        return in_array($s, $allowed, true) ? $s : 'active';
    }

    /** Birleşik liste admin özellik filtresi (BadgeHelper rozetleri ile uyumlu). */
    public static function normalizeUnifiedFeatureFilter($raw): string
    {
        $allowed = array_merge(
            ['', 'gecici', 'notes', 'erapor'],
            \App\Helpers\PatientClinicalFlagsHelper::listBadgeFilterKeys()
        );
        $s = is_string($raw) ? strtolower(trim($raw)) : '';
        return in_array($s, $allowed, true) ? $s : '';
    }

    private function unifiedFeatureFilterCondition(string $featureKey): string
    {
        switch ($featureKey) {
            case 'gecici':
                return ' AND (CAST(h.gecici AS SIGNED) != 0)';
            case 'notes':
                return " AND (TRIM(COALESCE(h.notes, '')) != ''"
                    . " AND TRIM(h.notes) != '[]'"
                    . ' AND CHAR_LENGTH(TRIM(h.notes)) > 2)';
            case 'erapor':
                return ' AND (CAST(h.erapor AS SIGNED) != 0)';
            default:
                return \App\Helpers\PatientClinicalFlagsHelper::unifiedFeatureFilterSql($featureKey);
        }
    }

    private function pasifStatusCondition($key) {
        switch ($key) {
            case 'passive':
                return "h.pasif = '1'";
            case 'waiting':
                return $this->waitingStatusSql();
            case 'died':
                // Eski URL uyumu: died -> muhtemel ölen (-1)
                return "h.pasif = '-1'";
            case 'deleted':
                return "h.pasif = '5'";
            case 'araf':
                return "h.pasif = '4'";
            case 'probable':
                return "h.pasif = '-1'";
            case 'all':
                return "h.pasif IN ('-3', '-1', '0', '1', '4', '5')";
            case 'active':
            default:
                return "h.pasif = '0'";
        }
    }

    /**
     * Birleşik/pasif liste WHERE parçası (legacy quote — PatientListSqlHelper paramsız).
     */
    private function unifiedPassiveFiltersSql($statusKey, $reason, $startDate, $endDate): string {
        $extra = '';
        if ($statusKey !== 'passive' && $statusKey !== 'all') {
            return $extra;
        }
        if (!empty($reason)) {
            if ($statusKey === 'passive') {
                $extra .= ' AND h.pasifnedeni = ' . $this->db->quote($reason);
            } else {
                $extra .= " AND (h.pasif <> '1' OR h.pasifnedeni = " . $this->db->quote($reason) . ')';
            }
        }
        if (!empty($startDate) || !empty($endDate)) {
            if (!empty($startDate) && empty($endDate)) {
                $sDate = date('Y-m-d', strtotime(str_replace('.', '-', $startDate)));
                $cond = 'h.pasiftarihi >= ' . $this->db->quote($sDate);
            } elseif (empty($startDate) && !empty($endDate)) {
                $eDate = date('Y-m-d', strtotime(str_replace('.', '-', $endDate)));
                $cond = 'h.pasiftarihi <= ' . $this->db->quote($eDate);
            } else {
                $sDate = date('Y-m-d', strtotime(str_replace('.', '-', $startDate)));
                $eDate = date('Y-m-d', strtotime(str_replace('.', '-', $endDate)));
                $cond = 'h.pasiftarihi BETWEEN ' . $this->db->quote($sDate) . ' AND ' . $this->db->quote($eDate);
            }
            if ($statusKey === 'passive') {
                $extra .= ' AND ' . $cond;
            } else {
                $extra .= " AND (h.pasif <> '1' OR ({$cond}))";
            }
        }

        return $extra;
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function unifiedPassiveFilters($statusKey, $reason, $startDate, $endDate): array {
        $extra = '';
        $params = [];
        if ($statusKey !== 'passive' && $statusKey !== 'all') {
            return [$extra, $params];
        }
        if (!empty($reason)) {
            if ($statusKey === 'passive') {
                $extra .= ' AND h.pasifnedeni = ?';
                $params[] = $reason;
            } else {
                $extra .= " AND (h.pasif <> '1' OR h.pasifnedeni = ?)";
                $params[] = $reason;
            }
        }
        if (!empty($startDate) || !empty($endDate)) {
            if (!empty($startDate) && empty($endDate)) {
                $sDate = date('Y-m-d', strtotime(str_replace('.', '-', $startDate)));
                $cond = 'h.pasiftarihi >= ?';
                $condParams = [$sDate];
            } elseif (empty($startDate) && !empty($endDate)) {
                $eDate = date('Y-m-d', strtotime(str_replace('.', '-', $endDate)));
                $cond = 'h.pasiftarihi <= ?';
                $condParams = [$eDate];
            } else {
                $sDate = date('Y-m-d', strtotime(str_replace('.', '-', $startDate)));
                $eDate = date('Y-m-d', strtotime(str_replace('.', '-', $endDate)));
                $cond = 'h.pasiftarihi BETWEEN ? AND ?';
                $condParams = [$sDate, $eDate];
            }
            if ($statusKey === 'passive') {
                $extra .= ' AND ' . $cond;
            } else {
                $extra .= " AND (h.pasif <> '1' OR ({$cond}))";
            }
            $params = array_merge($params, $condParams);
        }

        return [$extra, $params];
    }

    public function countUnified($statusKey, $search = '', $reason = '', $startDate = '', $endDate = '', $featureKey = '') {
        $where = 'WHERE ' . $this->pasifStatusCondition($statusKey);
        $params = [];
        $sql = "SELECT COUNT(h.id) FROM #__hastalar as h 
                LEFT JOIN #__adrestablosu as a1 ON h.ilce = a1.id";
        [$searchSql, $searchParams] = $this->searchWherePrepared($search);
        $where .= $searchSql;
        $params = array_merge($params, $searchParams);
        [$filterSql, $filterParams] = $this->unifiedPassiveFilters($statusKey, $reason, $startDate, $endDate);
        $where .= $filterSql;
        $params = array_merge($params, $filterParams);
        $where .= $this->unifiedFeatureFilterCondition(self::normalizeUnifiedFeatureFilter($featureKey));

        if ($statusKey === 'waiting') {
            $where .= $this->waitingTenantScopeSql($params);

            return $this->db->loadResultPrepared($sql . ' ' . $where, $params);
        }

        return $this->db->loadResultPrepared($sql . ' ' . $this->scopedWhere($where), $params);
    }

    public function getUnified($limit = 20, $offset = 0, $ordering = 'h.isim ASC', $statusKey = 'active', $search = '', $reason = '', $startDate = '', $endDate = '', $featureKey = '') {
        $where = 'WHERE ' . $this->pasifStatusCondition($statusKey);
        if (!empty($search)) {
            $where .= $this->searchWhere($search);
        }
        $where .= $this->unifiedPassiveFiltersSql($statusKey, $reason, $startDate, $endDate);
        $where .= $this->unifiedFeatureFilterCondition(self::normalizeUnifiedFeatureFilter($featureKey));

        return $this->fetchPatientListPage(
            $where,
            $ordering,
            $limit,
            $offset,
            true,
            true,
            $statusKey === 'waiting'
        );
    }

    /**
     * TC Kimlik numarasına göre tek bir hasta nesnesi döndürür
     */
    public function findByTc($tc) {
        return $this->db->fetchObjectPrepared(
            'SELECT * FROM #__hastalar WHERE tckimlik = ?' . TenantSqlHelper::andBare(),
            [$tc]
        );
    }

    /**
     * TC ile hasta — tüm kurumlar (tenant filtresi yok; benzersizlik / AJAX kontrolü).
     */
    public function findByTcGlobal(string $tc): ?object
    {
        $tc = preg_replace('/\D+/', '', $tc);
        if (strlen($tc) !== 11) {
            return null;
        }
        $row = $this->db->fetchObjectPrepared(
            'SELECT * FROM #__hastalar WHERE tckimlik = ? LIMIT 1',
            [$tc]
        );

        return $row ?: null;
    }

    /** TC ile hasta + ilçe/mahalle adları (dashboard tam eşleşme). */
    public function findByTcWithAddress(string $tc): ?object
    {
        $tc = preg_replace('/\D+/', '', $tc);
        if (strlen($tc) !== 11) {
            return null;
        }
        $sql = 'SELECT h.*, a1.adi AS ilce_adi, a2.adi AS mahalle_adi
                FROM #__hastalar AS h
                LEFT JOIN #__adrestablosu AS a1 ON h.ilce = a1.id
                LEFT JOIN #__adrestablosu AS a2 ON h.mahalle = a2.id
                WHERE h.tckimlik = ?'
                . TenantSqlHelper::andEquals('h', 'kurum_id') . '
                LIMIT 1';

        $row = $this->db->fetchObjectPrepared($sql, [$tc]);

        return $row ?: null;
    }

    /** İlçe / mahalle tek satır (arama sonuçları). */
    public static function formatIlceMahalle(?object $row): string
    {
        if ($row === null) {
            return '';
        }
        $ilce = trim((string) ($row->ilce_adi ?? ''));
        $mahalle = trim((string) ($row->mahalle_adi ?? ''));
        if ($ilce === '' && $mahalle === '') {
            return '';
        }
        if ($ilce !== '' && $mahalle !== '') {
            return $ilce . ' / ' . $mahalle;
        }

        return $ilce !== '' ? $ilce : $mahalle;
    }

    /**
     * Randevu takvimi hasta arama JSON satırları.
     *
     * @param array<int, object> $list
     * @return array<int, array<string, string>>
     */
    public static function mapRandevuPatientSearchJson(array $list): array
    {
        $out = [];
        foreach ($list as $row) {
            $tc = (string) ($row->tckimlik ?? '');
            $isimFull = trim((string) ($row->isim ?? '') . ' ' . (string) ($row->soyisim ?? ''));
            $adres = self::formatIlceMahalle($row);
            $out[] = [
                'tckimlik' => $tc,
                'isim' => trim((string) ($row->isim ?? '')),
                'soyisim' => trim((string) ($row->soyisim ?? '')),
                'adres' => $adres,
                'label' => $isimFull !== ''
                    ? ($isimFull . ' — ' . \App\Helpers\ValidationHelper::formatTc($tc))
                    : \App\Helpers\ValidationHelper::formatTc($tc),
            ];
        }

        return $out;
    }

    /**
     * TC prefix ile hızlı hasta arama (dashboard ajax autocomplete).
     *
     * @return array<int, object>
     */
    public function searchByTcPrefix(string $prefix, int $limit = 8): array {
        $prefix = preg_replace('/\D+/', '', $prefix);
        if ($prefix === '') {
            return [];
        }
        $query = "SELECT id, tckimlik, isim, soyisim, pasif, pasifnedeni
                  FROM #__hastalar
                  WHERE tckimlik LIKE ?"
                  . TenantSqlHelper::andBare('kurum_id') . "
                  ORDER BY tckimlik ASC
                  LIMIT " . (int) $limit;

        return $this->db->fetchObjectListPrepared($query, [$prefix . '%']) ?: [];
    }

    /**
     * Dashboard — TC veya ad/soyad ile hasta arama (tüm dosya durumları).
     *
     * @return array<int, object>
     */
    public function searchForDashboardLookup(string $q, int $limit = 10): array
    {
        $q = trim($q);
        $lim = max(1, min(30, (int) $limit));
        if (strlen($q) < 2) {
            return [];
        }
        $digits = preg_replace('/\D+/', '', $q);
        $addrSelect = 'h.id, h.tckimlik, h.isim, h.soyisim, h.pasif, h.pasifnedeni, a1.adi AS ilce_adi, a2.adi AS mahalle_adi';
        $addrFrom = 'FROM #__hastalar AS h
                    LEFT JOIN #__adrestablosu AS a1 ON h.ilce = a1.id
                    LEFT JOIN #__adrestablosu AS a2 ON h.mahalle = a2.id';
        $kurumFilter = TenantSqlHelper::andEquals('h', 'kurum_id');
        if (strlen($digits) >= 2) {
            $sql = "SELECT {$addrSelect}
                    {$addrFrom}
                    WHERE h.tckimlik LIKE ?{$kurumFilter}
                    ORDER BY h.tckimlik ASC
                    LIMIT {$lim}";
            $params = [$digits . '%'];
        } else {
            $esc = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
            $like = '%' . $esc . '%';
            $sql = "SELECT {$addrSelect}
                    {$addrFrom}
                    WHERE (
                        CONCAT(TRIM(COALESCE(h.isim, '')), ' ', TRIM(COALESCE(h.soyisim, ''))) LIKE ?
                        OR TRIM(COALESCE(h.isim, '')) LIKE ?
                        OR TRIM(COALESCE(h.soyisim, '')) LIKE ?
                      ){$kurumFilter}
                    ORDER BY h.isim ASC, h.soyisim ASC, h.tckimlik ASC
                    LIMIT {$lim}";
            $params = [$like, $like, $like];
        }

        $list = $this->db->fetchObjectListPrepared($sql, $params);

        return is_array($list) ? $list : [];
    }

    /**
     * Branş randevu takvimi — hasta arama (yalnız aktif pasif=0): TC rakamları veya ad/soyad.
     *
     * @return array<int, object>
     */
    public function searchForBransRandevu(string $q, int $limit = 12): array
    {
        $q = trim($q);
        $lim = max(1, min(30, (int) $limit));
        if (strlen($q) < 2) {
            return [];
        }
        $digits = preg_replace('/\D+/', '', $q);
        $addrSelect = 'h.id, h.tckimlik, h.isim, h.soyisim, a1.adi AS ilce_adi, a2.adi AS mahalle_adi';
        $addrFrom = 'FROM #__hastalar AS h
                    LEFT JOIN #__adrestablosu AS a1 ON h.ilce = a1.id
                    LEFT JOIN #__adrestablosu AS a2 ON h.mahalle = a2.id';
        $kurumScope = TenantSqlHelper::andEquals('h', 'kurum_id');
        if (strlen($digits) >= 2) {
            $sql = "SELECT {$addrSelect}
                    {$addrFrom}
                    WHERE h.pasif = '0' AND h.tckimlik LIKE ?{$kurumScope}
                    ORDER BY h.tckimlik ASC
                    LIMIT {$lim}";
            $params = [$digits . '%'];
        } else {
            $esc = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
            $like = '%' . $esc . '%';
            $sql = "SELECT {$addrSelect}
                    {$addrFrom}
                    WHERE h.pasif = '0'{$kurumScope}
                      AND (
                        CONCAT(TRIM(COALESCE(h.isim, '')), ' ', TRIM(COALESCE(h.soyisim, ''))) LIKE ?
                        OR TRIM(COALESCE(h.isim, '')) LIKE ?
                        OR TRIM(COALESCE(h.soyisim, '')) LIKE ?
                      )
                    ORDER BY h.isim ASC, h.soyisim ASC, h.tckimlik ASC
                    LIMIT {$lim}";
            $params = [$like, $like, $like];
        }

        $list = $this->db->fetchObjectListPrepared($sql, $params);

        return is_array($list) ? $list : [];
    }

    /**
     * ID'ye göre tek bir hasta nesnesi döndürür
     */
    public function getById($id) {
        $izK = $this->izSubK();
        $iK = $this->izSubK('i');
        $i2K = $this->izSubK('i2');
        $i3K = $this->izSubK('i3');
        $pK = $this->izSubK('p');
        $p2K = $this->izSubK('p2');
        $p3K = $this->izSubK('p3');

        $query = "SELECT h.*,
                         (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 1{$izK}) as izlemsayisi,
                         (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 0{$izK}) as yizlemsayisi,
                         (SELECT COUNT(id) FROM #__pizlemler WHERE hastatckimlik = h.tckimlik AND COALESCE(durum, 0) = 0{$izK}) as totalplanli,
                         (SELECT i.izlemtarihi FROM #__izlemler i WHERE i.hastatckimlik = h.tckimlik AND i.yapildimi = 1{$iK} ORDER BY i.izlemtarihi DESC, i.id DESC LIMIT 1) as son_yapilan_tarih,
                         (SELECT GROUP_CONCAT(isl.islemadi ORDER BY isl.id SEPARATOR ', ')
                            FROM #__islemler isl
                           WHERE FIND_IN_SET(
                                isl.id,
                                REPLACE(COALESCE((
                                    SELECT i2.yapilan
                                      FROM #__izlemler i2
                                     WHERE i2.hastatckimlik = h.tckimlik AND i2.yapildimi = 1{$i2K}
                                     ORDER BY i2.izlemtarihi DESC, i2.id DESC
                                     LIMIT 1
                                ), ''), ' ', '')
                           )) as son_yapilan_islemler,
                         (SELECT GROUP_CONCAT(u.name ORDER BY u.id SEPARATOR ', ')
                            FROM #__users u
                           WHERE FIND_IN_SET(
                                u.id,
                                REPLACE(CAST(COALESCE((
                                    SELECT i3.izlemiyapan
                                      FROM #__izlemler i3
                                     WHERE i3.hastatckimlik = h.tckimlik AND i3.yapildimi = 1{$i3K}
                                     ORDER BY i3.izlemtarihi DESC, i3.id DESC
                                     LIMIT 1
                                ), '') AS CHAR), ' ', '')
                           )) as son_yapilan_yapanlar,
                         (SELECT i.izlemtarihi FROM #__izlemler i WHERE i.hastatckimlik = h.tckimlik AND i.yapildimi = 0{$iK} ORDER BY i.izlemtarihi DESC, i.id DESC LIMIT 1) as son_yapilmayan_tarih,
                         (SELECT GROUP_CONCAT(isl.islemadi ORDER BY isl.id SEPARATOR ', ')
                            FROM #__islemler isl
                           WHERE FIND_IN_SET(
                                isl.id,
                                REPLACE(COALESCE((
                                    SELECT i2.yapilan
                                      FROM #__izlemler i2
                                     WHERE i2.hastatckimlik = h.tckimlik AND i2.yapildimi = 0{$i2K}
                                     ORDER BY i2.izlemtarihi DESC, i2.id DESC
                                     LIMIT 1
                                ), ''), ' ', '')
                           )) as son_yapilmayan_islemler,
                         (SELECT GROUP_CONCAT(u.name ORDER BY u.id SEPARATOR ', ')
                            FROM #__users u
                           WHERE FIND_IN_SET(
                                u.id,
                                REPLACE(CAST(COALESCE((
                                    SELECT i3.izlemiyapan
                                      FROM #__izlemler i3
                                     WHERE i3.hastatckimlik = h.tckimlik AND i3.yapildimi = 0{$i3K}
                                     ORDER BY i3.izlemtarihi DESC, i3.id DESC
                                     LIMIT 1
                                ), '') AS CHAR), ' ', '')
                           )) as son_yapilmayan_yapanlar,
                         (SELECT p.planlanantarih FROM #__pizlemler p WHERE p.hastatckimlik = h.tckimlik AND COALESCE(p.durum, 0) = 0{$pK} ORDER BY p.planlanantarih ASC, p.id ASC LIMIT 1) as son_planli_tarih,
                         (SELECT GROUP_CONCAT(isl.islemadi ORDER BY isl.id SEPARATOR ', ')
                            FROM #__islemler isl
                           WHERE FIND_IN_SET(
                                isl.id,
                                REPLACE(COALESCE((
                                    SELECT p2.yapilacak
                                      FROM #__pizlemler p2
                                     WHERE p2.hastatckimlik = h.tckimlik AND COALESCE(p2.durum, 0) = 0{$p2K}
                                     ORDER BY p2.planlanantarih ASC, p2.id ASC
                                     LIMIT 1
                                ), ''), ' ', '')
                           )) as son_planli_islemler,
                         (SELECT GROUP_CONCAT(u.name ORDER BY u.id SEPARATOR ', ')
                            FROM #__users u
                           WHERE FIND_IN_SET(
                                u.id,
                                REPLACE(CAST(COALESCE((
                                    SELECT p3.planiyapan
                                      FROM #__pizlemler p3
                                     WHERE p3.hastatckimlik = h.tckimlik AND COALESCE(p3.durum, 0) = 0{$p3K}
                                     ORDER BY p3.planlanantarih ASC, p3.id ASC
                                     LIMIT 1
                                ), '') AS CHAR), ' ', '')
                           )) as son_planli_planlayanlar,
                         a1.adi AS ilce_adi,
                         a2.adi AS mahalle_adi,
                         a3.adi AS sokak_adi,
                         a4.adi AS kapino_adi
                  FROM #__hastalar AS h
                  LEFT JOIN #__adrestablosu AS a1 ON a1.id = h.ilce
                  LEFT JOIN #__adrestablosu AS a2 ON a2.id = h.mahalle
                  LEFT JOIN #__adrestablosu AS a3 ON a3.id = h.sokak
                  LEFT JOIN #__adrestablosu AS a4 ON a4.id = h.kapino
                  WHERE h.id = ?";

        return $this->db->fetchObjectPrepared($query, [(int) $id]);
    }
    
    // ÖNERİ: Toplam Barthel Puanını ve Durumunu Hesapla
    public function getBarthelScore() {
        $fields = ['barbeslenme', 'barbanyo', 'barbakim', 'bargiyinme', 'barbarsak', 'barmesane', 'bartuvalet', 'bartransfer', 'barmobilite', 'barmerdiven'];
        $total = 0;
        foreach ($fields as $f) {
            $total += (int) ($this->$f ?? 0);
        }
        
        $status = "Bağımsız";
        if($total <= 20) $status = "Tam Bağımlı";
        elseif($total <= 60) $status = "Ağır Bağımlı";
        elseif($total <= 90) $status = "Orta Bağımlı";
        
        return ['score' => $total, 'status' => $status];
    }
    
    //Hastayı pasife alma
    public function setPassive($reason, $type, $date = null) {
        $this->set('pasifnedeni', (int) $reason);
        $this->set('pasif', $type);
        $this->set('pasiftarihi', $date ?? date('Y-m-d'));
        return $this->store();
    }
    /**
     * Raporun süresinin dolmak üzere olup olmadığını kontrol eder.
     * * @param string $type Rapor türü: 'mama', 'bez' veya 'sonda'
     * @param int $days Kaç gün kala uyarı verileceği (Varsayılan: 15)
     * @return bool
     */
    public function isReportExpiring($type = 'mama', $days = 15) {
        // 1. Tip bazlı özellik ismini belirleyelim
        if ($type == 'mama') {
            $prop = 'mamaraporbitis';
        } elseif ($type == 'bez') {
            $prop = 'bezraporbitis';
        } elseif ($type == 'sonda') {
            $prop = 'sondatarihi';
        } else {
            return false; // Bilinmeyen bir tip gelirse false dön
        }

        // 2. Veritabanındaki tarih boş mu kontrol edelim
        if (empty($this->$prop)) return false;

        // 3. Bitiş tarihini hesaplayalım
        if ($type == 'sonda') {
            // Sonda için: sondatarihi + 30 gün
            $expiryDate = strtotime($this->$prop . " +30 days");
        } else {
            // Mama ve Bez için: Doğrudan ilgili sütun tarihi
            $expiryDate = strtotime($this->$prop);
        }

        // 4. Uyarı limitini hesaplayalım (Bugün + $days)
        $warningLimit = strtotime("+$days days");

        // Bitiş tarihi uyarı limitinin içindeyse true döner
        return $expiryDate <= $warningLimit;
    }

    /**
     * TC Kimlik numarası geçerlilik kontrolü (Algoritmik)
     */
    public function validateTc($tc) {
    // Parametre olarak gelen $tc'yi kullanıyoruz
    $tc = (string)$tc;
    
    // Temel kontroller: 11 hane, sadece rakam, ilk hane 0 olamaz
    if (strlen($tc) != 11 || !ctype_digit($tc) || $tc[0] == '0') return false;
    
    $digits = str_split($tc);
    $oddSum = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
    $evenSum = $digits[1] + $digits[3] + $digits[5] + $digits[7];
    
    // Negatif sonuç ihtimaline karşı +10 ekleyip tekrar mod alıyoruz
    $digit10 = (($oddSum * 7) - $evenSum) % 10;
    if ($digit10 < 0) $digit10 += 10; 
    
    $digit11 = (array_sum(array_slice($digits, 0, 10))) % 10;

    return ($digits[9] == $digit10 && $digits[10] == $digit11);
}
    
    /**
     * Virgüllü hastalık id listesini veya id dizisini pozitif tekil tamsayı dizisine çevirir.
     *
     * @return list<int>
     */
    public static function parseHastalikCsvToIntIds($csv): array {
        if (is_array($csv)) {
            $out = [];
            foreach ($csv as $p) {
                $n = (int) trim((string) $p);
                if ($n > 0) {
                    $out[$n] = $n;
                }
            }

            return array_values($out);
        }

        $csv = trim((string) $csv);
        if ($csv === '') {
            return [];
        }
        $out = [];
        foreach (preg_split('/\s*,\s*/', $csv) as $p) {
            $n = (int) trim((string) $p);
            if ($n > 0) {
                $out[$n] = $n;
            }
        }

        return array_values($out);
    }

    /**
     * İlaç raporu ekranı için hasta `#__hastalar.hastaliklar` CSV listesini sayısal id dizisine çevirir.
     *
     * @return list<int>
     */
    public static function mergedHastalikIdsForIlacRapor(object $patient): array {
        $ids = self::parseHastalikCsvToIntIds($patient->hastaliklar ?? null);
        sort($ids);

        return $ids;
    }

    /**
     * Hastanın hastalık ID'lerini dizi (array) olarak döndürür
     */
    public function getDiseaseArray() {
        return !empty($this->hastaliklar) ? explode(',', $this->hastaliklar) : [];
    }

    /**
     * Hastaya ait hastalık isimlerini veritabanından çekerek döndürür
     */
    public function getDiseaseNames() {
        if (empty($this->hastaliklar)) return 'Belirtilmemiş';

        $ids = array_filter(array_map('intval', explode(',', (string) $this->hastaliklar)));
        if (empty($ids)) {
            return 'Belirtilmemiş';
        }
        [$inSql, $inParams] = $this->db->whereInClause($ids);
        $query = "SELECT GROUP_CONCAT(hastalikadi SEPARATOR ', ') as isimler 
                  FROM #__hastaliklar 
                  WHERE id IN ($inSql)";
        
        return $this->db->loadResultPrepared($query, $inParams);
    }
    
    /**
    * Ölüm kontrolünde kullanılan fonksiyon
    */
    public function died($tc) {
        $sql = "SELECT 
                id, pasif, isim, soyisim, anneAdi, babaAdi 
                FROM #__hastalar 
                WHERE tckimlik = ?";
                
        return $this->db->fetchObjectPrepared($sql, [(string) $tc]);
    }
    
    // 20'şerli paket çekme (Sıralama tckimlik ASC)
    public function getPatientsForScan($offset = 0, $limit = 20, $scope = 'active') {
        $scope = is_string($scope) ? strtolower(trim($scope)) : 'active';
        if ($scope === 'waiting') {
            $where = "h.pasif = '-3'";
        } elseif ($scope === 'both') {
            $where = "h.pasif IN ('0', '-3')";
        } else {
            $where = "h.pasif = '0'";
        }
        $query = "SELECT h.id, h.pasif, h.cinsiyet, h.tckimlik, h.isim, h.soyisim, h.anneAdi, h.babaAdi, h.kayittarihi,
                         a1.adi AS ilce_adi, a2.adi AS mahalle_adi,
                         (SELECT izlemtarihi FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 1 ORDER BY izlemtarihi DESC LIMIT 1) AS sonizlemtarihi
                  FROM #__hastalar h
                  LEFT JOIN #__adrestablosu a1 ON h.ilce = a1.id
                  LEFT JOIN #__adrestablosu a2 ON h.mahalle = a2.id
                  WHERE {$where}" . TenantSqlHelper::andEquals('h', 'kurum_id') . "
                  ORDER BY h.tckimlik ASC 
                  LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        return $this->db->fetchObjectListPrepared($query);
    }
    
    public function countPatientsForScan($scope = 'active') {
        $scope = is_string($scope) ? strtolower(trim($scope)) : 'active';
        if ($scope === 'waiting') {
            $where = "h.pasif = '-3'";
        } elseif ($scope === 'both') {
            $where = "h.pasif IN ('0', '-3')";
        } else {
            $where = "h.pasif = '0'";
        }
        $query = "SELECT COUNT(h.id) FROM #__hastalar h WHERE {$where}" . TenantSqlHelper::andEquals('h', 'kurum_id');
        
        return $this->db->loadResultPrepared($query);
    }
    
    /**
     * Periyodik pansuman hastaları (koordinatı olanlar).
     *
     * @param int|string $today_day Hafta günü 0–6 (date('w'); Pazar=0), pgunleri ile eşlenir.
     * @param string|null $excludeIfIzlemOnDate Y-m-d; bu tarihte herhangi bir izlem kaydı varsa hasta dahil edilmez
     *        (eski site/takvim: o gün izlem girilmişse takvim ve günlük plandan pansuman düşer).
     */
    public function getPansumanlar($today_day, ?string $excludeIfIzlemOnDate = null) {
        $day = (int) $today_day;
        $last_date_sql = "(SELECT izlemtarihi FROM #__izlemler WHERE hastatckimlik = h.tckimlik ORDER BY izlemtarihi DESC LIMIT 1) as son_izlem_tarihi";

        $last_islem_sql = "(SELECT GROUP_CONCAT(i2.islemadi SEPARATOR ', ')
                        FROM #__izlemler iz2
                        INNER JOIN #__islemler i2 ON FIND_IN_SET(i2.id, iz2.yapilan) > 0
                        WHERE iz2.id = (
                            SELECT iz3.id
                            FROM #__izlemler iz3
                            WHERE iz3.hastatckimlik = h.tckimlik
                            ORDER BY iz3.izlemtarihi DESC, iz3.id DESC
                            LIMIT 1
                        )) as son_izlem_yapilanlar";

        $noIzlemSql = '';
        $pansumanParams = [];
        if ($excludeIfIzlemOnDate !== null && $excludeIfIzlemOnDate !== '') {
            $noIzlemSql = " AND NOT EXISTS (
                SELECT 1 FROM #__izlemler iz
                WHERE iz.hastatckimlik = h.tckimlik AND iz.izlemtarihi = ?
            )";
            $pansumanParams[] = $excludeIfIzlemOnDate;
        }

        $eff = Address::effectiveCoordsExpr('h', 'k');
        $effWhere = Address::effectiveCoordsWhereClause('h', 'k');
        $kapinoJoin = Address::kapinoJoinSql('h', 'k');
        $mpJoin = MahallePlan::joinSqlForHasta('m', 'mp', 'h');
        $bolgeSel = MahallePlan::bolgeSelectSql('mp', 'bolge_id');
        $q = "SELECT h.id, h.isim, h.soyisim, h.tckimlik, {$eff} AS coords, h.mahalle as mahalle_id, 
                 m.adi as mahalle_adi, {$bolgeSel}, h.pzaman as zaman_kodu,
                 $last_date_sql, $last_islem_sql
          FROM #__hastalar AS h 
          LEFT JOIN #__adrestablosu AS m ON h.mahalle = m.id 
          {$mpJoin}
          {$kapinoJoin}
          WHERE h.pansuman = 1 AND FIND_IN_SET($day, h.pgunleri) > 0 
          AND h.pasif = '0' AND {$effWhere}
          $noIzlemSql";

        return $this->db->fetchObjectListPrepared($q, $pansumanParams);
    }
    
    public function getIlkMuayeneler($date) {
        $eff = Address::effectiveCoordsExpr('h', 'k');
        $effWhere = Address::effectiveCoordsWhereClause('h', 'k');
        $kapinoJoin = Address::kapinoJoinSql('h', 'k');
        $mpJoin = MahallePlan::joinSqlForHasta('m', 'mp', 'h');
        $bolgeSel = MahallePlan::bolgeSelectSql('mp', 'bolge_id');
        $q = "SELECT h.id, h.isim, h.soyisim, h.tckimlik, {$eff} AS coords, h.mahalle as mahalle_id, 
                     m.adi as mahalle_adi, {$bolgeSel}, h.zaman as zaman_kodu 
              FROM #__hastalar AS h 
              LEFT JOIN #__adrestablosu AS m ON h.mahalle = m.id 
              {$mpJoin}
              {$kapinoJoin}
              WHERE h.pasif = '-3' AND h.randevutarihi = ? 
              AND {$effWhere}";

        return $this->db->fetchObjectListPrepared($q, [(string) $date]);
    }

    /**
     * EK-3 PDF için adres satırı adları ve güvence adı ile hasta.
     */
    public function loadForEk3(int $patientId): ?object {
        $patientId = (int) $patientId;
        if ($patientId < 1) {
            return null;
        }
        $sql = 'SELECT h.*, i.adi AS ilceadi, m.adi AS mahalleadi, s.adi AS sokakadi, k.adi AS kapino_adi,
                (SELECT guvenceadi FROM #__guvence WHERE id = h.guvence LIMIT 1) AS guvenceadi
                FROM #__hastalar AS h
                LEFT JOIN #__adrestablosu AS i ON i.id = h.ilce
                LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle
                LEFT JOIN #__adrestablosu AS s ON s.id = h.sokak
                LEFT JOIN #__adrestablosu AS k ON k.id = h.kapino
                WHERE h.id = ?';

        return $this->db->fetchObjectPrepared($sql, [$patientId]);
    }

    /**
     * @param string|null $csv hastaliklar sütunu (virgüllü id)
     */
    public function hastalikEtiketleriFromCsv(?string $csv): string {
        $csv = trim((string) $csv);
        if ($csv === '') {
            return '';
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', preg_split('/\s*,\s*/', $csv, -1, PREG_SPLIT_NO_EMPTY)))));
        if ($ids === []) {
            return '';
        }
        [$inSql, $inParams] = $this->db->whereInClause($ids);
        $sql = 'SELECT CONCAT(COALESCE(icd, \'\'), \'.\', hastalikadi) FROM #__hastaliklar WHERE id IN (' . $inSql . ') ORDER BY id ASC';
        $rows = $this->db->fetchColumnListPrepared($sql, $inParams, 0);
        return $rows ? implode(', ', $rows) : '';
    }

    /**
     * Yönetim hasta haritası: aktif kayıtlar, dolu coords, son izlem tarihi (subquery).
     *
     * @return array<int, object>
     */
    public function getActivePatientsWithCoordsForMap(): array {
        $eff = Address::effectiveCoordsExpr('h', 'a4');
        $effWhere = Address::effectiveCoordsWhereClause('h', 'a4');
        $kapinoJoin = "LEFT JOIN #__adrestablosu AS a4 ON h.kapino = a4.id AND a4.tip = 'kapino'";

        $sql = "SELECT h.id, h.tckimlik, h.isim, h.soyisim, h.cinsiyet, h.ceptel1, {$eff} AS coords,
            a1.adi AS ilce_adi, a2.adi AS mahalle_adi, a3.adi AS sokak_adi, a4.adi AS kapino
            FROM #__hastalar h
            LEFT JOIN #__adrestablosu AS a1 ON h.ilce = a1.id
            LEFT JOIN #__adrestablosu AS a2 ON h.mahalle = a2.id
            LEFT JOIN #__adrestablosu AS a3 ON h.sokak = a3.id
            {$kapinoJoin}
            WHERE h.pasif = '0'
            AND {$effWhere}
            ORDER BY h.isim ASC, h.soyisim ASC";
        $list = $this->db->fetchObjectListPrepared($sql);
        if (!is_array($list) || $list === []) {
            return [];
        }

        $tcs = [];
        foreach ($list as $row) {
            $tc = trim((string) ($row->tckimlik ?? ''));
            if ($tc !== '') {
                $tcs[$tc] = true;
            }
        }
        if ($tcs === []) {
            return $list;
        }

        [$inSql, $inParams] = $this->db->whereInClause(array_keys($tcs));
        $izRows = $this->db->fetchObjectListPrepared(
            "SELECT hastatckimlik, MAX(izlemtarihi) AS sonizlem
             FROM #__izlemler
             WHERE hastatckimlik IN ({$inSql})
             GROUP BY hastatckimlik",
            $inParams
        ) ?: [];
        $sonByTc = [];
        foreach ($izRows as $iz) {
            $sonByTc[(string) ($iz->hastatckimlik ?? '')] = $iz->sonizlem ?? null;
        }
        foreach ($list as $row) {
            $row->sonizlem = $sonByTc[trim((string) ($row->tckimlik ?? ''))] ?? null;
        }

        return $list;
    }

    /**
     * Girişsiz TC sorgusu: tüm dosya durumları (aktif, pasif, bekleyen, araf, …) — tek satır.
     */
    public function findByTckimlikForPublicLookup(string $tckimlik, ?int $kurumId = null): ?object {
        $tckimlik = preg_replace('/\D/', '', $tckimlik);
        if (strlen($tckimlik) !== 11) {
            return null;
        }
        $sql = 'SELECT isim, soyisim, kayittarihi, pasif, pasiftarihi, pasifnedeni FROM #__hastalar'
            . ' WHERE tckimlik = ?';
        $params = [$tckimlik];
        if ($kurumId !== null && $kurumId > 0) {
            $sql .= ' AND kurum_id = ?';
            $params[] = (int) $kurumId;
        }
        $sql .= ' LIMIT 1';
        $row = $this->db->fetchObjectPrepared($sql, $params);

        return $row ?: null;
    }

    /**
     * @deprecated findByTckimlikForPublicLookup kullanın (artık yalnızca aktif filtrelemez).
     */
    public function findActiveByTckimlikForPublicLookup(string $tckimlik): ?object {
        return $this->findByTckimlikForPublicLookup($tckimlik);
    }

    /** pasif = 0 → aktif hasta (izlem girişi / planlama açık). */
    public static function isAktif(mixed $pasif): bool {
        return (string) $pasif === '0';
    }

    /** pasif = 1 → takipten çıkarılmış (dosya kapalı) hasta. */
    /**
     * MERNİS vefat kontrolü; vefat tespitinde hasta kaydını günceller.
     *
     * @return array{oldu: int, olumTarihi: ?string, mesaj: string, skipped: bool}
     */
    public function mernisVefatKontrolVeKaydet(string $tc): array
    {
        return \App\Helpers\PatientVefatCheckHelper::checkAndApplyByTc($tc);
    }

    public static function isPasifKapali(mixed $pasif): bool {
        return (string) $pasif === '1';
    }
}