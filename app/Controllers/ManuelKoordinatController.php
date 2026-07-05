<?php
namespace App\Controllers;

use App\Helpers\IdHelper;
use App\Core\Database;
use App\Helpers\AuthHelper;
use App\Helpers\GeocodeQuotaHelper;
use App\Helpers\KurumAdresScope;
use App\Helpers\MapRoutingAjaxHelper;
use App\Helpers\MapRoutingGeocodeHelper;
use App\Helpers\OperationalSettings;
use App\Helpers\TenantSqlHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\Address;
use App\Models\Patient;
use App\Services\MapRouting\MapRoutingProviderFactory;

/**
 * Yönetim: haritadan kapı koordinatı manuel seçme / düzeltme.
 */
class ManuelKoordinatController {

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

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonOut(array $payload, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function scopeDeny(string $adresId, ?string $parentId = null): ?string {
        return KurumAdresScope::denyUnlessAllowed($adresId, $parentId);
    }

    public function index(): void {
        $centerLon = defined('START_LNG') ? (float) START_LNG : 29.079663;
        $centerLat = defined('START_LAT') ? (float) START_LAT : 37.783291;
        $activeMapProvider = OperationalSettings::activeMapProviderStatusForAdmin();
        $mapProviderConfigured = MapRoutingGeocodeHelper::isActiveProviderConfigured();
        $keyStatus = MapRoutingProviderFactory::keyStatusForProvider($activeMapProvider['code'] ?? 'tomtom');
        $providerLabel = (string) ($activeMapProvider['label'] ?? 'Harita');
        $pageTitle = 'Manuel koordinat düzeltme';
        $deepLinkHastaId = IdHelper::normalizeRequestId($_GET['hasta_id'] ?? null);
        $deepLinkKapinoId = isset($_GET['kapino_id']) ? trim((string) $_GET['kapino_id']) : '';
        $inlineMap = AuthHelper::sessionIsSuperAdmin() && !empty($mapProviderConfigured);

        $GLOBALS['eshManuelKoordinatPageConfig'] = [
            'center' => [$centerLon, $centerLat],
            'mapConfigUrl' => $inlineMap ? esh_url('ManuelKoordinat', 'mapConfigAjax') : '',
            'searchUrl' => esh_url('ManuelKoordinat', 'searchAjax'),
            'kapinoDetailUrl' => esh_url('ManuelKoordinat', 'kapinoDetailAjax'),
            'geocodePreviewUrl' => esh_url('ManuelKoordinat', 'geocodePreviewAjax'),
            'saveCoordsUrl' => esh_url('ManuelKoordinat', 'saveCoordsAjax'),
            'providerLabel' => $providerLabel,
            'providerConfigured' => $inlineMap,
            'deepLinkHastaId' => $deepLinkHastaId > 0 ? $deepLinkHastaId : null,
            'deepLinkKapinoId' => $deepLinkKapinoId !== '' ? $deepLinkKapinoId : null,
        ];

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'manuel-koordinat/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function mapConfigAjax(): void {
        if (!AuthHelper::sessionIsSuperAdmin()) {
            $this->jsonOut(['ok' => false, 'error' => 'forbidden'], 403);
        }
        $payload = MapRoutingAjaxHelper::mapConfigPayload();
        $this->jsonOut($payload, !empty($payload['ok']) ? 200 : 503);
    }

    public function searchAjax(): void {
        if (!AuthHelper::sessionIsAdmin()) {
            $this->jsonOut(['ok' => false, 'error' => 'forbidden'], 403);
        }

        $hastaId = IdHelper::normalizeRequestId($_GET['hasta_id'] ?? null);
        if ($hastaId !== null) {
            $row = $this->buildSearchRowFromPatientId($hastaId);
            $this->jsonOut(['ok' => true, 'results' => $row ? [$row] : []]);
        }

        $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        if (strlen($q) < 2) {
            $this->jsonOut(['ok' => true, 'results' => []]);
        }

        $results = $this->searchPatients($q, 20);
        $this->jsonOut(['ok' => true, 'results' => $results]);
    }

    public function kapinoDetailAjax(): void {
        if (!AuthHelper::sessionIsAdmin()) {
            $this->jsonOut(['ok' => false, 'error' => 'forbidden'], 403);
        }

        $kapinoId = isset($_GET['kapino_id']) ? trim((string) $_GET['kapino_id']) : '';
        $hastaId = IdHelper::normalizeRequestId($_GET['hasta_id'] ?? null);
        if ($kapinoId === '' && $hastaId > 0) {
            $patient = (new Patient())->getById($hastaId);
            if ($patient) {
                $kapinoId = trim((string) ($patient->kapino ?? ''));
            }
        }
        if ($kapinoId === '') {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Kapı kaydı belirtilmedi.']);
        }

        $address = new Address();
        $row = $address->getRowById($kapinoId);
        if (!$row || (string) $row->tip !== 'kapino') {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Geçersiz kapı kaydı.']);
        }

        $scopeErr = $this->scopeDeny($kapinoId, trim((string) ($row->ust_id ?? '')) ?: null);
        if ($scopeErr !== null) {
            $this->jsonOut(['ok' => false, 'mesaj' => $scopeErr], 403);
        }

        $detail = $this->buildKapinoDetail($kapinoId, $hastaId > 0 ? $hastaId : null);
        if ($detail === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Kapı detayı oluşturulamadı.']);
        }

        $this->jsonOut(['ok' => true, 'detail' => $detail]);
    }

    public function geocodePreviewAjax(): void {
        if (!AuthHelper::sessionIsAdmin()) {
            $this->jsonOut(['ok' => false, 'error' => 'forbidden'], 403);
        }

        $kapinoId = isset($_GET['kapino_id']) ? trim((string) $_GET['kapino_id']) : '';
        if ($kapinoId === '') {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Kapı kaydı belirtilmedi.']);
        }

        $address = new Address();
        $row = $address->getRowById($kapinoId);
        if (!$row || (string) $row->tip !== 'kapino') {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Geçersiz kapı kaydı.']);
        }

        $scopeErr = $this->scopeDeny($kapinoId, trim((string) ($row->ust_id ?? '')) ?: null);
        if ($scopeErr !== null) {
            $this->jsonOut(['ok' => false, 'mesaj' => $scopeErr], 403);
        }

        if (!MapRoutingGeocodeHelper::isActiveProviderConfigured()) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Aktif harita sağlayıcısı API anahtarı tanımlı değil.']);
        }

        if (!GeocodeQuotaHelper::canMakeRequest()) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Günlük geocode kotası doldu.']);
        }

        $query = $address->buildGeocodeQueryForKapinoId($kapinoId);
        if ($query === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Adres metni oluşturulamadı.']);
        }

        $position = MapRoutingGeocodeHelper::firstPosition($query);
        if ($position === null) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Koordinat bulunamadı.']);
        }

        $coords = number_format((float) $position['lat'], 6, '.', '') . ','
            . number_format((float) $position['lon'], 6, '.', '');

        $this->jsonOut([
            'ok' => true,
            'coords' => $coords,
            'query' => $query,
            'lat' => (float) $position['lat'],
            'lon' => (float) $position['lon'],
        ]);
    }

    public function saveCoordsAjax(): void {
        if (!AuthHelper::sessionIsAdmin()) {
            $this->jsonOut(['ok' => false, 'error' => 'forbidden'], 403);
        }

        $kapinoId = isset($_POST['kapino_id']) ? trim((string) $_POST['kapino_id']) : '';
        $coordsRaw = isset($_POST['coords']) ? trim((string) $_POST['coords']) : '';
        $clear = isset($_POST['clear']) && (string) $_POST['clear'] === '1';

        if ($kapinoId === '') {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Kapı kaydı belirtilmedi.']);
        }

        $address = new Address();
        $row = $address->getRowById($kapinoId);
        if (!$row || (string) $row->tip !== 'kapino') {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Geçersiz kapı kaydı.']);
        }

        $scopeErr = $this->scopeDeny($kapinoId, trim((string) ($row->ust_id ?? '')) ?: null);
        if ($scopeErr !== null) {
            $this->jsonOut(['ok' => false, 'mesaj' => $scopeErr], 403);
        }

        $coords = $clear ? '' : Address::normalizeCoordsString($coordsRaw);
        if (!$clear && $coords === '') {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Geçerli koordinat girin (enlem,boylam).']);
        }

        if (!$address->setKapinoCoords($kapinoId, $coords, false)) {
            $this->jsonOut(['ok' => false, 'mesaj' => 'Koordinat kaydedilemedi.']);
        }

        $this->jsonOut([
            'ok' => true,
            'coords' => $coords,
            'has_coords' => $coords !== '' ? 1 : 0,
            'mesaj' => $clear ? 'Koordinat temizlendi.' : 'Koordinat kaydedildi.',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchPatients(string $q, int $limit): array {
        $db = Database::getInstance();
        $lim = max(1, min(30, $limit));
        $digits = preg_replace('/\D+/', '', $q);
        $eff = Address::effectiveCoordsExpr('h', 'k');
        $kapinoJoin = Address::kapinoJoinSql('h', 'k');
        $kurumScope = TenantSqlHelper::andEquals('h', 'kurum_id');

        $select = "SELECT h.id, h.tckimlik, h.isim, h.soyisim, h.kapino, h.pasif,
            a1.adi AS ilce_adi, a2.adi AS mahalle_adi, a3.adi AS sokak_adi, k.adi AS kapino_adi,
            {$eff} AS coords";

        $from = "FROM #__hastalar AS h
            LEFT JOIN #__adrestablosu AS a1 ON h.ilce = a1.id
            LEFT JOIN #__adrestablosu AS a2 ON h.mahalle = a2.id
            LEFT JOIN #__adrestablosu AS a3 ON h.sokak = a3.id
            {$kapinoJoin}";

        $statusWhere = "(h.pasif = '0' OR h.pasif = '-3')";

        if (strlen($digits) >= 2) {
            $sql = "{$select} {$from}
                WHERE {$statusWhere} AND h.tckimlik LIKE ?{$kurumScope}
                ORDER BY h.tckimlik ASC
                LIMIT {$lim}";
            $params = [$digits . '%'];
        } else {
            $esc = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
            $like = '%' . $esc . '%';
            $sql = "{$select} {$from}
                WHERE {$statusWhere}{$kurumScope}
                  AND (
                    CONCAT(TRIM(COALESCE(h.isim, '')), ' ', TRIM(COALESCE(h.soyisim, ''))) LIKE ?
                    OR TRIM(COALESCE(h.isim, '')) LIKE ?
                    OR TRIM(COALESCE(h.soyisim, '')) LIKE ?
                    OR CONCAT(COALESCE(a1.adi,''), ' ', COALESCE(a2.adi,''), ' ', COALESCE(a3.adi,''), ' ', COALESCE(k.adi,'')) LIKE ?
                  )
                ORDER BY h.isim ASC, h.soyisim ASC, h.tckimlik ASC
                LIMIT {$lim}";
            $params = [$like, $like, $like, $like];
        }

        $list = $db->fetchObjectListPrepared($sql, $params);
        if (!is_array($list)) {
            return [];
        }

        $out = [];
        foreach ($list as $row) {
            $mapped = $this->mapSearchRow($row);
            if ($mapped !== null) {
                $out[] = $mapped;
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildSearchRowFromPatientId(int|string $hastaId): ?array {
        $patient = (new Patient())->getById($hastaId);
        if (!$patient) {
            return null;
        }

        $kapinoId = trim((string) ($patient->kapino ?? ''));
        if ($kapinoId !== '') {
            $scopeErr = $this->scopeDeny($kapinoId);
            if ($scopeErr !== null) {
                return null;
            }
        }

        $db = Database::getInstance();
        $eff = Address::effectiveCoordsExpr('h', 'k');
        $kapinoJoin = Address::kapinoJoinSql('h', 'k');
        $sql = "SELECT h.id, h.tckimlik, h.isim, h.soyisim, h.kapino, h.pasif,
            a1.adi AS ilce_adi, a2.adi AS mahalle_adi, a3.adi AS sokak_adi, k.adi AS kapino_adi,
            {$eff} AS coords
            FROM #__hastalar AS h
            LEFT JOIN #__adrestablosu AS a1 ON h.ilce = a1.id
            LEFT JOIN #__adrestablosu AS a2 ON h.mahalle = a2.id
            LEFT JOIN #__adrestablosu AS a3 ON h.sokak = a3.id
            {$kapinoJoin}
            WHERE h.id = ?
            LIMIT 1";
        $row = $db->fetchObjectPrepared($sql, [$hastaId]);

        return $row ? $this->mapSearchRow($row) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapSearchRow(object $row): ?array {
        $kapinoId = trim((string) ($row->kapino ?? ''));
        if ($kapinoId !== '') {
            $scopeErr = $this->scopeDeny($kapinoId);
            if ($scopeErr !== null) {
                return null;
            }
        }

        $adresParts = array_filter([
            trim((string) ($row->ilce_adi ?? '')),
            trim((string) ($row->mahalle_adi ?? '')),
            trim((string) ($row->sokak_adi ?? '')),
            trim((string) ($row->kapino_adi ?? '')),
        ], static fn ($v) => $v !== '');

        $isim = trim((string) ($row->isim ?? '') . ' ' . (string) ($row->soyisim ?? ''));
        $pasif = (string) ($row->pasif ?? '0');
        $durum = $pasif === '-3' ? 'bekleyen' : 'aktif';

        return [
            'hasta_id' => (string) ($row->id ?? ''),
            'tckimlik' => (string) ($row->tckimlik ?? ''),
            'isim' => $isim,
            'durum' => $durum,
            'kapino_id' => $kapinoId,
            'adres_ozet' => implode(' · ', $adresParts),
            'coords' => Address::normalizeCoordsString((string) ($row->coords ?? '')),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildKapinoDetail(string $kapinoId, int|string|null $hastaId = null): ?array {
        $address = new Address();
        $query = $address->buildGeocodeQueryForKapinoId($kapinoId);
        $row = $address->getRowById($kapinoId);
        if (!$row) {
            return null;
        }

        $db = Database::getInstance();
        $sql = 'SELECT i.adi AS ilce, m.adi AS mahalle, s.adi AS sokak, k.adi AS kapino, k.coords
                FROM #__adrestablosu AS k
                LEFT JOIN #__adrestablosu AS s ON s.id = k.ust_id
                LEFT JOIN #__adrestablosu AS m ON m.id = s.ust_id
                LEFT JOIN #__adrestablosu AS i ON i.id = m.ust_id
                WHERE k.id = ? AND k.tip = ?';
        $addrRow = $db->fetchObjectPrepared($sql, [$kapinoId, 'kapino']);
        if (!$addrRow) {
            return null;
        }

        $adresParts = array_filter([
            trim((string) ($addrRow->ilce ?? '')),
            trim((string) ($addrRow->mahalle ?? '')),
            trim((string) ($addrRow->sokak ?? '')),
            trim((string) ($addrRow->kapino ?? '')),
        ], static fn ($v) => $v !== '');

        $countRow = $db->fetchOnePrepared(
            "SELECT COUNT(*) AS cnt FROM #__hastalar WHERE kapino = ? AND pasif IN ('0', '-3')",
            [$kapinoId]
        );
        $hastaCount = (int) ($countRow['cnt'] ?? 0);

        $hastaInfo = null;
        if ($hastaId !== null && $hastaId > 0) {
            $patient = (new Patient())->getById($hastaId);
            if ($patient) {
                $hastaInfo = [
                    'id' => $hastaId,
                    'tckimlik' => (string) ($patient->tckimlik ?? ''),
                    'isim' => trim((string) ($patient->isim ?? '') . ' ' . (string) ($patient->soyisim ?? '')),
                    'view_url' => esh_url('Patient', 'view', ['id' => $hastaId]),
                ];
            }
        }

        return [
            'kapino_id' => $kapinoId,
            'kapino_adi' => trim((string) ($addrRow->kapino ?? '')),
            'adres_tam' => implode(', ', $adresParts),
            'geocode_query' => $query,
            'coords' => Address::normalizeCoordsString((string) ($addrRow->coords ?? '')),
            'hasta_sayisi' => $hastaCount,
            'hasta' => $hastaInfo,
        ];
    }
}
