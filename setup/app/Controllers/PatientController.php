<?php
namespace App\Controllers;

use App\Helpers\ThemeViewHelper;
use App\Helpers\AppSettings;
use App\Core\Database;
use App\Helpers\DateHelper;
use App\Helpers\HastaIlacRaporStatusHelper;
use App\Helpers\PatientCareHelper;
use App\Helpers\PatientUnifiedPdfHelper;
use App\Helpers\AuthHelper;
use App\Helpers\AuditLogHelper;
use App\Helpers\EsysComplianceHelper;
use App\Helpers\UsbsComplianceHelper;
use App\Helpers\ClinicalDecisionSupportHelper;
use App\Helpers\CinsiyetHelper;
use App\Helpers\BradenScaleHelper;
use App\Helpers\ItakiScaleHelper;
use App\Helpers\HarizmiScaleHelper;
use App\Helpers\BarthelScaleHelper;
use App\Helpers\MnaScaleHelper;
use App\Helpers\IslemIdSettings;
use App\Helpers\PatientKurumTransfer;
use App\Helpers\PatientNakilRequest;
use App\Helpers\IdHelper;
use App\Helpers\PatientAccessHelper;
use App\Helpers\PostAllowlistHelper;
use App\Helpers\QueryHelper;
use App\Helpers\KurumAdresScope;
use App\Helpers\TenantContext;
use App\Helpers\TenantStoreHelper;
use App\Helpers\ValidationHelper;
use App\Models\Patient;
use App\Models\Address;
use App\Models\Hastalik;
use App\Models\Guvence;
use App\Models\Islem;
use App\Models\Visit;
use App\Models\PlannedVisit;
use App\Models\WoundPhoto;
use App\Models\BradenAssessment;
use App\Models\ItakiAssessment;
use App\Models\HarizmiAssessment;
use App\Models\MnaAssessment;
use App\Models\BarthelAssessment;
use App\Models\HastaIlac;
use App\Models\HastaIlacRapor;
use App\Models\MahallePlan;

class PatientController {

    /**
     * Tüm hasta türleri: tek sayfa, durum filtresi + (pasif için) neden/tarih.
     */
    /**
     * Birleşik hasta listesi: ortak GET durumu (durum, sayfalama, filtre, sıralama).
     *
     * @return array{
     *   status:string,
     *   limit:int,
     *   page:int,
     *   offset:int,
     *   search:string,
     *   reason:string,
     *   startDate:string,
     *   endDate:string,
     *   orderby:string,
     *   orderdir:string,
     *   ordering:string,
     *   baseParams:array<string, string>
     * }
     */
    private function unifiedListRequestState(): array {
        $status = Patient::normalizeUnifiedStatus($_GET['status'] ?? 'active');
        $adminOnly = ['all', 'deleted', 'died', 'araf', 'probable'];
        if (in_array($status, $adminOnly, true) && !AuthHelper::sessionIsAdmin()) {
            $status = 'active';
        }

        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        if ($limit < 1) {
            $limit = 20;
        }
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $limit;
        $search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
        $reason = isset($_GET['reason']) ? trim((string) $_GET['reason']) : '';
        $startDate = isset($_GET['startDate']) ? (string) $_GET['startDate'] : '';
        $endDate = isset($_GET['endDate']) ? (string) $_GET['endDate'] : '';
        $feature = '';
        if (AuthHelper::sessionIsAdmin()) {
            $feature = Patient::normalizeUnifiedFeatureFilter($_GET['feature'] ?? '');
        }

        $orderby = isset($_GET['orderby']) ? (string) $_GET['orderby'] : 'h.isim';
        $orderdir = (isset($_GET['orderdir']) && strtoupper((string) $_GET['orderdir']) === 'DESC') ? 'DESC' : 'ASC';
        $ordering = QueryHelper::patientListOrderBy($orderby, $orderdir);

        $baseParams = [
            'controller' => 'Patient',
            'action' => 'unified',
            'status' => $status,
        ];
        if ($search !== '') {
            $baseParams['search'] = $search;
        }
        if ($reason !== '') {
            $baseParams['reason'] = $reason;
        }
        if ($startDate !== '') {
            $baseParams['startDate'] = $startDate;
        }
        if ($endDate !== '') {
            $baseParams['endDate'] = $endDate;
        }
        if ($feature !== '') {
            $baseParams['feature'] = $feature;
        }

        return [
            'status' => $status,
            'limit' => $limit,
            'page' => $page,
            'offset' => $offset,
            'search' => $search,
            'reason' => $reason,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'feature' => $feature,
            'orderby' => $orderby,
            'orderdir' => $orderdir,
            'ordering' => $ordering,
            'baseParams' => $baseParams,
        ];
    }

    public function unified() {
        $st = $this->unifiedListRequestState();
        $status = $st['status'];
        $limit = $st['limit'];
        $page = $st['page'];
        $search = $st['search'];
        $reason = $st['reason'];
        $startDate = $st['startDate'];
        $endDate = $st['endDate'];
        $feature = $st['feature'];
        $orderby = $st['orderby'];
        $orderdir = $st['orderdir'];
        $ordering = $st['ordering'];

        $model = new Patient();
        $totalPatients = $model->countUnified($status, $search, $reason, $startDate, $endDate, $feature);

        $pasifListesi = PatientCareHelper::pasifDosyaNedeniFilterLabels();

        $baseParams = $st['baseParams'];
        $pagelink = \App\Helpers\UrlHelper::fromRequestParams($baseParams);

        $unifiedRowsFetchParams = array_merge($baseParams, [
            'action' => 'unifiedRows',
            'page' => $page,
            'limit' => $limit,
            'orderby' => $orderby,
            'orderdir' => $orderdir,
        ]);
        $unifiedRowsFetchUrl = \App\Helpers\UrlHelper::fromRequestParams($unifiedRowsFetchParams);

        $unifiedPdfDataUrl = '';
        if (AuthHelper::sessionIsAdmin()) {
            $unifiedPdfDataParams = array_merge($baseParams, [
                'action' => 'unifiedPdfData',
                'page' => $page,
                'limit' => $limit,
                'orderby' => $orderby,
                'orderdir' => $orderdir,
            ]);
            $unifiedPdfDataUrl = \App\Helpers\UrlHelper::fromRequestParams($unifiedPdfDataParams);
        }

        $pageTitle = 'Hasta Listesi';
        $showPassiveFilters = ($status === 'passive' || $status === 'all');

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'hasta/unified');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Birleşik liste tablo satırları (JSON HTML parçası) — ilk sayfa iskeletinden sonra XHR ile yüklenir.
     */
    public function unifiedRows() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $st = $this->unifiedListRequestState();
        $model = new Patient();
        $patients = $model->getUnified(
            $st['limit'],
            $st['offset'],
            $st['ordering'],
            $st['status'],
            $st['search'],
            $st['reason'],
            $st['startDate'],
            $st['endDate'],
            $st['feature']
        );

        $curStatus = $st['status'];
        $pagelink = \App\Helpers\UrlHelper::fromRequestParams($st['baseParams']);
        $unifiedPasifTarihiInsteadOfRandevu = in_array($curStatus, ['passive', 'probable', 'araf', 'deleted'], true);

        ob_start();
        include ROOT_PATH . '/views/site/hasta/partials/unified_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Birleşik liste — pdfMake için mevcut sayfa satırları (JSON).
     */
    public function unifiedPdfData() {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        AuthHelper::requireAdminJson();

        $st = $this->unifiedListRequestState();
        AuditLogHelper::patientExport([
            'status' => $st['status'],
            'page' => $st['page'],
        ]);
        $model = new Patient();
        $patients = $model->getUnified(
            $st['limit'],
            $st['offset'],
            $st['ordering'],
            $st['status'],
            $st['search'],
            $st['reason'],
            $st['startDate'],
            $st['endDate'],
            $st['feature']
        );

        $curStatus = $st['status'];
        $usesPasif = PatientUnifiedPdfHelper::usesPasifTarihiColumn($curStatus);
        $altCol = $usesPasif
            ? PatientUnifiedPdfHelper::pasifTarihiColumnTitle($curStatus)
            : 'Randevu';

        $isAdmin = AuthHelper::sessionIsAdmin();
        $labels = PatientUnifiedPdfHelper::statusLabelsForSession($isAdmin);
        $statusLabel = $labels[$curStatus] ?? $curStatus;
        $total = $model->countUnified(
            $curStatus,
            $st['search'],
            $st['reason'],
            $st['startDate'],
            $st['endDate'],
            $st['feature']
        );

        $rows = [];
        foreach ($patients as $p) {
            $rows[] = PatientUnifiedPdfHelper::exportPatientRow($p, $usesPasif);
        }

        echo json_encode([
            'ok' => true,
            'headers' => PatientUnifiedPdfHelper::tableHeaders($usesPasif, $altCol),
            'rows' => $rows,
            'meta' => [
                'filterSummary' => PatientUnifiedPdfHelper::buildFilterSummary($st, $total, $statusLabel),
                'generatedAt' => DateHelper::nowTrDateTime(),
            ],
            'filename' => PatientUnifiedPdfHelper::suggestFilename($curStatus, $st['page']),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Eski URL uyumu → birleşik liste */
    public function listactive() {
        header('Location: ' . esh_url_from_merged_get([
            'controller' => 'Patient',
            'action' => 'unified',
            'status' => 'active',
        ]));
        exit;
    }
    
    public function listpassive() {
        header('Location: ' . esh_url_from_merged_get([
            'controller' => 'Patient',
            'action' => 'unified',
            'status' => 'passive',
        ]));
        exit;
    }
    
    public function listwaiting() {
        header('Location: ' . esh_url_from_merged_get([
            'controller' => 'Patient',
            'action' => 'unified',
            'status' => 'waiting',
        ]));
        exit;
    }
    
    public function listdied() {
        header('Location: ' . esh_url_from_merged_get([
            'controller' => 'Patient',
            'action' => 'unified',
            'status' => 'probable',
        ]));
        exit;
    }
    
    public function listaraf() {
        header('Location: ' . esh_url_from_merged_get([
            'controller' => 'Patient',
            'action' => 'unified',
            'status' => 'araf',
        ]));
        exit;
    }
    
    public function listdeleted() {
        header('Location: ' . esh_url_from_merged_get([
            'controller' => 'Patient',
            'action' => 'unified',
            'status' => 'deleted',
        ]));
        exit;
    }
    
    //Bekleyen hasta detay gösterimi
    public function bview() {
    
    }

    /**
     * Dashboard "ilk ziyaret" kısayolu: bekleyen hastayı düzenleme ekranına yönlendirir.
     */
    public function firstSave() {
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        if ($id === null) {
            header('Location: ' . esh_url('Patient', 'ilkkayit'));
            exit;
        }
        header('Location: ' . esh_url('Patient', 'edit', ['id' => $id]));
        exit;
    }

    /**
     * Bekleyen hasta için pdfmake başvuru/değerlendirme formu.
     */
    public function waitingForm() {
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta kaydı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'waiting',
)));
            exit;
        }

        $patient = (new Patient())->getById($id);
        if (!$patient) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'waiting',
)));
            exit;
        }

        if ((string) ($patient->pasif ?? '') !== '-3') {
            $_SESSION['error'] = 'Bu form yalnızca bekleyen hastalar için kullanılabilir.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'waiting',
)));
            exit;
        }

        $patient = PatientAccessHelper::requirePatientAccess(
            $id,
            $patient,
            esh_url('Patient', 'unified', ['status' => 'waiting'])
        );

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'hasta/waiting_form');
        include ThemeViewHelper::resolvePartial('footer');
    }
    
    //bekleyen hasta düzenlemesi
    public function bedit() {
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $patient = PatientAccessHelper::requirePatientAccess(
            $id,
            null,
            esh_url('Patient', 'unified', ['status' => 'waiting'])
        );
        
        $districts = (new Address())->getDistricts();

        $lists = (new Address())->getAdresListeleri($patient);
        foreach (['ilce', 'mahalle', 'sokak', 'kapino'] as $addrField) {
            if (!empty($lists[$addrField])) {
                $lists[$addrField] = str_replace(
                    'name="' . $addrField . '"',
                    'name="adres[0][' . $addrField . ']"',
                    $lists[$addrField]
                );
            }
        }
        if (!empty($lists['adres_aciklama'])) {
            $lists['adres_aciklama'] = str_replace(
                'name="adres_aciklama"',
                'name="adres[0][adres_aciklama]"',
                $lists['adres_aciklama']
            );
        }

        $decoded = json_decode($patient->diger_adres ?? '[]', true);
        $patient->diger_adres = is_array($decoded) ? $decoded : [];
        $patient->coords = Address::resolveCoordsForPatient($patient);
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'hasta/bedit');
        include ThemeViewHelper::resolvePartial('footer');
    
    }

    //hasta ilk kayıt
    public function ilkkayit() {
        
        $districts = (new Address())->getDistricts();

        $hastalikKurumId = AuthHelper::sessionIsSuperAdmin()
            ? TenantContext::sessionKurumFilter()
            : TenantContext::assignKurumIdForStore();
        $hastaliklar = (new Hastalik())->getList(
            $hastalikKurumId !== null && $hastalikKurumId > 0 ? (int) $hastalikKurumId : null
        );

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'hasta/ilkkayit');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function randevu_yogunluk_kontrol() {
        $tarihRaw = isset($_GET['tarih']) ? trim((string) $_GET['tarih']) : '';
        $zaman = \App\Helpers\ZamanDilimiHelper::clamp($_GET['zaman'] ?? null);

        $tarihYmd = DateHelper::trDateToYmd($tarihRaw);
        if ($tarihYmd === null) {
            http_response_code(400);
            echo '0';
            exit;
        }

        $db = (new Patient())->db;
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
    
    //ilk kayıt hasta kaydetme
    public function fsave() {
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $isAdminFsave = AuthHelper::sessionIsAdmin();
        $data = PostAllowlistHelper::pick($_POST, $this->patientFsaveAllowlistKeys($id, $isAdminFsave));
        if ($isAdminFsave && EsysComplianceHelper::enabled()) {
            $data = array_merge($data, EsysComplianceHelper::pickPatientRefs($data));
        }
        if ($isAdminFsave && UsbsComplianceHelper::enabled()) {
            $data = array_merge($data, UsbsComplianceHelper::pickPatientRefs($data));
        }
        $id = IdHelper::normalizeRequestId($data['id'] ?? null) ?? $id;

        $model = new Patient();
        $previousTc = '';
        if ($id !== null) {
            $existingPatient = PatientAccessHelper::requirePatientAccess(
                $id,
                null,
                esh_url('Patient', 'unified', ['status' => 'waiting'])
            );
            if (!$isAdminFsave) {
                foreach (['tckimlik', 'kayittarihi'] as $fsaveImmutableField) {
                    if (!array_key_exists($fsaveImmutableField, $data)) {
                        $data[$fsaveImmutableField] = (string) ($existingPatient->$fsaveImmutableField ?? '');
                    }
                }
            }
            if (!$model->load($id)) {
                $_SESSION['error'] = 'Hasta bulunamadı.';
                header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'waiting',
)));
                exit;
            }
            $previousTc = trim((string) ($model->tckimlik ?? ''));
        }

        $phoneErr = \App\Helpers\ValidationHelper::applyPhoneFields($data, $id === null);
        if ($phoneErr !== null) {
            $_SESSION['error'] = $phoneErr;
            header('Location: ' . ($id !== null ? esh_url('Patient', 'bedit', ['id' => $id]) : esh_url('Patient', 'ilkkayit')));
            exit;
        }

        if (!\App\Helpers\ValidationHelper::isTcLength11($data['tckimlik'] ?? '')) {
            $_SESSION['error'] = 'TC kimlik numarası tam 11 haneli olmalıdır.';
            header('Location: ' . ($id !== null ? esh_url('Patient', 'bedit', ['id' => $id]) : esh_url('Patient', 'ilkkayit')));
            exit;
        }

        $tcDigits = \App\Helpers\ValidationHelper::tcDigitsOnly($data['tckimlik'] ?? '');
        $tcUniqueErr = $this->validateTcGloballyUnique($tcDigits, $id);
        if ($tcUniqueErr !== null) {
            $_SESSION['error'] = $tcUniqueErr;
            header('Location: ' . ($id !== null ? esh_url('Patient', 'bedit', ['id' => $id]) : esh_url('Patient', 'ilkkayit')));
            exit;
        }

        if ($id !== null && AuthHelper::sessionIsSuperAdmin() && isset($_POST['kurum_id'])) {
            $newKid = (int) $_POST['kurum_id'];
            if ((int) ($model->kurum_id ?? 0) !== $newKid) {
                $err = PatientKurumTransfer::validate($model, $newKid);
                if ($err !== null) {
                    $_SESSION['error'] = $err;
                    header('Location: ' . esh_url('Patient', 'bedit', ['id' => $id]));
                    exit;
                }
                if ((string) ($model->pasif ?? '') === '0') {
                    $transferResult = PatientKurumTransfer::apply($model, $newKid, (AuthHelper::sessionUserId() ?? ''));
                    if ($transferResult === false) {
                        $_SESSION['error'] = 'Kurum değiştirilirken bir hata oluştu.';
                        header('Location: ' . esh_url('Patient', 'bedit', ['id' => $id]));
                        exit;
                    }
                    if (is_int($transferResult)) {
                        $_SESSION['success'] = 'Hasta hedef kuruma taşındı.';
                        header('Location: ' . esh_url('Patient', 'edit', ['id' => $transferResult]));
                        exit;
                    }
                } else {
                    $data['kurum_id'] = $newKid;
                }
            }
        }

        foreach (['dogumtarihi', 'kayittarihi', 'randevutarihi', 'pasiftarihi'] as $df) {
            if (!empty($data[$df])) {
                $raw = trim((string) $data[$df]);
                $ymd = DateHelper::trDateToYmd($raw);
                if ($ymd !== null) {
                    $data[$df] = $ymd;
                } else {
                    $ts = strtotime(str_replace(['.', '/'], '-', $raw));
                    $data[$df] = $ts ? date('Y-m-d', $ts) : null;
                }
            }
        }

        // İlk kayıt (ilkkayit): doğum tarihi, ana adres mahalle zorunlu
        if ($id === null) {
            $dogumVal = isset($data['dogumtarihi']) ? trim((string) $data['dogumtarihi']) : '';
            if ($dogumVal === '' || $dogumVal === '0000-00-00') {
                $_SESSION['error'] = 'Doğum tarihi zorunludur.';
                header('Location: ' . esh_url('Patient', 'ilkkayit'));
                exit;
            }
            $mahalleAna = isset($data['adres'][0]['mahalle']) ? trim((string) $data['adres'][0]['mahalle']) : '';
            if ($mahalleAna === '') {
                $_SESSION['error'] = 'Mahalle seçimi zorunludur (ana adres).';
                header('Location: ' . esh_url('Patient', 'ilkkayit'));
                exit;
            }
        }

        $kayitYmd = isset($data['kayittarihi']) ? trim((string) $data['kayittarihi']) : '';
        $randevuYmd = isset($data['randevutarihi']) ? trim((string) $data['randevutarihi']) : '';
        if ($kayitYmd !== '' && $randevuYmd !== '' && strcmp($randevuYmd, $kayitYmd) < 0) {
            $_SESSION['error'] = 'Ilk randevu tarihi, sisteme kayit tarihinden kucuk olamaz.';
            header('Location: ' . ($id !== null ? esh_url('Patient', 'bedit', ['id' => $id]) : esh_url('Patient', 'ilkkayit')));
            exit;
        }
        
        $pasifVal = null;
        $nakilPassiveCtx = ['error' => null, 'should_create' => false, 'hedef' => ''];
        if ($id === null) {
            $data['pasif'] = '-3';
        } elseif ($isAdminFsave) {
            $pasifVal = PatientCareHelper::normalizePasifForStore(
                $data['pasif'] ?? null,
                $model->pasif ?? -3,
                true
            );
            $data['pasif'] = (string) $pasifVal;
            if ($pasifVal === 1) {
                $data['pasifnedeni'] = PatientCareHelper::normalizePasifNedeniForStore($data['pasifnedeni'] ?? '1');
            } else {
                $data['pasifnedeni'] = PatientCareHelper::pasifNedeniForNonPassiveStore($pasifVal, $model);
                $data['pasiftarihi'] = null;
            }
            $nakilPassiveCtx = $this->resolveNakilPassiveContext($model, $data, $id);
            if ($nakilPassiveCtx['error'] !== null) {
                $_SESSION['error'] = $nakilPassiveCtx['error'];
                header('Location: ' . esh_url('Patient', 'bedit', ['id' => $id]));
                exit;
            }
        } else {
            unset($data['pasif'], $data['pasifnedeni'], $data['pasiftarihi']);
        }
        $data['cinsiyet'] = CinsiyetHelper::normalize($data['cinsiyet'] ?? null);
    
        // Çoklu Adres İşleme
        if (isset($data['adres']) && is_array($data['adres'])) {
            $anaIndex = $data['ana_adres_index'] ?? 0;
            $yedekler = [];

            foreach ($data['adres'] as $idx => $val) {
                if ($idx == $anaIndex) {
                    // Ana adresi modelin ana kolonlarına yaz
                    $data['ilce'] = $val['ilce'] ?? null;
                    $data['mahalle'] = $val['mahalle'] ?? null;
                    $data['sokak'] = $val['sokak'] ?? null;
                    $data['kapino'] = $val['kapino'] ?? null;
                    $data['adres_aciklama'] = $val['adres_aciklama'] ?? null;
                } else {
                    $yedekler[] = $val;
                }
            }
            $data['diger_adres'] = json_encode($yedekler, JSON_UNESCAPED_UNICODE);
        }

        $kurumIdForScope = $id !== null
            ? (int) ($data['kurum_id'] ?? $model->kurum_id ?? TenantContext::assignKurumIdForStore())
            : $this->resolveIlkkayitKurumId($id !== null ? esh_url('Patient', 'bedit', ['id' => $id]) : esh_url('Patient', 'ilkkayit'));
        $this->assertPatientAddressScopeInPost(
            $data,
            $kurumIdForScope,
            $id !== null ? esh_url('Patient', 'bedit', ['id' => $id]) : esh_url('Patient', 'ilkkayit')
        );

        if (array_key_exists('guvence', $data)) {
            $yupasIds = $this->getYupasGuvenceIds();
            $guvForYupas = (int) $data['guvence'];
            if (!in_array($guvForYupas, $yupasIds, true)) {
                $data['yupasno'] = '';
            }
        }

        unset($data['coords']);

        $model->bind($data);

        if ($id === null) {
            TenantStoreHelper::applyKurumIdToModel(
                $model,
                AuthHelper::sessionIsSuperAdmin() ? $kurumIdForScope : null
            );
        }
        
        // Kayıt işlemi
        $saved = $id !== null
            ? $this->storePatientWithTcCascade($model, $previousTc)
            : $model->store();
        if ($saved) {
            if ($id !== null) {
                AuditLogHelper::patientUpdate($model);
            } else {
                AuditLogHelper::patientCreate($model);
            }
            $this->patientStoreTcChecksumWarning($data);
            $this->processNakilAfterPassiveSave($model, $nakilPassiveCtx);
            $_SESSION['success'] = $id !== null
                ? 'Hasta bilgileri güncellendi.'
                : 'Hasta ön kaydı başarıyla oluşturuldu.';
            $redirectStatus = 'waiting';
            if ($id !== null && isset($pasifVal)) {
                if ($pasifVal === 0) {
                    $redirectStatus = 'active';
                } elseif ($pasifVal === 1) {
                    $redirectStatus = 'passive';
                }
            }
            header('Location: ' . esh_url('Patient', 'unified', ['status' => $redirectStatus]));
        } else {
            $_SESSION['error'] = 'Veritabanı hatası: Kayıt tamamlanamadı.';
            header('Location: ' . ($id !== null ? esh_url('Patient', 'bedit', ['id' => $id]) : esh_url('Patient', 'ilkkayit')));
        }
        
        exit;
    }

    //hasta düzenleme
    public function edit() {
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $patient = PatientAccessHelper::requirePatientAccess($id);

        AuditLogHelper::patientEditView($patient);

        extract($this->buildPatientEditFormContext($patient));
        
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'hasta/edit');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Hasta düzenleme formları (edit + view modalları) için ortak değişkenler.
     *
     * @return array<string, mixed>
     */
    private function buildPatientEditFormContext(object $patient): array
    {
        $id = (string) ($patient->id ?? '');

        if (is_array($patient->hastaliklar ?? null)) {
            $selectedHastalikIcds = Patient::parseHastalikCsvToIcds($patient->hastaliklar);
        } else {
            $selectedHastalikIcds = Patient::parseHastalikCsvToIcds((string) ($patient->hastaliklar ?? ''));
            $patient->hastaliklar = $selectedHastalikIcds;
        }

        $patientKurumId = (int) ($patient->kurum_id ?? 0);
        $hastalikModel = new Hastalik();
        $kurumHastalikBos = false;
        if ($patientKurumId > 0 && \App\Models\KurumHastalik::tableExists()) {
            $kurumHastalikBos = (new \App\Models\KurumHastalik())->countAssigned($patientKurumId) === 0;
        }

        $preselectedRows = [];
        if ($selectedHastalikIcds !== []) {
            $preselectedRows = $hastalikModel->ensureIcdsInList([], $selectedHastalikIcds);
        }

        $options = [];
        foreach ($preselectedRows as $hastalik) {
            $icd = Patient::normalizeHastalikIcd((string) ($hastalik->icd ?? ''));
            if ($icd === '') {
                continue;
            }
            $name = trim((string) ($hastalik->hastalikadi ?? ''));
            $label = $icd . ' — ' . $name;
            $options[] = \App\Helpers\FormHelper::makeOption($icd, $label);
        }

        $searchAssignedUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'Hastalik',
            'action' => 'searchAssigned',
        ]);
        $hastalikSelectAttrs = 'multiple="multiple" required="required"'
            . ' class="form-select esh-tomselect esh-hastalik-ajax"'
            . ' data-search-url="' . htmlspecialchars($searchAssignedUrl, ENT_QUOTES, 'UTF-8') . '"'
            . ' data-kurum-id="' . (int) $patientKurumId . '"'
            . ' data-placeholder="Tanı ara veya listeden seçin"';
        if ($kurumHastalikBos && $selectedHastalikIcds === []) {
            $hastalikSelectAttrs = str_replace('required="required"', '', $hastalikSelectAttrs) . ' disabled="disabled"';
        }

        $hast = \App\Helpers\FormHelper::selectList(
            $options,
            'hastaliklar[]',
            $hastalikSelectAttrs,
            'value',
            'text',
            $selectedHastalikIcds,
            null,
            false
        );

        $guvenceList = (new Guvence())->getList();
        if (!is_array($guvenceList)) {
            $guvenceList = [];
        }
        $yupasGuvenceIdCsv = implode(',', $this->getYupasGuvenceIds());

        $lists = (new Address())->getAdresListeleri($patient);
        foreach (['ilce', 'mahalle', 'sokak', 'kapino'] as $addrField) {
            if (!empty($lists[$addrField])) {
                $lists[$addrField] = str_replace(
                    'name="' . $addrField . '"',
                    'name="adres[0][' . $addrField . ']"',
                    $lists[$addrField]
                );
            }
        }
        if (!empty($lists['adres_aciklama'])) {
            $lists['adres_aciklama'] = str_replace(
                'name="adres_aciklama"',
                'name="adres[0][adres_aciklama]"',
                $lists['adres_aciklama']
            );
        }

        if (!is_array($patient->diger_adres ?? null)) {
            $patient->diger_adres = json_decode($patient->diger_adres ?? '[]', true);
        }
        if (!is_array($patient->diger_adres)) {
            $patient->diger_adres = [];
        }

        return [
            'guvence' => $guvenceList,
            'guvenceList' => $guvenceList,
            'lists' => $lists,
            'hast' => $hast,
            'yupasGuvenceIdCsv' => $yupasGuvenceIdCsv,
            'kurumHastalikBos' => $kurumHastalikBos,
            'hastalikSearchUrl' => $searchAssignedUrl,
        ];
    }

    /** Süper yönetici: hasta kurumu değiştirme formu. */
    public function changeKurum(): void
    {
        AuthHelper::requireSuperAdmin();
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }

        $patient = PatientAccessHelper::requirePatientAccess($id);
        $kurumlar = \App\Models\Kurum::tableExists() ? \App\Helpers\TenantContext::kurumListForScope(true) : [];
        $currentKurum = null;
        $kid = (int) ($patient->kurum_id ?? 0);
        if ($kid > 0) {
            $k = new \App\Models\Kurum();
            if ($k->load($kid)) {
                $currentKurum = $k;
            }
        }

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'hasta/change_kurum');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /** Süper yönetici: hasta kurumu kaydet. */
    public function storeKurum(): void
    {
        AuthHelper::requireSuperAdmin();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }

        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $newKid = isset($_POST['kurum_id']) ? (int) $_POST['kurum_id'] : 0;
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }

        $patient = new Patient();
        if (!$patient->load($id)) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }
        PatientAccessHelper::requirePatientAccess($id, $patient);
        $redirect = esh_url('Patient', 'changeKurum', ['id' => $id]);
        $err = PatientKurumTransfer::validate($patient, $newKid);
        if ($err !== null) {
            $_SESSION['error'] = $err;
            header('Location: ' . $redirect);
            exit;
        }

        if ((int) ($patient->kurum_id ?? 0) === $newKid) {
            $_SESSION['success'] = 'Kurum zaten seçili kurum ile aynı.';
            header('Location: ' . esh_url('Patient', 'edit', ['id' => $id]));
            exit;
        }

        $transferResult = PatientKurumTransfer::apply($patient, $newKid, (AuthHelper::sessionUserId() ?? ''));
        if ($transferResult === false) {
            $_SESSION['error'] = 'Kurum değiştirilirken bir hata oluştu.';
            header('Location: ' . $redirect);
            exit;
        }

        $newPatientId = is_int($transferResult) ? $transferResult : $id;
        $hedefKurum = new \App\Models\Kurum();
        $hedefAd = $hedefKurum->load($newKid) ? trim((string) ($hedefKurum->ad ?? '')) : '';
        $_SESSION['success'] = 'Hasta hedef kuruma taşındı.'
            . ($hedefAd !== '' ? ' Kurum: ' . $hedefAd . '.' : '');
        header('Location: ' . esh_url('Patient', 'edit', ['id' => $newPatientId]));
        exit;
    }
    
    //hasta bilgi gösterme
    public function view() {
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $hasta = PatientAccessHelper::requirePatientAccess($id);

        AuditLogHelper::patientView($hasta);
        
        $anaadres = (new Address())->getUserAddress($hasta->id);

        $mahallePlanMeta = null;
        if (!empty($hasta->mahalle)) {
            $kurumId = (int) ($hasta->kurum_id ?? 0);
            if ($kurumId > 0) {
                $mahallePlanMeta = (new MahallePlan())->getForHastaKurum($kurumId, (string) $hasta->mahalle);
            }
        }

        $hasta->diger_adres = json_decode($hasta->diger_adres ?? '[]', true);
        $hasta->coords = Address::resolveCoordsForPatient($hasta);
        
        $diger_adres = (new Address())->getUserOtherAddresses($hasta->diger_adres);
        
        $hastalikCardItems = (new Hastalik())->getUserHastaliklarWithIds($hasta->hastaliklar ?? '');
        $hastalikRaporVar = [];
        $hastalikRaporMeta = [];
        $hastaIlaclar = [];
        $ilacRaporOzet = [];
        $hastalikIcdsRapor = [];
        foreach ($hastalikCardItems as $hi) {
            $icd = Patient::normalizeHastalikIcd((string) ($hi->icd ?? ''));
            if ($icd !== '') {
                $hastalikIcdsRapor[] = $icd;
            }
        }
        $hastalikAdByIcdKimlik = [];
        foreach ($hastalikCardItems as $hi) {
            $icd = Patient::normalizeHastalikIcd((string) ($hi->icd ?? ''));
            if ($icd !== '') {
                $hastalikAdByIcdKimlik[$icd] = (string) ($hi->ad ?? '');
            }
        }
        if (AppSettings::isModuleEnabled('hasta_ilac_rapor')) {
            $ilacModel = new HastaIlac();
            $ilacModel->ensureTable();
            $hastaIlaclar = $ilacModel->getByHastaId((string) $hasta->id);
            foreach ($hastaIlaclar as $il) {
                $icd = Patient::normalizeHastalikIcd((string) ($il->hastalikicd ?? ''));
                $il->hastalik_adi = $icd !== '' ? ($hastalikAdByIcdKimlik[$icd] ?? $icd) : '';
            }

            $mergedIlacRaporIcds = Patient::mergedHastalikIcdsForIlacRapor($hasta);
            $tcDigits = preg_replace('/\D+/', '', (string) ($hasta->tckimlik ?? ''));
            if ($mergedIlacRaporIcds !== [] && ValidationHelper::isTcLength11($tcDigits)) {
                foreach ((new HastaIlacRapor())->getReportRowsForPatient($tcDigits, $mergedIlacRaporIcds) as $rr) {
                    $icd = Patient::normalizeHastalikIcd((string) ($rr->hastalik_icd ?? ''));
                    if ($icd === '') {
                        continue;
                    }
                    $raporEval = HastaIlacRaporStatusHelper::evaluateRow($rr);
                    if (!$raporEval['raporLu']) {
                        continue;
                    }
                    $hastalikRaporVar[$icd] = true;
                    $bitisTr = !empty($rr->bitistarihi) ? DateHelper::toTrOrEmpty((string) $rr->bitistarihi) : '';
                    $hastalikRaporMeta[$icd] = [
                        'bitis_tr' => $bitisTr,
                        'raporyeri' => (int) ($rr->raporyeri ?? 0),
                        'status' => $raporEval['status'],
                    ];
                    if ($raporEval['raporFlag']) {
                        $ilacRaporOzet[] = $rr;
                    }
                }
            }
        } elseif ($hastalikIcdsRapor !== []) {
            $tcDigits = preg_replace('/\D+/', '', (string) ($hasta->tckimlik ?? ''));
            if (ValidationHelper::isTcLength11($tcDigits)) {
                foreach ((new HastaIlacRapor())->getReportRowsForPatient($tcDigits, $hastalikIcdsRapor) as $rr) {
                    $icd = Patient::normalizeHastalikIcd((string) ($rr->hastalik_icd ?? ''));
                    if ($icd === '' || (int) ($rr->rapor_id ?? 0) < 1) {
                        continue;
                    }
                    $raporEval = HastaIlacRaporStatusHelper::evaluateRow($rr);
                    if ($raporEval['raporLu']) {
                        $hastalikRaporVar[$icd] = true;
                        $bitisTr = !empty($rr->bitistarihi) ? DateHelper::toTrOrEmpty((string) $rr->bitistarihi) : '';
                        $hastalikRaporMeta[$icd] = [
                            'bitis_tr' => $bitisTr,
                            'raporyeri' => (int) ($rr->raporyeri ?? 0),
                            'status' => $raporEval['status'],
                        ];
                    }
                }
            }
        }
        
        $guvenceAdi = (new Guvence())->getUserGuvence($hasta->guvence);
        
        $pasif_nedenleri_ikonlu = PatientCareHelper::pasifDosyaNedeniDefinitionsStringKeys();
        $woundModel = new WoundPhoto();
        $woundModel->ensureTable();
        $woundPhotoCount = 0;
        $woundPhotosPreview = [];
        if (\App\Helpers\PatientClinicalFlagsHelper::isWoundPhotosModuleEnabled($hasta)) {
            $woundPhotosAll = $woundModel->getByHastaId((string) $hasta->id);
            $woundPhotoCount = count($woundPhotosAll);
            $woundPhotosPreview = array_slice($woundPhotosAll, 0, 3);
        }

        $bradenModel = new BradenAssessment();
        $bradenModel->ensureTable();
        $bradenLatest = null;
        $bradenCount = 0;
        if (\App\Helpers\PatientClinicalFlagsHelper::isBradenModuleEnabled($hasta)) {
            $bradenLatest = $bradenModel->getLatestByHastaId((string) $hasta->id);
            $bradenCount = $bradenModel->countByHastaId((string) $hasta->id);
        }

        $itakiModel = new ItakiAssessment();
        $itakiModel->ensureTable();
        $itakiLatest = null;
        $itakiCount = 0;
        if (\App\Helpers\PatientClinicalFlagsHelper::isItakiModuleEnabled($hasta)) {
            $itakiLatest = $itakiModel->getLatestByHastaId((string) $hasta->id);
            $itakiCount = $itakiModel->countByHastaId((string) $hasta->id);
        }

        $harizmiModel = new HarizmiAssessment();
        $harizmiModel->ensureTable();
        $harizmiLatest = null;
        $harizmiCount = 0;
        if (\App\Helpers\PatientClinicalFlagsHelper::isHarizmiModuleEnabled($hasta)) {
            $harizmiLatest = $harizmiModel->getLatestByHastaId((string) $hasta->id);
            $harizmiCount = $harizmiModel->countByHastaId((string) $hasta->id);
        }

        $mnaModel = new MnaAssessment();
        $mnaModel->ensureTable();
        $mnaLatest = null;
        $mnaCount = 0;
        if (\App\Helpers\PatientClinicalFlagsHelper::isMnaModuleEnabled($hasta)) {
            $mnaLatest = $mnaModel->getLatestByHastaId((string) $hasta->id);
            $mnaCount = $mnaModel->countByHastaId((string) $hasta->id);
        }

        $barthelModel = new BarthelAssessment();
        $barthelModel->ensureTable();
        $barthelLatest = null;
        $barthelCount = 0;
        if (\App\Helpers\PatientClinicalFlagsHelper::isBarthelModuleEnabled($hasta)) {
            $barthelLatest = $barthelModel->getLatestByHastaId((string) $hasta->id);
            $barthelCount = $barthelModel->countByHastaId((string) $hasta->id);
        }

        $clinicalDecisionAlerts = [];
        if (ClinicalDecisionSupportHelper::showOnPatientDetail()) {
            $cdsAssessments = [
                'braden' => $bradenLatest,
                'itaki' => $itakiLatest,
                'harizmi' => $harizmiLatest,
                'mna' => $mnaLatest,
                'barthel' => $barthelLatest,
            ];
            $cdsDaysSince = ClinicalDecisionSupportHelper::daysSinceLastCompletedVisit(
                isset($hasta->son_yapilan_tarih) ? (string) $hasta->son_yapilan_tarih : null
            );
            $clinicalDecisionAlerts = ClinicalDecisionSupportHelper::evaluateAlerts(
                $hasta,
                $cdsAssessments,
                $cdsDaysSince
            );
            $ackCodes = $this->loadAcknowledgedClinicalAlertCodes((string) $hasta->id);
            if ($ackCodes !== []) {
                $clinicalDecisionAlerts = array_values(array_filter(
                    $clinicalDecisionAlerts,
                    static function (array $alert) use ($ackCodes): bool {
                        $code = (string) ($alert['code'] ?? '');
                        return $code === '' || !in_array($code, $ackCodes, true);
                    }
                ));
            }
        }
        $clinicalDecisionPatientId = (string) $hasta->id;

        $pasifnedeni = "";
        if ($hasta->pasif) {
            if ($hasta->pasif == '1') {
                $pasifnedeni = PatientCareHelper::pasifDosyaNedeniLabelByCode($hasta->pasifnedeni ?? '');
            }
            else if ($hasta->pasif == '-1') { $pasifnedeni = 'Muhtemel Vefat'; }
            else if ($hasta->pasif == '5') { $pasifnedeni = 'Silinmiş Hasta'; }
            else if ($hasta->pasif == '-3') {
                $pasifnedeni = PatientKurumTransfer::isWaitingFromNakil($hasta)
                    ? 'Başka Kuruma Nakil (Bekleyen)'
                    : 'Bekleyen Hasta';
            }
            else if ($hasta->pasif == '4') { $pasifnedeni = 'Arafta Hasta'; }
        }

        $pasifDosyaKapali = Patient::isPasifKapali($hasta->pasif ?? null);

        $patient = $hasta;
        extract($this->buildPatientEditFormContext($patient));
        $eshPatientEditModalsReady = true;
        $GLOBALS['eshPatientViewEditModals'] = true;

        $hastaKurum = null;
        if (AuthHelper::sessionIsSuperAdmin() && \App\Models\Kurum::tableExists()) {
            $hastaKurumId = (int) ($hasta->kurum_id ?? 0);
            if ($hastaKurumId > 0) {
                $hastaKurumModel = new \App\Models\Kurum();
                if ($hastaKurumModel->load($hastaKurumId)) {
                    $hastaKurum = $hastaKurumModel;
                }
            }
        }

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'hasta/detail');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function acknowledgeClinicalAlert(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }
        $patientId = IdHelper::normalizeRequestId($_POST['patient_id'] ?? null);
        $code = trim((string) ($_POST['alert_code'] ?? ''));
        if ($patientId === null || $code === '') {
            $_SESSION['error'] = 'Geçersiz klinik uyarı isteği.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }
        $patient = PatientAccessHelper::requirePatientAccess($patientId);
        $db = \App\Core\Database::getInstance();
        $ok = $db->executePrepared(
            'INSERT INTO #__cds_ack (hasta_id, kurum_id, alert_code, ack_by_user_id, ack_note, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())',
            [
                $patientId,
                (int) ($patient->kurum_id ?? 1),
                substr($code, 0, 80),
                (AuthHelper::sessionUserId() ?? ''),
                trim((string) ($_POST['ack_note'] ?? '')),
            ]
        );
        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Klinik uyarı onaylandı.'
            : 'Klinik uyarı onaylanamadı.';
        header('Location: ' . esh_url('Patient', 'view', ['id' => $patientId]));
        exit;
    }

    /**
     * @return list<string>
     */
    private function loadAcknowledgedClinicalAlertCodes(string $patientId): array
    {
        if ($patientId === null) {
            return [];
        }
        $db = \App\Core\Database::getInstance();
        $rows = $db->fetchObjectListPrepared(
            'SELECT DISTINCT alert_code
             FROM #__cds_ack
             WHERE hasta_id = ?
             ORDER BY id DESC',
            [$patientId]
        );
        if (!is_array($rows)) {
            return [];
        }
        $codes = [];
        foreach ($rows as $row) {
            $code = trim((string) ($row->alert_code ?? ''));
            if ($code !== '' && !in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    /**
     * Hasta yara fotoğrafları — tam galeri, yükleme ve karşılaştırma.
     */
    public function wounds() {
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta kaydı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $hasta = PatientAccessHelper::requirePatientAccess($id);

        if (!\App\Helpers\PatientClinicalFlagsHelper::isWoundPhotosModuleEnabled($hasta)) {
            $_SESSION['error'] = 'Yara fotoğrafları modülü yalnızca aktif bası yarası işaretli hastalarda kullanılabilir.';
            header('Location: ' . esh_url('Patient', 'view', ['id' => $id]));
            exit;
        }

        $woundModel = new WoundPhoto();
        $woundModel->ensureTable();
        $woundPhotos = $woundModel->getByHastaId($id);
        $pasifDosyaKapali = Patient::isPasifKapali($hasta->pasif ?? null);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'hasta/wounds');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Hasta Braden ölçeği — değerlendirme formu ve geçmiş kayıtlar.
     */
    public function braden() {
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta kaydı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $hasta = PatientAccessHelper::requirePatientAccess($id);
        $this->denyBradenIfNotEnabled($hasta, $id);

        $bradenModel = new BradenAssessment();
        $bradenModel->ensureTable();
        $bradenAssessments = $bradenModel->getByHastaId($id);
        $bradenFields = BradenScaleHelper::getFormFieldDefinitions();
        $bradenLatest = $bradenModel->getLatestByHastaId($id);
        $pasifDosyaKapali = Patient::isPasifKapali($hasta->pasif ?? null);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'hasta/braden');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function saveBraden() {
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta kaydı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $patient = PatientAccessHelper::requirePatientAccess($id);
        $this->denyBradenIfNotEnabled($patient, $id);

        if (Patient::isPasifKapali($patient->pasif ?? null)) {
            $_SESSION['error'] = 'Pasif dosyada Braden değerlendirmesi kaydedilemez.';
            header('Location: ' . $this->patientBradenUrl($id));
            exit;
        }

        $tarih = $this->normalizeDateInput($_POST['degerlendirme_tarihi'] ?? '');
        if ($tarih === null) {
            $_SESSION['error'] = 'Geçerli bir değerlendirme tarihi girin.';
            header('Location: ' . $this->patientBradenUrl($id));
            exit;
        }

        $scores = BradenScaleHelper::sanitizeScores($_POST);
        $total = BradenScaleHelper::calculateTotal($scores);
        $risk = BradenScaleHelper::resolveRisk($total);

        $bradenModel = new BradenAssessment();
        $bradenModel->ensureTable();
        $row = new BradenAssessment();
        $row->set('kurum_id', (int) ($patient->kurum_id ?? TenantContext::assignKurumIdForStore()));
        $row->set('hasta_id', $id);
        $row->set('degerlendirme_tarihi', $tarih);
        foreach ($scores as $key => $val) {
            $row->set($key, $val);
        }
        $row->set('toplam_skor', $total);
        $row->set('risk_duzeyi', $risk['label']);
        $notlar = trim((string) ($_POST['notlar'] ?? ''));
        $row->set('notlar', $notlar !== '' ? $notlar : null);
        $row->set('kaydeden_id', AuthHelper::sessionUserId());

        if ($row->store()) {
            $_SESSION['success'] = 'Braden değerlendirmesi kaydedildi.';
        } else {
            $_SESSION['error'] = 'Braden değerlendirmesi kaydedilemedi.';
        }

        header('Location: ' . $this->patientBradenUrl($id));
        exit;
    }

    public function deleteBraden() {
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $assessmentId = IdHelper::normalizeRequestId($_POST['assessment_id'] ?? null);
        if ($id === null || $assessmentId === null) {
            $_SESSION['error'] = 'Geçersiz silme isteği.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $patient = PatientAccessHelper::requirePatientAccess($id);
        $this->denyBradenIfNotEnabled($patient, $id);

        if (Patient::isPasifKapali($patient->pasif ?? null)) {
            $_SESSION['error'] = 'Pasif dosyada Braden kaydı silinemez.';
            header('Location: ' . $this->patientBradenUrl($id));
            exit;
        }

        $bradenModel = new BradenAssessment();
        $bradenModel->ensureTable();
        $assessment = $bradenModel->getById($assessmentId);
        if (!$assessment || !IdHelper::idsMatch($assessment->hasta_id ?? null, $id)) {
            $_SESSION['error'] = 'Braden kaydı bulunamadı.';
            header('Location: ' . $this->patientBradenUrl($id));
            exit;
        }

        $ok = $bradenModel->delete($assessmentId);
        $_SESSION['success'] = $ok ? 'Braden değerlendirmesi silindi.' : 'Braden kaydı silinemedi.';
        header('Location: ' . $this->patientBradenUrl($id));
        exit;
    }

    /**
     * Hasta İTAKİ II düşme riski ölçeği.
     */
    public function itaki() {
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta kaydı.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }

        $hasta = PatientAccessHelper::requirePatientAccess($id);
        $this->denyItakiIfNotEnabled($hasta, $id);

        $itakiModel = new ItakiAssessment();
        $itakiModel->ensureTable();
        $itakiAssessments = $itakiModel->getByHastaId($id);
        $itakiParameters = ItakiScaleHelper::getParameterDefinitions();
        $itakiLatest = $itakiModel->getLatestByHastaId($id);
        $pasifDosyaKapali = Patient::isPasifKapali($hasta->pasif ?? null);
        $patientAge = \App\Helpers\PatientClinicalFlagsHelper::isItakiModuleEnabled($hasta)
            ? (int) \App\Helpers\DateHelper::calculateAge((string) ($hasta->dogumtarihi ?? ''))
            : -1;
        $suggestedAgeOption = $patientAge >= 0 ? ItakiScaleHelper::suggestAgeOptionId($patientAge) : null;

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'hasta/itaki');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function saveItaki() {
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta kaydı.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }

        $patient = PatientAccessHelper::requirePatientAccess($id);
        $this->denyItakiIfNotEnabled($patient, $id);

        if (Patient::isPasifKapali($patient->pasif ?? null)) {
            $_SESSION['error'] = 'Pasif dosyada İTAKİ değerlendirmesi kaydedilemez.';
            header('Location: ' . $this->patientItakiUrl($id));
            exit;
        }

        $tarih = $this->normalizeDateInput($_POST['degerlendirme_tarihi'] ?? '');
        if ($tarih === null) {
            $_SESSION['error'] = 'Geçerli bir değerlendirme tarihi girin.';
            header('Location: ' . $this->patientItakiUrl($id));
            exit;
        }

        $rawSelections = $this->collectFallRiskSelections($_POST);
        $selections = ItakiScaleHelper::sanitizeSelections($rawSelections);
        $total = ItakiScaleHelper::calculateTotal($selections);
        $risk = ItakiScaleHelper::resolveRisk($total);
        $gerekce = ItakiScaleHelper::sanitizeEvaluationReason($_POST['degerlendirme_gerekcesi'] ?? 1);

        $itakiModel = new ItakiAssessment();
        $itakiModel->ensureTable();
        $row = new ItakiAssessment();
        $row->set('kurum_id', (int) ($patient->kurum_id ?? TenantContext::assignKurumIdForStore()));
        $row->set('hasta_id', $id);
        $row->set('degerlendirme_tarihi', $tarih);
        $row->set('degerlendirme_gerekcesi', $gerekce);
        $row->set('secimler_json', ItakiScaleHelper::encodeSelections($selections));
        $row->set('toplam_skor', $total);
        $row->set('risk_duzeyi', $risk['label']);
        $notlar = trim((string) ($_POST['notlar'] ?? ''));
        $row->set('notlar', $notlar !== '' ? $notlar : null);
        $row->set('kaydeden_id', AuthHelper::sessionUserId());

        if ($row->store()) {
            $_SESSION['success'] = 'İTAKİ değerlendirmesi kaydedildi.';
        } else {
            $_SESSION['error'] = 'İTAKİ değerlendirmesi kaydedilemedi.';
        }

        header('Location: ' . $this->patientItakiUrl($id));
        exit;
    }

    public function deleteItaki() {
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $assessmentId = IdHelper::normalizeRequestId($_POST['assessment_id'] ?? null);
        if ($id === null || $assessmentId === null) {
            $_SESSION['error'] = 'Geçersiz silme isteği.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }

        $patient = PatientAccessHelper::requirePatientAccess($id);
        $this->denyItakiIfNotEnabled($patient, $id);

        if (Patient::isPasifKapali($patient->pasif ?? null)) {
            $_SESSION['error'] = 'Pasif dosyada İTAKİ kaydı silinemez.';
            header('Location: ' . $this->patientItakiUrl($id));
            exit;
        }

        $itakiModel = new ItakiAssessment();
        $itakiModel->ensureTable();
        $assessment = $itakiModel->getById($assessmentId);
        if (!$assessment || !IdHelper::idsMatch($assessment->hasta_id ?? null, $id)) {
            $_SESSION['error'] = 'İTAKİ kaydı bulunamadı.';
            header('Location: ' . $this->patientItakiUrl($id));
            exit;
        }

        $ok = $itakiModel->delete($assessmentId);
        $_SESSION['success'] = $ok ? 'İTAKİ değerlendirmesi silindi.' : 'İTAKİ kaydı silinemedi.';
        header('Location: ' . $this->patientItakiUrl($id));
        exit;
    }

    /**
     * Hasta Harizmi II düşme riski ölçeği.
     */
    public function harizmi() {
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta kaydı.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }

        $hasta = PatientAccessHelper::requirePatientAccess($id);
        $this->denyHarizmiIfNotEnabled($hasta, $id);

        $harizmiModel = new HarizmiAssessment();
        $harizmiModel->ensureTable();
        $harizmiAssessments = $harizmiModel->getByHastaId($id);
        $harizmiParameters = HarizmiScaleHelper::getParameterDefinitions();
        $harizmiLatest = $harizmiModel->getLatestByHastaId($id);
        $pasifDosyaKapali = Patient::isPasifKapali($hasta->pasif ?? null);
        $patientAge = \App\Helpers\PatientClinicalFlagsHelper::isHarizmiModuleEnabled($hasta)
            ? (int) \App\Helpers\DateHelper::calculateAge((string) ($hasta->dogumtarihi ?? ''))
            : -1;
        $suggestedAgeOption = $patientAge >= 0 ? HarizmiScaleHelper::suggestAgeOptionId($patientAge) : null;

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'hasta/harizmi');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function saveHarizmi() {
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta kaydı.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }

        $patient = PatientAccessHelper::requirePatientAccess($id);
        $this->denyHarizmiIfNotEnabled($patient, $id);

        if (Patient::isPasifKapali($patient->pasif ?? null)) {
            $_SESSION['error'] = 'Pasif dosyada Harizmi değerlendirmesi kaydedilemez.';
            header('Location: ' . $this->patientHarizmiUrl($id));
            exit;
        }

        $tarih = $this->normalizeDateInput($_POST['degerlendirme_tarihi'] ?? '');
        if ($tarih === null) {
            $_SESSION['error'] = 'Geçerli bir değerlendirme tarihi girin.';
            header('Location: ' . $this->patientHarizmiUrl($id));
            exit;
        }

        $rawSelections = $this->collectFallRiskSelections($_POST);
        $selections = HarizmiScaleHelper::sanitizeSelections($rawSelections);
        $total = HarizmiScaleHelper::calculateTotal($selections);
        $risk = HarizmiScaleHelper::resolveRisk($total);
        $gerekce = HarizmiScaleHelper::sanitizeEvaluationReason($_POST['degerlendirme_gerekcesi'] ?? 1);

        $harizmiModel = new HarizmiAssessment();
        $harizmiModel->ensureTable();
        $row = new HarizmiAssessment();
        $row->set('kurum_id', (int) ($patient->kurum_id ?? TenantContext::assignKurumIdForStore()));
        $row->set('hasta_id', $id);
        $row->set('degerlendirme_tarihi', $tarih);
        $row->set('degerlendirme_gerekcesi', $gerekce);
        $row->set('secimler_json', HarizmiScaleHelper::encodeSelections($selections));
        $row->set('toplam_skor', $total);
        $row->set('risk_duzeyi', $risk['label']);
        $notlar = trim((string) ($_POST['notlar'] ?? ''));
        $row->set('notlar', $notlar !== '' ? $notlar : null);
        $row->set('kaydeden_id', AuthHelper::sessionUserId());

        if ($row->store()) {
            $_SESSION['success'] = 'Harizmi değerlendirmesi kaydedildi.';
        } else {
            $_SESSION['error'] = 'Harizmi değerlendirmesi kaydedilemedi.';
        }

        header('Location: ' . $this->patientHarizmiUrl($id));
        exit;
    }

    public function deleteHarizmi() {
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $assessmentId = IdHelper::normalizeRequestId($_POST['assessment_id'] ?? null);
        if ($id === null || $assessmentId === null) {
            $_SESSION['error'] = 'Geçersiz silme isteği.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }

        $patient = PatientAccessHelper::requirePatientAccess($id);
        $this->denyHarizmiIfNotEnabled($patient, $id);

        if (Patient::isPasifKapali($patient->pasif ?? null)) {
            $_SESSION['error'] = 'Pasif dosyada Harizmi kaydı silinemez.';
            header('Location: ' . $this->patientHarizmiUrl($id));
            exit;
        }

        $harizmiModel = new HarizmiAssessment();
        $harizmiModel->ensureTable();
        $assessment = $harizmiModel->getById($assessmentId);
        if (!$assessment || !IdHelper::idsMatch($assessment->hasta_id ?? null, $id)) {
            $_SESSION['error'] = 'Harizmi kaydı bulunamadı.';
            header('Location: ' . $this->patientHarizmiUrl($id));
            exit;
        }

        $ok = $harizmiModel->delete($assessmentId);
        $_SESSION['success'] = $ok ? 'Harizmi değerlendirmesi silindi.' : 'Harizmi kaydı silinemedi.';
        header('Location: ' . $this->patientHarizmiUrl($id));
        exit;
    }

    /**
     * Hasta MNA-SF beslenme değerlendirmesi.
     */
    public function mna() {
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta kaydı.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }

        $hasta = PatientAccessHelper::requirePatientAccess($id);
        $this->denyMnaIfNotEnabled($hasta, $id);

        $mnaModel = new MnaAssessment();
        $mnaModel->ensureTable();
        $mnaAssessments = $mnaModel->getByHastaId($id);
        $mnaFields = MnaScaleHelper::getFieldDefinitions();
        $mnaBmiField = MnaScaleHelper::getBmiFieldDefinition();
        $mnaCalfField = MnaScaleHelper::getCalfFieldDefinition();
        $mnaLatest = $mnaModel->getLatestByHastaId($id);
        $pasifDosyaKapali = Patient::isPasifKapali($hasta->pasif ?? null);
        $mnaBmiSuggest = MnaScaleHelper::suggestBmiFromPatient($hasta);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'hasta/mna');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function saveMna() {
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta kaydı.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }

        $patient = PatientAccessHelper::requirePatientAccess($id);
        $this->denyMnaIfNotEnabled($patient, $id);

        if (Patient::isPasifKapali($patient->pasif ?? null)) {
            $_SESSION['error'] = 'Pasif dosyada MNA değerlendirmesi kaydedilemez.';
            header('Location: ' . $this->patientMnaUrl($id));
            exit;
        }

        $tarih = $this->normalizeDateInput($_POST['degerlendirme_tarihi'] ?? '');
        if ($tarih === null) {
            $_SESSION['error'] = 'Geçerli bir değerlendirme tarihi girin.';
            header('Location: ' . $this->patientMnaUrl($id));
            exit;
        }

        $scores = MnaScaleHelper::sanitizeScores($_POST);
        $olcumTipi = MnaScaleHelper::sanitizeOlcumTipi($_POST['bmi_olcum_tipi'] ?? MnaScaleHelper::OLcum_BMI);
        $bmiRaw = $olcumTipi === MnaScaleHelper::OLcum_BALDIR
            ? ($_POST['bmi_skor_calf'] ?? 0)
            : ($_POST['bmi_skor'] ?? 0);
        $bmiSkor = MnaScaleHelper::sanitizeBmiScore($bmiRaw, $olcumTipi);
        $total = MnaScaleHelper::calculateTotal($scores, $bmiSkor);
        $status = MnaScaleHelper::resolveStatus($total);

        $mnaModel = new MnaAssessment();
        $mnaModel->ensureTable();
        $row = new MnaAssessment();
        $row->set('kurum_id', (int) ($patient->kurum_id ?? TenantContext::assignKurumIdForStore()));
        $row->set('hasta_id', $id);
        $row->set('degerlendirme_tarihi', $tarih);
        foreach ($scores as $key => $val) {
            $row->set($key, $val);
        }
        $row->set('bmi_olcum_tipi', $olcumTipi);
        $row->set('bmi_skor', $bmiSkor);
        $row->set('toplam_skor', $total);
        $row->set('durum_duzeyi', $status['label']);
        $notlar = trim((string) ($_POST['notlar'] ?? ''));
        $row->set('notlar', $notlar !== '' ? $notlar : null);
        $row->set('kaydeden_id', AuthHelper::sessionUserId());

        if ($row->store()) {
            $_SESSION['success'] = 'MNA değerlendirmesi kaydedildi.';
        } else {
            $_SESSION['error'] = 'MNA değerlendirmesi kaydedilemedi.';
        }

        header('Location: ' . $this->patientMnaUrl($id));
        exit;
    }

    public function deleteMna() {
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $assessmentId = IdHelper::normalizeRequestId($_POST['assessment_id'] ?? null);
        if ($id === null || $assessmentId === null) {
            $_SESSION['error'] = 'Geçersiz silme isteği.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }

        $patient = PatientAccessHelper::requirePatientAccess($id);
        $this->denyMnaIfNotEnabled($patient, $id);

        if (Patient::isPasifKapali($patient->pasif ?? null)) {
            $_SESSION['error'] = 'Pasif dosyada MNA kaydı silinemez.';
            header('Location: ' . $this->patientMnaUrl($id));
            exit;
        }

        $mnaModel = new MnaAssessment();
        $mnaModel->ensureTable();
        $assessment = $mnaModel->getById($assessmentId);
        if (!$assessment || !IdHelper::idsMatch($assessment->hasta_id ?? null, $id)) {
            $_SESSION['error'] = 'MNA kaydı bulunamadı.';
            header('Location: ' . $this->patientMnaUrl($id));
            exit;
        }

        $ok = $mnaModel->delete($assessmentId);
        $_SESSION['success'] = $ok ? 'MNA değerlendirmesi silindi.' : 'MNA kaydı silinemedi.';
        header('Location: ' . $this->patientMnaUrl($id));
        exit;
    }

    /**
     * Hasta Barthel indeksi — düzenleme formu (edit sayfasındaki bölümle aynı).
     */
    public function barthel() {
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta kaydı.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }

        $hasta = PatientAccessHelper::requirePatientAccess($id);
        $this->denyBarthelIfNotEnabled($hasta, $id);

        $barthelModel = new BarthelAssessment();
        $barthelModel->ensureTable();
        $barthelAssessments = $barthelModel->getByHastaId($id);
        $barthelFields = BarthelScaleHelper::getFieldDefinitions();
        $barthelLatest = $barthelModel->getLatestByHastaId($id);
        $pasifDosyaKapali = Patient::isPasifKapali($hasta->pasif ?? null);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'hasta/barthel');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function saveBarthel() {
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta kaydı.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }

        $patient = PatientAccessHelper::requirePatientAccess($id);
        $this->denyBarthelIfNotEnabled($patient, $id);

        if (Patient::isPasifKapali($patient->pasif ?? null)) {
            $_SESSION['error'] = 'Pasif dosyada Barthel değerlendirmesi kaydedilemez.';
            header('Location: ' . $this->patientBarthelUrl($id));
            exit;
        }

        $tarih = $this->normalizeDateInput($_POST['degerlendirme_tarihi'] ?? '');
        if ($tarih === null) {
            $_SESSION['error'] = 'Geçerli bir değerlendirme tarihi girin.';
            header('Location: ' . $this->patientBarthelUrl($id));
            exit;
        }

        $scores = BarthelScaleHelper::sanitizeScores($_POST);
        $total = BarthelScaleHelper::calculateTotal($scores);
        $status = BarthelScaleHelper::resolveDependencyLevel($total);

        $barthelModel = new BarthelAssessment();
        $barthelModel->ensureTable();
        $row = new BarthelAssessment();
        $row->set('kurum_id', (int) ($patient->kurum_id ?? TenantContext::assignKurumIdForStore()));
        $row->set('hasta_id', $id);
        $row->set('degerlendirme_tarihi', $tarih);
        foreach ($scores as $key => $val) {
            $row->set($key, $val);
        }
        $row->set('toplam_skor', $total);
        $row->set('bagimlilik_duzeyi', $status['label']);
        $notlar = trim((string) ($_POST['notlar'] ?? ''));
        $row->set('notlar', $notlar !== '' ? $notlar : null);
        $row->set('kaydeden_id', AuthHelper::sessionUserId());

        if ($row->store()) {
            $_SESSION['success'] = 'Barthel değerlendirmesi kaydedildi.';
        } else {
            $_SESSION['error'] = 'Barthel değerlendirmesi kaydedilemedi.';
        }

        header('Location: ' . $this->patientBarthelUrl($id));
        exit;
    }

    public function deleteBarthel() {
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $assessmentId = IdHelper::normalizeRequestId($_POST['assessment_id'] ?? null);
        if ($id === null || $assessmentId === null) {
            $_SESSION['error'] = 'Geçersiz silme isteği.';
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'active']));
            exit;
        }

        $patient = PatientAccessHelper::requirePatientAccess($id);
        $this->denyBarthelIfNotEnabled($patient, $id);

        if (Patient::isPasifKapali($patient->pasif ?? null)) {
            $_SESSION['error'] = 'Pasif dosyada Barthel kaydı silinemez.';
            header('Location: ' . $this->patientBarthelUrl($id));
            exit;
        }

        $barthelModel = new BarthelAssessment();
        $barthelModel->ensureTable();
        $assessment = $barthelModel->getById($assessmentId);
        if (!$assessment || !IdHelper::idsMatch($assessment->hasta_id ?? null, $id)) {
            $_SESSION['error'] = 'Barthel kaydı bulunamadı.';
            header('Location: ' . $this->patientBarthelUrl($id));
            exit;
        }

        $ok = $barthelModel->delete($assessmentId);
        $_SESSION['success'] = $ok ? 'Barthel değerlendirmesi silindi.' : 'Barthel kaydı silinemedi.';
        header('Location: ' . $this->patientBarthelUrl($id));
        exit;
    }

    public function uploadWoundPhoto() {
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta kaydı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $patient = PatientAccessHelper::requirePatientAccess($id);

        if (!\App\Helpers\PatientClinicalFlagsHelper::isWoundPhotosModuleEnabled($patient)) {
            $_SESSION['error'] = 'Yara fotoğrafları modülü yalnızca aktif bası yarası işaretli hastalarda kullanılabilir.';
            header('Location: ' . esh_url('Patient', 'view', ['id' => $id]));
            exit;
        }

        if (Patient::isPasifKapali($patient->pasif ?? null)) {
            $_SESSION['error'] = 'Pasif dosyada yara fotoğrafı yüklenemez.';
            header('Location: ' . $this->patientWoundsUrl($id));
            exit;
        }

        if (!isset($_FILES['wound_photo']) || !is_array($_FILES['wound_photo'])) {
            $_SESSION['error'] = 'Fotoğraf seçilmedi.';
            header('Location: ' . $this->patientWoundsUrl($id));
            exit;
        }

        $files = $this->normalizeUploadFiles($_FILES['wound_photo']);
        if ($files === []) {
            $_SESSION['error'] = 'Geçerli bir fotoğraf bulunamadı.';
            header('Location: ' . $this->patientWoundsUrl($id));
            exit;
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $maxBytes = 8 * 1024 * 1024; // 8 MB

        $folder = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'wounds';
        if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
            $_SESSION['error'] = 'Yükleme klasörü oluşturulamadı.';
            header('Location: ' . $this->patientWoundsUrl($id));
            exit;
        }

        $aciklama = substr(trim((string) ($_POST['aciklama'] ?? '')), 0, 255) ?: null;
        $yaraBolgesi = substr(trim((string) ($_POST['yara_bolgesi'] ?? '')), 0, 100) ?: null;
        $yaraEvresi = substr(trim((string) ($_POST['yara_evresi'] ?? '')), 0, 50) ?: null;
        $cekimTarihi = $this->normalizeDateTimeInput($_POST['cekim_tarihi'] ?? '');

        $saved = 0;
        $errors = [];
        $model = new WoundPhoto();
        $model->ensureTable();

        foreach ($files as $file) {
            if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errors[] = 'Hata kodu: ' . (int) ($file['error'] ?? -1);
                continue;
            }
            $size = (int) ($file['size'] ?? 0);
            if ($size <= 0 || $size > $maxBytes) {
                $errors[] = 'Boyut limiti: ' . substr((string) ($file['name'] ?? ''), 0, 40);
                continue;
            }

            $tmp = (string) ($file['tmp_name'] ?? '');
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? (string) finfo_file($finfo, $tmp) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
            if (!isset($allowed[$mime])) {
                $errors[] = 'Desteklenmeyen tip: ' . substr((string) ($file['name'] ?? ''), 0, 40);
                continue;
            }

            $ext = $allowed[$mime];
            $safeName = 'wound_' . $id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $fullPath = $folder . DIRECTORY_SEPARATOR . $safeName;
            if (!move_uploaded_file($tmp, $fullPath)) {
                $errors[] = 'Taşıma hatası: ' . substr((string) ($file['name'] ?? ''), 0, 40);
                continue;
            }

            $row = new WoundPhoto();
            $row->set('hasta_id', $id);
            $row->set('dosya_adi', $safeName);
            $row->set('orijinal_ad', substr((string) ($file['name'] ?? ''), 0, 255));
            $row->set('mime', $mime);
            $row->set('boyut', $size);
            $row->set('aciklama', $aciklama);
            $row->set('yara_bolgesi', $yaraBolgesi);
            $row->set('yara_evresi', $yaraEvresi);
            $row->set('cekim_tarihi', $cekimTarihi);
            $row->set('yukleyen_id', AuthHelper::sessionUserId());
            $ok = $row->store();
            if (!$ok) {
                @unlink($fullPath);
                $errors[] = 'Veritabanı kaydı: ' . substr((string) ($file['name'] ?? ''), 0, 40);
                continue;
            }
            $saved++;
        }

        if ($saved > 0 && $errors === []) {
            $_SESSION['success'] = $saved . ' fotoğraf başarıyla yüklendi.';
        } elseif ($saved > 0) {
            $_SESSION['success'] = $saved . ' fotoğraf yüklendi, bazı dosyalar atlandı.';
            $_SESSION['error'] = count($errors) . ' dosya güvenlik veya format nedeniyle atlandı.';
        } else {
            $_SESSION['error'] = 'Fotoğraf yüklenemedi. Lütfen JPG, PNG veya WebP formatında ve 8 MB altında dosya seçin.';
        }
        header('Location: ' . $this->patientWoundsUrl($id));
        exit;
    }

    public function uploadPatientPhoto() {
        if (!AuthHelper::sessionIsAdmin()) {
            $_SESSION['error'] = 'Bu işlem için yetkiniz yok.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta kaydı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }
        $patient = new Patient();
        if (!$patient->load($id)) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }
        PatientAccessHelper::requirePatientAccess($id, $patient);

        $this->ensurePatientPhotoColumn();

        if (!isset($_FILES['profil_foto']) || !is_array($_FILES['profil_foto'])) {
            $_SESSION['error'] = 'Fotoğraf seçilmedi.';
            header('Location: ' . esh_url('Patient', 'view', ['id' => $id]));
            exit;
        }
        $file = $_FILES['profil_foto'];
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Dosya yüklenemedi.';
            header('Location: ' . esh_url('Patient', 'view', ['id' => $id]));
            exit;
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            $_SESSION['error'] = 'Profil fotoğrafı en fazla 5 MB olabilir.';
            header('Location: ' . esh_url('Patient', 'view', ['id' => $id]));
            exit;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? (string) finfo_file($finfo, $tmp) : '';
        if ($finfo) {
            finfo_close($finfo);
        }
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($allowed[$mime])) {
            $_SESSION['error'] = 'Sadece JPG, PNG veya WEBP yükleyebilirsiniz.';
            header('Location: ' . esh_url('Patient', 'view', ['id' => $id]));
            exit;
        }

        $folder = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'patients';
        if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
            $_SESSION['error'] = 'Fotoğraf klasörü oluşturulamadı.';
            header('Location: ' . esh_url('Patient', 'view', ['id' => $id]));
            exit;
        }

        $ext = $allowed[$mime];
        $safeName = 'patient_' . $id . '_' . time() . '.' . $ext;
        $fullPath = $folder . DIRECTORY_SEPARATOR . $safeName;
        if (!move_uploaded_file($tmp, $fullPath)) {
            $_SESSION['error'] = 'Dosya taşınamadı.';
            header('Location: ' . esh_url('Patient', 'view', ['id' => $id]));
            exit;
        }

        $old = basename((string) ($patient->profil_foto ?? ''));

        $patient->set('profil_foto', $safeName);
        if ($patient->store()) {
            if ($old !== '' && $old !== $safeName) {
                $oldPath = $folder . DIRECTORY_SEPARATOR . $old;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $_SESSION['success'] = 'Hasta profil fotoğrafı güncellendi.';
        } else {
            @unlink($fullPath);
            $_SESSION['error'] = 'Profil fotoğrafı kaydedilemedi.';
        }
        header('Location: ' . esh_url('Patient', 'view', ['id' => $id]));
        exit;
    }

    public function deletePatientPhoto() {
        if (!AuthHelper::sessionIsAdmin()) {
            $_SESSION['error'] = 'Bu işlem için yetkiniz yok.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta kaydı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $patient = new Patient();
        if (!$patient->load($id)) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }
        PatientAccessHelper::requirePatientAccess($id, $patient);

        $current = basename((string) ($patient->profil_foto ?? ''));
        if ($current === '') {
            $_SESSION['error'] = 'Silinecek profil fotoğrafı bulunamadı.';
            header('Location: ' . esh_url('Patient', 'view', ['id' => $id]));
            exit;
        }

        $fullPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'patients' . DIRECTORY_SEPARATOR . $current;
        $patient->set('profil_foto', null);
        $ok = $patient->store();
        if ($ok) {
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
            $_SESSION['success'] = 'Profil fotoğrafı silindi.';
        } else {
            $_SESSION['error'] = 'Profil fotoğrafı silinemedi.';
        }
        header('Location: ' . esh_url('Patient', 'view', ['id' => $id]));
        exit;
    }

    public function deleteWoundPhoto() {
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $photoId = IdHelper::normalizeRequestId($_POST['photo_id'] ?? null);
        if ($id === null || $photoId === null) {
            $_SESSION['error'] = 'Geçersiz silme isteği.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $model = new WoundPhoto();
        $model->ensureTable();
        $photo = $model->getById($photoId);
        if (!$photo || !IdHelper::idsMatch($photo->hasta_id ?? null, $id)) {
            $_SESSION['error'] = 'Fotoğraf kaydı bulunamadı.';
            header('Location: ' . $this->patientWoundsUrl($id));
            exit;
        }
        $patient = PatientAccessHelper::requirePatientAccess($id);
        if (!\App\Helpers\PatientClinicalFlagsHelper::isWoundPhotosModuleEnabled($patient)) {
            $_SESSION['error'] = 'Yara fotoğrafları modülü yalnızca aktif bası yarası işaretli hastalarda kullanılabilir.';
            header('Location: ' . esh_url('Patient', 'view', ['id' => $id]));
            exit;
        }

        $fullPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . 'wounds' . DIRECTORY_SEPARATOR . basename((string) $photo->dosya_adi);
        $ok = $model->delete($photoId);
        if ($ok && is_file($fullPath)) {
            @unlink($fullPath);
        }

        $_SESSION['success'] = $ok ? 'Yara fotoğrafı silindi.' : 'Fotoğraf silinemedi.';
        header('Location: ' . $this->patientWoundsUrl($id));
        exit;
    }

    public function resolvePatientCoords() {
        if (!AuthHelper::sessionIsAdmin()) {
            $_SESSION['error'] = 'Bu işlem için yetkiniz yok.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta kaydı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $patient = new Patient();
        if (!$patient->load($id)) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }
        PatientAccessHelper::requirePatientAccess($id, $patient);

        $anaadres = (new Address())->getUserAddress($id);
        if (!$anaadres) {
            $_SESSION['error'] = 'Koordinat için geçerli adres bulunamadı.';
            header('Location: ' . esh_url('Patient', 'view', ['id' => $id]));
            exit;
        }

        $parts = [];
        foreach (['mahalle', 'sokak', 'kapino', 'ilce'] as $f) {
            $val = trim((string) ($anaadres->$f ?? ''));
            if ($val !== '') {
                $parts[] = $val;
            }
        }
        $adresAciklama = trim((string) ($patient->adres_aciklama ?? ''));
        if ($adresAciklama !== '') {
            $parts[] = $adresAciklama;
        }
        $parts[] = 'Denizli';
        $parts[] = 'Turkey';
        $query = implode(', ', array_values(array_unique($parts)));

        $position = $this->tomtomGeocodeFirstResult($query);
        if ($position === null) {
            $_SESSION['error'] = 'Koordinat bulunamadı (aktif harita sağlayıcısı).';
            header('Location: ' . esh_url('Patient', 'view', ['id' => $id]));
            exit;
        }

        if ($this->persistCoordsForPatient($patient, (float) $position['lat'], (float) $position['lon'])) {
            $_SESSION['success'] = 'Koordinat kapı no kaydına yazıldı.';
        } else {
            $kapinoId = trim((string) ($patient->kapino ?? ''));
            $_SESSION['error'] = $kapinoId === ''
                ? 'Koordinat kaydedilemedi: önce tam adres (kapı no) seçilmelidir.'
                : 'Koordinat bulundu ancak kaydedilemedi (veritabanı hatası).';
        }
        header('Location: ' . esh_url('Patient', 'view', ['id' => $id]));
        exit;
    }

    /**
     * @param array<string, mixed> $upload
     * @return array<int, array{name: string, type: string, tmp_name: string, error: int, size: int}>
     */
    private function normalizeUploadFiles(array $upload): array
    {
        $names = $upload['name'] ?? null;
        if (!is_array($names)) {
            return [$upload];
        }
        $out = [];
        foreach ($names as $i => $name) {
            $out[] = [
                'name' => (string) $name,
                'type' => (string) ($upload['type'][$i] ?? ''),
                'tmp_name' => (string) ($upload['tmp_name'][$i] ?? ''),
                'error' => (int) ($upload['error'][$i] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) ($upload['size'][$i] ?? 0),
            ];
        }

        return $out;
    }

    private function normalizeDateTimeInput($raw): ?string
    {
        $v = trim((string) $raw);
        if ($v === '') {
            return null;
        }
        $ts = strtotime(str_replace('T', ' ', $v));
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $ts);
    }

    private function ensurePatientPhotoColumn(): void
    {
        $db = Database::getInstance();
        $tableName = $db->replacePrefix('#__hastalar');
        $exists = (int) $db->loadResultPrepared(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$tableName, 'profil_foto']
        );
        if ($exists === 0) {
            $db->execLogged('ALTER TABLE #__hastalar ADD COLUMN profil_foto VARCHAR(255) DEFAULT NULL AFTER notes');
        }
    }

    /**
     * @return array{lat: float, lon: float}|null
     */
    private function tomtomGeocodeFirstResult(string $addressQuery): ?array
    {
        return \App\Helpers\MapRoutingGeocodeHelper::firstPosition($addressQuery);
    }

    private function refreshCoordsFromCurrentMainAddress(string $patientId): void
    {
        if ($patientId === null) {
            return;
        }

        $patient = new Patient();
        if (!$patient->load($patientId)) {
            return;
        }

        $kapinoId = trim((string) ($patient->kapino ?? ''));
        if ($kapinoId === '') {
            return;
        }

        $query = (new Address())->buildGeocodeQueryForKapinoId(
            $kapinoId,
            trim((string) ($patient->adres_aciklama ?? ''))
        );
        if ($query === null) {
            return;
        }

        $position = $this->tomtomGeocodeFirstResult($query);
        if ($position === null) {
            return;
        }

        $this->persistCoordsForPatient($patient, (float) $position['lat'], (float) $position['lon']);
    }

    private function persistCoordsForPatient(Patient $patient, float $lat, float $lon): bool
    {
        $coords = number_format($lat, 6, '.', '') . ',' . number_format($lon, 6, '.', '');

        return $this->persistCoordsStringForPatient($patient, $coords);
    }

    private function persistCoordsStringForPatient(Patient $patient, string $coordsRaw): bool
    {
        $coords = Address::normalizeCoordsString($coordsRaw);
        if ($coords === '') {
            return false;
        }

        $kapinoId = trim((string) ($patient->kapino ?? ''));
        if ($kapinoId === '') {
            return false;
        }

        return (new Address())->setKapinoCoords($kapinoId, $coords);
    }


    /** Adres tablosu kimliği (UUID veya sayısal) dolu mu? */
    private function patientEditAddressIdFilled(mixed $value): bool
    {
        $s = trim((string) $value);

        return $s !== '' && $s !== '0';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertPatientAddressScopeInPost(array $data, int $kurumId, string $redirectUrl): void
    {
        if ($kurumId <= 0 || !KurumAdresScope::shouldFilter($kurumId)) {
            return;
        }

        $rows = [];
        if (isset($data['adres']) && is_array($data['adres'])) {
            foreach ($data['adres'] as $row) {
                if (is_array($row)) {
                    $rows[] = $row;
                }
            }
        } else {
            $rows[] = $data;
        }

        foreach ($rows as $row) {
            $err = KurumAdresScope::assertPatientAddressParts($kurumId, [
                'ilce' => $row['ilce'] ?? '',
                'mahalle' => $row['mahalle'] ?? '',
                'sokak' => $row['sokak'] ?? '',
            ]);
            if ($err !== null) {
                $_SESSION['error'] = $err;
                header('Location: ' . $redirectUrl);
                exit;
            }
        }
    }

    /**
     * Store POST — seçili ana adres satırı (adres[n] veya düz ilce/mahalle/sokak/kapino).
     *
     * @return array{ilce:string, mahalle:string, sokak:string, kapino:string}
     */
    private function patientEditResolveMainAddressRow(array $data): array
    {
        $anaIndex = isset($data['ana_adres_index']) ? (string) $data['ana_adres_index'] : '0';
        if (isset($data['adres']) && is_array($data['adres'])) {
            if (isset($data['adres'][$anaIndex]) && is_array($data['adres'][$anaIndex])) {
                $row = $data['adres'][$anaIndex];
            } elseif ($anaIndex === '0' && isset($data['adres'][0]) && is_array($data['adres'][0])) {
                $row = $data['adres'][0];
            } else {
                $row = null;
            }
            if (is_array($row)) {
                return [
                    'ilce' => trim((string) ($row['ilce'] ?? '')),
                    'mahalle' => trim((string) ($row['mahalle'] ?? '')),
                    'sokak' => trim((string) ($row['sokak'] ?? '')),
                    'kapino' => trim((string) ($row['kapino'] ?? '')),
                ];
            }
        }

        return [
            'ilce' => trim((string) ($data['ilce'] ?? '')),
            'mahalle' => trim((string) ($data['mahalle'] ?? '')),
            'sokak' => trim((string) ($data['sokak'] ?? '')),
            'kapino' => trim((string) ($data['kapino'] ?? '')),
        ];
    }

    private function patientEditDateFilled(mixed $value): bool
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return false;
        }

        return \App\Helpers\DateHelper::trDateToYmd($raw) !== null;
    }

    private function patientEditPositiveMetricFilled(mixed $value): bool
    {
        $norm = \App\Helpers\ValidationHelper::normalizeDecimalDotInput($value);
        if ($norm === '') {
            return false;
        }

        return \App\Helpers\ValidationHelper::parseDecimalDot($norm) > 0;
    }

    private function patientEditHastaliklarFilled(array $data): bool
    {
        if (!isset($data['hastaliklar']) || !is_array($data['hastaliklar'])) {
            return false;
        }

        return Patient::parseHastalikCsvToIcds($data['hastaliklar']) !== [];
    }

    /**
     * @param list<string> $icds
     */
    private function normalizePatientHastaliklarInput(array $icds, int $kurumId): string
    {
        $icds = Patient::parseHastalikCsvToIcds($icds);
        if ($kurumId > 0 && \App\Models\KurumHastalik::tableExists()) {
            $assigned = array_flip((new \App\Models\KurumHastalik())->getAssignedIcds($kurumId));
            if ($assigned !== []) {
                $icds = array_values(array_filter(
                    $icds,
                    static fn (string $icd): bool => isset($assigned[$icd])
                ));
            }
        }

        return Patient::hastaliklarToStorageCsv($icds);
    }

    /**
     * Parçalı modal kaydı — yalnızca ilgili bölümde doğrulanacak alan anahtarları.
     *
     * @return list<string>|null bilinmeyen bölüm
     */
    private function patientPartialEditValidationScope(string $section): ?array
    {
        $map = [
            'kimlik_iletisim' => ['tckimlik', 'isim', 'soyisim', 'anneAdi', 'babaAdi', 'dogumtarihi', 'kayittarihi', 'randevutarihi', 'cinsiyet', 'guvence', 'ceptel1'],
            'dosya_secenekleri' => [],
            'fiziksel_olcumler' => ['boy', 'kilo', 'bagimlilik'],
            'adres' => ['ilce', 'mahalle', 'sokak', 'kapino'],
            'klinik_tanilar' => ['hastaliklar'],
            'klinik_uyarilar' => [],
            'tibbi_cihaz' => [],
            'bakim_sarf' => [],
            'dosya_durumu' => ['pasif'],
        ];

        return array_key_exists($section, $map) ? $map[$section] : null;
    }

    /**
     * Hasta düzenleme (store) — eksik zorunlu alan etiketleri.
     *
     * @param list<string>|null $scope null = tam form; dolu = yalnızca bu alan anahtarları
     * @return string[] boş = geçerli
     */
    private function patientEditMissingRequiredLabels(array $data, bool $isAdmin = false, ?array $scope = null): array
    {
        $inScope = static function (string $key) use ($scope): bool {
            return $scope === null || in_array($key, $scope, true);
        };

        $missing = [];
        if ($inScope('tckimlik')) {
            $tc = \App\Helpers\ValidationHelper::tcDigitsOnly($data['tckimlik'] ?? '');
            if (!\App\Helpers\ValidationHelper::isTcLength11($tc)) {
                $missing[] = 'TC Kimlik No (11 hane)';
            }
        }
        if ($inScope('isim') && trim((string) ($data['isim'] ?? '')) === '') {
            $missing[] = 'Ad';
        }
        if ($inScope('soyisim') && trim((string) ($data['soyisim'] ?? '')) === '') {
            $missing[] = 'Soyad';
        }
        if ($inScope('anneAdi') && trim((string) ($data['anneAdi'] ?? '')) === '') {
            $missing[] = 'Anne adı';
        }
        if ($inScope('babaAdi') && trim((string) ($data['babaAdi'] ?? '')) === '') {
            $missing[] = 'Baba adı';
        }
        if ($inScope('dogumtarihi') && !$this->patientEditDateFilled($data['dogumtarihi'] ?? '')) {
            $missing[] = 'Doğum tarihi';
        }
        if ($inScope('kayittarihi') && !$this->patientEditDateFilled($data['kayittarihi'] ?? '')) {
            $missing[] = 'Kayıt tarihi';
        }
        if ($inScope('randevutarihi') && !$this->patientEditDateFilled($data['randevutarihi'] ?? '')) {
            $missing[] = 'Randevu tarihi';
        }
        if ($inScope('cinsiyet') && CinsiyetHelper::normalize($data['cinsiyet'] ?? null) === null) {
            $missing[] = 'Cinsiyet';
        }
        if ($inScope('guvence')) {
            $guvence = trim((string) ($data['guvence'] ?? ''));
            if ($guvence === '' || $guvence === '0') {
                $missing[] = 'Güvence';
            }
        }
        if ($inScope('ceptel1')) {
            $phoneDigits = \App\Helpers\ValidationHelper::phoneDigits((string) ($data['ceptel1'] ?? ''));
            if (strlen($phoneDigits) !== 11) {
                $missing[] = 'Telefon 1 (cep)';
            }
        }
        if ($inScope('boy') && !$this->patientEditPositiveMetricFilled($data['boy'] ?? '')) {
            $missing[] = 'Boy';
        }
        if ($inScope('kilo') && !$this->patientEditPositiveMetricFilled($data['kilo'] ?? '')) {
            $missing[] = 'Kilo';
        }

        if ($inScope('ilce') || $inScope('mahalle') || $inScope('sokak') || $inScope('kapino')) {
            $addr = $this->patientEditResolveMainAddressRow($data);
            if ($inScope('ilce') && !$this->patientEditAddressIdFilled($addr['ilce'])) {
                $missing[] = 'İlçe';
            }
            if ($inScope('mahalle') && !$this->patientEditAddressIdFilled($addr['mahalle'])) {
                $missing[] = 'Mahalle';
            }
            if ($inScope('sokak') && !$this->patientEditAddressIdFilled($addr['sokak'])) {
                $missing[] = 'Sokak / cadde';
            }
            if ($inScope('kapino') && !$this->patientEditAddressIdFilled($addr['kapino'])) {
                $missing[] = 'Kapı no';
            }
        }
        if ($inScope('hastaliklar') && !$this->patientEditHastaliklarFilled($data)) {
            $missing[] = 'Hastalıklar (tanılar)';
        }
        if ($inScope('pasif') && (!array_key_exists('pasif', $data) || trim((string) $data['pasif']) === '')) {
            $missing[] = 'Kayıt durumu (pasif)';
        }
        $pasifForTarih = isset($data['pasif']) && is_numeric($data['pasif']) ? (int) $data['pasif'] : null;
        if (($scope === null || $inScope('pasif')) && $pasifForTarih === 1 && !$this->patientEditDateFilled($data['pasiftarihi'] ?? '')) {
            $missing[] = 'Pasif tarihi';
        }

        return array_values(array_unique($missing));
    }

    /** Parçalı modal kaydı için mevcut hasta verisini store doğrulamasına uygun diziye çevirir. */
    private function patientSnapshotForPartialStore(Patient $patient): array
    {
        $snap = [];
        $scalarKeys = [
            'isim', 'soyisim', 'anneAdi', 'babaAdi', 'tckimlik', 'cinsiyet', 'ceptel1', 'ceptel2',
            'bakimveren_ad', 'bakimveren_tel', 'bakimveren_yakinlik', 'alerji', 'acil_not',
            'guvence', 'yupasno', 'ailehekimi', 'ailehekimitel', 'kangrubu', 'gecici', 'erapor', 'boy', 'kilo', 'bagimlilik',
            'sms_bilgilendirme_onay',
            'pasif', 'pasifnedeni',
            'ng', 'peg', 'port', 'o2bagimli', 'ventilator', 'kolostomi',
            'trakeostomi', 'cpap', 'aspirasyon', 'ileostomi', 'urostomi', 'picc',
            'dren', 'diyaliz', 'basiyarasi', 'ivtedavi', 'izolasyon', 'sonda',
            'pansuman', 'pzaman', 'mama', 'mamacesit', 'mamaraporyeri', 'bez', 'bezrapor', 'yatak',
            'ilce', 'mahalle', 'sokak', 'kapino', 'adres_aciklama',
        ];
        foreach ($scalarKeys as $key) {
            if (isset($patient->$key)) {
                $snap[$key] = $patient->$key;
            }
        }

        $gender = CinsiyetHelper::normalize($patient->cinsiyet ?? null);
        if ($gender !== null) {
            $snap['cinsiyet'] = $gender;
        }

        foreach (['dogumtarihi', 'kayittarihi', 'pasiftarihi', 'randevutarihi', 'mamaraporbitis', 'bezraporbitis', 'sondatarihi'] as $df) {
            $raw = trim((string) ($patient->$df ?? ''));
            if ($raw !== '' && $raw !== '0000-00-00') {
                $snap[$df] = DateHelper::toTrOrEmpty($raw);
            }
        }

        $snap['hastaliklar'] = Patient::parseHastalikCsvToIcds($patient->hastaliklar ?? null);

        $snap['adres'] = [[
            'ilce' => $patient->ilce ?? null,
            'mahalle' => $patient->mahalle ?? null,
            'sokak' => $patient->sokak ?? null,
            'kapino' => $patient->kapino ?? null,
            'adres_aciklama' => $patient->adres_aciklama ?? null,
        ]];
        $diger = json_decode($patient->diger_adres ?? '[]', true);
        if (is_array($diger)) {
            foreach ($diger as $idx => $addrRow) {
                if (is_array($addrRow)) {
                    $snap['adres'][(int) $idx + 1] = $addrRow;
                }
            }
        }
        $snap['ana_adres_index'] = '0';

        $pgCsv = trim((string) ($patient->pgunleri ?? ''));
        $snap['pgunleri'] = $pgCsv === '' ? [] : array_map('intval', array_filter(explode(',', $pgCsv), static fn ($v) => $v !== ''));

        return $snap;
    }

    private function patientPartialStoreRedirectUrl(string $id, string $partialSection = ''): string
    {
        $params = ['id' => $id];
        if ($partialSection !== '') {
            $params['open_modal'] = $partialSection;
        }

        return esh_url('Patient', 'view', $params);
    }
    private function storePatientWithTcCascade(Patient $patient, string $previousTc): bool
    {
        $oldTc = trim($previousTc);
        $newTc = trim((string) ($patient->tckimlik ?? ''));
        $tcChanged = $oldTc !== '' && $newTc !== '' && $oldTc !== $newTc;

        if (!$tcChanged) {
            return $patient->store();
        }

        try {
            return Database::getInstance()->transaction(function (Database $db) use ($patient, $newTc, $oldTc) {
                if (!$patient->store()) {
                    return false;
                }
                $db->executePrepared(
                    'UPDATE #__izlemler SET hastatckimlik = ? WHERE hastatckimlik = ?',
                    [$newTc, $oldTc]
                );
                $db->executePrepared(
                    'UPDATE #__pizlemler SET hastatckimlik = ? WHERE hastatckimlik = ?',
                    [$newTc, $oldTc]
                );
                $db->executePrepared(
                    'UPDATE #__erapor SET hastatckimlik = ? WHERE hastatckimlik = ?',
                    [$newTc, $oldTc]
                );

                return true;
            });
        } catch (\Throwable $e) {
            return false;
        }
    }
    
    //hasta bilgileri kayıt
    public function store() {
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz hasta.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $patient = new Patient();
        if (!$patient->load($id)) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        PatientAccessHelper::requirePatientAccess($id, $patient);

        $kurumTransferApplied = false;
        if (AuthHelper::sessionIsSuperAdmin() && isset($_POST['kurum_id'])) {
            $newKid = (int) $_POST['kurum_id'];
            $err = PatientKurumTransfer::validate($patient, $newKid);
            if ($err !== null) {
                $_SESSION['error'] = $err;
                header('Location: ' . esh_url('Patient', 'edit', ['id' => $id]));
                exit;
            }
            if ((int) ($patient->kurum_id ?? 0) !== $newKid) {
                $transferResult = PatientKurumTransfer::apply($patient, $newKid, (AuthHelper::sessionUserId() ?? ''));
                if ($transferResult === false) {
                    $_SESSION['error'] = 'Kurum değiştirilirken bir hata oluştu.';
                    header('Location: ' . esh_url('Patient', 'edit', ['id' => $id]));
                    exit;
                }
                if (is_int($transferResult)) {
                    $kurumTransferApplied = true;
                    $id = $transferResult;
                    $patient = new Patient();
                    if (!$patient->load($id)) {
                        $_SESSION['error'] = 'Nakil sonrası hasta kaydı yüklenemedi.';
                        header('Location: ' . esh_url('Patient', 'unified', ['status' => 'waiting']));
                        exit;
                    }
                }
            }
        }

        $wasWaiting = (string) ($patient->pasif ?? '') === '-3';

        $previousTc = trim((string) ($patient->tckimlik ?? ''));

        $oldMainAddressSignature = implode('|', [
            (string) ($patient->ilce ?? ''),
            (string) ($patient->mahalle ?? ''),
            (string) ($patient->sokak ?? ''),
            (string) ($patient->kapino ?? ''),
            (string) ($patient->adres_aciklama ?? ''),
        ]);

        $partialSection = trim((string) ($_POST['partial_section'] ?? ''));
        $isPartialStore = $partialSection !== '';
        $storeRedirectUrl = $isPartialStore
            ? $this->patientPartialStoreRedirectUrl($id, $partialSection)
            : esh_url('Patient', 'edit', ['id' => $id]);
        $storeSuccessRedirectUrl = $isPartialStore
            ? esh_url('Patient', 'view', ['id' => $id])
            : esh_url('Patient', 'view', ['id' => $id]);

        $isAdminStore = true;
        $picked = PostAllowlistHelper::pick($_POST, $this->patientStoreAllowlistKeys($isAdminStore));
        if (EsysComplianceHelper::enabled()) {
            $picked = array_merge($picked, EsysComplianceHelper::pickPatientRefs($picked));
        }
        if (UsbsComplianceHelper::enabled()) {
            $picked = array_merge($picked, UsbsComplianceHelper::pickPatientRefs($picked));
        }
        $partialValidationScope = null;
        if ($isPartialStore) {
            $partialValidationScope = $this->patientPartialEditValidationScope($partialSection);
            if ($partialValidationScope === null) {
                $_SESSION['error'] = 'Geçersiz düzenleme bölümü.';
                header('Location: ' . $this->patientPartialStoreRedirectUrl($id));
                exit;
            }
            $data = $picked;
        } else {
            $data = $picked;
        }
        unset($data['new_note']);

        if ($kurumTransferApplied) {
            unset($data['pasif'], $data['pasifnedeni'], $data['pasiftarihi'], $data['kurum_id']);
        }

        $phoneErr = \App\Helpers\ValidationHelper::applyPhoneFields($data, !$isPartialStore || in_array('ceptel1', $partialValidationScope ?? [], true));
        if ($phoneErr !== null) {
            $_SESSION['error'] = $phoneErr;
            header('Location: ' . $storeRedirectUrl);
            exit;
        }

        if (array_key_exists('zaman', $data)) {
            $allowInactiveZaman = (int) ($patient->zaman ?? 0) > 0
                && \App\Helpers\ZamanDilimiHelper::normalize($patient->zaman) === \App\Helpers\ZamanDilimiHelper::normalize($data['zaman']);
            $zamanValid = \App\Helpers\ZamanDilimiHelper::validateForSave($data['zaman'], $allowInactiveZaman);
            if ($zamanValid !== true) {
                $_SESSION['error'] = is_string($zamanValid) ? $zamanValid : 'Geçersiz zaman dilimi.';
                header('Location: ' . $storeRedirectUrl);
                exit;
            }
            $data['zaman'] = (string) \App\Helpers\ZamanDilimiHelper::clamp($data['zaman']);
        }
        if (array_key_exists('pzaman', $data)) {
            $allowInactivePzaman = (int) ($patient->pzaman ?? 0) > 0
                && \App\Helpers\ZamanDilimiHelper::normalize($patient->pzaman) === \App\Helpers\ZamanDilimiHelper::normalize($data['pzaman']);
            $pzamanValid = \App\Helpers\ZamanDilimiHelper::validateForSave($data['pzaman'], $allowInactivePzaman);
            if ($pzamanValid !== true) {
                $_SESSION['error'] = is_string($pzamanValid) ? $pzamanValid : 'Geçersiz pansuman zaman dilimi.';
                header('Location: ' . $storeRedirectUrl);
                exit;
            }
            $data['pzaman'] = (string) \App\Helpers\ZamanDilimiHelper::clamp($data['pzaman']);
        }

        $missingLabels = $this->patientEditMissingRequiredLabels($data, true, $partialValidationScope);
        if ($missingLabels !== []) {
            $_SESSION['error'] = 'Kayıt tamamlanamadı. Eksik veya geçersiz alanlar: ' . implode(', ', $missingLabels) . '.';
            $_SESSION['patient_edit_validation_fields'] = $missingLabels;
            header('Location: ' . $storeRedirectUrl);
            exit;
        }

        if (array_key_exists('tckimlik', $data)) {
            $tcDigits = \App\Helpers\ValidationHelper::tcDigitsOnly($data['tckimlik'] ?? '');
            $tcUniqueErr = $this->validateTcGloballyUnique($tcDigits, $id);
            if ($tcUniqueErr !== null) {
                $_SESSION['error'] = $tcUniqueErr;
                header('Location: ' . $storeRedirectUrl);
                exit;
            }
        }

        if (!$isPartialStore || array_key_exists('cinsiyet', $picked)) {
            $data['cinsiyet'] = CinsiyetHelper::normalize($data['cinsiyet'] ?? null);
        }

        if (array_key_exists('boy', $data)) {
            $data['boy'] = \App\Helpers\ValidationHelper::normalizeDecimalDotInput($data['boy']);
        }
        if (array_key_exists('kilo', $data)) {
            $data['kilo'] = \App\Helpers\ValidationHelper::normalizeDecimalDotInput($data['kilo']);
        }
        if (array_key_exists('kangrubu', $data)) {
            $data['kangrubu'] = PatientCareHelper::normalizeKanGrubu($data['kangrubu']);
        }
        if (array_key_exists('ailehekimi', $data)) {
            $data['ailehekimi'] = trim((string) $data['ailehekimi']) !== '' ? trim((string) $data['ailehekimi']) : null;
        }

        if (isset($data['hastaliklar']) && is_array($data['hastaliklar'])) {
            $kurumIdForHl = (int) ($data['kurum_id'] ?? $patient->kurum_id ?? TenantContext::assignKurumIdForStore() ?? 0);
            $data['hastaliklar'] = $this->normalizePatientHastaliklarInput($data['hastaliklar'], $kurumIdForHl);
        }

        $dateFields = ['dogumtarihi', 'kayittarihi', 'pasiftarihi', 'randevutarihi', 'mamaraporbitis', 'bezraporbitis', 'sondatarihi'];
        foreach ($dateFields as $f) {
            if (!array_key_exists($f, $data)) {
                continue;
            }
            $raw = trim((string) $data[$f]);
            if ($raw === '') {
                $data[$f] = null;
                continue;
            }
            $ymd = DateHelper::trDateToYmd($raw);
            if ($ymd !== null) {
                $data[$f] = $ymd;
                continue;
            }
            $ts = strtotime(str_replace(['.', '/'], '-', $raw));
            $data[$f] = $ts ? date('Y-m-d', $ts) : null;
        }

        if (!$isPartialStore || $partialSection === 'bakim_sarf') {
            $mamaOn = isset($data['mama']) && (int) $data['mama'] === 1;
            if (!$mamaOn) {
                $data['mamacesit'] = '0';
                $data['mamaraporyeri'] = '0';
                $data['mamaraporbitis'] = null;
            } else {
                $data['mamacesit'] = (string) PatientCareHelper::normalizeMamaCesit($data['mamacesit'] ?? 0);
                $data['mamaraporyeri'] = (string) PatientCareHelper::normalizeMamaRaporYeri($data['mamaraporyeri'] ?? 0);
            }

            $bezOn = isset($data['bez']) && (int) $data['bez'] === 1;
            if (!$bezOn) {
                $data['bezrapor'] = '0';
                $data['bezraporbitis'] = null;
            } else {
                $data['bezrapor'] = (isset($data['bezrapor']) && (int) $data['bezrapor'] === 1) ? '1' : '0';
                if ((int) $data['bezrapor'] !== 1) {
                    $data['bezraporbitis'] = null;
                }
            }

            $pansumanOn = isset($data['pansuman']) && (int) $data['pansuman'] === 1;
            if (!$pansumanOn) {
                $data['pgunleri'] = '';
                $data['pzaman'] = (string) \App\Helpers\ZamanDilimiHelper::SABAH;
            } else {
                if (!empty($_POST['pgunleri']) && is_array($_POST['pgunleri'])) {
                    $data['pgunleri'] = PatientCareHelper::normalizePgunleriCsvFromArray($_POST['pgunleri']);
                } else {
                    $data['pgunleri'] = '';
                }
            }
        }

        if (!$isPartialStore || array_key_exists('randevutarihi', $picked) || array_key_exists('kayittarihi', $picked)) {
            $kayitYmd = trim((string) ($data['kayittarihi'] ?? $patient->kayittarihi ?? ''));
            $randevuYmd = trim((string) ($data['randevutarihi'] ?? ''));
            if ($kayitYmd !== '' && $kayitYmd !== '0000-00-00' && $randevuYmd !== '' && strcmp($randevuYmd, $kayitYmd) < 0) {
                $_SESSION['error'] = 'Randevu tarihi, kayıt tarihinden küçük olamaz.';
                header('Location: ' . $storeRedirectUrl);
                exit;
            }
        }

        $nakilPassiveCtx = ['error' => null, 'should_create' => false, 'hedef' => ''];
        if (!$isPartialStore || array_key_exists('pasif', $picked)) {
            $pasifVal = PatientCareHelper::normalizePasifForStore(
                $data['pasif'] ?? null,
                $patient->pasif ?? 0,
                true
            );
            $data['pasif'] = (string) $pasifVal;
            $pasifDosyaOn = $pasifVal === 1;
            if (!$pasifDosyaOn) {
                $data['pasifnedeni'] = PatientCareHelper::pasifNedeniForNonPassiveStore($pasifVal, $patient);
                $data['pasiftarihi'] = null;
            } else {
                $data['pasifnedeni'] = PatientCareHelper::normalizePasifNedeniForStore($data['pasifnedeni'] ?? '1');
            }

            $nakilPassiveCtx = $this->resolveNakilPassiveContext($patient, $data, $id);
            if ($nakilPassiveCtx['error'] !== null) {
                $_SESSION['error'] = $nakilPassiveCtx['error'];
                header('Location: ' . $storeRedirectUrl);
                exit;
            }
        } else {
            $pasifVal = is_numeric($patient->pasif ?? null) ? (int) $patient->pasif : 0;
        }

        if (isset($data['adres'])) {
            unset($data['adres']);
        }
        unset($data['coords'], $data['partial_section']);

        $yupasIds = $this->getYupasGuvenceIds();
        if (array_key_exists('guvence', $data)) {
            $guvForYupas = (int) $data['guvence'];
            if (!in_array($guvForYupas, $yupasIds, true)) {
                $data['yupasno'] = '';
            }
        }

        $patient->bind($data);

        if (!$isPartialStore && !array_key_exists('hastaliklar', $_POST)) {
            $patient->set('hastaliklar', '');
        }

        if (!$isPartialStore) {
            foreach (\App\Helpers\PatientClinicalFlagsHelper::allBooleanKeys() as $flag) {
                if (!isset($_POST[$flag])) {
                    $patient->set($flag, 0);
                }
            }
        }

        if (!empty(trim($_POST['new_note'] ?? ''))) {
            $existingNotes = json_decode($patient->notes ?? '', true);
            if (!is_array($existingNotes)) {
                $existingNotes = [];
            }
            array_unshift($existingNotes, [
                'date'    => date('d-m-Y H:i'),
                'user'    => $_SESSION['name'] ?? 'Sistem',
                'message' => trim($_POST['new_note']),
            ]);
            $patient->set('notes', json_encode($existingNotes, JSON_UNESCAPED_UNICODE));
        }

        if (isset($_POST['adres']) && is_array($_POST['adres'])) {
            $anaIndex = $_POST['ana_adres_index'] ?? 0;
            $yedekler = [];

            // Yeni ana adres ek adreslerden biri seçildiyse, mevcut ana adresi yedeklere taşı.
            if ((string) $anaIndex !== '0') {
                $oncekiAnaAdres = [
                    'ilce' => $patient->ilce ?? null,
                    'mahalle' => $patient->mahalle ?? null,
                    'sokak' => $patient->sokak ?? null,
                    'kapino' => $patient->kapino ?? null,
                    'adres_aciklama' => $patient->adres_aciklama ?? null,
                ];
                $hasAnyPrev = false;
                foreach (['ilce', 'mahalle', 'sokak', 'kapino', 'adres_aciklama'] as $k) {
                    if (!empty($oncekiAnaAdres[$k])) {
                        $hasAnyPrev = true;
                        break;
                    }
                }
                if ($hasAnyPrev) {
                    $yedekler[] = $oncekiAnaAdres;
                }
            }

            foreach ($_POST['adres'] as $idx => $val) {
                if ($idx == $anaIndex) {
                    $patient->set('ilce', $val['ilce'] ?? null);
                    $patient->set('mahalle', $val['mahalle'] ?? null);
                    $patient->set('sokak', $val['sokak'] ?? null);
                    $patient->set('kapino', $val['kapino'] ?? null);
                    $patient->set('adres_aciklama', $val['adres_aciklama'] ?? null);
                } else {
                    $yedekler[] = $val;
                }
            }
            $patient->set('diger_adres', json_encode($yedekler, JSON_UNESCAPED_UNICODE));
        }

        if (!$isPartialStore || $partialSection === 'adres') {
            $this->assertPatientAddressScopeInPost(
                $_POST,
                (int) ($patient->kurum_id ?? TenantContext::assignKurumIdForStore()),
                $storeRedirectUrl
            );
        }

        $newMainAddressSignature = implode('|', [
            (string) ($patient->ilce ?? ''),
            (string) ($patient->mahalle ?? ''),
            (string) ($patient->sokak ?? ''),
            (string) ($patient->kapino ?? ''),
            (string) ($patient->adres_aciklama ?? ''),
        ]);
        $mainAddressChanged = $oldMainAddressSignature !== $newMainAddressSignature;

        $result = $this->storePatientWithTcCascade($patient, $previousTc);

        if ($result) {
            AuditLogHelper::patientUpdate($patient, ['partial' => $isPartialStore]);
            $this->patientStoreTcChecksumWarning($_POST);
            $this->processNakilAfterPassiveSave($patient, $nakilPassiveCtx);
            if ($mainAddressChanged) {
                $this->refreshCoordsFromCurrentMainAddress((string) $patient->id);
            }
            $savedTc = trim((string) ($patient->tckimlik ?? ''));
            $redirectToFirstVisit = $wasWaiting
                && in_array($pasifVal, [0, 1], true)
                && $savedTc !== ''
                && (int) (new Visit())->countPatientVisits($savedTc) === 0;
            if ($redirectToFirstVisit) {
                $_SESSION['success'] = 'Hasta bilgileri kaydedildi. İlk izlemi girebilirsiniz.';
                $firstIslemId = IslemIdSettings::resolvedInt('visit_after_register_default_yapilan_islem_id');
                header('Location: ' . esh_url('Visit', 'create', ['tc' => $savedTc, 'yapilan' => $firstIslemId]));
            } else {
                $_SESSION['success'] = $isPartialStore
                    ? 'Hasta bilgisi güncellendi.'
                    : 'Hasta bilgileri kaydedildi.';
                header('Location: ' . $storeSuccessRedirectUrl);
            }
        } else {
            $_SESSION['error'] = "Veritabanı hatası: Hasta bilgileri kaydedilemedi";
            header('Location: ' . $storeRedirectUrl);
        }
        exit;
    }
    
    /** Kayıt sonrası TC kontrol hanesi uyarısı (11 hane geçerliyse). */
    private function patientStoreTcChecksumWarning(array $data): void
    {
        $tc = \App\Helpers\ValidationHelper::tcDigitsOnly($data['tckimlik'] ?? '');
        if (\App\Helpers\ValidationHelper::isTcLength11($tc) && !\App\Helpers\ValidationHelper::isTc($tc)) {
            $_SESSION['warning'] = \App\Helpers\ValidationHelper::tcChecksumWarningMessage();
        }
    }

    private function validateTcGloballyUnique(string $tc, int|string|null $excludeId = null): ?string
    {
        if (!\App\Helpers\ValidationHelper::isTcLength11($tc)) {
            return null;
        }
        $found = (new Patient())->findByTcGlobal($tc);
        if ($found === null) {
            return null;
        }
        $foundId = IdHelper::normalizeRequestId($found->id ?? null);
        if (!IdHelper::isEmptyEntityId($excludeId) && IdHelper::idsMatch($foundId, $excludeId)) {
            return null;
        }

        return 'Bu TC kimlik numarası sistemde başka bir hasta kaydında kullanılıyor.';
    }

    //hasta tc kontrol
    public function checkTC() {
    if (!isset($_GET['tc'])) {
        exit;
    }

    $tc = \App\Helpers\ValidationHelper::tcDigitsOnly($_GET['tc'] ?? '');
    $excludeId = IdHelper::normalizeRequestId($_GET['exclude_id'] ?? null);
    $model = new Patient();

    $lenOk = \App\Helpers\ValidationHelper::isTcLength11($tc);
    $checksumOk = $lenOk && $model->validateTc($tc);
    $exists = false;
    if ($lenOk) {
        $found = $model->findByTcGlobal($tc);
        if ($found) {
            $foundId = IdHelper::normalizeRequestId($found->id ?? null);
            $exists = !(!IdHelper::isEmptyEntityId($excludeId) && IdHelper::idsMatch($foundId, $excludeId));
        }
    }

    $wantJson = isset($_GET['format']) && strtolower((string) $_GET['format']) === 'json';
    if ($wantJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'valid' => $lenOk ? 1 : 0,
            'checksum_ok' => $checksumOk ? 1 : 0,
            'exists' => $exists ? 1 : 0,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!$lenOk) {
        echo '<span class="text-danger small"><i class="fa-solid fa-xmark"></i> TC 11 haneli olmalıdır</span>';
    } elseif (!$checksumOk) {
        echo '<span class="text-warning small"><i class="fa-solid fa-triangle-exclamation"></i> Kontrol hanesi uyarısı (kayıt engellenmez)</span>';
    } elseif ($exists) {
        echo '<span class="text-warning small"><i class="fa-solid fa-triangle-exclamation"></i> TC Kimlik Numarası kayıtlı</span>';
    } else {
        echo '<span class="text-success small"><i class="fa-solid fa-check"></i> TC Kimlik Numarası geçerli</span>';
    }
    
    // View dosyasının geri kalanının yüklenmemesi için sonlandırıyoruz
    exit; 
}

    //tekli hasta ölüm sorgulama
    public function died() {
        $tc = \App\Helpers\ValidationHelper::tcDigitsOnly($_GET['tc'] ?? '');

        if ($tc === '' || !\App\Helpers\ValidationHelper::isTcLength11($tc)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['oldu' => 0, 'error' => 'TC gecersiz'], JSON_UNESCAPED_UNICODE);
            return 0;
        }

        $model = new Patient();
        $patientRow = $model->findByTc($tc);
        if (!$patientRow || empty($patientRow->id)) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(404);
            echo json_encode(['oldu' => 0, 'error' => 'Hasta bulunamadi'], JSON_UNESCAPED_UNICODE);
            return 0;
        }
        if (!PatientAccessHelper::canAccessPatient( $patientRow->id, $patientRow)) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode(['oldu' => 0, 'error' => 'Bu hasta kaydina erisim yetkiniz bulunmamaktadir.'], JSON_UNESCAPED_UNICODE);
            return 0;
        }

        $result = $model->mernisVefatKontrolVeKaydet($tc);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'oldu' => (int) ($result['oldu'] ?? 0),
            'olumTarihi' => $result['olumTarihi'] ?? null,
            'mesaj' => $result['mesaj'] ?? '',
            'source' => $result['source'] ?? 'none',
            'status' => $result['status'] ?? 'alive',
        ], JSON_UNESCAPED_UNICODE);

        return (int) ($result['oldu'] ?? 0);
    }

    /**
    * @desc Hasta not işlemleri
    */
    public function prepareNotes($existingJson, $newNote) {
    // 1. Mevcut notları çöz (Eğer boşsa boş dizi oluştur)
    $notesArray = json_decode($existingJson, true) ?: [];

    $newNote = trim($newNote);

    // 2. Eğer yeni bir not yazılmışsa ekle
    if (!empty($newNote)) {
        $notesArray[] = [
            'date'    => date('d-m-Y H:i'), // Otomatik tarih ve saat
            'user'    => $_SESSION['name'] ?? 'Sistem', // Opsiyonel: Notu yazan kişi
            'message' => htmlspecialchars($newNote)
        ];
    }

    // 3. Tekrar JSON'a çevir (Türkçe karakterleri koruyarak)
    return json_encode($notesArray, JSON_UNESCAPED_UNICODE);
    }
    
    public function updateNotes() {
        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        if ($id === null) {
            $_SESSION['error'] = 'Geçersiz istek.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $patient = new Patient();
        if (!$patient->load($id)) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }
        PatientAccessHelper::requirePatientAccess($id, $patient);
        $this->denyIfPasifKapali($patient, 'hasta notu eklenemez');

        $mevcutNotlar = $patient->notes;
        $new = $this->prepareNotes($mevcutNotlar, $_POST['new_note'] ?? '');

        $patient->set('notes', $new);

        if ($patient->store()) {
            $_SESSION['success'] = 'Hasta notu oluşturuldu.';
            header('Location: ' . esh_url('Patient', 'view', ['id' => $patient->id]));
        } else {
            $_SESSION['error'] = 'Veritabanı hatası: Hasta notu kaydedilemedi.';
            header('Location: ' . esh_url('Patient', 'view', ['id' => $patient->id]));
        }
        exit;
    }

    public function toggleClinicalFlag(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $field = trim((string) ($_POST['field'] ?? ''));
        $value = (int) ($_POST['value'] ?? -1);

        if ($id === null || !in_array($field, \App\Helpers\PatientClinicalFlagsHelper::generalTabToggleKeys(), true)) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz istek.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($value !== 0 && $value !== 1) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz değer.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $patient = new Patient();
        if (!$patient->load($id)) {
            echo json_encode(['success' => false, 'message' => 'Hasta bulunamadı.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!PatientAccessHelper::canAccessPatient($id, $patient)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bu hasta kaydına erişim yetkiniz bulunmamaktadır.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!Patient::isAktif($patient->pasif ?? null)) {
            echo json_encode(['success' => false, 'message' => 'Yalnızca aktif dosyalarda bu alan güncellenebilir.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $patient->set($field, $value);
        if ($field === 'sonda' && $value === 0) {
            $patient->set('sondatarihi', null);
        }

        if (!$patient->store()) {
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $fieldLabel = \App\Helpers\PatientClinicalFlagsHelper::label($field);
        $storedVal = $patient->$field ?? 0;
        $reload = in_array($field, \App\Helpers\PatientClinicalFlagsHelper::summaryChipKeys(), true)
            || $field === 'izolasyon';

        echo json_encode([
            'success' => true,
            'message' => $fieldLabel . ' — ' . ($value ? 'Evet' : 'Hayır') . ' olarak kaydedildi.',
            'field' => $field,
            'value' => $value,
            'badge_html' => \App\Helpers\BadgeHelper::yesNoEvetHayirToggleable($storedVal, $field, $fieldLabel, true),
            'reload' => $reload,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public function deleteNote() {
    header('Content-Type: application/json; charset=utf-8');

    $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
    $index = (int) ($_POST['index'] ?? 0);

    if ($id === null) {
        echo json_encode(['success' => false, 'message' => 'Geçersiz istek.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 1. Mevcut notları çek
    $patient = new Patient();
    if (!$patient->load($id)) {
        echo json_encode(['success' => false, 'message' => 'Hasta bulunamadı.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if (!PatientAccessHelper::canAccessPatient($id, $patient)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bu hasta kaydına erişim yetkiniz bulunmamaktadır.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $currentJson = $patient->notes;
    $notesArray = json_decode($currentJson, true);
    if (!is_array($notesArray)) {
        $notesArray = [];
    }

    if (isset($notesArray[$index])) {
        // 2. Belirtilen index'teki notu sil
        unset($notesArray[$index]);

        // 3. Diziyi yeniden indexle (0,1,2 diye sıralı kalsın)
        $notesArray = array_values($notesArray);

        // 4. Yeni halini kaydet
        $newJson = json_encode($notesArray, JSON_UNESCAPED_UNICODE);
        $patient->set('notes', $newJson);

        if ($patient->store()) {
            echo json_encode(['success' => true, 'message' => 'Not kaydedildi']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Veritabanı hatası.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Not bulunamadı.']);
    }
    exit;
}

    /**
    * Hasta ölüm taraması 
    */
    public function scan() {
        \App\Helpers\AuthHelper::requireAdmin();
        $model = new Patient();
        $scanScope = 'active';
        $scanTitle = 'MERNIS Toplu Vefat Taraması (Aktif Hastalar)';
        $scanInfo = "Bilgi: Aktif hastalar TC Kimlik sırasına göre 20'şerli paketler halinde Denizli B.B. servisinden taranır.";
        $scanConfirm = 'Tum aktif hastalar taranacak. Sadece vefat edenler listelenecektir. Emin misiniz?';
        $scanAction = 'bulkDiedScan';
        $totalCount = $model->countPatientsForScan($scanScope);
        
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'hasta/scan');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function scanWaiting() {
        \App\Helpers\AuthHelper::requireAdmin();
        $model = new Patient();
        $scanScope = 'waiting';
        $scanTitle = 'MERNIS Toplu Vefat Taraması (Bekleyen Hastalar)';
        $scanInfo = "Bilgi: Bekleyen (ilk kayıt) hastalar TC Kimlik sırasına göre 20'şerli paketler halinde Denizli B.B. servisinden taranır.";
        $scanConfirm = 'Tum bekleyen hastalar taranacak. Sadece vefat edenler listelenecektir. Emin misiniz?';
        $scanAction = 'bulkDiedScan';
        $totalCount = $model->countPatientsForScan($scanScope);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'hasta/scan');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function bulkDiedScan() {
        \App\Helpers\AuthHelper::requireAdmin();
    // JS'den gelen offset değerini al
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $limit = 20;
    $scope = isset($_GET['scope']) ? (string) $_GET['scope'] : 'active';
    
    $model = new Patient();  
    
    $patients = $model->getPatientsForScan($offset, $limit, $scope);
    
    $results = [];
    foreach ($patients as $p) {
        $model->reset();

        $tc = \App\Helpers\ValidationHelper::tcDigitsOnly((string) ($p->tckimlik ?? ''));
        if (!ValidationHelper::isTcLength11($tc)) {
            continue;
        }

        $apply = \App\Helpers\PatientVefatCheckHelper::checkAndApplyByTc($tc);
        if (empty($apply['oldu'])) {
            continue;
        }

        $check = (string) ($apply['olumTarihi'] ?? '');
        $results[] = [
            'tc' => \App\Helpers\ValidationHelper::formatTc($p->tckimlik),
            'ad' => $p->isim . ' ' . $p->soyisim,
            'cinsiyet' => (string) ($p->cinsiyet ?? ''),
            'anneAdi' => $p->anneAdi,
            'babaAdi' => $p->babaAdi,
            'ilce' => (string) ($p->ilce_adi ?? ''),
            'mahalle' => (string) ($p->mahalle_adi ?? ''),
            'kayittarihi' => !empty($p->kayittarihi) ? date('d-m-Y', strtotime((string) $p->kayittarihi)) : '—',
            'sonizlem' => !empty($p->sonizlemtarihi) ? date('d-m-Y', strtotime((string) $p->sonizlemtarihi)) : '—',
            'oldu' => '1',
            'tarih' => $check,
            'source' => (string) ($apply['source'] ?? 'belediye'),
        ];
    }

    header('Content-Type: application/json');
    echo json_encode([
        'processed' => count($patients),
        'results'   => $results,
        'nextOffset' => $offset + $limit
    ]);
    exit;
}

    /**
    * Hasta durum değiştirme işlemleri
    */
    public function deletedied() {
        
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $patient = new Patient();
        
        if (!$patient->load($id)) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'passive',
)));
            exit;
        }
        
        $patient->set('pasif', '1');
        $patient->set('pasifnedeni', '2');
        
        $result = $patient->store();

        if ($result) {
            $_SESSION['success'] = "Hasta pasif alındı";
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'passive',
)));
        } else {
            $_SESSION['error'] = "Veritabanı hatası: Hasta bilgileri kaydedilemedi";
            header('Location: ' . esh_url('Patient', 'edit', ['id' => (string) $patient->id]));
        }
        exit;
        
    }
    
    public function deletewaiting() {
        
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $patient = new Patient();
        
        if (!$patient->load($id)) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'waiting',
)));
            exit;
        }
        
        $patient->set('pasif', '5');
        $patient->set('pasifnedeni', '6');
        $patient->set('pasiftarihi', date('Y-m-d'));
        
        $result = $patient->store();

        if ($result) {
            $_SESSION['success'] = "Hasta bekleyenlerden silindi";
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'waiting',
)));
        } else {
            $_SESSION['error'] = "Veritabanı hatası: Hasta bilgileri kaydedilemedi";
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'waiting',
)));
        }
        exit;
        
    }

    /**
     * Silinen dosyayı (pasif=5) tekrar bekleyen ön kayıt (pasif=-3) durumuna alır. Yalnızca yönetici.
     */
    public function deletedToWaiting() {
        if (!AuthHelper::sessionIsAdmin()) {
            $_SESSION['error'] = 'Bu işlem için yetkiniz yok.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
            exit;
        }

        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $patient = new Patient();

        if ($id === null || !$patient->load($id)) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'deleted',
)));
            exit;
        }

        $pasif = $patient->get('pasif');
        $n = is_numeric($pasif) ? (int) $pasif : null;
        if ($n !== 5) {
            $_SESSION['error'] = 'Bu kayıt silinen durumda değil; işlem yapılamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'deleted',
)));
            exit;
        }

        $patient->set('pasif', '-3');
        $patient->set('pasifnedeni', null);
        $patient->set('pasiftarihi', null);

        if ($patient->store()) {
            $_SESSION['success'] = 'Hasta bekleyen listesine alındı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'waiting',
)));
        } else {
            $_SESSION['error'] = 'Veritabanı hatası: Kayıt güncellenemedi.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'deleted',
)));
        }
        exit;
    }

    /**
     * Pasif dosyayı (pasif=1) bekleyen ön kayıt (pasif=-3) durumuna alır.
     */
    public function passiveToWaiting(): void {
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $patient = new Patient();

        if ($id === null || !$patient->load($id)) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'passive',
)));
            exit;
        }

        $pasif = $patient->get('pasif');
        $n = is_numeric($pasif) ? (int) $pasif : null;
        if ($n !== 1) {
            $_SESSION['error'] = 'Bu kayıt pasif dosyada değil; işlem yapılamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'passive',
)));
            exit;
        }

        $patient->set('pasif', '-3');
        $patient->set('pasifnedeni', null);
        $patient->set('pasiftarihi', null);

        if ($patient->store()) {
            $_SESSION['success'] = 'Hasta bekleyen (ön kayıt) listesine alındı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'waiting',
)));
        } else {
            $_SESSION['error'] = 'Veritabanı hatası: Kayıt güncellenemedi.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'passive',
)));
        }
        exit;
    }
    
    public function changeactive() {
        
        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $patient = new Patient();
        
        if (!$patient->load($id)) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'passive',
)));
            exit;
        }
        
        $patient->set('pasif', '0');
        $patient->set('pasifnedeni', NULL);
        $patient->set('pasiftarihi', NULL);
        
        $result = $patient->store();

        if ($result) {
            $_SESSION['success'] = "Hasta aktif edildi";
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'active',
)));
        } else {
            $_SESSION['error'] = "Veritabanı hatası: Hasta bilgileri kaydedilemedi";
            header('Location: ' . esh_url('Patient', 'unified', array (
  'status' => 'passive',
)));
        }
        exit;
        
    }

    /**
     * `#__guvence.guvenceadi` YUPAS olan kayıtların id listesi (büyük/küçük harf duyarsız, trim).
     *
     * @return int[]
     */
    private function getYupasGuvenceIds(): array {
        $list = (new Guvence())->getList();
        if (!is_array($list)) {
            return [];
        }
        $ids = [];
        foreach ($list as $row) {
            $name = isset($row->guvenceadi) ? trim((string) $row->guvenceadi) : '';
            if ($name !== '' && strcasecmp($name, 'YUPAS') === 0) {
                $id = (string) ($row->id ?? '');
                if ($id !== null) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    private function denyIfPasifKapali(object $patient, string $islem): void {
        if (!Patient::isPasifKapali($patient->pasif ?? null)) {
            return;
        }
        $_SESSION['error'] = 'Pasif dosyada ' . $islem . '.';
        $id = (string) ($patient->id ?? '');
        header('Location: ' . ($id !== null ? esh_url('Patient', 'view', ['id' => $id]) : esh_url('Patient', 'unified', ['status' => 'passive'])));
        exit;
    }

    private function patientWoundsUrl(string $patientId): string
    {
        return esh_url('Patient', 'wounds', array (
  'id' => '',
)) . max(0, $patientId);
    }

    private function patientBarthelUrl(string $patientId): string
    {
        return esh_url('Patient', 'barthel', array (
  'id' => '',
)) . max(0, $patientId);
    }

    private function patientBradenUrl(string $patientId): string
    {
        return esh_url('Patient', 'braden', array (
  'id' => '',
)) . max(0, $patientId);
    }

    private function denyBradenIfNotEnabled(object $patient, string $patientId): void
    {
        if (\App\Helpers\PatientClinicalFlagsHelper::isBradenModuleEnabled($patient)) {
            return;
        }
        $_SESSION['error'] = 'Braden ölçeği yalnızca aktif bası yarası işaretli hastalarda kullanılabilir.';
        header('Location: ' . esh_url('Patient', 'view', ['id' => $patientId]));
        exit;
    }

    private function patientItakiUrl(string $patientId): string
    {
        return esh_url('Patient', 'itaki', ['id' => $patientId]);
    }

    private function patientHarizmiUrl(string $patientId): string
    {
        return esh_url('Patient', 'harizmi', ['id' => $patientId]);
    }

    private function patientMnaUrl(string $patientId): string
    {
        return esh_url('Patient', 'mna', ['id' => $patientId]);
    }

    private function denyItakiIfNotEnabled(object $patient, string $patientId): void
    {
        if (\App\Helpers\PatientClinicalFlagsHelper::isItakiModuleEnabled($patient)) {
            return;
        }
        $_SESSION['error'] = 'İTAKİ II ölçeği yalnızca 18 yaş ve üzeri hastalarda kullanılabilir.';
        header('Location: ' . esh_url('Patient', 'view', ['id' => $patientId]));
        exit;
    }

    private function denyHarizmiIfNotEnabled(object $patient, string $patientId): void
    {
        if (\App\Helpers\PatientClinicalFlagsHelper::isHarizmiModuleEnabled($patient)) {
            return;
        }
        $_SESSION['error'] = 'Harizmi II ölçeği yalnızca 0–17 yaş arası hastalarda kullanılabilir.';
        header('Location: ' . esh_url('Patient', 'view', ['id' => $patientId]));
        exit;
    }

    private function denyMnaIfNotEnabled(object $patient, string $patientId): void
    {
        if (\App\Helpers\PatientClinicalFlagsHelper::isMnaModuleEnabled($patient)) {
            return;
        }
        $_SESSION['error'] = 'MNA değerlendirmesi bu hasta için kullanılamaz.';
        header('Location: ' . esh_url('Patient', 'view', ['id' => $patientId]));
        exit;
    }

    private function denyBarthelIfNotEnabled(object $patient, string $patientId): void
    {
        if (\App\Helpers\PatientClinicalFlagsHelper::isBarthelModuleEnabled($patient)) {
            return;
        }
        $_SESSION['error'] = 'Barthel değerlendirmesi bu hasta için kullanılamaz.';
        header('Location: ' . esh_url('Patient', 'view', ['id' => $patientId]));
        exit;
    }

    /**
     * İTAKİ / Harizmi form POST — checkbox ve radio grup alanlarını birleştirir.
     *
     * @return list<string>
     */
    private function collectFallRiskSelections(array $post): array
    {
        $raw = [];
        if (isset($post['secimler']) && is_array($post['secimler'])) {
            foreach ($post['secimler'] as $val) {
                if (is_string($val) && $val !== '') {
                    $raw[] = $val;
                }
            }
        }
        foreach ($post as $key => $val) {
            if (!is_string($key) || !str_starts_with($key, 'secimler_')) {
                continue;
            }
            if (is_string($val) && $val !== '') {
                $raw[] = $val;
            }
        }

        return $raw;
    }

    private function normalizeDateInput($raw): ?string
    {
        $v = trim((string) $raw);
        if ($v === '') {
            return null;
        }
        $ymd = DateHelper::trDateToYmd($v);
        if ($ymd !== null) {
            return $ymd;
        }
        $ts = strtotime($v);
        if ($ts === false) {
            return null;
        }

        return date('Y-m-d', $ts);
    }

    /** İlk kayıt (fsave) için atanacak kurum_id. */
    private function resolveIlkkayitKurumId(string $redirectUrl): int
    {
        if (AuthHelper::sessionIsSuperAdmin()) {
            $kid = isset($_POST['kurum_id']) ? (int) $_POST['kurum_id'] : 0;
            if ($kid <= 0) {
                $_SESSION['error'] = 'Geçerli bir kurum seçin.';
                header('Location: ' . $redirectUrl);
                exit;
            }
            $kurum = new \App\Models\Kurum();
            if (!$kurum->load($kid) || empty($kurum->aktif)) {
                $_SESSION['error'] = 'Seçilen kurum bulunamadı veya pasif.';
                header('Location: ' . $redirectUrl);
                exit;
            }

            return $kid;
        }

        return TenantContext::assignKurumIdForStore();
    }

    /** @return list<string> */
    private function patientFsaveAllowlistKeys(?string $patientId, bool $isAdmin): array
    {
        $keys = [
            'id', 'tckimlik', 'isim', 'soyisim', 'anneAdi', 'babaAdi', 'dogumtarihi', 'cinsiyet',
            'ceptel1', 'ceptel2', 'guvence', 'yupasno', 'kayittarihi', 'randevutarihi', 'zaman',
            'ilce', 'mahalle', 'sokak', 'kapino', 'adres_aciklama', 'diger_adres',
            'adres', 'ana_adres_index', 'hastaliklar', 'bagimlilik',
        ];
        if ($isAdmin) {
            $keys[] = 'pasif';
            $keys[] = 'pasifnedeni';
            $keys[] = 'pasiftarihi';
        }
        if ($patientId !== null && $patientId !== '' && !$isAdmin) {
            $keys = array_values(array_diff($keys, ['tckimlik', 'kayittarihi']));
        }
        if ($patientId !== null && $patientId !== '' && AuthHelper::sessionIsSuperAdmin()) {
            $keys[] = 'kurum_id';
        }
        if ($isAdmin && EsysComplianceHelper::enabled()) {
            $keys[] = 'esys_hasta_ref';
            $keys[] = 'esys_basvuru_ref';
        }
        if ($isAdmin && UsbsComplianceHelper::enabled()) {
            $keys[] = 'enabiz_hasta_ref';
            $keys[] = 'usbs_hasta_ref';
        }

        return $keys;
    }

    /** @return list<string> */
    private function patientStoreAllowlistKeys(bool $isAdmin): array
    {
        $keys = [
            'isim', 'soyisim', 'anneAdi', 'babaAdi', 'tckimlik', 'dogumtarihi', 'cinsiyet',
            'ceptel1', 'ceptel2', 'bakimveren_ad', 'bakimveren_tel', 'bakimveren_yakinlik',
            'alerji', 'acil_not',
            'guvence', 'yupasno', 'ailehekimi', 'ailehekimitel', 'kangrubu', 'kayittarihi', 'randevutarihi', 'gecici', 'erapor',
            'sms_bilgilendirme_onay',
            'boy', 'kilo', 'hastaliklar', 'bagimlilik',
            'pasif', 'pasifnedeni', 'pasiftarihi',
            'ng', 'peg', 'port', 'o2bagimli', 'ventilator', 'kolostomi',
            'trakeostomi', 'cpap', 'aspirasyon', 'ileostomi', 'urostomi', 'picc',
            'dren', 'diyaliz', 'basiyarasi', 'ivtedavi', 'izolasyon',
            'sonda', 'sondatarihi',
            'pansuman', 'pgunleri', 'pzaman', 'mama', 'mamacesit', 'mamaraporyeri', 'mamaraporbitis',
            'bez', 'bezrapor', 'bezraporbitis', 'yatak',
            'ilce', 'mahalle', 'sokak', 'kapino', 'adres_aciklama', 'diger_adres',
            'adres', 'ana_adres_index', 'zaman', 'partial_section',
        ];
        if ($isAdmin && EsysComplianceHelper::enabled()) {
            $keys[] = 'esys_hasta_ref';
            $keys[] = 'esys_basvuru_ref';
        }
        if ($isAdmin && UsbsComplianceHelper::enabled()) {
            $keys[] = 'enabiz_hasta_ref';
            $keys[] = 'usbs_hasta_ref';
        }

        return $keys;
    }

    /**
     * @return array{error: ?string, should_create: bool, hedef: string}
     */
    private function resolveNakilPassiveContext(object $patient, array $data, ?string $patientId): array
    {
        $out = ['error' => null, 'should_create' => false, 'hedef' => ''];
        $pasifVal = isset($data['pasif']) ? (int) $data['pasif'] : (int) ($patient->pasif ?? 0);
        $pasifNedeni = isset($data['pasifnedeni']) ? (int) $data['pasifnedeni'] : (int) ($patient->pasifnedeni ?? 0);

        if ($pasifVal !== 1 || $pasifNedeni !== PatientKurumTransfer::PASIF_NEDENI_NAKIL) {
            return $out;
        }

        $activeErr = PatientNakilRequest::validatePatientEligibleForNakilInit($patient);
        if ($activeErr !== null) {
            $out['error'] = $activeErr;

            return $out;
        }

        $err = PatientNakilRequest::validatePasifNedeniForStore($pasifNedeni, AuthHelper::sessionIsAdmin());
        if ($err !== null) {
            $out['error'] = $err;

            return $out;
        }

        if (!PatientNakilRequest::tableReady()) {
            $out['error'] = 'Nakil modülü henüz kurulmamış. Güncel database/schemas/schema.sql ile kurulum yapın.';

            return $out;
        }

        $hedef = trim((string) ($_POST['nakil_hedef'] ?? ''));
        $err = PatientNakilRequest::validateNakilHedef($hedef, $patient);
        if ($err !== null) {
            $out['error'] = $err;

            return $out;
        }

        if ($patientId !== null && $patientId !== '' && !PatientNakilRequest::hasPending($patientId)) {
            $out['should_create'] = true;
            $out['hedef'] = $hedef;
        }

        return $out;
    }

    /** @param array{error: ?string, should_create: bool, hedef: string} $ctx */
    private function processNakilAfterPassiveSave(object $patient, array $ctx): void
    {
        if (empty($ctx['should_create']) || ($ctx['hedef'] ?? '') === '') {
            return;
        }

        $userId = (AuthHelper::sessionUserId() ?? '');
        if ($userId === null) {
            return;
        }

        $logId = PatientNakilRequest::createFromPassiveSave($patient, (string) $ctx['hedef'], $userId);
        if ($logId === false) {
            $_SESSION['warning'] = 'Hasta kaydedildi ancak nakil talebi oluşturulamadı.';

            return;
        }

        $base = trim((string) ($_SESSION['success'] ?? 'Hasta kaydedildi.'));
        if (PatientNakilRequest::isBolgeHedef((string) $ctx['hedef'])) {
            $_SESSION['success'] = $base . ' Nakil talebi hedef bölgeye iletildi (onay bekleniyor).';
        } elseif (ctype_digit(trim((string) $ctx['hedef']))
            && PatientNakilRequest::detectReturnNakilContext($patient, (int) $ctx['hedef']) !== null) {
            $_SESSION['success'] = $base . ' Geri nakil talebi kaynak kuruma iletildi (onay bekleniyor).';
        } else {
            $_SESSION['success'] = $base . ' Nakil talebi hedef kuruma iletildi (onay bekleniyor).';
        }
    }
}
