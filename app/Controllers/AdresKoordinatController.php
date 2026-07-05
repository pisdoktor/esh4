<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\GeocodeQuotaHelper;
use App\Helpers\MapRoutingGeocodeHelper;
use App\Helpers\OperationalSettings;
use App\Helpers\ThemeViewHelper;
use App\Models\Address;
use App\Services\MapRouting\MapRoutingProviderFactory;

/**
 * Yönetim: coords boş kapı kayıtları için geocode (günlük kota ile).
 */
class AdresKoordinatController {

    public function __construct() {
        AuthHelper::requireSuperAdmin();
    }

    public function index(): void {
        $address = new Address();
        $quota = GeocodeQuotaHelper::getSummary();
        $kapinoStats = $address->getKapinoCoordStats();
        $missingCount = $kapinoStats->missing;
        $totalKapino = $kapinoStats->total;
        $hasCoordsCount = $kapinoStats->with_coords;
        $activeMapProvider = OperationalSettings::activeMapProviderStatusForAdmin();
        $mapProviderConfigured = MapRoutingGeocodeHelper::isActiveProviderConfigured();
        $keyStatus = MapRoutingProviderFactory::keyStatusForProvider($activeMapProvider['code']);
        $pageTitle = 'Adres koordinat bulma';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'adres-koordinat/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Tek kapı: geocode (AJAX). Günlük en fazla 2500 API çağrısı.
     */
    public function processNext(): void {
        header('Content-Type: application/json; charset=utf-8');

        if (!MapRoutingGeocodeHelper::isActiveProviderConfigured()) {
            echo json_encode([
                'ok' => false,
                'done' => true,
                'stop_reason' => 'no_key',
                'mesaj' => 'Aktif harita sağlayıcısı API anahtarı tanımlı değil.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!GeocodeQuotaHelper::canMakeRequest()) {
            $quota = GeocodeQuotaHelper::getSummary();
            $address = new Address();
            echo json_encode([
                'ok' => false,
                'done' => true,
                'stop_reason' => 'quota',
                'quota' => $quota,
                'missing' => $address->countKapinoMissingCoords(),
                'mesaj' => 'Bugünkü geocode kotası (' . GeocodeQuotaHelper::DAILY_LIMIT . ') doldu.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $afterId = isset($_GET['after_id']) ? trim((string) $_GET['after_id']) : '';
        $address = new Address();
        $kapinoId = $address->fetchNextKapinoMissingCoordsId($afterId);

        if ($kapinoId === null || $kapinoId === '') {
            $quota = GeocodeQuotaHelper::getSummary();
            $missingLeft = $address->countKapinoMissingCoords();
            $allDone = $missingLeft === 0;
            echo json_encode([
                'ok' => true,
                'done' => true,
                'stop_reason' => $allDone ? 'complete' : 'round_complete',
                'processed' => 0,
                'quota' => $quota,
                'missing' => $missingLeft,
                'mesaj' => $allDone
                    ? 'Koordinatsız kapı kaydı kalmadı.'
                    : 'Bu tur bitti; koordinatsız ' . number_format($missingLeft, 0, ',', '.') . ' kapı kaldı. Kotanız varsa yeniden başlatabilir veya yarın devam edebilirsiniz.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $summary = $address->getKapinoAddressSummary($kapinoId);
        $query = $address->buildGeocodeQueryForKapinoId($kapinoId);

        $result = ['ok' => false, 'mesaj' => 'Koordinat işlenemedi.'];

        if ($query === null) {
            $result = ['ok' => false, 'mesaj' => 'Adres metni oluşturulamadı (eksik hiyerarşi).'];
        } else {
            if (!GeocodeQuotaHelper::canMakeRequest()) {
                $quota = GeocodeQuotaHelper::getSummary();
                echo json_encode([
                    'ok' => false,
                    'done' => true,
                    'stop_reason' => 'quota',
                    'quota' => $quota,
                    'missing' => $address->countKapinoMissingCoords(),
                    'mesaj' => 'Bugünkü geocode kotası (' . GeocodeQuotaHelper::DAILY_LIMIT . ') doldu.',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $result = $address->geocodeKapinoById($kapinoId, false);
        }

        $quota = GeocodeQuotaHelper::getSummary();
        $missing = $address->countKapinoMissingCoords();
        $quotaExhausted = !GeocodeQuotaHelper::canMakeRequest();
        $done = $quotaExhausted;

        $stopReason = null;
        if ($done) {
            $stopReason = 'quota';
        }

        echo json_encode([
            'ok' => true,
            'done' => $done,
            'stop_reason' => $stopReason,
            'processed' => 1,
            'next_after_id' => $kapinoId,
            'quota' => $quota,
            'missing' => $missing,
            'item' => [
                'id' => $kapinoId,
                'ilce' => (string) ($summary->ilce ?? ''),
                'mahalle' => (string) ($summary->mahalle ?? ''),
                'sokak' => (string) ($summary->sokak ?? ''),
                'kapino' => (string) ($summary->kapino ?? ''),
                'adres' => $query ?? '',
                'geocode_ok' => !empty($result['ok']),
                'coords' => (string) ($result['coords'] ?? ''),
                'mesaj' => (string) ($result['mesaj'] ?? ''),
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
