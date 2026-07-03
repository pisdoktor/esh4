<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLogHelper;
use App\Helpers\AuthHelper;
use App\Helpers\FederationBridgeHelper;
use App\Helpers\FederationHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\FederationSyncLog;
use App\Services\Federation\FederationBridgeService;

/**
 * Federasyon dosya köprüsü — düğüm anlık görüntüsü ve hub eşlemesi.
 */
class FederationBridgeController
{
    public function __construct()
    {
        AuthHelper::requirePlatformOwner();
        if (!FederationHelper::columnsReady()) {
            $_SESSION['error'] = 'Federasyon tabloları kurulu değil.';
            header('Location: ' . esh_url('Federation', 'index'));
            exit;
        }
    }

    public function index(): void
    {
        if (!FederationBridgeHelper::isReady()) {
            $_SESSION['error'] = 'Federasyon köprüsü kapalı. Ayarlar → Güvenlik bölümünden açabilirsiniz.';
            header('Location: ' . esh_url('Federation', 'index'));
            exit;
        }

        $syncLogs = (new FederationSyncLog())->recent(20);
        $apiConfigured = FederationBridgeHelper::apiConfigured();
        $manifestState = FederationHelper::lastManifestState();
        $regionComplianceRows = FederationHelper::regionComplianceRows();
        $pageTitle = 'Federasyon köprüsü';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'federation_bridge/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function export(): void
    {
        if (!FederationBridgeHelper::isReady()) {
            $_SESSION['error'] = 'Federasyon köprüsü kapalı.';
            header('Location: ' . esh_url('FederationBridge', 'index'));
            exit;
        }

        $service = new FederationBridgeService();
        $bundle = $service->exportBundle();
        $json = json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (!is_string($json)) {
            $_SESSION['error'] = 'Dışa aktarma oluşturulamadı.';
            header('Location: ' . esh_url('FederationBridge', 'index'));
            exit;
        }

        $service->logSync('node_snapshot', 'success', 'esh-federation-export.json', (array) ($bundle['meta'] ?? []));
        AuditLogHelper::log('federation.export', 'federation', null, null, (array) ($bundle['meta'] ?? []));

        $filename = 'esh-federation-export-' . date('Y-m-d-His') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        echo $json;
        exit;
    }

    public function importBundle(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('FederationBridge', 'index'));
            exit;
        }

        if (!FederationBridgeHelper::isReady()) {
            $_SESSION['error'] = 'Federasyon köprüsü kapalı.';
            header('Location: ' . esh_url('FederationBridge', 'index'));
            exit;
        }

        $upload = $_FILES['bundle_file'] ?? null;
        if (!is_array($upload) || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'JSON dosyası yüklenemedi.';
            header('Location: ' . esh_url('FederationBridge', 'index'));
            exit;
        }

        $tmp = (string) ($upload['tmp_name'] ?? '');
        $origName = (string) ($upload['name'] ?? 'import.json');
        if ($tmp === '' || !is_readable($tmp)) {
            $_SESSION['error'] = 'Dosya okunamadı.';
            header('Location: ' . esh_url('FederationBridge', 'index'));
            exit;
        }

        if ((int) ($upload['size'] ?? 0) > 8 * 1024 * 1024) {
            $_SESSION['error'] = 'Dosya boyutu 8 MB sınırını aşıyor.';
            header('Location: ' . esh_url('FederationBridge', 'index'));
            exit;
        }

        $raw = file_get_contents($tmp);
        $bundle = FederationBridgeHelper::parseUploadedJson(is_string($raw) ? $raw : null);
        if ($bundle === null) {
            $_SESSION['error'] = 'Geçersiz JSON dosyası.';
            header('Location: ' . esh_url('FederationBridge', 'index'));
            exit;
        }

        $service = new FederationBridgeService();
        $result = $service->importBundle($bundle);
        $stats = is_array($result['stats'] ?? null) ? $result['stats'] : [];
        $errors = is_array($result['errors'] ?? null) ? $result['errors'] : [];

        $status = 'failed';
        if (!empty($result['ok'])) {
            $updated = (int) ($stats['regions_upserted'] ?? 0) + (int) ($stats['kurumlar_updated'] ?? 0);
            $status = $errors !== [] ? 'partial' : ($updated > 0 ? 'success' : 'partial');
        }

        $service->logSync(
            'hub_to_node',
            $status,
            $origName,
            $stats,
            $errors !== [] ? implode('; ', array_slice($errors, 0, 5)) : null
        );

        AuditLogHelper::log('federation.import', 'federation', null, null, [
            'stats' => $stats,
            'errors' => count($errors),
        ]);

        if (!empty($result['ok']) && $status !== 'failed') {
            $_SESSION['success'] = sprintf(
                'Federasyon içe aktarma tamamlandı: %d bölge, %d kurum güncellendi.',
                (int) ($stats['regions_upserted'] ?? 0),
                (int) ($stats['kurumlar_updated'] ?? 0)
            );
            if ($errors !== []) {
                $_SESSION['success'] .= ' Bazı satırlar atlandı.';
            }
        } else {
            $_SESSION['error'] = $errors !== []
                ? implode(' ', array_slice($errors, 0, 3))
                : 'İçe aktarma başarısız.';
        }

        header('Location: ' . esh_url('FederationBridge', 'index'));
        exit;
    }

    public function syncLogRows(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!AuthHelper::sessionIsSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['ok' => false], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rows = (new FederationSyncLog())->recent(25);

        ob_start();
        include ROOT_PATH . '/views/admin/federation_bridge/partials/sync_log_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function manualSyncManifest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('FederationBridge', 'index'));
            exit;
        }
        $result = FederationHelper::fetchCentralManifest();
        if (!empty($result['ok'])) {
            $_SESSION['success'] = 'Manifest kontrolü tamamlandı: ' . (string) ($result['checked_at'] ?? '');
        } else {
            $_SESSION['error'] = (string) ($result['error'] ?? 'Manifest kontrolü başarısız.');
        }
        header('Location: ' . esh_url('FederationBridge', 'index'));
        exit;
    }
}
