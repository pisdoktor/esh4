<?php
namespace App\Controllers;

use App\Helpers\AgeBandHelper;
use App\Helpers\AuthHelper;
use App\Helpers\DateHelper;
use App\Helpers\PatientClinicalFlagsHelper;
use App\Helpers\StatsCrossTabBuilder;
use App\Helpers\StatsCrossTabRegistry;
use App\Helpers\StatsIntroHelper;
use App\Helpers\StatsNavHelper;
use App\Helpers\StatsReportPdfService;
use App\Helpers\TenantSqlHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\Address;
use App\Models\Hastalik;
use App\Models\Stats;

/**
 * Admin istatistik merkezi (eski includes/stats.php işlevlerinin MVC karşılığı).
 */
class StatsController {

    public function __construct() {
        if (AuthHelper::sessionIsAdmin()) {
            return;
        }
        $action = (string) ($GLOBALS['actionName'] ?? '');
        if ($action === 'reportPdfData') {
            AuthHelper::requirePermission('stats.export');
            return;
        }
        AuthHelper::requirePermission('stats.read');
    }

    private function render(string $view, string $pageTitle, array $vars = []): void {
        $action = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
        if (!isset($vars['statsIntro']) && $action !== '') {
            $vars['statsIntro'] = StatsIntroHelper::forAction($action);
        }
        if (!isset($vars['statsBreadcrumb'])) {
            $vars['statsBreadcrumb'] = StatsNavHelper::breadcrumbTrail($action, $pageTitle);
        }
        $vars['eshStatsHubCss'] = true;
        if (!empty($vars['eshStatsHubCss'])) {
            \App\Helpers\PageAssetHelper::registerPageStylesheet('stats-hub.css');
        }
        extract($vars, EXTR_SKIP);
        include ThemeViewHelper::resolvePartial('header');
        $canonicalStatsView = rtrim((string) ROOT_PATH, '/\\') . '/views/admin/stats/' . $view . '.php';
        $statsViewFile = is_file($canonicalStatsView)
            ? $canonicalStatsView
            : ThemeViewHelper::resolveAreaView('admin', 'stats/' . $view);
        include $statsViewFile;
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * GET date_from / date_to → Y-m-d aralığı ve TR görüntü değerleri.
     *
     * @return array{0: string, 1: string, 2: string, 3: string, 4: bool}
     */
    private function statsFilterDateRange(string $defaultFromExpr = 'first day of this month'): array {
        return DateHelper::resolveFilterDateRange($_GET, $defaultFromExpr);
    }

    private function statsPagination(int $total, int $limit = 50): array {
        $limit = max(10, min(200, $limit));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pages = max(1, (int) ceil($total / $limit));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $limit;
        return [$limit, $offset, $page, $pages, $total];
    }

    /**
     * @return array{orderby: string, orderdir: string, orderFragment: string}
     */
    private function statsPatientDrilldownSortState(string $defaultKey = 'h.isim', string $defaultDir = 'ASC'): array {
        $sort = \App\Helpers\QueryHelper::statsPatientDrilldownSort($defaultKey, $defaultDir);
        $sort['orderFragment'] .= ', h.isim ASC, h.soyisim ASC';

        return $sort;
    }

    /**
     * @param array<string, mixed> $q
     * @param array{orderby?: string, orderdir?: string} $state
     */
    private function mergeSortIntoFetchQuery(array &$q, array $state): void {
        if (!empty($state['orderby'])) {
            $q['orderby'] = $state['orderby'];
            $q['orderdir'] = $state['orderdir'];
        }
    }

    /** İstatistik merkezi — tüm alt raporlara bağlantılar */
    public function index() {
        $pageTitle = 'İstatistik Merkezi';
        $this->render('hub', $pageTitle, []);
    }

    /**
     * Hub kartı veya rapor action — pdfMake verisi (JSON).
     * GET: report={action}[, months=… date_from=…]
     */
    public function reportPdfData(): void {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        AuthHelper::requireAdminJson();

        $report = isset($_GET['report']) ? trim((string) $_GET['report']) : '';

        try {
            $payload = StatsReportPdfService::build($report, $_GET);
            echo json_encode(array_merge(['ok' => true], $payload), JSON_UNESCAPED_UNICODE);
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'PDF verisi oluşturulamadı.'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /** Özet kartlar + mahalle + yıllık kayıt tabloları */
    public function overview() {
        $model = new Stats();
        $summary = $model->getGeneralSummary();
        if (!$summary) {
            $summary = (object) ['toplam' => 0, 'aktif' => 0, 'pasif' => 0, 'erkek' => 0, 'kadin' => 0];
        }
        $data = [
            'summary' => $summary,
            'mahalleler' => $model->getMahalleStats() ?: [],
            'yillar' => $model->getKayitYiliStats() ?: [],
        ];
        $pageTitle = 'Genel özet ve dağılımlar';
        $this->render('dashboard', $pageTitle, ['data' => $data]);
    }

    /** Hastalık kategorisi özeti ve en sık tanılar (grafik) */
    public function charts() {
        $model = new Stats();
        $pack = $model->getHastalikDiagnosisDistribution();
        $data = [
            'hastaliklar' => $model->getHastalikStats() ?: [],
            'hastalik_kategorileri' => $model->getHastalikCategorySummary(),
            'total_aktif' => (int) ($pack['total_aktif'] ?? 0),
        ];
        $pageTitle = 'Hastalık istatistiği';
        $this->render('charts', $pageTitle, ['data' => $data]);
    }

    /** Eski task=hastalik: kategorili tanı tablosu ve hasta sayıları */
    public function hastalik() {
        $m = new Stats();
        $pack = $m->getHastalikDiagnosisDistribution();
        $pageTitle = 'Hastalıklarına göre hasta sayısı';
        $this->render('stats_hastalik', $pageTitle, [
            'total_aktif' => (int) ($pack['total_aktif'] ?? 0),
            'categories' => $pack['categories'] ?? [],
            'counts' => $pack['counts'] ?? [],
        ]);
    }

    /** Eski task=hastagetir: tanıya göre aktif hasta listesi */
    public function hastalikPatients() {
        $state = $this->hastalikPatientsState();
        if ($state === null) {
            header('Location: ' . esh_url('Stats', 'hastalik'));
            exit;
        }

        $hModel = new Hastalik();
        $hAd = 'Bilinmeyen tanı';
        if ($hModel->load($state['hastalik_id'])) {
            $hAd = (string) ($hModel->hastalikadi ?? $hAd);
        }
        $hRow = (object) ['id' => $state['hastalik_id'], 'hastalikadi' => $hAd];

        $pageTitle = 'Tanıya göre hastalar';
        $this->render('stats_hastalik_patients', $pageTitle, array_merge($state, [
            'hastalik' => $hRow,
            'rows' => [],
            'hastalikPatientsRowsFetchUrl' => $this->buildHastalikPatientsRowsFetchUrl($state),
        ]));
    }

    /**
     * Tanıya göre hasta listesi tablo satırları (JSON HTML parçası).
     */
    public function hastalikPatientsRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $state = $this->hastalikPatientsState();
        if ($state === null) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Geçersiz tanı.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rows = (new Stats())->getPatientsWithHastalikId(
            $state['hastalik_id'],
            $state['orderFragment'],
            $state['limit'],
            $state['offset']
        );
        $ordering = trim($state['orderby'] . ' ' . $state['orderdir']);
        $ayAdlar = [1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'];

        ob_start();
        include ROOT_PATH . '/views/admin/stats/partials/stats_hastalik_patients/table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function hastalikPatientsState(): ?array {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id < 1) {
            return null;
        }
        $orderby = isset($_GET['orderby']) ? trim((string) $_GET['orderby']) : 'h.isim';
        $orderdir = (isset($_GET['orderdir']) && strtoupper((string) $_GET['orderdir']) === 'DESC') ? 'DESC' : 'ASC';
        $orderFragment = \App\Helpers\QueryHelper::patientListOrderBy($orderby, $orderdir);

        $m = new Stats();
        $total = $m->countPatientsWithHastalikId($id);
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
        $limit = max(10, min(200, $limit));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pages = max(1, (int) ceil($total / $limit));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $limit;

        return [
            'hastalik_id' => $id,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'limit' => $limit,
            'orderby' => $orderby,
            'orderdir' => $orderdir,
            'orderFragment' => $orderFragment,
            'offset' => $offset,
        ];
    }

    /**
     * @param array<string, mixed> $state
     */
    private function buildHastalikPatientsRowsFetchUrl(array $state): string {
        $q = [
            'controller' => 'Stats',
            'action' => 'hastalikPatientsRows',
            'id' => (string) $state['hastalik_id'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
            'limit' => (string) $state['limit'],
        ];
        if ($state['page'] > 1) {
            $q['page'] = (string) $state['page'];
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    /**
     * @return array{
     *   ilce: string,
     *   mahalle: string,
     *   ilceFilter: ?string,
     *   mahalleFilter: ?string,
     *   orderby: string,
     *   orderdir: string,
     *   orderByClause: string,
     *   limit: int,
     *   offset: int,
     *   page: int,
     *   pages: int,
     *   total: int
     * }
     */
    private function birIzlemlilerState(): array {
        $ilce = isset($_GET['ilce']) ? trim((string) $_GET['ilce']) : '';
        $mahalle = isset($_GET['mahalle']) ? trim((string) $_GET['mahalle']) : '';
        $ilceFilter = ($ilce !== '' && $ilce !== '0') ? $ilce : null;
        $mahalleFilter = ($mahalle !== '' && $mahalle !== '0') ? $mahalle : null;

        $orderby = isset($_GET['orderby']) ? trim((string) $_GET['orderby']) : 'h.isim';
        $orderdir = (isset($_GET['orderdir']) && strtoupper((string) $_GET['orderdir']) === 'DESC') ? 'DESC' : 'ASC';
        $orderByClause = 'ORDER BY ' . $this->birIzlemOrderBySql($orderby, $orderdir);

        $allTcs = (new Stats())->getBirIzlemMatchingTckimliks($ilceFilter, $mahalleFilter);
        $total = count($allTcs);

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
        $limit = max(10, min(200, $limit));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pages = max(1, (int) ceil($total / $limit));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $limit;

        return [
            'ilce' => $ilce,
            'mahalle' => $mahalle,
            'ilceFilter' => $ilceFilter,
            'mahalleFilter' => $mahalleFilter,
            'orderby' => $orderby,
            'orderdir' => $orderdir,
            'orderByClause' => $orderByClause,
            'allTcs' => $allTcs,
            'limit' => $limit,
            'offset' => $offset,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ];
    }

    /**
     * @param array{
     *   ilce: string,
     *   mahalle: string,
     *   orderby: string,
     *   orderdir: string,
     *   page: int,
     *   limit: int
     * } $state
     */
    private function buildBirIzlemlilerRowsFetchUrl(array $state): string {
        $q = [
            'controller' => 'Stats',
            'action' => 'birIzlemlilerRows',
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
        ];
        if ($state['ilce'] !== '' && $state['ilce'] !== '0') {
            $q['ilce'] = $state['ilce'];
        }
        if ($state['mahalle'] !== '' && $state['mahalle'] !== '0') {
            $q['mahalle'] = $state['mahalle'];
        }
        if ($state['page'] > 1) {
            $q['page'] = $state['page'];
        }
        if ($state['limit'] !== 50) {
            $q['limit'] = $state['limit'];
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    /** Eski task=bir (birIzlemliler): tek tamamlanmış izlem + yapilan içinde işlem 1 */
    public function birIzlemliler() {
        $state = $this->birIzlemlilerState();

        $addr = new Address();
        $ilceler = $addr->getDistricts() ?: [];
        $mahalleler = [];
        if ($state['ilceFilter'] !== null) {
            $mahalleler = $addr->getSubs($state['ilce'], 'mahalle') ?: [];
        }

        $pageTitle = 'Bir izlemliler';
        $this->render('stats_bir_izlemliler', $pageTitle, [
            'ilce' => $state['ilce'],
            'mahalle' => $state['mahalle'],
            'ilceler' => $ilceler,
            'mahalleler' => $mahalleler,
            'page' => $state['page'],
            'pages' => $state['pages'],
            'total' => $state['total'],
            'limit' => $state['limit'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
            'birIzlemlilerRowsFetchUrl' => $this->buildBirIzlemlilerRowsFetchUrl($state),
        ]);
    }

    /**
     * Bir izlemliler listesi tablo satırları (JSON HTML parçası).
     */
    public function birIzlemlilerRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $state = $this->birIzlemlilerState();
        $slice = array_slice($state['allTcs'], $state['offset'], $state['limit']);
        $rows = (new Stats())->getBirIzlemPatientRows($slice, $state['orderByClause']);

        ob_start();
        include ROOT_PATH . '/views/admin/stats/partials/bir_izlemliler_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return array{
     *   year: int,
     *   month: int,
     *   period_label: string,
     *   turkce_aylar: array<int, string>,
     *   ilce: string,
     *   mahalle: string,
     *   ilceFilter: string|null,
     *   mahalleFilter: string|null,
     *   orderby: string,
     *   orderdir: string,
     *   orderByClause: string,
     *   allTcs: list<string>,
     *   limit: int,
     *   offset: int,
     *   page: int,
     *   pages: int,
     *   total: int
     * }
     */
    private function aylikTekIzlemlilerState(): array {
        $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
        $month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        if ($year < 2000 || $year > ((int) date('Y') + 1)) {
            $year = (int) date('Y');
        }

        $turkceAylar = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
            7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
        ];

        $ilce = isset($_GET['ilce']) ? trim((string) $_GET['ilce']) : '';
        $mahalle = isset($_GET['mahalle']) ? trim((string) $_GET['mahalle']) : '';
        $ilceFilter = ($ilce !== '' && $ilce !== '0') ? $ilce : null;
        $mahalleFilter = ($mahalle !== '' && $mahalle !== '0') ? $mahalle : null;

        $orderby = isset($_GET['orderby']) ? trim((string) $_GET['orderby']) : 'h.isim';
        $orderdir = (isset($_GET['orderdir']) && strtoupper((string) $_GET['orderdir']) === 'DESC') ? 'DESC' : 'ASC';
        $orderByClause = 'ORDER BY ' . $this->birIzlemOrderBySql($orderby, $orderdir);

        $allTcs = (new Stats())->getAylikTekIzlemMatchingTckimliks($year, $month, $ilceFilter, $mahalleFilter);
        $total = count($allTcs);

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
        $limit = max(10, min(200, $limit));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pages = max(1, (int) ceil($total / $limit));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $limit;

        return [
            'year' => $year,
            'month' => $month,
            'period_label' => ($turkceAylar[$month] ?? '') . ' ' . $year,
            'turkce_aylar' => $turkceAylar,
            'ilce' => $ilce,
            'mahalle' => $mahalle,
            'ilceFilter' => $ilceFilter,
            'mahalleFilter' => $mahalleFilter,
            'orderby' => $orderby,
            'orderdir' => $orderdir,
            'orderByClause' => $orderByClause,
            'allTcs' => $allTcs,
            'limit' => $limit,
            'offset' => $offset,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ];
    }

    /**
     * @param array{
     *   year: int,
     *   month: int,
     *   ilce: string,
     *   mahalle: string,
     *   orderby: string,
     *   orderdir: string,
     *   page: int,
     *   limit: int
     * } $state
     */
    private function buildAylikTekIzlemlilerRowsFetchUrl(array $state): string {
        $q = [
            'controller' => 'Stats',
            'action' => 'aylikTekIzlemlilerRows',
            'year' => $state['year'],
            'month' => $state['month'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
        ];
        if ($state['ilce'] !== '' && $state['ilce'] !== '0') {
            $q['ilce'] = $state['ilce'];
        }
        if ($state['mahalle'] !== '' && $state['mahalle'] !== '0') {
            $q['mahalle'] = $state['mahalle'];
        }
        if ($state['page'] > 1) {
            $q['page'] = $state['page'];
        }
        if ($state['limit'] !== 50) {
            $q['limit'] = $state['limit'];
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    /** Seçilen ay/yılda tam bir tamamlanmış izlemi olan aktif hastalar */
    public function aylikTekIzlemliler() {
        $state = $this->aylikTekIzlemlilerState();

        $addr = new Address();
        $ilceler = $addr->getDistricts() ?: [];
        $mahalleler = [];
        if ($state['ilceFilter'] !== null) {
            $mahalleler = $addr->getSubs($state['ilce'], 'mahalle') ?: [];
        }

        $pageTitle = 'Aylık tek izlemliler';
        $this->render('stats_aylik_tek_izlemliler', $pageTitle, [
            'year' => $state['year'],
            'month' => $state['month'],
            'period_label' => $state['period_label'],
            'turkce_aylar' => $state['turkce_aylar'],
            'ilce' => $state['ilce'],
            'mahalle' => $state['mahalle'],
            'ilceler' => $ilceler,
            'mahalleler' => $mahalleler,
            'page' => $state['page'],
            'pages' => $state['pages'],
            'total' => $state['total'],
            'limit' => $state['limit'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
            'aylikTekIzlemlilerRowsFetchUrl' => $this->buildAylikTekIzlemlilerRowsFetchUrl($state),
        ]);
    }

    /**
     * Aylık tek izlemliler listesi tablo satırları (JSON HTML parçası).
     */
    public function aylikTekIzlemlilerRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $state = $this->aylikTekIzlemlilerState();
        $slice = array_slice($state['allTcs'], $state['offset'], $state['limit']);
        $rows = (new Stats())->getBirIzlemPatientRows($slice, $state['orderByClause']);

        ob_start();
        include ROOT_PATH . '/views/admin/stats/partials/bir_izlemliler_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Sıralama: eski birIzlemliler ordering + isim/soyisim kırılımı */
    private function birIzlemOrderBySql(string $orderby, string $orderdir): string {
        $dir = strtoupper($orderdir) === 'DESC' ? 'DESC' : 'ASC';
        if ($orderby === 'h.kayittarihi') {
            return "h.kayittarihi {$dir}, h.isim ASC, h.soyisim ASC";
        }

        return \App\Helpers\QueryHelper::patientListOrderBy($orderby, $orderdir) . ', h.isim ASC, h.soyisim ASC';
    }

    /** Sistem veri sağlığı (eski superVeriDenetimPaneli) */
    public function dataHealth() {
        $dataHealthContentFetchUrl = esh_url('Stats', 'dataHealthContent');
        $pageTitle = 'Sistem veri sağlığı';
        $this->render('data_health', $pageTitle, [
            'dataHealthContentFetchUrl' => $dataHealthContentFetchUrl,
        ]);
    }

    /**
     * Veri sağlığı özet ve metrik listeleri (JSON HTML parçası).
     */
    public function dataHealthContent() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $health = (new Stats())->getDataHealthSnapshot();
        $kritik = (int) ($health->toplam_kritik ?? 0);

        ob_start();
        include ROOT_PATH . '/views/admin/stats/partials/data_health_content.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html, 'kritik' => $kritik], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return array{
     *   metric: string,
     *   labels: array<string, string>,
     *   orderby: string,
     *   orderdir: string,
     *   orderFragment: string,
     *   total: int,
     *   limit: int,
     *   page: int,
     *   pages: int,
     *   offset: int
     * }
     */
    private function dataHealthPatientsState(): array {
        $metric = isset($_GET['metric']) ? trim((string) $_GET['metric']) : '';
        $labels = Stats::dataHealthMetricLabels();
        if ($metric === '' || !isset($labels[$metric])) {
            header('Location: ' . esh_url('Stats', 'dataHealth'));
            exit;
        }

        $orderby = isset($_GET['orderby']) ? trim((string) $_GET['orderby']) : 'h.isim';
        $orderdir = (isset($_GET['orderdir']) && strtoupper((string) $_GET['orderdir']) === 'DESC') ? 'DESC' : 'ASC';
        $orderFragment = \App\Helpers\QueryHelper::patientListOrderBy($orderby, $orderdir);

        $total = (new Stats())->countDataHealthPatients($metric);
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
        $limit = max(10, min(200, $limit));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pages = max(1, (int) ceil($total / $limit));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $limit;

        return [
            'metric' => $metric,
            'labels' => $labels,
            'orderby' => $orderby,
            'orderdir' => $orderdir,
            'orderFragment' => $orderFragment,
            'total' => $total,
            'limit' => $limit,
            'page' => $page,
            'pages' => $pages,
            'offset' => $offset,
        ];
    }

    /**
     * @param array{
     *   metric: string,
     *   orderby: string,
     *   orderdir: string,
     *   page: int,
     *   limit: int
     * } $state
     */
    private function buildDataHealthPatientsRowsFetchUrl(array $state): string {
        $q = [
            'controller' => 'Stats',
            'action' => 'dataHealthPatientsRows',
            'metric' => $state['metric'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
        ];
        if ($state['page'] > 1) {
            $q['page'] = $state['page'];
        }
        if ($state['limit'] !== 50) {
            $q['limit'] = $state['limit'];
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    /** Veri sağlığı metriğine göre aktif hasta listesi */
    public function dataHealthPatients() {
        $state = $this->dataHealthPatientsState();

        $pageTitle = 'Veri sağlığı — hasta listesi';
        $this->render('data_health_patients', $pageTitle, [
            'metric' => $state['metric'],
            'metricLabel' => $state['labels'][$state['metric']],
            'page' => $state['page'],
            'pages' => $state['pages'],
            'total' => $state['total'],
            'limit' => $state['limit'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
            'dataHealthPatientsRowsFetchUrl' => $this->buildDataHealthPatientsRowsFetchUrl($state),
        ]);
    }

    /** Veri sağlığı hasta listesi satırları (JSON HTML parçası). */
    public function dataHealthPatientsRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $state = $this->dataHealthPatientsState();
        $rows = (new Stats())->getDataHealthPatients(
            $state['metric'],
            $state['orderFragment'],
            $state['limit'],
            $state['offset']
        );

        ob_start();
        include ROOT_PATH . '/views/admin/stats/partials/data_health_patients_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Son 3 ay izlem KPI (eski IzlemVerimlilikSkoru) */
    public function followKpi() {
        $model = new Stats();
        $kpi = $model->getFollowEfficiencyKpi();
        $pageTitle = 'İzlem verimlilik skoru';
        $this->render('follow_kpi', $pageTitle, ['kpi' => $kpi]);
    }

    /** Eski bolgeBazliVerimlilikRaporu (task=verimlilik) */
    public function regionalPerformance() {
        $model = new Stats();
        $rows = $model->getRegionalNeighborhoodVisitPerformance(3);
        $sinceYmd = date('Y-m-d', strtotime('-3 months'));
        $pageTitle = 'Bölgesel izlem performansı';
        $this->render('stats_regional_performance', $pageTitle, [
            'rows' => $rows,
            'since_ymd' => $sinceYmd,
            'rolling_months' => 3,
        ]);
    }

    /** Yıllık kapsama ve son 3 yıl izlem adetleri */
    public function yearlyFollow() {
        $model = new Stats();
        $yearly = $model->getYearlyFollowCoverage();
        $pageTitle = 'Yıllık izlem verimliliği';
        $this->render('yearly_follow', $pageTitle, ['yearly' => $yearly]);
    }

    /** En çok izlenen aktif hastalar */
    public function topVisits() {
        $pageTitle = 'En yoğun takip edilen hastalar';
        $this->render('top_visits', $pageTitle, [
            'rows' => [],
            'topVisitsRowsFetchUrl' => esh_url('Stats', 'topVisitsRows'),
        ]);
    }

    /**
     * En yoğun hastalar tablo satırları (JSON HTML parçası).
     */
    public function topVisitsRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rows = (new Stats())->getTopVisitedPatients(10);

        ob_start();
        include ROOT_PATH . '/views/admin/stats/partials/top_visits/table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Pasif koduna göre hasta durum dağılımı */
    public function patientStatus() {
        $model = new Stats();
        $rows = $model->getPatientStatusCounts();
        $pageTitle = 'Hasta durum dağılımı';
        $this->render('patient_status', $pageTitle, ['rows' => $rows]);
    }

    /** Bugün doğum günü olan aktif hastalar */
    public function birthdays() {
        $pageTitle = 'Bugün doğum günü';
        $this->render('birthdays', $pageTitle, [
            'rows' => [],
            'birthdaysRowsFetchUrl' => esh_url('Stats', 'birthdaysRows'),
        ]);
    }

    /**
     * Doğum günü listesi tablo satırları (JSON HTML parçası).
     */
    public function birthdaysRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rows = (new Stats())->getTodaysBirthdays() ?: [];

        ob_start();
        include ROOT_PATH . '/views/admin/stats/partials/birthdays/table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Bu ay izlenen hastaların yaş grupları */
    public function monthlyPool() {
        $stat = new Stats();
        $age = $stat->getMonthlyFollowUpAgeGroups();
        if (!$age) {
            $age = AgeBandHelper::emptyObject();
        }
        $pageTitle = 'Bu ay izlenen yaş grupları';
        $this->render('monthly_pool', $pageTitle, ['age' => $age]);
    }

    /** Cari ay izlem özeti kartları + son aylar sıklık tablosu */
    public function monthlyFollowFreq(): void {
        $stat = new Stats();
        $monthly = $stat->getMonthlyFollowUpStats();
        if (!$monthly) {
            $monthly = (object) ['toplamhasta' => 0, 'toplamizlem' => 0];
        }
        $history = $stat->getMonthlyFollowUpStatsLastMonths(6);
        $pageTitle = 'Aylık izlem sıklığı';
        $this->render('monthly_follow_freq', $pageTitle, [
            'monthly' => $monthly,
            'history' => $history,
        ]);
    }

    /** Takipten çıkarma nedenleri (pasiftarihi aralığı) */
    public function exitReasons() {
        [$from, $to, $dateFromTr, $dateToTr] = $this->statsFilterDateRange();
        $stat = new Stats();
        $rows = $stat->getExitReasons($from, $to) ?: [];
        $pageTitle = 'Takipten çıkarma nedenleri';
        $this->render('exit_reasons', $pageTitle, [
            'rows' => $rows,
            'date_from' => $dateFromTr,
            'date_to' => $dateToTr,
            'date_from_ymd' => $from,
            'date_to_ymd' => $to,
        ]);
    }

    /** Yaş grupları × cinsiyet (aktif) */
    public function ageGenderBands() {
        $stat = new Stats();
        $raw = $stat->getAgeGroups() ?: [];
        $pageTitle = 'Yaş grupları ve cinsiyet';
        $this->render('age_gender_bands', $pageTitle, ['raw' => $raw]);
    }

    /** Aktif hastalar — VKİ (boy/kilo) dağılımı */
    public function bmiVki() {
        $stat = new Stats();
        $report = $stat->getBmiVkiReport();
        $pageTitle = 'VKİ dağılımı (aktif hastalar)';
        $this->render('stats_bmi_vki', $pageTitle, ['r' => $report]);
    }

    /** Ay içi hareket: genel özet + yeni / çıkan (Stat getGeneralStats) */
    public function ayMovement() {
        $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
        $month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        if ($year < 2000 || $year > ((int) date('Y') + 1)) {
            $year = (int) date('Y');
        }
        $stat = new Stats();
        $g = $stat->getGeneralStats($year, $month);
        $turkceAylar = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
            7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
        ];
        $pageTitle = 'Ay hareket özeti';
        $this->render('ay_movement', $pageTitle, [
            'g' => $g,
            'year' => $year,
            'month' => $month,
            'period_label' => ($turkceAylar[$month] ?? '') . ' ' . $year,
            'turkce_aylar' => $turkceAylar,
            'filterExpanded' => isset($_GET['year']) || isset($_GET['month']),
        ]);
    }

    /** Operasyonel özet: ilçe, izlem trendi, e-rapor, pansuman, planlı, aylık grafik */
    public function operationsPulse() {
        $s = new Stats();
        $st = new Stats();
        $pageTitle = 'Operasyonel nabız';
        $this->render('operations_pulse', $pageTitle, [
            'ilce' => $s->getIlceActiveRanking(25),
            'trend' => $s->getVisitTrendStats(),
            'pendingMonth' => $s->getVisitPendingThisMonth(),
            'erapor' => $s->getEraporPoolStats(),
            'pansuman' => $s->getPansumanActiveCount(),
            'bagimlilik' => $s->getBagimlilikActiveBreakdown(),
            'planned' => $s->getPlannedOpenCount(),
            'waiting' => $s->getWaitingPatientCount(),
            'visitsYear' => $s->getCompletedVisitsThisYear(),
            'visitMonths' => $s->getCompletedVisitsByMonth(12),
            'brans' => $st->getEraporBransDistribution(),
        ]);
    }

    /** Hasta kartı kayıt ↔ randevu gün farkı (`#__hastalar`, aktif; `randevutarihi` dönem filtresi). */
    public function randevuKayitGap(): void
    {
        [$from, $to, $dateFromTr, $dateToTr] = $this->statsFilterDateRange('first day of -11 months');

        $report = (new Stats())->getHastaKayitRandevuGapReport($from, $to);
        $this->render('randevu_kayit_gap', 'Kayıt – randevu gün farkı', [
            'date_from' => $dateFromTr,
            'date_to' => $dateToTr,
            'date_from_ymd' => $from,
            'date_to_ymd' => $to,
            'report' => $report,
        ]);
    }

    /** Branş ve görüntülü muayene randevu takvimleri — dönem özeti */
    public function randevuTakvim(): void
    {
        [$from, $to, $dateFromTr, $dateToTr] = $this->statsFilterDateRange('first day of -11 months');

        $m = new Stats();
        $this->render('randevu_takvim', 'Randevu takvimleri', [
            'date_from' => $dateFromTr,
            'date_to' => $dateToTr,
            'date_from_ymd' => $from,
            'date_to_ymd' => $to,
            'kons' => $m->getRandevuTakvimReport('kons', $from, $to),
            'uhds' => $m->getRandevuTakvimReport('uhds', $from, $to),
        ]);
    }

    /** Planlı izlemler — yapıldı / yapılmadı ve öncelik özeti (planlanan tarih aralığı) */
    public function plannedVisitStats(): void
    {
        [$from, $to, $dateFromTr, $dateToTr, $filterExpanded] = $this->statsFilterDateRange('first day of -11 months');

        $report = (new Stats())->getPlannedVisitStatsReport($from, $to);
        $this->render('planned_visit_stats', 'Planlı izlem istatistikleri', [
            'date_from' => $dateFromTr,
            'date_to' => $dateToTr,
            'date_from_ymd' => $from,
            'date_to_ymd' => $to,
            'report' => $report,
            'statsDateFilterAction' => 'plannedVisitStats',
            'statsDateFilterIdPrefix' => 'stats-planned-visit',
            'statsDateFilterAccent' => 'primary',
            'filterExpanded' => $filterExpanded,
        ]);
    }

    /** Yapılan izlemler — yapıldı / yapılmadı özeti (izlem tarihi aralığı) */
    public function visitStats(): void
    {
        [$from, $to, $dateFromTr, $dateToTr, $filterExpanded] = $this->statsFilterDateRange('first day of -11 months');

        $report = (new Stats())->getVisitStatsReport($from, $to);
        $this->render('visit_stats', 'Yapılan izlem istatistikleri', [
            'date_from' => $dateFromTr,
            'date_to' => $dateToTr,
            'date_from_ymd' => $from,
            'date_to_ymd' => $to,
            'report' => $report,
            'statsDateFilterAction' => 'visitStats',
            'statsDateFilterIdPrefix' => 'stats-visit',
            'statsDateFilterAccent' => 'success',
            'filterExpanded' => $filterExpanded,
        ]);
    }

    /** Aktif hastalar — kayıt tarihine göre aylık dağılım (grafik + tablo) */
    public function kayitMonths(): void {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 24;
        if ($limit < 0) {
            $limit = 0;
        }
        $rows = (new Stats())->getKayitAyiStats() ?: [];
        if ($limit > 0) {
            $rows = array_slice($rows, 0, $limit);
        }
        $pageTitle = 'Kayıt ayları (aktif hasta)';
        $this->render('kayit_months', $pageTitle, [
            'rows' => $rows,
            'limit' => $limit,
        ]);
    }

    /** Eski admin stats → guvenceStats */
    public function guvenceDist() {
        $m = new Stats();
        $rows = $m->getGuvenceActiveDistribution();
        $this->render('stats_guvence', 'Güvence türleri (aktif)', ['rows' => $rows]);
    }

    /**
     * @return array{
     *   device: string,
     *   selectedDeviceLabel: ?string,
     *   labels: array<string, string>
     * }
     */
    private function specialDevicesState(): array {
        $device = isset($_GET['device']) ? trim((string) $_GET['device']) : '';
        $labels = PatientClinicalFlagsHelper::statsReportLabels();
        if (!isset($labels[$device])) {
            $device = '';
        }
        $selectedLabel = $device !== '' ? $labels[$device] : null;

        return [
            'device' => $device,
            'selectedDeviceLabel' => $selectedLabel,
            'labels' => $labels,
        ];
    }

    private function buildSpecialDevicesRowsFetchUrl(string $device): string {
        $q = [
            'controller' => 'Stats',
            'action' => 'specialDevicesRows',
            'device' => $device,
        ];

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    /** Eski specialStats */
    public function specialDevices() {
        $st = $this->specialDevicesState();
        $s = (new Stats())->getSpecialEquipmentSummary();

        $this->render('stats_special_devices', 'Cihaz ve özel durum özeti', [
            's' => $s,
            'selectedDevice' => $st['device'],
            'selectedDeviceLabel' => $st['selectedDeviceLabel'],
            'specialDevicesRowsFetchUrl' => $st['device'] !== '' ? $this->buildSpecialDevicesRowsFetchUrl($st['device']) : '',
        ]);
    }

    /**
     * Cihaz/özel durum hasta listesi tablo satırları (JSON HTML parçası).
     */
    public function specialDevicesRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $st = $this->specialDevicesState();
        if ($st['device'] === '') {
            echo json_encode(['ok' => true, 'html' => ''], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rows = (new Stats())->getSpecialEquipmentPatientsByField($st['device'], 1000);

        ob_start();
        include ROOT_PATH . '/views/admin/stats/partials/special_devices_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Eski barthelStats */
    public function barthel() {
        $m = new Stats();
        $b = $m->getBarthelDistribution();
        $this->render('stats_barthel', 'Barthel bağımlılık dağılımı', ['b' => $b]);
    }

    /** Eski tarihHata */
    public function chronologyIssues() {
        $m = new Stats();
        $rows = $m->getChronologyRegistrationVsFirstVisit();
        $this->render('stats_chronology', 'Kayıt / ilk izlem kronolojisi', ['rows' => $rows]);
    }

    /** Eski isYukuHesapla */
    private function workloadState(): array {
        $tabMap = ['kritik' => 'KRITIK', 'kronik' => 'KRONIK', 'standart' => 'STANDART'];
        $tabSlug = strtolower(trim((string) ($_GET['tab'] ?? 'kritik')));
        $activeGroup = $tabMap[$tabSlug] ?? 'KRITIK';
        if (!isset($tabMap[$tabSlug])) {
            $activeGroup = 'KRITIK';
            $tabSlug = 'kritik';
        }

        $sort = \App\Helpers\QueryHelper::statsWorkloadSort('hizmet_suresi_gun', 'DESC');

        $m = new Stats();
        $groupCounts = $m->getWorkloadContinuityGroupCounts();
        $total = (int) ($groupCounts[$activeGroup] ?? 0);
        [$limit, $offset, $page, $pages, $total] = $this->statsPagination($total, 50);

        return [
            'activeGroup' => $activeGroup,
            'tabSlug' => $tabSlug,
            'groupCounts' => $groupCounts,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit,
            'offset' => $offset,
            'total' => $total,
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
            'orderFragment' => $sort['orderFragment'],
        ];
    }

    /**
     * @param array{tabSlug: string, page: int, limit: int, orderby: string, orderdir: string} $state
     */
    private function buildWorkloadRowsFetchUrl(array $state): string {
        $q = [
            'controller' => 'Stats',
            'action' => 'workloadRows',
            'tab' => $state['tabSlug'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
        ];
        if ($state['page'] > 1) {
            $q['page'] = $state['page'];
        }
        if ($state['limit'] !== 50) {
            $q['limit'] = $state['limit'];
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    public function workload() {
        $state = $this->workloadState();
        $pagelink = esh_url('Stats', 'workload', ['tab' => '']) . rawurlencode($state['tabSlug'])
            . '&orderby=' . rawurlencode($state['orderby'])
            . '&orderdir=' . rawurlencode($state['orderdir']);

        $this->render('stats_workload', 'Hizmet süresi ve son izlem', [
            'activeGroup' => $state['activeGroup'],
            'tabSlug' => $state['tabSlug'],
            'groupCounts' => $state['groupCounts'],
            'page' => $state['page'],
            'pages' => $state['pages'],
            'limit' => $state['limit'],
            'total' => $state['total'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
            'pagelink' => $pagelink,
            'workloadRowsFetchUrl' => $this->buildWorkloadRowsFetchUrl($state),
        ]);
    }

    /**
     * Hasta bakım sürekliliği listesi tablo satırları (JSON HTML parçası).
     */
    public function workloadRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $state = $this->workloadState();
        $rows = (new Stats())->getWorkloadContinuityRowsByGroup(
            $state['activeGroup'],
            $state['limit'],
            $state['offset'],
            $state['orderFragment']
        );

        ob_start();
        include ROOT_PATH . '/views/admin/stats/partials/workload_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Eski islemGetir */
    public function visitProcedures() {
        [$from, $to, $dateFromTr, $dateToTr] = $this->statsFilterDateRange();
        $m = new Stats();
        $rows = $m->getProcedureCountsFromVisits($from, $to);
        $this->render('stats_visit_procedures', 'İzlemde yapılan işlemler', [
            'rows' => $rows,
            'date_from' => $dateFromTr,
            'date_to' => $dateToTr,
        ]);
    }

    /** Eski personelGetir */
    public function visitPersonnel() {
        [$from, $to, $dateFromTr, $dateToTr] = $this->statsFilterDateRange();

        $m = new Stats();
        $rows = $m->getPersonnelCountsFromVisits($from, $to) ?: [];
        foreach ($rows as $r) {
            $u = trim((string) ($r->unvan ?? ''));
            $r->unvan = ($u === '') ? 'Belirtilmemiş' : $u;
        }
        usort($rows, function ($a, $b) {
            $u = strcmp((string) ($a->unvan ?? ''), (string) ($b->unvan ?? ''));
            if ($u !== 0) {
                return $u;
            }
            return ((int) ($b->adet ?? 0)) <=> ((int) ($a->adet ?? 0));
        });

        $this->render('stats_visit_personnel', 'İzlem personel adetleri', [
            'rows' => $rows,
            'date_from' => $dateFromTr,
            'date_to' => $dateToTr,
        ]);
    }

    /** İzlemde konsültasyon alanları (brans/kons_istekler) aylık dökümü */
    public function visitConsultationMonthly() {
        [$from, $to, $dateFromTr, $dateToTr] = $this->statsFilterDateRange('first day of -5 months');

        $m = new Stats();
        $data = $m->getVisitConsultationMonthlyBreakdown($from, $to);
        $this->render('stats_visit_consultation_monthly', 'İzlem konsültasyon aylık döküm', [
            'data' => $data,
            'date_from' => $dateFromTr,
            'date_to' => $dateToTr,
        ]);
    }

    /** @deprecated `exitReasons` ile aynı veri; eski bağlantılar yönlendirilir */
    public function passiveReasons() {
        $q = ['controller' => 'Stats', 'action' => 'exitReasons'];
        if (isset($_GET['date_from']) && trim((string) $_GET['date_from']) !== '') {
            $q['date_from'] = trim((string) $_GET['date_from']);
        }
        if (isset($_GET['date_to']) && trim((string) $_GET['date_to']) !== '') {
            $q['date_to'] = trim((string) $_GET['date_to']);
        }
        header('Location: ' . \App\Helpers\UrlHelper::fromRequestParams($q));
        exit;
    }

    /**
     * @return array{
     *   ilce: string,
     *   mahalle: string,
     *   ilceFilter: ?string,
     *   mahalleFilter: ?string,
     *   limit: int,
     *   offset: int,
     *   page: int,
     *   pages: int,
     *   total: int,
     *   filterExpanded: bool
     * }
     */
    private function eraporListState(): array {
        $ilce = isset($_GET['ilce']) ? trim((string) $_GET['ilce']) : '';
        $mahalle = isset($_GET['mahalle']) ? trim((string) $_GET['mahalle']) : '';
        $ilceFilter = $ilce !== '' ? $ilce : null;
        $mahalleFilter = $mahalle !== '' ? $mahalle : null;
        $sort = $this->statsPatientDrilldownSortState('h.isim');

        $total = (new Stats())->countEraporPatients($ilceFilter, $mahalleFilter);
        [$limit, $offset, $page, $pages, $total] = $this->statsPagination($total);

        return [
            'ilce' => $ilce,
            'mahalle' => $mahalle,
            'ilceFilter' => $ilceFilter,
            'mahalleFilter' => $mahalleFilter,
            'limit' => $limit,
            'offset' => $offset,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
            'orderFragment' => $sort['orderFragment'],
            'filterExpanded' => $ilce !== '' || $mahalle !== '',
        ];
    }

    /**
     * @param array{ilce: string, mahalle: string, page: int, limit: int, orderby: string, orderdir: string} $state
     */
    private function buildEraporListRowsFetchUrl(array $state): string {
        $q = [
            'controller' => 'Stats',
            'action' => 'eraporListRows',
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
        ];
        if ($state['ilce'] !== '') {
            $q['ilce'] = $state['ilce'];
        }
        if ($state['mahalle'] !== '') {
            $q['mahalle'] = $state['mahalle'];
        }
        if ($state['page'] > 1) {
            $q['page'] = $state['page'];
        }
        if ($state['limit'] !== 50) {
            $q['limit'] = $state['limit'];
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    /** Eski Eraporlular (liste) */
    public function eraporList() {
        $state = $this->eraporListState();
        $addr = new Address();
        $ilceler = $addr->getDistricts() ?: [];
        $mahalleler = [];
        if ($state['ilce'] !== '') {
            $mahalleler = $addr->getSubs($state['ilce'], 'mahalle') ?: [];
        }
        $this->render('stats_erapor_list', 'e-Rapor işaretli hastalar', [
            'ilceler' => $ilceler,
            'mahalleler' => $mahalleler,
            'ilce' => $state['ilce'],
            'mahalle' => $state['mahalle'],
            'page' => $state['page'],
            'pages' => $state['pages'],
            'total' => $state['total'],
            'limit' => $state['limit'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
            'filterExpanded' => $state['filterExpanded'],
            'eraporRowsFetchUrl' => $this->buildEraporListRowsFetchUrl($state),
        ]);
    }

    /**
     * e-Rapor hasta listesi tablo satırları (JSON HTML parçası).
     */
    public function eraporListRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $state = $this->eraporListState();
        $rows = (new Stats())->getEraporPatientRows(
            $state['ilceFilter'],
            $state['mahalleFilter'],
            $state['limit'],
            $state['offset'],
            $state['orderFragment']
        );

        ob_start();
        include ROOT_PATH . '/views/admin/stats/partials/erapor_list_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return array{
     *   from: string,
     *   to: string,
     *   date_from: string,
     *   date_to: string,
     *   tab: string,
     *   limit: int,
     *   offset: int,
     *   page: int,
     *   pages: int,
     *   total: int,
     *   filterExpanded: bool
     * }
     */
    private function supplyReportsState(): array {
        [$from, $to, $dateFromTr, $dateToTr, $filterExpanded] = $this->statsFilterDateRange();

        $tab = isset($_GET['tab']) && $_GET['tab'] === 'bez' ? 'bez' : 'mama';
        $defaultKey = $tab === 'bez' ? 'h.bezraporbitis' : 'h.mamaraporbitis';
        $sort = $this->statsPatientDrilldownSortState($defaultKey);
        $m = new Stats();
        if ($tab === 'bez') {
            $total = $m->countBezRaporRows($from, $to);
        } else {
            $total = $m->countMamaRaporRows($from, $to);
        }
        [$limit, $offset, $page, $pages, $total] = $this->statsPagination($total);

        return [
            'from' => $from,
            'to' => $to,
            'date_from' => $dateFromTr,
            'date_to' => $dateToTr,
            'tab' => $tab,
            'limit' => $limit,
            'offset' => $offset,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
            'orderFragment' => $sort['orderFragment'],
            'filterExpanded' => $filterExpanded,
        ];
    }

    /**
     * @param array{date_from: string, date_to: string, tab: string, page: int, limit: int, orderby: string, orderdir: string} $state
     */
    private function buildSupplyReportsRowsFetchUrl(array $state): string {
        $q = [
            'controller' => 'Stats',
            'action' => 'supplyReportsRows',
            'tab' => $state['tab'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
        ];
        if ($state['date_from'] !== '') {
            $q['date_from'] = $state['date_from'];
        }
        if ($state['date_to'] !== '') {
            $q['date_to'] = $state['date_to'];
        }
        if ($state['page'] > 1) {
            $q['page'] = $state['page'];
        }
        if ($state['limit'] !== 50) {
            $q['limit'] = $state['limit'];
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    /** Eski mamaRaporu + bezRaporu */
    public function supplyReports() {
        $state = $this->supplyReportsState();
        $this->render('stats_supply_mama_bez', 'Mama / bez raporları', [
            'tab' => $state['tab'],
            'date_from' => $state['date_from'],
            'date_to' => $state['date_to'],
            'page' => $state['page'],
            'pages' => $state['pages'],
            'total' => $state['total'],
            'limit' => $state['limit'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
            'filterExpanded' => $state['filterExpanded'],
            'supplyRowsFetchUrl' => $this->buildSupplyReportsRowsFetchUrl($state),
        ]);
    }

    /**
     * Mama / bez rapor listesi tablo satırları (JSON HTML parçası).
     */
    public function supplyReportsRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $state = $this->supplyReportsState();
        $tab = $state['tab'];
        $m = new Stats();
        if ($tab === 'bez') {
            $rows = $m->getBezRaporRows($state['from'], $state['to'], $state['limit'], $state['offset'], $state['orderFragment']);
        } else {
            $rows = $m->getMamaRaporRows($state['from'], $state['to'], $state['limit'], $state['offset'], $state['orderFragment']);
        }

        ob_start();
        include ROOT_PATH . '/views/admin/stats/partials/supply_reports_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return array{
     *   from: string,
     *   to: string,
     *   date_from: string,
     *   date_to: string,
     *   limit: int,
     *   offset: int,
     *   page: int,
     *   pages: int,
     *   total: int,
     *   filterExpanded: bool
     * }
     */
    private function sondaChangesState(): array {
        [$from, $to, $dateFromTr, $dateToTr, $filterExpanded] = $this->statsFilterDateRange();
        $statsModel = new Stats();
        $sort = \App\Helpers\QueryHelper::statsSondaSort(
            $statsModel->sondaDegisimTarihiOrderExpr('h'),
            'sonda_degisim',
            'ASC'
        );

        $total = $statsModel->countSondaChangeRows($from, $to);
        [$limit, $offset, $page, $pages, $total] = $this->statsPagination($total);

        return [
            'from' => $from,
            'to' => $to,
            'date_from' => $dateFromTr,
            'date_to' => $dateToTr,
            'limit' => $limit,
            'offset' => $offset,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'orderby' => $sort['orderby'],
            'orderdir' => $sort['orderdir'],
            'orderFragment' => $sort['orderFragment'],
            'filterExpanded' => $filterExpanded,
        ];
    }

    /**
     * @param array{date_from: string, date_to: string, page: int, limit: int, orderby: string, orderdir: string} $state
     */
    private function buildSondaChangesRowsFetchUrl(array $state): string {
        $q = [
            'controller' => 'Stats',
            'action' => 'sondaChangesRows',
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
        ];
        if ($state['date_from'] !== '') {
            $q['date_from'] = $state['date_from'];
        }
        if ($state['date_to'] !== '') {
            $q['date_to'] = $state['date_to'];
        }
        if ($state['page'] > 1) {
            $q['page'] = $state['page'];
        }
        if ($state['limit'] !== 50) {
            $q['limit'] = $state['limit'];
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    /** Sonda değişim takibi (filtre: sondatarihi + 1 ay) */
    public function sondaChanges() {
        $state = $this->sondaChangesState();
        $this->render('stats_sonda', 'Sonda tarihi takibi', [
            'date_from' => $state['date_from'],
            'date_to' => $state['date_to'],
            'page' => $state['page'],
            'pages' => $state['pages'],
            'total' => $state['total'],
            'limit' => $state['limit'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
            'filterExpanded' => $state['filterExpanded'],
            'sondaRowsFetchUrl' => $this->buildSondaChangesRowsFetchUrl($state),
        ]);
    }

    /**
     * Sonda değişim listesi tablo satırları (JSON HTML parçası).
     */
    public function sondaChangesRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $state = $this->sondaChangesState();
        $rows = (new Stats())->getSondaChangeRows(
            $state['from'],
            $state['to'],
            $state['limit'],
            $state['offset'],
            $state['orderFragment']
        );

        ob_start();
        include ROOT_PATH . '/views/admin/stats/partials/sonda_changes_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Eski stats task=adres — adres hiyerarşisi + özellik filtresi durumu.
     *
     * @return array{
     *   ilce: string,
     *   mahalle: string,
     *   sokak: string,
     *   kapino: string,
     *   ozellik: string,
     *   orderby: string,
     *   orderdir: string,
     *   orderByClause: string,
     *   limit: int,
     *   offset: int,
     *   page: int,
     *   pages: int,
     *   total: int
     * }
     */
    private function buildAdresPatientFilterOrderBy(string $orderby, string $orderdir): string {
        $dir = strtoupper($orderdir) === 'DESC' ? 'DESC' : 'ASC';

        if ($orderby === 'sonizlemtarihi') {
            $sonExpr = \App\Helpers\QueryHelper::patientSonIzlemDateSqlExpr();

            return 'ORDER BY ' . $sonExpr . ' ' . $dir . ', h.isim ASC, h.soyisim ASC';
        }
        if ($orderby === 'ilceadi') {
            return 'ORDER BY ilc.adi ' . $dir . ', m.adi ASC, h.isim ASC, h.soyisim ASC';
        }
        if ($orderby === 'mahalleadi') {
            return 'ORDER BY m.adi ' . $dir . ', ilc.adi ASC, h.isim ASC, h.soyisim ASC';
        }
        if ($orderby === 'sokakadi') {
            return 'ORDER BY s.adi ' . $dir . ', ilc.adi ASC, m.adi ASC, h.isim ASC, h.soyisim ASC';
        }
        if ($orderby === 'kapinoadi') {
            return 'ORDER BY k.adi ' . $dir . ', s.adi ASC, ilc.adi ASC, m.adi ASC, h.isim ASC, h.soyisim ASC';
        }
        if ($orderby === 'h.isim') {
            return 'ORDER BY h.isim ' . $dir . ', h.soyisim ' . $dir;
        }
        if ($orderby === 'h.tckimlik') {
            return 'ORDER BY h.tckimlik ' . $dir . ', h.isim ASC, h.soyisim ASC';
        }
        if ($orderby === 'h.kayittarihi') {
            return 'ORDER BY h.kayittarihi ' . $dir . ', h.isim ASC, h.soyisim ASC';
        }

        return 'ORDER BY ' . \App\Helpers\QueryHelper::patientListOrderBy($orderby, $orderdir) . ', h.isim ASC, h.soyisim ASC';
    }

    private function adresPatientFilterState(): array {
        $norm = static function (string $key): string {
            $v = isset($_GET[$key]) ? trim((string) $_GET[$key]) : '';
            return ($v === '0') ? '' : $v;
        };
        $ilce = $norm('ilce');
        $mahalle = $norm('mahalle');
        $sokak = $norm('sokak');
        $kapino = $norm('kapino');
        $ozellik = $norm('ozellik');
        if ($ozellik !== '' && !in_array($ozellik, Stats::adresPatientOzellikFieldList(), true)) {
            $ozellik = '';
        }

        $orderby = isset($_GET['orderby']) ? trim((string) $_GET['orderby']) : 'h.isim';
        $orderdir = (isset($_GET['orderdir']) && strtoupper((string) $_GET['orderdir']) === 'DESC') ? 'DESC' : 'ASC';
        $allowedOrder = ['h.isim', 'h.tckimlik', 'h.kayittarihi', 'sonizlemtarihi', 'ilceadi', 'mahalleadi', 'sokakadi', 'kapinoadi'];
        if (!in_array($orderby, $allowedOrder, true)) {
            $orderby = 'h.isim';
        }
        $orderByClause = $this->buildAdresPatientFilterOrderBy($orderby, $orderdir);

        $total = (new Stats())->countAdresPatientFilter(
            $ilce !== '' ? $ilce : null,
            $mahalle !== '' ? $mahalle : null,
            $sokak !== '' ? $sokak : null,
            $kapino !== '' ? $kapino : null,
            $ozellik !== '' ? $ozellik : null
        );
        $limitReq = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
        [$limit, $offset, $page, $pages, $total] = $this->statsPagination($total, $limitReq);

        return [
            'ilce' => $ilce,
            'mahalle' => $mahalle,
            'sokak' => $sokak,
            'kapino' => $kapino,
            'ozellik' => $ozellik,
            'orderby' => $orderby,
            'orderdir' => $orderdir,
            'orderByClause' => $orderByClause,
            'limit' => $limit,
            'offset' => $offset,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'filterExpanded' => $ilce !== '' || $mahalle !== '' || $sokak !== '' || $kapino !== '' || $ozellik !== '',
        ];
    }

    /** @param array<string, mixed> $state */
    private function buildAdresPatientFilterRowsFetchUrl(array $state): string {
        $q = [
            'controller' => 'Stats',
            'action' => 'adresPatientFilterRows',
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
        ];
        foreach (['ilce', 'mahalle', 'sokak', 'kapino', 'ozellik'] as $k) {
            if (!empty($state[$k])) {
                $q[$k] = $state[$k];
            }
        }
        if ($state['page'] > 1) {
            $q['page'] = $state['page'];
        }
        if ($state['limit'] !== 50) {
            $q['limit'] = $state['limit'];
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    /** Eski stats task=adres — adres tabanlı hasta filtreleme */
    public function adresPatientFilter() {
        $state = $this->adresPatientFilterState();
        $addr = new Address();
        $ilceler = $addr->getDistrictsWithActivePatientCounts();
        $mahalleler = [];
        $sokaklar = [];
        $kapinolar = [];
        if ($state['ilce'] !== '') {
            $mahalleler = $addr->getAdresFilterChildrenWithCounts($state['ilce'], 'mahalle');
        }
        if ($state['mahalle'] !== '') {
            $sokaklar = $addr->getAdresFilterChildrenWithCounts($state['mahalle'], 'sokak');
        }
        if ($state['sokak'] !== '') {
            $kapinolar = $addr->getAdresFilterChildrenWithCounts($state['sokak'], 'kapino');
        }

        $this->render('stats_adres_patient_filter', 'Adrese göre hastalar', [
            'ilce' => $state['ilce'],
            'mahalle' => $state['mahalle'],
            'sokak' => $state['sokak'],
            'kapino' => $state['kapino'],
            'ozellik' => $state['ozellik'],
            'ozellikLabels' => Stats::adresPatientOzellikLabels(),
            'ilceler' => $ilceler,
            'mahalleler' => $mahalleler,
            'sokaklar' => $sokaklar,
            'kapinolar' => $kapinolar,
            'page' => $state['page'],
            'pages' => $state['pages'],
            'total' => $state['total'],
            'limit' => $state['limit'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
            'filterExpanded' => $state['filterExpanded'],
            'adresPatientFilterRowsFetchUrl' => $this->buildAdresPatientFilterRowsFetchUrl($state),
            'adresFilterAjaxUrl' => esh_url('Stats', 'adresFilterOptions'),
        ]);
    }

    /** Adres hasta filtresi — tablo satırları (JSON HTML). */
    public function adresPatientFilterRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $state = $this->adresPatientFilterState();
        $rows = (new Stats())->getAdresPatientFilterRows(
            $state['ilce'] !== '' ? $state['ilce'] : null,
            $state['mahalle'] !== '' ? $state['mahalle'] : null,
            $state['sokak'] !== '' ? $state['sokak'] : null,
            $state['kapino'] !== '' ? $state['kapino'] : null,
            $state['ozellik'] !== '' ? $state['ozellik'] : null,
            $state['orderByClause'],
            $state['limit'],
            $state['offset']
        );

        ob_start();
        include ROOT_PATH . '/views/admin/stats/partials/adres_patient_filter_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Adres filtresi — kademeli mahalle/sokak/kapı (JSON). */
    public function adresFilterOptions() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $tip = isset($_GET['tip']) ? trim((string) $_GET['tip']) : '';
        $ustId = isset($_GET['ust_id']) ? trim((string) $_GET['ust_id']) : '';
        if ($ustId === '' || $ustId === '0') {
            echo json_encode(['ok' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $addr = new Address();
        if ($tip === 'ilce') {
            $rows = $addr->getDistrictsWithActivePatientCounts();
        } else {
            $rows = $addr->getAdresFilterChildrenWithCounts($ustId, $tip);
        }

        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'id' => (string) ($r->id ?? ''),
                'adi' => (string) ($r->adi ?? ''),
                'sayi' => (int) ($r->sayi ?? 0),
            ];
        }

        echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** e-Rapor havuzu ↔ hasta kartı karşılaştırma özeti */
    public function eraporHastaUyum(): void {
        $pageTitle = 'e-Rapor – Hasta uyumu';
        $this->render('erapor_hasta_uyum', $pageTitle, [
            'eraporHastaUyumSummaryFetchUrl' => esh_url('Stats', 'eraporHastaUyumContent'),
            'eraporHastaUyumMetricsFetchUrl' => esh_url('Stats', 'eraporHastaUyumMetrics'),
        ]);
    }

    /** e-Rapor ↔ hasta uyum — özet kartlar (JSON HTML). */
    public function eraporHastaUyumContent(): void {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $snap = (new Stats())->getEraporHastaUyumSnapshot();

        ob_start();
        include ROOT_PATH . '/views/admin/stats/partials/erapor_hasta_uyum_content.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** e-Rapor ↔ hasta uyum — metrik tablosu (JSON HTML). GET: metric, compact=1 */
    public function eraporHastaUyumMetrics(): void {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $metric = isset($_GET['metric']) ? trim((string) $_GET['metric']) : '';
        $compact = isset($_GET['compact']) && (string) $_GET['compact'] === '1';
        $stats = new Stats();
        $activeMetric = ($metric !== '' && $stats->isEraporHastaUyumMetric($metric)) ? $metric : null;

        $snap = $stats->getEraporHastaUyumSnapshot();
        $metricsRows = $stats->eraporHastaUyumMetricsWithCounts($snap);
        $uyumsuz = (int) ($snap->uyumsuz_toplam ?? 0);

        ob_start();
        include ROOT_PATH . '/views/admin/stats/partials/erapor_hasta_uyum_metrics_content.php';
        $html = ob_get_clean();

        echo json_encode([
            'ok' => true,
            'html' => $html,
            'uyumsuz' => $uyumsuz,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return array{
     *   metric: string,
     *   labels: array<string, string>,
     *   patientPrimary: bool,
     *   orderby: string,
     *   orderdir: string,
     *   orderFragment: string,
     *   total: int,
     *   limit: int,
     *   page: int,
     *   pages: int,
     *   offset: int
     * }
     */
    private function eraporHastaUyumListState(): array {
        $metric = isset($_GET['metric']) ? trim((string) $_GET['metric']) : '';
        $labels = Stats::eraporHastaUyumMetricLabels();
        if ($metric === '' || !isset($labels[$metric])) {
            header('Location: ' . esh_url('Stats', 'eraporHastaUyum'));
            exit;
        }

        $stats = new Stats();
        $patientPrimary = $stats->isEraporHastaUyumPatientPrimary($metric);
        $orderby = isset($_GET['orderby']) ? trim((string) $_GET['orderby']) : ($patientPrimary ? 'h.isim' : 'e.basvurutarihi');
        $orderdir = (isset($_GET['orderdir']) && strtoupper((string) $_GET['orderdir']) === 'DESC') ? 'DESC' : 'ASC';
        $orderFragment = $stats->eraporHastaUyumOrderFragment($metric, $orderby, $orderdir);

        $total = $stats->countEraporHastaUyumRows($metric);
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
        $limit = max(10, min(200, $limit));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pages = max(1, (int) ceil($total / $limit));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $limit;

        return [
            'metric' => $metric,
            'labels' => $labels,
            'patientPrimary' => $patientPrimary,
            'orderby' => $orderby,
            'orderdir' => $orderdir,
            'orderFragment' => $orderFragment,
            'total' => $total,
            'limit' => $limit,
            'page' => $page,
            'pages' => $pages,
            'offset' => $offset,
        ];
    }

    /**
     * @param array{metric: string, orderby: string, orderdir: string, page: int, limit: int} $state
     */
    private function buildEraporHastaUyumListRowsFetchUrl(array $state): string {
        $q = [
            'controller' => 'Stats',
            'action' => 'eraporHastaUyumListRows',
            'metric' => $state['metric'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
        ];
        if ($state['page'] > 1) {
            $q['page'] = $state['page'];
        }
        if ($state['limit'] !== 50) {
            $q['limit'] = $state['limit'];
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    /** e-Rapor ↔ hasta uyum — detay listesi */
    public function eraporHastaUyumList(): void {
        $state = $this->eraporHastaUyumListState();
        $pageTitle = 'e-Rapor – Hasta uyumu — liste';
        $metricsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Stats',
            'action' => 'eraporHastaUyumMetrics',
            'metric' => $state['metric'],
            'compact' => '1',
        ]);
        $this->render('erapor_hasta_uyum_list', $pageTitle, [
            'metric' => $state['metric'],
            'metricLabel' => $state['labels'][$state['metric']],
            'patientPrimary' => $state['patientPrimary'],
            'page' => $state['page'],
            'pages' => $state['pages'],
            'total' => $state['total'],
            'limit' => $state['limit'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
            'eraporHastaUyumMetricsFetchUrl' => $metricsFetchUrl,
            'eraporHastaUyumListRowsFetchUrl' => $this->buildEraporHastaUyumListRowsFetchUrl($state),
        ]);
    }

    /** e-Rapor ↔ hasta uyum liste satırları (JSON HTML). */
    public function eraporHastaUyumListRows(): void {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $state = $this->eraporHastaUyumListState();
        $rows = (new Stats())->getEraporHastaUyumRows(
            $state['metric'],
            $state['orderFragment'],
            $state['limit'],
            $state['offset']
        );

        ob_start();
        $patientPrimary = $state['patientPrimary'];
        include ROOT_PATH . '/views/admin/stats/partials/erapor_hasta_uyum_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Bağımlılık düzeyi (aktif). */
    public function bagimlilikDist(): void {
        $m = new Stats();
        $pack = $m->getBagimlilikDistributionLabeled();
        $this->render('stats_bagimlilik', 'Bağımlılık dağılımı', $pack);
    }

    /** İlçe / mahalle yoğunluğu (aktif). */
    public function geoDistribution(): void {
        $m = new Stats();
        $this->render('stats_geo_distribution', 'Coğrafi dağılım', [
            'report' => $m->getGeoDistributionReport(30),
        ]);
    }

    /** Boy / kilo / VKİ veri kapsamı. */
    public function anthroCoverage(): void {
        $m = new Stats();
        $this->render('stats_anthro_coverage', 'Antropometri kapsamı', [
            'r' => $m->getAnthropometryCoverageReport(),
        ]);
    }

    /** Kayıt süresi (tenure) grupları. */
    public function kayitTenure(): void {
        $m = new Stats();
        $this->render('stats_kayit_tenure', 'Kayıt süresi dağılımı', $m->getKayitTenureReport());
    }

    /** Tanı sayısı (komorbidite yükü). */
    public function hastalikCountDist(): void {
        $m = new Stats();
        $this->render('stats_hastalik_count', 'Tanı sayısı dağılımı', $m->getHastalikCountDistribution());
    }

    /** Klinik cihaz / özel durum özeti. */
    public function clinicalProfile(): void {
        $m = new Stats();
        $this->render('stats_clinical_profile', 'Klinik profil özeti', [
            'report' => $m->getClinicalProfileReport(),
        ]);
    }

    /** Demografik alan tamamlama oranları. */
    public function demographicCompleteness(): void {
        $m = new Stats();
        $this->render('stats_demographic_completeness', 'Demografik veri tamamlama', [
            'report' => $m->getDemographicCompletenessReport(),
        ]);
    }

    /** Yaş ortalaması, medyan ve bantlar. */
    public function ageSummary(): void {
        $m = new Stats();
        $this->render('stats_age_summary', 'Yaş özeti', [
            'report' => $m->getAgeSummaryReport(),
        ]);
    }

    /** Bekleyen hasta havuzu (-3). */
    public function waitingPoolProfile(): void {
        $m = new Stats();
        $this->render('stats_waiting_pool', 'Bekleyen hasta profili', [
            'report' => $m->getWaitingPoolProfile(),
        ]);
    }

    /** Pansuman hastaları — zaman / gün. */
    public function pansumanProfile(): void {
        $m = new Stats();
        $this->render('stats_pansuman_profile', 'Pansuman profili', [
            'report' => $m->getPansumanProfile(),
        ]);
    }

    /** Kayıt yılı × kayıt anı yaş grubu. */
    public function kayitKohortAge(): void {
        $m = new Stats();
        $this->render('stats_kayit_kohort_age', 'Kayıt kohortu × yaş bandı', [
            'report' => $m->getKayitKohortAgeReport(10),
        ]);
    }

    /** Güvence türü × yaş bandı. */
    public function guvenceAgeBands(): void {
        $m = new Stats();
        $this->render('stats_guvence_age_bands', 'Güvence × yaş bandı', [
            'report' => $m->getGuvenceAgeBandsReport(),
        ]);
    }

    /** Telefon, fotoğraf ve ebeveyn adı doluluk. */
    public function fieldCoverage(): void {
        $m = new Stats();
        $this->render('stats_field_coverage', 'Alan doluluk özeti', [
            'report' => $m->getDemographicFieldCoverageReport(),
        ]);
    }

    /**
     * @return array{
     *   metric: string,
     *   labels: array<string, string>,
     *   orderby: string,
     *   orderdir: string,
     *   orderFragment: string,
     *   total: int,
     *   limit: int,
     *   page: int,
     *   pages: int,
     *   offset: int
     * }
     */
    private function fieldCoveragePatientsState(): array {
        $metric = isset($_GET['metric']) ? trim((string) $_GET['metric']) : '';
        $labels = Stats::parentNamePlaceholderMetricLabels();
        if ($metric === '' || !isset($labels[$metric])) {
            header('Location: ' . esh_url('Stats', 'fieldCoverage'));
            exit;
        }

        $orderby = isset($_GET['orderby']) ? trim((string) $_GET['orderby']) : 'h.isim';
        $orderdir = (isset($_GET['orderdir']) && strtoupper((string) $_GET['orderdir']) === 'DESC') ? 'DESC' : 'ASC';
        $orderFragment = \App\Helpers\QueryHelper::patientListOrderBy($orderby, $orderdir);

        $total = (new Stats())->countParentNamePlaceholderPatients($metric);
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
        $limit = max(10, min(200, $limit));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pages = max(1, (int) ceil($total / $limit));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $limit;

        return [
            'metric' => $metric,
            'labels' => $labels,
            'orderby' => $orderby,
            'orderdir' => $orderdir,
            'orderFragment' => $orderFragment,
            'total' => $total,
            'limit' => $limit,
            'page' => $page,
            'pages' => $pages,
            'offset' => $offset,
        ];
    }

    /**
     * @param array{
     *   metric: string,
     *   orderby: string,
     *   orderdir: string,
     *   page: int,
     *   limit: int
     * } $state
     */
    private function buildFieldCoveragePatientsRowsFetchUrl(array $state): string {
        $q = [
            'controller' => 'Stats',
            'action' => 'fieldCoveragePatientsRows',
            'metric' => $state['metric'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
        ];
        if ($state['page'] > 1) {
            $q['page'] = $state['page'];
        }
        if ($state['limit'] !== 50) {
            $q['limit'] = $state['limit'];
        }

        return \App\Helpers\UrlHelper::fromRequestParams($q);
    }

    /** Alan doluluk — anne/baba nokta placeholder hasta listesi */
    public function fieldCoveragePatients(): void {
        $state = $this->fieldCoveragePatientsState();

        $this->render('field_coverage_patients', 'Alan doluluk — hasta listesi', [
            'metric' => $state['metric'],
            'metricLabel' => $state['labels'][$state['metric']],
            'page' => $state['page'],
            'pages' => $state['pages'],
            'total' => $state['total'],
            'limit' => $state['limit'],
            'orderby' => $state['orderby'],
            'orderdir' => $state['orderdir'],
            'fieldCoveragePatientsRowsFetchUrl' => $this->buildFieldCoveragePatientsRowsFetchUrl($state),
        ]);
    }

    /** Alan doluluk placeholder hasta listesi satırları (JSON HTML parçası). */
    public function fieldCoveragePatientsRows(): void {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $state = $this->fieldCoveragePatientsState();
        $rows = (new Stats())->getParentNamePlaceholderPatients(
            $state['metric'],
            $state['orderFragment'],
            $state['limit'],
            $state['offset']
        );

        ob_start();
        include ROOT_PATH . '/views/admin/stats/partials/field_coverage_patients_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Stok özeti — kritik sayı, 30 gün çıkış, kategori kırılımı.
     */
    public function stokOzet() {
        if (!\App\Helpers\AppSettings::isModuleEnabled('stok') || !\App\Services\Stok\StokService::moduleReady()) {
            $_SESSION['error'] = 'Stok modülü kapalı veya kurulu değil.';
            header('Location: ' . esh_url('Stats', 'index'));
            exit;
        }
        $kurumId = \App\Helpers\TenantContext::filterKurumId();
        $ozet = (new \App\Models\StokMalzeme())->getOzetStats($kurumId);
        $kritikItems = (new \App\Models\StokMalzeme())->listCriticalItems($kurumId, 30);

        $this->render('stats_stok_ozet', 'Stok özeti', [
            'ozet' => $ozet,
            'kritikItems' => $kritikItems,
        ]);
    }

    /**
     * Mama/bez rapor bitişi + kritik stok birleşik panel.
     */
    public function supplyStokPanel() {
        [$from, $to, $dateFromTr, $dateToTr] = array_slice($this->statsFilterDateRange(), 0, 4);
        $statsModel = new Stats();
        $mamaTotal = $statsModel->countMamaRaporRows($from, $to);
        $bezTotal = $statsModel->countBezRaporRows($from, $to);

        $stokOzet = ['kritik_sayisi' => 0, 'cikis_30_gun' => 0.0, 'kategori' => []];
        $kritikItems = [];
        if (\App\Helpers\AppSettings::isModuleEnabled('stok') && \App\Services\Stok\StokService::moduleReady()) {
            $kurumId = \App\Helpers\TenantContext::filterKurumId();
            $stokOzet = (new \App\Models\StokMalzeme())->getOzetStats($kurumId);
            $kritikItems = (new \App\Models\StokMalzeme())->listCriticalItems($kurumId, 25);
        }

        $this->render('stats_supply_stok_panel', 'Sarf + stok paneli', [
            'date_from' => $dateFromTr,
            'date_to' => $dateToTr,
            'mamaTotal' => $mamaTotal,
            'bezTotal' => $bezTotal,
            'stokOzet' => $stokOzet,
            'kritikItems' => $kritikItems,
            'supplyReportsUrl' => esh_url('Stats', 'supplyReports'),
            'stokIndexUrl' => esh_url('Stok', 'index'),
        ]);
    }

    /**
     * Çapraz tablo raporları — action=xTab_{id} (ör. xTab_bagimlilikAge).
     *
     * @param array<int, mixed> $arguments
     */
    public function __call(string $name, array $arguments): void {
        unset($arguments);
        if (!str_starts_with($name, 'xTab_')) {
            header('HTTP/1.0 404 Not Found');
            die('Hata: <b>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</b> metodu bulunamadı.');
        }
        $id = substr($name, 5);
        if (!StatsCrossTabRegistry::has($id)) {
            header('HTTP/1.0 404 Not Found');
            die('Geçersiz çapraz tablo raporu.');
        }
        $this->renderCrossTab($id);
    }

    private function renderCrossTab(string $id): void {
        $months = StatsCrossTabBuilder::normalizePeriodMonths((int) ($_GET['months'] ?? 12));
        $m = new Stats();
        $report = StatsCrossTabBuilder::build($m, $id, ['months' => $months]);
        $meta = StatsCrossTabRegistry::definition($id);
        $title = (string) ($report['title'] ?? $meta['title'] ?? 'Çapraz tablo');
        $this->render('stats_cross_tab', $title, [
            'report' => $report,
            'months' => $months,
            'tabId' => $id,
        ]);
    }
}
