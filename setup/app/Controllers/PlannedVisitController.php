<?php
namespace App\Controllers;

use App\Helpers\ThemeViewHelper;
use App\Models\PlannedVisit;
use App\Models\Patient;
use App\Models\Islem;
use App\Models\User;
use App\Helpers\DateHelper;
use App\Helpers\AuditLogHelper;
use App\Helpers\IzlemPlanTekrarHelper;
use App\Helpers\PlannedVisitIndexPdfHelper;
use App\Helpers\AuthHelper;
use App\Helpers\IdHelper;
use App\Helpers\PatientAccessHelper;
use App\Helpers\IslemIdSettings;
use App\Helpers\VisitIslemHelper;
use App\Helpers\ZamanDilimiHelper;
use App\Helpers\ValidationHelper;

class PlannedVisitController {

    /**
     * Planlı izlem liste sayfasına güvenli geri dönüş (retq yalnızca bilinen filtre anahtarları).
     */
    private static function plannedVisitIndexListUrlFromRetq(?string $retq): string {
        $def = esh_url('PlannedVisit', 'index');
        if ($retq === null || trim($retq) === '') {
            return $def;
        }
        $parsed = [];
        parse_str($retq, $parsed);
        if (!is_array($parsed)) {
            return $def;
        }
        $allowed = ['search', 'date_from', 'date_to', 'secim', 'durum', 'limit', 'page', 'ordering'];
        $out = [];
        foreach ($allowed as $k) {
            if (!array_key_exists($k, $parsed)) {
                continue;
            }
            $v = $parsed[$k];
            if (is_array($v)) {
                continue;
            }
            $out[$k] = (string) $v;
        }
        $out['controller'] = 'PlannedVisit';
        $out['action'] = 'index';
        if (isset($out['secim'])) {
            $out['secim'] = (string) max(0, (int) $out['secim']);
        }
        if (isset($out['durum'])) {
            $d = (string) $out['durum'];
            if ($d !== '' && $d !== '0' && $d !== '1') {
                unset($out['durum']);
            }
        }
        if (isset($out['limit'])) {
            $out['limit'] = (string) max(10, min(200, (int) $out['limit']));
        }
        if (isset($out['page'])) {
            $out['page'] = (string) max(1, (int) $out['page']);
        }

        return \App\Helpers\UrlHelper::fromRequestParams($out);
    }

    /**
     * Pasif hasta bekleyen plan listesine güvenli geri dönüş (retq).
     */
    private static function passivePendingListUrlFromRetq(?string $retq): string
    {
        $def = esh_url('PlannedVisit', 'passivePendingPlans');
        if ($retq === null || trim($retq) === '') {
            return $def;
        }
        $parsed = [];
        parse_str($retq, $parsed);
        if (!is_array($parsed)) {
            return $def;
        }
        $allowed = ['search', 'pasif', 'limit', 'page', 'ordering'];
        $out = [];
        foreach ($allowed as $k) {
            if (!array_key_exists($k, $parsed)) {
                continue;
            }
            $v = $parsed[$k];
            if (is_array($v)) {
                continue;
            }
            $out[$k] = (string) $v;
        }
        $out['controller'] = 'PlannedVisit';
        $out['action'] = 'passivePendingPlans';
        if (isset($out['limit'])) {
            $out['limit'] = (string) max(10, min(200, (int) $out['limit']));
        }
        if (isset($out['page'])) {
            $out['page'] = (string) max(1, (int) $out['page']);
        }

        return \App\Helpers\UrlHelper::fromRequestParams($out);
    }

    private static function requireAdmin(): void
    {
        AuthHelper::requireAdmin();
    }

    /**
     * Planlı izlem listesi — ortak GET durumu.
     *
     * @return array<string, mixed>
     */
    private function indexListRequestState(): array {
        $today = date('Y-m-d');
        $limit = isset($_GET['limit']) ? max(10, min(200, (int) $_GET['limit'])) : 50;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $limit;
        $search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
        $dateFromRaw = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : '';
        $dateToRaw = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : '';
        $dateFrom = DateHelper::parseFilterDate($dateFromRaw, $today);
        $dateTo = DateHelper::parseFilterDate($dateToRaw, $today);
        if (strcmp($dateFrom, $dateTo) > 0) {
            $tmp = $dateFrom;
            $dateFrom = $dateTo;
            $dateTo = $tmp;
        }
        $secim = isset($_GET['secim']) ? (int) $_GET['secim'] : 0;
        $ordering = isset($_GET['ordering']) ? trim((string) $_GET['ordering']) : '';

        $durum = array_key_exists('durum', $_GET) ? trim((string) $_GET['durum']) : null;
        if ($durum === null && array_key_exists('status', $_GET)) {
            $durum = trim((string) $_GET['status']);
        }
        if ($durum === null) {
            $durum = '0';
        } elseif ($durum !== '' && $durum !== '0' && $durum !== '1') {
            $durum = '0';
        }

        $planFilterBase = [
            'controller' => 'PlannedVisit',
            'action' => 'index',
            'search' => $search,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'secim' => $secim,
            'durum' => $durum,
            'limit' => $limit,
        ];
        $planFilterQuery = http_build_query($planFilterBase);

        return [
            'limit' => $limit,
            'page' => $page,
            'offset' => $offset,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'secim' => $secim,
            'ordering' => $ordering,
            'durum' => $durum,
            'planFilterBase' => $planFilterBase,
            'planFilterQuery' => $planFilterQuery,
        ];
    }

    public function index() {
        $st = $this->indexListRequestState();
        $limit = $st['limit'];
        $page = $st['page'];
        $search = $st['search'];
        $dateFrom = $st['dateFrom'];
        $dateTo = $st['dateTo'];
        $secim = $st['secim'];
        $ordering = $st['ordering'];
        $durum = $st['durum'];
        $planFilterBase = $st['planFilterBase'];
        $planFilterQuery = $st['planFilterQuery'];

        $model = new PlannedVisit();
        $totalItems = $model->countAllPlanned($search, $durum, $dateFrom, $dateTo, $secim);
        $totalPlans = $totalItems;
        $totalPages = $totalItems > 0 ? (int) ceil($totalItems / $limit) : 1;

        $islemler = (new Islem())->getList();

        $indexRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams(array_merge($planFilterBase, [
            'action' => 'indexRows',
            'ordering' => $ordering,
            'page' => $page,
        ]));
        $plannedVisitIndexPdfParams = array_merge($planFilterBase, [
            'action' => 'indexPdfData',
            'ordering' => $ordering,
            'page' => $page,
        ]);
        $plannedVisitIndexPdfDataUrl = \App\Helpers\UrlHelper::fromRequestParams($plannedVisitIndexPdfParams);

        $passivePendingCount = 0;
        if (AuthHelper::sessionIsAdmin()) {
            $passivePendingCount = (new PlannedVisit())->countPassivePendingForPassivePatients();
        }

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'izlem/pindex');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Planlı izlem listesi tablo satırları (JSON HTML parçası).
     */
    public function indexRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $st = $this->indexListRequestState();
        $oncelikConfig = self::oncelikConfigMap();
        $planDeleteRetq = http_build_query(array_merge($st['planFilterBase'], [
            'page' => $st['page'],
            'ordering' => $st['ordering'],
        ]));

        $model = new PlannedVisit();
        $plans = $model->getAllPlanned(
            $st['limit'],
            $st['offset'],
            $st['search'],
            $st['durum'],
            $st['ordering'],
            $st['dateFrom'],
            $st['dateTo'],
            $st['secim']
        );

        ob_start();
        include ROOT_PATH . '/views/site/izlem/partials/pindex_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** @return array<string, array{text: string, class: string}> */
    private static function oncelikConfigMap(): array {
        return [
            '1' => ['text' => 'Normal', 'class' => 'success'],
            '2' => ['text' => 'Orta', 'class' => 'warning'],
            '3' => ['text' => 'Yüksek', 'class' => 'danger'],
            '0' => ['text' => '—', 'class' => 'secondary'],
        ];
    }

    /**
     * Planlı izlem listesi — pdfMake için mevcut sayfa satırları (JSON).
     */
    public function indexPdfData() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        AuthHelper::requireAdminJson();

        $st = $this->indexListRequestState();
        AuditLogHelper::plannedVisitExport([
            'page' => $st['page'],
            'date_from' => $st['dateFrom'],
            'date_to' => $st['dateTo'],
        ]);
        $islemler = (new Islem())->getList();
        $islemLabel = PlannedVisitIndexPdfHelper::islemFilterLabel($st['secim'], $islemler);
        $durumLabel = PlannedVisitIndexPdfHelper::durumFilterLabel($st['durum']);

        $model = new PlannedVisit();
        $plans = $model->getAllPlanned(
            $st['limit'],
            $st['offset'],
            $st['search'],
            $st['durum'],
            $st['ordering'],
            $st['dateFrom'],
            $st['dateTo'],
            $st['secim']
        );
        $total = (int) $model->countAllPlanned(
            $st['search'],
            $st['durum'],
            $st['dateFrom'],
            $st['dateTo'],
            $st['secim']
        );

        $rows = [];
        foreach ($plans as $p) {
            $rows[] = PlannedVisitIndexPdfHelper::exportPlanRow($p);
        }

        echo json_encode([
            'ok' => true,
            'headers' => PlannedVisitIndexPdfHelper::tableHeaders(),
            'rows' => $rows,
            'meta' => [
                'filterSummary' => PlannedVisitIndexPdfHelper::buildFilterSummary($st, $total, $islemLabel, $durumLabel),
                'generatedAt' => DateHelper::nowTrDateTime(),
            ],
            'filename' => PlannedVisitIndexPdfHelper::suggestFilename($st['durum'], $st['page']),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Pasif hastalarda bekleyen planlı izlemler — yalnızca yönetici; silme imkânı.
     */
    /**
     * Pasif hastalarda bekleyen planlar — ortak GET durumu.
     *
     * @return array<string, mixed>
     */
    private function passivePendingListRequestState(): array {
        $limit = isset($_GET['limit']) ? max(10, min(200, (int) $_GET['limit'])) : 50;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $limit;
        $search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
        $pasif = isset($_GET['pasif']) ? trim((string) $_GET['pasif']) : 'all';
        $ordering = isset($_GET['ordering']) ? trim((string) $_GET['ordering']) : '';

        $filterBase = [
            'controller' => 'PlannedVisit',
            'action' => 'passivePendingPlans',
            'search' => $search,
            'pasif' => $pasif,
            'limit' => $limit,
        ];
        $filterQuery = http_build_query($filterBase);

        return [
            'limit' => $limit,
            'page' => $page,
            'offset' => $offset,
            'search' => $search,
            'pasif' => $pasif,
            'ordering' => $ordering,
            'filterBase' => $filterBase,
            'filterQuery' => $filterQuery,
        ];
    }

    public function passivePendingPlans(): void
    {
        self::requireAdmin();

        $st = $this->passivePendingListRequestState();
        $limit = $st['limit'];
        $page = $st['page'];
        $search = $st['search'];
        $pasif = $st['pasif'];
        $ordering = $st['ordering'];
        $filterQuery = $st['filterQuery'];

        $model = new PlannedVisit();
        $totalItems = $model->countPassivePendingPlans($search, $pasif);

        $passivePendingRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams(array_merge($st['filterBase'], [
            'action' => 'passivePendingPlansRows',
            'ordering' => $ordering,
            'page' => $page,
        ]));

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'izlem/passive_pending_plans');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Pasif hastalarda bekleyen planlar — tablo satırları (JSON HTML parçası).
     */
    public function passivePendingPlansRows(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        AuthHelper::requireAdminJson();

        $st = $this->passivePendingListRequestState();
        $oncelikConfig = self::oncelikConfigMap();
        $deleteRetq = http_build_query([
            'search' => $st['search'],
            'pasif' => $st['pasif'],
            'page' => $st['page'],
            'limit' => $st['limit'],
            'ordering' => $st['ordering'],
        ]);

        $model = new PlannedVisit();
        $plans = $model->getPassivePendingPlans($st['limit'], $st['offset'], $st['search'], $st['ordering'], $st['pasif']);

        ob_start();
        include ROOT_PATH . '/views/site/izlem/partials/passive_pending_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Seçili pasif-hasta bekleyen planları toplu siler (POST, yönetici).
     */
    public function deletePassivePendingBulk(): void
    {
        self::requireAdmin();
        $listUrl = self::passivePendingListUrlFromRetq(isset($_POST['retq']) ? (string) $_POST['retq'] : null);

        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : [];
        if ($ids === []) {
            $_SESSION['error'] = 'Silinecek plan seçilmedi.';
            header('Location: ' . $listUrl);
            exit;
        }

        $deleted = (new PlannedVisit())->deletePassivePendingByIds($ids);
        if ($deleted > 0) {
            $_SESSION['success'] = $deleted . ' planlı izlem kaydı silindi.';
        } else {
            $_SESSION['error'] = 'Seçilen kayıtlar silinemedi veya zaten güncellenmiş.';
        }

        header('Location: ' . $listUrl);
        exit;
    }

    /**
     * Pasif tarihinden önceki seçili planları yapılmadı izlem + plan kapatma (POST, yönetici).
     */
    public function markPassivePendingMissedBulk(): void
    {
        self::requireAdmin();
        $listUrl = self::passivePendingListUrlFromRetq(isset($_POST['retq']) ? (string) $_POST['retq'] : null);

        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : [];
        if ($ids === []) {
            $_SESSION['error'] = 'Yapılmadı işaretlenecek plan seçilmedi.';
            header('Location: ' . $listUrl);
            exit;
        }

        $result = (new PlannedVisit())->markPassivePendingMissedByIds($ids);
        $marked = (int) ($result['marked'] ?? 0);
        $closed = (int) ($result['plans_closed'] ?? 0);
        $skipped = (int) ($result['skipped'] ?? 0);

        if ($closed > 0) {
            $msg = $closed . ' planlı izlem kapatıldı';
            if ($marked > 0) {
                $msg .= '; ' . $marked . ' yapılmadı izlem kaydı oluşturuldu';
            }
            if ($skipped > 0) {
                $msg .= ' (' . $skipped . ' kayıt atlandı — pasif tarihinden önce değil veya işlenemedi)';
            }
            $_SESSION['success'] = $msg . '.';
        } else {
            $_SESSION['error'] = $skipped > 0
                ? 'Seçilen kayıtlar işlenemedi. Yalnızca plan tarihi, hasta pasif tarihinden önce olanlar işaretlenir.'
                : 'Seçilen kayıtlar işlenemedi veya zaten güncellenmiş.';
        }

        header('Location: ' . $listUrl);
        exit;
    }

    /**
     * Pasif tarihinden sonraki seçili planları siler (POST, yönetici).
     */
    public function deletePassivePendingAfterBulk(): void
    {
        self::requireAdmin();
        $listUrl = self::passivePendingListUrlFromRetq(isset($_POST['retq']) ? (string) $_POST['retq'] : null);

        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : [];
        if ($ids === []) {
            $_SESSION['error'] = 'Silinecek plan seçilmedi.';
            header('Location: ' . $listUrl);
            exit;
        }

        $deleted = (new PlannedVisit())->deletePassivePendingAfterPasifByIds($ids);
        if ($deleted > 0) {
            $_SESSION['success'] = $deleted . ' planlı izlem kaydı silindi (pasif tarihinden sonraki planlar).';
        } else {
            $_SESSION['error'] = 'Seçilen kayıtlar silinemedi. Yalnızca plan tarihi, hasta pasif tarihinden sonra olanlar silinir.';
        }

        header('Location: ' . $listUrl);
        exit;
    }

    /**
     * Tek hastanın planlı izlem kayıtları (?tc=…).
     */
    /**
     * Hasta plan listesi — ortak GET durumu.
     *
     * @return array<string, mixed>
     */
    private function patientListRequestState(): array {
        $tc = isset($_GET['tc']) ? trim((string) $_GET['tc']) : '';
        if (array_key_exists('durum', $_GET)) {
            $durum = trim((string) $_GET['durum']);
            if ($durum !== '0' && $durum !== '1') {
                $durum = '';
            }
        } else {
            $durum = '0';
        }
        $limit = isset($_GET['limit']) ? max(10, min(100, (int) $_GET['limit'])) : 30;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $limit;
        $ordering = isset($_GET['ordering']) ? trim((string) $_GET['ordering']) : 'p.planlanantarih-ASC';

        $patientPlansBase = [
            'controller' => 'PlannedVisit',
            'action' => 'patient',
            'tc' => $tc,
            'durum' => $durum,
            'limit' => $limit,
        ];
        $patientPlansQueryBase = http_build_query($patientPlansBase);
        $patientPlansQuery = $patientPlansQueryBase . '&ordering=' . rawurlencode($ordering);

        return [
            'tc' => $tc,
            'durum' => $durum,
            'limit' => $limit,
            'page' => $page,
            'offset' => $offset,
            'ordering' => $ordering,
            'patientPlansBase' => $patientPlansBase,
            'patientPlansQuery' => $patientPlansQuery,
        ];
    }

    public function patient() {
        $st = $this->patientListRequestState();
        $tc = $st['tc'];
        if ($tc === '') {
            $_SESSION['error'] = 'Planları görmek için hasta TC kimlik numarası gerekli.';
            header('Location: ' . esh_url('PlannedVisit', 'index'));
            exit;
        }

        $patient = (new Patient())->findByTc($tc);
        if (!$patient) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . esh_url('PlannedVisit', 'index'));
            exit;
        }
        $patient = $this->requirePatientAccessByTc($tc, esh_url('PlannedVisit', 'index'));

        $limit = $st['limit'];
        $page = $st['page'];
        $durum = $st['durum'];
        $ordering = $st['ordering'];
        $patientPlansQuery = $st['patientPlansQuery'];
        $patientPlansQueryBase = http_build_query($st['patientPlansBase']);

        $model = new PlannedVisit();
        $totalItems = $model->countPatientPlans($tc, $durum);
        $totalPages = $totalItems > 0 ? (int) ceil($totalItems / $limit) : 1;

        $patientPlansFiltersOpen = ($durum !== '0' || $limit !== 30);
        $viewerKurumId = (int) ($patient->kurum_id ?? 0);

        $patientPlansRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams(array_merge($st['patientPlansBase'], [
            'action' => 'patientRows',
            'ordering' => $ordering,
            'page' => $page,
        ]));

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'izlem/patient_plans');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Hasta plan listesi tablo satırları (JSON HTML parçası).
     */
    public function patientRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $st = $this->patientListRequestState();
        $tc = $st['tc'];
        if ($tc === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'TC gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $patient = (new Patient())->findByTc($tc);
        if (!$patient) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Hasta bulunamadı'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!PatientAccessHelper::canAccessPatient((string) ($patient->id ?? ''), $patient)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Bu hasta kaydına erişim yetkiniz bulunmamaktadır.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $viewerKurumId = (int) ($patient->kurum_id ?? 0);
        $oncelikConfig = self::oncelikConfigMap();

        $model = new PlannedVisit();
        $plans = $model->getPatientPlans($tc, $st['limit'], $st['offset'], $st['durum'], $st['ordering']);

        ob_start();
        include ROOT_PATH . '/views/site/izlem/partials/patient_plans_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Planlı izlem satırını siler (yalnızca yönetici). GET id + tc ile hasta plan listesine dönüş.
     */
    public function delete() {
        $returnRaw = isset($_POST['return']) ? trim((string) $_POST['return']) : '';
        $returnIndex = $returnRaw === 'index';
        $returnPassivePending = $returnRaw === 'passivePending';
        $retq = isset($_POST['retq']) ? (string) $_POST['retq'] : '';
        $indexListUrl = self::plannedVisitIndexListUrlFromRetq($retq !== '' ? $retq : null);
        $passivePendingListUrl = self::passivePendingListUrlFromRetq($retq !== '' ? $retq : null);

        $tcRaw = isset($_POST['tc']) ? trim((string) $_POST['tc']) : '';
        $tc = preg_replace('/\D/', '', $tcRaw);
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);

        $redirectPatient = ($tc !== '' && ValidationHelper::isTcLength11($tc))
            ? esh_url('PlannedVisit', 'patient', ['tc' => $tc])
            : ($returnPassivePending ? $passivePendingListUrl : ($returnIndex ? $indexListUrl : esh_url('PlannedVisit', 'index')));
        $redirectFail = $returnPassivePending ? $passivePendingListUrl : ($returnIndex ? $indexListUrl : $redirectPatient);

        \App\Helpers\CsrfHelper::requirePostMethod($redirectFail);

        if (!AuthHelper::sessionIsAdmin()) {
            $_SESSION['error'] = 'Planlı izlem kaydı silmek için yönetici yetkisi gerekir.';
            header('Location: ' . $redirectFail);
            exit;
        }

        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz plan kaydı.';
            header('Location: ' . $redirectFail);
            exit;
        }

        $plan = new PlannedVisit();
        if (!$plan->load($id)) {
            $_SESSION['error'] = 'Plan kaydı bulunamadı.';
            header('Location: ' . $redirectFail);
            exit;
        }

        $this->requirePatientAccessByTc((string) $plan->hastatckimlik, $redirectFail);

        $recordTc = (string) $plan->hastatckimlik;
        if ($tc !== '' && ValidationHelper::isTcLength11($tc) && $recordTc !== $tc) {
            $_SESSION['error'] = 'İstek ile kayıt uyuşmuyor.';
            header('Location: ' . ($returnIndex ? $indexListUrl : esh_url('PlannedVisit', 'patient', ['tc' => $recordTc])));
            exit;
        }

        if ($plan->delete($id)) {
            $_SESSION['success'] = 'Planlı izlem kaydı silindi.';
        } else {
            $_SESSION['error'] = 'Plan silinirken bir hata oluştu.';
        }

        if ($returnPassivePending) {
            header('Location: ' . $passivePendingListUrl);
            exit;
        }

        if ($returnIndex) {
            header('Location: ' . $indexListUrl);
            exit;
        }

        $backTc = ($tc !== '' && ValidationHelper::isTcLength11($tc)) ? $tc : $recordTc;
        header('Location: ' . esh_url('PlannedVisit', 'patient', ['tc' => $backTc]));
        exit;
    }

    /**
     * Planlı izlem kaydını düzenleme formu (yalnızca yönetici / süper yönetici).
     */
    public function edit(): void
    {
        self::requireAdmin();

        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz plan kaydı.';
            header('Location: ' . esh_url('PlannedVisit', 'index'));
            exit;
        }

        $plan = new PlannedVisit();
        if (!$plan->load($id)) {
            $_SESSION['error'] = 'Plan kaydı bulunamadı.';
            header('Location: ' . esh_url('PlannedVisit', 'index'));
            exit;
        }

        $tc = trim((string) ($plan->hastatckimlik ?? ''));
        $tcGet = isset($_GET['tc']) ? preg_replace('/\D/', '', trim((string) $_GET['tc'])) : '';
        if ($tcGet !== '' && ValidationHelper::isTcLength11($tcGet) && $tcGet !== $tc) {
            $_SESSION['error'] = 'İstek ile kayıt uyuşmuyor.';
            header('Location: ' . esh_url('PlannedVisit', 'patient', ['tc' => $tc]));
            exit;
        }

        $patient = (new Patient())->findByTc($tc);
        if (!$patient) {
            $_SESSION['error'] = 'Plan kaydına bağlı hasta bulunamadı.';
            header('Location: ' . esh_url('PlannedVisit', 'index'));
            exit;
        }
        PatientAccessHelper::requirePatientAccess((string) $patient->id, $patient);

        $islemler = (new Islem())->getList();
        $preYapilacak = [];
        if (!empty($plan->yapilacak)) {
            $preYapilacak = array_filter(array_map('intval', explode(',', str_replace(' ', '', (string) $plan->yapilacak))));
        }
        $prePlaniyapan = [];
        if (!empty($plan->planiyapan)) {
            $prePlaniyapan = IdHelper::csvToEntityIds((string) $plan->planiyapan);
        }

        $list['islem'] = \App\Helpers\FormHelper::selectList($islemler, 'yapilacak[]', 'multiple="multiple" required', 'id', 'islemadi', $preYapilacak);
        $list['personel'] = \App\Helpers\FormHelper::selectList((new User())->getList(), 'planiyapan[]', 'multiple="multiple" required', 'id', 'name', $prePlaniyapan);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'izlem/plan_edit');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Planlı izlem kaydını günceller (POST, yalnızca yönetici / süper yönetici).
     */
    public function update(): void
    {
        self::requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('PlannedVisit', 'index'));
            exit;
        }

        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz plan kaydı.';
            header('Location: ' . esh_url('PlannedVisit', 'index'));
            exit;
        }

        $plan = new PlannedVisit();
        if (!$plan->load($id)) {
            $_SESSION['error'] = 'Plan kaydı bulunamadı.';
            header('Location: ' . esh_url('PlannedVisit', 'index'));
            exit;
        }

        $tc = trim((string) ($plan->hastatckimlik ?? ''));
        $postedTc = isset($_POST['hastatckimlik']) ? trim((string) $_POST['hastatckimlik']) : '';
        if ($postedTc === '' || $postedTc !== $tc) {
            $_SESSION['error'] = 'Hasta TC bilgisi kayıt ile uyuşmuyor.';
            header('Location: ' . esh_url('PlannedVisit', 'edit', ['id' => $id, 'tc' => $tc]));
            exit;
        }
        $this->requirePatientAccessByTc($tc, esh_url('PlannedVisit', 'index'));

        $redirectPatient = esh_url('PlannedVisit', 'patient', ['tc' => $tc]);

        $dateTr = isset($_POST['planlanantarih_date']) ? trim((string) $_POST['planlanantarih_date']) : '';
        $planYmd = DateHelper::trDateToYmd($dateTr);
        if ($planYmd === null) {
            $_SESSION['error'] = 'Planlanan tarih geçersiz. Tarihi GG-AA-YYYY olarak giriniz.';
            header('Location: ' . esh_url('PlannedVisit', 'edit', ['id' => $id, 'tc' => $tc]));
            exit;
        }

        $oncelik = isset($_POST['oncelik']) ? (int) $_POST['oncelik'] : 1;
        if ($oncelik < 1 || $oncelik > 3) {
            $oncelik = 1;
        }

        if (empty($_POST['yapilacak']) || !is_array($_POST['yapilacak'])) {
            $_SESSION['error'] = 'En az bir yapılacak işlem seçmelisiniz.';
            header('Location: ' . esh_url('PlannedVisit', 'edit', ['id' => $id, 'tc' => $tc]));
            exit;
        }
        $yapilacakCsv = implode(',', array_filter(array_map('intval', $_POST['yapilacak'])));

        if (empty($_POST['planiyapan']) || !is_array($_POST['planiyapan'])) {
            $_SESSION['error'] = 'Planı yapan personel seçmelisiniz.';
            header('Location: ' . esh_url('PlannedVisit', 'edit', ['id' => $id, 'tc' => $tc]));
            exit;
        }
        $planiyapanCsv = implode(',', IdHelper::csvToEntityIds(implode(',', (array) ($_POST['planiyapan'] ?? []))));

        $zaman = \App\Helpers\ZamanDilimiHelper::clamp($_POST['zaman'] ?? null);
        $zamanValid = \App\Helpers\ZamanDilimiHelper::validateForSave(
            $zaman,
            (int) ($plan->zaman ?? 0) > 0 && \App\Helpers\ZamanDilimiHelper::normalize($plan->zaman) === $zaman
        );
        if ($zamanValid !== true) {
            $_SESSION['error'] = is_string($zamanValid) ? $zamanValid : 'Geçersiz zaman dilimi.';
            header('Location: ' . esh_url('PlannedVisit', 'edit', ['id' => $id, 'tc' => $tc]));
            exit;
        }
        $aciklama = isset($_POST['aciklama']) ? trim((string) $_POST['aciklama']) : '';
        $aciklama = $aciklama !== '' ? $aciklama : null;

        $dupMsg = $this->duplicatePlanSameIslemMessage($tc, $planYmd, $yapilacakCsv, $id, $zaman);
        if ($dupMsg !== null) {
            $_SESSION['error'] = $dupMsg;
            header('Location: ' . esh_url('PlannedVisit', 'edit', ['id' => $id, 'tc' => $tc]));
            exit;
        }

        $plan->bind([
            'planlanantarih' => $planYmd,
            'yapilacak' => $yapilacakCsv,
            'zaman' => $zaman,
            'planiyapan' => $planiyapanCsv,
            'oncelik' => $oncelik,
            'aciklama' => $aciklama,
            'notlar' => $aciklama,
        ]);

        if ($plan->store()) {
            $_SESSION['success'] = 'Planlı izlem kaydı güncellendi.';
        } else {
            $_SESSION['error'] = 'Plan güncellenirken bir hata oluştu.';
        }

        header('Location: ' . $redirectPatient);
        exit;
    }
    
    public function create() {
        $tc = $_GET['tc'] ?? null; // URL'den gelen TC
        if (!$tc) {
            $_SESSION['error'] = "Geçersiz hasta bilgisi!";
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $patientModel = new Patient();
        $patient = $patientModel->findByTc($tc);
        if (!$patient || empty($patient->id)) {
            $_SESSION['error'] = 'Bu TC ile kayıtlı hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }
        PatientAccessHelper::requirePatientAccess((string) $patient->id, $patient);
        $this->requireAktifPatientForPlan($patient);

        $islemler = (new Islem())->getList();
        
        $list['islem'] = \App\Helpers\FormHelper::selectList($islemler, 'yapilacak[]', 'multiple="multiple" required', 'id', 'islemadi');
        
        $planlayan = (new User())->getList();
        
        $list['personel'] = \App\Helpers\FormHelper::selectList($planlayan, 'planiyapan[]', 'multiple="multiple" required', 'id', 'name', $_SESSION['user_id']);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'izlem/planla');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function plan_yogunluk_kontrol() {
        $tarihRaw = isset($_GET['tarih']) ? trim((string) $_GET['tarih']) : '';
        $zaman = \App\Helpers\ZamanDilimiHelper::clamp($_GET['zaman'] ?? null);

        $tarihYmd = DateHelper::trDateToYmd($tarihRaw);
        if ($tarihYmd === null) {
            http_response_code(400);
            echo '0';
            exit;
        }

        $db = (new PlannedVisit())->db;

        $nakilIslemId = IslemIdSettings::resolvedInt('nakil_islem_id');
        $zamanQ = (int) $zaman;
        $gunIndeks = (int) date('N', strtotime($tarihYmd));

        $planCount = (int) $db->loadResultPrepared(
            'SELECT COUNT(id) FROM #__pizlemler
             WHERE planlanantarih = ?
               AND zaman = ?
               AND COALESCE(durum, 0) = 0
               AND NOT FIND_IN_SET(?, REPLACE(COALESCE(yapilacak, \'\'), \' \', \'\'))',
            [$tarihYmd, $zamanQ, (string) $nakilIslemId]
        );

        $ilkCount = (int) $db->loadResultPrepared(
            'SELECT COUNT(id) FROM #__hastalar
             WHERE randevutarihi = ?
               AND zaman = ?
               AND pasif = \'-3\'',
            [$tarihYmd, $zamanQ]
        );

        $pansumanCount = (int) $db->loadResultPrepared(
            'SELECT COUNT(id) FROM #__hastalar
             WHERE pansuman = \'1\'
               AND pzaman = ?
               AND pasif = \'0\'
               AND FIND_IN_SET(?, pgunleri)',
            [$zamanQ, (string) $gunIndeks]
        );

        echo (string) ($planCount + $ilkCount + $pansumanCount);
        exit;
    }

    public function checkPlanSameSlot(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $tc = isset($_GET['tc']) ? trim((string) $_GET['tc']) : '';
        $tarihRaw = isset($_GET['tarih']) ? trim((string) $_GET['tarih']) : '';
        if (!ValidationHelper::isTcLength11($tc)) {
            echo json_encode(['ok' => false, 'error' => 'tc'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $ymd = DateHelper::trDateToYmd($tarihRaw);
        if ($ymd === null) {
            echo json_encode([
                'ok' => true,
                'overlapCount' => 0,
                'hasOverlap' => false,
                'overlapNames' => '',
                'overlapZamanLabel' => '',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $patient = (new Patient())->findByTc($tc);
        if (!$patient || empty($patient->id)) {
            echo json_encode(['ok' => false, 'error' => 'patient'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $islemIds = $this->parseYapilacakIdsFromQuery();
        $zaman = $this->parseZamanFromQuery();
        $excludePlanId = IdHelper::normalizeRequestId($_GET['exclude_plan_id'] ?? null);

        $planned = new PlannedVisit();
        $overlapIds = ($islemIds !== [] && $zaman !== null)
            ? $planned->overlappingYapilacakIslemIdsForPatientOnDate($tc, $ymd, $islemIds, $zaman, $excludePlanId)
            : [];
        $overlapNames = $overlapIds !== [] ? (new Islem())->namesForIds($overlapIds) : '';
        $overlapZamanLabel = ($zaman !== null && $overlapIds !== [])
            ? ZamanDilimiHelper::label($zaman)
            : '';
        $overlapCount = count($overlapIds);

        echo json_encode([
            'ok' => true,
            'overlapCount' => $overlapCount,
            'hasOverlap' => $overlapCount > 0,
            'overlapNames' => $overlapNames,
            'overlapZamanLabel' => $overlapZamanLabel,
            'tarih' => $ymd,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function store() {
        $data = $_POST;
        $tc = isset($data['hastatckimlik']) ? trim((string) $data['hastatckimlik']) : '';

        if ($tc === '') {
            $_SESSION['error'] = 'Geçersiz hasta bilgisi.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $patientRow = (new Patient())->findByTc($tc);
        if (!$patientRow || empty($patientRow->id)) {
            $_SESSION['error'] = 'Bu TC ile kayıtlı hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }
        PatientAccessHelper::requirePatientAccess((string) $patientRow->id, $patientRow);
        $this->requireAktifPatientForPlan($patientRow);

        $dateTr = isset($data['planlanantarih_date']) ? trim((string) $data['planlanantarih_date']) : '';
        $planYmd = DateHelper::trDateToYmd($dateTr);
        if ($planYmd === null) {
            $_SESSION['error'] = 'Planlanan tarih geçersiz. Tarihi GG-AA-YYYY olarak giriniz.';
            header('Location: ' . esh_url('PlannedVisit', 'create', ['tc' => $tc]));
            exit;
        }
        if (strcmp($planYmd, date('Y-m-d')) < 0) {
            $_SESSION['error'] = 'Planlanan tarih gecmis bir tarih olamaz.';
            header('Location: ' . esh_url('PlannedVisit', 'create', ['tc' => $tc]));
            exit;
        }

        $oncelik = isset($data['oncelik']) ? (int) $data['oncelik'] : 1;
        if ($oncelik < 1 || $oncelik > 3) {
            $oncelik = 1;
        }

        if (empty($data['yapilacak']) || !is_array($data['yapilacak'])) {
            $_SESSION['error'] = 'En az bir yapılacak işlem seçmelisiniz.';
            header('Location: ' . esh_url('PlannedVisit', 'create', ['tc' => $tc]));
            exit;
        }
        $yapilacakCsv = implode(',', array_filter(array_map('intval', $data['yapilacak'])));

        if (empty($data['planiyapan']) || !is_array($data['planiyapan'])) {
            $_SESSION['error'] = 'Planı yapan personel seçmelisiniz.';
            header('Location: ' . esh_url('PlannedVisit', 'create', ['tc' => $tc]));
            exit;
        }
        $planiyapanCsv = implode(',', IdHelper::csvToEntityIds(implode(',', (array) ($data['planiyapan'] ?? []))));

        $zaman = \App\Helpers\ZamanDilimiHelper::clamp($data['zaman'] ?? null);
        $zamanValid = \App\Helpers\ZamanDilimiHelper::validateForSave($zaman);
        if ($zamanValid !== true) {
            $_SESSION['error'] = is_string($zamanValid) ? $zamanValid : 'Geçersiz zaman dilimi.';
            header('Location: ' . esh_url('PlannedVisit', 'create', ['tc' => $tc]));
            exit;
        }

        $aralik = IzlemPlanTekrarHelper::normalizeAralik($data['tekrar_araligi'] ?? null);
        $sayi = IzlemPlanTekrarHelper::normalizeSayi($data['tekrar_sayisi'] ?? 1);
        if ($aralik === IzlemPlanTekrarHelper::ARALIK_YOK) {
            $sayi = 1;
        }

        $plantarihi = isset($data['plantarihi']) ? trim((string) $data['plantarihi']) : '';
        if ($plantarihi === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $plantarihi)) {
            $plantarihi = date('Y-m-d');
        }
        $plantarihiDt = $plantarihi . ' 00:00:00';

        $aciklama = isset($data['aciklama']) ? trim((string) $data['aciklama']) : '';
        $aciklama = $aciklama !== '' ? $aciklama : null;

        $dates = IzlemPlanTekrarHelper::expandPlanDates($planYmd, $aralik, $sayi);

        foreach ($dates as $ymd) {
            $dupMsg = $this->duplicatePlanSameIslemMessage($tc, $ymd, $yapilacakCsv, 0, $zaman);
            if ($dupMsg !== null) {
                $_SESSION['error'] = $dupMsg;
                header('Location: ' . esh_url('PlannedVisit', 'create', ['tc' => $tc]));
                exit;
            }
        }

        $db = (new PlannedVisit())->db;
        $db->beginTransaction();
        $ok = true;
        $inserted = 0;
        try {
            foreach ($dates as $ymd) {
                $pizlem = new PlannedVisit();
                $pizlem->bind([
                    'hastatckimlik' => $tc,
                    'planlanantarih' => $ymd,
                    'yapilacak' => $yapilacakCsv,
                    'zaman' => $zaman,
                    'planiyapan' => $planiyapanCsv,
                    'plantarihi' => $plantarihiDt,
                    'oncelik' => $oncelik,
                    'aciklama' => $aciklama,
                    'notlar' => $aciklama,
                    'durum' => 0,
                ]);
                if (!$pizlem->store()) {
                    $ok = false;
                    break;
                }
                $inserted++;
            }
            if ($ok) {
                $db->commit();
            } else {
                $db->rollBack();
            }
        } catch (\Throwable $e) {
            $db->rollBack();
            $ok = false;
        }

        if ($ok && $inserted > 0) {
            $_SESSION['success'] = $inserted > 1
                ? $inserted . ' adet planlı izlem kaydı oluşturuldu.'
                : 'İzlem planı başarıyla kaydedildi.';
            header('Location: ' . esh_url('Visit', 'history', ['tc' => $tc]));
        } else {
            $_SESSION['error'] = 'Plan kaydedilirken bir hata oluştu.';
            header('Location: ' . esh_url('PlannedVisit', 'create', ['tc' => $tc]));
        }
        exit;
    }

    private function requireAktifPatientForPlan(object $patient): void {
        if (Patient::isAktif($patient->pasif ?? null)) {
            return;
        }
        $_SESSION['error'] = 'Yalnızca aktif (pasif=0) hastalara izlem planlanabilir.';
        $id = IdHelper::normalizeRequestId($patient->id ?? null);
        header('Location: ' . ($id !== null ? esh_url('Patient', 'view', ['id' => $id]) : esh_url('Patient', 'unified', ['status' => 'active'])));
        exit;
    }

    private function requirePatientAccessByTc(string $tc, string $redirectUrl = ''): object
    {
        $tc = preg_replace('/\D/', '', trim($tc));
        if ($tc === '' || !ValidationHelper::isTcLength11($tc)) {
            $_SESSION['error'] = 'Geçersiz hasta TC bilgisi.';
            header('Location: ' . ($redirectUrl !== '' ? $redirectUrl : esh_url('PlannedVisit', 'index')));
            exit;
        }

        $patient = (new Patient())->findByTc($tc);
        if (!$patient || empty($patient->id)) {
            $_SESSION['error'] = 'Bu TC ile kayıtlı hasta bulunamadı.';
            header('Location: ' . ($redirectUrl !== '' ? $redirectUrl : esh_url('PlannedVisit', 'index')));
            exit;
        }

        return PatientAccessHelper::requirePatientAccess((string) $patient->id, $patient, $redirectUrl);
    }

    /**
     * Aynı hasta + plan tarihi + zaman dilimi + işlem çakışması varsa hata metni.
     */
    private function duplicatePlanSameIslemMessage(
        string $tc,
        string $planYmd,
        string $yapilacakCsv,
        int $excludePlanId = 0,
        ?int $zaman = null
    ): ?string {
        $tc = trim($tc);
        $islemIds = VisitIslemHelper::yapilanCsvToIntIds($yapilacakCsv);
        if (
            $tc === ''
            || $planYmd === ''
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $planYmd)
            || $islemIds === []
            || $zaman === null
            || !ZamanDilimiHelper::isValid($zaman)
        ) {
            return null;
        }

        $overlapIds = (new PlannedVisit())->overlappingYapilacakIslemIdsForPatientOnDate(
            $tc,
            $planYmd,
            $islemIds,
            $zaman,
            $excludePlanId
        );
        if ($overlapIds === []) {
            return null;
        }

        $names = (new Islem())->namesForIds($overlapIds);
        $tr = DateHelper::toTrOrEmpty($planYmd);
        $tarihPart = $tr !== '' ? $tr . ' tarihinde ' : 'seçilen tarihte ';
        $islemPart = $names !== '' ? ' («' . $names . '»)' : '';
        $zamanPart = ZamanDilimiHelper::label($zaman) . ' diliminde ';

        return 'Bu hasta için ' . $tarihPart . $zamanPart . 'aynı işlem' . $islemPart
            . ' zaten planlanmış. Aynı gün ve aynı zaman diliminde aynı işlemden tekrar plan oluşturulamaz.';
    }

    /**
     * @return int[]
     */
    private function parseYapilacakIdsFromQuery(): array
    {
        if (!isset($_GET['yapilacak'])) {
            return [];
        }
        if (is_array($_GET['yapilacak'])) {
            return array_values(array_unique(array_filter(array_map('intval', $_GET['yapilacak']))));
        }

        return VisitIslemHelper::yapilanCsvToIntIds((string) $_GET['yapilacak']);
    }

    private function parseZamanFromQuery(): ?int
    {
        if (!isset($_GET['zaman']) || trim((string) $_GET['zaman']) === '') {
            return null;
        }
        $z = ZamanDilimiHelper::normalize($_GET['zaman']);

        return ZamanDilimiHelper::isValid($z) ? $z : null;
    }
}