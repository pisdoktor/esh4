<?php
namespace App\Controllers;

use App\Helpers\BadgeHelper;
use App\Helpers\AuditLogHelper;
use App\Helpers\EraporIndexPdfHelper;
use App\Helpers\AuthHelper;
use App\Helpers\IdHelper;
use App\Helpers\ThemeViewHelper;
use App\Helpers\DateHelper;
use App\Helpers\TenantStoreHelper;
use App\Helpers\ValidationHelper;
use App\Models\Brans;
use App\Models\Erapor;
use App\Models\Patient;

/**
 * e-Rapor Havuzu Controller (Admin Paneli)
 * Dışarıdan gelen rapor başvurularını ve sistemdeki hasta eşleşmelerini yönetir.
 */
class EraporController {
    
    /**
     * e-Rapor index: filtre ve sayfalama parametreleri.
     *
     * @return array{
     *   limit:int,
     *   page:int,
     *   bransFilter:int,
     *   durumFilter:?int,
     *   yenilendiFilter:?int,
     *   search:string,
     *   dateFromTr:string,
     *   dateToTr:string,
     *   orderby:string,
     *   orderdir:string,
     *   filters:array<string, mixed>,
     *   baseParams:array<string, string|int>
     * }
     */
    private function indexRequestState(): array {
        $allowedLimits = [5, 10, 15, 20, 25, 50, 100];
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        if (!in_array($limit, $allowedLimits, true)) {
            $limit = 20;
        }
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }

        $bransFilter = isset($_GET['brans']) ? (int) $_GET['brans'] : 0;
        $durumFilter = isset($_GET['kayitlimi']) && $_GET['kayitlimi'] !== '' ? (int) $_GET['kayitlimi'] : null;
        $yenilendiFilter = isset($_GET['yenilendimi']) && $_GET['yenilendimi'] !== '' ? (int) $_GET['yenilendimi'] : null;
        $search = trim((string) ($_GET['search'] ?? ''));
        $dateFromTr = trim((string) ($_GET['date_from'] ?? ''));
        $dateToTr = trim((string) ($_GET['date_to'] ?? ''));
        $dateFrom = DateHelper::trDateToYmd($dateFromTr) ?? '';
        $dateTo = DateHelper::trDateToYmd($dateToTr) ?? '';
        $orderby = trim((string) ($_GET['orderby'] ?? 'basvurutarihi'));
        $orderdir = strtoupper(trim((string) ($_GET['orderdir'] ?? 'DESC')));
        if (!in_array($orderdir, ['ASC', 'DESC'], true)) {
            $orderdir = 'DESC';
        }

        $filters = [
            'bransId' => $bransFilter > 0 ? $bransFilter : null,
            'kayitlimi' => $durumFilter,
            'yenilendimi' => $yenilendiFilter,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'orderby' => $orderby,
            'orderdir' => $orderdir,
        ];

        $baseParams = [
            'controller' => 'Erapor',
            'action' => 'index',
        ];
        if ($bransFilter > 0) {
            $baseParams['brans'] = $bransFilter;
        }
        if ($durumFilter !== null) {
            $baseParams['kayitlimi'] = $durumFilter;
        }
        if ($yenilendiFilter !== null) {
            $baseParams['yenilendimi'] = $yenilendiFilter;
        }
        if ($search !== '') {
            $baseParams['search'] = $search;
        }
        if ($dateFromTr !== '') {
            $baseParams['date_from'] = $dateFromTr;
        }
        if ($dateToTr !== '') {
            $baseParams['date_to'] = $dateToTr;
        }
        if (array_key_exists('orderby', $_GET) && trim((string) $_GET['orderby']) !== '') {
            $baseParams['orderby'] = trim((string) $_GET['orderby']);
        }
        if (array_key_exists('orderdir', $_GET)) {
            $baseParams['orderdir'] = $orderdir;
        }

        return [
            'limit' => $limit,
            'page' => $page,
            'bransFilter' => $bransFilter,
            'durumFilter' => $durumFilter,
            'yenilendiFilter' => $yenilendiFilter,
            'search' => $search,
            'dateFromTr' => $dateFromTr,
            'dateToTr' => $dateToTr,
            'orderby' => $orderby,
            'orderdir' => $orderdir,
            'filters' => $filters,
            'baseParams' => $baseParams,
        ];
    }

    /**
     * @return array{page:int, offset:int}
     */
    private function indexResolvePaging(int $page, int $limit, int $total): array {
        $totalPages = max(1, (int) ceil($total / $limit));
        if ($total > 0 && $page > $totalPages) {
            $page = $totalPages;
        }

        return [
            'page' => $page,
            'offset' => ($page - 1) * $limit,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     * @return list<object>
     */
    private function loadReportsWithSystemMatch(int $limit, int $offset, array $filters): array {
        $model = new Erapor();
        $reports = $model->getReportsPage($limit, $offset, $filters);

        foreach ($reports as $report) {
            $rObj = new Erapor();
            if ($rObj->load($report->id)) {
                $rObj->matchWithSystem();
                $report->kayitlimi = (int) $rObj->kayitlimi;
            }
        }

        return $reports;
    }

    /**
     * e-Rapor Havuzu Listesi
     * Görünüm: views/site/erapor/index.php
     */
    public function index() {
        $st = $this->indexRequestState();
        $limit = $st['limit'];
        $bransFilter = $st['bransFilter'];
        $durumFilter = $st['durumFilter'];
        $yenilendiFilter = $st['yenilendiFilter'];
        $search = $st['search'];
        $dateFromTr = $st['dateFromTr'];
        $dateToTr = $st['dateToTr'];
        $orderby = $st['orderby'];
        $orderdir = $st['orderdir'];

        $model = new Erapor();
        $total = $model->countReports($st['filters']);
        $paging = $this->indexResolvePaging($st['page'], $limit, $total);
        $page = $paging['page'];

        $bransModel = new Brans();
        $branslar = $bransModel->getList();

        $pagelink = \App\Helpers\UrlHelper::fromRequestParams($st['baseParams']);

        $indexRowsFetchParams = array_merge($st['baseParams'], [
            'action' => 'indexRows',
            'page' => $page,
            'limit' => $limit,
        ]);
        $indexRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams($indexRowsFetchParams);

        $indexPdfDataUrl = '';
        if (AuthHelper::sessionIsAdmin()) {
            $indexPdfDataParams = array_merge($st['baseParams'], [
                'action' => 'indexPdfData',
                'page' => $page,
                'limit' => $limit,
            ]);
            $indexPdfDataUrl = \App\Helpers\UrlHelper::fromRequestParams($indexPdfDataParams);
        }

        $pageTitle = "e-Rapor Başvuru Havuzu";

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'erapor/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * e-Rapor havuzu tablo satırları (JSON HTML parçası) — liste iskeletinden sonra XHR ile yüklenir.
     */
    public function indexRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $st = $this->indexRequestState();
        $model = new Erapor();
        $total = $model->countReports($st['filters']);
        $paging = $this->indexResolvePaging($st['page'], $st['limit'], $total);
        $reports = $this->loadReportsWithSystemMatch($st['limit'], $paging['offset'], $st['filters']);

        ob_start();
        include ROOT_PATH . '/views/site/erapor/partials/index_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Tek TC grubu alt satırları (JSON HTML) — liste genişletmesi.
     */
    public function tcGroupRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $tc = preg_replace('/\D+/', '', (string) ($_GET['tc'] ?? ''));
        $excludeId = IdHelper::normalizeRequestId($_GET['exclude_id'] ?? null);
        $excludeIdsRaw = trim((string) ($_GET['exclude_ids'] ?? ''));
        $excludeIds = [];
        if ($excludeIdsRaw !== '') {
            foreach (explode(',', $excludeIdsRaw) as $part) {
                $id = IdHelper::normalizeRequestId(trim($part));
                if ($id !== null) {
                    $excludeIds[$id] = true;
                }
            }
        }
        if ($excludeId !== null) {
            $excludeIds[$excludeId] = true;
        }
        if (!ValidationHelper::isTcLength11($tc)) {
            echo json_encode(['ok' => false, 'error' => 'Geçersiz TC'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $st = $this->indexRequestState();
        $model = new Erapor();
        $reports = $model->getReportsByTc($tc, $st['filters'], null);
        if ($excludeIds !== []) {
            $reports = array_values(array_filter($reports, static function ($row) use ($excludeIds) {
                $rowId = IdHelper::normalizeRequestId($row->id ?? null);

                return $rowId === null || !isset($excludeIds[$rowId]);
            }));
        }

        foreach ($reports as $report) {
            $rObj = new Erapor();
            if ($rObj->load($report->id)) {
                $rObj->matchWithSystem();
                $report->kayitlimi = (int) $rObj->kayitlimi;
            }
        }

        $tcGroup = $tc;
        ob_start();
        include ROOT_PATH . '/views/site/erapor/partials/index_table_child_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     */
    public function indexPdfData() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        AuthHelper::requireAdminJson();

        $st = $this->indexRequestState();
        AuditLogHelper::eraporExport(['page' => $st['page']]);
        $model = new Erapor();
        $total = $model->countReports($st['filters']);
        $paging = $this->indexResolvePaging($st['page'], $st['limit'], $total);
        $reports = $this->loadReportsWithSystemMatch($st['limit'], $paging['offset'], $st['filters']);

        $bransModel = new Brans();
        $branslar = $bransModel->getList();

        $rows = [];
        foreach ($reports as $row) {
            $rows[] = EraporIndexPdfHelper::exportReportRow($row);
        }

        echo json_encode([
            'ok' => true,
            'headers' => EraporIndexPdfHelper::tableHeaders(),
            'rows' => $rows,
            'meta' => [
                'filterSummary' => EraporIndexPdfHelper::buildFilterSummary($st, $total, $branslar),
                'generatedAt' => DateHelper::nowTrDateTime(),
            ],
            'filename' => EraporIndexPdfHelper::suggestFilename($paging['page']),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function tcLookupAjax() {
        header('Content-Type: application/json; charset=utf-8');
        $tc = preg_replace('/\D+/', '', (string) ($_GET['tc'] ?? ''));
        $excludeId = IdHelper::normalizeRequestId($_GET['exclude_id'] ?? null);
        $out = [
            'ok' => true,
            'valid' => false,
            'exists' => false,
            'isim' => '',
            'soyisim' => '',
            'havuz_count' => 0,
        ];
        if (!ValidationHelper::isTcLength11($tc)) {
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
            exit;
        }
        $patientModel = new Patient();
        $out['valid'] = (bool) $patientModel->validateTc($tc);
        if ($out['valid']) {
            $eraporModel = new Erapor();
            $havuzCount = $eraporModel->countByTc($tc, $excludeId);
            $out['havuz_count'] = $havuzCount;

            $hasta = $patientModel->findByTc($tc);
            if ($hasta) {
                $out['exists'] = true;
                $out['isim'] = (string) ($hasta->isim ?? '');
                $out['soyisim'] = (string) ($hasta->soyisim ?? '');
                $out = array_merge($out, BadgeHelper::patientFileStatusForApi($hasta));
            }
        }
        echo json_encode($out, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Yeni Rapor Verisi Giriş Formu
     * Görünüm: views/site/erapor/create.php
     */
    public function create() {
        // Branşları model üzerinden çekiyoruz
        // Not: App\Models\Brans modelinizin olduğunu varsayıyorum
        $bransModel = new \App\Models\Brans();
        $branslar = $bransModel->getList(); // Tüm branşları getiren fonksiyon

        $pageTitle = "Yeni Rapor Kaydı";
        
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'erapor/create');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Mevcut havuz kaydını düzenle (aynı form: create.php)
     */
    public function edit() {
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $model = new Erapor();

        if ($id !== null && $model->load($id)) {
            TenantStoreHelper::assertModelKurum($model);
            $item = $model;
            $bransModel = new \App\Models\Brans();
            $branslar = $bransModel->getList();

            $pageTitle = 'Rapor Kaydı Düzenle';

            include ThemeViewHelper::resolvePartial('header');
            include ThemeViewHelper::resolveAreaView('site', 'erapor/create');
            include ThemeViewHelper::resolvePartial('footer');

            return;
        }

        $_SESSION['error'] = 'Düzenlenecek rapor kaydı bulunamadı.';
        header('Location: ' . esh_url('Erapor', 'index'));
        exit;
    }

    /**
     * Rapor Detaylarını Görüntüle
     * Görünüm: views/site/erapor/view.php
     */
    public function view() {
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $model = new Erapor();
        
        if ($id !== null && $model->loadWithBrans($id)) {
            TenantStoreHelper::assertModelKurum($model);
            $item = $model;
            include ThemeViewHelper::resolvePartial('header');
            include ThemeViewHelper::resolveAreaView('site', 'erapor/view');
            include ThemeViewHelper::resolvePartial('footer');
        } else {
            $_SESSION['error'] = "Rapor detayı bulunamadı!";
            header('Location: ' . esh_url('Erapor', 'index'));
            exit;
        }
    }

    /**
     * Raporu Havuzdan Sil
     */
    public function delete() {
        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('Erapor', 'index'));

        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $model = new Erapor();

        if ($id !== null && $model->load($id)) {
            TenantStoreHelper::assertModelKurum($model);
            if ($model->delete()) {
                $_SESSION['success'] = "Rapor kaydı havuzdan kaldırıldı.";
            } else {
                $_SESSION['error'] = "Silme işlemi sırasında bir hata oluştu.";
            }
        }

        header('Location: ' . esh_url('Erapor', 'index'));
        exit;
    }

    /**
     * Manuel Eşleştirme (Opsiyonel)
     * Eğer TC tutmuyorsa ama isimden eşleşme yapılabiliyorsa kullanılır.
     */
    public function markAsProcessed() {
        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('Erapor', 'index'));

        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $model = new Erapor();
        
        if ($id !== null && $model->load($id)) {
            TenantStoreHelper::assertModelKurum($model);
            $model->kayitlimi = 1;
            $model->store();
            $_SESSION['success'] = "Rapor işlendi olarak işaretlendi.";
        }
        
        header('Location: ' . esh_url('Erapor', 'index'));
        exit;
    }
    
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $model = new Erapor();
            $editId = IdHelper::normalizeRequestId($_POST['id'] ?? null);

            if ($editId !== null) {
                if (!$model->load($editId)) {
                    $_SESSION['error'] = 'Güncellenecek rapor kaydı bulunamadı.';
                    header('Location: ' . esh_url('Erapor', 'index'));
                    exit;
                }
                TenantStoreHelper::assertModelKurum($model);
            } else {
                // Yeni kayıt için ID'yi temizle (BaseModel uyumu için)
                if (isset($_POST['id']) && $_POST['id'] === '') {
                    unset($_POST['id']);
                }
            }

            $post = $_POST;
            if (!empty($post['basvurutarihi'])) {
                $ymd = DateHelper::trDateToYmd(trim((string) $post['basvurutarihi']));
                if ($ymd !== null) {
                    $post['basvurutarihi'] = $ymd;
                }
            }
            if (isset($post['isim'])) {
                $post['isim'] = mb_strtoupper(trim((string) $post['isim']), 'UTF-8');
            }
            if (isset($post['soyisim'])) {
                $post['soyisim'] = mb_strtoupper(trim((string) $post['soyisim']), 'UTF-8');
            }

            $phoneErr = \App\Helpers\ValidationHelper::applyPhoneFields($post, false);
            if ($phoneErr !== null) {
                $_SESSION['error'] = $phoneErr;
                header('Location: ' . (!IdHelper::isEmptyEntityId($editId) ? esh_url('Erapor', 'edit', ['id' => $editId]) : esh_url('Erapor', 'create')));
                exit;
            }

            $model->bind($post);

            if (IdHelper::isEmptyEntityId($editId)) {
                TenantStoreHelper::applyKurumIdToModel($model);
            }
            
            // Veritabanına kaydet
            if ($model->store()) {
                $_SESSION['success'] = !IdHelper::isEmptyEntityId($editId)
                    ? 'Rapor kaydı güncellendi.'
                    : 'Rapor verisi başarıyla havuzuna eklendi.';
            } else {
                $_SESSION['error'] = "Kayıt sırasında bir hata oluştu!";
            }
            
            header('Location: ' . esh_url('Erapor', 'index'));
            exit;
        }
    }
}