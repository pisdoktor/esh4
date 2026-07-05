<?php
namespace App\Controllers;

use App\Helpers\IdHelper;
use App\Helpers\AuthHelper;
use App\Helpers\CinsiyetHelper;
use App\Helpers\MapRoutingAjaxHelper;
use App\Helpers\MapRoutingGeocodeHelper;
use App\Helpers\OperationalSettings;
use App\Helpers\StatsQueryCache;
use App\Helpers\TenantContext;
use App\Helpers\ThemeViewHelper;
use App\Models\Address;
use App\Models\Patient;
use App\Services\MapRouting\MapRoutingProviderFactory;

/**
 * Yönetim: hasta haritası (çoklu sağlayıcı).
 */
class HaritaController {

    public function __construct() {
        AuthHelper::requireSuperAdmin();
    }

    public function index(): void {
        $rows = $this->loadMapPatientRows();
        $mapPayload = $this->buildMapMarkers($rows);
        $centerLon = defined('START_LNG') ? (float) START_LNG : 29.079663;
        $centerLat = defined('START_LAT') ? (float) START_LAT : 37.783291;
        $mapConfigUrl = esh_url('Harita', 'mapConfigAjax');
        $mapKeyUrl = esh_url('Harita', 'mapConfigAjax');
        $routeUrl = esh_url('Harita', 'routeAjax');
        $activeMapProvider = OperationalSettings::activeMapProviderStatusForAdmin();
        $mapProviderConfigured = MapRoutingGeocodeHelper::isActiveProviderConfigured();
        $keyStatus = MapRoutingProviderFactory::keyStatusForProvider($activeMapProvider['code']);
        $eshHaritaScopeLabel = $this->haritaScopeLabel();
        $eshHaritaPatientCount = count($mapPayload);
        $eshHaritaCounts = $this->summarizeMarkerCounts($mapPayload);
        $pageTitle = 'Hasta haritası';
        $mahalleCombinedUrl = '';
        $mahalleGeoNameAllowlist = (new Address())->getMahalleAdlariForHaritaGeoPamukkaleMerkezefendi();
        $mahalleGeoPath = ROOT_PATH . '/public/geo/denizli_pamukkale_merkezefendi_mahalleler.geojson';
        if (is_file($mahalleGeoPath)) {
            $mahalleCombinedUrl = rtrim((string) SITEURL, '/') . '/public/geo/denizli_pamukkale_merkezefendi_mahalleler.geojson';
        }

        $GLOBALS['eshHaritaPageConfig'] = [
            'patients' => $mapPayload,
            'center' => [$centerLon, $centerLat],
            'mapConfigUrl' => $mapConfigUrl,
            'mapKeyUrl' => $mapKeyUrl,
            'routeUrl' => $routeUrl,
            'providerLabel' => (string) ($activeMapProvider['label'] ?? 'Harita'),
            'providerConfigured' => !empty($mapProviderConfigured),
            'patientCount' => $eshHaritaPatientCount,
            'mahalleCombinedUrl' => $mahalleCombinedUrl,
            'mahalleGeoNameAllowlist' => $mahalleGeoNameAllowlist,
        ];

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'harita/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * @return array<int, object>
     */
    private function loadMapPatientRows(): array {
        $cacheKey = 'harita_map_rows_' . TenantContext::haritaScopeCacheKey();
        $cached = StatsQueryCache::get($cacheKey, 600);
        if (is_array($cached)) {
            $rows = [];
            foreach ($cached as $item) {
                if (is_array($item)) {
                    $rows[] = (object) $item;
                }
            }
            if ($rows !== []) {
                return $rows;
            }
        }

        $rows = (new Patient())->getActivePatientsWithCoordsForMap();
        $payload = [];
        foreach ($rows as $row) {
            $payload[] = (array) $row;
        }
        if ($payload !== []) {
            StatsQueryCache::set($cacheKey, $payload, 600);
        }

        return $rows;
    }

    private function haritaScopeLabel(): string
    {
        if (\App\Helpers\FederationHelper::enabled()) {
            if (AuthHelper::sessionIsPlatformOwner()) {
                $bolgeId = \App\Helpers\FederationContext::sessionBolgeFilter();
                if ($bolgeId !== null && $bolgeId > 0) {
                    return \App\Helpers\FederationHelper::kurumBolgeLabel($bolgeId);
                }

                return 'Tüm bölgeler';
            }
            $assigned = TenantContext::sessionAssignedBolgeId();
            if ($assigned !== null && $assigned > 0) {
                return \App\Helpers\FederationHelper::kurumBolgeLabel($assigned);
            }

            if (AuthHelper::sessionIsSuperAdminOnly() && TenantContext::sessionKurumFilter() === null) {
                return 'Bölge atanmamış';
            }
        }

        $kurumFilter = TenantContext::sessionKurumFilter();
        if ($kurumFilter !== null && $kurumFilter > 0) {
            $kurum = new \App\Models\Kurum();
            if ($kurum->load($kurumFilter)) {
                $ad = trim((string) ($kurum->ad ?? ''));
                if ($ad !== '') {
                    return $ad;
                }
            }

            return 'Kurum #' . $kurumFilter;
        }

        $ids = TenantContext::filterKurumIds();
        if ($ids === []) {
            return 'Kapsam dışı';
        }
        if ($ids === null) {
            return 'Tüm bölgeler';
        }

        return count($ids) === 1 ? ('Kurum #' . (int) $ids[0]) : (count($ids) . ' kurum');
    }

    /**
     * @param array<int, array<string, mixed>> $markers
     * @return array{all: int, erkek: int, kadin: int}
     */
    private function summarizeMarkerCounts(array $markers): array
    {
        $erkek = 0;
        $kadin = 0;
        foreach ($markers as $marker) {
            if (CinsiyetHelper::isErkek($marker['cinsiyet'] ?? null)) {
                $erkek++;
            } elseif (CinsiyetHelper::isKadin($marker['cinsiyet'] ?? null)) {
                $kadin++;
            }
        }

        return ['all' => count($markers), 'erkek' => $erkek, 'kadin' => $kadin];
    }

    /**
     * @param array<int, object> $rows
     * @return array<int, array<string, mixed>>
     */
    private function buildMapMarkers(array $rows): array {
        $out = [];
        foreach ($rows as $row) {
            $coords = trim((string) ($row->coords ?? ''));
            if ($coords === '') {
                continue;
            }
            $parts = array_map('trim', explode(',', $coords));
            if (count($parts) < 2) {
                continue;
            }
            $lat = (float) $parts[0];
            $lng = (float) $parts[1];
            if ($lat === 0.0 && $lng === 0.0) {
                continue;
            }

            $isimRaw = trim((string) ($row->isim ?? '') . ' ' . (string) ($row->soyisim ?? ''));
            $isimPlain = function_exists('mb_strtoupper') ? mb_strtoupper($isimRaw, 'UTF-8') : strtoupper($isimRaw);
            $tamIsim = htmlspecialchars($isimPlain, ENT_QUOTES, 'UTF-8');
            $tcEsc = htmlspecialchars((string) ($row->tckimlik ?? ''), ENT_QUOTES, 'UTF-8');
            $sonIzlemRaw = $this->formatMapLastVisit($row->sonizlem ?? null);
            $sonIzlem = htmlspecialchars($sonIzlemRaw, ENT_QUOTES, 'UTF-8');

            $patientId = (string) ($row->id ?? '');
            $viewUrl = esh_url('Patient', 'view', ['id' => $patientId]);

            $popupHtml = "<div class='esh-harita-popup-card'>";
            $popupHtml .= "<div class='esh-harita-popup-card__head'><span class='esh-harita-popup-card__title'>" . $tamIsim . "</span></div>";
            $popupHtml .= "<div class='esh-harita-popup-card__body'>";
            $popupHtml .= "<dl class='esh-harita-popup-card__dl'>";
            $popupHtml .= "<div class='esh-harita-popup-card__row'><dt>TC</dt><dd>" . $tcEsc . "</dd></div>";
            $adresRaw = $this->formatMapAddressBrief($row);
            $adresEsc = htmlspecialchars($adresRaw, ENT_QUOTES, 'UTF-8');
            $adresDd = $adresRaw !== ''
                ? "<dd class='esh-harita-popup-card__dd--address'>" . $adresEsc . '</dd>'
                : "<dd class='esh-harita-popup-card__dd--address esh-harita-popup-card__dd--muted'>—</dd>";
            $popupHtml .= "<div class='esh-harita-popup-card__row'><dt>Adres</dt>" . $adresDd . '</div>';
            $popupHtml .= "<div class='esh-harita-popup-card__row'><dt>Son izlem</dt><dd class='esh-harita-popup-card__dd--accent'>" . $sonIzlem . "</dd></div>";
            $popupHtml .= "</dl></div>";

            $ceptel = preg_replace('/\D+/', '', (string) ($row->ceptel1 ?? ''));
            $popupHtml .= "<div class='esh-harita-popup-card__actions'>";
            $popupHtml .= "<a href='" . htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') . "' class='btn btn-primary btn-sm'><i class='fa-solid fa-id-card me-1'></i>Hasta kartı</a>";
            if (\App\Helpers\AppSettings::isModuleEnabled('manuel_koordinat') && AuthHelper::sessionIsAdmin()) {
                $mkUrl = esh_url('ManuelKoordinat', 'index', ['hasta_id' => $patientId]);
                $popupHtml .= "<a href='" . htmlspecialchars($mkUrl, ENT_QUOTES, 'UTF-8') . "' class='btn btn-warning btn-sm'><i class='fa-solid fa-map-pin me-1'></i>Konumu düzelt</a>";
            }
            $rotaCoordsEsc = htmlspecialchars(json_encode([(float) $lng, (float) $lat], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
            $popupHtml .= "<button type='button' class='btn btn-success btn-sm' data-esh-harita-rota='" . $rotaCoordsEsc . "'><i class='fa-solid fa-route me-1'></i>Rota</button>";
            if ($ceptel !== '') {
                $ceptelEsc = htmlspecialchars($ceptel, ENT_QUOTES, 'UTF-8');
                $popupHtml .= "<a href='tel:" . $ceptelEsc . "' class='btn btn-outline-secondary btn-sm'><i class='fa-solid fa-phone me-1'></i>" . $ceptelEsc . "</a>";
            }
            $popupHtml .= "</div></div>";

            $out[] = [
                'id' => $patientId,
                'tc' => (string) ($row->tckimlik ?? ''),
                'isim' => $isimPlain,
                'lng' => $lng,
                'lat' => $lat,
                'cinsiyet' => $row->cinsiyet ?? null,
                'html' => $popupHtml,
            ];
        }
        return $out;
    }

  private function formatMapAddressBrief(object $row): string {
        $parts = [];
        foreach (['ilce_adi', 'mahalle_adi', 'sokak_adi', 'kapino'] as $key) {
            $v = isset($row->$key) ? trim((string) $row->$key) : '';
            if ($v !== '') {
                $parts[] = $v;
            }
        }
        return implode(' · ', $parts);
    }

    private function formatMapLastVisit($raw): string {
        if ($raw === null || $raw === '') {
            return 'Belirtilmedi';
        }
        $ts = strtotime((string) $raw);
        if ($ts === false) {
            return 'Belirtilmedi';
        }
        return date('d-m-Y', $ts);
    }

    /** Süper yönetici — harita istemcisi yapılandırması. */
    public function mapConfigAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!AuthHelper::sessionIsSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(MapRoutingAjaxHelper::mapConfigPayload(), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** @deprecated mapConfigAjax alias */
    public function tomtomMapKeyAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!AuthHelper::sessionIsSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(MapRoutingAjaxHelper::legacyMapKeyPayload(), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Süper yönetici — rota hesabı proxy. POST JSON: { locations: [[lon,lat],...] } */
    public function routeAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!AuthHelper::sessionIsSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE);
            exit;
        }

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

        $result = MapRoutingAjaxHelper::routePayload($payload['locations']);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** @deprecated routeAjax alias */
    public function tomtomRouteAjax(): void
    {
        $this->routeAjax();
    }
}
