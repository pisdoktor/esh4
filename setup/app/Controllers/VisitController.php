<?php
namespace App\Controllers;

use App\Helpers\ThemeViewHelper;
use App\Models\Visit;
use App\Models\PlannedVisit;
use App\Models\Patient;
use App\Models\Islem;
use App\Models\User;
use App\Models\Arac;
use App\Models\Brans;
use App\Models\Istek;
use App\Helpers\DateHelper;
use App\Helpers\IzlemYapilmamaNedenHelper;
use App\Helpers\VisitIslemHelper;
use App\Helpers\ZamanDilimiHelper;
use App\Helpers\Ek3PdfHelper;
use App\Helpers\KonsBransIstekHelper;
use App\Helpers\VisitIndexPdfHelper;
use App\Helpers\AuthHelper;
use App\Helpers\AuditLogHelper;
use App\Helpers\PatientAccessHelper;
use App\Helpers\ValidationHelper;
use App\Helpers\OperationalSettings;
use App\Helpers\EsysComplianceHelper;
use App\Helpers\UsbsComplianceHelper;
use App\Helpers\ClinicalDecisionSupportHelper;
use App\Helpers\FieldVisitGeoHelper;
use App\Helpers\CsrfHelper;
use App\Services\Usbs\UsbsBridgeService;

class VisitController {

    /**
     * Aktif izlem listesi — ortak GET durumu.
     *
     * @return array{
     *   limit:int, page:int, offset:int, search:string, yap:string,
     *   dateFrom:string, dateTo:string, secim:int, ordering:string,
     *   totalPages:int, izlemSortBase:array<string, mixed>, izlemFilterQuery:string, izlemPagelink:string
     * }
     */
    private function indexListRequestState(): array {
        $today = date('Y-m-d');
        $limit = isset($_GET['limit']) ? max(10, min(200, (int) $_GET['limit'])) : 50;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $limit;
        $search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
        $yap = array_key_exists('yap', $_GET) ? (string) $_GET['yap'] : null;
        if ($yap === null && array_key_exists('status', $_GET)) {
            $yap = (string) $_GET['status'];
        }
        if ($yap === null || ($yap !== '0' && $yap !== '1')) {
            $yap = '1';
        }
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
        if ($ordering === '') {
            $ordering = 'h.isim-ASC';
        }

        $izlemSortBase = [
            'controller' => 'Visit',
            'action' => 'index',
            'search' => $search,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'secim' => $secim,
            'yap' => $yap,
            'limit' => $limit,
        ];
        $izlemFilterQuery = http_build_query(array_merge($izlemSortBase, ['ordering' => $ordering]));
        $izlemPagelink = \App\Helpers\UrlHelper::fromRequestParams(array_merge($izlemSortBase, ['ordering' => $ordering]));

        return [
            'limit' => $limit,
            'page' => $page,
            'offset' => $offset,
            'search' => $search,
            'yap' => $yap,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'secim' => $secim,
            'ordering' => $ordering,
            'izlemSortBase' => $izlemSortBase,
            'izlemFilterQuery' => $izlemFilterQuery,
            'izlemPagelink' => $izlemPagelink,
        ];
    }

    public function index() {
        $st = $this->indexListRequestState();
        $limit = $st['limit'];
        $page = $st['page'];
        $search = $st['search'];
        $yap = $st['yap'];
        $dateFrom = $st['dateFrom'];
        $dateTo = $st['dateTo'];
        $secim = $st['secim'];
        $ordering = $st['ordering'];
        $izlemSortBase = $st['izlemSortBase'];
        $izlemFilterQuery = $st['izlemFilterQuery'];
        $izlemPagelink = $st['izlemPagelink'];

        $model = new Visit();
        $totalVisits = $model->countAllVisits($search, $yap, $dateFrom, $dateTo, $secim);
        $totalPages = $totalVisits > 0 ? (int) ceil($totalVisits / $limit) : 1;

        $islemler = (new Islem())->getList();

        $indexRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams(array_merge($izlemSortBase, [
            'action' => 'indexRows',
            'ordering' => $ordering,
            'page' => $page,
        ]));
        $visitIndexPdfParams = array_merge($izlemSortBase, [
            'action' => 'indexPdfData',
            'ordering' => $ordering,
            'page' => $page,
        ]);
        $visitIndexPdfDataUrl = \App\Helpers\UrlHelper::fromRequestParams($visitIndexPdfParams);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'izlem/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Aktif izlem listesi tablo satırları (JSON HTML parçası).
     */
    public function indexRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $st = $this->indexListRequestState();
        $model = new Visit();
        $visits = $model->getAllVisits(
            $st['limit'],
            $st['offset'],
            $st['search'],
            $st['yap'],
            $st['ordering'],
            $st['dateFrom'],
            $st['dateTo'],
            $st['secim']
        );

        ob_start();
        include ROOT_PATH . '/views/site/izlem/partials/index_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Aktif izlem listesi — pdfMake için mevcut sayfa satırları (JSON).
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
        AuditLogHelper::visitExport([
            'page' => $st['page'],
            'date_from' => $st['dateFrom'],
            'date_to' => $st['dateTo'],
        ]);
        $islemler = (new Islem())->getList();
        $islemLabel = VisitIndexPdfHelper::islemFilterLabel($st['secim'], $islemler);

        $model = new Visit();
        $visits = $model->getAllVisits(
            $st['limit'],
            $st['offset'],
            $st['search'],
            $st['yap'],
            $st['ordering'],
            $st['dateFrom'],
            $st['dateTo'],
            $st['secim']
        );
        $total = (int) $model->countAllVisits(
            $st['search'],
            $st['yap'],
            $st['dateFrom'],
            $st['dateTo'],
            $st['secim']
        );

        $rows = [];
        foreach ($visits as $row) {
            $rows[] = VisitIndexPdfHelper::exportVisitRow($row);
        }

        echo json_encode([
            'ok' => true,
            'headers' => VisitIndexPdfHelper::tableHeaders(),
            'rows' => $rows,
            'meta' => [
                'filterSummary' => VisitIndexPdfHelper::buildFilterSummary($st, $total, $islemLabel),
                'generatedAt' => DateHelper::nowTrDateTime(),
            ],
            'filename' => VisitIndexPdfHelper::suggestFilename($st['yap'], $st['page']),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * İzlem geçmişi — ortak GET durumu.
     *
     * @return array<string, mixed>
     */
    private function historyListRequestState(): array {
        $tc = isset($_GET['tc']) ? preg_replace('/\D/', '', trim((string) $_GET['tc'])) : '';
        $limit = isset($_GET['limit']) ? max(5, min(100, (int) $_GET['limit'])) : 20;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $limit;
        $status = isset($_GET['status']) ? (string) $_GET['status'] : '';

        $historyPagelinkParams = ['tc' => $tc];
        if ($status !== '') {
            $historyPagelinkParams['status'] = $status;
        }
        $historyPagelink = esh_url('Visit', 'history', $historyPagelinkParams);

        return [
            'tc' => $tc,
            'limit' => $limit,
            'page' => $page,
            'offset' => $offset,
            'status' => $status,
            'historyPagelink' => $historyPagelink,
            'historyPagelinkParams' => $historyPagelinkParams,
        ];
    }

    public function history() {
        $st = $this->historyListRequestState();
        $tc = $st['tc'];
        if ($tc === '') {
            $_SESSION['error'] = 'İzlem geçmişi için TC kimlik numarası gerekli.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $limit = $st['limit'];
        $page = $st['page'];
        $status = $st['status'];
        $historyPagelink = $st['historyPagelink'];

        $historyRedirect = esh_url('Patient', 'unified', ['status' => 'active']);
        $historyPatient = $this->requirePatientAccessByTc($tc, $historyRedirect);

        $model = new Visit();
        $patientIdForHeader = ($historyPatient && !empty($historyPatient->id)) ? (int) $historyPatient->id : 0;
        $viewerKurumId = (int) ($historyPatient->kurum_id ?? \App\Helpers\TenantContext::assignKurumIdForStore());

        $total = $model->countPatientVisits($tc, $status);
        $totalPages = $total > 0 ? (int) ceil($total / $limit) : 1;
        $historyFiltersOpen = ($status !== '');

        $ek3OpenVisitId = isset($_GET['ek3_open']) ? max(0, (int) $_GET['ek3_open']) : 0;
        if ($ek3OpenVisitId > 0) {
            $ek3VisitCheck = new Visit();
            if (!$ek3VisitCheck->load($ek3OpenVisitId)
                || preg_replace('/\D/', '', (string) $ek3VisitCheck->hastatckimlik) !== preg_replace('/\D/', '', $tc)) {
                $ek3OpenVisitId = 0;
            }
        }

        $historyRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams(array_merge($st['historyPagelinkParams'], [
            'controller' => 'Visit',
            'action' => 'historyRows',
            'limit' => $limit,
            'page' => $page,
            'status' => $status,
        ]));

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'izlem/history');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * İzlem geçmişi tablo satırları (JSON HTML parçası).
     */
    public function historyRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $st = $this->historyListRequestState();
        $tc = $st['tc'];
        if ($tc === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'TC gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $historyRedirect = esh_url('Patient', 'unified', ['status' => 'active']);
        $historyPatient = $this->requirePatientAccessByTc($tc, $historyRedirect);
        $viewerKurumId = (int) ($historyPatient->kurum_id ?? \App\Helpers\TenantContext::assignKurumIdForStore());

        $model = new Visit();
        $visits = $model->getPatientVisits($tc, $st['limit'], $st['offset'], $st['status'], 'i.izlemtarihi DESC');

        ob_start();
        include ROOT_PATH . '/views/site/izlem/partials/history_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * İzlem satırını siler (yalnızca yönetici).
     * GET: id, tc (doğrulama), return: missed | index | (boş = history).
     */
    public function delete() {
        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('Visit', 'index'));
        $tcRaw = isset($_POST['tc']) ? trim((string) $_POST['tc']) : '';
        $tc = preg_replace('/\D/', '', $tcRaw);
        $id = (int) ($_POST['id'] ?? 0);
        $returnRaw = isset($_POST['return']) ? trim((string) $_POST['return']) : '';

        $listUrlForTc = function (string $tc11) use ($returnRaw) {
            if ($returnRaw === 'index') {
                return esh_url('Visit', 'index');
            }
            if ($returnRaw === 'missed' && ValidationHelper::isTcLength11($tc11)) {
                return esh_url('Visit', 'missed', ['tc' => $tc11]);
            }

            return esh_url('Visit', 'history', ['tc' => $tc11]);
        };

        if ($returnRaw === 'index') {
            $redirectDefault = esh_url('Visit', 'index');
        } elseif ($tc !== '' && ValidationHelper::isTcLength11($tc)) {
            $redirectDefault = $listUrlForTc($tc);
        } else {
            $redirectDefault = esh_url('Visit', 'index');
        }

        if ($id < 1) {
            $_SESSION['error'] = 'Geçersiz izlem kaydı.';
            header('Location: ' . $redirectDefault);
            exit;
        }

        $izlem = new Visit();
        if (!$izlem->load($id)) {
            $_SESSION['error'] = 'İzlem kaydı bulunamadı.';
            header('Location: ' . $redirectDefault);
            exit;
        }

        $this->requirePatientAccessByTc((string) $izlem->hastatckimlik, $redirectDefault);

        $recordTc = (string) $izlem->hastatckimlik;
        if ($tc !== '' && ValidationHelper::isTcLength11($tc) && $recordTc !== $tc) {
            $_SESSION['error'] = 'İstek ile kayıt uyuşmuyor.';
            $mis = ($returnRaw === 'index')
                ? esh_url('Visit', 'index')
                : (esh_url('Visit', 'history', ['tc' => $recordTc]));
            header('Location: ' . $mis);
            exit;
        }

        if ($izlem->delete($id)) {
            AuditLogHelper::visitDelete($izlem);
            $_SESSION['success'] = 'İzlem kaydı silindi.';
        } else {
            $_SESSION['error'] = 'İzlem silinirken bir hata oluştu.';
        }

        $backTc = ($tc !== '' && ValidationHelper::isTcLength11($tc)) ? $tc : $recordTc;
        header('Location: ' . $listUrlForTc($backTc));
        exit;
    }

    public function create() {
        $tc = isset($_GET['tc']) ? trim((string) $_GET['tc']) : '';
        if ($tc === '') {
            $_SESSION['error'] = 'Yeni izlem için geçerli hasta TC bilgisi gerekli.';
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
        PatientAccessHelper::requirePatientAccess((int) $patient->id, $patient);
        $this->requireAktifPatientForVisit($patient);

        $planId = isset($_GET['plan_id']) ? (int) $_GET['plan_id'] : 0;
        $plan = null;
        if ($planId > 0) {
            $pModel = new PlannedVisit();
            if ($pModel->load($planId) && (string) $pModel->hastatckimlik === $tc) {
                $plan = $pModel;
            }
        }

        $islemler = (new Islem())->getList();
        $personel = (new User())->getList();

        $preYapilan = [];
        if ($plan && !empty($plan->yapilacak)) {
            $preYapilan = array_filter(array_map('intval', explode(',', str_replace(' ', '', (string) $plan->yapilacak))));
        } elseif (!$plan && isset($_GET['yapilan'])) {
            $gy = $_GET['yapilan'];
            if (is_array($gy)) {
                $ids = array_filter(array_map('intval', $gy));
            } else {
                $ids = array_filter(array_map('intval', explode(',', str_replace(' ', '', (string) $gy))));
            }
            if (!empty($ids)) {
                $preYapilan = array_values(array_unique($ids));
            }
        }

        $defaultIzlemDate = date('Y-m-d');
        if ($plan && !empty($plan->planlanantarih)) {
            $ts = strtotime((string) $plan->planlanantarih);
            if ($ts) {
                $defaultIzlemDate = date('Y-m-d', $ts);
            }
        } elseif (!$plan) {
            $tarihGet = isset($_GET['tarih']) ? trim((string) $_GET['tarih']) : '';
            if ($tarihGet !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tarihGet)) {
                $defaultIzlemDate = $tarihGet;
            }
        }

        $defaultZaman = null;
        $autoZaman = \App\Helpers\UIHelper::zamanDilimiFromHour();
        if ($autoZaman !== null) {
            $defaultZaman = $autoZaman;
        }
        if ($plan && isset($plan->zaman) && $plan->zaman !== '' && $plan->zaman !== null) {
            $defaultZaman = \App\Helpers\ZamanDilimiHelper::normalize($plan->zaman);
        } elseif (!$plan && isset($_GET['zaman']) && $_GET['zaman'] !== '') {
            $defaultZaman = \App\Helpers\ZamanDilimiHelper::normalize($_GET['zaman']);
        }

        $todayYmd = date('Y-m-d');
        if (strcmp($defaultIzlemDate, $todayYmd) > 0) {
            $defaultIzlemDate = $todayYmd;
        }

        $list = [];
        $list['islem'] = \App\Helpers\FormHelper::selectList(
            $islemler,
            'yapilan[]',
            'multiple="multiple" size="6" required',
            'id',
            'islemadi',
            $preYapilan
        );
        $prePersonel = [];
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if ($uid > 0) {
            $prePersonel = [$uid];
        }
        $list['personel'] = \App\Helpers\FormHelper::selectList(
            $personel,
            'personel_id[]',
            'multiple="multiple" size="5"',
            'id',
            'name',
            $prePersonel
        );

        $araclar = (new Arac())->getList();
        $selectedAracId = 0;

        $defaultAciklama = '';
        if (isset($_GET['aciklama'])) {
            $defaultAciklama = trim((string) $_GET['aciklama']);
            if (strlen($defaultAciklama) > 4000) {
                $defaultAciklama = substr($defaultAciklama, 0, 4000);
            }
        }
        $uhdsId = isset($_GET['uhds_id']) ? (int) $_GET['uhds_id'] : 0;
        if ($uhdsId > 0 && $defaultAciklama === '') {
            $uhdsRow = new \App\Models\Uhds();
            if ($uhdsRow->load($uhdsId) && (string) $uhdsRow->hastatckimlik === $tc) {
                $defaultAciklama = trim((string) ($uhdsRow->telehealth_summary ?? ''));
            } else {
                $uhdsId = 0;
            }
        }

        $clinicalDecisionAlerts = [];
        if (ClinicalDecisionSupportHelper::showOnVisitForm()) {
            $patientRow = (new Patient())->getById((int) $patient->id);
            if ($patientRow) {
                $assessments = ClinicalDecisionSupportHelper::loadAssessmentBundle((int) $patient->id, $patientRow);
                $daysSince = ClinicalDecisionSupportHelper::daysSinceLastCompletedVisit(
                    isset($patientRow->son_yapilan_tarih) ? (string) $patientRow->son_yapilan_tarih : null
                );
                $clinicalDecisionAlerts = ClinicalDecisionSupportHelper::evaluateAlerts(
                    $patientRow,
                    $assessments,
                    $daysSince
                );
            }
        }

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'izlem/create');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Yeni izlem formu: seçilen gün için hasta başına mevcut kayıt sayısı (JSON, oturum gerekli).
     */
    public function checkVisitSameDay() {
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
                'count' => 0,
                'has' => false,
                'plannedCount' => 0,
                'hasPlanned' => false,
                'isToday' => false,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $patient = (new Patient())->findByTc($tc);
        if (!$patient || empty($patient->id)) {
            echo json_encode(['ok' => false, 'error' => 'patient'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $yapilanIds = $this->parseIslemIdsFromQuery();
        $zaman = $this->parseZamanFromQuery();
        $visit = new Visit();
        $overlapIds = ($yapilanIds !== [] && $zaman !== null)
            ? $visit->overlappingIslemIdsForPatientOnDate($tc, $ymd, $yapilanIds, $zaman)
            : [];
        $overlapNames = $overlapIds !== [] ? (new Islem())->namesForIds($overlapIds) : '';
        $overlapZamanLabel = ($zaman !== null && $overlapIds !== [])
            ? ZamanDilimiHelper::label($zaman)
            : '';
        $planned = new PlannedVisit();
        $plannedCount = $planned->countPendingForPatientOnDate($tc, $ymd);
        $isToday = ($ymd === date('Y-m-d'));
        $overlapCount = count($overlapIds);
        echo json_encode([
            'ok' => true,
            'overlapCount' => $overlapCount,
            'hasOverlap' => $overlapCount > 0,
            'overlapNames' => $overlapNames,
            'overlapZamanLabel' => $overlapZamanLabel,
            'count' => $overlapCount,
            'has' => $overlapCount > 0,
            'plannedCount' => $plannedCount,
            'hasPlanned' => $plannedCount > 0,
            'isToday' => $isToday,
            'tarih' => $ymd,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Çevrimdışı izlem taslağını senkronize et (JSON POST, oturum gerekli).
     */
    public function syncDraft(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'auth'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!OperationalSettings::fieldVisitOfflineDraftEnabled()) {
            http_response_code(503);
            echo json_encode(['ok' => false, 'error' => 'disabled'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'method'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw !== false ? $raw : '', true);
        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'json'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $token = (string) ($payload['_csrf'] ?? $payload['csrf_token'] ?? '');
        if (!CsrfHelper::validate($token !== '' ? $token : null)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'csrf'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $form = is_array($payload['form'] ?? null) ? $payload['form'] : $payload;
        $data = $this->normalizeVisitFormPayload($form);
        $tcRedirect = trim((string) ($data['hastatckimlik'] ?? ''));
        if ($tcRedirect === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'tc'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $patientRow = (new Patient())->findByTc($tcRedirect);
        if (!$patientRow || empty($patientRow->id)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'patient'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        PatientAccessHelper::requirePatientAccess((int) $patientRow->id, $patientRow);
        $this->requireAktifPatientForVisit($patientRow);

        if (!isset($data['yapilan']) || $data['yapilan'] === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'yapilan'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $checkinErr = $this->validateVisitCheckinPayload($data);
        if ($checkinErr !== null) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'checkin', 'message' => $checkinErr], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $izlem = new Visit();
        $data['kurum_id'] = (int) ($patientRow->kurum_id ?? \App\Helpers\TenantContext::assignKurumIdForStore());
        if (!$izlem->save($data)) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'save'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        AuditLogHelper::visitCreate($izlem, $patientRow);
        $this->logVisitCheckinGeofence($izlem, $patientRow);
        $this->maybeQueueUsbsVisitNotification((int) $izlem->id);

        echo json_encode([
            'ok' => true,
            'visit_id' => (int) $izlem->id,
            'redirect' => esh_url('Visit', 'history', ['tc' => $tcRedirect]),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function edit() {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id < 1) {
            $_SESSION['error'] = 'Geçersiz izlem kaydı.';
            header('Location: ' . esh_url('Visit', 'index'));
            exit;
        }

        $visit = new Visit();
        if (!$visit->load($id)) {
            $_SESSION['error'] = 'İzlem kaydı bulunamadı.';
            header('Location: ' . esh_url('Visit', 'index'));
            exit;
        }

        $tc = trim((string) ($visit->hastatckimlik ?? ''));
        if ($tc === '') {
            $_SESSION['error'] = 'Kayıtta hasta TC bilgisi yok.';
            header('Location: ' . esh_url('Visit', 'index'));
            exit;
        }

        $patient = (new Patient())->findByTc($tc);
        if (!$patient || empty($patient->id)) {
            $_SESSION['error'] = 'İzleme bağlı hasta bulunamadı.';
            header('Location: ' . esh_url('Visit', 'index'));
            exit;
        }
        PatientAccessHelper::requirePatientAccess((int) $patient->id, $patient);

        $islemler = (new Islem())->getList();
        $personel = (new User())->getList();

        $preYapilan = [];
        if (!empty($visit->yapilan)) {
            $preYapilan = array_filter(array_map('intval', explode(',', str_replace(' ', '', (string) $visit->yapilan))));
        }

        $selPersonel = [];
        if (!empty($visit->izlemiyapan)) {
            $selPersonel = array_filter(array_map('intval', explode(',', str_replace(' ', '', (string) $visit->izlemiyapan))));
        }

        $list = [];
        $list['islem'] = \App\Helpers\FormHelper::selectList(
            $islemler,
            'yapilan[]',
            'multiple="multiple" size="6" required',
            'id',
            'islemadi',
            $preYapilan
        );
        $list['personel'] = \App\Helpers\FormHelper::selectList(
            $personel,
            'personel_id[]',
            'multiple="multiple" size="5"',
            'id',
            'name',
            $selPersonel
        );

        $araclar = (new Arac())->getList();
        $selectedAracId = (int) ($visit->arac ?? 0);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'izlem/edit');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Hasta bazlı yapılmamış (bekleyen) izlem satırları — unified / izlem listesi linkleriyle uyumlu (?tc=).
     */
    /**
     * Yapılmayan izlemler — ortak GET durumu.
     *
     * @return array<string, mixed>
     */
    private function missedListRequestState(): array {
        $tc = isset($_GET['tc']) ? preg_replace('/\D/', '', trim((string) $_GET['tc'])) : '';
        $limit = isset($_GET['limit']) ? max(5, min(100, (int) $_GET['limit'])) : 50;
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $offset = ($page - 1) * $limit;

        return [
            'tc' => $tc,
            'limit' => $limit,
            'page' => $page,
            'offset' => $offset,
        ];
    }

    public function missed() {
        $st = $this->missedListRequestState();
        $tc = $st['tc'];
        if ($tc === '') {
            $_SESSION['error'] = 'Yapılmayan izlemler için TC kimlik numarası gerekli.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $limit = $st['limit'];
        $page = $st['page'];

        $missedRedirect = esh_url('Patient', 'unified', ['status' => 'active']);
        $missedPatient = $this->requirePatientAccessByTc($tc, $missedRedirect);

        $model = new Visit();
        $statusPending = '0';
        $total = $model->countPatientVisits($tc, $statusPending);
        $totalPages = $total > 0 ? (int) ceil($total / $limit) : 1;

        $patientIdForHeader = (int) ($missedPatient->id ?? 0);

        $missedRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Visit',
            'action' => 'missedRows',
            'tc' => $tc,
            'limit' => $limit,
            'page' => $page,
        ]);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'izlem/missed');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Yapılmayan izlemler tablo satırları (JSON HTML parçası).
     */
    public function missedRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $st = $this->missedListRequestState();
        $tc = $st['tc'];
        if ($tc === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'TC gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $missedRedirect = esh_url('Patient', 'unified', ['status' => 'active']);
        $this->requirePatientAccessByTc($tc, $missedRedirect);

        $model = new Visit();
        $visits = $model->getPatientVisits($tc, $st['limit'], $st['offset'], '0', 'i.izlemtarihi ASC');

        ob_start();
        include ROOT_PATH . '/views/site/izlem/partials/missed_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Yeni izlem kaydı (POST). Plan tamamlama için isteğe bağlı plan_id.
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Visit', 'index'));
            exit;
        }

        $planId = isset($_POST['plan_id']) ? (int) $_POST['plan_id'] : 0;
        $uhdsId = isset($_POST['uhds_id']) ? (int) $_POST['uhds_id'] : 0;
        $data = $this->normalizeVisitFormPayload($_POST);

        $tcRedirect = $data['hastatckimlik'] ?? '';
        $after = $tcRedirect !== ''
            ? esh_url('Visit', 'history', ['tc' => $tcRedirect])
            : esh_url('Visit', 'index');

        if ($tcRedirect === '') {
            $_SESSION['error'] = 'Hasta TC bilgisi eksik.';
            header('Location: ' . esh_url('Visit', 'index'));
            exit;
        }

        $patientRow = (new Patient())->findByTc($tcRedirect);
        if (!$patientRow || empty($patientRow->id)) {
            $_SESSION['error'] = 'Bu TC ile kayıtlı hasta bulunamadı.';
            header('Location: ' . esh_url('Visit', 'index'));
            exit;
        }
        PatientAccessHelper::requirePatientAccess((int) $patientRow->id, $patientRow);
        $this->requireAktifPatientForVisit($patientRow);

        if (!isset($data['yapilan']) || $data['yapilan'] === '') {
            $_SESSION['error'] = 'En az bir yapılan işlem seçmelisiniz.';
            $qParams = ['tc' => $tcRedirect];
            if ($planId > 0) {
                $qParams['plan_id'] = $planId;
            }
            $q = esh_url('Visit', 'create', $qParams);
            header('Location: ' . $q);
            exit;
        }

        $izlemTarihYmd = (string) ($data['izlemtarihi'] ?? '');
        if ($izlemTarihYmd !== '' && DateHelper::isYmdAfterToday($izlemTarihYmd)) {
            $_SESSION['error'] = 'İzlem tarihi bugünden ileri olamaz.';
            $qParams = ['tc' => $tcRedirect];
            if ($planId > 0) {
                $qParams['plan_id'] = $planId;
            }
            $q = esh_url('Visit', 'create', $qParams);
            header('Location: ' . $q);
            exit;
        }

        $zamanErr = $this->validateVisitZamanPayload($data);
        if ($zamanErr !== null) {
            $_SESSION['error'] = $zamanErr;
            $qParams = ['tc' => $tcRedirect];
            if ($planId > 0) {
                $qParams['plan_id'] = $planId;
            }
            header('Location: ' . esh_url('Visit', 'create', $qParams));
            exit;
        }

        $dupMsg = $this->duplicateVisitSameIslemMessage(
            $tcRedirect,
            $izlemTarihYmd,
            (string) ($data['yapilan'] ?? ''),
            0,
            $this->zamanFromVisitPayload($data)
        );
        if ($dupMsg !== null) {
            $_SESSION['error'] = $dupMsg;
            $qParams = ['tc' => $tcRedirect];
            if ($planId > 0) {
                $qParams['plan_id'] = $planId;
            }
            header('Location: ' . esh_url('Visit', 'create', $qParams));
            exit;
        }

        $checkinErr = $this->validateVisitCheckinPayload($data);
        if ($checkinErr !== null) {
            $_SESSION['error'] = $checkinErr;
            $qParams = ['tc' => $tcRedirect];
            if ($planId > 0) {
                $qParams['plan_id'] = $planId;
            }
            header('Location: ' . esh_url('Visit', 'create', $qParams));
            exit;
        }

        $izlem = new Visit();
        $data['kurum_id'] = (int) ($patientRow->kurum_id ?? \App\Helpers\TenantContext::assignKurumIdForStore());
        if ($izlem->save($data)) {
            AuditLogHelper::visitCreate($izlem, $patientRow);
            $this->logVisitCheckinGeofence($izlem, $patientRow);
            if ($uhdsId > 0) {
                $uhdsLink = new \App\Models\Uhds();
                if ($uhdsLink->load($uhdsId) && (string) $uhdsLink->hastatckimlik === (string) $tcRedirect) {
                    $uhdsLink->completeTelehealthSession($uhdsId, null, 1, (int) $izlem->id);
                }
            }
            $this->syncPatientSondaFromVisitYapilan(
                (string) ($data['hastatckimlik'] ?? ''),
                (string) ($data['yapilan'] ?? ''),
                (string) ($data['izlemtarihi'] ?? ''),
                (int) ($data['yapildimi'] ?? 0)
            );
            $planMarkedOk = false;
            if ($planId > 0) {
                $plan = new PlannedVisit();
                if ($plan->load($planId) && (string) $plan->hastatckimlik === (string) $tcRedirect) {
                    $plan->bind(['durum' => 1]);
                    $planMarkedOk = (bool) $plan->store();
                }
            }
            $yapCsv = (string) ($data['yapilan'] ?? '');
            $vid = (int) $izlem->id;
            $this->maybeQueueUsbsVisitNotification($vid);
            if ($vid > 0 && VisitIslemHelper::yapilanCsvContainsIslem($yapCsv, VisitIslemHelper::konsultasyonIslemId())) {
                $_SESSION['success'] = 'İzlem kaydedildi.'
                    . ($planMarkedOk ? ' Planlı izlem «Yapıldı» olarak işaretlendi.' : '')
                    . ' Konsültasyon için EK-3 bilgilerini girin.';
                header('Location: ' . esh_url('Visit', 'ek3Consult', ['id' => $vid, 'tc' => $tcRedirect]));
                exit;
            }
            $_SESSION['success'] = $planMarkedOk
                ? 'Ziyaret kaydı başarıyla oluşturuldu; bağlı planlı izlem «Yapıldı» olarak işaretlendi.'
                : 'Ziyaret kaydı başarıyla oluşturuldu.';
        } else {
            $_SESSION['error'] = 'Ziyaret kaydedilirken bir hata oluştu.';
        }

        header('Location: ' . $after);
        exit;
    }

    /**
     * Mevcut izlem satırını günceller (POST).
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Visit', 'index'));
            exit;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id < 1) {
            $_SESSION['error'] = 'Geçersiz izlem kaydı.';
            header('Location: ' . esh_url('Visit', 'index'));
            exit;
        }

        $izlem = new Visit();
        if (!$izlem->load($id)) {
            $_SESSION['error'] = 'İzlem kaydı bulunamadı.';
            header('Location: ' . esh_url('Visit', 'index'));
            exit;
        }

        $data = $this->normalizeVisitFormPayload($_POST);
        $data = $this->preserveVisitEsysRefs($data, $izlem);
        $data = $this->preserveVisitUsbsRefs($data, $izlem);
        $postedTc = $data['hastatckimlik'] ?? '';
        if ($postedTc === '' || $postedTc !== (string) $izlem->hastatckimlik) {
            $_SESSION['error'] = 'Hasta TC bilgisi kayıt ile uyuşmuyor.';
            header('Location: ' . esh_url('Visit', 'edit', ['id' => $id]));
            exit;
        }
        $this->requirePatientAccessByTc($postedTc, esh_url('Visit', 'index'));

        if (!isset($data['yapilan']) || $data['yapilan'] === '') {
            $_SESSION['error'] = 'En az bir işlem seçmelisiniz.';
            header('Location: ' . esh_url('Visit', 'edit', ['id' => $id]));
            exit;
        }

        $zamanErr = $this->validateVisitZamanPayload($data, (int) ($izlem->zaman ?? 0));
        if ($zamanErr !== null) {
            $_SESSION['error'] = $zamanErr;
            header('Location: ' . esh_url('Visit', 'edit', ['id' => $id]));
            exit;
        }

        $dupMsg = $this->duplicateVisitSameIslemMessage(
            $postedTc,
            (string) ($data['izlemtarihi'] ?? ''),
            (string) ($data['yapilan'] ?? ''),
            $id,
            $this->zamanFromVisitPayload($data, (int) ($izlem->zaman ?? 0))
        );
        if ($dupMsg !== null) {
            $_SESSION['error'] = $dupMsg;
            header('Location: ' . esh_url('Visit', 'edit', ['id' => $id]));
            exit;
        }

        $data = $this->preserveVisitCheckinIfEmpty($data, $izlem);
        $checkinErr = $this->validateVisitCheckinPayload($data, $izlem);
        if ($checkinErr !== null) {
            $_SESSION['error'] = $checkinErr;
            header('Location: ' . esh_url('Visit', 'edit', ['id' => $id]));
            exit;
        }

        $patientRow = (new Patient())->findByTc(preg_replace('/\D/', '', $postedTc));
        $data['kurum_id'] = (int) ($patientRow->kurum_id ?? $izlem->kurum_id ?? \App\Helpers\TenantContext::assignKurumIdForStore());

        $izlem->bind($data);
        $yapCsv = (string) ($data['yapilan'] ?? '');
        if ($izlem->store()) {
            AuditLogHelper::visitUpdate($izlem);
            if ($patientRow) {
                $this->logVisitCheckinGeofence($izlem, $patientRow);
            }
            $this->maybeQueueUsbsVisitNotification($id);
            $this->syncPatientSondaFromVisitYapilan(
                (string) ($data['hastatckimlik'] ?? ''),
                $yapCsv,
                (string) ($data['izlemtarihi'] ?? ''),
                (int) ($data['yapildimi'] ?? 0)
            );
            if (VisitIslemHelper::yapilanCsvContainsIslem($yapCsv, VisitIslemHelper::konsultasyonIslemId())) {
                $_SESSION['success'] = 'İzlem güncellendi. Konsültasyon için EK-3 bilgilerini girin.';
                header('Location: ' . esh_url('Visit', 'ek3Consult', ['id' => $id, 'tc' => $postedTc]));
                exit;
            }
            $_SESSION['success'] = 'İzlem kaydı güncellendi.';
        } else {
            $_SESSION['error'] = 'İzlem güncellenirken bir hata oluştu.';
        }

        header('Location: ' . esh_url('Visit', 'history', ['tc' => $postedTc]));
        exit;
    }

    /**
     * Konsültasyon seçilmiş izlem sonrası: branş / başvuru amacı / açıklama (EK-3 öncesi).
     */
    public function ek3Consult() {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $tc = isset($_GET['tc']) ? preg_replace('/\D/', '', (string) $_GET['tc']) : '';
        if ($id < 1 || !ValidationHelper::isTcLength11($tc)) {
            $_SESSION['error'] = 'Geçersiz istek.';
            header('Location: ' . esh_url('Visit', 'index'));
            exit;
        }

        $ek3Redirect = esh_url('Visit', 'history', ['tc' => $tc]);
        $patient = $this->requirePatientAccessByTc($tc, $ek3Redirect);

        $visit = new Visit();
        if (!$visit->load($id) || (string) $visit->hastatckimlik !== $tc) {
            $_SESSION['error'] = 'İzlem kaydı bulunamadı.';
            header('Location: ' . $ek3Redirect);
            exit;
        }

        if (!VisitIslemHelper::yapilanCsvContainsIslem((string) ($visit->yapilan ?? ''), VisitIslemHelper::konsultasyonIslemId())) {
            $_SESSION['error'] = 'Bu izlemde konsültasyon işlemi seçili değil.';
            header('Location: ' . $ek3Redirect);
            exit;
        }

        $branslar = (new Brans())->getList();
        $isteklerList = (new Istek())->getList();

        $selBransIstek = KonsBransIstekHelper::resolveMap(
            (string) ($visit->kons_brans_istek ?? ''),
            (string) ($visit->brans ?? ''),
            (string) ($visit->kons_istekler ?? '')
        );
        $selBrans = KonsBransIstekHelper::bransIds($selBransIstek);
        if ($selBrans === [] && !empty($visit->brans)) {
            $selBrans = array_values(array_unique(array_filter(array_map('intval', explode(',', str_replace(' ', '', (string) $visit->brans))))));
        }

        $patientIdForHeader = (int) ($patient->id ?? 0);
        $patientLabel = trim((string) ($patient->isim ?? '') . ' ' . (string) ($patient->soyisim ?? ''));
        $histErkek = ($patient->cinsiyet ?? '') === 'E' || ($patient->cinsiyet ?? '') === '1';
        $histAktif = \App\Models\Patient::isAktif($patient->pasif ?? null);
        $tcQ = rawurlencode($tc);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'izlem/ek3_consult');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Konsültasyon detay kaydı + EK-3 PDF ekranına yönlendirme.
     */
    public function ek3SavePrint() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Visit', 'index'));
            exit;
        }

        $izlemId = isset($_POST['izlem_id']) ? (int) $_POST['izlem_id'] : 0;
        $hastaId = isset($_POST['hasta_id']) ? (int) $_POST['hasta_id'] : 0;
        $tc = isset($_POST['tc']) ? preg_replace('/\D/', '', (string) $_POST['tc']) : '';

        if ($izlemId < 1 || $hastaId < 1 || !ValidationHelper::isTcLength11($tc)) {
            $_SESSION['error'] = 'Eksik bilgi.';
            header('Location: ' . esh_url('Visit', 'index'));
            exit;
        }

        $ek3Redirect = esh_url('Visit', 'history', ['tc' => $tc]);
        $p = $this->requirePatientAccessByTc($tc, $ek3Redirect);
        if ((int) $p->id !== $hastaId) {
            $_SESSION['error'] = 'Hasta bilgisi uyuşmuyor.';
            header('Location: ' . esh_url('Visit', 'ek3Consult', ['id' => $izlemId, 'tc' => $tc]));
            exit;
        }

        $visit = new Visit();
        if (!$visit->load($izlemId) || (string) $visit->hastatckimlik !== $tc) {
            $_SESSION['error'] = 'İzlem kaydı bulunamadı.';
            header('Location: ' . $ek3Redirect);
            exit;
        }

        $bransArr = (isset($_POST['brans']) && is_array($_POST['brans'])) ? array_map('intval', $_POST['brans']) : [];
        $istekByBrans = (isset($_POST['istek']) && is_array($_POST['istek'])) ? $_POST['istek'] : [];
        $bransIstekMap = KonsBransIstekHelper::fromPost($bransArr, $istekByBrans);

        if (!KonsBransIstekHelper::validateSelectedBranchesHaveIstek($bransArr, $bransIstekMap)) {
            $_SESSION['error'] = 'Her seçili branş için en az bir başvuru amacı işaretleyin.';
            header('Location: ' . esh_url('Visit', 'ek3Consult', ['id' => $izlemId, 'tc' => $tc]));
            exit;
        }

        $bransCsv = KonsBransIstekHelper::bransCsv($bransIstekMap);
        $istekCsv = KonsBransIstekHelper::istekCsv($bransIstekMap);
        $konsBransIstekJson = KonsBransIstekHelper::encode($bransIstekMap);
        $aciklama = isset($_POST['aciklama']) ? trim((string) $_POST['aciklama']) : '';

        $visit->bind([
            'brans' => $bransCsv,
            'kons_istekler' => $istekCsv,
            'kons_brans_istek' => $konsBransIstekJson !== '' ? $konsBransIstekJson : null,
            'aciklama' => $aciklama,
        ]);

        if (!$visit->store()) {
            $_SESSION['error'] = 'Konsültasyon bilgileri kaydedilemedi.';
            header('Location: ' . esh_url('Visit', 'ek3Consult', ['id' => $izlemId, 'tc' => $tc]));
            exit;
        }

        $_SESSION['success'] = 'EK-3 formu için bilgiler kaydedildi.';
        header('Location: ' . esh_url('Visit', 'history', ['tc' => $tc, 'ek3_open' => $izlemId]));
        exit;
    }

    /**
     * EK-3 PDF önizleme / yazdır (pdfMake).
     */
    public function ek3Document() {
        $visitId = isset($_GET['visit_id']) ? (int) $_GET['visit_id'] : 0;
        if ($visitId < 1) {
            $_SESSION['error'] = 'Geçersiz izlem.';
            header('Location: ' . esh_url('Visit', 'index'));
            exit;
        }

        $ctx = $this->resolveEk3DocumentContext($visitId);
        if ($ctx === null) {
            return;
        }

        $visit = $ctx['visit'];
        $hasta = $ctx['hasta'];
        $bransIstekMap = $ctx['bransIstekMap'];
        $tc = $ctx['tc'];

        $ek3Meta = isset($_GET['meta']) && (string) $_GET['meta'] !== '' && (string) $_GET['meta'] !== '0';
        if ($ek3Meta) {
            $tabs = $this->buildEk3TabList($visitId, $bransIstekMap);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['tabs' => $tabs], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $bransIdFilter = isset($_GET['brans_id']) ? (int) $_GET['brans_id'] : 0;
        $bransCount = count($bransIstekMap);
        $ek3Embed = isset($_GET['embed']) && (string) $_GET['embed'] !== '' && (string) $_GET['embed'] !== '0';

        if ($bransIdFilter > 0) {
            if (!isset($bransIstekMap[$bransIdFilter])) {
                if ($ek3Embed) {
                    http_response_code(400);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'Geçersiz branş.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $_SESSION['error'] = 'Geçersiz branş seçimi.';
                header('Location: ' . esh_url('Visit', 'history', ['tc' => $tc]));
                exit;
            }
            $bransIstekMap = [$bransIdFilter => $bransIstekMap[$bransIdFilter]];
            $bransCount = 1;
        } elseif ($bransCount > 1 && $ek3Embed) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Çoklu branş için brans_id gerekli.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pasifUyari = (string) ($hasta->pasif ?? '') !== '0';
        $ek3Tabs = [];

        if ($bransCount > 1 && !$ek3Embed && $bransIdFilter < 1) {
            $ek3Tabs = $this->buildEk3TabList($visitId, $ctx['bransIstekMap']);
            include ThemeViewHelper::resolvePartial('header');
            include ThemeViewHelper::resolveAreaView('site', 'izlem/ek3_document');
            include ThemeViewHelper::resolvePartial('footer');
            exit;
        }

        $dd = $this->buildEk3PdfDefinition($hasta, $bransIstekMap, $ctx['hastaliklarStr'], $ctx['muracaatArg']);
        if ($dd === null) {
            $_SESSION['error'] = 'EK-3 formu oluşturulamadı: geçerli branş/istek eşlemesi yok.';
            header('Location: ' . esh_url('Visit', 'ek3Consult', ['id' => $visitId, 'tc' => $tc]));
            exit;
        }

        $ek3PdfJson = json_encode($dd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($ek3PdfJson === false) {
            $ek3PdfJson = '{}';
        }

        if ($ek3Embed) {
            include ROOT_PATH . '/views/site/izlem/ek3_document_embed.php';
            exit;
        }

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'izlem/ek3_document');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * @return array{visit:Visit,hasta:object,bransIstekMap:array<int,int[]>,hastaliklarStr:string,muracaatArg:?string,tc:string}|null
     */
    private function resolveEk3DocumentContext(int $visitId): ?array {
        $visit = new Visit();
        if (!$visit->load($visitId)) {
            $_SESSION['error'] = 'İzlem kaydı bulunamadı.';
            header('Location: ' . esh_url('Visit', 'index'));
            exit;
        }

        $tc = preg_replace('/\D/', '', (string) $visit->hastatckimlik);
        if (!ValidationHelper::isTcLength11($tc)) {
            $_SESSION['error'] = 'Geçersiz izlem kaydı.';
            header('Location: ' . esh_url('Visit', 'index'));
            exit;
        }

        $docRedirect = esh_url('Visit', 'history', ['tc' => $tc]);
        $patientRow = $this->requirePatientAccessByTc($tc, $docRedirect);

        if (!VisitIslemHelper::yapilanCsvContainsIslem((string) ($visit->yapilan ?? ''), VisitIslemHelper::konsultasyonIslemId())) {
            $_SESSION['error'] = 'Bu izlem konsültasyon içermiyor.';
            header('Location: ' . $docRedirect);
            exit;
        }

        $patientModel = new Patient();
        $hasta = $patientModel->loadForEk3((int) $patientRow->id);
        if (!$hasta) {
            $_SESSION['error'] = 'Hasta verisi yüklenemedi.';
            header('Location: ' . esh_url('Visit', 'history', ['tc' => $tc]));
            exit;
        }

        $bransIstekMap = KonsBransIstekHelper::resolveMap(
            (string) ($visit->kons_brans_istek ?? ''),
            (string) ($visit->brans ?? ''),
            (string) ($visit->kons_istekler ?? '')
        );
        if ($bransIstekMap === []) {
            $_SESSION['error'] = 'EK-3 için branş ve başvuru amacı tanımlı değil. Önce konsültasyon bilgilerini kaydedin.';
            header('Location: ' . esh_url('Visit', 'ek3Consult', ['id' => $visitId, 'tc' => $tc]));
            exit;
        }

        $muracaatTr = DateHelper::toTrOrEmpty((string) ($visit->izlemtarihi ?? ''));
        if ($muracaatTr !== '') {
            $muracaatTr = str_replace('-', '.', $muracaatTr);
        }

        return [
            'visit' => $visit,
            'hasta' => $hasta,
            'bransIstekMap' => $bransIstekMap,
            'hastaliklarStr' => $patientModel->hastalikEtiketleriFromCsv((string) ($hasta->hastaliklar ?? '')),
            'muracaatArg' => $muracaatTr !== '' ? $muracaatTr : null,
            'tc' => $tc,
        ];
    }

    /**
     * @param array<int, int[]> $bransIstekMap
     * @return list<array{bransId:int,bransName:string,docUrl:string}>
     */
    private function buildEk3TabList(int $visitId, array $bransIstekMap): array {
        $bransModel = new Brans();
        $tabs = [];
        foreach ($bransIstekMap as $bransId => $istekIds) {
            $bransId = (int) $bransId;
            if ($bransId < 1 || empty($istekIds)) {
                continue;
            }
            $bransName = '';
            if ($bransModel->load($bransId)) {
                $bransName = trim((string) $bransModel->bransadi);
            }
            if ($bransName === '') {
                $bransName = 'Branş #' . $bransId;
            }
            $tabs[] = [
                'bransId' => $bransId,
                'bransName' => $bransName,
                'docUrl' => esh_url('Visit', 'ek3Document', [
                    'visit_id' => $visitId,
                    'embed' => 1,
                    'brans_id' => $bransId,
                ]),
            ];
        }

        return $tabs;
    }

    /**
     * @param array<int, int[]> $bransIstekMap
     */
    private function buildEk3PdfDefinition(object $hasta, array $bransIstekMap, string $hastaliklarStr, ?string $muracaatArg): ?array {
        $istekModel = new Istek();
        foreach ($bransIstekMap as $bransId => $istekIds) {
            $bransId = (int) $bransId;
            if ($bransId < 1 || empty($istekIds)) {
                continue;
            }
            $isteklerMetni = $istekModel->namesForIds($istekIds);

            return Ek3PdfHelper::buildDefinition(
                $hasta,
                $hastaliklarStr,
                $isteklerMetni,
                $muracaatArg
            );
        }

        return null;
    }

    /**
     * @return int[]
     */
    private function parseIslemIdsFromQuery(): array
    {
        if (!isset($_GET['yapilan'])) {
            return [];
        }
        if (is_array($_GET['yapilan'])) {
            return array_values(array_unique(array_filter(array_map('intval', $_GET['yapilan']))));
        }

        return VisitIslemHelper::yapilanCsvToIntIds((string) $_GET['yapilan']);
    }

    private function parseZamanFromQuery(): ?int
    {
        if (!isset($_GET['zaman']) || trim((string) $_GET['zaman']) === '') {
            return null;
        }
        $z = ZamanDilimiHelper::normalize($_GET['zaman']);

        return ZamanDilimiHelper::isValid($z) ? $z : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function zamanFromVisitPayload(array $data, int $fallbackZaman = 0): ?int
    {
        if (isset($data['zaman']) && $data['zaman'] !== '' && $data['zaman'] !== null) {
            $z = ZamanDilimiHelper::clamp($data['zaman']);

            return ZamanDilimiHelper::isValid($z) ? $z : null;
        }
        if ($fallbackZaman > 0 && ZamanDilimiHelper::isValid($fallbackZaman)) {
            return ZamanDilimiHelper::normalize($fallbackZaman);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateVisitZamanPayload(array $data, int $existingZaman = 0): ?string
    {
        if (!isset($data['zaman']) || $data['zaman'] === '' || $data['zaman'] === null) {
            return null;
        }
        $newZ = ZamanDilimiHelper::normalize($data['zaman']);
        $allowInactive = $existingZaman > 0
            && ZamanDilimiHelper::normalize($existingZaman) === $newZ;
        $valid = ZamanDilimiHelper::validateForSave($data['zaman'], $allowInactive);

        return $valid === true ? null : (string) $valid;
    }

    /**
     * Aynı hasta + gün + zaman dilimi + işlem çakışması varsa hata metni.
     */
    private function duplicateVisitSameIslemMessage(
        string $tc,
        string $izlemTarihYmd,
        string $yapilanCsv,
        int $excludeVisitId = 0,
        ?int $zaman = null
    ): ?string {
        $tc = trim($tc);
        $islemIds = VisitIslemHelper::yapilanCsvToIntIds($yapilanCsv);
        if (
            $tc === ''
            || $izlemTarihYmd === ''
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $izlemTarihYmd)
            || $islemIds === []
            || $zaman === null
            || !ZamanDilimiHelper::isValid($zaman)
        ) {
            return null;
        }

        $overlapIds = (new Visit())->overlappingIslemIdsForPatientOnDate(
            $tc,
            $izlemTarihYmd,
            $islemIds,
            $zaman,
            $excludeVisitId
        );
        if ($overlapIds === []) {
            return null;
        }

        $names = (new Islem())->namesForIds($overlapIds);
        $tr = DateHelper::toTrOrEmpty($izlemTarihYmd);
        $tarihPart = $tr !== '' ? $tr . ' tarihinde ' : 'seçilen tarihte ';
        $islemPart = $names !== '' ? ' («' . $names . '»)' : '';
        $zamanPart = ZamanDilimiHelper::label($zaman) . ' diliminde ';

        return 'Bu hasta için ' . $tarihPart . $zamanPart . 'aynı işlem' . $islemPart
            . ' zaten kayıtlı. Aynı gün ve aynı zaman diliminde aynı işlemden tekrar izlem girilemez.';
    }

    /**
     * @return array<string, string|int>
     */
    private function normalizeVisitFormPayload(array $raw): array {
        $data = $raw;
        if (isset($data['hastatckimlik'])) {
            $data['hastatckimlik'] = trim((string) $data['hastatckimlik']);
        }

        if (isset($data['izlemtarihi'])) {
            $rawIz = trim((string) $data['izlemtarihi']);
            if ($rawIz !== '') {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawIz)) {
                    $data['izlemtarihi'] = $rawIz;
                } else {
                    $conv = DateHelper::trDateToYmd($rawIz);
                    $data['izlemtarihi'] = $conv !== null ? $conv : date('Y-m-d');
                }
            }
        }

        if (!empty($data['yapilan']) && is_array($data['yapilan'])) {
            $ids = array_filter(array_map('intval', $data['yapilan']));
            $data['yapilan'] = $ids ? implode(',', $ids) : '';
        }

        $pid = $data['personel_id'] ?? $data['izlemiyapan'] ?? null;
        if (is_array($pid)) {
            $ids = array_filter(array_map('intval', $pid));
            $data['izlemiyapan'] = $ids ? implode(',', $ids) : (string) (int) ($_SESSION['user_id'] ?? 0);
        } elseif ($pid !== null && $pid !== '') {
            $data['izlemiyapan'] = (string) (int) $pid;
        } else {
            $data['izlemiyapan'] = (string) (int) ($_SESSION['user_id'] ?? 0);
        }

        if (empty($data['izlemtarihi'])) {
            $data['izlemtarihi'] = date('Y-m-d');
        }

        if (!isset($data['yapildimi']) || $data['yapildimi'] === '') {
            $data['yapildimi'] = 1;
        }
        $data['yapildimi'] = (int) $data['yapildimi'] ? 1 : 0;

        if (isset($data['zaman']) && $data['zaman'] !== '') {
            $data['zaman'] = \App\Helpers\ZamanDilimiHelper::clamp($data['zaman']);
        }

        $yapildi = (int) $data['yapildimi'] ? 1 : 0;
        if ($yapildi === 0) {
            $k = IzlemYapilmamaNedenHelper::normalizeKey($data['yapilmama_neden'] ?? null);
            $data['neden'] = IzlemYapilmamaNedenHelper::compose($k);
        } else {
            $data['neden'] = '';
        }

        $arSel = $data['arac'] ?? null;
        if ($arSel === null || $arSel === '') {
            $data['arac'] = null;
        } else {
            $aid = (int) $arSel;
            $data['arac'] = $aid > 0 ? $aid : null;
        }

        $data = $this->normalizeVisitCheckinPayload($data);

        $allowed = ['hastatckimlik', 'izlemtarihi', 'yapilan', 'yapildimi', 'neden', 'izlemiyapan', 'zaman', 'aciklama', 'arac', 'checkin_lat', 'checkin_lon', 'checkin_at', 'checkin_accuracy'];
        if (AuthHelper::sessionIsAdmin() && EsysComplianceHelper::enabled()) {
            $data = array_merge($data, EsysComplianceHelper::pickVisitRefs($data));
            $allowed[] = 'esys_izlem_ref';
            $allowed[] = 'esys_konsultasyon_ref';
        }
        if (AuthHelper::sessionIsAdmin() && UsbsComplianceHelper::enabled()) {
            $data = array_merge($data, UsbsComplianceHelper::pickVisitRefs($data));
            $allowed[] = 'usbs_bildirim_ref';
            $allowed[] = 'erecete_ref';
            $allowed[] = 'usbs_bildirim_durum';
        }

        return array_intersect_key($data, array_flip($allowed));
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function preserveVisitEsysRefs(array $data, object $existing): array
    {
        if (!EsysComplianceHelper::enabled()) {
            return $data;
        }
        if (!AuthHelper::sessionIsAdmin()) {
            $data['esys_izlem_ref'] = $existing->esys_izlem_ref ?? null;
            $data['esys_konsultasyon_ref'] = $existing->esys_konsultasyon_ref ?? null;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function preserveVisitUsbsRefs(array $data, object $existing): array
    {
        if (!UsbsComplianceHelper::enabled()) {
            return $data;
        }
        if (!AuthHelper::sessionIsAdmin()) {
            $data['usbs_bildirim_ref'] = $existing->usbs_bildirim_ref ?? null;
            $data['erecete_ref'] = $existing->erecete_ref ?? null;
            $data['usbs_bildirim_durum'] = $existing->usbs_bildirim_durum ?? null;
            $data['usbs_bildirim_at'] = $existing->usbs_bildirim_at ?? null;
        }

        return $data;
    }

    private function maybeQueueUsbsVisitNotification(int $visitId): void
    {
        if ($visitId < 1) {
            return;
        }
        try {
            (new UsbsBridgeService())->queueVisitNotification($visitId);
        } catch (\Throwable $e) {
            // Köprü hazırlığı — izlem kaydı başarısız olmamalı.
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeVisitCheckinPayload(array $data): array
    {
        $latRaw = $data['checkin_lat'] ?? null;
        $lonRaw = $data['checkin_lon'] ?? null;
        $lat = is_numeric($latRaw) ? (float) $latRaw : null;
        $lon = is_numeric($lonRaw) ? (float) $lonRaw : null;

        if ($lat === null || $lon === null || $lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            $data['checkin_lat'] = null;
            $data['checkin_lon'] = null;
            $data['checkin_at'] = null;
            $data['checkin_accuracy'] = null;

            return $data;
        }

        $data['checkin_lat'] = round($lat, 7);
        $data['checkin_lon'] = round($lon, 7);

        $accRaw = $data['checkin_accuracy'] ?? null;
        if (is_numeric($accRaw)) {
            $acc = max(0.0, (float) $accRaw);
            $data['checkin_accuracy'] = $acc > 0 ? round($acc, 1) : null;
        } else {
            $data['checkin_accuracy'] = null;
        }

        $atRaw = trim((string) ($data['checkin_at'] ?? ''));
        if ($atRaw !== '') {
            $ts = strtotime($atRaw);
            $data['checkin_at'] = $ts !== false ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
        } else {
            $data['checkin_at'] = date('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateVisitCheckinPayload(array $data, ?object $existing = null): ?string
    {
        $mode = OperationalSettings::fieldVisitCheckinMode();
        $yapildi = (int) ($data['yapildimi'] ?? 0) === 1;
        $hasCheckin = $this->visitPayloadHasCheckin($data)
            || ($existing !== null && $this->visitRowHasCheckin($existing));

        if ($mode === 'required_completed' && $yapildi && !$hasCheckin) {
            return 'Yapıldı olarak kaydedilen izlemler için saha konumu (GPS) zorunludur. Tarayıcı konum iznini açıp tekrar deneyin.';
        }

        $maxAcc = OperationalSettings::fieldVisitMinGpsAccuracyM();
        if ($mode === 'required_completed' && $yapildi && $hasCheckin && $maxAcc > 0) {
            $acc = isset($data['checkin_accuracy']) && is_numeric($data['checkin_accuracy'])
                ? (float) $data['checkin_accuracy'] : null;
            if (!FieldVisitGeoHelper::isAccuracyAcceptable($acc, $maxAcc)) {
                return 'GPS doğruluğu yetersiz (±' . (int) round($acc ?? 0) . ' m). Daha iyi sinyal alıp tekrar deneyin.';
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function preserveVisitCheckinIfEmpty(array $data, object $existing): array
    {
        if ($this->visitPayloadHasCheckin($data)) {
            return $data;
        }
        if (!$this->visitRowHasCheckin($existing)) {
            return $data;
        }
        $data['checkin_lat'] = round((float) $existing->checkin_lat, 7);
        $data['checkin_lon'] = round((float) $existing->checkin_lon, 7);
        $data['checkin_at'] = $existing->checkin_at ?? null;
        if (isset($existing->checkin_accuracy) && is_numeric($existing->checkin_accuracy)) {
            $data['checkin_accuracy'] = round((float) $existing->checkin_accuracy, 1);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function visitPayloadHasCheckin(array $data): bool
    {
        $lat = $data['checkin_lat'] ?? null;
        $lon = $data['checkin_lon'] ?? null;
        if (!is_numeric($lat) || !is_numeric($lon)) {
            return false;
        }
        $latF = (float) $lat;
        $lonF = (float) $lon;

        return $latF >= -90 && $latF <= 90 && $lonF >= -180 && $lonF <= 180;
    }

    private function visitRowHasCheckin(object $row): bool
    {
        return $this->visitPayloadHasCheckin([
            'checkin_lat' => $row->checkin_lat ?? null,
            'checkin_lon' => $row->checkin_lon ?? null,
        ]);
    }

    private function logVisitCheckinGeofence(object $visit, object $patient): void
    {
        if (!$this->visitRowHasCheckin($visit)) {
            return;
        }
        $radius = OperationalSettings::fieldVisitGeofenceRadiusM();
        if ($radius <= 0) {
            return;
        }
        $patientCoords = FieldVisitGeoHelper::patientCoords($patient);
        $status = FieldVisitGeoHelper::geofenceStatus(
            is_numeric($visit->checkin_lat ?? null) ? (float) $visit->checkin_lat : null,
            is_numeric($visit->checkin_lon ?? null) ? (float) $visit->checkin_lon : null,
            $patientCoords,
            $radius
        );
        if (!$status['outside']) {
            return;
        }
        AuditLogHelper::visitCheckinGeofence($visit, $patient, $status);
    }

    /**
     * İzlemde seçilen mesane sonda işlemlerine göre hasta `sonda` / `sondatarihi` güncellenir.
     * Yalnız yapılmış izlem (yapildimi=1); id listeleri config’te (ESH_VISIT_SONDA_*).
     */
    private function syncPatientSondaFromVisitYapilan(string $hastaTc, string $yapilanCsv, string $izlemtarihiYmd, int $yapildimi): void {
        if ($yapildimi !== 1) {
            return;
        }
        $dec = VisitIslemHelper::mesaneSondaDecisionFromYapilan($yapilanCsv);
        if ($dec === null) {
            return;
        }
        $tc = preg_replace('/\D+/', '', $hastaTc);
        if (!ValidationHelper::isTcLength11($tc)) {
            return;
        }
        $izYmd = trim($izlemtarihiYmd);
        if ($izYmd === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $izYmd)) {
            return;
        }
        $row = (new Patient())->findByTc($tc);
        if (!$row || empty($row->id)) {
            return;
        }
        $patient = new Patient();
        if (!$patient->load((int) $row->id)) {
            return;
        }
        if ($dec === 'off') {
            $patient->set('sonda', 0);
            $patient->set('sondatarihi', null);
        } else {
            $patient->set('sonda', 1);
            $patient->set('sondatarihi', $izYmd);
        }
        $patient->store();
    }

    private function requireAktifPatientForVisit(object $patient): void {
        if (Patient::isAktif($patient->pasif ?? null)) {
            return;
        }
        $_SESSION['error'] = 'Yalnızca aktif (pasif=0) hastalara izlem girilebilir.';
        $id = (int) ($patient->id ?? 0);
        header('Location: ' . ($id > 0 ? esh_url('Patient', 'view', ['id' => $id]) : esh_url('Patient', 'unified', ['status' => 'active'])));
        exit;
    }

    private function requirePatientAccessByTc(string $tc, string $redirectUrl = ''): object
    {
        $tc = preg_replace('/\D/', '', trim($tc));
        if ($tc === '' || !ValidationHelper::isTcLength11($tc)) {
            $_SESSION['error'] = 'Geçersiz hasta TC bilgisi.';
            header('Location: ' . ($redirectUrl !== '' ? $redirectUrl : esh_url('Visit', 'index')));
            exit;
        }

        $patient = (new Patient())->findByTc($tc);
        if (!$patient || empty($patient->id)) {
            $_SESSION['error'] = 'Bu TC ile kayıtlı hasta bulunamadı.';
            header('Location: ' . ($redirectUrl !== '' ? $redirectUrl : esh_url('Visit', 'index')));
            exit;
        }

        return PatientAccessHelper::requirePatientAccess((int) $patient->id, $patient, $redirectUrl);
    }
}