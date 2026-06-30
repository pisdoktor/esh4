<?php
namespace App\Models;

use App\Helpers\IzlemYapilmamaNedenHelper;
use App\Helpers\IslemIdSettings;
use App\Helpers\VisitIslemHelper;
use App\Helpers\ZamanDilimiHelper;
use App\Helpers\TenantSqlHelper;

/**
 * Planlı İzlem Modeli
 */
class PlannedVisit extends BaseModel {
    public $id = null;
    public $kurum_id = 1;
    public $hastatckimlik = null;
    public $planlanantarih = null;
    public $yapilacak = null;
    public $zaman = null;
    public $planiyapan = null;
    public $plantarihi = null;
    public $oncelik = 1;
    public $aciklama = null;
    public $notlar = null;
    public $durum = 0;

    public function __construct() {
        parent::__construct('#__pizlemler', 'id');
    }

    private function nakilIslemId(): int
    {
        return IslemIdSettings::resolvedInt('nakil_islem_id');
    }

    /** Takvim «İzlem» ile günün planı planli satırları — nakil dışı kayıt koşulu. */
    private function sqlNonNakilPlanCondition(string $yapilacakColumn, int $nakilId): string
    {
        $nakil = (int) $nakilId;

        return "(
            TRIM(COALESCE({$yapilacakColumn}, '')) = ''
            OR FIND_IN_SET('{$nakil}', REPLACE(TRIM({$yapilacakColumn}), ' ', '')) = 0
            OR TRIM(REPLACE(TRIM({$yapilacakColumn}), ' ', '')) != '{$nakil}'
        )";
    }
    
    /**
     * Eski ESH pizlemler getIzlemList ile uyumlu: tarih aralığı, işlem (FIND_IN_SET),
     * sadece aktif hastalar (pasif=0), isteğe bağlı durum (bekleyen/tamam),
     * çoklu yapilacak / planiyapan için GROUP_CONCAT alt sorguları.
     *
     * @param string $durum       '' = tümü, '0' bekleyen, '1' tamamlandı
     * @param string $dateFrom    Y-m-d veya boş
     * @param string $dateTo      Y-m-d veya boş
     * @param int    $secim       0 = tüm işlemler, aksi halde #__islemler.id
     */
    public function getAllPlanned(
        $limit = 20,
        $offset = 0,
        $search = '',
        $durum = '',
        $ordering = '',
        $dateFrom = '',
        $dateTo = '',
        $secim = 0
    ) {
        [$whereSql, $params] = $this->buildPlannedListWhere($search, $durum, $dateFrom, $dateTo, $secim);
        $orderSql = $this->plannedOrderClause($ordering);

        $query = "SELECT p.*, h.id AS hid, h.isim, h.soyisim, h.cinsiyet, h.gecici, h.tckimlik,
                         h.ceptel1,
                         (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 1) as izlemsayisi,
                         (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 0) as yizlemsayisi,
                         (SELECT COUNT(id) FROM #__pizlemler WHERE hastatckimlik = h.tckimlik AND COALESCE(durum, 0) = 0) as totalplanli,
                         a1.adi AS ilce, a2.adi AS mahalle,
                         (SELECT GROUP_CONCAT(isl2.islemadi ORDER BY isl2.id SEPARATOR ', ')
                            FROM #__islemler isl2
                            WHERE FIND_IN_SET(isl2.id, REPLACE(p.yapilacak, ' ', ''))) AS yapilacaklar,
                         (SELECT GROUP_CONCAT(u.name ORDER BY u.id SEPARATOR ', ')
                            FROM #__users u
                            WHERE FIND_IN_SET(u.id, REPLACE(CAST(p.planiyapan AS CHAR), ' ', ''))) AS planlayanlar
                  FROM #__pizlemler AS p
                  LEFT JOIN #__hastalar AS h ON h.tckimlik = p.hastatckimlik
                  LEFT JOIN #__adrestablosu AS a1 ON h.ilce = a1.id
                  LEFT JOIN #__adrestablosu AS a2 ON h.mahalle = a2.id
                  $whereSql
                  $orderSql
                  LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        return $this->db->fetchObjectListPrepared($query, $params);
    }

    public function countAllPlanned(
        $search = '',
        $durum = '',
        $dateFrom = '',
        $dateTo = '',
        $secim = 0
    ) {
        [$whereSql, $params] = $this->buildPlannedListWhere($search, $durum, $dateFrom, $dateTo, $secim);
        $query = "SELECT COUNT(p.id) FROM #__pizlemler AS p
                  LEFT JOIN #__hastalar AS h ON h.tckimlik = p.hastatckimlik
                  $whereSql";

        return $this->db->loadResultPrepared($query, $params);
    }

    /**
     * Tek hastanın planlı izlem satırları (TC). Genel liste filtresi (yalnız aktif hasta) uygulanmaz.
     *
     * @param string $durum '' tümü, '0' bekleyen, '1' tamamlandı
     */
    public function countPatientPlans(string $tc, string $durum = ''): int {
        $tc = trim($tc);
        if ($tc === '') {
            return 0;
        }
        $where = ['p.hastatckimlik = ?'];
        $params = [$tc];
        if ($durum === '0' || $durum === '1') {
            $where[] = 'p.durum = ' . (int) $durum;
        }
        $sql = 'SELECT COUNT(p.id) FROM #__pizlemler AS p WHERE ' . implode(' AND ', $where);

        return (int) $this->db->loadResultPrepared($sql, $params);
    }

    /**
     * @param string $ordering plannedOrderClause biçimi, örn. p.planlanantarih-ASC
     * @return array<int, object>
     */
    public function getPatientPlans(string $tc, int $limit = 50, int $offset = 0, string $durum = '', string $ordering = 'p.planlanantarih-ASC'): array {
        $tc = trim($tc);
        if ($tc === '') {
            return [];
        }
        $where = ['p.hastatckimlik = ?'];
        $params = [$tc];
        if ($durum === '0' || $durum === '1') {
            $where[] = 'p.durum = ' . (int) $durum;
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $orderSql = $this->plannedOrderClause($ordering);

        $query = "SELECT p.*, h.id AS hid, h.isim, h.soyisim, h.cinsiyet, h.gecici, h.tckimlik,
                         p.kurum_id, ku.ad AS kurum_adi,
                         h.ceptel1,
                         (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 1) as izlemsayisi,
                         (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 0) as yizlemsayisi,
                         (SELECT COUNT(id) FROM #__pizlemler WHERE hastatckimlik = h.tckimlik AND COALESCE(durum, 0) = 0) as totalplanli,
                         a1.adi AS ilce, a2.adi AS mahalle,
                         (SELECT GROUP_CONCAT(isl2.islemadi ORDER BY isl2.id SEPARATOR ', ')
                            FROM #__islemler isl2
                            WHERE FIND_IN_SET(isl2.id, REPLACE(p.yapilacak, ' ', ''))) AS yapilacaklar,
                         (SELECT GROUP_CONCAT(u.name ORDER BY u.id SEPARATOR ', ')
                            FROM #__users u
                            WHERE FIND_IN_SET(u.id, REPLACE(CAST(p.planiyapan AS CHAR), ' ', ''))) AS planlayanlar
                  FROM #__pizlemler AS p
                  LEFT JOIN #__hastalar AS h ON h.tckimlik = p.hastatckimlik
                  LEFT JOIN #__kurumlar AS ku ON ku.id = p.kurum_id
                  LEFT JOIN #__adrestablosu AS a1 ON h.ilce = a1.id
                  LEFT JOIN #__adrestablosu AS a2 ON h.mahalle = a2.id
                  $whereSql
                  $orderSql
                  LIMIT " . (int) $limit . " OFFSET " . (int) $offset;

        return $this->db->fetchObjectListPrepared($query, $params);
    }

    /**
     * Eski sistem: yalnızca aktif hastalar listelenir (h.pasif = 0).
     *
     * @return string WHERE ... veya boş
     */
    private function buildPlannedListWhere($search, $durum, $dateFrom, $dateTo, $secim) {
        $where = [];
        $params = [];
        $where[] = "h.pasif = '0'";

        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $where[] = "(p.hastatckimlik LIKE ? OR h.isim LIKE ? OR h.soyisim LIKE ?
                OR CONCAT(COALESCE(h.isim,''), ' ', COALESCE(h.soyisim,'')) LIKE ?)";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }
        if ($durum !== '' && ($durum === '0' || $durum === '1')) {
            $where[] = 'p.durum = ' . (int)$durum;
        }
        $df = $this->normalizeYmd($dateFrom);
        $dt = $this->normalizeYmd($dateTo);
        if ($df !== null) {
            $where[] = 'p.planlanantarih >= ?';
            $params[] = $df;
        }
        if ($dt !== null) {
            $where[] = 'p.planlanantarih <= ?';
            $params[] = $dt;
        }
        if ((int)$secim > 0) {
            $id = (int)$secim;
            $where[] = "FIND_IN_SET($id, REPLACE(p.yapilacak, ' ', ''))";
        }
        TenantSqlHelper::mergeParts($where, 'h', 'kurum_id');

        return count($where) ? ['WHERE ' . implode(' AND ', $where), $params] : ['', $params];
    }

    private function normalizeYmd($d) {
        if ($d === null || $d === '') {
            return null;
        }
        $d = trim((string)$d);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : null;
    }

    /**
     * Eski sistem: ordering = "p.planlanantarih-ASC" (son tire ayırır).
     */
    private function plannedOrderClause($ordering) {
        $fallback = 'ORDER BY p.planlanantarih ASC, h.isim ASC, h.soyisim ASC, a2.adi ASC';
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
            'p.planlanantarih' => true,
            'p.zaman' => true,
            'p.oncelik' => true,
            'p.yapilacak' => true,
            'h.isim' => true,
            'h.soyisim' => true,
            'h.tckimlik' => true,
            'a2.adi' => true,
            'h.pasif' => true,
            'h.pasiftarihi' => true,
        ];
        if (!isset($allowed[$col])) {
            return $fallback;
        }
        return "ORDER BY $col $dir, p.id ASC";
    }

    //Aylık planlı getir
    //Aylık planlı getir (Pansumanlar Dahil Edildi)
    public function getMonthPlans($year, $month) {
    
        $startDate = sprintf('%04d-%02d-01', (int)$year, (int)$month);
        $endDate = date("Y-m-t", strtotime($startDate));
        $kurumH = TenantSqlHelper::andEquals('h', 'kurum_id');
        $kurumBare = TenantSqlHelper::andBare('kurum_id');
        
        $list = ['resProc' => [], 'resDone' => [], 'resFirst' => [], 'resPansuman' => []];

        $nakilId = $this->nakilIslemId();
        $nonNakilPlanSql = $this->sqlNonNakilPlanCondition('p.yapilacak', $nakilId);
        // 1. Planlı İzlemler — nakil işlem id'si config'ten gelir; yapilacak virgüllü olabilir (FIND_IN_SET)
        // ozel: listede nakil id'si var; normal: getDailyPlans planli ile aynı filtre (boş yapilacak dahil)
        $query1 = "SELECT DATE_FORMAT(p.planlanantarih, '%Y-%m-%d') AS tarih, 
                   SUM(CASE WHEN FIND_IN_SET('" . (int) $nakilId . "', REPLACE(TRIM(p.yapilacak), ' ', '')) > 0 THEN 1 ELSE 0 END) AS ozel_total,
                   SUM(CASE WHEN {$nonNakilPlanSql} THEN 1 ELSE 0 END) AS normal_total
                   FROM #__pizlemler AS p
                   INNER JOIN #__hastalar AS h ON h.tckimlik = p.hastatckimlik AND h.pasif = '0'
                   WHERE p.planlanantarih BETWEEN ? AND ? 
                   AND COALESCE(p.durum, 0) = 0{$kurumH}
                   GROUP BY p.planlanantarih";
        $rawResults = $this->db->fetchObjectListPrepared($query1, [$startDate, $endDate]);
        
        foreach($rawResults as $row) {
            $list['resProc'][$row->tarih] = $row;
        }

        // 2. Yapılan İzlemler (Y) - Anahtar: izlemtarihi
        $query2 = "SELECT DATE_FORMAT(izlemtarihi, '%Y-%m-%d') as tarih, COUNT(id) as total 
                   FROM #__izlemler 
                   WHERE izlemtarihi BETWEEN ? AND ? AND yapildimi=1{$kurumBare}
                   GROUP BY izlemtarihi";
        $rawResults2 = $this->db->fetchObjectListPrepared($query2, [$startDate, $endDate]);
        
        foreach($rawResults2 as $row2) {
            $list['resDone'][$row2->tarih] = $row2;
        }

        // 3. İlk Ziyaretler (+) - Anahtar: randevutarihi
        $query3 = "SELECT DATE_FORMAT(randevutarihi, '%Y-%m-%d') as tarih, COUNT(id) as total 
                   FROM #__hastalar 
                   WHERE randevutarihi BETWEEN ? AND ? AND pasif='-3'{$kurumBare}
                   GROUP BY randevutarihi";
        $rawResults3 = $this->db->fetchObjectListPrepared($query3, [$startDate, $endDate]);
        
        foreach($rawResults3 as $row3) {
            $list['resFirst'][$row3->tarih] = $row3;
        }

        // 4. PLANLI PANSUMANLAR (Haftalık periyoda göre hesaplama)
        // Eski takvim (site/modules/takvim): o gün için hastanın herhangi bir izlem kaydı varsa pansuman sayılmaz / listeden düşer.
        $sqlPansuman = "SELECT tckimlik, pgunleri FROM #__hastalar WHERE pansuman = 1 AND pasif = '0' AND pgunleri != '' AND TRIM(COALESCE(tckimlik,'')) != ''{$kurumBare}";
        $pansumanHastalari = $this->db->fetchObjectListPrepared($sqlPansuman);

        if ($pansumanHastalari) {
            $tcList = [];
            foreach ($pansumanHastalari as $h) {
                $tc = trim((string) ($h->tckimlik ?? ''));
                if ($tc !== '') {
                    $tcList[$tc] = true;
                }
            }
            $tcKeys = array_keys($tcList);
            $visitOnDate = [];
            if ($tcKeys !== []) {
                foreach (array_chunk($tcKeys, 400) as $chunk) {
                    [$inSql, $inParams] = $this->db->whereInClause($chunk);
                    $vsql = "SELECT hastatckimlik, izlemtarihi FROM #__izlemler
                             WHERE izlemtarihi BETWEEN ? AND ? AND hastatckimlik IN ($inSql){$kurumBare}";
                    $vParams = array_merge([$startDate, $endDate], $inParams);
                    foreach ($this->db->fetchObjectListPrepared($vsql, $vParams) as $vr) {
                        $tc = (string) ($vr->hastatckimlik ?? '');
                        $d = (string) ($vr->izlemtarihi ?? '');
                        if ($tc !== '' && $d !== '') {
                            $visitOnDate[$tc . '|' . $d] = true;
                        }
                    }
                }
            }

            $period = new \DatePeriod(
                new \DateTime($startDate),
                new \DateInterval('P1D'),
                (new \DateTime($endDate))->modify('+1 day')
            );

            foreach ($period as $date) {
                $tarihKey = $date->format('Y-m-d');
                $gunIndex = (int) $date->format('w'); // 0: Pazar, 6: Cumartesi

                $gunlukToplam = 0;
                foreach ($pansumanHastalari as $hasta) {
                    $gunlerRaw = array_filter(array_map('trim', explode(',', (string) ($hasta->pgunleri ?? ''))), static fn ($g) => $g !== '');
                    $gunlerInts = array_map(static fn ($g) => (int) $g, $gunlerRaw);
                    if (!in_array($gunIndex, $gunlerInts, true)) {
                        continue;
                    }
                    $tc = trim((string) ($hasta->tckimlik ?? ''));
                    if ($tc === '' || isset($visitOnDate[$tc . '|' . $tarihKey])) {
                        continue;
                    }
                    $gunlukToplam++;
                }

                if ($gunlukToplam > 0) {
                    $list['resPansuman'][$tarihKey] = (object)['tarih' => $tarihKey, 'total' => $gunlukToplam];
                }
            }
        }
        
        return $list;
    }
    //Günlük planlı getir
    public function getDailyPlans($date) {
        $data = [[], [], []]; // Sabah, Öğle, Akşam
        $nakiller = [];
        $nakilId = $this->nakilIslemId();
        $kurumH = TenantSqlHelper::andEquals('h', 'kurum_id');
        $mpJoin = MahallePlan::joinSqlForHasta('m', 'mp', 'h');
        $bolgeSel = MahallePlan::bolgeSelectSql('mp', 'bolge');

        $islemLabelSql = "(SELECT GROUP_CONCAT(isl.islemadi ORDER BY isl.id SEPARATOR ', ')
                FROM #__islemler isl
                WHERE FIND_IN_SET(isl.id, REPLACE(TRIM(p.yapilacak), ' ', '')))";

        $dayOfWeek = (int) date('w', strtotime($date));
        $nonNakilPlanSql = $this->sqlNonNakilPlanCondition('p.yapilacak', $nakilId);
        $zPlanMatch = 'COALESCE(' . ZamanDilimiHelper::sqlNormalizeCaseExpr('p.zaman') . ', ' . ZamanDilimiHelper::SABAH . ')';
        $zHastaMatch = 'COALESCE(' . ZamanDilimiHelper::sqlNormalizeCaseExpr('h.zaman') . ', ' . ZamanDilimiHelper::SABAH . ')';

        for ($i = 0; $i < 3; $i++) {
            $data[$i] = ['planli' => [], 'ilkziyaret' => [], 'pansuman' => []];
        }

        foreach (ZamanDilimiHelper::activeVardiyaIndexes() as $i) {
            $zamanKodu = ZamanDilimiHelper::fromVardiyaIndex($i);
            // Planlanmış izlemler (nakil dışı: listede nakil id'si dışında en az bir işlem id’si)
            $sql = "SELECT p.*, h.id AS hastaid, h.isim, h.soyisim, h.tckimlik, h.ceptel1, h.coords, il.adi AS ilce, m.adi AS mahalle, {$bolgeSel},
                    $islemLabelSql AS islem_label
                    FROM #__pizlemler AS p
                    LEFT JOIN #__hastalar AS h ON p.hastatckimlik = h.tckimlik
                    LEFT JOIN #__adrestablosu AS il ON il.id = h.ilce
                    LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle
                    {$mpJoin}
                    WHERE p.planlanantarih = ?
                    AND {$zPlanMatch} = $zamanKodu
                    AND COALESCE(p.durum, 0) = 0
                    AND h.pasif = '0'
                    AND {$nonNakilPlanSql}{$kurumH}
                    ORDER BY h.mahalle ASC";
            $data[$i]['planli'] = $this->db->fetchObjectListPrepared($sql, [$date]);
            //Planlanmış ilk ziyaretler
            $sql = "SELECT h.id AS hastaid, h.tckimlik, h.isim, h.soyisim, h.ceptel1, h.coords, il.adi AS ilce, m.adi AS mahalle, {$bolgeSel}, 'İlk Ziyaret' as islem_label 
                    FROM #__hastalar AS h
                    LEFT JOIN #__adrestablosu AS il ON il.id = h.ilce
                    LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle
                    {$mpJoin}
                    WHERE {$zHastaMatch} = $zamanKodu AND h.pasif='-3' AND h.randevutarihi = ?{$kurumH}
                    ORDER BY h.mahalle ASC";
            $data[$i]['ilkziyaret'] = $this->db->fetchObjectListPrepared($sql, [$date]);
            
            // 3. Periyodik pansumanlar (pzaman 1–3; eski 0–2 kayıtları da eşlenir)
            $pzamanMatch = '(CASE WHEN CAST(COALESCE(h.pzaman, 1) AS SIGNED) BETWEEN 1 AND 3 THEN CAST(h.pzaman AS SIGNED) '
                . 'WHEN CAST(COALESCE(h.pzaman, 1) AS SIGNED) BETWEEN 0 AND 2 THEN CAST(h.pzaman AS SIGNED) + 1 ELSE 1 END)';
            $sqlPansuman = "SELECT h.id AS hastaid, h.tckimlik, h.isim, h.soyisim, h.ceptel1, h.coords, il.adi AS ilce, m.adi AS mahalle, {$bolgeSel}, 'Pansuman' as islem_label 
                            FROM #__hastalar AS h
                            LEFT JOIN #__adrestablosu AS il ON il.id = h.ilce
                            LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle
                            {$mpJoin}
                            WHERE {$pzamanMatch} = $zamanKodu 
                            AND h.pansuman = 1 
                            AND h.pasif = '0' 
                            AND FIND_IN_SET(" . (int) $dayOfWeek . ", h.pgunleri) > 0
                            AND NOT EXISTS (
                                SELECT 1 FROM #__izlemler iz
                                WHERE iz.hastatckimlik = h.tckimlik AND iz.izlemtarihi = ?
                            ){$kurumH}
                            ORDER BY h.mahalle ASC";
            $data[$i]['pansuman'] = $this->db->fetchObjectListPrepared($sqlPansuman, [$date]);
            
        }
        // Planlanmış nakiller (listedeki id’lerden biri nakil işlem id'si)
        $sqlNakil = "SELECT p.*, h.id AS hastaid, h.isim, h.soyisim, h.tckimlik, h.ceptel1, h.coords, il.adi AS ilce, m.adi AS mahalle, {$bolgeSel},
                     $islemLabelSql AS islem_label
                     FROM #__pizlemler AS p
                     LEFT JOIN #__hastalar AS h ON p.hastatckimlik = h.tckimlik
                     LEFT JOIN #__adrestablosu AS il ON il.id = h.ilce
                     LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle
                     {$mpJoin}
                     WHERE p.planlanantarih = ?
                     AND COALESCE(p.durum, 0) = 0
                     AND h.pasif = '0'
                     AND FIND_IN_SET('" . (int) $nakilId . "', REPLACE(TRIM(p.yapilacak), ' ', '')) > 0{$kurumH}";
        $nakiller = $this->db->fetchObjectListPrepared($sqlNakil, [$date]);

        return [
            'sabah' => $data[0],
            'ogle' => $data[1],
            'aksam' => $data[2],
            'nakiller' => $nakiller,
            'activeSlots' => ZamanDilimiHelper::uiSections(),
        ];
    }

    /**
     * Günün planındaki benzersiz hastalar (MERNİS taraması için).
     *
     * @return list<array{tckimlik: string, isim: string, soyisim: string, hastaid: int}>
     */
    public function getDailyPlanUniquePatients(string $date): array
    {
        $plans = $this->getDailyPlans($date);
        $byTc = [];

        $add = static function (object $item) use (&$byTc): void {
            $tc = preg_replace('/\D+/', '', (string) ($item->tckimlik ?? ''));
            if (strlen($tc) !== 11) {
                return;
            }
            $byTc[$tc] = [
                'tckimlik' => $tc,
                'isim' => (string) ($item->isim ?? ''),
                'soyisim' => (string) ($item->soyisim ?? ''),
                'hastaid' => (int) ($item->hastaid ?? 0),
            ];
        };

        foreach (['sabah', 'ogle', 'aksam'] as $slot) {
            if (!isset($plans[$slot]) || !is_array($plans[$slot])) {
                continue;
            }
            foreach (['planli', 'ilkziyaret', 'pansuman'] as $type) {
                $list = $plans[$slot][$type] ?? [];
                if (!is_array($list)) {
                    continue;
                }
                foreach ($list as $item) {
                    if (is_object($item)) {
                        $add($item);
                    }
                }
            }
        }

        $nakiller = $plans['nakiller'] ?? [];
        if (is_array($nakiller)) {
            foreach ($nakiller as $item) {
                if (is_object($item)) {
                    $add($item);
                }
            }
        }

        return array_values($byTc);
    }

    /**
     * Takvim için tüm planları getirir
     */
    public function getAllForCalendar() {
        $query = "SELECT p.id, 
                         CONCAT(h.isim, ' ', h.soyisim) as title, 
                         p.planlanantarih as start,
                         p.oncelik
                  FROM #__pizlemler p 
                  JOIN #__hastalar h ON p.hastatckimlik = h.tckimlik";
        
        return $this->db->fetchObjectListPrepared($query);
    }

    /**
     * Belirli bir tarihteki veya gelecekteki izlemleri getirir
     */
    public function getUpcoming($limit = 10) {
        $query = "SELECT p.*, h.isim, h.soyisim 
                  FROM #__pizlemler p 
                  JOIN #__hastalar h ON p.hastatckimlik = h.tckimlik 
                  WHERE p.planlanantarih >= CURDATE()
                  AND COALESCE(p.durum, 0) = 0
                  ORDER BY p.planlanantarih ASC
                  LIMIT " . (int) $limit;
        
        return $this->db->fetchObjectListPrepared($query);
    }

    /**
     * Hastanın tüm izlem geçmişini getirir
     */
    public function getHistoryByPatientTc($tc, $limit = 10, $offset = 0) {
        $query = "SELECT p.*, u.username as yapan_personel 
                  FROM #__pizlemler p 
                  LEFT JOIN #__users u ON p.planiyapan = u.id 
                  WHERE p.hastatckimlik = ? 
                  ORDER BY p.planlanantarih DESC
                  LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset;
                  
        return $this->db->fetchObjectListPrepared($query, [$tc]);
    }

    /**
     * İzlemi Başka Bir Tarihe Güncelle (Erteleme/Öne Çekme)
     */
    public function reschedule($newDate) {
        $this->planlanantarih = $newDate;
        return $this->store();
    }

    /**
     * Kaydı Sil (Artık 'yapildimi' olmadığı için iptal edilen kayıtlar silinebilir)
     */
    public function remove() {
        if ($this->id) {
            return $this->db->executePrepared('DELETE FROM #__pizlemler WHERE id = ?', [(int) $this->id]);
        }
        return false;
    }
    
    // ÖNERİ: Gecikmiş planları getir (Bugünden önce ve henüz gerçekleşmemiş)
    public function getOverdueVisits() {
        $sql = "SELECT p.*, h.isim, h.soyisim FROM #__pizlemler p 
                JOIN #__hastalar h ON p.hastatckimlik = h.tckimlik
                WHERE p.planlanantarih < CURDATE() AND p.id NOT IN (SELECT id FROM #__izlemler)
                ORDER BY p.planlanantarih ASC";

        return $this->db->fetchObjectListPrepared($sql);
    }
    
    public function getIzlemler($ts) {
        $q_islem_sql = "(SELECT GROUP_CONCAT(islemadi SEPARATOR ', ') FROM #__islemler WHERE FIND_IN_SET(id, p.yapilacak) > 0) as islem_detaylari";

        $last_date_sql = "(SELECT izlemtarihi FROM #__izlemler WHERE hastatckimlik = p.hastatckimlik ORDER BY izlemtarihi DESC LIMIT 1) as son_izlem_tarihi";

        $last_islem_sql = "(SELECT GROUP_CONCAT(i2.islemadi SEPARATOR ', ')
                        FROM #__izlemler iz2
                        INNER JOIN #__islemler i2 ON FIND_IN_SET(i2.id, iz2.yapilan) > 0
                        WHERE iz2.id = (
                            SELECT iz3.id
                            FROM #__izlemler iz3
                            WHERE iz3.hastatckimlik = p.hastatckimlik
                            ORDER BY iz3.izlemtarihi DESC, iz3.id DESC
                            LIMIT 1
                        )) as son_izlem_yapilanlar";

        $eff = Address::effectiveCoordsExpr('h', 'k');
        $effWhere = Address::effectiveCoordsWhereClause('h', 'k');
        $kapinoJoin = Address::kapinoJoinSql('h', 'k');
        $mpJoin = MahallePlan::joinSqlForHasta('m', 'mp', 'h');
        $bolgeSel = MahallePlan::bolgeSelectSql('mp', 'bolge_id');
        $q = "SELECT h.id, h.isim, h.soyisim, h.tckimlik, {$eff} AS coords, h.mahalle as mahalle_id, 
                 m.adi as mahalle_adi, {$bolgeSel}, p.zaman AS zaman_kodu,
                 p.oncelik, p.yapilacak, $q_islem_sql, $last_date_sql, $last_islem_sql
          FROM #__pizlemler AS p 
          LEFT JOIN #__hastalar AS h ON p.hastatckimlik = h.tckimlik 
          LEFT JOIN #__adrestablosu AS m ON h.mahalle = m.id 
          {$mpJoin}
          {$kapinoJoin}
          WHERE p.planlanantarih = ? AND COALESCE(p.durum, 0) = 0 AND h.pasif = '0' AND {$effWhere}";

        return $this->db->fetchObjectListPrepared($q, [(string) $ts]);
    }

    /**
     * Seçilen günde (Y-m-d) hastanın bekleyen planlı izlem (#__pizlemler, durum=0) satırı sayısı.
     */
    public function countPendingForPatientOnDate(string $tc, string $ymd): int
    {
        $tc = trim($tc);
        if ($tc === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ymd)) {
            return 0;
        }
        $sql = 'SELECT COUNT(*) FROM #__pizlemler WHERE hastatckimlik = ? AND planlanantarih = ? AND COALESCE(durum, 0) = 0';

        return (int) $this->db->loadResultPrepared($sql, [$tc, $ymd]);
    }

    /**
     * Aynı gün + aynı zaman diliminde mevcut planlarda çakışan işlem id'leri (durum 0 ve 1).
     *
     * @param int[] $requestedIslemIds
     * @return int[]
     */
    public function overlappingYapilacakIslemIdsForPatientOnDate(
        string $tc,
        string $ymd,
        array $requestedIslemIds,
        int $requestedZaman,
        int $excludePlanId = 0
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
        $excludeSql = $excludePlanId > 0 ? ' AND id <> ' . (int) $excludePlanId : '';
        $sql = "SELECT yapilacak FROM #__pizlemler
                WHERE hastatckimlik = ? AND planlanantarih = ?
                  AND {$zExpr} = {$zNorm}{$excludeSql}";
        $rows = $this->db->fetchObjectListPrepared($sql, [$tc, $ymd]);
        if (!$rows) {
            return [];
        }

        $overlap = [];
        foreach ($rows as $row) {
            $existing = VisitIslemHelper::yapilanCsvToIntIds($row->yapilacak ?? null);
            $overlap = array_merge($overlap, array_intersect($requestedIslemIds, $existing));
        }

        return array_values(array_unique($overlap));
    }

    /**
     * Pasif (aktif olmayan) hastalarda bekleyen planlı izlem sayısı.
     */
    public function countPassivePendingForPassivePatients(): int
    {
        return (int) $this->db->loadResultPrepared(
            "SELECT COUNT(p.id) FROM #__pizlemler AS p
             INNER JOIN #__hastalar AS h ON h.tckimlik = p.hastatckimlik AND h.kurum_id = p.kurum_id
             WHERE COALESCE(p.durum, 0) = 0 AND h.pasif != '0'"
            . TenantSqlHelper::andEquals('h', 'kurum_id')
        );
    }

    public function countPassivePendingPlans(string $search = '', string $pasifFilter = ''): int
    {
        [$whereSql, $params] = $this->buildPassivePendingWhere($search, $pasifFilter);
        $query = "SELECT COUNT(p.id) FROM #__pizlemler AS p
                  INNER JOIN #__hastalar AS h ON h.tckimlik = p.hastatckimlik AND h.kurum_id = p.kurum_id
                  $whereSql";

        return (int) $this->db->loadResultPrepared($query, $params);
    }

    /**
     * @return array<int, object>
     */
    public function getPassivePendingPlans(
        int $limit,
        int $offset,
        string $search = '',
        string $ordering = '',
        string $pasifFilter = ''
    ): array {
        [$whereSql, $params] = $this->buildPassivePendingWhere($search, $pasifFilter);
        $orderSql = $this->plannedOrderClause($ordering);

        $query = "SELECT p.*, h.id AS hid, h.isim, h.soyisim, h.cinsiyet, h.gecici, h.tckimlik, h.pasif, h.pasiftarihi,
                         h.ceptel1,
                         (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 1) AS izlemsayisi,
                         (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi = 0) AS yizlemsayisi,
                         (SELECT COUNT(id) FROM #__pizlemler WHERE hastatckimlik = h.tckimlik AND COALESCE(durum, 0) = 0) AS totalplanli,
                         a1.adi AS ilce, a2.adi AS mahalle,
                         (SELECT GROUP_CONCAT(isl2.islemadi ORDER BY isl2.id SEPARATOR ', ')
                            FROM #__islemler isl2
                            WHERE FIND_IN_SET(isl2.id, REPLACE(p.yapilacak, ' ', ''))) AS yapilacaklar,
                         (SELECT GROUP_CONCAT(u.name ORDER BY u.id SEPARATOR ', ')
                            FROM #__users u
                            WHERE FIND_IN_SET(u.id, REPLACE(CAST(p.planiyapan AS CHAR), ' ', ''))) AS planlayanlar
                  FROM #__pizlemler AS p
                  INNER JOIN #__hastalar AS h ON h.tckimlik = p.hastatckimlik AND h.kurum_id = p.kurum_id
                  LEFT JOIN #__adrestablosu AS a1 ON h.ilce = a1.id
                  LEFT JOIN #__adrestablosu AS a2 ON h.mahalle = a2.id
                  $whereSql
                  $orderSql
                  LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset;

        $list = $this->db->fetchObjectListPrepared($query, $params);

        return is_array($list) ? $list : [];
    }

    /**
     * @return list<int>
     */
    public function deletePassivePendingByIds(array $ids): int
    {
        $deleted = 0;
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id < 1) {
                continue;
            }
            $row = $this->db->loadResultPrepared(
                "SELECT p.id FROM #__pizlemler AS p
                 INNER JOIN #__hastalar AS h ON h.tckimlik = p.hastatckimlik AND h.kurum_id = p.kurum_id
                 WHERE p.id = ? AND COALESCE(p.durum, 0) = 0 AND h.pasif != '0'"
                . TenantSqlHelper::andEquals('h', 'kurum_id'),
                [$id]
            );
            if ($row && $this->delete($id)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Plan tarihi ile hasta pasif tarihine göre sınıf: before | after | same | unknown.
     */
    public static function passivePendingSplitSide(?string $planYmd, ?string $pasifYmd): string
    {
        $planYmd = trim((string) $planYmd);
        $pasifYmd = trim((string) $pasifYmd);
        if ($pasifYmd === '' || $pasifYmd === '0000-00-00') {
            return 'unknown';
        }
        if ($planYmd === '' || $planYmd === '0000-00-00') {
            return 'unknown';
        }
        if ($planYmd < $pasifYmd) {
            return 'before';
        }
        if ($planYmd > $pasifYmd) {
            return 'after';
        }

        return 'same';
    }

    /**
     * Pasif tarihinden önceki bekleyen planları yapılmadı izlem kaydı + plan kapatma (durum=1).
     *
     * @return array{marked: int, skipped: int, plans_closed: int}
     */
    public function markPassivePendingMissedByIds(array $ids): array
    {
        $marked = 0;
        $skipped = 0;
        $plansClosed = 0;
        $neden = IzlemYapilmamaNedenHelper::compose(5);
        $aciklama = 'Pasif dosya — planlı izlem otomatik kapatma (yapılmadı).';

        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id < 1) {
                continue;
            }
            $row = $this->db->fetchObjectPrepared(
                "SELECT p.*, h.pasiftarihi
                 FROM #__pizlemler AS p
                 INNER JOIN #__hastalar AS h ON h.tckimlik = p.hastatckimlik AND h.kurum_id = p.kurum_id
                 WHERE p.id = ? AND COALESCE(p.durum, 0) = 0 AND h.pasif != '0'"
                . TenantSqlHelper::andEquals('h', 'kurum_id'),
                [$id]
            );
            if (!$row) {
                $skipped++;
                continue;
            }
            $side = self::passivePendingSplitSide(
                (string) ($row->planlanantarih ?? ''),
                (string) ($row->pasiftarihi ?? '')
            );
            if ($side !== 'before') {
                $skipped++;
                continue;
            }

            $tc = trim((string) ($row->hastatckimlik ?? ''));
            $planYmd = trim((string) ($row->planlanantarih ?? ''));
            $yapilacak = trim((string) ($row->yapilacak ?? ''));
            $islemIds = VisitIslemHelper::yapilanCsvToIntIds($yapilacak);
            $zaman = ZamanDilimiHelper::clamp($row->zaman ?? null);
            $izlemiyapan = trim((string) ($row->planiyapan ?? ''));
            if ($izlemiyapan === '') {
                $izlemiyapan = (string) (int) ($_SESSION['user_id'] ?? 0);
            }

            if ($tc !== '' && $planYmd !== '' && $islemIds !== []) {
                $visit = new Visit();
                $overlap = $visit->overlappingIslemIdsForPatientOnDate($tc, $planYmd, $islemIds, $zaman, 0);
                if ($overlap === []) {
                    if ($visit->save([
                        'hastatckimlik' => $tc,
                        'izlemtarihi' => $planYmd,
                        'yapilan' => $yapilacak,
                        'yapildimi' => 0,
                        'neden' => $neden,
                        'izlemiyapan' => $izlemiyapan,
                        'zaman' => $zaman,
                        'aciklama' => $aciklama,
                        'arac' => null,
                    ])) {
                        $marked++;
                    }
                }
            }

            $plan = new self();
            if ($plan->load($id)) {
                $plan->bind(['durum' => 1]);
                if ($plan->store()) {
                    $plansClosed++;
                } else {
                    $skipped++;
                }
            } else {
                $skipped++;
            }
        }

        return ['marked' => $marked, 'skipped' => $skipped, 'plans_closed' => $plansClosed];
    }

    /**
     * Pasif tarihinden sonraki bekleyen planları siler.
     */
    public function deletePassivePendingAfterPasifByIds(array $ids): int
    {
        $deleted = 0;
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id < 1) {
                continue;
            }
            $row = $this->db->fetchObjectPrepared(
                "SELECT p.id, p.planlanantarih, h.pasiftarihi
                 FROM #__pizlemler AS p
                 INNER JOIN #__hastalar AS h ON h.tckimlik = p.hastatckimlik AND h.kurum_id = p.kurum_id
                 WHERE p.id = ? AND COALESCE(p.durum, 0) = 0 AND h.pasif != '0'"
                . TenantSqlHelper::andEquals('h', 'kurum_id'),
                [$id]
            );
            if (!$row) {
                continue;
            }
            $side = self::passivePendingSplitSide(
                (string) ($row->planlanantarih ?? ''),
                (string) ($row->pasiftarihi ?? '')
            );
            if ($side !== 'after') {
                continue;
            }
            if ($this->delete($id)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Pasif hasta + bekleyen plan — liste WHERE.
     *
     * @return string WHERE ...
     */
    private function buildPassivePendingWhere(string $search, string $pasifFilter): array
    {
        $where = [];
        $params = [];
        $where[] = 'COALESCE(p.durum, 0) = 0';
        $where[] = "h.pasif != '0'";

        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $where[] = "(p.hastatckimlik LIKE ? OR h.isim LIKE ? OR h.soyisim LIKE ?
                OR CONCAT(COALESCE(h.isim,''), ' ', COALESCE(h.soyisim,'')) LIKE ?)";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        $pasifFilter = trim($pasifFilter);
        if ($pasifFilter !== '' && $pasifFilter !== 'all') {
            if (preg_match('/^-?\d+$/', $pasifFilter)) {
                $where[] = 'h.pasif = ?';
                $params[] = $pasifFilter;
            }
        }

        TenantSqlHelper::mergeParts($where, 'h', 'kurum_id');
        TenantSqlHelper::mergeParts($where, 'p', 'kurum_id');

        return ['WHERE ' . implode(' AND ', $where), $params];
    }
}