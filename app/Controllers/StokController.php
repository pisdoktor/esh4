<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AppSettings;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;
use App\Helpers\DateHelper;
use App\Helpers\IdHelper;
use App\Helpers\StokHelper;
use App\Helpers\TenantContext;
use App\Helpers\ThemeViewHelper;
use App\Helpers\ValidationHelper;
use App\Models\Patient;
use App\Models\StokHareket;
use App\Models\StokMalzeme;
use App\Services\Stok\StokService;

/**
 * Stok takip modülü — kurum kapsamlı malzeme ve hareket yönetimi.
 */
class StokController
{
    private StokService $service;

    public function __construct()
    {
        $this->service = new StokService();
        $this->ensureModule();
        AuthHelper::requirePermission('stok.read');
    }

    private function ensureModule(): void
    {
        if (!AppSettings::isModuleEnabled('stok')) {
            $_SESSION['error'] = 'Stok takip modülü kapalı.';
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }
        if (!StokService::moduleReady()) {
            $_SESSION['error'] = 'Stok modülü tabloları kurulu değil. database/migrate_esh_stok_tables.sql dosyasını çalıştırın.';
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }
    }

    private function requireAdmin(): void
    {
        AuthHelper::requirePermission('stok.admin');
    }

    private function requireCreate(): void
    {
        if (!AuthHelper::can('stok.create') && !AuthHelper::can('stok.admin')) {
            AuthHelper::requirePermission('stok.create');
        }
    }

    private function jsonOut(array $payload, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function kurumIdForStore(?int $requested = null, ?string $errorRedirect = null): int
    {
        try {
            return TenantContext::assignKurumIdForStore($requested);
        } catch (\RuntimeException $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ' . ($errorRedirect ?? esh_url('Dashboard', 'index')));
            exit;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function indexFilters(): array
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $kategori = trim((string) ($_GET['kategori'] ?? ''));
        $kritikOnly = isset($_GET['kritik']) && (string) $_GET['kritik'] === '1';
        $orderby = trim((string) ($_GET['orderby'] ?? 'm.ad'));
        $orderdir = strtoupper(trim((string) ($_GET['orderdir'] ?? 'ASC'))) === 'DESC' ? 'DESC' : 'ASC';

        $kurumIdFilter = null;
        if (AuthHelper::sessionIsSuperAdmin()) {
            $kurumRaw = trim((string) ($_GET['kurum_id'] ?? ''));
            if ($kurumRaw !== '' && ctype_digit($kurumRaw) && (int) $kurumRaw > 0) {
                $kurumIdFilter = (int) $kurumRaw;
            }
        }

        return [
            'search' => $search,
            'kategori' => $kategori,
            'kritik_only' => $kritikOnly,
            'orderby' => $orderby,
            'orderdir' => $orderdir,
            'kurum_id' => $kurumIdFilter,
        ];
    }

    public function index(): void
    {
        $filters = $this->indexFilters();
        $canAdmin = AuthHelper::can('stok.admin');
        $canCreate = AuthHelper::can('stok.create') || $canAdmin;
        $criticalCount = (new StokMalzeme())->countCritical($this->service->effectiveKurumIdForList());

        $listQuery = ['controller' => 'Stok', 'action' => 'index'];
        foreach (['search', 'kategori', 'orderby', 'orderdir'] as $k) {
            if (($filters[$k] ?? '') !== '' && !($k === 'orderby' && $filters[$k] === 'm.ad')) {
                $listQuery[$k] = $filters[$k];
            }
        }
        if ($filters['kritik_only']) {
            $listQuery['kritik'] = '1';
        }
        if ($filters['kurum_id'] !== null) {
            $listQuery['kurum_id'] = (string) $filters['kurum_id'];
        }

        $indexRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams(array_merge($listQuery, [
            'action' => 'indexRows',
        ]));

        $indexExportDataUrl = '';
        if ($canAdmin) {
            $indexExportDataUrl = \App\Helpers\UrlHelper::fromRequestParams(array_merge($listQuery, [
                'action' => 'indexExportData',
            ]));
        }

        $pageTitle = 'Stok Durumu';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'stok/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function indexRows(): void
    {
        AuthHelper::requirePermissionJson('stok.read');
        $filters = $this->indexFilters();
        $items = (new StokMalzeme())->listWithStock($filters);
        $canAdmin = AuthHelper::can('stok.admin');

        ob_start();
        include ROOT_PATH . '/views/site/stok/partials/index_table_rows.php';
        $html = ob_get_clean();

        $this->jsonOut(['ok' => true, 'html' => $html]);
    }

    public function kritikStok(): void
    {
        $_GET['kritik'] = '1';
        $this->index();
    }

    public function kritikStokRows(): void
    {
        $_GET['kritik'] = '1';
        $this->indexRows();
    }

    public function malzemeList(): void
    {
        $this->requireAdmin();
        $filters = $this->indexFilters();
        $filters['include_pasif'] = true;

        $listQuery = ['controller' => 'Stok', 'action' => 'malzemeList'];
        $indexRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams(array_merge($listQuery, [
            'action' => 'malzemeListRows',
        ]));

        $pageTitle = 'Malzeme Kartları';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'stok/malzeme_list');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function malzemeListRows(): void
    {
        $this->requireAdmin();
        AuthHelper::requirePermissionJson('stok.admin');
        $filters = $this->indexFilters();
        $filters['include_pasif'] = true;
        $items = (new StokMalzeme())->listCatalog($filters);

        ob_start();
        include ROOT_PATH . '/views/site/stok/partials/malzeme_list_rows.php';
        $html = ob_get_clean();

        $this->jsonOut(['ok' => true, 'html' => $html]);
    }

    public function malzemeCreate(): void
    {
        $this->requireAdmin();
        $item = new StokMalzeme();
        $pageTitle = 'Yeni Malzeme';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'stok/malzeme_form');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function malzemeEdit(): void
    {
        $this->requireAdmin();
        $id = (int) ($_GET['id'] ?? 0);
        $item = new StokMalzeme();
        if ($id < 1 || !$item->loadForKurum($id)) {
            $_SESSION['error'] = 'Malzeme kaydı bulunamadı.';
            header('Location: ' . esh_url('Stok', 'malzemeList'));
            exit;
        }
        TenantContext::assertRecordKurum((int) ($item->kurum_id ?? 0));
        $pageTitle = 'Malzeme Düzenle';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'stok/malzeme_form');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function malzemeStore(): void
    {
        $this->requireAdmin();
        CsrfHelper::requirePostMethod(esh_url('Stok', 'malzemeList'));

        $model = new StokMalzeme();
        $id = (int) ($_POST['id'] ?? 0);
        $isNew = $id < 1;

        if (!$isNew) {
            if (!$model->loadForKurum($id)) {
                $_SESSION['error'] = 'Malzeme bulunamadı.';
                header('Location: ' . esh_url('Stok', 'malzemeList'));
                exit;
            }
            TenantContext::assertRecordKurum((int) ($model->kurum_id ?? 0));
        }

        $kategori = trim((string) ($_POST['kategori'] ?? 'sarf'));
        if (!array_key_exists($kategori, StokHelper::kategoriOptions())) {
            $kategori = 'sarf';
        }
        $birim = trim((string) ($_POST['birim'] ?? 'adet'));
        if (!array_key_exists($birim, StokHelper::birimOptions())) {
            $birim = 'adet';
        }

        $bindData = [
            'kod' => trim((string) ($_POST['kod'] ?? '')),
            'ad' => trim((string) ($_POST['ad'] ?? '')),
            'kategori' => $kategori,
            'birim' => $birim,
            'min_stok' => max(0, (float) str_replace(',', '.', (string) ($_POST['min_stok'] ?? '0'))),
            'aktif' => isset($_POST['aktif']) ? 1 : 0,
            'aciklama' => trim((string) ($_POST['aciklama'] ?? '')),
            'tedarikci_adi' => trim((string) ($_POST['tedarikci_adi'] ?? '')),
            'tedarikci_tel' => trim((string) ($_POST['tedarikci_tel'] ?? '')),
            'birim_fiyat' => trim((string) ($_POST['birim_fiyat'] ?? '')) !== ''
                ? (float) str_replace(',', '.', (string) $_POST['birim_fiyat'])
                : null,
        ];
        if ($bindData['ad'] === '') {
            $_SESSION['error'] = 'Malzeme adı zorunludur.';
            header('Location: ' . esh_url('Stok', $isNew ? 'malzemeCreate' : 'malzemeEdit', $isNew ? [] : ['id' => $id]));
            exit;
        }

        if ($isNew) {
            $bindData['kurum_id'] = $this->kurumIdForStore(
                isset($_POST['kurum_id']) ? (int) $_POST['kurum_id'] : null,
                esh_url('Stok', 'malzemeCreate')
            );
        }

        $model->bind($bindData);
        if ($model->store()) {
            if ($isNew && (int) ($model->id ?? 0) > 0) {
                $this->service->initMalzemeStock((int) $model->kurum_id, (int) $model->id);
            }
            $_SESSION['success'] = $isNew ? 'Malzeme kaydı oluşturuldu.' : 'Malzeme güncellendi.';
            header('Location: ' . esh_url('Stok', 'malzemeList'));
            exit;
        }

        $_SESSION['error'] = 'Kayıt sırasında hata oluştu.';
        header('Location: ' . esh_url('Stok', $isNew ? 'malzemeCreate' : 'malzemeEdit', $isNew ? [] : ['id' => $id]));
        exit;
    }

    public function malzemeDelete(): void
    {
        $this->requireAdmin();
        CsrfHelper::requirePostMethod(esh_url('Stok', 'malzemeList'));

        $id = (int) ($_POST['id'] ?? 0);
        $model = new StokMalzeme();
        if ($id < 1 || !$model->loadForKurum($id)) {
            $_SESSION['error'] = 'Malzeme bulunamadı.';
            header('Location: ' . esh_url('Stok', 'malzemeList'));
            exit;
        }
        TenantContext::assertRecordKurum((int) ($model->kurum_id ?? 0));

        $stock = $this->service->getCurrentStock((int) $model->kurum_id, $id);
        if ($stock > 0) {
            $_SESSION['error'] = 'Stok miktarı sıfır olmayan malzeme silinemez. Önce pasif yapın.';
            header('Location: ' . esh_url('Stok', 'malzemeList'));
            exit;
        }

        if ($model->delete()) {
            $_SESSION['success'] = 'Malzeme kaydı silindi.';
        } else {
            $_SESSION['error'] = 'Silme işlemi başarısız.';
        }
        header('Location: ' . esh_url('Stok', 'malzemeList'));
        exit;
    }

    public function giris(): void
    {
        $this->requireAdmin();
        $kurumId = $this->kurumIdForStore();
        $malzemeler = (new StokMalzeme())->listActiveForSelect($kurumId);
        $pageTitle = 'Stok Girişi';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'stok/giris');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function girisStore(): void
    {
        $this->requireAdmin();
        CsrfHelper::requirePostMethod(esh_url('Stok', 'giris'));

        $kurumId = $this->kurumIdForStore(
            isset($_POST['kurum_id']) ? (int) $_POST['kurum_id'] : null,
            esh_url('Stok', 'giris')
        );
        $tarih = DateHelper::trDateToYmd(trim((string) ($_POST['hareket_tarihi'] ?? ''))) ?? date('Y-m-d');
        $aciklama = trim((string) ($_POST['aciklama'] ?? ''));
        $kullaniciId = AuthHelper::sessionUserId() ?? '';

        $linesRaw = $_POST['lines'] ?? null;
        if (is_array($linesRaw) && $linesRaw !== []) {
            $lines = [];
            foreach ($linesRaw as $line) {
                if (!is_array($line)) {
                    continue;
                }
                $sktTr = trim((string) ($line['skt'] ?? ''));
                $skt = $sktTr !== '' ? (DateHelper::trDateToYmd($sktTr) ?? $sktTr) : null;
                $lines[] = [
                    'malzeme_id' => (int) ($line['malzeme_id'] ?? 0),
                    'miktar' => (float) str_replace(',', '.', (string) ($line['miktar'] ?? '0')),
                    'lot_no' => trim((string) ($line['lot_no'] ?? '')),
                    'skt' => $skt,
                ];
            }
            $result = $this->service->recordBulkGiris($lines, [
                'kurum_id' => $kurumId,
                'hareket_tarihi' => $tarih,
                'kullanici_id' => $kullaniciId,
                'aciklama' => $aciklama !== '' ? $aciklama : null,
            ]);
            if ($result['ok'] ?? false) {
                $_SESSION['success'] = ((int) ($result['count'] ?? 0)) . ' kalem stok girişi kaydedildi.';
                header('Location: ' . esh_url('Stok', 'index'));
                exit;
            }
            $_SESSION['error'] = (string) ($result['error'] ?? 'Toplu giriş kaydedilemedi.');
            header('Location: ' . esh_url('Stok', 'giris'));
            exit;
        }

        $malzemeId = (int) ($_POST['malzeme_id'] ?? 0);
        $miktar = (float) str_replace(',', '.', (string) ($_POST['miktar'] ?? '0'));

        $result = $this->service->recordMovement([
            'kurum_id' => $kurumId,
            'malzeme_id' => $malzemeId,
            'hareket_tipi' => 'giris',
            'miktar' => $miktar,
            'hareket_tarihi' => $tarih,
            'kullanici_id' => $kullaniciId,
            'aciklama' => $aciklama !== '' ? $aciklama : null,
        ]);

        if ($result['ok'] ?? false) {
            $_SESSION['success'] = 'Stok girişi kaydedildi.';
            header('Location: ' . esh_url('Stok', 'index'));
            exit;
        }

        $_SESSION['error'] = (string) ($result['error'] ?? 'Giriş kaydedilemedi.');
        header('Location: ' . esh_url('Stok', 'giris'));
        exit;
    }

    public function cikis(): void
    {
        $this->requireCreate();
        $kurumId = $this->kurumIdForStore();
        $malzemeler = (new StokMalzeme())->listActiveForSelect($kurumId);
        $ekipler = (new StokHareket())->listRecentEkipler($kurumId);

        $preHasta = null;
        $hastaId = IdHelper::normalizeRequestId($_GET['hasta_id'] ?? null);
        if ($hastaId !== null) {
            $pModel = new Patient();
            if ($pModel->load($hastaId)) {
                TenantContext::assertRecordKurum((int) ($pModel->kurum_id ?? 0));
                if (Patient::isAktif($pModel->pasif ?? null)) {
                    $preHasta = $pModel;
                } else {
                    $_SESSION['error'] = 'Stok çıkışı yalnızca aktif hastalara yapılabilir.';
                }
            }
        }

        $pageTitle = 'Stok Çıkışı / Dağıtım';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'stok/cikis');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function cikisStore(): void
    {
        $this->requireCreate();
        CsrfHelper::requirePostMethod(esh_url('Stok', 'cikis'));

        $kurumId = $this->kurumIdForStore(
            isset($_POST['kurum_id']) ? (int) $_POST['kurum_id'] : null,
            esh_url('Stok', 'cikis')
        );
        $malzemeId = (int) ($_POST['malzeme_id'] ?? 0);
        $miktar = (float) str_replace(',', '.', (string) ($_POST['miktar'] ?? '0'));
        $tarih = DateHelper::trDateToYmd(trim((string) ($_POST['hareket_tarihi'] ?? ''))) ?? date('Y-m-d');
        $hastaId = IdHelper::normalizeRequestId($_POST['hasta_id'] ?? null);
        $ekipId = (int) ($_POST['ekip_id'] ?? 0);
        $aciklama = trim((string) ($_POST['aciklama'] ?? ''));

        $result = $this->service->recordMovement([
            'kurum_id' => $kurumId,
            'malzeme_id' => $malzemeId,
            'hareket_tipi' => 'cikis',
            'miktar' => $miktar,
            'hareket_tarihi' => $tarih,
            'kullanici_id' => AuthHelper::sessionUserId(),
            'hasta_id' => $hastaId,
            'ekip_id' => $ekipId > 0 ? $ekipId : null,
            'aciklama' => $aciklama !== '' ? $aciklama : null,
        ]);

        if ($result['ok'] ?? false) {
            $_SESSION['success'] = 'Stok çıkışı kaydedildi.';
            header('Location: ' . esh_url('Stok', 'hareketler'));
            exit;
        }

        $_SESSION['error'] = (string) ($result['error'] ?? 'Çıkış kaydedilemedi.');
        header('Location: ' . esh_url('Stok', 'cikis'));
        exit;
    }

    public function iade(): void
    {
        $this->requireCreate();
        $kurumId = $this->kurumIdForStore();
        $malzemeler = (new StokMalzeme())->listActiveForSelect($kurumId);
        $ekipler = (new StokHareket())->listRecentEkipler($kurumId);

        $preHasta = null;
        $hastaId = IdHelper::normalizeRequestId($_GET['hasta_id'] ?? null);
        if ($hastaId !== null) {
            $pModel = new Patient();
            if ($pModel->load($hastaId)) {
                TenantContext::assertRecordKurum((int) ($pModel->kurum_id ?? 0));
                $preHasta = $pModel;
            }
        }

        $pageTitle = 'Stok İadesi';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'stok/iade');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function iadeStore(): void
    {
        $this->requireCreate();
        CsrfHelper::requirePostMethod(esh_url('Stok', 'iade'));

        $kurumId = $this->kurumIdForStore(
            isset($_POST['kurum_id']) ? (int) $_POST['kurum_id'] : null,
            esh_url('Stok', 'iade')
        );
        $malzemeId = (int) ($_POST['malzeme_id'] ?? 0);
        $miktar = (float) str_replace(',', '.', (string) ($_POST['miktar'] ?? '0'));
        $tarih = DateHelper::trDateToYmd(trim((string) ($_POST['hareket_tarihi'] ?? ''))) ?? date('Y-m-d');
        $hastaId = IdHelper::normalizeRequestId($_POST['hasta_id'] ?? null);
        $ekipId = (int) ($_POST['ekip_id'] ?? 0);
        $aciklama = trim((string) ($_POST['aciklama'] ?? ''));

        $result = $this->service->recordMovement([
            'kurum_id' => $kurumId,
            'malzeme_id' => $malzemeId,
            'hareket_tipi' => 'iade',
            'miktar' => $miktar,
            'hareket_tarihi' => $tarih,
            'kullanici_id' => AuthHelper::sessionUserId(),
            'hasta_id' => $hastaId,
            'ekip_id' => $ekipId > 0 ? $ekipId : null,
            'aciklama' => $aciklama !== '' ? $aciklama : null,
        ]);

        if ($result['ok'] ?? false) {
            $_SESSION['success'] = 'Stok iadesi kaydedildi.';
            header('Location: ' . esh_url('Stok', 'hareketler'));
            exit;
        }

        $_SESSION['error'] = (string) ($result['error'] ?? 'İade kaydedilemedi.');
        header('Location: ' . esh_url('Stok', 'iade'));
        exit;
    }

    /**
     * @return array<string, mixed>
     */
    private function hareketFilters(): array
    {
        $malzemeId = (int) ($_GET['malzeme_id'] ?? 0);
        $hareketTipi = trim((string) ($_GET['hareket_tipi'] ?? ''));
        $hastaId = IdHelper::normalizeRequestId($_GET['hasta_id'] ?? null);
        $dateFromTr = trim((string) ($_GET['date_from'] ?? ''));
        $dateToTr = trim((string) ($_GET['date_to'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = (int) ($_GET['limit'] ?? 30);
        if (!in_array($limit, [10, 20, 30, 50, 100], true)) {
            $limit = 30;
        }

        return [
            'malzeme_id' => $malzemeId,
            'hareket_tipi' => $hareketTipi,
            'hasta_id' => $hastaId,
            'date_from' => DateHelper::trDateToYmd($dateFromTr) ?? '',
            'date_to' => DateHelper::trDateToYmd($dateToTr) ?? '',
            'date_from_tr' => $dateFromTr,
            'date_to_tr' => $dateToTr,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function hareketler(): void
    {
        $filters = $this->hareketFilters();
        $hareketModel = new StokHareket();
        $total = $hareketModel->countFiltered($filters);
        $offset = ($filters['page'] - 1) * $filters['limit'];
        $items = $hareketModel->listFiltered($filters, $filters['limit'], $offset);
        $malzemeler = (new StokMalzeme())->listActiveForSelect($this->kurumIdForStore());

        $listQuery = ['controller' => 'Stok', 'action' => 'hareketler'];
        foreach (['malzeme_id', 'hareket_tipi', 'hasta_id', 'limit'] as $k) {
            if (!empty($filters[$k])) {
                $listQuery[$k] = (string) $filters[$k];
            }
        }
        if ($filters['date_from_tr'] !== '') {
            $listQuery['date_from'] = $filters['date_from_tr'];
        }
        if ($filters['date_to_tr'] !== '') {
            $listQuery['date_to'] = $filters['date_to_tr'];
        }

        $hareketlerRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams(array_merge($listQuery, [
            'action' => 'hareketlerRows',
        ]));

        $hareketlerExportDataUrl = \App\Helpers\UrlHelper::fromRequestParams(array_merge($listQuery, [
            'action' => 'hareketlerExportData',
        ]));

        $pageTitle = 'Stok Hareketleri';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'stok/hareketler');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function hareketlerRows(): void
    {
        AuthHelper::requirePermissionJson('stok.read');
        $filters = $this->hareketFilters();
        $offset = ($filters['page'] - 1) * $filters['limit'];
        $items = (new StokHareket())->listFiltered($filters, $filters['limit'], $offset);

        ob_start();
        include ROOT_PATH . '/views/site/stok/partials/hareketler_rows.php';
        $html = ob_get_clean();

        $this->jsonOut(['ok' => true, 'html' => $html]);
    }

    public function hastaLookupAjax(): void
    {
        AuthHelper::requirePermissionJson('stok.create');
        header('Content-Type: application/json; charset=utf-8');

        $qRaw = trim((string) ($_GET['q'] ?? ''));
        if ($qRaw === '') {
            echo json_encode(['query' => '', 'suggestions' => [], 'exact' => null], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $onlyAktif = isset($_GET['scope']) && (string) $_GET['scope'] === 'aktif';
        $patientModel = new Patient();
        $suggestions = [];
        if (strlen($qRaw) >= 2) {
            $rows = $onlyAktif
                ? $patientModel->searchForBransRandevu($qRaw, 10)
                : $patientModel->searchForDashboardLookup($qRaw, 10);
            foreach ($rows as $p) {
                $suggestions[] = [
                    'id' => (string) ($p->id ?? ''),
                    'tckimlik' => (string) ($p->tckimlik ?? ''),
                    'isim' => (string) ($p->isim ?? ''),
                    'soyisim' => (string) ($p->soyisim ?? ''),
                    'adres' => Patient::formatIlceMahalle($p),
                    'care_summary' => StokHelper::patientCareFlagsSummary($p),
                ];
            }
        }

        $exact = null;
        $qDigits = preg_replace('/\D+/', '', $qRaw);
        if (ValidationHelper::isTcLength11($qDigits)) {
            $one = $patientModel->findByTcWithAddress($qDigits);
            if ($one && (!$onlyAktif || Patient::isAktif($one->pasif ?? null))) {
                $exact = [
                    'id' => (string) ($one->id ?? ''),
                    'tckimlik' => (string) ($one->tckimlik ?? ''),
                    'isim' => (string) ($one->isim ?? ''),
                    'soyisim' => (string) ($one->soyisim ?? ''),
                    'adres' => Patient::formatIlceMahalle($one),
                    'care_summary' => StokHelper::patientCareFlagsSummary($one),
                ];
            }
        }

        echo json_encode(['query' => $qRaw, 'suggestions' => $suggestions, 'exact' => $exact], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function hastaOzet(): void
    {
        $hastaId = IdHelper::normalizeRequestId($_GET['hasta_id'] ?? null);
        $pModel = new Patient();
        if ($hastaId === null || !$pModel->load($hastaId)) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }
        TenantContext::assertRecordKurum((int) ($pModel->kurum_id ?? 0));

        $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));
        $filters = [
            'hasta_id' => $hastaId,
            'hareket_tipi_in' => ['cikis', 'iade'],
            'date_from' => $sixMonthsAgo,
            'page' => max(1, (int) ($_GET['page'] ?? 1)),
            'limit' => 30,
        ];
        $hareketModel = new StokHareket();
        $total = $hareketModel->countFiltered($filters);
        $offset = ($filters['page'] - 1) * $filters['limit'];
        $items = $hareketModel->listFiltered($filters, $filters['limit'], $offset);

        $hastaOzetRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Stok',
            'action' => 'hastaOzetRows',
            'hasta_id' => (string) $hastaId,
        ]);

        $pageTitle = 'Hasta Stok Tüketimi';
        $hasta = $pModel;
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'stok/hasta_ozet');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function hastaOzetRows(): void
    {
        AuthHelper::requirePermissionJson('stok.read');
        $hastaId = IdHelper::normalizeRequestId($_GET['hasta_id'] ?? null);
        if ($hastaId === null) {
            $this->jsonOut(['ok' => false, 'error' => 'Hasta gerekli'], 400);
        }
        $pModel = new Patient();
        if (!$pModel->load($hastaId)) {
            $this->jsonOut(['ok' => false, 'error' => 'Hasta bulunamadı'], 404);
        }
        TenantContext::assertRecordKurum((int) ($pModel->kurum_id ?? 0));

        $sixMonthsAgo = date('Y-m-d', strtotime('-6 months'));
        $filters = [
            'hasta_id' => $hastaId,
            'hareket_tipi_in' => ['cikis', 'iade'],
            'date_from' => $sixMonthsAgo,
        ];
        $items = (new StokHareket())->listFiltered($filters, 50, 0);

        ob_start();
        include ROOT_PATH . '/views/site/stok/partials/hasta_stok_rows.php';
        $html = ob_get_clean();

        $this->jsonOut(['ok' => true, 'html' => $html]);
    }

    public function sayim(): void
    {
        $this->requireAdmin();
        $kurumId = $this->kurumIdForStore();
        $malzemeler = (new StokMalzeme())->listActiveForSelect($kurumId);
        $pageTitle = 'Stok Sayımı';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'stok/sayim');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function sayimStore(): void
    {
        $this->requireAdmin();
        CsrfHelper::requirePostMethod(esh_url('Stok', 'sayim'));

        $kurumId = $this->kurumIdForStore(
            isset($_POST['kurum_id']) ? (int) $_POST['kurum_id'] : null,
            esh_url('Stok', 'sayim')
        );
        $malzemeId = (int) ($_POST['malzeme_id'] ?? 0);
        $sayilan = (float) str_replace(',', '.', (string) ($_POST['sayilan_miktar'] ?? '0'));
        $tarih = DateHelper::trDateToYmd(trim((string) ($_POST['hareket_tarihi'] ?? ''))) ?? date('Y-m-d');

        $result = $this->service->recordSayimAdjustment([
            'kurum_id' => $kurumId,
            'malzeme_id' => $malzemeId,
            'sayilan_miktar' => $sayilan,
            'hareket_tarihi' => $tarih,
            'kullanici_id' => AuthHelper::sessionUserId(),
        ]);

        if ($result['ok'] ?? false) {
            if (!empty($result['skipped'])) {
                $_SESSION['success'] = 'Fark yok; stok zaten sayılan miktarda.';
            } else {
                $_SESSION['success'] = 'Sayım düzeltmesi kaydedildi.';
            }
            header('Location: ' . esh_url('Stok', 'hareketler'));
            exit;
        }

        $_SESSION['error'] = (string) ($result['error'] ?? 'Sayım kaydedilemedi.');
        header('Location: ' . esh_url('Stok', 'sayim'));
        exit;
    }

    public function siparisOneri(): void
    {
        $this->requireAdmin();
        $items = (new StokMalzeme())->listSiparisOneri($this->service->effectiveKurumIdForList());
        $siparisExportDataUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Stok',
            'action' => 'siparisExportData',
        ]);

        $pageTitle = 'Sipariş Önerisi';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'stok/siparis_oneri');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function indexExportData(): void
    {
        AuthHelper::requirePermissionJson('stok.admin');
        $filters = $this->indexFilters();
        $items = (new StokMalzeme())->listWithStock($filters);

        $summary = [];
        if (($filters['search'] ?? '') !== '') {
            $summary[] = 'Arama: ' . $filters['search'];
        }
        if (($filters['kategori'] ?? '') !== '') {
            $summary[] = 'Kategori: ' . StokHelper::kategoriLabel($filters['kategori']);
        }
        if ($filters['kritik_only']) {
            $summary[] = 'Yalnız kritik';
        }

        $this->jsonOut([
            'ok' => true,
            'headers' => \App\Helpers\StokExportHelper::indexHeaders(),
            'rows' => \App\Helpers\StokExportHelper::indexRows($items),
            'meta' => [
                'filterSummary' => implode(' · ', $summary),
                'generatedAt' => DateHelper::nowTrDateTime(),
            ],
            'filename' => 'stok-durumu',
        ]);
    }

    public function hareketlerExportData(): void
    {
        AuthHelper::requirePermissionJson('stok.read');
        $filters = $this->hareketFilters();
        $items = (new StokHareket())->listFiltered($filters, 500, 0);

        $summary = [];
        if (!empty($filters['malzeme_id'])) {
            $summary[] = 'Malzeme #' . $filters['malzeme_id'];
        }
        if (!empty($filters['hareket_tipi'])) {
            $summary[] = StokHelper::hareketTipiLabel($filters['hareket_tipi']);
        }

        $this->jsonOut([
            'ok' => true,
            'headers' => \App\Helpers\StokExportHelper::hareketHeaders(),
            'rows' => \App\Helpers\StokExportHelper::hareketRows($items),
            'meta' => [
                'filterSummary' => implode(' · ', $summary),
                'generatedAt' => DateHelper::nowTrDateTime(),
            ],
            'filename' => 'stok-hareketleri',
        ]);
    }

    public function siparisExportData(): void
    {
        AuthHelper::requirePermissionJson('stok.admin');
        $items = (new StokMalzeme())->listSiparisOneri($this->service->effectiveKurumIdForList());

        $this->jsonOut([
            'ok' => true,
            'headers' => \App\Helpers\StokExportHelper::siparisHeaders(),
            'rows' => \App\Helpers\StokExportHelper::siparisRows($items),
            'meta' => [
                'filterSummary' => count($items) . ' kalem',
                'generatedAt' => DateHelper::nowTrDateTime(),
            ],
            'filename' => 'stok-siparis-oneri',
        ]);
    }
}
