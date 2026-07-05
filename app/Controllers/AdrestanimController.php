<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\FederationAdresBolgeSync;
use App\Helpers\KurumAdresScope;
use App\Helpers\MapRoutingAjaxHelper;
use App\Helpers\MapRoutingGeocodeHelper;
use App\Helpers\TenantContext;
use App\Helpers\ThemeViewHelper;
use App\Models\Address;
use App\Services\MapRouting\MapRoutingProviderFactory;

/**
 * Admin: #__adrestablosu — hiyerarşik bölge → ilçe → mahalle → sokak → kapı.
 */
class AdrestanimController {

    private static $validTips = ['bolge', 'ilce', 'mahalle', 'sokak', 'kapino'];

    public function __construct() {
        if (AuthHelper::sessionIsAdmin()) {
            return;
        }
        $action = (string) ($GLOBALS['actionName'] ?? '');
        if ($action !== 'index' && (str_starts_with($action, 'ajax') || str_ends_with($action, 'Ajax'))) {
            AuthHelper::requireAdminJson();
        }
        AuthHelper::requireAdmin();
    }

    private function validTip($tip) {
        return in_array($tip, self::$validTips, true) ? $tip : 'bolge';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonOut($payload) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function scopeDeny(string $adresId, ?string $parentId = null): ?string
    {
        return KurumAdresScope::denyUnlessAllowed($adresId, $parentId);
    }

    public function index() {
        $pageTitle = 'Hiyerarşik Adres Yönetimi';
        $eshAdrestanimCanViewBolge = AuthHelper::sessionIsSuperAdmin();
        $eshAdrestanimCanManageIlce = AuthHelper::sessionIsSuperAdmin();
        $eshAdrestanimCanManageBolge = AuthHelper::sessionIsSuperAdmin();
        $eshAdrestanimPreselectBolgeId = '';
        $activeMapCode = MapRoutingProviderFactory::activeCode();
        $eshAdrestanimMapProviderLabel = MapRoutingProviderFactory::LABELS[$activeMapCode] ?? 'Harita';
        $eshAdrestanimMapConfigured = MapRoutingGeocodeHelper::isActiveProviderConfigured();
        $centerLon = defined('START_LNG') ? (float) START_LNG : 29.079663;
        $centerLat = defined('START_LAT') ? (float) START_LAT : 37.783291;
        $eshAdrestanimInlineMap = AuthHelper::sessionIsSuperAdmin() && !empty($eshAdrestanimMapConfigured);
        $GLOBALS['eshAdrestanimMapConfig'] = [
            'center' => [$centerLon, $centerLat],
            'mapConfigUrl' => $eshAdrestanimInlineMap ? esh_url('Adrestanim', 'ajaxMapConfig') : '',
            'providerConfigured' => $eshAdrestanimInlineMap,
            'providerLabel' => $eshAdrestanimMapProviderLabel,
            'manuelKoordinatUrl' => \App\Helpers\AppSettings::isModuleEnabled('manuel_koordinat')
                ? esh_url('ManuelKoordinat', 'index')
                : '',
        ];
        $fedFilter = TenantContext::effectiveBolgeFilterId();
        if ($fedFilter !== null && $fedFilter > 0 && FederationAdresBolgeSync::columnReady()) {
            $linked = FederationAdresBolgeSync::findAdresBolgeIdByFederationId($fedFilter);
            if ($linked !== null) {
                $eshAdrestanimPreselectBolgeId = $linked;
            }
        }
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'adrestanim/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function ajaxMapConfig(): void {
        if (!AuthHelper::sessionIsSuperAdmin()) {
            http_response_code(403);
            $this->jsonOut(['ok' => false, 'error' => 'forbidden']);
        }
        $payload = MapRoutingAjaxHelper::mapConfigPayload();
        if (empty($payload['ok'])) {
            http_response_code(503);
        }
        $this->jsonOut($payload);
    }

    /**
     * AJAX: üst kayda göre çocuk listesi [{id, adi}, ...]
     */
    public function ajaxList() {
        $tip = $this->validTip(isset($_GET['tip']) ? (string) $_GET['tip'] : 'bolge');
        $ustRaw = isset($_GET['ust_id']) ? (string) $_GET['ust_id'] : '0';
        if ($tip === 'bolge' && !AuthHelper::sessionIsSuperAdmin()) {
            $this->jsonOut([]);
        }
        $ustId = $tip === 'bolge' ? '0' : trim($ustRaw);

        $model = new Address();
        $list = $model->adminListByTipUst($tip, $ustId);
        $this->jsonOut($list);
    }

    /**
     * AJAX: Denizli API ile birleştir (mevcut kayıtlar korunur, API'deki yeni id'ler eklenir).
     * GET: parent_id, tip = mahalle | sokak | kapino
     */
    public function ajaxExternalFill() {
        $parentId = isset($_GET['parent_id']) ? trim((string) $_GET['parent_id']) : '';
        $tip = isset($_GET['tip']) ? trim((string) $_GET['tip']) : '';
        if (!in_array($tip, ['mahalle', 'sokak', 'kapino'], true)) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Geçersiz tip.']);
        }
        if ($parentId === '') {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'parent_id gerekli.']);
        }
        $scopeErr = $this->scopeDeny($parentId);
        if ($scopeErr !== null) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => $scopeErr]);
        }
        $model = new Address();
        $res = $model->mergeExternalChildren($parentId, $tip);
        if (empty($res['ok'])) {
            $this->jsonOut([
                'durum' => 'hata',
                'mesaj' => isset($res['mesaj']) ? (string) $res['mesaj'] : 'Senkron başarısız.',
            ]);
        }
        $payload = [
            'durum' => 'tamam',
            'api_kayit' => (int) ($res['api_kayit'] ?? 0),
        ];
        if ($tip === 'kapino') {
            $geo = $model->syncKapinoCoordsUnderSokak($parentId, 35);
            $payload['geocode_bulunan'] = (int) ($geo['bulunan'] ?? 0);
            $payload['geocode_guncellenen'] = (int) ($geo['guncellenen'] ?? 0);
            $payload['geocode_kalan'] = (int) ($geo['kalan'] ?? 0);
            if (!empty($geo['last_id'])) {
                $payload['geocode_last_id'] = (string) $geo['last_id'];
            }
        }
        $this->jsonOut($payload);
    }

    /**
     * AJAX: kayıt ekle / yalnızca ad düzenle (üst ve tip sabit).
     */
    public function ajaxSave() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Geçersiz istek.']);
        }

        $id = isset($_POST['id']) ? trim((string) $_POST['id']) : '';
        $adi = isset($_POST['adi']) ? trim((string) $_POST['adi']) : '';
        $tip = $this->validTip(isset($_POST['tip']) ? (string) $_POST['tip'] : '');
        $ustId = isset($_POST['ust_id']) ? trim((string) $_POST['ust_id']) : '';

        if ($adi === '') {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Ad alanı boş olamaz.']);
        }

        $model = new Address();

        if ($id !== '') {
            $scopeErr = $this->scopeDeny($id, $ustId !== '' ? $ustId : null);
            if ($scopeErr !== null) {
                $this->jsonOut(['durum' => 'hata', 'mesaj' => $scopeErr]);
            }
            $row = $model->adminGetRowById($id, $ustId !== '' ? $ustId : null);
            if (!$row) {
                $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Kayıt bulunamadı.']);
            }
            if ((string) ($row->tip ?? '') === 'bolge' && !AuthHelper::sessionIsSuperAdmin()) {
                $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Bölge düzenleme yetkiniz bulunmamaktadır.']);
            }
            if ((string) ($row->tip ?? '') === 'ilce' && !AuthHelper::sessionIsSuperAdmin()) {
                $this->jsonOut(['durum' => 'hata', 'mesaj' => 'İlçe düzenleme yetkiniz bulunmamaktadır.']);
            }
            if ($tip === 'kapino') {
                $coordsRaw = isset($_POST['coords']) ? trim((string) $_POST['coords']) : null;
                if ($model->adminUpdateKapinoRow($id, $adi, $coordsRaw, $ustId !== '' ? $ustId : null)) {
                    $out = ['durum' => 'tamam'];
                    if ($coordsRaw === null || $coordsRaw === '') {
                        $geo = $model->geocodeKapinoById($id, true);
                        if (!empty($geo['coords'])) {
                            $out['coords'] = $geo['coords'];
                        }
                    }
                    $this->jsonOut($out);
                }
            } elseif ($model->adminUpdateAdiOnly($id, $adi, $ustId !== '' ? $ustId : null)) {
                $this->jsonOut(['durum' => 'tamam']);
            }
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Güncelleme başarısız.']);
        }

        if ($tip === 'bolge') {
            if (!AuthHelper::sessionIsSuperAdmin()) {
                $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Yeni bölge ekleme yetkiniz bulunmamaktadır.']);
            }
            $ustId = '0';
        } elseif ($tip === 'ilce') {
            if (!AuthHelper::sessionIsSuperAdmin()) {
                $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Yeni ilçe ekleme yetkiniz bulunmamaktadır.']);
            }
            if ($ustId === '') {
                $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Üst bölge seçilmedi.']);
            }
        } elseif ($ustId === '') {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Üst kayıt seçilmedi.']);
        } else {
            $scopeErr = $this->scopeDeny($ustId);
            if ($scopeErr !== null) {
                $this->jsonOut(['durum' => 'hata', 'mesaj' => $scopeErr]);
            }
        }

        if ($tip !== 'bolge' && !$model->adminValidateParentForChild($tip, $ustId)) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Üst kayıt geçersiz veya tip uyuşmuyor.']);
        }

        $newId = Address::generateUuidV4();
        if ($model->adminInsertRow($newId, $adi, $ustId, $tip)) {
            $out = ['durum' => 'tamam', 'id' => $newId];
            if ($tip === 'kapino') {
                $geo = $model->geocodeKapinoById($newId, true);
                if (!empty($geo['coords'])) {
                    $out['coords'] = $geo['coords'];
                }
            }
            $this->jsonOut($out);
        }
        $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Kayıt eklenemedi (ID çakışması veya veritabanı hatası).']);
    }

    /**
     * AJAX: sil (alt kayıt ve hasta kullanımı kontrolü).
     */
    public function ajaxDelete() {
        $id = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
        $ustId = isset($_GET['ust_id']) ? trim((string) $_GET['ust_id']) : '';
        if ($id === '') {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'ID eksik.']);
        }

        $scopeErr = $this->scopeDeny($id, $ustId !== '' ? $ustId : null);
        if ($scopeErr !== null) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => $scopeErr]);
        }

        $model = new Address();
        $row = $model->adminGetRowById($id, $ustId !== '' ? $ustId : null);
        if (!$row) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Kayıt bulunamadı.']);
        }
        if ((string) ($row->tip ?? '') === 'bolge' && !AuthHelper::sessionIsSuperAdmin()) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Bölge silme yetkiniz bulunmamaktadır.']);
        }
        if ((string) ($row->tip ?? '') === 'ilce' && !AuthHelper::sessionIsSuperAdmin()) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'İlçe silme yetkiniz bulunmamaktadır.']);
        }

        if ($model->adminChildCount($id, $ustId !== '' ? $ustId : null) > 0) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Bu birimin altında kayıtlar var. Önce onları silmelisiniz.']);
        }

        if ($model->adminPatientReferenceCount($id) > 0) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Bu adres hasta kayıtlarında kullanılıyor; silinemez.']);
        }

        if ($model->adminDeleteById($id, $ustId !== '' ? $ustId : null)) {
            $this->jsonOut(['durum' => 'tamam']);
        }
        $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Silme işlemi başarısız.']);
    }

    /**
     * AJAX: kapı no için geocode; sonucu #__adrestablosu.coords yazar.
     * POST: id (kapino kayıt id)
     */
    public function ajaxGeocodeKapino() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Geçersiz istek.']);
        }
        $id = isset($_POST['id']) ? trim((string) $_POST['id']) : '';
        if ($id === '') {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Kapı kaydı belirtilmedi.']);
        }
        $scopeErr = $this->scopeDeny($id);
        if ($scopeErr !== null) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => $scopeErr]);
        }
        $model = new Address();
        if (!$model->adminGetRowById($id)) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Kapı kaydı bulunamadı veya erişim yok.']);
        }
        $geo = $model->reconcileKapinoCoordsById($id);
        if (empty($geo['ok'])) {
            $this->jsonOut([
                'durum' => 'hata',
                'mesaj' => isset($geo['mesaj']) ? (string) $geo['mesaj'] : 'Koordinat bulunamadı.',
            ]);
        }
        $this->jsonOut([
            'durum' => 'tamam',
            'coords' => $geo['coords'] ?? '',
            'changed' => !empty($geo['changed']),
        ]);
    }

    /**
     * AJAX: sokak altı kapılar — geocode ile karşılaştır, eksik/değişmiş coords güncelle (parti).
     * POST: sokak_id, limit, after_id (isteğe bağlı)
     */
    public function ajaxGeocodeKapinoBulk() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Geçersiz istek.']);
        }
        $sokakId = isset($_POST['sokak_id']) ? trim((string) $_POST['sokak_id']) : '';
        if ($sokakId === '') {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Sokak seçilmedi.']);
        }
        $scopeErr = $this->scopeDeny($sokakId);
        if ($scopeErr !== null) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => $scopeErr]);
        }
        $limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 35;
        $afterId = isset($_POST['after_id']) ? trim((string) $_POST['after_id']) : '';
        $model = new Address();
        $geo = $model->syncKapinoCoordsUnderSokak($sokakId, $limit, $afterId);
        $this->jsonOut(array_merge(['durum' => 'tamam'], $geo));
    }
}
