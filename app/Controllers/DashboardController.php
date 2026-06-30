<?php
namespace App\Controllers;

use App\Models\PlannedVisit;
use App\Models\KonsRandevu;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\Address;
use App\Models\Islem;
use App\Models\Ekip;
use App\Models\User;
use App\Models\Cache;
use App\Helpers\AppSettings;
use App\Helpers\AuthHelper;
use App\Helpers\BadgeHelper;
use App\Helpers\IslemIdSettings;
use App\Helpers\ThemeViewHelper;
use App\Helpers\TomTomClient;
use App\Helpers\ZamanDilimiHelper;
use App\Helpers\ValidationHelper;

class DashboardController {
    
    private $ayarlar;
    
    public function __construct() {
        // Sınıf oluşturulduğu anda ayarları bir kez yüklüyoruz
        $this->ayarlar = array(
            'tomtom_APIKEY' => defined('TOMTOM_KEY') ? (string) TOMTOM_KEY : '',
            'oncelik_yuksek_bonusu' => defined('oncelik_yuksek_bonusu') ? oncelik_yuksek_bonusu : 75,
            'mahalle_bonusu' => defined('mahalle_bonusu') ? mahalle_bonusu : 40,
            'bolge_bonusu' => defined('bolge_bonusu') ? bolge_bonusu : 50,
            'is_yuku_cezasi' => defined('is_yuku_cezasi') ? is_yuku_cezasi : 10,
            'personel_dosya_sayisi' => defined('personel_dosya_sayisi') ? personel_dosya_sayisi : 10, 
            'sureler' => [
                'pansuman' => defined('sure_pansuman') ? sure_pansuman : 15,
                'muayene' => defined('sure_muayene') ? sure_muayene : 25,
                'izlem' => defined('sure_izlem') ? sure_izlem : 20
            ],
            'merkez' => [
                'lat' => defined('START_LAT') ? START_LAT : 37.783291,
                'lon' => defined('START_LNG') ? START_LNG : 29.079663
            ]
        );
    }
    
    public function index() {
        $plannedModel = new PlannedVisit();
        $patientModel = new Patient();
        $calendarState = $this->dashboardCalendarState();
        $year = $calendarState['year'];
        $month = $calendarState['month'];
        $calendarData = $plannedModel->getMonthPlans($year, $month);
        $calendarData['resRandevu'] = $this->dashboardMonthRandevuCounts($year, $month);
        $calendarHtml = $this->renderCalendar($year, $month, $calendarData);
        $currentMonthName = $calendarState['currentMonthName'];
        $prevMonth = $calendarState['prevMonth'];
        $prevYear = $calendarState['prevYear'];
        $nextMonth = $calendarState['nextMonth'];
        $nextYear = $calendarState['nextYear'];

        $tcLookupQuery = '';
        $tcLookupPatient = null;
        $tcLookupMessage = '';
        if (isset($_GET['tc_lookup'])) {
            $tcLookupQuery = ValidationHelper::tcDigitsOnly($_GET['tc_lookup']);
            if ($tcLookupQuery === '') {
                $tcLookupMessage = 'Lütfen 11 haneli TC Kimlik numarası girin.';
            } elseif (!ValidationHelper::isTcLength11($tcLookupQuery)) {
                $tcLookupMessage = 'TC Kimlik numarası 11 haneli olmalıdır.';
            } else {
                $tcLookupPatient = $patientModel->findByTc($tcLookupQuery);
                if (!$tcLookupPatient) {
                    $tcLookupMessage = 'Bu TC Kimlik numarasıyla kayıtlı hasta bulunamadı.';
                }
            }
        }

        $pageTitle = "Planlı İşlem Takvimi";
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'dashboard/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Takvimde ay gecisleri icin aylik tabloyu JSON HTML parcasi olarak dondurur.
     */
    public function calendarMonth() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $state = $this->dashboardCalendarState();
        $calendarData = (new PlannedVisit())->getMonthPlans($state['year'], $state['month']);
        $calendarData['resRandevu'] = $this->dashboardMonthRandevuCounts($state['year'], $state['month']);
        $html = $this->renderCalendar($state['year'], $state['month'], $calendarData);

        echo json_encode([
            'ok' => true,
            'html' => $html,
            'year' => $state['year'],
            'month' => $state['month'],
            'monthLabel' => $state['currentMonthName'] . ' ' . $state['year'],
            'prevYear' => $state['prevYear'],
            'prevMonth' => $state['prevMonth'],
            'nextYear' => $state['nextYear'],
            'nextMonth' => $state['nextMonth'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return array{
     *   year: int,
     *   month: int,
     *   currentMonthName: string,
     *   prevYear: int,
     *   prevMonth: int,
     *   nextYear: int,
     *   nextMonth: int
     * }
     */
    private function dashboardCalendarState(): array {
        $year = (int) ($_GET['year'] ?? date('Y'));
        $month = (int) ($_GET['month'] ?? date('n'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }

        $monthNames = ["", "Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
        $currentMonthName = $monthNames[$month];

        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth === 0) {
            $prevMonth = 12;
            $prevYear--;
        }

        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth === 13) {
            $nextMonth = 1;
            $nextYear++;
        }

        return [
            'year' => $year,
            'month' => $month,
            'currentMonthName' => $currentMonthName,
            'prevYear' => $prevYear,
            'prevMonth' => $prevMonth,
            'nextYear' => $nextYear,
            'nextMonth' => $nextMonth,
        ];
    }

    /**
     * @return array<string, int> Y-m-d => adet (branş randevu; modül kapalıysa boş)
     */
    private function dashboardMonthRandevuCounts(int $year, int $month): array
    {
        if (!AppSettings::isModuleEnabled('randevu')) {
            return [];
        }

        return (new KonsRandevu())->countByDayInMonth($year, $month);
    }

    private function renderCalendar($year, $month, $plans) {
    $firstDay = date('N', strtotime("$year-$month-01"));
    $daysInMonth = date('t', strtotime("$year-$month-01"));
    $daysOfWeek = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];

    // Tablo başlığı tasarımı
    $html = '<table class="table table-bordered m-0" style="table-layout: fixed;">';
    $html .= '<thead><tr class="text-center bg-light text-muted small">';
    foreach ($daysOfWeek as $d) {
        $html .= "<th class='py-2 fw-bold' style='font-size: 0.75rem;'>$d</th>";
    }
    $html .= '</tr></thead><tbody><tr>';

    // Boş hücreler
    for ($i = 1; $i < $firstDay; $i++) {
        $html .= '<td class="bg-light border-0"></td>';
    }

    for ($day = 1; $day <= $daysInMonth; $day++) {
        if (($day + $firstDay - 2) % 7 == 0 && $day != 1) {
            $html .= '</tr><tr>';
        }
        
        $currentDate = sprintf('%04d-%02d-%02d', (int)$year, (int)$month, (int)$day);
        $isToday = ($currentDate == date('Y-m-d')) ? 'bg-primary-subtle border border-primary' : '';

        // Hücre Başlangıcı
        $html .= "<td class='calendar-day p-1 $isToday' onclick='getDailyTasks(\"$currentDate\")' 
                    style='cursor:pointer; height:100px; vertical-align:top; transition: 0.2s;'>";
        
        // Gün Numarası (Sağ Üstte)
        $html .= "<div class='text-end mb-1'><span class='fw-bold small px-1' style='font-size: 0.8rem;'>$day</span></div>";
        
        $html .= "<div class='d-flex flex-column gap-1 esh-cal-tasks'>";

        if (isset($plans['resProc'][$currentDate])) {
            $p = $plans['resProc'][$currentDate];
            if ($p->normal_total > 0) {
                $html .= $this->calendarTaskIcon('fa-calendar-check', (int) $p->normal_total, 'İzlem');
            }
            if ($p->ozel_total > 0) {
                $html .= $this->calendarTaskIcon('fa-ambulance', (int) $p->ozel_total, 'Nakil');
            }
        }

        if (isset($plans['resPansuman'][$currentDate])) {
            $html .= $this->calendarTaskIcon('fa-plus-square', (int) $plans['resPansuman'][$currentDate]->total, 'Pansuman');
        }

        if (isset($plans['resFirst'][$currentDate])) {
            $html .= $this->calendarTaskIcon('fa-user-plus', (int) $plans['resFirst'][$currentDate]->total, 'İlk ziyaret');
        }

        if (!empty($plans['resRandevu'][$currentDate])) {
            $html .= $this->calendarTaskIcon('fa-calendar-days', (int) $plans['resRandevu'][$currentDate], 'Randevu');
        }

        if (isset($plans['resDone'][$currentDate])) {
            $visitDoneUrl = \App\Helpers\UrlHelper::fromRequestParams([
                'controller' => 'Visit',
                'action' => 'index',
                'date_from' => $currentDate,
                'date_to' => $currentDate,
            ]);
            $html .= $this->calendarTaskIcon(
                'fa-check-circle',
                (int) $plans['resDone'][$currentDate]->total,
                'Yapılan',
                true,
                $visitDoneUrl
            );
        }

        $html .= "</div></td>";
    }

    // Satırı tamamlamak için eksik hücreleri ekleyelim (Opsiyonel ama görünümü düzeltir)
    $remainingDays = (7 - (($daysInMonth + $firstDay - 1) % 7)) % 7;
    for ($i = 0; $i < $remainingDays; $i++) {
        $html .= '<td class="bg-light border-0"></td>';
    }

    $html .= '</tr></tbody></table>';
    return $html;
}

    private function calendarTaskIcon(string $faIcon, int $count, string $label, bool $done = false, ?string $href = null): string {
        if ($count <= 0) {
            return '';
        }
        $icon = htmlspecialchars($faIcon, ENT_QUOTES, 'UTF-8');
        $labelEsc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $badgeClass = $done
            ? 'bg-success text-white border-success'
            : 'bg-white border text-muted';
        $iconClass = $done ? 'text-white' : 'text-secondary';

        $classes = 'badge esh-cal-task w-100 fw-normal shadow-sm text-start text-decoration-none ' . $badgeClass;
        $inner = '<i class="fa ' . $icon . ' me-1 ' . $iconClass . '" aria-hidden="true"></i>'
            . (int) $count . ' ' . $labelEsc;

        if ($href !== null && $href !== '') {
            $hrefEsc = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');

            return '<a href="' . $hrefEsc . '" class="' . $classes . '" onclick="event.stopPropagation();">'
                . $inner
                . '</a>';
        }

        return '<div class="' . $classes . '">'
            . $inner
            . '</div>';
    }

    public function getDailyEvents() {
        $date = $_GET['date'] ?? date('Y-m-d');
        $model = new PlannedVisit();
        $payload = $model->getDailyPlans($date);
        $payload['mernisPatientCount'] = count($model->getDailyPlanUniquePatients($date));
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    /**
     * Günün planındaki hastalar için MERNİS vefat taraması (paketli AJAX).
     * GET: date (Y-m-d), offset, limit
     */
    public function dailyPlanMernisScan(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        AuthHelper::requireAdminJson();

        $date = trim((string) ($_GET['date'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $offset = max(0, (int) ($_GET['offset'] ?? 0));
        $limit = max(1, min(8, (int) ($_GET['limit'] ?? 5)));

        $planned = new PlannedVisit();
        $all = $planned->getDailyPlanUniquePatients($date);
        $total = count($all);
        $slice = array_slice($all, $offset, $limit);

        $patientModel = new Patient();
        $deceased = [];
        foreach ($slice as $p) {
            $result = $patientModel->mernisVefatKontrolVeKaydet($p['tckimlik']);
            if (!empty($result['oldu'])) {
                $deceased[] = [
                    'tckimlik' => $p['tckimlik'],
                    'isim' => $p['isim'],
                    'soyisim' => $p['soyisim'],
                    'olumTarihi' => $result['olumTarihi'] ?? null,
                    'mesaj' => $result['mesaj'] ?? '',
                    'view_url' => $p['hastaid'] > 0
                        ? esh_url('Patient', 'view', ['id' => $p['hastaid']])
                        : '',
                ];
            }
        }

        $nextOffset = $offset + count($slice);
        echo json_encode([
            'ok' => true,
            'date' => $date,
            'total' => $total,
            'processed' => count($slice),
            'nextOffset' => $nextOffset,
            'done' => $nextOffset >= $total,
            'deceased' => $deceased,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function tcLookupAjax() {
        header('Content-Type: application/json; charset=utf-8');
        $qRaw = trim((string) ($_GET['q'] ?? ''));
        $qDigits = preg_replace('/\D+/', '', $qRaw);
        if ($qRaw === '') {
            echo json_encode(['query' => '', 'suggestions' => [], 'exact' => null], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $patientModel = new Patient();
        $suggestions = [];
        if (strlen($qRaw) >= 2) {
            $rows = $patientModel->searchForDashboardLookup($qRaw, 10);
            foreach ($rows as $p) {
                $key = BadgeHelper::patientPasifKey($p);
                $suggestions[] = [
                    'id' => (int) ($p->id ?? 0),
                    'tckimlik' => (string) ($p->tckimlik ?? ''),
                    'isim' => (string) ($p->isim ?? ''),
                    'soyisim' => (string) ($p->soyisim ?? ''),
                    'adres' => Patient::formatIlceMahalle($p),
                    'status_key' => $key,
                    'status_text' => $this->statusTextFromKey($key),
                    'view_url' => esh_url('Patient', 'view', array (
  'id' => '',
)) . (int) ($p->id ?? 0),
                ];
            }
        }

        $exact = null;
        if (ValidationHelper::isTcLength11($qDigits)) {
            $one = $patientModel->findByTcWithAddress($qDigits);
            if ($one) {
                $key = BadgeHelper::patientPasifKey($one);
                $exact = [
                    'id' => (int) ($one->id ?? 0),
                    'tckimlik' => (string) ($one->tckimlik ?? ''),
                    'isim' => (string) ($one->isim ?? ''),
                    'soyisim' => (string) ($one->soyisim ?? ''),
                    'adres' => Patient::formatIlceMahalle($one),
                    'status_key' => $key,
                    'status_text' => $this->statusTextFromKey($key),
                    'view_url' => esh_url('Patient', 'view', array (
  'id' => '',
)) . (int) ($one->id ?? 0),
                ];
            }
        }

        echo json_encode(['query' => $qRaw, 'suggestions' => $suggestions, 'exact' => $exact], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function showRoute() {
        AuthHelper::requireAdmin();

        $targetDate = isset($_GET['date']) ? (string) $_GET['date'] : date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate)) {
            $targetDate = date('Y-m-d');
        }
        $ts = strtotime($targetDate);
        if ($ts === false) {
            $targetDate = date('Y-m-d');
            $ts = strtotime($targetDate);
        }

        $data = [
            'tum_vardiya_verisi' => [],
            'merkez' => [],
            'tarih_basligi' => $this->tarihFormatla((int) $ts),
        ];
        $showRouteRowsFetchUrl = esh_url('Dashboard', 'showRouteRows', ['date' => $targetDate]);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'rota/rota_view');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Günlük rota sekmeleri ve ekip kartları (JSON HTML parçası + harita verisi).
     */
    public function showRouteRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        AuthHelper::requireAdminJson();

        $targetDate = isset($_GET['date']) ? (string) $_GET['date'] : date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate)) {
            $targetDate = date('Y-m-d');
        }

        $data = $this->planla($targetDate);

        ob_start();
        include ROOT_PATH . '/views/site/rota/partials/route_dynamic_content.php';
        $html = ob_get_clean();

        echo json_encode([
            'ok' => true,
            'html' => $html,
            'allData' => $data['tum_vardiya_verisi'] ?? [],
            'merkez' => $data['merkez'] ?? [],
            'tarih_basligi' => $data['tarih_basligi'] ?? '',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Oturum açmış kullanıcı — TomTom geocode proxy (anahtar istemciye verilmez). */
    public function tomtomGeocodeAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ((int) ($_SESSION['user_id'] ?? 0) <= 0) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rateKey = 'u' . (int) $_SESSION['user_id'];
        if (\App\Helpers\RateLimitHelper::tooManyAttempts(
            'tomtom_geocode',
            $rateKey,
            ESH_TOMTOM_GEOCODE_RATE_MAX_ATTEMPTS,
            ESH_TOMTOM_GEOCODE_RATE_WINDOW_SECONDS
        )) {
            http_response_code(429);
            echo json_encode(['ok' => false, 'error' => 'rate_limited'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $kapinoId = trim((string) ($_GET['kapino_id'] ?? ''));
        if ($kapinoId !== '') {
            $geo = (new Address())->geocodeKapinoById($kapinoId, false);
            if (!empty($geo['ok']) && !empty($geo['coords'])) {
                \App\Helpers\RateLimitHelper::hit(
                    'tomtom_geocode',
                    $rateKey,
                    ESH_TOMTOM_GEOCODE_RATE_WINDOW_SECONDS
                );
                echo json_encode([
                    'ok' => true,
                    'coords' => (string) $geo['coords'],
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            echo json_encode([
                'ok' => false,
                'error' => 'not_found',
                'mesaj' => (string) ($geo['mesaj'] ?? 'Koordinat bulunamadı.'),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $q = trim((string) ($_GET['q'] ?? ''));
        $result = TomTomClient::geocode($q);
        if (!empty($result['ok'])) {
            \App\Helpers\RateLimitHelper::hit(
                'tomtom_geocode',
                $rateKey,
                ESH_TOMTOM_GEOCODE_RATE_WINDOW_SECONDS
            );
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Yönetici — rota haritası için SDK anahtarı (yalnızca admin rota ekranı). */
    public function tomtomMapKeyAjax(): void
    {
        AuthHelper::requireAdminJson();
        header('Content-Type: application/json; charset=utf-8');
        $key = TomTomClient::apiKey();
        if ($key === '') {
            echo json_encode(['ok' => false, 'error' => 'not_configured'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(['ok' => true, 'key' => $key], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Yönetici — çok duraklı rota hesabı proxy. POST JSON: { locations: [[lon,lat],...] } */
    public function tomtomRouteAjax(): void
    {
        AuthHelper::requireAdminJson();
        header('Content-Type: application/json; charset=utf-8');

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $raw = file_get_contents('php://input');
        $payload = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($payload) || !isset($payload['locations']) || !is_array($payload['locations'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'invalid_payload'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $result = TomTomClient::calculateRoute($payload['locations']);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function planla($date = null) {
        $tarih = $date ? $date : date('Y-m-d');
        $ts = strtotime($tarih);
        $today_day = date('w', $ts); 

        // 1. Verileri Model Üzerinden Çek
        $ham_veri = $this->hamVeriyiDerle($tarih, $today_day);
        
       
        $ekipler = $this->ekipleriHazirla($tarih);

        // 2. Zaman Dilimlerine Göre Grupla
        $zamanli_veri = [];
        $activeVardiyaIndexes = ZamanDilimiHelper::activeVardiyaIndexes();
        foreach ($ham_veri as $h) {
            $vardiyaId = ZamanDilimiHelper::toVardiyaIndex((int) ($h->zaman_kodu ?? ZamanDilimiHelper::SABAH));
            if (!in_array($vardiyaId, $activeVardiyaIndexes, true)) {
                continue;
            }
            $zamanli_veri[$vardiyaId][] = $h;
        }

        // 3. Rota Algoritmasını Çalıştır
        $sonuc = [];
        foreach ($activeVardiyaIndexes as $vardiyaId) {
            if(isset($zamanli_veri[$vardiyaId]) && isset($ekipler[$vardiyaId])) {
                $sonuc[$vardiyaId] = $this->rotaHesapla($tarih, $zamanli_veri[$vardiyaId], $ekipler[$vardiyaId]);
            }
        }
        
        // planla() fonksiyonunun sonu
        /**
        * Akıllı uyarılar kısmı için eklendi
        */
        //$uyarilar = $this->rotaAnaliziYap($sonuc);

        return [
            'tum_vardiya_verisi' => $sonuc,
            'merkez' => $this->ayarlar['merkez'],
            'tarih_basligi' => $this->tarihFormatla($ts)
            //'ai_analiz' => $uyarilar // Akıllı öneriler
        ];
    }
    
    private function rotaHesapla($tarih, $hastalar, $ekipler) {
    $kalanlar = $hastalar;
    $ekip_antalari = $ekipler;

    while(count($kalanlar) > 0) {
        foreach($ekip_antalari as $eID => &$eData) {
            if(empty($kalanlar)) break;

            // TomTom Matrix API'den mesafeleri al
            $matrixResults = $this->getTomTomMatrixData($eData['lat'], $eData['lon'], $kalanlar, $this->ayarlar['tomtom_APIKEY']);
            
            $best_idx = -1;
            $min_maliyet = 999999;
            $secilen_data = [];

            foreach($kalanlar as $k => $h) {
                $s_sure = isset($matrixResults[$k]['travelTimeInSeconds']) ? ($matrixResults[$k]['travelTimeInSeconds'] / 60) : 999;
                $s_dist = isset($matrixResults[$k]['lengthInMeters']) ? ($matrixResults[$k]['lengthInMeters'] / 1000) : 999;

                // --- PERSONEL ODAKLI GELİŞMİŞ MALİYET FORMÜLÜ ---
                $n = count($eData['hastalar']);
                $personel_sayisi = isset($eData['personel']) ? (int)$eData['personel'] : 1;
                $max_dosya = $personel_sayisi * $this->ayarlar['personel_dosya_sayisi']; // Örn: Personel başına max 10 dosya
                if (count($eData['hastalar']) >= $max_dosya) continue;  

                // 1. Temel: Sürüş Süresi
                $maliyet = $s_sure;

                // 2. Ceza: Personel Başına İş Yükü (Kritik Değişiklik)
                // Sadece hasta sayısına değil, (Hasta Sayısı / Personel Sayısı) oranına bakıyoruz.
                // Bu sayede 2 kişilik ekipte 2 hasta olması, 1 kişilik ekipte 1 hasta olmasıyla aynı maliyeti üretir.
                $is_yuku_orani = $n / $personel_sayisi;
                $maliyet += ($is_yuku_orani * $this->ayarlar['is_yuku_cezasi'] * 2); 

                // 3. Bonus: Mahalle ve Bölge Sadakati
                if ($h->mahalle_id == $eData['son_mahalle']) {
                    $maliyet -= $this->ayarlar['mahalle_bonusu'];
                } elseif ($h->bolge_id == $eData['son_bolge']) {
                    $maliyet -= $this->ayarlar['bolge_bonusu'];
                }

                // 4. Bonus: Öncelik
                if ($h->oncelik == 2) {
                    $maliyet -= $this->ayarlar['oncelik_yuksek_bonusu'];
                }

                if($maliyet < $min_maliyet) {
                    $min_maliyet = $maliyet;
                    $best_idx = $k;
                    $secilen_data = ['sure' => $s_sure, 'dist' => $s_dist];
                }
            }

            // Seçilen hastayı ekibe ata
            if($best_idx !== -1) {
                $sec = $kalanlar[$best_idx];
                
                $eData['saat'] += ($secilen_data['sure'] * 60);
                $sec->varis_saati = date('H:i', $eData['saat']);
                $sec->mesafe_artisi = (float)$secilen_data['dist'];
                
                $eData['toplam_km'] += (float)$secilen_data['dist'];
                $eData['saat'] += ($sec->sure * 60);

                $coords = explode(',', $sec->coords);
                $eData['lat'] = trim($coords[0]);
                $eData['lon'] = trim($coords[1]);
                $eData['son_mahalle'] = $sec->mahalle_id;
                $eData['son_bolge'] = $sec->bolge_id;

                $eData['hastalar'][] = $sec;
                array_splice($kalanlar, $best_idx, 1);
            }
        }
    }
    return $ekip_antalari;
}
    /**
    * Yardımcı fonksiyonlar
    */
    private function hamVeriyiDerle($tarih, $today_day) {
    $ham_veri = [];
    
    // A) Pansumanlar
    $pansumanlar = (new Patient())->getPansumanlar($today_day, $tarih);
    foreach($pansumanlar as $r) {
        $r->etiket = '<span class="label label-primary">Pansuman</span>';
        $r->oncelik = 0;
        $r->sure = $this->ayarlar['sureler']['pansuman'];
        
        // Yeni Alanlar: SQL'den gelen veriyi koruyoruz, yoksa varsayılan atıyoruz
        $r->son_izlem = $r->son_izlem_tarihi ? date('d-m-Y', strtotime($r->son_izlem_tarihi)) : 'Kayıt Yok';
        $r->son_yapilan = $r->son_izlem_yapilanlar ?? 'İşlem Bilgisi Yok';
        
        $ham_veri[] = $r;
    }

    // B) İlk Muayeneler (Henüz izlem olmadığı için buraları sabit geçebilirsin)
    $muayeneler = (new Patient())->getIlkMuayeneler($tarih);
    foreach($muayeneler as $r) {
        $r->etiket = '<span class="label label-warning">İlk Ziyaret</span>';
        $r->oncelik = 1;
        $r->sure = $this->ayarlar['sureler']['muayene'];
        
        // İlk ziyarette geçmiş veri olmayacağı için boş/varsayılan değerler
        $r->son_izlem = 'Yeni Hasta';
        $r->son_yapilan = 'İlk Kez Gidilecek';
        
        $ham_veri[] = $r;
    }

        $nakilId = (string) IslemIdSettings::resolvedInt('nakil_islem_id');
        // C) İzlemler
    $izlemler = (new PlannedVisit())->getIzlemler($tarih);
    foreach($izlemler as $r) {
        // İptal kriteri
        $y_parts = explode(',', $r->yapilacak);
            if (in_array($nakilId, array_map('trim', $y_parts), true)) continue;

        $txt = $r->islem_detaylari ? $r->islem_detaylari : 'İzlem';
        $r->etiket = '<span class="label label-success">' . htmlspecialchars((string) $txt, ENT_QUOTES, 'UTF-8') . '</span>';
        $r->oncelik = (int)$r->oncelik;
        $r->sure = $this->ayarlar['sureler']['izlem'];
        
        // Yeni Alanlar: SQL sorgusuna eklediğimiz subquery'lerden gelen veriler
        $r->son_izlem = $r->son_izlem_tarihi ? date('d-m-Y', strtotime($r->son_izlem_tarihi)) : 'Kayıt Yok';
        $r->son_yapilan = $r->son_izlem_yapilanlar ?? 'İşlem Bilgisi Yok';

        $ham_veri[] = $r;
    }

    return $ham_veri;
}

    private function ekipleriHazirla($tarih) {
    $planlar = (new Ekip())->getEkipler($tarih);
    $ekipler = [];
    $activeVardiyaIndexes = ZamanDilimiHelper::activeVardiyaIndexes();

    if ($planlar) {
        // Veritabanında o güne özel bir ekip planı varsa
        foreach ($planlar as $p) {
            $vIdx = (int) $p->vardiya;
            if (!in_array($vIdx, $activeVardiyaIndexes, true)) {
                continue;
            }
            $p_names = (new User())->getUserNames($p->user_ids);
            $ekipler[$vIdx][$p->ekip_no] = [
                'isim'      => implode(', ', $p_names),
                'personel'  => count($p_names), // Veritabanındaki kullanıcı sayısını al
                'lat'       => $this->ayarlar['merkez']['lat'],
                'lon'       => $this->ayarlar['merkez']['lon'],
                'hastalar'  => [],
                'toplam_km' => 0,
                'son_mahalle' => -1,
                'son_bolge'   => -1,
                'saat'      => strtotime($tarih . ' ' . ($p->baslangic_saati ?? ZamanDilimiHelper::ekipBaslangicSaati($vIdx)))
            ];
        }
    } else {
        // --- Varsayılan Nöbetçi Ekipler Düzeni ---
        $varsayilan_yapi = [
            0 => [ // Sabah
                ['no' => 1, 'p' => 2], ['no' => 2, 'p' => 2]
            ],
            1 => [ // Öğle
                ['no' => 1, 'p' => 2], ['no' => 2, 'p' => 2]
            ],
            2 => [ // Akşam
                ['no' => 1, 'p' => 1], ['no' => 2, 'p' => 2]
            ]
        ];

        foreach ($activeVardiyaIndexes as $vId) {
            $ekipListesi = $varsayilan_yapi[$vId] ?? [['no' => 1, 'p' => 2]];
            foreach ($ekipListesi as $eData) {
                $ekipler[$vId][$eData['no']] = [
                    'isim'      => 'Nöbetçi Ekip ' . $eData['no'],
                    'personel'  => $eData['p'],
                    'lat'       => $this->ayarlar['merkez']['lat'],
                    'lon'       => $this->ayarlar['merkez']['lon'],
                    'hastalar'  => [],
                    'toplam_km' => 0,
                    'son_mahalle' => -1,
                    'son_bolge'   => -1,
                    'saat'      => strtotime($tarih . ' ' . ZamanDilimiHelper::ekipBaslangicSaati($vId))
                ];
            }
        }
    }
    return $ekipler;
}

    private function tarihFormatla($time) {
        $months = ['01'=>'Ocak', '02'=>'Şubat','03'=>'Mart','04'=>'Nisan','05'=>'Mayıs','06'=>'Haziran','07'=>'Temmuz','08'=>'Ağustos','09'=>'Eylül','10'=>'Ekim','11'=>'Kasım','12'=>'Aralık'];
        $days = ['0'=>'Pazar', '1'=>'Pazartesi', '2'=>'Salı', '3'=>'Çarşamba', '4'=>'Perşembe', '5'=>'Cuma', '6'=>'Cumartesi'];
        
        return date('j', $time).' '.$months[date('m', $time)].' '.date('Y', $time).', '.$days[date('w', $time)];
    }

    private function statusTextFromKey(string $key): string
    {
        $map = [
            'active' => 'Aktif',
            'passive' => 'Pasif',
            'waiting' => 'Bekleyen',
            'died' => 'Vefat',
            'deleted' => 'Silinen',
            'araf' => 'Araf',
            'probable' => 'Muhtemel ölen',
            'unknown' => 'Belirsiz',
        ];
        return $map[$key] ?? 'Belirsiz';
    }
    
    public function admin() {
        \App\Helpers\AuthHelper::requireAdmin();
        $pageTitle = "Yönetim Paneli";
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'dashboard/index');
        include ThemeViewHelper::resolvePartial('footer');
    }
    
    /**
     * TomTom Matrix API: Ekibin konumundan tüm hastalara olan mesafeleri tek seferde çeker.
     */
    public function getTomTomMatrixData($startLat, $startLon, $hastalar, $apiKey) {
    set_time_limit(0); // Büyük listelerde zaman aşımını önler
    $results = array();
    $originStr = $startLat . "," . $startLon;

    foreach ($hastalar as $k => $h) {
        $destStr = str_replace(' ', '', trim($h->coords));
        $hash = md5($originStr . $destStr);
        
        // 1. Model üzerinden Cache tablosuna bakıyoruz
        $model = new Cache();
        $cache = $model->getCache($hash);

        if ($cache) {
            $results[$k] = [
                'travelTimeInSeconds' => (int)$cache->sure, 
                'lengthInMeters' => (int)$cache->mesafe
            ];
        } else {
            // 2. Cache'te yoksa API'ye git
            $url = "https://api.tomtom.com/routing/1/calculateRoute/$originStr:$destStr/json?key=$apiKey&travelMode=car&traffic=true";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Bekleme süresini 5sn yaptık
            curl_setopt($ch, CURLOPT_USERAGENT, 'GeminiKodlamaDestegi/1.0'); 
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode == 200 && $response) {
                $data = json_decode($response);
                if (isset($data->routes[0]->summary)) {
                    $summary = $data->routes[0]->summary;
                    
                    // 3. Model üzerinden Cache'e Kaydet
                    $model->saveCache(
                        $hash, 
                        $originStr, 
                        $destStr, 
                        $summary->travelTimeInSeconds, 
                        $summary->lengthInMeters
                    );
                        
                    $results[$k] = [
                        'travelTimeInSeconds' => (int)$summary->travelTimeInSeconds,
                        'lengthInMeters' => (int)$summary->lengthInMeters
                    ];
                }
            }
            
            // Eğer veri hala gelmediyse (API hatası veya format hatası)
            if (!isset($results[$k])) {
                $results[$k] = ['travelTimeInSeconds' => 900, 'lengthInMeters' => 5000]; // Varsayılan ceza değeri
            }
            
            // API'yi yormamak ve Rate Limit'e takılmamak için çok kısa bekleme
            usleep(50000); // 0.05 saniye
        }
    }
    return $results;
}

}
