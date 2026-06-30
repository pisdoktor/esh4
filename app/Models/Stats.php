<?php
namespace App\Models;

use App\Helpers\AgeBandHelper;
use App\Helpers\BmiHelper;
use App\Helpers\CatalogScopeSqlHelper;
use App\Helpers\IslemIdSettings;
use App\Helpers\KonsBransIstekHelper;
use App\Helpers\PatientClinicalFlagsHelper;
use App\Helpers\PatientListSqlHelper;
use App\Helpers\StatsQueryCache;
use App\Helpers\CatalogStoreHelper;
use App\Helpers\TenantContext;
use App\Helpers\TenantSqlHelper;
use App\Helpers\ZamanDilimiHelper;

class Stats extends BaseModel {
    
    public function __construct() {
        parent::__construct('#__hastalar', 'id');
    }

    /** @param list<string> $where */
    private function mergeKurumWhere(array &$where, string $alias = 'h'): void
    {
        TenantSqlHelper::mergeParts($where, $alias);
    }

    private function statsCacheKey(string $key): string
    {
        $kid = TenantContext::filterKurumId();

        return 'k' . ($kid ?? 'all') . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $key);
    }

    /** İzlem/pizlem alt sorgusu — hasta ile aynı kurum. */
    private function izSubK(): string
    {
        return ' AND kurum_id = h.kurum_id';
    }

    /**
     * @param array{joins?:string,where:string,from?:string} $parts
     * @return array{joins?:string,where:string,from?:string}
     */
    private function filterPartsWithKurum(array $parts, string $hAlias = 'h', ?string $eAlias = null): array
    {
        $kid = TenantContext::filterKurumId();
        if ($kid === null) {
            return $parts;
        }
        $parts['where'] .= ' AND ' . $hAlias . '.kurum_id = ' . (int) $kid;
        if ($eAlias !== null && ($parts['from'] ?? '') === 'erapor') {
            $parts['where'] .= ' AND ' . $eAlias . '.kurum_id = ' . (int) $kid;
        }

        return $parts;
    }

    /**
     * Mahalle bazlı hasta sayısı dökümü
     */
    public function getMahalleStats() {
        $query = "SELECT m.adi as mahalle_adi, il.adi as ilce_adi, 
                  COUNT(h.id) as toplam_hasta,
                  SUM(CASE WHEN h.cinsiyet = 'E' THEN 1 ELSE 0 END) as erkek_sayisi,
                  SUM(CASE WHEN h.cinsiyet = 'K' THEN 1 ELSE 0 END) as kadin_sayisi
                  FROM #__hastalar as h
                  LEFT JOIN #__adrestablosu as m ON m.id = h.mahalle
                  LEFT JOIN #__adrestablosu as il ON il.id = h.ilce
                  WHERE h.pasif = '0'" . TenantSqlHelper::andEquals('h') . "
                  GROUP BY h.mahalle
                  ORDER BY il.adi ASC, m.adi ASC";
        return $this->db->fetchObjectListPrepared($query);
    }

    /**
     * Kayıt yılına göre hasta dağılımı
     */
    public function getKayitYiliStats() {
    $query = "SELECT YEAR(kayittarihi) as kayityili, 
              SUM(CASE WHEN cinsiyet = 'E' THEN 1 ELSE 0 END) as erkek_sayisi,
              SUM(CASE WHEN cinsiyet = 'K' THEN 1 ELSE 0 END) as kadin_sayisi,
              COUNT(id) as toplam_sayi
              FROM #__hastalar 
              WHERE pasif = '0' AND kayittarihi IS NOT NULL AND kayittarihi != '0000-00-00'" . TenantSqlHelper::andBare() . "
              GROUP BY YEAR(kayittarihi)
              ORDER BY YEAR(kayittarihi) ASC";
    return $this->db->fetchObjectListPrepared($query);
}

    /**
     * Kayıt ayına göre döküm
     */
    public function getKayitAyiStats() {
    // Hem yıl hem ay bilgisi alarak grupluyoruz
    $query = "SELECT YEAR(kayittarihi) as kayityili, MONTH(kayittarihi) as kayitay, 
              SUM(CASE WHEN cinsiyet = 'E' THEN 1 ELSE 0 END) as erkek_sayisi,
              SUM(CASE WHEN cinsiyet = 'K' THEN 1 ELSE 0 END) as kadin_sayisi,
              COUNT(id) as toplam_sayi
              FROM #__hastalar 
              WHERE pasif = '0' AND kayittarihi IS NOT NULL AND kayittarihi != '0000-00-00'" . TenantSqlHelper::andBare() . "
              GROUP BY YEAR(kayittarihi), MONTH(kayittarihi)
              ORDER BY YEAR(kayittarihi) DESC, MONTH(kayittarihi) DESC";
    return $this->db->fetchObjectListPrepared($query);
}

    /**
     * Genel özet (Toplam hasta, Aktif, Pasif, Erkek, Kadın)
     */
    public function getGeneralSummary() {
        $query = "SELECT 
                  COUNT(id) as toplam,
                  SUM(CASE WHEN pasif = '0' THEN 1 ELSE 0 END) as aktif,
                  SUM(CASE WHEN pasif = '1' THEN 1 ELSE 0 END) as pasif,
                  SUM(CASE WHEN (cinsiyet = 'E' OR cinsiyet = '1') AND pasif = '0' THEN 1 ELSE 0 END) as erkek,
                  SUM(CASE WHEN (cinsiyet = 'K' OR cinsiyet = '2') AND pasif = '0' THEN 1 ELSE 0 END) as kadin
                  FROM #__hastalar WHERE 1=1" . TenantSqlHelper::andBare();
        return $this->db->fetchObjectPrepared($query);
    }
    
     public function getDetailedPatientList($filters = [], $limit = 50, $offset = 0) {
        $where = ["h.pasif = '0'"];
        $params = [];
        $this->mergeKurumWhere($where);
        if (!empty($filters['ilce'])) {
            $where[] = 'h.ilce = ?';
            $params[] = $filters['ilce'];
        }
        if (!empty($filters['mahalle'])) {
            $where[] = 'h.mahalle = ?';
            $params[] = $filters['mahalle'];
        }
        
        $whereSql = " WHERE " . implode(' AND ', $where);
        $query = "SELECT h.*, m.adi as mahalle, il.adi as ilce 
                  FROM #__hastalar as h
                  LEFT JOIN #__adrestablosu as m ON m.id = h.mahalle
                  LEFT JOIN #__adrestablosu as il ON il.id = h.ilce
                  $whereSql 
                  ORDER BY h.isim ASC LIMIT " . (int) $limit . " OFFSET " . (int) $offset;
        return $this->db->fetchObjectListPrepared($query, $params);
    }

    // TASK: hHastalik -> Hastalık Grupları (Yeni Eklenen)
    public function getHastalikStats() {
        $rows = $this->db->fetchObjectListPrepared("SELECT hastaliklar
             FROM #__hastalar
             WHERE pasif = '0'" . TenantSqlHelper::andBare()
        ) ?: [];

        $diseaseMapRows = $this->db->fetchObjectListPrepared(
            'SELECT id, icd, hastalikadi FROM #__hastaliklar WHERE kurum_id = ? ORDER BY id ASC',
            [CatalogStoreHelper::PLATFORM_KURUM_ID]
        ) ?: [];

        $diseaseById = [];
        foreach ($diseaseMapRows as $r) {
            $id = (int) ($r->id ?? 0);
            if ($id <= 0) {
                continue;
            }
            $icd = trim((string) ($r->icd ?? ''));
            $adi = trim((string) ($r->hastalikadi ?? ''));
            $label = $adi;
            if ($icd !== '' && $adi !== '') {
                $label = $icd . '-' . $adi;
            } elseif ($icd !== '') {
                $label = $icd;
            }
            if ($label === '') {
                $label = 'Tanı #' . $id;
            }
            $diseaseById[$id] = (object) [
                'id' => $id,
                'icd' => $icd,
                'hastalikadi' => $adi,
                'etiket' => $label,
            ];
        }

        $counts = [];
        foreach ($rows as $row) {
            $csv = trim((string) ($row->hastaliklar ?? ''));
            if ($csv === '') {
                continue;
            }
            $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', str_replace(' ', '', $csv))))));
            foreach ($ids as $hid) {
                if ($hid <= 0) {
                    continue;
                }
                $counts[$hid] = ($counts[$hid] ?? 0) + 1;
            }
        }

        if ($counts === []) {
            return [];
        }

        arsort($counts);
        $out = [];
        foreach ($counts as $hid => $count) {
            $meta = $diseaseById[$hid] ?? (object) [
                'id' => $hid,
                'icd' => '',
                'hastalikadi' => '',
                'etiket' => 'Tanı #' . $hid,
            ];
            $out[] = (object) [
                'id' => (int) $hid,
                'icd' => (string) ($meta->icd ?? ''),
                'hastalikadi' => (string) ($meta->hastalikadi ?? ''),
                'etiket' => (string) ($meta->etiket ?? ''),
                'sayi' => (int) $count,
            ];
        }

        return $out;
    }

    /**
     * Aktif hastalarda tanı kategorisi özeti (hasta kategoride en az bir tanıya sahipse bir kez sayılır).
     *
     * @return array<int, object{cat_id:int,cat_name:string,hasta_sayisi:int,tani_kayitli_sayisi:int}>
     */
    public function getHastalikCategorySummary(): array
    {
        $hKurum = TenantSqlHelper::andEquals('h', 'kurum_id');
        $h2Kurum = TenantSqlHelper::andEquals('h2', 'kurum_id');
        $hlPlatform = CatalogScopeSqlHelper::sqlPlatformCatalogWhere('hl');
        $hlScope = CatalogScopeSqlHelper::sqlHastalikOperationalScopeWhere('hl', 'id');
        $hl2Platform = CatalogScopeSqlHelper::sqlPlatformCatalogWhere('hl2');
        $hl2Scope = CatalogScopeSqlHelper::sqlHastalikOperationalScopeWhere('hl2', 'id');
        $list = $this->db->fetchObjectListPrepared("SELECT c.id AS cat_id,
                    c.name AS cat_name,
                    (SELECT COUNT(DISTINCT h.id)
                       FROM #__hastalar h
                      WHERE h.pasif = '0'
                        AND TRIM(IFNULL(h.hastaliklar, '')) != ''
                        {$hKurum}
                        AND EXISTS (
                            SELECT 1 FROM #__hastaliklar hl
                             WHERE hl.cat = c.id
                               {$hlPlatform}
                               {$hlScope}
                               AND FIND_IN_SET(hl.id, REPLACE(h.hastaliklar, ' ', '')) > 0
                        )) AS hasta_sayisi,
                    (SELECT COUNT(*)
                       FROM #__hastaliklar hl2
                      WHERE hl2.cat = c.id
                        {$hl2Platform}
                        {$hl2Scope}
                        AND EXISTS (
                            SELECT 1 FROM #__hastalar h2
                             WHERE h2.pasif = '0'
                               {$h2Kurum}
                               AND FIND_IN_SET(hl2.id, REPLACE(h2.hastaliklar, ' ', '')) > 0
                        )) AS tani_kayitli_sayisi
               FROM #__hastalikcat c
               ORDER BY hasta_sayisi DESC, c.id ASC"
        );

        return is_array($list) ? $list : [];
    }

    /**
     * Eski admin stats task=hastalik: kategori başlıkları altında ICD + tanı,
     * aktif hastalardaki tanı adetleri ve toplam aktif hasta (oran paydası).
     *
     * @return array{total_aktif:int,categories:list<array{id:int,name:string,hastaliklar:list<object>}>}
     */
    public function getHastalikDiagnosisDistribution(): array {
        $hScope = CatalogScopeSqlHelper::sqlHastalikOperationalScopeWhere('h', 'id');
        $query = "SELECT c.id AS cat_id, c.name AS cat_name, h.id, h.icd, h.hastalikadi
                  FROM #__hastalikcat AS c
                  LEFT JOIN #__hastaliklar AS h ON h.cat = c.id"
            . CatalogScopeSqlHelper::sqlPlatformCatalogWhere('h')
            . $hScope
            . " ORDER BY c.id ASC, h.hastalikadi ASC";
        $all_hast = $this->db->fetchObjectListPrepared($query) ?: [];

        $hastaliklar = [];
        foreach ($all_hast as $item) {
            $cid = (int) ($item->cat_id ?? 0);
            if (!isset($hastaliklar[$cid])) {
                $hastaliklar[$cid] = [
                    'id' => $cid,
                    'name' => (string) ($item->cat_name ?? ''),
                    'hastaliklar' => [],
                ];
            }
            $hid = isset($item->id) ? (int) $item->id : 0;
            if ($hid > 0) {
                $hastaliklar[$cid]['hastaliklar'][] = (object) [
                    'id' => $hid,
                    'icd' => (string) ($item->icd ?? ''),
                    'hastalikadi' => (string) ($item->hastalikadi ?? ''),
                ];
            }
        }

        $sqlHastaHastaliklari = "SELECT hastaliklar FROM #__hastalar WHERE pasif = '0' AND hastaliklar != '' AND hastaliklar IS NOT NULL" . TenantSqlHelper::andBare('kurum_id');
        $liste = $this->db->fetchColumnListPrepared($sqlHastaHastaliklari, [], 0) ?: [];

        $data = [];
        foreach ($liste as $li) {
            foreach (explode(',', (string) $li) as $idRaw) {
                $idRaw = trim($idRaw);
                if ($idRaw === '') {
                    continue;
                }
                $nid = (int) $idRaw;
                if ($nid > 0) {
                    $data[] = $nid;
                }
            }
        }
        $counts = $data === [] ? [] : array_count_values($data);

        $sqlTotalHasta = "SELECT COUNT(id) FROM #__hastalar WHERE pasif = '0'" . TenantSqlHelper::andBare('kurum_id');
        $totalh = (int) $this->db->loadResultPrepared($sqlTotalHasta);

        return [
            'total_aktif' => $totalh,
            'categories' => array_values($hastaliklar),
            'counts' => $counts,
        ];
    }

    public function countPatientsWithHastalikId(int $hid): int {
        $hid = max(1, $hid);
        $sql = "SELECT COUNT(id) FROM #__hastalar WHERE pasif = '0' AND FIND_IN_SET(?, hastaliklar)" . TenantSqlHelper::andBare();
        return (int) $this->db->loadResultPrepared($sql, [$hid]);
    }

    /**
     * @return list<object>
     */
    public function getPatientsWithHastalikId(int $hid, string $orderFragment, int $limit, int $offset): array {
        $hid = max(1, $hid);
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $izK = $this->izSubK();
        $sql = "SELECT h.*, m.adi AS mahalle, ilc.adi AS ilce,
            (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik{$izK}) AS toplamizlem,
            (SELECT MAX(izlemtarihi) FROM #__izlemler WHERE hastatckimlik = h.tckimlik{$izK}) AS sonizlem
            FROM #__hastalar AS h
            LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle
            LEFT JOIN #__adrestablosu AS ilc ON ilc.id = h.ilce
            WHERE FIND_IN_SET(" . $hid . ", h.hastaliklar) AND h.pasif = '0'" . TenantSqlHelper::andEquals('h') . "
            ORDER BY " . $orderFragment . "
            LIMIT " . (int) $offset . ", " . (int) $limit;
        $list = $this->db->fetchObjectListPrepared($sql);

        return is_array($list) ? $list : [];
    }

    /**
     * Eski task=bir (birIzlemliler): tamamlanmış tek izlem + yapilan içinde işlem id 1.
     * `#__hastalar.ilce` / `mahalle` VARCHAR olduğu için filtre değerleri `quote` ile bağlanır (e-Rapor listesi ile aynı).
     *
     * @return list<string> TC listesi (eski sorgu sırası; sayfalama PHP tarafında)
     */
    public function getBirIzlemMatchingTckimliks(?string $ilce, ?string $mahalle): array {
        $where = ["h.pasif = '0'", 'i.yapildimi = 1'];
        $params = [];
        $this->mergeKurumWhere($where, 'h');
        if ($ilce !== null && $ilce !== '' && $ilce !== '0') {
            $where[] = 'h.ilce = ?';
            $params[] = $ilce;
        }
        if ($mahalle !== null && $mahalle !== '' && $mahalle !== '0') {
            $where[] = 'h.mahalle = ?';
            $params[] = $mahalle;
        }
        $whereSql = implode(' AND ', $where);
        $sql = 'SELECT h.tckimlik
            FROM #__hastalar AS h
            INNER JOIN #__izlemler AS i ON i.hastatckimlik = h.tckimlik
            WHERE ' . $whereSql . '
            GROUP BY h.tckimlik
            HAVING COUNT(i.id) = 1
            AND FIND_IN_SET(\'' . IslemIdSettings::resolvedInt('stats_bir_izlem_yapilan_islem_id') . '\', REPLACE(GROUP_CONCAT(i.yapilan), \' \', \'\')) > 0';
        $rows = $this->db->fetchObjectListPrepared($sql, $params) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $tc = trim((string) ($r->tckimlik ?? ''));
            if ($tc !== '') {
                $out[] = $tc;
            }
        }

        return $out;
    }

    /**
     * Seçilen ay/yılda tam olarak bir tamamlanmış izlemi olan aktif hastalar (işlem tipi fark etmez).
     *
     * @return list<string> TC listesi (sayfalama PHP tarafında)
     */
    public function getAylikTekIzlemMatchingTckimliks(int $year, int $month, ?string $ilce, ?string $mahalle): array {
        $startYmd = sprintf('%04d-%02d-01', $year, $month);
        $endYmd = date('Y-m-t', strtotime($startYmd));
        $izd = $this->sqlIzlemTarihiAsDate('i');

        $where = ["h.pasif = '0'", 'i.yapildimi = 1', $izd . ' BETWEEN ? AND ?'];
        $params = [$startYmd, $endYmd];
        $this->mergeKurumWhere($where, 'h');
        if ($ilce !== null && $ilce !== '' && $ilce !== '0') {
            $where[] = 'h.ilce = ?';
            $params[] = $ilce;
        }
        if ($mahalle !== null && $mahalle !== '' && $mahalle !== '0') {
            $where[] = 'h.mahalle = ?';
            $params[] = $mahalle;
        }
        $whereSql = implode(' AND ', $where);
        $sql = 'SELECT h.tckimlik
            FROM #__hastalar AS h
            INNER JOIN #__izlemler AS i ON i.hastatckimlik = h.tckimlik
            WHERE ' . $whereSql . '
            GROUP BY h.tckimlik
            HAVING COUNT(i.id) = 1';
        $rows = $this->db->fetchObjectListPrepared($sql, $params) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $tc = trim((string) ($r->tckimlik ?? ''));
            if ($tc !== '') {
                $out[] = $tc;
            }
        }

        return $out;
    }

    /**
     * @param list<string> $tcs
     * @return list<object>
     */
    public function getBirIzlemPatientRows(array $tcs, string $orderByClause): array {
        if ($tcs === []) {
            return [];
        }
        $cleanTcs = [];
        foreach ($tcs as $tc) {
            $tc = trim((string) $tc);
            if ($tc !== '') {
                $cleanTcs[] = $tc;
            }
        }
        if ($cleanTcs === []) {
            return [];
        }
        [$inSql, $inParams] = $this->db->whereInClause($cleanTcs);
        $izK = $this->izSubK();
        $sql = 'SELECT h.*, m.adi AS mahalle, ii.adi AS ilce, 1 AS toplamizlem,
            (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 1' . $izK . ') AS izlemsayisi,
            (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 0' . $izK . ') AS yizlemsayisi,
            (SELECT COUNT(id) FROM #__pizlemler WHERE hastatckimlik = h.tckimlik' . $izK . ') AS totalplanli,
            (SELECT MAX(izlemtarihi) FROM #__izlemler WHERE hastatckimlik = h.tckimlik' . $izK . ') AS sonizlem
            FROM #__hastalar AS h
            LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle
            LEFT JOIN #__adrestablosu AS ii ON ii.id = h.ilce
            WHERE h.tckimlik IN (' . $inSql . ')' . TenantSqlHelper::andEquals('h') . '
            ' . $orderByClause;
        $list = $this->db->fetchObjectListPrepared($sql, $inParams);

        return is_array($list) ? $list : [];
    }

    // Yardımcı: Yaş hesaplama mantığını SQL'e gömelim
    public function getYasGrubuStats() {
        $query = "SELECT 
            SUM(CASE WHEN (YEAR(CURDATE()) - YEAR(dogumtarihi)) < 18 THEN 1 ELSE 0 END) as cocuk,
            SUM(CASE WHEN (YEAR(CURDATE()) - YEAR(dogumtarihi)) BETWEEN 18 AND 65 THEN 1 ELSE 0 END) as yetiskin,
            SUM(CASE WHEN (YEAR(CURDATE()) - YEAR(dogumtarihi)) > 65 THEN 1 ELSE 0 END) as yasli
            FROM #__hastalar WHERE pasif = '0'" . TenantSqlHelper::andBare();
        return $this->db->fetchObjectPrepared($query);
    }

    /** @var bool|null */
    private static $izlemTarihiDtColumnExists = null;

    /**
     * İzlem tarihini DATE olarak çözer (izlemtarihi_dt varsa önce onu; yoksa string parse).
     */
    public function sqlIzlemTarihiAsDate(string $alias = 'i'): string {
        if ($this->hasIzlemTarihiDtColumn()) {
            $legacy = "COALESCE(STR_TO_DATE(TRIM({$alias}.izlemtarihi),'%Y-%m-%d'),STR_TO_DATE(TRIM({$alias}.izlemtarihi),'%d.%m.%Y'),STR_TO_DATE(TRIM({$alias}.izlemtarihi),'%d-%m-%Y'))";

            return "COALESCE({$alias}.izlemtarihi_dt, {$legacy})";
        }

        return "COALESCE(STR_TO_DATE(TRIM({$alias}.izlemtarihi),'%Y-%m-%d'),STR_TO_DATE(TRIM({$alias}.izlemtarihi),'%d.%m.%Y'),STR_TO_DATE(TRIM({$alias}.izlemtarihi),'%d-%m-%Y'))";
    }

    public function hasIzlemTarihiDtColumn(): bool {
        if (self::$izlemTarihiDtColumnExists !== null) {
            return self::$izlemTarihiDtColumnExists;
        }
        $sql = 'SELECT COUNT(*) FROM information_schema.COLUMNS'
            . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?';
        self::$izlemTarihiDtColumnExists = (int) $this->db->loadResultPrepared(
            $sql,
            [$this->db->replacePrefix('#__izlemler'), 'izlemtarihi_dt']
        ) > 0;

        return self::$izlemTarihiDtColumnExists;
    }

    /**
     * Aktif hastalarda adres / kimlik / izlem eksikleri (eski superVeriDenetimPaneli).
     */
    public function getDataHealthSnapshot(): object {
        $cacheKey = $this->statsCacheKey('data_health_snapshot');
        $cached = StatsQueryCache::get($cacheKey);
        if (is_array($cached)) {
            return (object) $cached;
        }

        $izK = $this->izSubK();
        $sql = "SELECT 
                SUM(CASE WHEN (h.ilce = 0 OR h.ilce IS NULL OR h.ilce = '') THEN 1 ELSE 0 END) AS ilce_yok,
                SUM(CASE WHEN (h.mahalle = 0 OR h.mahalle IS NULL OR h.mahalle = '') THEN 1 ELSE 0 END) AS mahalle_yok,
                SUM(CASE WHEN (h.sokak = 0 OR h.sokak IS NULL OR h.sokak = '') THEN 1 ELSE 0 END) AS sokak_yok,
                SUM(CASE WHEN (h.kapino = 0 OR h.kapino IS NULL OR h.kapino = '') THEN 1 ELSE 0 END) AS kapi_yok,
                SUM(CASE WHEN (m.id IS NOT NULL AND m.ust_id != h.ilce) THEN 1 ELSE 0 END) AS mahalle_ilce_uyumsuz,
                SUM(CASE WHEN (s.id IS NOT NULL AND s.ust_id != h.mahalle) THEN 1 ELSE 0 END) AS sokak_mahalle_uyumsuz,
                SUM(CASE WHEN (k.id IS NOT NULL AND k.ust_id != h.sokak) THEN 1 ELSE 0 END) AS kapino_sokak_uyumsuz,
                SUM(CASE WHEN (h.tckimlik = '' OR h.tckimlik IS NULL OR CHAR_LENGTH(TRIM(h.tckimlik)) != 11) THEN 1 ELSE 0 END) AS hatali_tc,
                SUM(CASE WHEN (h.dogumtarihi = '0000-00-00' OR h.dogumtarihi IS NULL OR TRIM(IFNULL(h.dogumtarihi,'')) = '') THEN 1 ELSE 0 END) AS dogum_yok,
                SUM(CASE WHEN (h.cinsiyet = '' OR h.cinsiyet IS NULL) THEN 1 ELSE 0 END) AS cinsiyet_yok,
                SUM(CASE WHEN (h.kilo IN ('', '0') OR h.kilo IS NULL) THEN 1 ELSE 0 END) AS kilo_yok,
                SUM(CASE WHEN (h.boy IN ('', '0') OR h.boy IS NULL) THEN 1 ELSE 0 END) AS boy_yok,
                SUM(CASE WHEN (h.ceptel1 = '' OR h.ceptel1 IS NULL) THEN 1 ELSE 0 END) AS tel_yok,
                SUM(CASE WHEN (h.guvence = '' OR h.guvence = 0 OR h.guvence IS NULL) THEN 1 ELSE 0 END) AS guvence_yok,
                SUM(CASE WHEN NOT EXISTS (
                    SELECT 1 FROM #__izlemler AS iz WHERE iz.hastatckimlik = h.tckimlik{$izK} LIMIT 1
                ) THEN 1 ELSE 0 END) AS hic_izlenmemis
            FROM #__hastalar AS h
            LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle
            LEFT JOIN #__adrestablosu AS s ON s.id = h.sokak
            LEFT JOIN #__adrestablosu AS k ON k.id = h.kapino
            WHERE h.pasif = '0'" . TenantSqlHelper::andEquals('h');
        $row = $this->db->fetchObjectPrepared($sql);
        if (!$row) {
            $row = (object) [];
        }
        $alanlar = ['ilce_yok', 'mahalle_yok', 'sokak_yok', 'mahalle_ilce_uyumsuz', 'sokak_mahalle_uyumsuz',
            'kapino_sokak_uyumsuz', 'kapi_yok', 'hatali_tc', 'dogum_yok', 'cinsiyet_yok', 'hic_izlenmemis',
            'kilo_yok', 'boy_yok', 'tel_yok', 'guvence_yok'];
        foreach ($alanlar as $alan) {
            if (!isset($row->$alan)) {
                $row->$alan = 0;
            } else {
                $row->$alan = (int) $row->$alan;
            }
        }
        $row->toplam_kritik = $row->ilce_yok + $row->mahalle_yok + $row->mahalle_ilce_uyumsuz + $row->hatali_tc;
        $row->adres_toplam = $row->ilce_yok + $row->mahalle_yok + $row->sokak_yok + $row->kapi_yok
            + $row->mahalle_ilce_uyumsuz + $row->sokak_mahalle_uyumsuz + $row->kapino_sokak_uyumsuz;
        $row->hasta_dosya_toplam = $row->hatali_tc + $row->dogum_yok + $row->cinsiyet_yok + $row->hic_izlenmemis
            + $row->kilo_yok + $row->boy_yok + $row->tel_yok + $row->guvence_yok;
        StatsQueryCache::set($cacheKey, (array) $row);

        return $row;
    }

    /**
     * Aktif hastalarda adres hiyerarşisi (ust_id) ile hasta kolonları örtüşmeyen kayıtlar.
     * Koşullar {@see getDataHealthSnapshot()} ile aynıdır (pasif = '0').
     *
     * @return array{mahalle_ilce: array<int, object>, sokak_mahalle: array<int, object>, kapino_sokak: array<int, object>}
     */
    public function getAddressMismatchPatientLists(int $limit = 50): array {
        $limit = max(1, min(2000, $limit));
        $lim = (int) $limit;
        $hKurum = TenantSqlHelper::andEquals('h');
        $izK = $this->izSubK();

        $sqlMahalleIlce = "SELECT h.id, h.isim, h.soyisim, h.tckimlik,
                h.ilce AS hasta_ilce_id, h.mahalle AS hasta_mahalle_id,
                (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 1{$izK}) AS izlemsayisi,
                (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 0{$izK}) AS yizlemsayisi,
                (SELECT COUNT(id) FROM #__pizlemler WHERE hastatckimlik = h.tckimlik{$izK}) AS totalplanli,
                il.adi AS ilce_adi, m.adi AS mahalle_adi, m.ust_id AS mahalle_beklenen_ust
            FROM #__hastalar AS h
            INNER JOIN #__adrestablosu AS m ON m.id = h.mahalle
            LEFT JOIN #__adrestablosu AS il ON il.id = h.ilce
            WHERE h.pasif = '0' AND m.ust_id != h.ilce{$hKurum}
            ORDER BY h.isim ASC, h.soyisim ASC
            LIMIT {$lim}";

        $sqlSokakMahalle = "SELECT h.id, h.isim, h.soyisim, h.tckimlik,
                h.mahalle AS hasta_mahalle_id, h.sokak AS hasta_sokak_id,
                (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 1{$izK}) AS izlemsayisi,
                (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 0{$izK}) AS yizlemsayisi,
                (SELECT COUNT(id) FROM #__pizlemler WHERE hastatckimlik = h.tckimlik{$izK}) AS totalplanli,
                m.adi AS mahalle_adi, s.adi AS sokak_adi, s.ust_id AS sokak_beklenen_ust
            FROM #__hastalar AS h
            INNER JOIN #__adrestablosu AS s ON s.id = h.sokak
            LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle
            WHERE h.pasif = '0' AND s.ust_id != h.mahalle{$hKurum}
            ORDER BY h.isim ASC, h.soyisim ASC
            LIMIT {$lim}";

        $sqlKapinoSokak = "SELECT h.id, h.isim, h.soyisim, h.tckimlik,
                h.sokak AS hasta_sokak_id, h.kapino AS hasta_kapino_id,
                (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 1{$izK}) AS izlemsayisi,
                (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 0{$izK}) AS yizlemsayisi,
                (SELECT COUNT(id) FROM #__pizlemler WHERE hastatckimlik = h.tckimlik{$izK}) AS totalplanli,
                s.adi AS sokak_adi, k.adi AS kapino_adi, k.ust_id AS kapino_beklenen_ust
            FROM #__hastalar AS h
            INNER JOIN #__adrestablosu AS k ON k.id = h.kapino
            LEFT JOIN #__adrestablosu AS s ON s.id = h.sokak
            WHERE h.pasif = '0' AND k.ust_id != h.sokak{$hKurum}
            ORDER BY h.isim ASC, h.soyisim ASC
            LIMIT {$lim}";

        $mahalleIlce = $this->db->fetchObjectListPrepared($sqlMahalleIlce) ?: [];
        $sokakMahalle = $this->db->fetchObjectListPrepared($sqlSokakMahalle) ?: [];
        $kapinoSokak = $this->db->fetchObjectListPrepared($sqlKapinoSokak) ?: [];

        return [
            'mahalle_ilce' => $mahalleIlce,
            'sokak_mahalle' => $sokakMahalle,
            'kapino_sokak' => $kapinoSokak,
        ];
    }

    /**
     * Veri sağlığı listesi metrikleri (anahtar → başlık). {@see getDataHealthSnapshot()} ile aynı koşullar.
     *
     * @return array<string, string>
     */
    public static function dataHealthMetricLabels(): array {
        return [
            'mahalle_ilce_uyumsuz' => 'İlçe–Mahalle uyumsuzluğu',
            'sokak_mahalle_uyumsuz' => 'Mahalle–Sokak uyumsuzluğu',
            'kapino_sokak_uyumsuz' => 'Sokak–Kapı no uyumsuzluğu',
            'ilce_yok' => 'İlçe bilgisi eksik',
            'mahalle_yok' => 'Mahalle bilgisi eksik',
            'sokak_yok' => 'Sokak bilgisi eksik',
            'kapi_yok' => 'Kapı no bilgisi eksik',
            'hatali_tc' => 'Geçersiz TC kimlik no',
            'dogum_yok' => 'Doğum tarihi belirsiz',
            'cinsiyet_yok' => 'Cinsiyet bilgisi yok',
            'hic_izlenmemis' => 'Kaydı olup hiç izlenmeyen',
            'kilo_yok' => 'Kilo bilgisi yok',
            'boy_yok' => 'Boy bilgisi yok',
            'tel_yok' => 'Telefon bilgisi yok',
            'guvence_yok' => 'Güvence bilgisi yok',
        ];
    }

    public function isDataHealthMetric(string $metric): bool {
        return isset(self::dataHealthMetricLabels()[$metric]);
    }

    public function countDataHealthPatients(string $metric): int {
        $parts = $this->dataHealthPatientFilter($metric);
        if ($parts === null) {
            return 0;
        }
        $sql = 'SELECT COUNT(h.id) FROM #__hastalar AS h ' . $parts['joins'] . ' WHERE ' . $parts['where'];

        return (int) $this->db->loadResultPrepared($sql);
    }

    /**
     * @return list<object>
     */
    public function getDataHealthPatients(string $metric, string $orderFragment, int $limit, int $offset): array {
        $parts = $this->dataHealthPatientFilter($metric);
        if ($parts === null) {
            return [];
        }
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $izK = $this->izSubK();
        $sql = "SELECT h.*, m.adi AS mahalle, ilc.adi AS ilce,
            (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik{$izK}) AS toplamizlem,
            (SELECT MAX(izlemtarihi) FROM #__izlemler WHERE hastatckimlik = h.tckimlik{$izK}) AS sonizlem,
            (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 1{$izK}) AS izlemsayisi,
            (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 0{$izK}) AS yizlemsayisi,
            (SELECT COUNT(id) FROM #__pizlemler WHERE hastatckimlik = h.tckimlik{$izK}) AS totalplanli
            FROM #__hastalar AS h
            LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle
            LEFT JOIN #__adrestablosu AS ilc ON ilc.id = h.ilce
            " . $parts['joins'] . '
            WHERE ' . $parts['where'] . '
            ORDER BY ' . $orderFragment . '
            LIMIT ' . (int) $offset . ', ' . (int) $limit;
        $list = $this->db->fetchObjectListPrepared($sql);

        return is_array($list) ? $list : [];
    }

    /**
     * @return array{joins:string, where:string}|null
     */
    private function dataHealthPatientFilter(string $metric): ?array {
        if (!$this->isDataHealthMetric($metric)) {
            return null;
        }
        $kid = TenantContext::filterKurumId();
        $kurumSql = TenantSqlHelper::andEquals('h');
        $izKurumSub = $kid !== null ? ' WHERE kurum_id = ' . (int) $kid : '';
        $pasif = "h.pasif = '0'";
        switch ($metric) {
            case 'ilce_yok':
                return ['joins' => '', 'where' => $pasif . " AND (h.ilce = 0 OR h.ilce IS NULL OR h.ilce = '')" . $kurumSql];
            case 'mahalle_yok':
                return ['joins' => '', 'where' => $pasif . " AND (h.mahalle = 0 OR h.mahalle IS NULL OR h.mahalle = '')" . $kurumSql];
            case 'sokak_yok':
                return ['joins' => '', 'where' => $pasif . " AND (h.sokak = 0 OR h.sokak IS NULL OR h.sokak = '')" . $kurumSql];
            case 'kapi_yok':
                return ['joins' => '', 'where' => $pasif . " AND (h.kapino = 0 OR h.kapino IS NULL OR h.kapino = '')" . $kurumSql];
            case 'mahalle_ilce_uyumsuz':
                return [
                    'joins' => 'INNER JOIN #__adrestablosu AS dm ON dm.id = h.mahalle',
                    'where' => $pasif . ' AND dm.ust_id != h.ilce' . $kurumSql,
                ];
            case 'sokak_mahalle_uyumsuz':
                return [
                    'joins' => 'INNER JOIN #__adrestablosu AS ds ON ds.id = h.sokak',
                    'where' => $pasif . ' AND ds.ust_id != h.mahalle' . $kurumSql,
                ];
            case 'kapino_sokak_uyumsuz':
                return [
                    'joins' => 'INNER JOIN #__adrestablosu AS dk ON dk.id = h.kapino',
                    'where' => $pasif . ' AND dk.ust_id != h.sokak' . $kurumSql,
                ];
            case 'hatali_tc':
                return [
                    'joins' => '',
                    'where' => $pasif . " AND (h.tckimlik = '' OR h.tckimlik IS NULL OR CHAR_LENGTH(TRIM(h.tckimlik)) != 11)" . $kurumSql,
                ];
            case 'dogum_yok':
                return [
                    'joins' => '',
                    'where' => $pasif . " AND (h.dogumtarihi = '0000-00-00' OR h.dogumtarihi IS NULL OR TRIM(IFNULL(h.dogumtarihi,'')) = '')" . $kurumSql,
                ];
            case 'cinsiyet_yok':
                return ['joins' => '', 'where' => $pasif . " AND (h.cinsiyet = '' OR h.cinsiyet IS NULL)" . $kurumSql];
            case 'hic_izlenmemis':
                return [
                    'joins' => 'LEFT JOIN (SELECT DISTINCT hastatckimlik FROM #__izlemler' . $izKurumSub . ') AS dh_i ON dh_i.hastatckimlik = h.tckimlik',
                    'where' => $pasif . ' AND dh_i.hastatckimlik IS NULL' . $kurumSql,
                ];
            case 'kilo_yok':
                return ['joins' => '', 'where' => $pasif . " AND (h.kilo IN ('', '0') OR h.kilo IS NULL)" . $kurumSql];
            case 'boy_yok':
                return ['joins' => '', 'where' => $pasif . " AND (h.boy IN ('', '0') OR h.boy IS NULL)" . $kurumSql];
            case 'tel_yok':
                return ['joins' => '', 'where' => $pasif . " AND (h.ceptel1 = '' OR h.ceptel1 IS NULL)" . $kurumSql];
            case 'guvence_yok':
                return ['joins' => '', 'where' => $pasif . " AND (h.guvence = '' OR h.guvence = 0 OR h.guvence IS NULL)" . $kurumSql];
            default:
                return null;
        }
    }

    /**
     * Son 3 ayda en az bir tamamlanmış izlemi olan aktif hasta oranı (KPI).
     * @return object{toplam_aktif:int, izlenen:int, skor:float, renk_sinifi:string, panel_sinifi:string, mesaj:string}
     */
    public function getFollowEfficiencyKpi(): object {
        $toplamAktif = (int) $this->db->loadResultPrepared("SELECT COUNT(id) FROM #__hastalar WHERE pasif = '0'" . TenantSqlHelper::andBare());
        $izd = $this->sqlIzlemTarihiAsDate('i');
        $ucAy = $this->db->quote(date('Y-m-d', strtotime('-3 months')));
        $izlenen = (int) $this->db->loadResultPrepared("SELECT COUNT(DISTINCT h.tckimlik) FROM #__hastalar h
            INNER JOIN #__izlemler i ON h.tckimlik = i.hastatckimlik
            WHERE h.pasif = '0' AND i.yapildimi = 1 AND {$izd} IS NOT NULL AND {$izd} >= {$ucAy}" . TenantSqlHelper::andEquals('h') . TenantSqlHelper::andEquals('i')
        );
        $skor = $toplamAktif > 0 ? round(($izlenen / $toplamAktif) * 100, 1) : 0.0;
        if ($skor >= 80) {
            $renk = 'bg-success';
            $panel = 'border-success';
            $mesaj = 'Sistem verimliliği iyi görünüyor.';
        } elseif ($skor >= 50) {
            $renk = 'bg-warning';
            $panel = 'border-warning';
            $mesaj = 'İzlem sıklığı artırılmalı.';
        } else {
            $renk = 'bg-danger';
            $panel = 'border-danger';
            $mesaj = 'Kritik: Birçok aktif hasta son 3 ayda izlenmemiş.';
        }
        return (object) [
            'toplam_aktif' => $toplamAktif,
            'izlenen' => $izlenen,
            'skor' => $skor,
            'renk_sinifi' => $renk,
            'panel_sinifi' => $panel,
            'mesaj' => $mesaj,
        ];
    }

    /**
     * İlçe > mahalle bazlı izlenme oranı (eski admin stats task=verimlilik / bolgeBazliVerimlilikRaporu).
     * Her satır: mahalledeki aktif hasta sayısı ve son $rollingMonths ay içinde en az bir tamamlanmış izlemi olan benzersiz aktif hasta sayısı.
     * Verimlilik skoru % = (izlenen_hasta / toplam_hasta) * 100 (toplam 0 ise 0).
     *
     * @return list<object>
     */
    public function getRegionalNeighborhoodVisitPerformance(int $rollingMonths = 3): array {
        $rollingMonths = max(1, min(24, $rollingMonths));
        $since = $this->db->quote(date('Y-m-d', strtotime('-' . $rollingMonths . ' months')));
        $izd = $this->sqlIzlemTarihiAsDate('iz');
        $hKurum = TenantSqlHelper::andEquals('h');
        $h2Kurum = TenantSqlHelper::andEquals('h2');
        $izKurum = TenantSqlHelper::andEquals('iz');
        $sql = "SELECT
                i.adi AS ilce_adi,
                m.adi AS mahalle_adi,
                h.ilce AS ilce_id,
                h.mahalle AS mahalle_id,
                COUNT(DISTINCT h.tckimlik) AS toplam_hasta,
                (SELECT COUNT(DISTINCT iz.hastatckimlik)
                 FROM #__izlemler AS iz
                 INNER JOIN #__hastalar AS h2 ON iz.hastatckimlik = h2.tckimlik
                 WHERE h2.mahalle = h.mahalle
                   AND h2.pasif = '0'
                   {$h2Kurum}
                   {$izKurum}
                   AND iz.yapildimi = 1
                   AND {$izd} IS NOT NULL
                   AND {$izd} >= {$since}
                ) AS izlenen_hasta
            FROM #__hastalar AS h
            LEFT JOIN #__adrestablosu AS i ON h.ilce = i.id AND i.tip = 'ilce'
            LEFT JOIN #__adrestablosu AS m ON h.mahalle = m.id AND m.tip = 'mahalle'
            WHERE h.pasif = '0'{$hKurum}
            GROUP BY h.ilce, h.mahalle, i.adi, m.adi
            ORDER BY i.adi ASC, m.adi ASC";

        return $this->db->fetchObjectListPrepared($sql) ?: [];
    }

    /**
     * Son 12 ay kapsama + son 3 takvim yılı tamamlanmış izlem sayıları.
     */
    public function getYearlyFollowCoverage(): object {
        $izd = $this->sqlIzlemTarihiAsDate('i');
        $toplamAktif = (int) $this->db->loadResultPrepared("SELECT COUNT(id) FROM #__hastalar WHERE pasif = '0'" . TenantSqlHelper::andBare());
        $oniki = $this->db->quote(date('Y-m-d', strtotime('-12 months')));
        $izlenenYillik = (int) $this->db->loadResultPrepared("SELECT COUNT(DISTINCT h.tckimlik) FROM #__hastalar h
            INNER JOIN #__izlemler i ON h.tckimlik = i.hastatckimlik
            WHERE h.pasif = '0' AND i.yapildimi = 1 AND {$izd} IS NOT NULL AND {$izd} >= {$oniki}" . TenantSqlHelper::andEquals('h') . TenantSqlHelper::andEquals('i')
        );
        $yillikSkor = $toplamAktif > 0 ? round(($izlenenYillik / $toplamAktif) * 100, 1) : 0.0;
        $hedef = 4;
        $maxBeklenti = $toplamAktif * $hedef;
        $iKurum = TenantSqlHelper::andEquals('i');
        $byYear = [];
        for ($i = 0; $i < 3; $i++) {
            $y = (int) date('Y') - $i;
            $byYear[$y] = (int) $this->db->loadResultPrepared("SELECT COUNT(i.id) FROM #__izlemler i WHERE i.yapildimi = 1 AND {$izd} IS NOT NULL AND YEAR({$izd}) = " . (int) $y . $iKurum
            );
        }
        return (object) [
            'toplam_aktif' => $toplamAktif,
            'izlenen_yillik' => $izlenenYillik,
            'yillik_skor' => $yillikSkor,
            'hedef' => $hedef,
            'max_beklenti' => $maxBeklenti,
            'by_year' => $byYear,
        ];
    }

    /**
     * Tamamlanmış izlem sayısına göre ilk N aktif hasta.
     */
    public function getTopVisitedPatients(int $limit = 10): array {
        $limit = max(1, min(50, $limit));
        $izK = $this->izSubK();
        $sql = "SELECT h.id, h.isim, h.soyisim, h.tckimlik, h.cinsiyet,
                h.anneAdi, h.babaAdi, h.dogumtarihi, h.ceptel1, h.kayittarihi, h.randevutarihi, h.pasif,
                il.adi AS ilce_adi, m.adi AS mahalle_adi,
                COUNT(i.id) AS toplam_izlem,
                (SELECT izlemtarihi FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 1{$izK} ORDER BY izlemtarihi DESC LIMIT 1) AS sonizlemtarihi,
                (SELECT COUNT(id) FROM #__izlemler i2 WHERE i2.hastatckimlik = h.tckimlik AND i2.yapildimi = 1{$izK}) AS izlemsayisi,
                (SELECT COUNT(id) FROM #__izlemler i3 WHERE i3.hastatckimlik = h.tckimlik AND i3.yapildimi = 0{$izK}) AS yizlemsayisi,
                (SELECT COUNT(id) FROM #__pizlemler p WHERE p.hastatckimlik = h.tckimlik{$izK}) AS totalplanli
            FROM #__izlemler AS i
            INNER JOIN #__hastalar AS h ON i.hastatckimlik = h.tckimlik
            LEFT JOIN #__adrestablosu il ON il.id = h.ilce
            LEFT JOIN #__adrestablosu m ON m.id = h.mahalle
            WHERE i.yapildimi = 1 AND h.pasif = '0'" . TenantSqlHelper::andEquals('h') . TenantSqlHelper::andEquals('i') . "
            GROUP BY h.id, h.isim, h.soyisim, h.tckimlik, h.cinsiyet,
                h.anneAdi, h.babaAdi, h.dogumtarihi, h.ceptel1, h.kayittarihi, h.randevutarihi, h.pasif, h.ilce, h.mahalle
            ORDER BY toplam_izlem DESC
            LIMIT " . (int) $limit;
        $list = $this->db->fetchObjectListPrepared($sql);
        return is_array($list) ? $list : [];
    }

    /**
     * pasif koduna göre hasta sayıları.
     */
    public function getPatientStatusCounts(): array {
        $sql = "SELECT pasif, COUNT(id) AS adet FROM #__hastalar WHERE 1=1" . TenantSqlHelper::andBare() . " GROUP BY pasif ORDER BY pasif ASC";
        $list = $this->db->fetchObjectListPrepared($sql);
        return is_array($list) ? $list : [];
    }

    /**
     * Aktif hastaya göre ilçe sıralaması (üst N).
     */
    public function getIlceActiveRanking(int $limit = 25): array {
        $limit = max(5, min(80, $limit));
        $sql = "SELECT il.adi AS ilce_adi, COUNT(h.id) AS adet
            FROM #__hastalar h
            LEFT JOIN #__adrestablosu il ON il.id = h.ilce
            WHERE h.pasif = '0' AND h.ilce IS NOT NULL AND TRIM(CAST(h.ilce AS CHAR)) NOT IN ('', '0')" . TenantSqlHelper::andEquals('h') . "
            GROUP BY h.ilce, il.adi
            ORDER BY adet DESC
            LIMIT " . (int) $limit;
        $list = $this->db->fetchObjectListPrepared($sql);
        return is_array($list) ? $list : [];
    }

    /**
     * Tamamlanmış izlem adetleri (tarih çözümlü): son 7 gün, 30 gün, bu ay.
     */
    public function getVisitTrendStats(): object {
        $izd = $this->sqlIzlemTarihiAsDate('i');
        $d7 = $this->db->quote(date('Y-m-d', strtotime('-7 days')));
        $d30 = $this->db->quote(date('Y-m-d', strtotime('-30 days')));
        $m0 = $this->db->quote(date('Y-m-01'));
        $m1 = $this->db->quote(date('Y-m-t'));
        $c7 = (int) $this->db->loadResultPrepared("SELECT COUNT(i.id) FROM #__izlemler i WHERE i.yapildimi = 1 AND {$izd} IS NOT NULL AND {$izd} >= {$d7}" . TenantSqlHelper::andEquals('i')
        );
        $c30 = (int) $this->db->loadResultPrepared("SELECT COUNT(i.id) FROM #__izlemler i WHERE i.yapildimi = 1 AND {$izd} IS NOT NULL AND {$izd} >= {$d30}" . TenantSqlHelper::andEquals('i')
        );
        $cm = (int) $this->db->loadResultPrepared("SELECT COUNT(i.id) FROM #__izlemler i WHERE i.yapildimi = 1 AND {$izd} IS NOT NULL AND {$izd} BETWEEN {$m0} AND {$m1}" . TenantSqlHelper::andEquals('i')
        );
        return (object) ['gun7' => $c7, 'gun30' => $c30, 'bu_ay' => $cm];
    }

    /**
     * Bu ay planlanmış ama yapılmamış (yapildimi=0) izlem kayıtları.
     */
    public function getVisitPendingThisMonth(): int {
        $izd = $this->sqlIzlemTarihiAsDate('i');
        $m0 = $this->db->quote(date('Y-m-01'));
        $m1 = $this->db->quote(date('Y-m-t'));
        return (int) $this->db->loadResultPrepared("SELECT COUNT(i.id) FROM #__izlemler i WHERE i.yapildimi = 0 AND {$izd} IS NOT NULL AND {$izd} BETWEEN {$m0} AND {$m1}" . TenantSqlHelper::andEquals('i')
        );
    }

    /**
     * Son N ay tamamlanmış izlem sayısı (ay bazlı).
     * @return array<int, object{ym: string, n: int}>
     */
    public function getCompletedVisitsByMonth(int $months = 12): array {
        $months = max(3, min(36, $months));
        $izd = $this->sqlIzlemTarihiAsDate('i');
        $from = $this->db->quote(date('Y-m-01', strtotime('-' . ($months - 1) . ' months')));
        $sql = "SELECT DATE_FORMAT({$izd}, '%Y-%m') AS ym, COUNT(i.id) AS n
            FROM #__izlemler i
            WHERE i.yapildimi = 1 AND {$izd} IS NOT NULL AND {$izd} >= {$from}" . TenantSqlHelper::andEquals('i') . "
            GROUP BY DATE_FORMAT({$izd}, '%Y-%m')
            ORDER BY ym ASC";
        $list = $this->db->fetchObjectListPrepared($sql);
        return is_array($list) ? $list : [];
    }

    /**
     * e-Rapor havuzu özet sayıları.
     */
    public function getEraporPoolStats(): object {
        $sql = "SELECT COUNT(id) AS toplam,
            IFNULL(SUM(CASE WHEN kayitlimi = 1 THEN 1 ELSE 0 END), 0) AS sistemde,
            IFNULL(SUM(CASE WHEN kayitlimi = 0 OR kayitlimi IS NULL THEN 1 ELSE 0 END), 0) AS disaridan
            FROM #__erapor WHERE 1=1" . TenantSqlHelper::andBare('kurum_id');
        $row = $this->db->fetchObjectPrepared($sql);
        if (!$row) {
            return (object) ['toplam' => 0, 'sistemde' => 0, 'disaridan' => 0];
        }
        return (object) [
            'toplam' => (int) $row->toplam,
            'sistemde' => (int) $row->sistemde,
            'disaridan' => (int) $row->disaridan,
        ];
    }

    /**
     * Aktif hastalarda pansuman=1 sayısı.
     */
    public function getPansumanActiveCount(): int {
        return (int) $this->db->loadResultPrepared("SELECT COUNT(id) FROM #__hastalar WHERE pasif = '0' AND pansuman = 1" . TenantSqlHelper::andBare()
        );
    }

    /**
     * Aktif hastalarda bağımlılık kodu dağılımı.
     */
    public function getBagimlilikActiveBreakdown(): array {
        $sql = "SELECT IFNULL(NULLIF(TRIM(bagimlilik), ''), '—') AS kod, COUNT(id) AS adet
            FROM #__hastalar WHERE pasif = '0'" . TenantSqlHelper::andBare() . "
            GROUP BY IFNULL(NULLIF(TRIM(bagimlilik), ''), '—')
            ORDER BY adet DESC";
        $list = $this->db->fetchObjectListPrepared($sql);
        return is_array($list) ? $list : [];
    }

    /**
     * Aktif hastaya bağlı açık planlı izlem (pizlemler) kayıt sayısı.
     */
    public function getPlannedOpenCount(): int {
        return (int) $this->db->loadResultPrepared("SELECT COUNT(p.id) FROM #__pizlemler p
            INNER JOIN #__hastalar h ON h.tckimlik = p.hastatckimlik
            WHERE h.pasif = '0'" . TenantSqlHelper::andEquals('h') . TenantSqlHelper::andEquals('p')
        );
    }

    /**
     * Bekleyen hasta (pasif -3) sayısı.
     */
    public function getWaitingPatientCount(): int {
        return (int) $this->db->loadResultPrepared("SELECT COUNT(id) FROM #__hastalar WHERE pasif = '-3'" . TenantSqlHelper::andBare()
        );
    }

    /**
     * Bu yıl tamamlanmış izlem toplamı.
     */
    public function getCompletedVisitsThisYear(): int {
        $izd = $this->sqlIzlemTarihiAsDate('i');
        $y = (int) date('Y');
        return (int) $this->db->loadResultPrepared("SELECT COUNT(i.id) FROM #__izlemler i WHERE i.yapildimi = 1 AND {$izd} IS NOT NULL AND YEAR({$izd}) = " . $y . TenantSqlHelper::andEquals('i')
        );
    }

    /** Kayıt tarihi referansı (`kayittarihi`; boş / 0000-00-00 → NULL). */
    public function sqlKayitTarihiExpr(string $alias = 'h'): string {
        return "NULLIF(NULLIF({$alias}.kayittarihi, ''), '0000-00-00')";
    }

    /** Aktif hastada güvence türü dağılımı (eski guvenceStats). */
    public function getGuvenceActiveDistribution(): array {
        $sql = "SELECT IFNULL(g.guvenceadi, 'Bilinmiyor') AS guvence_adi, COUNT(h.id) AS hastasayisi
            FROM #__hastalar h
            LEFT JOIN #__guvence g ON h.guvence = g.id
            WHERE h.pasif = '0'" . TenantSqlHelper::andEquals('h') . "
            GROUP BY h.guvence, g.guvenceadi
            ORDER BY hastasayisi DESC, guvence_adi ASC";
        $list = $this->db->fetchObjectListPrepared($sql);
        return is_array($list) ? $list : [];
    }

    /** Cihaz / geçici / sarf özeti (eski specialStats). */
    public function getSpecialEquipmentSummary(): object {
        $sumParts = ["SUM(CASE WHEN pasif = '0' THEN 1 ELSE 0 END) AS aktif_toplam"];
        foreach (PatientClinicalFlagsHelper::statsReportKeys() as $key) {
            $sumParts[] = "SUM(CASE WHEN pasif = '0' AND `{$key}` = 1 THEN 1 ELSE 0 END) AS `{$key}`";
        }
        $sql = 'SELECT ' . implode(', ', $sumParts) . ' FROM #__hastalar WHERE 1=1' . TenantSqlHelper::andBare();
        $row = $this->db->fetchObjectPrepared($sql);
        if (!$row) {
            return (object) [];
        }
        foreach ($row as $k => $v) {
            $row->$k = (int) $v;
        }
        return $row;
    }

    /**
     * Cihaz/özel durum alanına göre aktif hasta listesi.
     *
     * @return array<int, object>
     */
    public function getSpecialEquipmentPatientsByField(string $field, int $limit = 500): array {
        $allowed = PatientClinicalFlagsHelper::statsReportKeys();
        if (!in_array($field, $allowed, true)) {
            return [];
        }
        $limit = max(1, min(2000, $limit));
        $izK = $this->izSubK();
        $sql = "SELECT h.id, h.isim, h.soyisim, h.tckimlik, h.anneAdi, h.babaAdi, h.dogumtarihi, h.kayittarihi, h.ceptel1,
            il.adi AS ilce, m.adi AS mahalle,
            (SELECT izlemtarihi FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 1{$izK} ORDER BY izlemtarihi DESC LIMIT 1) AS sonizlemtarihi,
            (SELECT COUNT(id) FROM #__izlemler i2 WHERE i2.hastatckimlik = h.tckimlik AND i2.yapildimi = 1{$izK}) AS izlemsayisi,
            (SELECT COUNT(id) FROM #__izlemler i3 WHERE i3.hastatckimlik = h.tckimlik AND i3.yapildimi = 0{$izK}) AS yizlemsayisi,
            (SELECT COUNT(id) FROM #__pizlemler p WHERE p.hastatckimlik = h.tckimlik{$izK}) AS totalplanli
            FROM #__hastalar h
            LEFT JOIN #__adrestablosu il ON il.id = h.ilce
            LEFT JOIN #__adrestablosu m ON m.id = h.mahalle
            WHERE h.pasif = '0' AND h.`{$field}` = 1" . TenantSqlHelper::andEquals('h') . "
            ORDER BY h.isim ASC, h.soyisim ASC
            LIMIT " . (int) $limit;
        $list = $this->db->fetchObjectListPrepared($sql);
        return is_array($list) ? $list : [];
    }

    /** @return list<string> */
    public static function adresPatientOzellikFieldList(): array {
        return PatientClinicalFlagsHelper::statsReportKeys();
    }

    /** @return array<string, string> */
    public static function adresPatientOzellikLabels(): array {
        return PatientClinicalFlagsHelper::adresFilterLabels();
    }

    /**
     * Adres + özellik filtresi WHERE (yalnızca aktif hastalar).
     */
    private function buildAdresPatientFilterWhereSql(
        ?string $ilce,
        ?string $mahalle,
        ?string $sokak,
        ?string $kapino,
        ?string $ozellik
    ): string {
        $where = ["h.pasif = '0'"];
        if ($ilce !== null && $ilce !== '' && $ilce !== '0') {
            $where[] = 'h.ilce = ' . $this->db->quote($ilce);
        }
        if ($mahalle !== null && $mahalle !== '' && $mahalle !== '0') {
            $where[] = 'h.mahalle = ' . $this->db->quote($mahalle);
        }
        if ($sokak !== null && $sokak !== '' && $sokak !== '0') {
            $where[] = 'h.sokak = ' . $this->db->quote($sokak);
        }
        if ($kapino !== null && $kapino !== '' && $kapino !== '0') {
            $where[] = 'h.kapino = ' . $this->db->quote($kapino);
        }
        if ($ozellik !== null && $ozellik !== '' && in_array($ozellik, self::adresPatientOzellikFieldList(), true)) {
            $where[] = 'CAST(h.`' . $ozellik . '` AS SIGNED) != 0';
        }
        $this->mergeKurumWhere($where, 'h');

        return ' WHERE ' . implode(' AND ', $where);
    }

    /**
     * Controller ORDER BY parçası — başında ORDER BY yoksa ekler (eski/eksik çağrılara karşı).
     */
    private function ensureAdresPatientFilterOrderBy(string $orderByClause): string
    {
        $orderByClause = trim($orderByClause);
        if ($orderByClause === '') {
            return 'ORDER BY h.isim ASC, h.soyisim ASC';
        }
        if (preg_match('/^\s*ORDER\s+BY\b/i', $orderByClause)) {
            return $orderByClause;
        }

        return 'ORDER BY ' . $orderByClause;
    }

    public function countAdresPatientFilter(
        ?string $ilce,
        ?string $mahalle,
        ?string $sokak,
        ?string $kapino,
        ?string $ozellik
    ): int {
        $where = $this->buildAdresPatientFilterWhereSql($ilce, $mahalle, $sokak, $kapino, $ozellik);
        $sql = 'SELECT COUNT(h.id) FROM #__hastalar AS h' . $where;

        return (int) $this->db->loadResultPrepared($sql);
    }

    /**
     * @return list<object>
     */
    public function getAdresPatientFilterRows(
        ?string $ilce,
        ?string $mahalle,
        ?string $sokak,
        ?string $kapino,
        ?string $ozellik,
        string $orderByClause,
        int $limit,
        int $offset
    ): array {
        $where = $this->buildAdresPatientFilterWhereSql($ilce, $mahalle, $sokak, $kapino, $ozellik);
        $orderByClause = $this->ensureAdresPatientFilterOrderBy($orderByClause);
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);

        return PatientListSqlHelper::fetchPage(
            $this->db,
            $where,
            PatientListSqlHelper::addressJoinStatsAdres(),
            'h.id, h.isim, h.soyisim, h.tckimlik, h.cinsiyet, h.pasif, h.anneAdi, h.babaAdi, h.dogumtarihi, h.kayittarihi, h.ceptel1,
            ilc.adi AS ilceadi, m.adi AS mahalleadi, s.adi AS sokakadi, k.adi AS kapinoadi',
            $orderByClause,
            $limit,
            $offset,
            true,
            false
        );
    }

    /**
     * Barthel toplamına göre aktif hasta grupları (eski barthelStats).
     * @return object{toplam_hasta:int,ortalama_skor:float,g_0_20:int,g_21_61:int,g_62_90:int,g_91_99:int,g_100:int}
     */
    public function getBarthelDistribution(): object {
        $sumExpr = '(IFNULL(h.barbeslenme,0)+IFNULL(h.barbanyo,0)+IFNULL(h.barbakim,0)+IFNULL(h.bargiyinme,0)+IFNULL(h.barbarsak,0)+'
            . 'IFNULL(h.barmesane,0)+IFNULL(h.bartuvalet,0)+IFNULL(h.bartransfer,0)+IFNULL(h.barmobilite,0)+IFNULL(h.barmerdiven,0))';
        $sql = "SELECT
            COUNT(*) AS toplam_hasta,
            AVG({$sumExpr}) AS ortalama_skor,
            SUM(CASE WHEN {$sumExpr} <= 20 THEN 1 ELSE 0 END) AS g_0_20,
            SUM(CASE WHEN {$sumExpr} BETWEEN 21 AND 61 THEN 1 ELSE 0 END) AS g_21_61,
            SUM(CASE WHEN {$sumExpr} BETWEEN 62 AND 90 THEN 1 ELSE 0 END) AS g_62_90,
            SUM(CASE WHEN {$sumExpr} BETWEEN 91 AND 99 THEN 1 ELSE 0 END) AS g_91_99,
            SUM(CASE WHEN {$sumExpr} >= 100 THEN 1 ELSE 0 END) AS g_100
            FROM #__hastalar h WHERE h.pasif = '0'" . TenantSqlHelper::andEquals('h');
        $o = $this->db->fetchObjectPrepared($sql);
        if (!$o) {
            return (object) [
                'toplam_hasta' => 0, 'ortalama_skor' => 0.0, 'g_0_20' => 0, 'g_21_61' => 0, 'g_62_90' => 0, 'g_91_99' => 0, 'g_100' => 0,
            ];
        }
        return (object) [
            'toplam_hasta' => (int) $o->toplam_hasta,
            'ortalama_skor' => round((float) $o->ortalama_skor, 2),
            'g_0_20' => (int) $o->g_0_20,
            'g_21_61' => (int) $o->g_21_61,
            'g_62_90' => (int) $o->g_62_90,
            'g_91_99' => (int) $o->g_91_99,
            'g_100' => (int) $o->g_100,
        ];
    }

    /** İlk izlem, kayıt tarihinden önce (eski tarihHata). */
    public function getChronologyRegistrationVsFirstVisit(): array {
        $izd = $this->sqlIzlemTarihiAsDate('i');
        $kayit = $this->sqlKayitTarihiExpr('h');
        $izK = $this->izSubK();
        $sql = "SELECT h.id, h.isim, h.soyisim, h.kayittarihi,
            h.tckimlik,
            DATE_FORMAT(MIN({$izd}), '%d.%m.%Y') AS ilk_izlem_tr,
            DATE_FORMAT({$kayit}, '%d.%m.%Y') AS kayit_tr,
            (SELECT COUNT(id) FROM #__izlemler i2 WHERE i2.hastatckimlik = h.tckimlik AND i2.yapildimi = 1{$izK}) AS izlemsayisi,
            (SELECT COUNT(id) FROM #__izlemler i3 WHERE i3.hastatckimlik = h.tckimlik AND i3.yapildimi = 0{$izK}) AS yizlemsayisi,
            (SELECT COUNT(id) FROM #__pizlemler p WHERE p.hastatckimlik = h.tckimlik{$izK}) AS totalplanli,
            DATEDIFF({$kayit}, MIN({$izd})) AS gun_fark
            FROM #__hastalar h
            INNER JOIN #__izlemler i ON h.tckimlik = i.hastatckimlik AND i.yapildimi = 1
            WHERE h.pasif = '0' AND {$kayit} IS NOT NULL" . TenantSqlHelper::andEquals('h') . TenantSqlHelper::andEquals('i') . "
            GROUP BY h.id, h.isim, h.soyisim, h.kayittarihi, h.tckimlik
            HAVING MIN({$izd}) IS NOT NULL AND MIN({$izd}) < DATE({$kayit})
            ORDER BY MIN({$izd}) ASC, h.isim ASC, h.soyisim ASC";
        $list = $this->db->fetchObjectListPrepared($sql);
        return is_array($list) ? $list : [];
    }

    /**
     * Hizmet süresi alt sorgusu (risk grubu: KRITIK / KRONIK / STANDART).
     */
    private function sqlWorkloadContinuitySubquery(): string
    {
        $izd = $this->sqlIzlemTarihiAsDate('i');
        $kayit = $this->sqlKayitTarihiExpr('h');
        $izK = $this->izSubK();

        return "SELECT h.id, h.isim, h.soyisim, h.tckimlik, h.dogumtarihi, h.anneAdi, h.babaAdi, h.ceptel1,
            h.kayittarihi, h.randevutarihi, h.pasif, h.cinsiyet,
            il.adi AS ilce_adi, m.adi AS mahalle_adi,
            {$kayit} AS kayit_tarihi,
            MAX(CASE WHEN i.yapildimi = 1 THEN {$izd} END) AS sonizlemtarihi,
            (SELECT COUNT(id) FROM #__izlemler i2 WHERE i2.hastatckimlik = h.tckimlik AND i2.yapildimi = 1{$izK}) AS izlemsayisi,
            (SELECT COUNT(id) FROM #__izlemler i3 WHERE i3.hastatckimlik = h.tckimlik AND i3.yapildimi = 0{$izK}) AS yizlemsayisi,
            (SELECT COUNT(id) FROM #__pizlemler p WHERE p.hastatckimlik = h.tckimlik{$izK}) AS totalplanli,
            DATEDIFF(CURDATE(), DATE({$kayit})) AS hizmet_suresi_gun,
            CASE
                WHEN DATEDIFF(CURDATE(), DATE({$kayit})) >= 1000 THEN 'KRITIK'
                WHEN DATEDIFF(CURDATE(), DATE({$kayit})) >= 365 THEN 'KRONIK'
                ELSE 'STANDART'
            END AS hizmet_durumu
            FROM #__hastalar h
            LEFT JOIN #__izlemler i ON h.tckimlik = i.hastatckimlik
            LEFT JOIN #__adrestablosu il ON il.id = h.ilce
            LEFT JOIN #__adrestablosu m ON m.id = h.mahalle
            WHERE h.pasif = '0' AND {$kayit} IS NOT NULL" . TenantSqlHelper::andEquals('h') . "
            GROUP BY h.id, h.isim, h.soyisim, h.tckimlik, h.dogumtarihi, h.anneAdi, h.babaAdi, h.ceptel1,
                h.kayittarihi, h.randevutarihi, h.pasif, h.cinsiyet, h.ilce, h.mahalle";
    }

    /**
     * @return array{KRITIK:int,KRONIK:int,STANDART:int}
     */
    public function getWorkloadContinuityGroupCounts(): array
    {
        $cacheKey = $this->statsCacheKey('workload_group_counts');
        $cached = StatsQueryCache::get($cacheKey);
        if (is_array($cached) && isset($cached['KRITIK'], $cached['KRONIK'], $cached['STANDART'])) {
            return [
                'KRITIK' => (int) $cached['KRITIK'],
                'KRONIK' => (int) $cached['KRONIK'],
                'STANDART' => (int) $cached['STANDART'],
            ];
        }

        $inner = $this->sqlWorkloadContinuitySubquery();
        $o = $this->db->fetchObjectPrepared("SELECT
                SUM(CASE WHEN w.hizmet_durumu = 'KRITIK' THEN 1 ELSE 0 END) AS KRITIK,
                SUM(CASE WHEN w.hizmet_durumu = 'KRONIK' THEN 1 ELSE 0 END) AS KRONIK,
                SUM(CASE WHEN w.hizmet_durumu = 'STANDART' THEN 1 ELSE 0 END) AS STANDART
             FROM ({$inner}) AS w"
        );

        $counts = [
            'KRITIK' => (int) ($o->KRITIK ?? 0),
            'KRONIK' => (int) ($o->KRONIK ?? 0),
            'STANDART' => (int) ($o->STANDART ?? 0),
        ];
        StatsQueryCache::set($cacheKey, $counts);

        return $counts;
    }

    /**
     * @return array<int, object>
     */
    public function getWorkloadContinuityRowsByGroup(
        string $group,
        int $limit,
        int $offset,
        string $orderFragment = 'w.hizmet_suresi_gun DESC, w.isim ASC, w.soyisim ASC'
    ): array
    {
        $allowed = ['KRITIK', 'KRONIK', 'STANDART'];
        if (!in_array($group, $allowed, true)) {
            return [];
        }
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $inner = $this->sqlWorkloadContinuitySubquery();
        $gq = $this->db->quote($group);
        $orderFragment = trim($orderFragment) !== '' ? $orderFragment : 'w.hizmet_suresi_gun DESC, w.isim ASC, w.soyisim ASC';
        $sql = "SELECT w.* FROM ({$inner}) AS w
            WHERE w.hizmet_durumu = {$gq}
            ORDER BY {$orderFragment}
            LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset;
        $list = $this->db->fetchObjectListPrepared($sql);

        return is_array($list) ? $list : [];
    }

    /**
     * Hizmet süresi ve son izlem (eski isYukuHesapla — tüm aktif hastalar).
     * @return array<int,object>
     */
    public function getWorkloadContinuityRows(): array {
        $inner = $this->sqlWorkloadContinuitySubquery();
        $sql = "SELECT w.* FROM ({$inner}) AS w
            ORDER BY w.hizmet_suresi_gun DESC, w.isim ASC, w.soyisim ASC";
        $list = $this->db->fetchObjectListPrepared($sql);
        return is_array($list) ? $list : [];
    }

    /**
     * Tarih aralığında tamamlanmış izlemlerde yapılan işlem adetleri (eski islemGetir).
     * @return array<int,object{id:int,islemadi:string,adet:int}>
     */
    public function getProcedureCountsFromVisits(?string $dateFrom = null, ?string $dateTo = null): array {
        $izd = $this->sqlIzlemTarihiAsDate('i');
        $where = ["i.yapildimi = 1", "{$izd} IS NOT NULL", "TRIM(COALESCE(i.yapilan,'')) != ''"];
        if ($dateFrom !== null && $dateFrom !== '') {
            $where[] = "{$izd} >= " . $this->db->quote($dateFrom);
        }
        if ($dateTo !== null && $dateTo !== '') {
            $where[] = "{$izd} <= " . $this->db->quote($dateTo);
        }
        $this->mergeKurumWhere($where, 'i');
        $w = implode(' AND ', $where);
        $raw = $this->db->fetchColumnListPrepared("SELECT i.yapilan FROM #__izlemler i WHERE {$w}");
        $counts = [];
        if (is_array($raw)) {
            foreach ($raw as $cell) {
                if ($cell === null || $cell === '') {
                    continue;
                }
                $parts = preg_split('/\s*,\s*/', (string) $cell, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($parts as $p) {
                    $id = (int) trim($p);
                    if ($id > 0) {
                        $counts[$id] = ($counts[$id] ?? 0) + 1;
                    }
                }
            }
        }
        $islemMap = CatalogScopeSqlHelper::loadIslemIdNameMap();
        $out = [];
        foreach ($islemMap as $id => $islemadi) {
            $out[] = (object) [
                'id' => (int) $id,
                'islemadi' => $islemadi,
                'adet' => (int) ($counts[$id] ?? 0),
            ];
        }
        usort($out, fn ($a, $b) => $b->adet <=> $a->adet);
        return $out;
    }

    /**
     * Tarih aralığında izlem yapan personel adetleri (eski personelGetir).
     * @return array<int,object{id:int,name:string,unvan:string,adet:int}>
     */
    public function getPersonnelCountsFromVisits(?string $dateFrom = null, ?string $dateTo = null): array {
        $izd = $this->sqlIzlemTarihiAsDate('i');
        $where = ["i.yapildimi = 1", "{$izd} IS NOT NULL"];
        if ($dateFrom !== null && $dateFrom !== '') {
            $where[] = "{$izd} >= " . $this->db->quote($dateFrom);
        }
        if ($dateTo !== null && $dateTo !== '') {
            $where[] = "{$izd} <= " . $this->db->quote($dateTo);
        }
        $this->mergeKurumWhere($where, 'i');
        $w = implode(' AND ', $where);
        $raw = $this->db->fetchColumnListPrepared("SELECT i.izlemiyapan FROM #__izlemler i WHERE {$w} AND i.izlemiyapan IS NOT NULL AND TRIM(CAST(i.izlemiyapan AS CHAR)) != ''"
        );
        $counts = [];
        if (is_array($raw)) {
            foreach ($raw as $cell) {
                $parts = preg_split('/\s*,\s*/', (string) $cell, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($parts as $p) {
                    $id = (int) trim($p);
                    if ($id > 0) {
                        $counts[$id] = ($counts[$id] ?? 0) + 1;
                    }
                }
            }
        }
        $users = $this->db->fetchObjectListPrepared('SELECT id, name, unvan FROM #__users ORDER BY name ASC');
        $out = [];
        if (is_array($users)) {
            foreach ($users as $u) {
                $id = (int) $u->id;
                $adet = (int) ($counts[$id] ?? 0);
                if ($adet > 0) {
                    $out[] = (object) [
                        'id' => $id,
                        'name' => (string) $u->name,
                        'unvan' => (string) ($u->unvan ?? ''),
                        'adet' => $adet,
                    ];
                }
            }
        }
        usort($out, fn ($a, $b) => $b->adet <=> $a->adet);
        return $out;
    }

    /**
     * İzlem kayıtlarındaki konsültasyon alanları için aylık döküm.
     * brans, kons_istekler (CSV) ve kons_brans_istek (JSON) birlikte değerlendirilir.
     *
     * @return array{
     *   month_rows: array<int, object>,
     *   month_labels: array<string, string>,
     *   brans_totals: array<string, int>,
     *   istek_totals: array<string, int>,
     *   brans_top: array<string, int>,
     *   istek_top: array<string, int>,
     *   pair_totals: array<string, int>,
     *   pair_top: array<string, int>
     * }
     */
    public function getVisitConsultationMonthlyBreakdown(?string $dateFrom = null, ?string $dateTo = null): array {
        $izd = $this->sqlIzlemTarihiAsDate('i');
        $where = ["i.yapildimi = 1", "{$izd} IS NOT NULL"];
        $konsIslemId = IslemIdSettings::resolvedInt('konsultasyon_islem_id');
        if ($konsIslemId > 0) {
            $where[] = 'FIND_IN_SET(' . $konsIslemId . ", REPLACE(i.yapilan, ' ', ''))";
        }
        if ($dateFrom !== null && $dateFrom !== '') {
            $where[] = "{$izd} >= " . $this->db->quote($dateFrom);
        }
        if ($dateTo !== null && $dateTo !== '') {
            $where[] = "{$izd} <= " . $this->db->quote($dateTo);
        }
        $this->mergeKurumWhere($where, 'i');
        $w = implode(' AND ', $where);

        $rows = $this->db->fetchObjectListPrepared("SELECT DATE_FORMAT({$izd}, '%Y-%m') AS ym, i.brans, i.kons_istekler, i.kons_brans_istek
             FROM #__izlemler i
             WHERE {$w}
             ORDER BY ym ASC, i.id ASC"
        ) ?: [];

        $bransMap = CatalogScopeSqlHelper::loadBransIdNameMap();
        $istekMap = CatalogScopeSqlHelper::loadIstekIdNameMap();

        $monthRows = [];
        $monthOrder = [];
        $bransTotals = [];
        $istekTotals = [];
        $pairTotals = [];
        foreach ($rows as $row) {
            $ym = (string) ($row->ym ?? '');
            if ($ym === '') {
                continue;
            }
            if (!isset($monthRows[$ym])) {
                $monthRows[$ym] = (object) [
                    'ym' => $ym,
                    'izlem_adet' => 0,
                    'bransli_izlem' => 0,
                    'istekli_izlem' => 0,
                    'ciftli_izlem' => 0,
                    'brans_counts' => [],
                    'istek_counts' => [],
                    'pair_counts' => [],
                ];
                $monthOrder[] = $ym;
            }
            $monthRows[$ym]->izlem_adet++;

            $bransIds = array_values(array_unique(array_filter(array_map('intval', preg_split(
                '/\s*,\s*/',
                str_replace(' ', '', (string) ($row->brans ?? '')),
                -1,
                PREG_SPLIT_NO_EMPTY
            )))));
            if ($bransIds !== []) {
                $monthRows[$ym]->bransli_izlem++;
                foreach ($bransIds as $bid) {
                    $label = $bransMap[$bid] ?? ('Branş #' . $bid);
                    $monthRows[$ym]->brans_counts[$label] = ($monthRows[$ym]->brans_counts[$label] ?? 0) + 1;
                    $bransTotals[$label] = ($bransTotals[$label] ?? 0) + 1;
                }
            }

            $istekIds = array_values(array_unique(array_filter(array_map('intval', preg_split(
                '/\s*,\s*/',
                str_replace(' ', '', (string) ($row->kons_istekler ?? '')),
                -1,
                PREG_SPLIT_NO_EMPTY
            )))));
            if ($istekIds !== []) {
                $monthRows[$ym]->istekli_izlem++;
                foreach ($istekIds as $iid) {
                    $label = $istekMap[$iid] ?? ('İstek #' . $iid);
                    $monthRows[$ym]->istek_counts[$label] = ($monthRows[$ym]->istek_counts[$label] ?? 0) + 1;
                    $istekTotals[$label] = ($istekTotals[$label] ?? 0) + 1;
                }
            }

            $pairMap = KonsBransIstekHelper::resolveMap(
                (string) ($row->kons_brans_istek ?? ''),
                (string) ($row->brans ?? ''),
                (string) ($row->kons_istekler ?? '')
            );
            $pairLabels = KonsBransIstekHelper::pairedLabelsFromMap($pairMap, $bransMap, $istekMap);
            if ($pairLabels !== []) {
                $monthRows[$ym]->ciftli_izlem++;
                foreach ($pairLabels as $pairLabel) {
                    $monthRows[$ym]->pair_counts[$pairLabel] = ($monthRows[$ym]->pair_counts[$pairLabel] ?? 0) + 1;
                    $pairTotals[$pairLabel] = ($pairTotals[$pairLabel] ?? 0) + 1;
                }
            }
        }

        $monthLabels = [];
        foreach ($monthOrder as $ym) {
            $dt = \DateTimeImmutable::createFromFormat('Y-m', $ym);
            $monthLabels[$ym] = $dt ? $dt->format('m.Y') : $ym;
        }

        arsort($bransTotals);
        arsort($istekTotals);
        arsort($pairTotals);

        return [
            'month_rows' => array_values(array_map(fn ($ym) => $monthRows[$ym], $monthOrder)),
            'month_labels' => $monthLabels,
            'brans_totals' => $bransTotals,
            'istek_totals' => $istekTotals,
            'brans_top' => array_slice($bransTotals, 0, 10, true),
            'istek_top' => array_slice($istekTotals, 0, 10, true),
            'pair_totals' => $pairTotals,
            'pair_top' => array_slice($pairTotals, 0, 15, true),
        ];
    }

    /**
     * e-Rapor işaretli aktif hastalar (eski Eraporlular — kısaltılmış liste).
     */
    public function countEraporPatients(?string $ilce = null, ?string $mahalle = null): int {
        $where = ["h.pasif = '0'", "(TRIM(CAST(h.erapor AS CHAR)) = '1' OR h.erapor = 1)"];
        $this->mergeKurumWhere($where, 'h');
        if ($ilce !== null && $ilce !== '') {
            $where[] = 'h.ilce = ' . $this->db->quote($ilce);
        }
        if ($mahalle !== null && $mahalle !== '') {
            $where[] = 'h.mahalle = ' . $this->db->quote($mahalle);
        }
        $w = implode(' AND ', $where);
        return (int) $this->db->loadResultPrepared("SELECT COUNT(h.id) FROM #__hastalar h WHERE {$w}");
    }

    public function getEraporPatientRows(
        ?string $ilce,
        ?string $mahalle,
        int $limit = 50,
        int $offset = 0,
        string $orderFragment = 'h.isim ASC, h.soyisim ASC'
    ): array {
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $where = ["h.pasif = '0'", "(TRIM(CAST(h.erapor AS CHAR)) = '1' OR h.erapor = 1)"];
        $this->mergeKurumWhere($where, 'h');
        if ($ilce !== null && $ilce !== '') {
            $where[] = 'h.ilce = ' . $this->db->quote($ilce);
        }
        if ($mahalle !== null && $mahalle !== '') {
            $where[] = 'h.mahalle = ' . $this->db->quote($mahalle);
        }
        $w = implode(' AND ', $where);
        $sql = "SELECT h.id, h.isim, h.soyisim, h.tckimlik, h.cinsiyet, h.pasif,
            h.anneAdi, h.babaAdi, h.dogumtarihi, h.ceptel1, h.kayittarihi, h.randevutarihi,
            il.adi AS ilce_adi, m.adi AS mahalle_adi,
            (SELECT izlemtarihi FROM #__izlemler i3 WHERE i3.hastatckimlik = h.tckimlik AND i3.yapildimi = 1 ORDER BY i3.izlemtarihi DESC LIMIT 1) AS sonizlemtarihi,
            (SELECT COUNT(id) FROM #__izlemler i4 WHERE i4.hastatckimlik = h.tckimlik AND i4.yapildimi = 1) AS izlemsayisi,
            (SELECT COUNT(id) FROM #__izlemler i5 WHERE i5.hastatckimlik = h.tckimlik AND i5.yapildimi = 0) AS yizlemsayisi,
            (SELECT COUNT(id) FROM #__pizlemler p WHERE p.hastatckimlik = h.tckimlik) AS totalplanli
            FROM #__hastalar h
            LEFT JOIN #__adrestablosu m ON m.id = h.mahalle
            LEFT JOIN #__adrestablosu il ON il.id = h.ilce
            WHERE {$w}
            ORDER BY {$orderFragment}
            LIMIT {$offset}, {$limit}";
        $list = $this->db->fetchObjectListPrepared($sql);
        return is_array($list) ? $list : [];
    }

    public function countMamaRaporRows(?string $dateFrom, ?string $dateTo): int {
        $where = ["h.mama = 1", "h.pasif = '0'", 'h.mamaraporbitis IS NOT NULL'];
        $this->mergeKurumWhere($where, 'h');
        if ($dateFrom) {
            $where[] = 'h.mamaraporbitis >= ' . $this->db->quote($dateFrom);
        }
        if ($dateTo) {
            $where[] = 'h.mamaraporbitis <= ' . $this->db->quote($dateTo);
        }
        $w = implode(' AND ', $where);
        return (int) $this->db->loadResultPrepared("SELECT COUNT(h.id) FROM #__hastalar h WHERE {$w}");
    }

    public function getMamaRaporRows(
        ?string $dateFrom,
        ?string $dateTo,
        int $limit = 50,
        int $offset = 0,
        string $orderFragment = 'h.mamaraporbitis ASC, h.isim ASC, h.soyisim ASC'
    ): array {
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $where = ["h.mama = 1", "h.pasif = '0'", 'h.mamaraporbitis IS NOT NULL'];
        $this->mergeKurumWhere($where, 'h');
        if ($dateFrom) {
            $where[] = 'h.mamaraporbitis >= ' . $this->db->quote($dateFrom);
        }
        if ($dateTo) {
            $where[] = 'h.mamaraporbitis <= ' . $this->db->quote($dateTo);
        }
        $w = implode(' AND ', $where);

        return $this->fetchSupplyReportPatientList($w, $orderFragment, $limit, $offset);
    }

    public function countBezRaporRows(?string $dateFrom, ?string $dateTo): int {
        $where = ['h.bezrapor = 1', "h.pasif = '0'", 'h.bezraporbitis IS NOT NULL'];
        $this->mergeKurumWhere($where, 'h');
        if ($dateFrom) {
            $where[] = 'h.bezraporbitis >= ' . $this->db->quote($dateFrom);
        }
        if ($dateTo) {
            $where[] = 'h.bezraporbitis <= ' . $this->db->quote($dateTo);
        }
        $w = implode(' AND ', $where);
        return (int) $this->db->loadResultPrepared("SELECT COUNT(h.id) FROM #__hastalar h WHERE {$w}");
    }

    public function getBezRaporRows(
        ?string $dateFrom,
        ?string $dateTo,
        int $limit = 50,
        int $offset = 0,
        string $orderFragment = 'h.bezraporbitis ASC, h.isim ASC, h.soyisim ASC'
    ): array {
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $where = ['h.bezrapor = 1', "h.pasif = '0'", 'h.bezraporbitis IS NOT NULL'];
        $this->mergeKurumWhere($where, 'h');
        if ($dateFrom) {
            $where[] = 'h.bezraporbitis >= ' . $this->db->quote($dateFrom);
        }
        if ($dateTo) {
            $where[] = 'h.bezraporbitis <= ' . $this->db->quote($dateTo);
        }
        $w = implode(' AND ', $where);

        return $this->fetchSupplyReportPatientList($w, $orderFragment, $limit, $offset);
    }

    /**
     * Mama/bez rapor listesi — Patient unified alanları (deferred join).
     *
     * @return list<object>
     */
    private function fetchSupplyReportPatientList(string $whereSql, string $orderFragment, int $limit, int $offset): array
    {
        $orderFragment = trim($orderFragment) !== '' ? $orderFragment : 'h.isim ASC, h.soyisim ASC';

        return PatientListSqlHelper::fetchPage(
            $this->db,
            'WHERE ' . $whereSql,
            PatientListSqlHelper::addressJoinIlMah(),
            'h.id, h.isim, h.soyisim, h.tckimlik, h.cinsiyet, h.pasif,
            h.anneAdi, h.babaAdi, h.dogumtarihi, h.ceptel1, h.kayittarihi, h.randevutarihi,
            h.mamaraporbitis, h.bezraporbitis,
            il.adi AS ilce_adi, m.adi AS mahalle_adi',
            $orderFragment,
            $limit,
            $offset,
            true,
            false
        );
    }

    public function countSondaChangeRows(?string $dateFrom, ?string $dateTo): int {
        $sondaTarih = $this->sqlSondatarihiAsDate('h');
        $degisimTarih = $this->sqlSondaDegisimTarihiExpr('h');
        $where = ["h.sonda = 1", "h.pasif = '0'", "{$sondaTarih} IS NOT NULL"];
        $this->mergeKurumWhere($where, 'h');
        if ($dateFrom) {
            $where[] = "{$degisimTarih} >= " . $this->db->quote($dateFrom);
        }
        if ($dateTo) {
            $where[] = "{$degisimTarih} <= " . $this->db->quote($dateTo);
        }
        $w = implode(' AND ', $where);
        return (int) $this->db->loadResultPrepared("SELECT COUNT(h.id) FROM #__hastalar h WHERE {$w}");
    }

    public function getSondaChangeRows(
        ?string $dateFrom,
        ?string $dateTo,
        int $limit = 50,
        int $offset = 0,
        ?string $orderFragment = null
    ): array {
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $sondaTarih = $this->sqlSondatarihiAsDate('h');
        $degisimTarih = $this->sqlSondaDegisimTarihiExpr('h');
        $where = ["h.sonda = 1", "h.pasif = '0'", "{$sondaTarih} IS NOT NULL"];
        $this->mergeKurumWhere($where, 'h');
        if ($dateFrom) {
            $where[] = "{$degisimTarih} >= " . $this->db->quote($dateFrom);
        }
        if ($dateTo) {
            $where[] = "{$degisimTarih} <= " . $this->db->quote($dateTo);
        }
        $w = implode(' AND ', $where);
        if ($orderFragment === null || trim($orderFragment) === '') {
            $orderFragment = "{$degisimTarih} ASC, h.isim ASC, h.soyisim ASC";
        }

        return PatientListSqlHelper::fetchPage(
            $this->db,
            'WHERE ' . $w,
            PatientListSqlHelper::addressJoinIlMah(),
            "h.id, h.isim, h.soyisim, h.tckimlik, h.cinsiyet, h.pasif,
            h.anneAdi, h.babaAdi, h.dogumtarihi, h.ceptel1, h.kayittarihi,
            {$degisimTarih} AS sonda_degisim_tarihi,
            il.adi AS ilce_adi, m.adi AS mahalle_adi",
            $orderFragment,
            $limit,
            $offset,
            true,
            false,
            false
        );
    }

    /**
     * Hasta doğum tarihini DATE'e çevirir.
     */
    private function sqlDogumTarihiAsDate(string $alias): string {
        return "COALESCE(
            STR_TO_DATE(NULLIF(TRIM({$alias}.dogumtarihi), ''), '%Y-%m-%d'),
            STR_TO_DATE(NULLIF(TRIM({$alias}.dogumtarihi), ''), '%d.%m.%Y'),
            STR_TO_DATE(NULLIF(TRIM({$alias}.dogumtarihi), ''), '%d-%m-%Y')
        )";
    }

    /**
     * Sonda takılma/değişim kayıt tarihini DATE'e çevirir.
     */
    private function sqlSondatarihiAsDate(string $alias = 'h'): string {
        $col = "{$alias}.sondatarihi";
        return "COALESCE(
            STR_TO_DATE(NULLIF(TRIM({$col}), ''), '%Y-%m-%d'),
            STR_TO_DATE(NULLIF(TRIM({$col}), ''), '%d.%m.%Y'),
            STR_TO_DATE(NULLIF(TRIM({$col}), ''), '%d-%m-%Y')
        )";
    }

    /** Planlanan sonda değişim tarihi: sondatarihi + 1 ay. */
    public function sondaDegisimTarihiOrderExpr(string $alias = 'h'): string {
        return $this->sqlSondaDegisimTarihiExpr($alias);
    }

    private function sqlSondaDegisimTarihiExpr(string $alias = 'h'): string {
        return 'DATE_ADD(' . $this->sqlSondatarihiAsDate($alias) . ', INTERVAL 1 MONTH)';
    }

    /**
     * Pasife alma tarihini DATE'e çevirir ($alias boşsa sütun adı `pasiftarihi`).
     */
    public function sqlPasifTarihiExpr(string $alias = ''): string {
        $col = $alias !== '' ? "{$alias}.pasiftarihi" : 'pasiftarihi';

        return "COALESCE(
            STR_TO_DATE(NULLIF(TRIM({$col}), ''), '%Y-%m-%d'),
            STR_TO_DATE(NULLIF(TRIM({$col}), ''), '%d.%m.%Y'),
            STR_TO_DATE(NULLIF(TRIM({$col}), ''), '%d-%m-%Y')
        )";
    }

    private function sqlPasifTarihiAsDate(): string {
        return $this->sqlPasifTarihiExpr('');
    }

    /**
     * Bugün doğum günü olan aktif hastalar.
     */
    public function getTodaysBirthdays(): array {
        $izK = $this->izSubK();
        $query = "SELECT 
                    h.id, 
                    h.isim, 
                    h.soyisim, 
                    h.cinsiyet, 
                    h.tckimlik, 
                    h.dogumtarihi, 
                    (SELECT COUNT(id) FROM #__izlemler i2 WHERE i2.hastatckimlik = h.tckimlik AND i2.yapildimi = 1{$izK}) AS izlemsayisi,
                    (SELECT COUNT(id) FROM #__izlemler i3 WHERE i3.hastatckimlik = h.tckimlik AND i3.yapildimi = 0{$izK}) AS yizlemsayisi,
                    (SELECT COUNT(id) FROM #__pizlemler p WHERE p.hastatckimlik = h.tckimlik{$izK}) AS totalplanli,
                    m.adi AS mahalle, 
                    ilc.adi AS ilce 
                  FROM #__hastalar AS h 
                  LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle 
                  LEFT JOIN #__adrestablosu AS ilc ON ilc.id = h.ilce 
                  WHERE h.pasif = 0 
                  AND MONTH(h.dogumtarihi) = MONTH(NOW()) 
                  AND DAY(h.dogumtarihi) = DAY(NOW())" . TenantSqlHelper::andEquals('h');
        $list = $this->db->fetchObjectListPrepared($query);
        return is_array($list) ? $list : [];
    }

    /**
     * Aktif hastalar için yaş grupları (cinsiyete göre).
     */
    public function getAgeGroups(): array {
        $bands = AgeBandHelper::sqlSumCaseLines('dogumtarihi');
        $query = "SELECT 
            {$bands},
            cinsiyet
            FROM #__hastalar
            WHERE pasif = 0" . TenantSqlHelper::andBare() . "
            GROUP BY cinsiyet";
        $list = $this->db->fetchObjectListPrepared($query);
        return is_array($list) ? $list : [];
    }

    /**
     * Belirli ay aralığında tamamlanmış izlem: benzersiz hasta + izlem adedi.
     */
    private function monthlyFollowUpStatsBetween(string $startYmd, string $endYmd): object {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startYmd) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endYmd)) {
            return (object) ['toplamhasta' => 0, 'toplamizlem' => 0];
        }
        $izd = $this->sqlIzlemTarihiAsDate('i');
        $query = 'SELECT COUNT(DISTINCT i.hastatckimlik) AS toplamhasta, COUNT(i.id) AS toplamizlem
                  FROM #__izlemler AS i
                  WHERE i.yapildimi = 1
                  AND ' . $izd . ' BETWEEN ' . $this->db->quote($startYmd) . ' AND ' . $this->db->quote($endYmd) . TenantSqlHelper::andEquals('i');
        $row = $this->db->fetchObjectPrepared($query);
        if (!$row) {
            return (object) ['toplamhasta' => 0, 'toplamizlem' => 0];
        }

        return $row;
    }

    /**
     * Bu ay tamamlanmış izlem istatistikleri.
     */
    public function getMonthlyFollowUpStats(): object {
        return $this->monthlyFollowUpStatsBetween(
            date('Y-m-01'),
            date('Y-m-t')
        );
    }

    /**
     * Son N ay tamamlanmış izlem özeti (sıklık = izlem / hasta).
     *
     * @return array<int, object{ym: string, yil: int, ay: int, toplamhasta: int, toplamizlem: int, siklik: float}>
     */
    public function getMonthlyFollowUpStatsLastMonths(int $months = 6): array {
        $months = max(1, min(24, $months));
        $out = [];
        for ($i = 0; $i < $months; $i++) {
            $ts = strtotime('first day of -' . $i . ' months');
            if ($ts === false) {
                continue;
            }
            $start = date('Y-m-01', $ts);
            $end = date('Y-m-t', $ts);
            $row = $this->monthlyFollowUpStatsBetween($start, $end);
            $th = (int) ($row->toplamhasta ?? 0);
            $ti = (int) ($row->toplamizlem ?? 0);
            $out[] = (object) [
                'ym' => date('Y-m', $ts),
                'yil' => (int) date('Y', $ts),
                'ay' => (int) date('n', $ts),
                'toplamhasta' => $th,
                'toplamizlem' => $ti,
                'siklik' => $th > 0 ? round($ti / $th, 2) : 0.0,
            ];
        }

        return $out;
    }

    /**
     * Pasife alınanların çıkış nedeni dağılımı (pasiftarihi aralığı).
     */
    public function getExitReasons(string $dateFrom, string $dateTo): array {
        $from = $dateFrom;
        $to = $dateTo;
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $pt = $this->sqlPasifTarihiAsDate();
        $fromQ = $this->db->quote($from);
        $toQ = $this->db->quote($to);
        $query = "SELECT pasifnedeni, COUNT(id) AS sayi
                  FROM #__hastalar
                  WHERE pasif = 1
                  AND pasifnedeni IS NOT NULL AND TRIM(CAST(pasifnedeni AS CHAR)) NOT IN ('', '0')
                  AND {$pt} IS NOT NULL
                  AND {$pt} BETWEEN {$fromQ} AND {$toQ}" . TenantSqlHelper::andBare() . "
                  GROUP BY pasifnedeni
                  ORDER BY sayi DESC";
        $list = $this->db->fetchObjectListPrepared($query);
        return is_array($list) ? $list : [];
    }

    /**
     * Bu ay izlemi yapılan hastaların yaş dağılımı.
     */
    public function getMonthlyFollowUpAgeGroups(): object {
        $izd = $this->sqlIzlemTarihiAsDate('i');
        $dob = $this->sqlDogumTarihiAsDate('h');
        $bands = AgeBandHelper::sqlSumCaseLines($dob);
        $iKurum = TenantSqlHelper::andEquals('i');
        $query = "SELECT 
            {$bands}
            FROM (
                SELECT DISTINCT i.hastatckimlik FROM #__izlemler AS i 
                WHERE {$izd} BETWEEN DATE_FORMAT(NOW(), '%Y-%m-01') AND LAST_DAY(NOW())
                AND i.yapildimi = 1{$iKurum}
            ) as sub
            LEFT JOIN #__hastalar AS h ON h.tckimlik = sub.hastatckimlik 
            WHERE h.dogumtarihi IS NOT NULL AND TRIM(h.dogumtarihi) != '' AND {$dob} IS NOT NULL" . TenantSqlHelper::andEquals('h');
        $res = $this->db->fetchObjectPrepared($query);
        if (!$res || $res->g01 === null) {
            return AgeBandHelper::emptyObject();
        }
        return $res;
    }

    /**
     * Dashboard için genel istatistik seti.
     */
    /**
     * Genel özet + seçilen ayda yeni kayıt / takipten çıkan (varsayılan: içinde bulunulan ay).
     */
    public function getGeneralStats(?int $year = null, ?int $month = null): object {
        $year = $year ?? (int) date('Y');
        $month = $month ?? (int) date('n');
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        if ($year < 2000 || $year > ((int) date('Y') + 1)) {
            $year = (int) date('Y');
        }

        $yearStr = (string) $year;
        $monthPadded = sprintf('%02d', $month);
        $monthInt = (int) $month;
        $yearQ = $this->db->quote($yearStr);
        $monthPaddedQ = $this->db->quote($monthPadded);
        $monthIntQ = $this->db->quote((string) $monthInt);

        $emptyGeneral = (object) ['total_reached' => 0, 'active_total' => 0, 'active_male' => 0, 'active_female' => 0, 'fully_dependent' => 0];
        $emptyNew = (object) ['new_male' => 0, 'new_female' => 0];
        $emptyExit = (object) ['exit_male' => 0, 'exit_female' => 0];

        $query1 = "SELECT 
                    COUNT(id) as total_reached,
                    IFNULL(SUM(CASE WHEN pasif = '0' THEN 1 ELSE 0 END), 0) as active_total,
                    IFNULL(SUM(CASE WHEN pasif = '0' AND (cinsiyet = 'E' OR cinsiyet = '1') THEN 1 ELSE 0 END), 0) as active_male,
                    IFNULL(SUM(CASE WHEN pasif = '0' AND (cinsiyet = 'K' OR cinsiyet = '2') THEN 1 ELSE 0 END), 0) as active_female,
                    IFNULL(SUM(CASE WHEN pasif = '0' AND bagimlilik = '2' THEN 1 ELSE 0 END), 0) as fully_dependent
                  FROM #__hastalar
                  WHERE pasif IN ('1','0','-1','-3')" . TenantSqlHelper::andBare();
        $stats = $this->db->fetchObjectPrepared($query1) ?: $emptyGeneral;

        $query2 = "SELECT 
                    IFNULL(SUM(CASE WHEN (cinsiyet = 'E' OR cinsiyet = '1') THEN 1 ELSE 0 END), 0) as new_male,
                    IFNULL(SUM(CASE WHEN (cinsiyet = 'K' OR cinsiyet = '2') THEN 1 ELSE 0 END), 0) as new_female
                  FROM #__hastalar 
                  WHERE pasif = '0'
                  AND kayittarihi IS NOT NULL AND kayittarihi != '0000-00-00'
                  AND YEAR(kayittarihi) = " . (int) $year . "
                  AND MONTH(kayittarihi) = " . (int) $monthInt . TenantSqlHelper::andBare();
        $newPatients = $this->db->fetchObjectPrepared($query2) ?: $emptyNew;

        $pt = $this->sqlPasifTarihiAsDate();
        $monthStartQ = $this->db->quote($yearStr . '-' . $monthPadded . '-01');
        $query3 = "SELECT 
                    IFNULL(SUM(CASE WHEN (cinsiyet = 'E' OR cinsiyet = '1') THEN 1 ELSE 0 END), 0) as exit_male,
                    IFNULL(SUM(CASE WHEN (cinsiyet = 'K' OR cinsiyet = '2') THEN 1 ELSE 0 END), 0) as exit_female
                  FROM #__hastalar
                  WHERE pasif = '1' 
                  AND {$pt} BETWEEN {$monthStartQ} AND LAST_DAY({$monthStartQ})" . TenantSqlHelper::andBare();
        $exitPatients = $this->db->fetchObjectPrepared($query3) ?: $emptyExit;

        return (object) [
            'general' => $stats,
            'new' => $newPatients,
            'exit' => $exitPatients,
            'period' => (object) ['year' => $year, 'month' => $month],
        ];
    }

    /**
     * Aktif hasta sayısı.
     */
    public function getActivePatientCount(): int {
        return (int) $this->db->loadResultPrepared("SELECT COUNT(id) FROM #__hastalar WHERE pasif = 0" . TenantSqlHelper::andBare());
    }

    /**
     * e-Rapor branş dağılımı.
     * @return array<int, object{brans: string, count: int}>
     */
    public function getEraporBransDistribution(): array {
        $sql = "SELECT 
            COALESCE(NULLIF(TRIM(br.bransadi), ''), NULLIF(TRIM(CAST(e.brans AS CHAR)), ''), 'Belirtilmemiş') AS brans,
            COUNT(e.id) AS `count`
            FROM #__erapor e
            LEFT JOIN #__branslar br ON ((e.brans = br.id OR e.brans = br.bransadi) AND " . CatalogScopeSqlHelper::sqlPlatformCatalogOnEquals('br') . ")
            WHERE 1=1" . TenantSqlHelper::andEquals('e') . "
            GROUP BY COALESCE(NULLIF(TRIM(br.bransadi), ''), NULLIF(TRIM(CAST(e.brans AS CHAR)), ''), 'Belirtilmemiş')
            ORDER BY COUNT(e.id) DESC
            LIMIT 40";
        $list = $this->db->fetchObjectListPrepared($sql);
        return is_array($list) ? $list : [];
    }

    /** @var array<string, string> */
    private const RANDEVU_TAKVIM_TABLES = [
        'kons' => '#__kons_randevu',
        'uhds' => '#__goruntulu_randevu',
    ];

    private function resolveRandevuTakvimTable(string $tableKey): ?string
    {
        return self::RANDEVU_TAKVIM_TABLES[$tableKey] ?? null;
    }

    /**
     * Branş / Uhds randevu takvimi özeti (dönem içi).
     *
     * @return array{
     *   ok: bool,
     *   summary: object,
     *   by_month: array<int, object>,
     *   by_brans: array<int, object>,
     *   by_zaman: array<int, object>
     * }
     */
    public function getRandevuTakvimReport(string $tableKey, string $dateFrom, string $dateTo): array
    {
        $emptySummary = (object) [
            'toplam_randevu' => 0,
            'benzersiz_hasta' => 0,
            'brans_sayisi' => 0,
            'durum_olumlu' => 0,
            'durum_olumsuz' => 0,
            'durum_belirtilmedi' => 0,
        ];
        $empty = [
            'ok' => false,
            'summary' => $emptySummary,
            'by_month' => [],
            'by_brans' => [],
            'by_zaman' => [],
        ];

        $tbl = $this->resolveRandevuTakvimTable($tableKey);
        if ($tbl === null || $dateFrom === '' || $dateTo === '') {
            return $empty;
        }

        $rKurum = TenantSqlHelper::andEquals('r');
        $dateWhere = 'r.randevu_tarihi >= ? AND r.randevu_tarihi <= ?' . $rKurum;
        $dateParams = [$dateFrom, $dateTo];

        try {
            $summary = $this->db->fetchObjectPrepared("SELECT COUNT(*) AS toplam_randevu,
                        COUNT(DISTINCT r.hastatckimlik) AS benzersiz_hasta,
                        COUNT(DISTINCT r.brans_id) AS brans_sayisi,
                        SUM(CASE WHEN r.hasta_geldi = 1 THEN 1 ELSE 0 END) AS durum_olumlu,
                        SUM(CASE WHEN r.hasta_geldi = 0 THEN 1 ELSE 0 END) AS durum_olumsuz,
                        SUM(CASE WHEN r.hasta_geldi IS NULL THEN 1 ELSE 0 END) AS durum_belirtilmedi
                 FROM {$tbl} AS r
                 WHERE {$dateWhere}",
                $dateParams
            );
            if (!$summary) {
                $summary = $emptySummary;
            }

            $byMonth = $this->db->fetchObjectListPrepared("SELECT YEAR(r.randevu_tarihi) AS yil,
                        MONTH(r.randevu_tarihi) AS ay,
                        COUNT(*) AS adet
                 FROM {$tbl} AS r
                 WHERE {$dateWhere}
                 GROUP BY YEAR(r.randevu_tarihi), MONTH(r.randevu_tarihi)
                 ORDER BY yil ASC, ay ASC",
                $dateParams
            );

            $bransAssignedJoin = CatalogScopeSqlHelper::sqlBransAssignedJoin('b', 'kb');
            $byBrans = $this->db->fetchObjectListPrepared("SELECT b.bransadi AS brans_adi, COUNT(*) AS adet
                 FROM {$tbl} AS r
                 INNER JOIN #__branslar AS b ON b.id = r.brans_id AND " . CatalogScopeSqlHelper::sqlPlatformCatalogOnEquals('b') . "
                 {$bransAssignedJoin}
                 WHERE {$dateWhere}
                 GROUP BY r.brans_id, b.bransadi
                 ORDER BY adet DESC, b.bransadi ASC
                 LIMIT 20",
                $dateParams
            );

            $byZaman = $this->db->fetchObjectListPrepared("SELECT r.zaman,
                        COUNT(*) AS adet
                 FROM {$tbl} AS r
                 WHERE {$dateWhere}
                 GROUP BY r.zaman
                 ORDER BY r.zaman ASC",
                $dateParams
            );

            return [
                'ok' => true,
                'summary' => $summary,
                'by_month' => is_array($byMonth) ? $byMonth : [],
                'by_brans' => is_array($byBrans) ? $byBrans : [],
                'by_zaman' => is_array($byZaman) ? $byZaman : [],
            ];
        } catch (\Throwable $e) {
            return $empty;
        }
    }

    /**
     * Hasta kartı kayıt ↔ randevu gün farkı (`#__hastalar.kayittarihi` / `randevutarihi`).
     * Yalnızca aktif hastalar; `randevutarihi` son 12 ay içinde.
     *
     * @return array{
     *   date_from_ymd: string,
     *   date_to_ymd: string,
     *   ok: bool,
     *   summary: object,
     *   histogram: array<int, array{key: string, label: string, adet: int, sort: int, mid: int}>,
     *   by_month: array<int, object>,
     *   by_ilce: array<int, object>,
     *   longest: array<int, object>
     * }
     */
    public function getHastaKayitRandevuGapReport(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $defaultTo = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $defaultFrom = (new \DateTimeImmutable('first day of -11 months'))->format('Y-m-d');
        $to = ($dateTo !== null && $dateTo !== '') ? $dateTo : $defaultTo;
        $from = ($dateFrom !== null && $dateFrom !== '') ? $dateFrom : $defaultFrom;
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $empty = $this->emptyHastaKayitRandevuGapReport();
        $empty['date_from_ymd'] = $from;
        $empty['date_to_ymd'] = $to;

        $base = $this->sqlHastaKayitRandevuGapBase($from, $to);
        if ($base === '') {
            return $empty;
        }

        try {
            $report = $this->buildHastaKayitRandevuGapReportFromBase($base);
            $report['date_from_ymd'] = $from;
            $report['date_to_ymd'] = $to;

            $kayit = $this->sqlKayitTarihiExpr('h');
            $randevuOk = "h.randevutarihi IS NOT NULL AND h.randevutarihi != '' AND h.randevutarihi != '0000-00-00'";
            $eksik = (int) $this->db->loadResultPrepared("SELECT COUNT(*) FROM #__hastalar AS h
                 WHERE h.pasif = '0' AND {$kayit} IS NOT NULL AND NOT ({$randevuOk})" . TenantSqlHelper::andEquals('h')
            );
            if (isset($report['summary']) && is_object($report['summary'])) {
                $report['summary']->randevu_tarihi_bos = $eksik;
            }

            return $report;
        } catch (\Throwable $e) {
            return $empty;
        }
    }

    /** @return array<string, mixed> */
    private function emptyHastaKayitRandevuGapReport(): array
    {
        return [
            'date_from_ymd' => '',
            'date_to_ymd' => '',
            'ok' => false,
            'summary' => (object) [
                'toplam_hasta' => 0,
                'ortalama_gun' => null,
                'medyan_gun' => null,
                'min_gun' => null,
                'max_gun' => null,
                'kayit_oncesi' => 0,
                'ayni_gun' => 0,
                'randevu_tarihi_bos' => 0,
            ],
            'histogram' => $this->kayitRandevuGapHistogramTemplate(0),
            'by_month' => [],
            'by_ilce' => [],
            'longest' => [],
        ];
    }

    private function sqlHastaKayitRandevuGapBase(string $dateFrom, string $dateTo): string
    {
        if ($dateFrom === '' || $dateTo === '') {
            return '';
        }
        $qf = $this->db->quote($dateFrom);
        $qt = $this->db->quote($dateTo);
        $kayit = $this->sqlKayitTarihiExpr('h');
        $randevuOk = "h.randevutarihi IS NOT NULL AND h.randevutarihi != '' AND h.randevutarihi != '0000-00-00'";

        return "SELECT h.id AS hasta_id,
                h.tckimlik,
                h.isim,
                h.soyisim,
                h.ilce,
                DATE({$kayit}) AS kayit_tarihi,
                DATE(h.randevutarihi) AS randevu_tarihi,
                DATEDIFF(DATE(h.randevutarihi), DATE({$kayit})) AS gun_fark
            FROM #__hastalar AS h
            WHERE h.pasif = '0'
              AND {$randevuOk}
              AND {$kayit} IS NOT NULL
              AND h.randevutarihi >= {$qf}
              AND h.randevutarihi <= {$qt}" . TenantSqlHelper::andEquals('h');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildHastaKayitRandevuGapReportFromBase(string $baseFrom): array
    {
        $histSql = $this->sqlKayitRandevuGapHistogramAgg('x');
        $summary = $this->db->fetchObjectPrepared("SELECT COUNT(*) AS toplam_hasta,
                    ROUND(AVG(x.gun_fark), 1) AS ortalama_gun,
                    MIN(x.gun_fark) AS min_gun,
                    MAX(x.gun_fark) AS max_gun,
                    SUM(CASE WHEN x.gun_fark < 0 THEN 1 ELSE 0 END) AS kayit_oncesi,
                    SUM(CASE WHEN x.gun_fark = 0 THEN 1 ELSE 0 END) AS ayni_gun,
                    {$histSql}
             FROM ({$baseFrom}) AS x"
        );

        $toplam = (int) ($summary->toplam_hasta ?? 0);
        if (!$summary || $toplam === 0) {
            $out = $this->emptyHastaKayitRandevuGapReport();
            $out['ok'] = true;
            return $out;
        }

        $histogram = $this->kayitRandevuGapHistogramFromSummary($summary, $toplam);
        $summary->medyan_gun = $this->medianGunFromHistogram($histogram, $toplam);
        $summary->ortalama_gun = isset($summary->ortalama_gun) ? (float) $summary->ortalama_gun : null;

        $byMonth = $this->db->fetchObjectListPrepared("SELECT YEAR(x.randevu_tarihi) AS yil,
                    MONTH(x.randevu_tarihi) AS ay,
                    COUNT(*) AS adet,
                    ROUND(AVG(x.gun_fark), 1) AS ortalama_gun
             FROM ({$baseFrom}) AS x
             GROUP BY YEAR(x.randevu_tarihi), MONTH(x.randevu_tarihi)
             ORDER BY yil ASC, ay ASC"
        );

        $byIlce = $this->db->fetchObjectListPrepared("SELECT COALESCE(il.adi, 'Belirtilmedi') AS ilce_adi,
                    COUNT(*) AS adet,
                    ROUND(AVG(x.gun_fark), 1) AS ortalama_gun
             FROM ({$baseFrom}) AS x
             LEFT JOIN #__adrestablosu AS il ON il.id = x.ilce
             GROUP BY x.ilce, il.adi
             HAVING adet >= 3
             ORDER BY adet DESC, ortalama_gun DESC
             LIMIT 15"
        );

        $detailSelect = $this->sqlHastaKayitRandevuGapDetailSelect();
        $longest = $this->db->fetchObjectListPrepared("SELECT {$detailSelect}
             FROM ({$baseFrom}) AS x
             LEFT JOIN #__adrestablosu AS il ON il.id = x.ilce
             LEFT JOIN #__hastalar AS h ON h.id = x.hasta_id
             LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle
             WHERE x.gun_fark >= 0
             ORDER BY x.gun_fark DESC, x.randevu_tarihi DESC
             LIMIT 25"
        );

        return [
            'ok' => true,
            'summary' => $summary,
            'histogram' => $histogram,
            'by_month' => is_array($byMonth) ? $byMonth : [],
            'by_ilce' => is_array($byIlce) ? $byIlce : [],
            'longest' => is_array($longest) ? $longest : [],
        ];
    }

    private function sqlKayitRandevuGapHistogramAgg(string $alias): string
    {
        $g = "{$alias}.gun_fark";
        return "SUM(CASE WHEN {$g} < 0 THEN 1 ELSE 0 END) AS h_neg,
                SUM(CASE WHEN {$g} = 0 THEN 1 ELSE 0 END) AS h_0,
                SUM(CASE WHEN {$g} BETWEEN 1 AND 7 THEN 1 ELSE 0 END) AS h_1_7,
                SUM(CASE WHEN {$g} BETWEEN 8 AND 14 THEN 1 ELSE 0 END) AS h_8_14,
                SUM(CASE WHEN {$g} BETWEEN 15 AND 30 THEN 1 ELSE 0 END) AS h_15_30,
                SUM(CASE WHEN {$g} BETWEEN 31 AND 60 THEN 1 ELSE 0 END) AS h_31_60,
                SUM(CASE WHEN {$g} BETWEEN 61 AND 90 THEN 1 ELSE 0 END) AS h_61_90,
                SUM(CASE WHEN {$g} BETWEEN 91 AND 180 THEN 1 ELSE 0 END) AS h_91_180,
                SUM(CASE WHEN {$g} BETWEEN 181 AND 365 THEN 1 ELSE 0 END) AS h_181_365,
                SUM(CASE WHEN {$g} > 365 THEN 1 ELSE 0 END) AS h_366";
    }

    /**
     * @return array<int, array{key: string, label: string, adet: int, sort: int, mid: int}>
     */
    private function kayitRandevuGapHistogramTemplate(int $adet = 0): array
    {
        return [
            ['key' => 'neg', 'label' => 'Kayıttan önce (randevu < kayıt)', 'adet' => $adet, 'sort' => 0, 'mid' => -1],
            ['key' => '0', 'label' => 'Aynı gün (0)', 'adet' => $adet, 'sort' => 1, 'mid' => 0],
            ['key' => '1_7', 'label' => '1–7 gün', 'adet' => $adet, 'sort' => 2, 'mid' => 4],
            ['key' => '8_14', 'label' => '7–14 gün', 'adet' => $adet, 'sort' => 3, 'mid' => 11],
            ['key' => '15_30', 'label' => '14–30 gün', 'adet' => $adet, 'sort' => 4, 'mid' => 22],
            ['key' => '31_60', 'label' => '30–60 gün', 'adet' => $adet, 'sort' => 5, 'mid' => 45],
            ['key' => '61_90', 'label' => '60–90 gün', 'adet' => $adet, 'sort' => 6, 'mid' => 75],
            ['key' => '91_180', 'label' => '91–180 gün', 'adet' => $adet, 'sort' => 7, 'mid' => 135],
            ['key' => '181_365', 'label' => '181–365 gün', 'adet' => $adet, 'sort' => 8, 'mid' => 270],
            ['key' => '366', 'label' => '366+ gün', 'adet' => $adet, 'sort' => 9, 'mid' => 400],
        ];
    }

    /** @return array<int, array{key: string, label: string, adet: int, sort: int, mid: int}> */
    private function kayitRandevuGapHistogramFromSummary(object $summary, int $toplam): array
    {
        $map = [
            'neg' => (int) ($summary->h_neg ?? 0),
            '0' => (int) ($summary->h_0 ?? 0),
            '1_7' => (int) ($summary->h_1_7 ?? 0),
            '8_14' => (int) ($summary->h_8_14 ?? 0),
            '15_30' => (int) ($summary->h_15_30 ?? 0),
            '31_60' => (int) ($summary->h_31_60 ?? 0),
            '61_90' => (int) ($summary->h_61_90 ?? 0),
            '91_180' => (int) ($summary->h_91_180 ?? 0),
            '181_365' => (int) ($summary->h_181_365 ?? 0),
            '366' => (int) ($summary->h_366 ?? 0),
        ];
        $rows = $this->kayitRandevuGapHistogramTemplate(0);
        foreach ($rows as &$row) {
            $row['adet'] = $map[$row['key']] ?? 0;
        }
        unset($row);

        return $rows;
    }

    /**
     * @param array<int, array{adet: int, mid: int}> $histogram
     */
    private function medianGunFromHistogram(array $histogram, int $toplam): ?int
    {
        if ($toplam <= 0) {
            return null;
        }
        $hedef = (int) floor(($toplam - 1) / 2);
        $toplanan = 0;
        foreach ($histogram as $row) {
            $toplanan += (int) $row['adet'];
            if ($toplanan > $hedef) {
                return (int) $row['mid'];
            }
        }

        return null;
    }

    private function sqlHastaKayitRandevuGapDetailSelect(): string
    {
        $sonIzlemIdSql = '(SELECT iz3.id FROM #__izlemler iz3
                WHERE iz3.hastatckimlik = x.tckimlik AND iz3.yapildimi = 1
                ORDER BY iz3.izlemtarihi DESC, iz3.id DESC
                LIMIT 1)';

        return "x.hasta_id AS id,
                x.hasta_id,
                x.isim,
                x.soyisim,
                x.tckimlik,
                DATE_FORMAT(x.kayit_tarihi, '%d.%m.%Y') AS kayit_tr,
                DATE_FORMAT(x.randevu_tarihi, '%d.%m.%Y') AS randevu_tr,
                x.gun_fark,
                il.adi AS ilce_adi,
                m.adi AS mahalle_adi,
                h.dogumtarihi,
                h.ceptel1,
                (SELECT izlemtarihi FROM #__izlemler izs
                    WHERE izs.hastatckimlik = x.tckimlik AND izs.yapildimi = 1
                    ORDER BY izs.izlemtarihi DESC, izs.id DESC
                    LIMIT 1) AS sonizlemtarihi,
                (SELECT GROUP_CONCAT(i2.islemadi ORDER BY i2.id SEPARATOR ', ')
                    FROM #__izlemler iz2
                    INNER JOIN #__islemler i2 ON FIND_IN_SET(i2.id, REPLACE(IFNULL(iz2.yapilan, ''), ' ', '')) > 0
                        AND " . CatalogScopeSqlHelper::sqlPlatformCatalogOnEquals('i2') . "
                    WHERE iz2.id = {$sonIzlemIdSql}) AS son_izlem_yapilanlar,
                (SELECT COUNT(id) FROM #__izlemler i2 WHERE i2.hastatckimlik = x.tckimlik AND i2.yapildimi = 1) AS izlemsayisi,
                (SELECT COUNT(id) FROM #__izlemler i3 WHERE i3.hastatckimlik = x.tckimlik AND i3.yapildimi = 0) AS yizlemsayisi,
                (SELECT COUNT(id) FROM #__pizlemler p WHERE p.hastatckimlik = x.tckimlik) AS totalplanli";
    }

    /**
     * Aktif hastalar — boy/kilo ile VKİ dağılımı (cinsiyet ve yaş bandı kırılımları).
     *
     * @return array<string, mixed>
     */
    public function getBmiVkiReport(): array {
        $activeTotal = (int) $this->db->loadResultPrepared("SELECT COUNT(*) FROM #__hastalar WHERE pasif = '0'" . TenantSqlHelper::andBare()
        );

        $sql = "SELECT h.cinsiyet, h.dogumtarihi, h.boy, h.kilo
            FROM #__hastalar h
            WHERE h.pasif = '0'" . TenantSqlHelper::andEquals('h');
        $rows = $this->db->fetchObjectListPrepared($sql) ?: [];

        $catKeys = BmiHelper::categoryKeys();
        $categories = BmiHelper::emptyCategoryCounts();
        $byGender = [
            'E' => BmiHelper::emptyCategoryCounts(),
            'K' => BmiHelper::emptyCategoryCounts(),
            '?' => BmiHelper::emptyCategoryCounts(),
        ];
        $byAge = [];
        foreach (AgeBandHelper::keys() as $band) {
            $byAge[$band] = BmiHelper::emptyCategoryCounts();
        }

        $withBothFields = 0;
        $computable = 0;
        $invalidAnthro = 0;
        $bmiSum = 0.0;
        $bmiSumByGender = ['E' => 0.0, 'K' => 0.0, '?' => 0.0];
        $bmiCountByGender = ['E' => 0, 'K' => 0, '?' => 0];

        foreach ($rows as $row) {
            $hasBoy = $row->boy !== null && (float) $row->boy > 0;
            $hasKilo = $row->kilo !== null && (float) $row->kilo > 0;
            if (!$hasBoy || !$hasKilo) {
                continue;
            }
            $withBothFields++;

            $cm = BmiHelper::normalizeBoyCm($row->boy);
            $kg = BmiHelper::normalizeKiloKg($row->kilo);
            if ($cm === null || $kg === null) {
                $invalidAnthro++;
                continue;
            }

            $bmi = BmiHelper::calculateBmi($kg, $cm);
            $cat = BmiHelper::classifyBmi($bmi);
            if (!isset($categories[$cat])) {
                $invalidAnthro++;
                continue;
            }

            $computable++;
            $categories[$cat]++;
            $bmiSum += $bmi;

            $gKey = BmiHelper::genderKey($row->cinsiyet ?? '');
            $byGender[$gKey][$cat]++;
            $bmiSumByGender[$gKey] += $bmi;
            $bmiCountByGender[$gKey]++;

            $ageKey = AgeBandHelper::bandFromBirthDate($row->dogumtarihi ?? null);
            if ($ageKey !== null && isset($byAge[$ageKey])) {
                $byAge[$ageKey][$cat]++;
            }
        }

        $withoutAnthro = max(0, $activeTotal - $withBothFields);
        $catMeta = BmiHelper::categories();
        $catRows = [];
        foreach ($catKeys as $key) {
            $n = (int) ($categories[$key] ?? 0);
            $catRows[] = [
                'key' => $key,
                'label' => $catMeta[$key]['label'],
                'short' => $catMeta[$key]['short'],
                'color' => $catMeta[$key]['color'],
                'count' => $n,
                'pct' => $computable > 0 ? round($n / $computable * 100, 1) : 0.0,
            ];
        }

        $avgBmi = $computable > 0 ? round($bmiSum / $computable, 1) : 0.0;
        $avgByGender = [];
        foreach (['E', 'K', '?'] as $gk) {
            $cnt = (int) $bmiCountByGender[$gk];
            $avgByGender[$gk] = [
                'label' => BmiHelper::genderLabel($gk),
                'count' => $cnt,
                'avg' => $cnt > 0 ? round($bmiSumByGender[$gk] / $cnt, 1) : null,
            ];
        }

        return [
            'active_total' => $activeTotal,
            'with_both_fields' => $withBothFields,
            'without_anthro' => $withoutAnthro,
            'computable' => $computable,
            'invalid_anthro' => $invalidAnthro,
            'avg_bmi' => $avgBmi,
            'categories' => $catRows,
            'by_gender' => $byGender,
            'by_age' => $byAge,
            'avg_by_gender' => $avgByGender,
            'cat_meta' => $catMeta,
            'cat_keys' => $catKeys,
            'age_band_keys' => AgeBandHelper::keys(),
        ];
    }

    /**
     * Planlı izlem istatistikleri — planlanan tarih aralığında aktif hastalar.
     *
     * @return array{
     *   ok: bool,
     *   summary: object,
     *   by_priority: list<object>,
     *   by_month: list<object>,
     *   by_zaman: list<object>
     * }
     */
    public function getPlannedVisitStatsReport(string $dateFrom, string $dateTo): array
    {
        $emptySummary = (object) [
            'toplam' => 0,
            'tamamlanan' => 0,
            'bekleyen' => 0,
            'gecikmis' => 0,
            'benzersiz_hasta' => 0,
            'tamamlanma_orani' => null,
        ];
        $empty = [
            'ok' => false,
            'summary' => $emptySummary,
            'by_priority' => [],
            'by_month' => [],
            'by_zaman' => [],
        ];

        if ($dateFrom === '' || $dateTo === '') {
            return $empty;
        }

        $qf = $this->db->quote($dateFrom);
        $qt = $this->db->quote($dateTo);
        $fromSql = "FROM #__pizlemler p
            INNER JOIN #__hastalar h ON h.tckimlik = p.hastatckimlik AND h.pasif = '0'
            WHERE p.planlanantarih >= {$qf} AND p.planlanantarih <= {$qt}" . TenantSqlHelper::andEquals('h') . TenantSqlHelper::andEquals('p');
        $oncelikExpr = "CASE
            WHEN COALESCE(p.oncelik, 1) < 1 OR COALESCE(p.oncelik, 1) > 3 THEN 1
            ELSE COALESCE(p.oncelik, 1)
        END";
        $zamanExpr = 'COALESCE(' . ZamanDilimiHelper::sqlNormalizeCaseExpr('p.zaman') . ', ' . ZamanDilimiHelper::SABAH . ')';

        try {
            $summary = $this->db->fetchObjectPrepared("SELECT COUNT(*) AS toplam,
                        SUM(CASE WHEN COALESCE(p.durum, 0) = 1 THEN 1 ELSE 0 END) AS tamamlanan,
                        SUM(CASE WHEN COALESCE(p.durum, 0) = 0 THEN 1 ELSE 0 END) AS bekleyen,
                        SUM(CASE WHEN COALESCE(p.durum, 0) = 0 AND p.planlanantarih < CURDATE() THEN 1 ELSE 0 END) AS gecikmis,
                        COUNT(DISTINCT p.hastatckimlik) AS benzersiz_hasta
                 {$fromSql}"
            );
            if (!$summary) {
                $summary = $emptySummary;
            } else {
                $toplam = (int) ($summary->toplam ?? 0);
                $tamamlanan = (int) ($summary->tamamlanan ?? 0);
                $summary->tamamlanma_orani = $toplam > 0
                    ? (int) round(100.0 * $tamamlanan / $toplam)
                    : null;
            }

            $byPriority = $this->db->fetchObjectListPrepared("SELECT {$oncelikExpr} AS oncelik_kod,
                        COUNT(*) AS toplam,
                        SUM(CASE WHEN COALESCE(p.durum, 0) = 1 THEN 1 ELSE 0 END) AS tamamlanan,
                        SUM(CASE WHEN COALESCE(p.durum, 0) = 0 THEN 1 ELSE 0 END) AS bekleyen,
                        SUM(CASE WHEN COALESCE(p.durum, 0) = 0 AND p.planlanantarih < CURDATE() THEN 1 ELSE 0 END) AS gecikmis
                 {$fromSql}
                 GROUP BY oncelik_kod
                 ORDER BY oncelik_kod ASC"
            );

            $byMonth = $this->db->fetchObjectListPrepared("SELECT YEAR(p.planlanantarih) AS yil,
                        MONTH(p.planlanantarih) AS ay,
                        COUNT(*) AS toplam,
                        SUM(CASE WHEN COALESCE(p.durum, 0) = 1 THEN 1 ELSE 0 END) AS tamamlanan,
                        SUM(CASE WHEN COALESCE(p.durum, 0) = 0 THEN 1 ELSE 0 END) AS bekleyen
                 {$fromSql}
                 GROUP BY YEAR(p.planlanantarih), MONTH(p.planlanantarih)
                 ORDER BY yil ASC, ay ASC"
            );

            $byZaman = $this->db->fetchObjectListPrepared("SELECT {$zamanExpr} AS zaman_kod,
                        COUNT(*) AS toplam,
                        SUM(CASE WHEN COALESCE(p.durum, 0) = 1 THEN 1 ELSE 0 END) AS tamamlanan,
                        SUM(CASE WHEN COALESCE(p.durum, 0) = 0 THEN 1 ELSE 0 END) AS bekleyen
                 {$fromSql}
                 GROUP BY zaman_kod
                 ORDER BY zaman_kod ASC"
            );

            return [
                'ok' => true,
                'summary' => $summary,
                'by_priority' => is_array($byPriority) ? $byPriority : [],
                'by_month' => is_array($byMonth) ? $byMonth : [],
                'by_zaman' => is_array($byZaman) ? $byZaman : [],
            ];
        } catch (\Throwable $e) {
            return $empty;
        }
    }

    /**
     * Yapılan izlem (Visit) istatistikleri — izlem tarihi aralığında aktif hastalar.
     *
     * @return array{
     *   ok: bool,
     *   summary: object,
     *   by_arac: list<object>,
     *   by_month: list<object>,
     *   by_zaman: list<object>,
     *   by_neden: list<object>
     * }
     */
    public function getVisitStatsReport(string $dateFrom, string $dateTo): array
    {
        $emptySummary = (object) [
            'toplam' => 0,
            'yapilan' => 0,
            'yapilmayan' => 0,
            'benzersiz_hasta' => 0,
            'tamamlanma_orani' => null,
        ];
        $empty = [
            'ok' => false,
            'summary' => $emptySummary,
            'by_arac' => [],
            'by_month' => [],
            'by_zaman' => [],
            'by_neden' => [],
        ];

        if ($dateFrom === '' || $dateTo === '') {
            return $empty;
        }

        $qf = $this->db->quote($dateFrom);
        $qt = $this->db->quote($dateTo);
        $izd = $this->sqlIzlemTarihiAsDate('i');
        $fromSql = "FROM #__izlemler i
            INNER JOIN #__hastalar h ON h.tckimlik = i.hastatckimlik AND h.pasif = '0'
            WHERE {$izd} IS NOT NULL AND {$izd} >= {$qf} AND {$izd} <= {$qt}" . TenantSqlHelper::andEquals('h') . TenantSqlHelper::andEquals('i');
        $zamanExpr = 'COALESCE(' . ZamanDilimiHelper::sqlNormalizeCaseExpr('i.zaman') . ', ' . ZamanDilimiHelper::SABAH . ')';
        $nedenExpr = "CASE
            WHEN i.neden REGEXP '^[0-9]+$' AND CAST(i.neden AS UNSIGNED) BETWEEN 1 AND 8 THEN CAST(i.neden AS UNSIGNED)
            ELSE 8
        END";
        $aracExpr = "CASE WHEN COALESCE(i.arac, 0) > 0 THEN 1 ELSE 0 END";

        try {
            $summary = $this->db->fetchObjectPrepared("SELECT COUNT(*) AS toplam,
                        SUM(CASE WHEN COALESCE(i.yapildimi, 0) = 1 THEN 1 ELSE 0 END) AS yapilan,
                        SUM(CASE WHEN COALESCE(i.yapildimi, 0) = 0 THEN 1 ELSE 0 END) AS yapilmayan,
                        COUNT(DISTINCT i.hastatckimlik) AS benzersiz_hasta
                 {$fromSql}"
            );
            if (!$summary) {
                $summary = $emptySummary;
            } else {
                $toplam = (int) ($summary->toplam ?? 0);
                $yapilan = (int) ($summary->yapilan ?? 0);
                $summary->tamamlanma_orani = $toplam > 0
                    ? (int) round(100.0 * $yapilan / $toplam)
                    : null;
            }

            $byArac = $this->db->fetchObjectListPrepared("SELECT {$aracExpr} AS arac_kod,
                        COUNT(*) AS toplam,
                        SUM(CASE WHEN COALESCE(i.yapildimi, 0) = 1 THEN 1 ELSE 0 END) AS yapilan,
                        SUM(CASE WHEN COALESCE(i.yapildimi, 0) = 0 THEN 1 ELSE 0 END) AS yapilmayan
                 {$fromSql}
                 GROUP BY arac_kod
                 ORDER BY arac_kod DESC"
            );

            $byMonth = $this->db->fetchObjectListPrepared("SELECT YEAR({$izd}) AS yil,
                        MONTH({$izd}) AS ay,
                        COUNT(*) AS toplam,
                        SUM(CASE WHEN COALESCE(i.yapildimi, 0) = 1 THEN 1 ELSE 0 END) AS yapilan,
                        SUM(CASE WHEN COALESCE(i.yapildimi, 0) = 0 THEN 1 ELSE 0 END) AS yapilmayan
                 {$fromSql}
                 GROUP BY YEAR({$izd}), MONTH({$izd})
                 ORDER BY yil ASC, ay ASC"
            );

            $byZaman = $this->db->fetchObjectListPrepared("SELECT {$zamanExpr} AS zaman_kod,
                        COUNT(*) AS toplam,
                        SUM(CASE WHEN COALESCE(i.yapildimi, 0) = 1 THEN 1 ELSE 0 END) AS yapilan,
                        SUM(CASE WHEN COALESCE(i.yapildimi, 0) = 0 THEN 1 ELSE 0 END) AS yapilmayan
                 {$fromSql}
                 GROUP BY zaman_kod
                 ORDER BY zaman_kod ASC"
            );

            $byNeden = $this->db->fetchObjectListPrepared("SELECT {$nedenExpr} AS neden_kod,
                        COUNT(*) AS adet
                 {$fromSql}
                 AND COALESCE(i.yapildimi, 0) = 0
                 GROUP BY neden_kod
                 ORDER BY neden_kod ASC"
            );

            return [
                'ok' => true,
                'summary' => $summary,
                'by_arac' => is_array($byArac) ? $byArac : [],
                'by_month' => is_array($byMonth) ? $byMonth : [],
                'by_zaman' => is_array($byZaman) ? $byZaman : [],
                'by_neden' => is_array($byNeden) ? $byNeden : [],
            ];
        } catch (\Throwable $e) {
            return $empty;
        }
    }

    // -------------------------------------------------------------------------
    // e-Rapor (#__erapor) ↔ Hasta (#__hastalar) karşılaştırma istatistikleri
    // -------------------------------------------------------------------------

    private function sqlEraporValidTc(string $alias = 'e'): string {
        return "({$alias}.hastatckimlik IS NOT NULL AND {$alias}.hastatckimlik <> '' AND CHAR_LENGTH({$alias}.hastatckimlik) = 11)";
    }

    /** e-Rapor havuzunda geçerli TC seti — EXISTS yerine JOIN (indeks: idx_tc). */
    private function sqlEraporPoolJoin(string $poolAlias = 'epool'): string {
        $valid = $this->sqlEraporValidTc('ex');

        return "LEFT JOIN (
            SELECT DISTINCT ex.hastatckimlik
            FROM #__erapor AS ex
            WHERE {$valid}" . TenantSqlHelper::andBare('kurum_id') . "
        ) AS {$poolAlias} ON {$poolAlias}.hastatckimlik = h.tckimlik";
    }

    private function sqlEraporInPool(string $poolAlias = 'epool'): string {
        return "{$poolAlias}.hastatckimlik IS NOT NULL";
    }

    private function sqlPatientEraporFlag(string $alias = 'h'): string {
        return "(TRIM(CAST({$alias}.erapor AS CHAR)) = '1' OR {$alias}.erapor = 1)";
    }

    private function sqlPatientActive(string $alias = 'h'): string {
        return "{$alias}.pasif = '0'";
    }

    private function sqlEraporBransJoin(string $eraporAlias = 'e'): string {
        return 'LEFT JOIN #__branslar b ON ((' . $eraporAlias . '.brans = b.id OR ' . $eraporAlias . '.brans = b.bransadi) AND '
            . CatalogScopeSqlHelper::sqlPlatformCatalogOnEquals('b') . ')';
    }

    /**
     * e-Rapor ↔ hasta uyum özeti (tek istekte havuz + hasta kırılımları).
     */
    public function getEraporHastaUyumSnapshot(): object {
        $cacheKey = $this->statsCacheKey('erapor_hasta_uyum_snapshot');
        $cached = StatsQueryCache::get($cacheKey);
        if (is_array($cached)) {
            $out = (object) $cached;
            if (isset($out->uyumsuz_toplam)) {
                return $out;
            }
        }

        $validTc = $this->sqlEraporValidTc('e');
        $eKurum = TenantSqlHelper::andEquals('e');
        $sqlErapor = "SELECT
                COUNT(e.id) AS erapor_toplam,
                SUM(CASE WHEN NOT {$validTc} THEN 1 ELSE 0 END) AS erapor_gecersiz_tc,
                SUM(CASE WHEN COALESCE(e.kayitlimi, 0) = 1 THEN 1 ELSE 0 END) AS erapor_kayitli_isaret,
                SUM(CASE WHEN COALESCE(e.kayitlimi, 0) = 0 THEN 1 ELSE 0 END) AS erapor_disaridan_isaret,
                SUM(CASE WHEN COALESCE(e.yenilendimi, 0) = 1 THEN 1 ELSE 0 END) AS erapor_yenilendi,
                SUM(CASE WHEN {$validTc} AND h.id IS NULL THEN 1 ELSE 0 END) AS erapor_hasta_yok,
                SUM(CASE WHEN {$validTc} AND COALESCE(e.kayitlimi, 0) = 0 AND h.id IS NOT NULL THEN 1 ELSE 0 END) AS erapor_disaridan_ama_hasta_var,
                SUM(CASE WHEN {$validTc} AND COALESCE(e.kayitlimi, 0) = 0 AND " . $this->sqlPatientActive('h') . " THEN 1 ELSE 0 END) AS erapor_disaridan_ama_hasta_aktif,
                SUM(CASE WHEN {$validTc} AND COALESCE(e.kayitlimi, 0) = 1 AND h.id IS NULL THEN 1 ELSE 0 END) AS erapor_kayitli_ama_hasta_yok,
                SUM(CASE WHEN {$validTc} AND COALESCE(e.kayitlimi, 0) = 1 AND h.id IS NOT NULL AND NOT " . $this->sqlPatientActive('h') . " THEN 1 ELSE 0 END) AS erapor_kayitli_ama_pasif,
                SUM(CASE WHEN {$validTc} AND h.id IS NOT NULL AND NOT " . $this->sqlPatientActive('h') . " THEN 1 ELSE 0 END) AS erapor_hasta_pasif,
                SUM(CASE WHEN {$validTc} AND " . $this->sqlPatientActive('h') . " THEN 1 ELSE 0 END) AS erapor_hasta_aktif_eslesen,
                SUM(CASE WHEN {$validTc} AND h.id IS NOT NULL
                    AND (UPPER(TRIM(e.isim)) <> UPPER(TRIM(h.isim)) OR UPPER(TRIM(e.soyisim)) <> UPPER(TRIM(h.soyisim))) THEN 1 ELSE 0 END) AS erapor_isim_uyumsuz,
                SUM(CASE WHEN COALESCE(e.yenilendimi, 0) = 1 AND COALESCE(e.kayitlimi, 0) = 0 THEN 1 ELSE 0 END) AS erapor_yenilendi_kayitsiz
            FROM #__erapor AS e
            LEFT JOIN #__hastalar AS h ON h.tckimlik = e.hastatckimlik AND h.kurum_id = e.kurum_id
            WHERE 1=1{$eKurum}";
        $eraporRow = $this->db->fetchObjectPrepared($sqlErapor) ?: (object) [];

        $dupValidTc = $this->sqlEraporValidTc('er');
        $erKurum = TenantSqlHelper::andEquals('er');
        $dupSql = "SELECT COUNT(e.id) AS cnt
            FROM #__erapor AS e
            INNER JOIN (
                SELECT er.hastatckimlik AS tc
                FROM #__erapor AS er
                WHERE {$dupValidTc}{$erKurum}
                GROUP BY er.hastatckimlik
                HAVING COUNT(*) > 1
            ) AS dup ON e.hastatckimlik = dup.tc
            WHERE 1=1{$eKurum}";
        $eraporTcCoklu = (int) $this->db->loadResultPrepared($dupSql);

        $eraporFlag = $this->sqlPatientEraporFlag('h');
        $active = $this->sqlPatientActive('h');
        $poolJoin = $this->sqlEraporPoolJoin('epool');
        $inPool = $this->sqlEraporInPool('epool');
        $notInPool = 'epool.hastatckimlik IS NULL';
        $sqlHasta = "SELECT
                SUM(CASE WHEN {$active} AND {$eraporFlag} AND {$notInPool} THEN 1 ELSE 0 END) AS hasta_aktif_eraporlu_havuz_yok,
                SUM(CASE WHEN {$active} AND NOT {$eraporFlag} AND {$inPool} THEN 1 ELSE 0 END) AS hasta_aktif_havuz_var_erapor_isaretsiz,
                SUM(CASE WHEN NOT {$active} AND {$inPool} THEN 1 ELSE 0 END) AS hasta_pasif_havuz_var,
                SUM(CASE WHEN {$active} AND {$eraporFlag} THEN 1 ELSE 0 END) AS hastalar_aktif_eraporlu,
                SUM(CASE WHEN {$active} AND {$inPool} THEN 1 ELSE 0 END) AS hastalar_aktif_havuzda
            FROM #__hastalar AS h
            {$poolJoin}
            WHERE 1=1" . TenantSqlHelper::andEquals('h');
        $hastaRow = $this->db->fetchObjectPrepared($sqlHasta) ?: (object) [];

        $out = (object) array_merge((array) $eraporRow, (array) $hastaRow, ['erapor_tc_coklu' => $eraporTcCoklu]);
        $intKeys = [
            'erapor_toplam', 'erapor_gecersiz_tc', 'erapor_kayitli_isaret', 'erapor_disaridan_isaret', 'erapor_yenilendi',
            'erapor_hasta_yok', 'erapor_disaridan_ama_hasta_var', 'erapor_disaridan_ama_hasta_aktif',
            'erapor_kayitli_ama_hasta_yok', 'erapor_kayitli_ama_pasif', 'erapor_hasta_pasif', 'erapor_hasta_aktif_eslesen',
            'erapor_isim_uyumsuz', 'erapor_yenilendi_kayitsiz', 'erapor_tc_coklu',
            'hasta_aktif_eraporlu_havuz_yok', 'hasta_aktif_havuz_var_erapor_isaretsiz', 'hasta_pasif_havuz_var',
            'hastalar_aktif_eraporlu', 'hastalar_aktif_havuzda',
        ];
        foreach ($intKeys as $k) {
            $out->$k = (int) ($out->$k ?? 0);
        }
        $out->uyumsuz_toplam = $out->erapor_hasta_yok + $out->erapor_disaridan_ama_hasta_aktif
            + $out->erapor_kayitli_ama_hasta_yok + $out->erapor_kayitli_ama_pasif + $out->erapor_isim_uyumsuz
            + $out->erapor_tc_coklu + $out->erapor_yenilendi_kayitsiz + $out->erapor_gecersiz_tc
            + $out->hasta_aktif_eraporlu_havuz_yok + $out->hasta_aktif_havuz_var_erapor_isaretsiz
            + $out->hasta_pasif_havuz_var;
        StatsQueryCache::set($cacheKey, (array) $out);

        return $out;
    }

    /**
     * @return array<string, string>
     */
    public static function eraporHastaUyumMetricLabels(): array {
        return [
            'erapor_gecersiz_tc' => 'Geçersiz veya boş TC (11 hane değil)',
            'erapor_hasta_yok' => 'e-Rapor kaydı var, hasta kartı yok',
            'erapor_disaridan_ama_hasta_var' => '«Kayıtlı değil» işaretli, hasta kartı var (tüm durumlar)',
            'erapor_disaridan_ama_hasta_aktif' => '«Kayıtlı değil» işaretli, hasta aktif',
            'erapor_kayitli_ama_hasta_yok' => '«Kayıtlı» işaretli, hasta kartı yok',
            'erapor_kayitli_ama_pasif' => '«Kayıtlı» işaretli, hasta pasif/çıkmış',
            'erapor_hasta_pasif' => 'e-Rapor kaydı var, hasta pasif/çıkmış',
            'erapor_isim_uyumsuz' => 'TC eşleşiyor, ad veya soyad farklı',
            'erapor_tc_coklu' => 'Aynı TC ile birden fazla e-Rapor satırı',
            'erapor_yenilendi_kayitsiz' => 'Yenilendi işaretli, «kayıtlı değil»',
            'hasta_aktif_eraporlu_havuz_yok' => 'Aktif hasta, kartta e-Rapor işaretli, havuzda kayıt yok',
            'hasta_aktif_havuz_var_erapor_isaretsiz' => 'Aktif hasta, havuzda kayıt var, kartta e-Rapor işareti yok',
            'hasta_pasif_havuz_var' => 'Pasif/çıkmış hasta, e-Rapor havuzunda kayıt var',
        ];
    }

    /**
     * Metrik tablosu grup tanımları (sıra ve başlık).
     *
     * @return array<string, array{order: int, label: string, hint: string}>
     */
    public static function eraporHastaUyumMetricsGroups(): array {
        return [
            'ozet_havuz' => [
                'order' => 10,
                'label' => 'Özet — e-Rapor havuzu',
                'hint' => 'Havuzdaki satır ve işaret dağılımı (liste yok)',
            ],
            'ozet_hasta' => [
                'order' => 20,
                'label' => 'Özet — Hasta kartı ve uyum',
                'hint' => 'Aktif hasta kırılımları ve uyumsuzluk toplamı',
            ],
            'uyum_havuz_tc' => [
                'order' => 30,
                'label' => 'Uyumsuzluk — Havuz (TC ve kart)',
                'hint' => 'e-Rapor satırı ↔ hasta kartı eşleşmesi',
            ],
            'uyum_havuz_isaret' => [
                'order' => 40,
                'label' => 'Uyumsuzluk — Havuz (kimlik ve işaret)',
                'hint' => 'Ad/soyad farkı, yenileme işareti',
            ],
            'uyum_hasta' => [
                'order' => 50,
                'label' => 'Uyumsuzluk — Hasta kartı',
                'hint' => 'Kart işareti ↔ havuz kaydı',
            ],
        ];
    }

    /**
     * @return array{key: string, label: string, group_id: string, group: string, group_order: int, group_hint: string, listable: bool, kind: string}
     */
    private static function eraporHastaUyumCatalogRow(
        string $key,
        string $groupId,
        bool $listable,
        string $kind,
        ?string $label = null
    ): array {
        $groups = self::eraporHastaUyumMetricsGroups();
        $g = $groups[$groupId] ?? ['order' => 999, 'label' => $groupId, 'hint' => ''];
        $labels = self::eraporHastaUyumMetricLabels();

        return [
            'key' => $key,
            'label' => $label ?? ($labels[$key] ?? $key),
            'group_id' => $groupId,
            'group' => (string) $g['label'],
            'group_order' => (int) $g['order'],
            'group_hint' => (string) ($g['hint'] ?? ''),
            'listable' => $listable,
            'kind' => $kind,
        ];
    }

    /**
     * Özet + uyumsuzluk metrikleri (gruplu, sabit sıra).
     *
     * @return list<array{key: string, label: string, group_id: string, group: string, group_order: int, group_hint: string, listable: bool, kind: string}>
     */
    public static function eraporHastaUyumMetricsCatalog(): array {
        return [
            self::eraporHastaUyumCatalogRow('erapor_toplam', 'ozet_havuz', false, 'info', 'Havuzdaki toplam e-Rapor satırı'),
            self::eraporHastaUyumCatalogRow('erapor_kayitli_isaret', 'ozet_havuz', false, 'info', 'Havuzda «kayıtlı» işaretli (kayitlimi=1)'),
            self::eraporHastaUyumCatalogRow('erapor_disaridan_isaret', 'ozet_havuz', false, 'info', 'Havuzda «kayıtlı değil» işaretli (kayitlimi=0)'),
            self::eraporHastaUyumCatalogRow('erapor_yenilendi', 'ozet_havuz', false, 'info', 'Havuzda yenilendi işaretli (yenilendimi=1)'),
            self::eraporHastaUyumCatalogRow('erapor_hasta_aktif_eslesen', 'ozet_hasta', false, 'ok', 'Geçerli TC + aktif hasta kartı (eşleşen)'),
            self::eraporHastaUyumCatalogRow('hastalar_aktif_eraporlu', 'ozet_hasta', false, 'info', 'Aktif hasta — kartta e-Rapor işaretli'),
            self::eraporHastaUyumCatalogRow('hastalar_aktif_havuzda', 'ozet_hasta', false, 'info', 'Aktif hasta — havuzda en az bir kayıt'),
            self::eraporHastaUyumCatalogRow('uyumsuz_toplam', 'ozet_hasta', false, 'warn', 'Uyumsuzluk metrikleri toplamı (çift sayım olabilir)'),
            self::eraporHastaUyumCatalogRow('erapor_gecersiz_tc', 'uyum_havuz_tc', true, 'issue'),
            self::eraporHastaUyumCatalogRow('erapor_tc_coklu', 'uyum_havuz_tc', true, 'issue'),
            self::eraporHastaUyumCatalogRow('erapor_hasta_yok', 'uyum_havuz_tc', true, 'issue'),
            self::eraporHastaUyumCatalogRow('erapor_kayitli_ama_hasta_yok', 'uyum_havuz_tc', true, 'issue'),
            self::eraporHastaUyumCatalogRow('erapor_disaridan_ama_hasta_var', 'uyum_havuz_tc', true, 'issue'),
            self::eraporHastaUyumCatalogRow('erapor_disaridan_ama_hasta_aktif', 'uyum_havuz_tc', true, 'issue'),
            self::eraporHastaUyumCatalogRow('erapor_kayitli_ama_pasif', 'uyum_havuz_tc', true, 'issue'),
            self::eraporHastaUyumCatalogRow('erapor_hasta_pasif', 'uyum_havuz_tc', true, 'issue'),
            self::eraporHastaUyumCatalogRow('erapor_isim_uyumsuz', 'uyum_havuz_isaret', true, 'issue'),
            self::eraporHastaUyumCatalogRow('erapor_yenilendi_kayitsiz', 'uyum_havuz_isaret', true, 'issue'),
            self::eraporHastaUyumCatalogRow('hasta_aktif_eraporlu_havuz_yok', 'uyum_hasta', true, 'issue'),
            self::eraporHastaUyumCatalogRow('hasta_aktif_havuz_var_erapor_isaretsiz', 'uyum_hasta', true, 'issue'),
            self::eraporHastaUyumCatalogRow('hasta_pasif_havuz_var', 'uyum_hasta', true, 'issue'),
        ];
    }

    /**
     * @return list<array{key: string, label: string, group_id: string, group: string, group_order: int, group_hint: string, listable: bool, kind: string, count: int}>
     */
    public function eraporHastaUyumMetricsWithCounts(object $snap): array {
        $rows = self::eraporHastaUyumMetricsCatalog();
        foreach ($rows as $i => $row) {
            $k = $row['key'];
            $rows[$i]['count'] = (int) ($snap->$k ?? 0);
        }

        return $rows;
    }

    public function isEraporHastaUyumMetric(string $metric): bool {
        return isset(self::eraporHastaUyumMetricLabels()[$metric]);
    }

    public function isEraporHastaUyumPatientPrimary(string $metric): bool {
        return in_array($metric, [
            'hasta_aktif_eraporlu_havuz_yok',
            'hasta_aktif_havuz_var_erapor_isaretsiz',
            'hasta_pasif_havuz_var',
        ], true);
    }

    public function countEraporHastaUyumRows(string $metric): int {
        $parts = $this->eraporHastaUyumFilter($metric);
        if ($parts === null) {
            return 0;
        }
        if ($parts['from'] === 'hasta') {
            $sql = 'SELECT COUNT(h.id) FROM #__hastalar AS h ' . $parts['joins'] . ' WHERE ' . $parts['where'];
        } else {
            $hJoin = (strpos($parts['joins'], '#__hastalar') !== false) ? '' : 'LEFT JOIN #__hastalar AS h ON h.tckimlik = e.hastatckimlik AND h.kurum_id = e.kurum_id ';
            $sql = 'SELECT COUNT(e.id) FROM #__erapor AS e ' . $hJoin . $parts['joins'] . ' WHERE ' . $parts['where'];
        }

        return (int) $this->db->loadResultPrepared($sql);
    }

    /**
     * @return list<object>
     */
    public function getEraporHastaUyumRows(string $metric, string $orderFragment, int $limit, int $offset): array {
        $parts = $this->eraporHastaUyumFilter($metric);
        if ($parts === null) {
            return [];
        }
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $izK = $this->izSubK();
        $izlemSub = "(SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 1{$izK}) AS izlemsayisi,
            (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 0{$izK}) AS yizlemsayisi,
            (SELECT COUNT(id) FROM #__pizlemler WHERE hastatckimlik = h.tckimlik{$izK}) AS totalplanli";

        if ($parts['from'] === 'hasta') {
            $sql = "SELECT h.*, m.adi AS mahalle, ilc.adi AS ilce,
                {$izlemSub},
                (SELECT MIN(ex.id) FROM #__erapor ex WHERE ex.hastatckimlik = h.tckimlik AND ex.kurum_id = h.kurum_id) AS erapor_id,
                (SELECT MAX(ex.basvurutarihi) FROM #__erapor ex WHERE ex.hastatckimlik = h.tckimlik AND ex.kurum_id = h.kurum_id) AS erapor_basvuru,
                (SELECT COUNT(*) FROM #__erapor ex WHERE ex.hastatckimlik = h.tckimlik AND ex.kurum_id = h.kurum_id) AS erapor_adet
                FROM #__hastalar AS h
                LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle
                LEFT JOIN #__adrestablosu AS ilc ON ilc.id = h.ilce
                " . $parts['joins'] . '
                WHERE ' . $parts['where'] . '
                ORDER BY ' . $orderFragment . '
                LIMIT ' . (int) $offset . ', ' . (int) $limit;
        } else {
            $sql = "SELECT e.id AS erapor_id, e.hastatckimlik, e.isim AS erapor_isim, e.soyisim AS erapor_soyisim,
                e.ceptel1 AS erapor_tel, e.basvurutarihi, e.kayitlimi, e.yenilendimi, e.neden,
                b.bransadi,
                h.id AS hastaid, h.isim AS hasta_isim, h.soyisim AS hasta_soyisim, h.pasif, h.erapor AS hasta_erapor,
                h.tckimlik,
                {$izlemSub},
                (SELECT COUNT(*) FROM #__erapor e2 WHERE e2.hastatckimlik = e.hastatckimlik AND e2.kurum_id = e.kurum_id) AS erapor_adet
                FROM #__erapor AS e
                " . $this->sqlEraporBransJoin('e') . '
                LEFT JOIN #__hastalar AS h ON h.tckimlik = e.hastatckimlik AND h.kurum_id = e.kurum_id
                ' . $parts['joins'] . '
                WHERE ' . $parts['where'] . '
                ORDER BY ' . $orderFragment . '
                LIMIT ' . (int) $offset . ', ' . (int) $limit;
        }
        $list = $this->db->fetchObjectListPrepared($sql);

        return is_array($list) ? $list : [];
    }

    public function eraporHastaUyumOrderFragment(string $metric, string $orderby, string $orderdir): string {
        if ($this->isEraporHastaUyumPatientPrimary($metric)) {
            return \App\Helpers\QueryHelper::patientListOrderBy($orderby, $orderdir) . ', h.isim ASC, h.soyisim ASC';
        }
        $dir = strtoupper($orderdir) === 'DESC' ? 'DESC' : 'ASC';
        $map = [
            'e.basvurutarihi' => 'e.basvurutarihi',
            'e.isim' => 'e.isim',
            'e.hastatckimlik' => 'e.hastatckimlik',
            'h.pasif' => 'h.pasif',
        ];
        $col = $map[$orderby] ?? 'e.basvurutarihi';

        return $col . ' ' . $dir . ', e.id DESC';
    }

    /**
     * @return array{from:string, joins:string, where:string}|null
     */
    private function eraporHastaUyumFilter(string $metric): ?array {
        if (!$this->isEraporHastaUyumMetric($metric)) {
            return null;
        }
        $validTc = $this->sqlEraporValidTc('e');
        $active = $this->sqlPatientActive('h');
        $eraporFlag = $this->sqlPatientEraporFlag('h');
        $poolJoin = $this->sqlEraporPoolJoin('epool');
        $inPool = $this->sqlEraporInPool('epool');
        $notInPool = 'epool.hastatckimlik IS NULL';
        $parts = null;

        switch ($metric) {
            case 'erapor_gecersiz_tc':
                $parts = ['from' => 'erapor', 'joins' => '', 'where' => 'NOT ' . $validTc];
                break;
            case 'erapor_hasta_yok':
                $parts = ['from' => 'erapor', 'joins' => '', 'where' => $validTc . ' AND h.id IS NULL'];
                break;
            case 'erapor_disaridan_ama_hasta_var':
                $parts = ['from' => 'erapor', 'joins' => '', 'where' => $validTc . ' AND COALESCE(e.kayitlimi, 0) = 0 AND h.id IS NOT NULL'];
                break;
            case 'erapor_disaridan_ama_hasta_aktif':
                $parts = ['from' => 'erapor', 'joins' => '', 'where' => $validTc . ' AND COALESCE(e.kayitlimi, 0) = 0 AND ' . $active];
                break;
            case 'erapor_kayitli_ama_hasta_yok':
                $parts = ['from' => 'erapor', 'joins' => '', 'where' => $validTc . ' AND COALESCE(e.kayitlimi, 0) = 1 AND h.id IS NULL'];
                break;
            case 'erapor_kayitli_ama_pasif':
                $parts = ['from' => 'erapor', 'joins' => '', 'where' => $validTc . ' AND COALESCE(e.kayitlimi, 0) = 1 AND h.id IS NOT NULL AND NOT ' . $active];
                break;
            case 'erapor_hasta_pasif':
                $parts = ['from' => 'erapor', 'joins' => '', 'where' => $validTc . ' AND h.id IS NOT NULL AND NOT ' . $active];
                break;
            case 'erapor_isim_uyumsuz':
                $parts = [
                    'from' => 'erapor',
                    'joins' => '',
                    'where' => $validTc . ' AND h.id IS NOT NULL AND (UPPER(TRIM(e.isim)) <> UPPER(TRIM(h.isim)) OR UPPER(TRIM(e.soyisim)) <> UPPER(TRIM(h.soyisim)))',
                ];
                break;
            case 'erapor_yenilendi_kayitsiz':
                $parts = ['from' => 'erapor', 'joins' => '', 'where' => 'COALESCE(e.yenilendimi, 0) = 1 AND COALESCE(e.kayitlimi, 0) = 0'];
                break;
            case 'erapor_tc_coklu':
                $dupValidTc = $this->sqlEraporValidTc('dup_e');
                $dupEKurum = TenantSqlHelper::andEquals('dup_e');
                $parts = [
                    'from' => 'erapor',
                    'joins' => "INNER JOIN (
                        SELECT dup_e.hastatckimlik AS tc FROM #__erapor AS dup_e WHERE {$dupValidTc}{$dupEKurum} GROUP BY dup_e.hastatckimlik HAVING COUNT(*) > 1
                    ) AS dup ON e.hastatckimlik = dup.tc",
                    'where' => $validTc,
                ];
                break;
            case 'hasta_aktif_eraporlu_havuz_yok':
                $parts = ['from' => 'hasta', 'joins' => $poolJoin, 'where' => $active . ' AND ' . $eraporFlag . ' AND ' . $notInPool];
                break;
            case 'hasta_aktif_havuz_var_erapor_isaretsiz':
                $parts = ['from' => 'hasta', 'joins' => $poolJoin, 'where' => $active . ' AND NOT ' . $eraporFlag . ' AND ' . $inPool];
                break;
            case 'hasta_pasif_havuz_var':
                $parts = ['from' => 'hasta', 'joins' => $poolJoin, 'where' => 'NOT ' . $active . ' AND ' . $inPool];
                break;
            default:
                return null;
        }

        if ($parts === null) {
            return null;
        }

        return ($parts['from'] ?? '') === 'erapor'
            ? $this->filterPartsWithKurum($parts, 'h', 'e')
            : $this->filterPartsWithKurum($parts);
    }

    /** Bağımlılık kodu → okunur etiket (aktif hasta formu ile uyumlu). */
    public static function bagimlilikLabel(string $kod): string {
        $map = [
            '1' => 'Bağımsız',
            '2' => 'Yarı bağımlı',
            '3' => 'Tam bağımlı',
        ];
        $k = trim($kod);
        if ($k === '' || $k === '—') {
            return 'Belirtilmemiş';
        }

        return $map[$k] ?? ('Kod ' . $k);
    }

    /**
     * Bağımlılık dağılımı (aktif; etiketli satırlar).
     *
     * @return array{rows: list<object{kod: string, label: string, adet: int}>, total: int}
     */
    public function getBagimlilikDistributionLabeled(): array {
        $rows = [];
        $total = 0;
        foreach ($this->getBagimlilikActiveBreakdown() as $r) {
            $kod = trim((string) ($r->kod ?? ''));
            if ($kod === '—') {
                $kod = '';
            }
            $adet = (int) ($r->adet ?? 0);
            $total += $adet;
            $rows[] = (object) [
                'kod' => $kod,
                'label' => self::bagimlilikLabel($kod),
                'adet' => $adet,
            ];
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * İlçe sıralaması + en yoğun mahalleler (aktif).
     *
     * @return array{ilce: list<object>, mahalle: list<object>, aktif_toplam: int}
     */
    public function getGeoDistributionReport(int $topMahalle = 30): array {
        $topMahalle = max(5, min(80, $topMahalle));

        return [
            'ilce' => $this->getIlceActiveRanking(50),
            'mahalle' => $this->getTopMahalleActiveCounts($topMahalle),
            'aktif_toplam' => $this->getActivePatientCount(),
        ];
    }

    /** @return list<object> */
    private function getTopMahalleActiveCounts(int $limit): array {
        $sql = "SELECT m.adi AS mahalle_adi, il.adi AS ilce_adi, COUNT(h.id) AS adet
            FROM #__hastalar h
            LEFT JOIN #__adrestablosu m ON m.id = h.mahalle
            LEFT JOIN #__adrestablosu il ON il.id = h.ilce
            WHERE h.pasif = '0'
              AND h.mahalle IS NOT NULL AND TRIM(CAST(h.mahalle AS CHAR)) NOT IN ('', '0')" . TenantSqlHelper::andEquals('h') . "
            GROUP BY h.mahalle, h.ilce, m.adi, il.adi
            ORDER BY adet DESC, il.adi ASC, m.adi ASC
            LIMIT " . (int) $limit;
        $list = $this->db->fetchObjectListPrepared($sql);

        return is_array($list) ? $list : [];
    }

    /**
     * Boy / kilo / VKİ hesaplanabilirlik kapsamı (aktif hasta).
     *
     * @return array{
     *   aktif: int,
     *   has_boy: int, has_kilo: int, has_both: int, has_neither: int,
     *   computable_bmi: int,
     *   pct_boy: float, pct_kilo: float, pct_both: float, pct_computable: float
     * }
     */
    public function getAnthropometryCoverageReport(): array {
        $aktif = $this->getActivePatientCount();
        $boyOk = "(h.boy IS NOT NULL AND h.boy > 0 AND TRIM(CAST(h.boy AS CHAR)) NOT IN ('', '0'))";
        $kiloOk = "(h.kilo IS NOT NULL AND h.kilo > 0 AND TRIM(CAST(h.kilo AS CHAR)) NOT IN ('', '0'))";
        $sql = "SELECT
                SUM(CASE WHEN {$boyOk} THEN 1 ELSE 0 END) AS has_boy,
                SUM(CASE WHEN {$kiloOk} THEN 1 ELSE 0 END) AS has_kilo,
                SUM(CASE WHEN {$boyOk} AND {$kiloOk} THEN 1 ELSE 0 END) AS has_both
            FROM #__hastalar h WHERE h.pasif = '0'" . TenantSqlHelper::andEquals('h');
        $o = $this->db->fetchObjectPrepared($sql);
        $hasBoy = (int) ($o->has_boy ?? 0);
        $hasKilo = (int) ($o->has_kilo ?? 0);
        $hasBoth = (int) ($o->has_both ?? 0);
        $hasNeither = max(0, $aktif - $hasBoy - $hasKilo + $hasBoth);

        $computable = 0;
        if ($hasBoth > 0) {
            $rows = $this->db->fetchObjectListPrepared("SELECT h.boy, h.kilo FROM #__hastalar h WHERE h.pasif = '0' AND {$boyOk} AND {$kiloOk}" . TenantSqlHelper::andEquals('h')
            ) ?: [];
            foreach ($rows as $row) {
                $cm = BmiHelper::normalizeBoyCm($row->boy);
                $kg = BmiHelper::normalizeKiloKg($row->kilo);
                if ($cm !== null && $kg !== null) {
                    $cat = BmiHelper::classifyBmi(BmiHelper::calculateBmi($kg, $cm));
                    if (isset(BmiHelper::categories()[$cat])) {
                        $computable++;
                    }
                }
            }
        }

        $pct = static function (int $n, int $den): float {
            return $den > 0 ? round($n / $den * 100, 1) : 0.0;
        };

        return [
            'aktif' => $aktif,
            'has_boy' => $hasBoy,
            'has_kilo' => $hasKilo,
            'has_both' => $hasBoth,
            'has_neither' => $hasNeither,
            'computable_bmi' => $computable,
            'pct_boy' => $pct($hasBoy, $aktif),
            'pct_kilo' => $pct($hasKilo, $aktif),
            'pct_both' => $pct($hasBoth, $aktif),
            'pct_computable' => $pct($computable, $aktif),
        ];
    }

    /**
     * Kayıt süresi (tenure) grupları — aktif hastalar.
     *
     * @return array{rows: list<object{label: string, adet: int, pct: float}>, total: int, bilinmeyen: int}
     */
    public function getKayitTenureReport(): array {
        $kayit = $this->sqlKayitTarihiExpr('h');
        $sql = "SELECT
                SUM(CASE WHEN {$kayit} IS NULL THEN 1 ELSE 0 END) AS bilinmeyen,
                SUM(CASE WHEN {$kayit} IS NOT NULL AND DATEDIFF(CURDATE(), {$kayit}) < 183 THEN 1 ELSE 0 END) AS g_0_6,
                SUM(CASE WHEN {$kayit} IS NOT NULL AND DATEDIFF(CURDATE(), {$kayit}) BETWEEN 183 AND 364 THEN 1 ELSE 0 END) AS g_6_12,
                SUM(CASE WHEN {$kayit} IS NOT NULL AND DATEDIFF(CURDATE(), {$kayit}) BETWEEN 365 AND 1094 THEN 1 ELSE 0 END) AS g_1_3,
                SUM(CASE WHEN {$kayit} IS NOT NULL AND DATEDIFF(CURDATE(), {$kayit}) >= 1095 THEN 1 ELSE 0 END) AS g_3_plus
            FROM #__hastalar h WHERE h.pasif = '0'" . TenantSqlHelper::andEquals('h');
        $o = $this->db->fetchObjectPrepared($sql);
        $defs = [
            ['key' => 'g_0_6', 'label' => '0–6 ay'],
            ['key' => 'g_6_12', 'label' => '6–12 ay'],
            ['key' => 'g_1_3', 'label' => '1–3 yıl'],
            ['key' => 'g_3_plus', 'label' => '3 yıl ve üzeri'],
        ];
        $rows = [];
        $total = 0;
        foreach ($defs as $d) {
            $adet = (int) ($o->{$d['key']} ?? 0);
            $total += $adet;
            $rows[] = (object) ['label' => $d['label'], 'adet' => $adet, 'pct' => 0.0];
        }
        $bilinmeyen = (int) ($o->bilinmeyen ?? 0);
        foreach ($rows as $r) {
            $r->pct = $total > 0 ? round($r->adet / $total * 100, 1) : 0.0;
        }

        return ['rows' => $rows, 'total' => $total, 'bilinmeyen' => $bilinmeyen];
    }

    /**
     * Tanı sayısı (hastaliklar CSV) dağılımı — aktif.
     *
     * @return array{rows: list<object{label: string, adet: int, pct: float}>, total: int, ortalama: float}
     */
    public function getHastalikCountDistribution(): array {
        $list = $this->db->fetchObjectListPrepared("SELECT hastaliklar FROM #__hastalar WHERE pasif = '0'" . TenantSqlHelper::andBare()
        ) ?: [];
        $buckets = [
            '0' => ['label' => 'Tanı yok', 'adet' => 0],
            '1' => ['label' => '1 tanı', 'adet' => 0],
            '2' => ['label' => '2 tanı', 'adet' => 0],
            '3' => ['label' => '3 tanı', 'adet' => 0],
            '4+' => ['label' => '4+ tanı', 'adet' => 0],
        ];
        $sumTanilar = 0;
        foreach ($list as $row) {
            $raw = trim((string) ($row->hastaliklar ?? ''));
            if ($raw === '') {
                $buckets['0']['adet']++;
                continue;
            }
            $ids = array_filter(array_map('trim', explode(',', $raw)), static function ($id) {
                return $id !== '' && $id !== '0';
            });
            $n = count($ids);
            $sumTanilar += $n;
            if ($n === 0) {
                $buckets['0']['adet']++;
            } elseif ($n === 1) {
                $buckets['1']['adet']++;
            } elseif ($n === 2) {
                $buckets['2']['adet']++;
            } elseif ($n === 3) {
                $buckets['3']['adet']++;
            } else {
                $buckets['4+']['adet']++;
            }
        }
        $total = count($list);
        $rows = [];
        foreach ($buckets as $b) {
            $adet = (int) $b['adet'];
            $rows[] = (object) [
                'label' => $b['label'],
                'adet' => $adet,
                'pct' => $total > 0 ? round($adet / $total * 100, 1) : 0.0,
            ];
        }

        return [
            'rows' => $rows,
            'total' => $total,
            'ortalama' => $total > 0 ? round($sumTanilar / $total, 2) : 0.0,
        ];
    }

    /**
     * Klinik cihaz / özel durum bayrakları + çoklu cihaz sayısı (aktif).
     *
     * @return array{
     *   summary: object,
     *   flags: list<object{key: string, label: string, adet: int}>,
     *   multi: list<object{label: string, adet: int, pct: float}>,
     *   aktif: int
     * }
     */
    public function getClinicalProfileReport(): array {
        $summary = $this->getSpecialEquipmentSummary();
        $aktif = (int) ($summary->aktif_toplam ?? $this->getActivePatientCount());
        $flagExpr = PatientClinicalFlagsHelper::sqlFlagSumExpression('h');
        $sql = "SELECT
                SUM(CASE WHEN {$flagExpr} = 0 THEN 1 ELSE 0 END) AS c0,
                SUM(CASE WHEN {$flagExpr} = 1 THEN 1 ELSE 0 END) AS c1,
                SUM(CASE WHEN {$flagExpr} = 2 THEN 1 ELSE 0 END) AS c2,
                SUM(CASE WHEN {$flagExpr} >= 3 THEN 1 ELSE 0 END) AS c3plus
            FROM #__hastalar h WHERE h.pasif = '0'" . TenantSqlHelper::andEquals('h');
        $m = $this->db->fetchObjectPrepared($sql);
        $multiDefs = [
            ['key' => 'c0', 'label' => 'Cihaz / özel durum yok'],
            ['key' => 'c1', 'label' => '1 işaret'],
            ['key' => 'c2', 'label' => '2 işaret'],
            ['key' => 'c3plus', 'label' => '3+ işaret'],
        ];
        $multi = [];
        foreach ($multiDefs as $d) {
            $adet = (int) ($m->{$d['key']} ?? 0);
            $multi[] = (object) [
                'label' => $d['label'],
                'adet' => $adet,
                'pct' => $aktif > 0 ? round($adet / $aktif * 100, 1) : 0.0,
            ];
        }
        $flagMap = PatientClinicalFlagsHelper::statsReportLabels();
        $flags = [];
        foreach ($flagMap as $key => $label) {
            $flags[] = (object) [
                'key' => $key,
                'label' => $label,
                'adet' => (int) ($summary->$key ?? 0),
            ];
        }
        usort($flags, static function ($a, $b) {
            return $b->adet <=> $a->adet;
        });

        return [
            'summary' => $summary,
            'flags' => $flags,
            'multi' => $multi,
            'aktif' => $aktif,
        ];
    }

    /**
     * Demografik alan tamamlama (aktif) — veri sağlığı alt kümesi.
     *
     * @return array{aktif: int, rows: list<object{key: string, label: string, adet: int, pct: float}>}
     */
    public function getDemographicCompletenessReport(): array {
        $snap = $this->getDataHealthSnapshot();
        $aktif = $this->getActivePatientCount();
        $defs = [
            ['key' => 'dogum_yok', 'label' => 'Doğum tarihi eksik'],
            ['key' => 'cinsiyet_yok', 'label' => 'Cinsiyet eksik'],
            ['key' => 'hatali_tc', 'label' => 'Geçersiz / eksik TC'],
            ['key' => 'tel_yok', 'label' => 'Cep telefonu eksik'],
            ['key' => 'guvence_yok', 'label' => 'Güvence eksik'],
            ['key' => 'boy_yok', 'label' => 'Boy eksik'],
            ['key' => 'kilo_yok', 'label' => 'Kilo eksik'],
            ['key' => 'ilce_yok', 'label' => 'İlçe eksik'],
            ['key' => 'mahalle_yok', 'label' => 'Mahalle eksik'],
        ];
        $rows = [];
        foreach ($defs as $d) {
            $adet = (int) ($snap->{$d['key']} ?? 0);
            $rows[] = (object) [
                'key' => $d['key'],
                'label' => $d['label'],
                'adet' => $adet,
                'pct' => $aktif > 0 ? round($adet / $aktif * 100, 1) : 0.0,
            ];
        }

        return ['aktif' => $aktif, 'rows' => $rows];
    }

    /**
     * Yaş özeti: ortalama, medyan, basit gruplar (aktif; geçerli doğum tarihi).
     *
     * @return array{
     *   aktif: int, gecerli: int,
     *   ortalama: float, medyan: float,
     *   cocuk: int, yetiskin: int, yasli: int,
     *   bands: array<string, int>
     * }
     */
    public function getAgeSummaryReport(): array {
        $aktif = $this->getActivePatientCount();
        $yas = $this->getYasGrubuStats();
        $sql = "SELECT h.dogumtarihi FROM #__hastalar h
            WHERE h.pasif = '0'
              AND h.dogumtarihi IS NOT NULL AND h.dogumtarihi != '' AND h.dogumtarihi != '0000-00-00'" . TenantSqlHelper::andEquals('h');
        $list = $this->db->fetchObjectListPrepared($sql) ?: [];
        $ages = [];
        $bands = AgeBandHelper::emptyCounts();
        $today = new \DateTimeImmutable('today');
        foreach ($list as $row) {
            $bd = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $row->dogumtarihi);
            if (!$bd) {
                continue;
            }
            $age = $bd->diff($today)->y;
            $ages[] = $age;
            $band = AgeBandHelper::bandFromBirthDate((string) $row->dogumtarihi);
            if ($band !== null && isset($bands[$band])) {
                $bands[$band]++;
            }
        }
        sort($ages);
        $n = count($ages);
        $ortalama = $n > 0 ? round(array_sum($ages) / $n, 1) : 0.0;
        $medyan = 0.0;
        if ($n > 0) {
            $mid = (int) floor($n / 2);
            $medyan = $n % 2 === 1 ? (float) $ages[$mid] : round(($ages[$mid - 1] + $ages[$mid]) / 2, 1);
        }

        return [
            'aktif' => $aktif,
            'gecerli' => $n,
            'ortalama' => $ortalama,
            'medyan' => $medyan,
            'cocuk' => (int) ($yas->cocuk ?? 0),
            'yetiskin' => (int) ($yas->yetiskin ?? 0),
            'yasli' => (int) ($yas->yasli ?? 0),
            'bands' => $bands,
        ];
    }

    /**
     * Bekleyen hasta havuzu (pasif = -3) profili.
     *
     * @return array{
     *   total: int,
     *   by_ilce: list<object>,
     *   by_bagimlilik: list<object>,
     *   by_zaman: list<object>
     * }
     */
    public function getWaitingPoolProfile(): array {
        $total = $this->getWaitingPatientCount();
        $byIlce = $this->db->fetchObjectListPrepared("SELECT IFNULL(il.adi, 'Belirtilmemiş') AS label, COUNT(h.id) AS adet
             FROM #__hastalar h
             LEFT JOIN #__adrestablosu il ON il.id = h.ilce
             WHERE h.pasif = '-3'" . TenantSqlHelper::andEquals('h') . "
             GROUP BY h.ilce, il.adi
             ORDER BY adet DESC, label ASC
             LIMIT 20"
        );
        $byBag = $this->db->fetchObjectListPrepared("SELECT IFNULL(NULLIF(TRIM(bagimlilik), ''), '—') AS kod, COUNT(id) AS adet
             FROM #__hastalar WHERE pasif = '-3'" . TenantSqlHelper::andBare() . "
             GROUP BY IFNULL(NULLIF(TRIM(bagimlilik), ''), '—')
             ORDER BY adet DESC"
        );
        foreach ($byBag as $r) {
            $r->label = self::bagimlilikLabel((string) ($r->kod ?? ''));
        }
        $zNorm = ZamanDilimiHelper::sqlNormalizeCaseExpr('zaman');
        $byZaman = $this->db->fetchObjectListPrepared("SELECT
                CASE
                    WHEN zaman IS NULL OR TRIM(CAST(zaman AS CHAR)) = '' THEN 'belirtilmemiş'
                    WHEN {$zNorm} = " . ZamanDilimiHelper::SABAH . " THEN 'sabah'
                    WHEN {$zNorm} = " . ZamanDilimiHelper::OGLE . " THEN 'öğle'
                    WHEN {$zNorm} = " . ZamanDilimiHelper::AKSAM . " THEN 'akşam'
                    ELSE 'diğer'
                END AS slot,
                COUNT(id) AS adet
             FROM #__hastalar WHERE pasif = '-3'" . TenantSqlHelper::andBare() . "
             GROUP BY slot ORDER BY adet DESC"
        );
        $zamanLabels = [
            'sabah' => 'Sabah',
            'öğle' => 'Öğle',
            'akşam' => 'Akşam',
            'belirtilmemiş' => 'Belirtilmemiş',
            'diğer' => 'Diğer',
        ];
        foreach ($byZaman as $r) {
            $slot = (string) ($r->slot ?? '');
            $r->label = $zamanLabels[$slot] ?? $slot;
        }

        return [
            'total' => $total,
            'by_ilce' => is_array($byIlce) ? $byIlce : [],
            'by_bagimlilik' => is_array($byBag) ? $byBag : [],
            'by_zaman' => is_array($byZaman) ? $byZaman : [],
        ];
    }

    /**
     * Pansuman hastaları — zaman dilimi ve gün bilgisi (aktif).
     *
     * @return array{total: int, by_zaman: list<object>, pansuman_gunlu: int, gun_belirsiz: int}
     */
    public function getPansumanProfile(): array {
        $total = $this->getPansumanActiveCount();
        $pzNorm = ZamanDilimiHelper::sqlNormalizeCaseExpr('pzaman');
        $byZaman = $this->db->fetchObjectListPrepared("SELECT
                CASE
                    WHEN pzaman IS NULL OR TRIM(CAST(pzaman AS CHAR)) = '' THEN 'belirtilmemiş'
                    WHEN {$pzNorm} = " . ZamanDilimiHelper::SABAH . " THEN 'sabah'
                    WHEN {$pzNorm} = " . ZamanDilimiHelper::OGLE . " THEN 'öğle'
                    WHEN {$pzNorm} = " . ZamanDilimiHelper::AKSAM . " THEN 'akşam'
                    ELSE 'diğer'
                END AS slot,
                COUNT(id) AS adet
             FROM #__hastalar WHERE pasif = '0' AND pansuman = 1" . TenantSqlHelper::andBare() . "
             GROUP BY slot ORDER BY adet DESC"
        );
        $zamanLabels = ['sabah' => 'Sabah', 'öğle' => 'Öğle', 'akşam' => 'Akşam', 'belirtilmemiş' => 'Belirtilmemiş', 'diğer' => 'Diğer'];
        foreach ($byZaman as $r) {
            $slot = (string) ($r->slot ?? '');
            $r->label = $zamanLabels[$slot] ?? $slot;
        }
        $gunRow = $this->db->fetchObjectPrepared("SELECT
                SUM(CASE WHEN pgunleri IS NOT NULL AND TRIM(pgunleri) != '' THEN 1 ELSE 0 END) AS gunlu,
                SUM(CASE WHEN pgunleri IS NULL OR TRIM(pgunleri) = '' THEN 1 ELSE 0 END) AS belirsiz
             FROM #__hastalar WHERE pasif = '0' AND pansuman = 1" . TenantSqlHelper::andBare()
        );

        return [
            'total' => $total,
            'by_zaman' => is_array($byZaman) ? $byZaman : [],
            'pansuman_gunlu' => (int) ($gunRow->gunlu ?? 0),
            'gun_belirsiz' => (int) ($gunRow->belirsiz ?? 0),
        ];
    }

    /**
     * Kayıt yılı × kayıt anındaki yaş grubu (çocuk / yetişkin / yaşlı) — aktif.
     *
     * @return array{
     *   years: list<int>,
     *   groups: list<string>,
     *   matrix: array<int, array<string, int>>,
     *   row_totals: array<int, int>
     * }
     */
    public function getKayitKohortAgeReport(int $yearSpan = 10): array {
        $yearSpan = max(3, min(20, $yearSpan));
        $minYear = (int) date('Y') - $yearSpan + 1;
        $kayit = $this->sqlKayitTarihiExpr('h');
        $bandExpr = AgeBandHelper::sqlBandCaseExpr('h.dogumtarihi', $kayit);
        $unknown = AgeBandHelper::UNKNOWN_KEY;
        $sql = "SELECT YEAR({$kayit}) AS kayit_yili,
                COALESCE({$bandExpr}, " . $this->db->quote($unknown) . ") AS yas_banti,
                COUNT(h.id) AS adet
            FROM #__hastalar h
            WHERE h.pasif = '0' AND {$kayit} IS NOT NULL AND YEAR({$kayit}) >= " . (int) $minYear . TenantSqlHelper::andEquals('h') . "
            GROUP BY kayit_yili, yas_banti
            ORDER BY kayit_yili ASC";
        $list = $this->db->fetchObjectListPrepared($sql) ?: [];
        $bandKeys = AgeBandHelper::keysWithUnknown();
        $groups = AgeBandHelper::labelsWithUnknown();
        $years = [];
        $matrix = [];
        $rowTotals = [];
        $colTotals = array_fill_keys($bandKeys, 0);
        for ($y = $minYear; $y <= (int) date('Y'); $y++) {
            $years[] = $y;
            $matrix[$y] = array_fill_keys($bandKeys, 0);
            $rowTotals[$y] = 0;
        }
        foreach ($list as $r) {
            $yil = (int) ($r->kayit_yili ?? 0);
            $band = (string) ($r->yas_banti ?? $unknown);
            if (!isset($matrix[$yil])) {
                continue;
            }
            if (!isset($groups[$band])) {
                $band = $unknown;
            }
            $adet = (int) ($r->adet ?? 0);
            $matrix[$yil][$band] = ($matrix[$yil][$band] ?? 0) + $adet;
            $rowTotals[$yil] = ($rowTotals[$yil] ?? 0) + $adet;
            $colTotals[$band] = ($colTotals[$band] ?? 0) + $adet;
        }

        return [
            'years' => $years,
            'band_keys' => $bandKeys,
            'band_labels' => $groups,
            'groups' => $groups,
            'matrix' => $matrix,
            'row_totals' => $rowTotals,
            'col_totals' => $colTotals,
        ];
    }

    /**
     * Güvence türü × yaş bandı (aktif; ageGenderBands bantları).
     *
     * @return array{
     *   band_keys: list<string>,
     *   band_labels: array<string, string>,
     *   guvences: list<string>,
     *   matrix: array<string, array<string, int>>,
     *   row_totals: array<string, int>,
     *   col_totals: array<string, int>,
     *   aktif: int
     * }
     */
    public function getGuvenceAgeBandsReport(): array {
        $bandKeys = AgeBandHelper::keys();
        $bandLabels = AgeBandHelper::labels();
        $unknownKey = '_unknown';
        $bandLabels[$unknownKey] = 'Yaş bilinmiyor';
        $allBandKeys = array_merge($bandKeys, [$unknownKey]);

        $sql = "SELECT h.dogumtarihi, IFNULL(g.guvenceadi, 'Belirtilmemiş') AS guvence_adi
            FROM #__hastalar h
            LEFT JOIN #__guvence g ON h.guvence = g.id
            WHERE h.pasif = '0'" . TenantSqlHelper::andEquals('h');
        $list = $this->db->fetchObjectListPrepared($sql) ?: [];

        $matrix = [];
        $rowTotals = [];
        $colTotals = array_fill_keys($allBandKeys, 0);
        $aktif = 0;

        foreach ($list as $row) {
            $aktif++;
            $guvence = trim((string) ($row->guvence_adi ?? ''));
            if ($guvence === '') {
                $guvence = 'Belirtilmemiş';
            }
            if (!isset($matrix[$guvence])) {
                $matrix[$guvence] = array_fill_keys($allBandKeys, 0);
                $rowTotals[$guvence] = 0;
            }
            $band = AgeBandHelper::bandFromBirthDate($row->dogumtarihi ?? null);
            if ($band === null || !isset($matrix[$guvence][$band])) {
                $band = $unknownKey;
            }
            $matrix[$guvence][$band]++;
            $rowTotals[$guvence]++;
            $colTotals[$band]++;
        }

        arsort($rowTotals);
        $guvences = array_keys($rowTotals);

        return [
            'band_keys' => $allBandKeys,
            'band_labels' => $bandLabels,
            'guvences' => $guvences,
            'matrix' => $matrix,
            'row_totals' => $rowTotals,
            'col_totals' => $colTotals,
            'aktif' => $aktif,
        ];
    }

    /**
     * İletişim, fotoğraf ve ebeveyn adı doluluk oranları (aktif).
     *
     * @return array{aktif: int, rows: list<object{key: string, label: string, dolu: int, bos: int|null, pct: float, placeholder?: bool, list_metric?: string}>}
     */
    public function getDemographicFieldCoverageReport(): array {
        $aktif = $this->getActivePatientCount();
        $anneTrim = $this->sqlAnneAdiTrim('h');
        $babaTrim = $this->sqlBabaAdiTrim('h');
        $annePlaceholder = $this->sqlParentNamePlaceholderMatch($anneTrim);
        $babaPlaceholder = $this->sqlParentNamePlaceholderMatch($babaTrim);
        $sql = "SELECT
                SUM(CASE WHEN TRIM(IFNULL(h.ceptel1, '')) != '' THEN 1 ELSE 0 END) AS ceptel1_dolu,
                SUM(CASE WHEN TRIM(IFNULL(h.ceptel2, '')) != '' THEN 1 ELSE 0 END) AS ceptel2_dolu,
                SUM(CASE WHEN
                    TRIM(IFNULL(h.ceptel1, '')) != '' OR TRIM(IFNULL(h.ceptel2, '')) != ''
                THEN 1 ELSE 0 END) AS herhangi_tel,
                SUM(CASE WHEN TRIM(IFNULL(h.profil_foto, '')) != '' THEN 1 ELSE 0 END) AS foto_dolu,
                SUM(CASE WHEN {$anneTrim} != '' AND NOT {$annePlaceholder} THEN 1 ELSE 0 END) AS anne_dolu,
                SUM(CASE WHEN {$annePlaceholder} THEN 1 ELSE 0 END) AS anne_nokta,
                SUM(CASE WHEN {$babaTrim} != '' AND NOT {$babaPlaceholder} THEN 1 ELSE 0 END) AS baba_dolu,
                SUM(CASE WHEN {$babaPlaceholder} THEN 1 ELSE 0 END) AS baba_nokta
            FROM #__hastalar h WHERE h.pasif = '0'" . TenantSqlHelper::andEquals('h');
        $o = $this->db->fetchObjectPrepared($sql);
        $defs = [
            ['key' => 'ceptel1', 'label' => 'Cep telefonu (ceptel1)', 'field' => 'ceptel1_dolu'],
            ['key' => 'ceptel2', 'label' => 'İkinci cep (ceptel2)', 'field' => 'ceptel2_dolu'],
            ['key' => 'herhangi_tel', 'label' => 'Herhangi telefon', 'field' => 'herhangi_tel'],
            ['key' => 'profil_foto', 'label' => 'Profil fotoğrafı', 'field' => 'foto_dolu'],
            ['key' => 'anne', 'label' => 'Anne adı', 'field' => 'anne_dolu'],
            ['key' => 'anne_nokta', 'label' => 'Anne adı (. / .. / ...)', 'field' => 'anne_nokta', 'placeholder' => true, 'list_metric' => 'anne_nokta'],
            ['key' => 'baba', 'label' => 'Baba adı', 'field' => 'baba_dolu'],
            ['key' => 'baba_nokta', 'label' => 'Baba adı (. / .. / ...)', 'field' => 'baba_nokta', 'placeholder' => true, 'list_metric' => 'baba_nokta'],
        ];
        $rows = [];
        foreach ($defs as $d) {
            $dolu = (int) ($o->{$d['field']} ?? 0);
            $isPlaceholder = !empty($d['placeholder']);
            $bos = $isPlaceholder ? null : max(0, $aktif - $dolu);
            $row = (object) [
                'key' => $d['key'],
                'label' => $d['label'],
                'dolu' => $dolu,
                'bos' => $bos,
                'pct' => $aktif > 0 ? round($dolu / $aktif * 100, 1) : 0.0,
                'placeholder' => $isPlaceholder,
            ];
            if (!empty($d['list_metric'])) {
                $row->list_metric = (string) $d['list_metric'];
            }
            $rows[] = $row;
        }

        return ['aktif' => $aktif, 'rows' => $rows];
    }

    /** Anne/baba adı placeholder listesi metrikleri (anahtar → başlık). */
    public static function parentNamePlaceholderMetricLabels(): array {
        return [
            'anne_nokta' => 'Anne adı (. / .. / ...)',
            'baba_nokta' => 'Baba adı (. / .. / ...)',
            'herhangi' => 'Anne veya baba adı (. / .. / ...)',
        ];
    }

    public function isParentNamePlaceholderMetric(string $metric): bool {
        return isset(self::parentNamePlaceholderMetricLabels()[$metric]);
    }

    public static function isParentNamePlaceholderValue(mixed $raw): bool {
        $v = trim((string) $raw);

        return in_array($v, ['.', '..', '...'], true);
    }

    public function countParentNamePlaceholderPatients(string $metric): int {
        $parts = $this->parentNamePlaceholderPatientFilter($metric);
        if ($parts === null) {
            return 0;
        }
        $sql = 'SELECT COUNT(h.id) FROM #__hastalar AS h ' . $parts['joins'] . ' WHERE ' . $parts['where'];

        return (int) $this->db->loadResultPrepared($sql);
    }

    /**
     * @return list<object>
     */
    public function getParentNamePlaceholderPatients(string $metric, string $orderFragment, int $limit, int $offset): array {
        $parts = $this->parentNamePlaceholderPatientFilter($metric);
        if ($parts === null) {
            return [];
        }
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $izK = $this->izSubK();
        $sql = "SELECT h.*, m.adi AS mahalle, ilc.adi AS ilce,
            (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik{$izK}) AS toplamizlem,
            (SELECT MAX(izlemtarihi) FROM #__izlemler WHERE hastatckimlik = h.tckimlik{$izK}) AS sonizlem,
            (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 1{$izK}) AS izlemsayisi,
            (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 0{$izK}) AS yizlemsayisi,
            (SELECT COUNT(id) FROM #__pizlemler WHERE hastatckimlik = h.tckimlik{$izK}) AS totalplanli
            FROM #__hastalar AS h
            LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle
            LEFT JOIN #__adrestablosu AS ilc ON ilc.id = h.ilce
            " . $parts['joins'] . '
            WHERE ' . $parts['where'] . '
            ORDER BY ' . $orderFragment . '
            LIMIT ' . (int) $offset . ', ' . (int) $limit;
        $list = $this->db->fetchObjectListPrepared($sql);

        return is_array($list) ? $list : [];
    }

    private function sqlAnneAdiTrim(string $alias = 'h'): string {
        return 'TRIM(IFNULL(' . $alias . ".anneAdi, ''))";
    }

    private function sqlBabaAdiTrim(string $alias = 'h'): string {
        return 'TRIM(IFNULL(' . $alias . ".babaAdi, ''))";
    }

    private function sqlParentNamePlaceholderMatch(string $trimExpr): string {
        return "({$trimExpr} IN ('.', '..', '...'))";
    }

    /** @return array{joins:string, where:string}|null */
    private function parentNamePlaceholderPatientFilter(string $metric): ?array {
        if (!$this->isParentNamePlaceholderMetric($metric)) {
            return null;
        }
        $kurumSql = TenantSqlHelper::andEquals('h');
        $pasif = "h.pasif = '0'";
        $annePh = $this->sqlParentNamePlaceholderMatch($this->sqlAnneAdiTrim('h'));
        $babaPh = $this->sqlParentNamePlaceholderMatch($this->sqlBabaAdiTrim('h'));

        return match ($metric) {
            'anne_nokta' => ['joins' => '', 'where' => $pasif . ' AND ' . $annePh . $kurumSql],
            'baba_nokta' => ['joins' => '', 'where' => $pasif . ' AND ' . $babaPh . $kurumSql],
            'herhangi' => ['joins' => '', 'where' => $pasif . ' AND (' . $annePh . ' OR ' . $babaPh . ')' . $kurumSql],
            default => null,
        };
    }
}
