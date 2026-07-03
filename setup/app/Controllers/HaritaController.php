<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\MapRoutingAjaxHelper;
use App\Helpers\StatsQueryCache;
use App\Helpers\ThemeViewHelper;
use App\Models\Address;
use App\Models\Patient;

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
        $mapPatientsJson = json_encode($mapPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
        if ($mapPatientsJson === false) {
            $mapPatientsJson = '[]';
        }
        $centerLon = defined('START_LNG') ? (float) START_LNG : 29.079663;
        $centerLat = defined('START_LAT') ? (float) START_LAT : 37.783291;
        $mapConfigUrl = esh_url('Harita', 'mapConfigAjax');
        $mapKeyUrl = esh_url('Harita', 'tomtomMapKeyAjax');
        $routeUrl = esh_url('Harita', 'routeAjax');
        $pageTitle = 'Hasta haritası';
        $mahalleCombinedUrl = '';
        $mahalleGeoNameAllowlist = (new Address())->getMahalleAdlariForHaritaGeoPamukkaleMerkezefendi();
        $mahalleGeoPath = ROOT_PATH . '/public/geo/denizli_pamukkale_merkezefendi_mahalleler.geojson';
        if (is_file($mahalleGeoPath)) {
            $mahalleCombinedUrl = rtrim((string) SITEURL, '/') . '/public/geo/denizli_pamukkale_merkezefendi_mahalleler.geojson';
        }

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'harita/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * @return array<int, object>
     */
    private function loadMapPatientRows(): array {
        $cached = StatsQueryCache::get('harita_map_rows', 600);
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
            StatsQueryCache::set('harita_map_rows', $payload, 600);
        }

        return $rows;
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

            $patientId = (int) ($row->id ?? 0);
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
            $popupHtml .= "<button type='button' class='btn btn-success btn-sm' onclick='window.ESH_HARITA_ROTA && window.ESH_HARITA_ROTA([" . $lng . ',' . $lat . "])'><i class='fa-solid fa-route me-1'></i>Rota</button>";
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
