<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLogHelper;
use App\Helpers\AuthHelper;
use App\Helpers\TenantContext;
use App\Helpers\ThemeViewHelper;
use App\Helpers\UsbsBridgeHelper;
use App\Helpers\UsbsComplianceHelper;
use App\Models\UsbsSyncLog;
use App\Services\Usbs\UsbsBridgeService;

/**
 * USBS / e-Nabız entegrasyon köprüsü — izlem bildirimi JSON dışa/içe aktarma.
 */
class UsbsBridgeController
{
    public function __construct()
    {
        AuthHelper::requirePlatformOwner();
        if (!UsbsComplianceHelper::enabled()) {
            $_SESSION['error'] = 'USBS referans sütunları kurulu değil. Önce migrate_esh_usbs_refs.sql çalıştırın.';
            header('Location: ' . esh_url('UsbsCompliance', 'index'));
            exit;
        }
    }

    public function index(): void
    {
        if (!UsbsBridgeHelper::isReady()) {
            $_SESSION['error'] = 'USBS köprüsü kapalı. Ayarlar → Güvenlik bölümünden açabilirsiniz.';
            header('Location: ' . esh_url('UsbsCompliance', 'index'));
            exit;
        }

        $kurumId = TenantContext::filterKurumId();
        $syncLogs = (new UsbsSyncLog())->recent(20, $kurumId);
        $apiConfigured = UsbsBridgeHelper::apiConfigured();
        $apiMode = \App\Helpers\OperationalSettings::usbsBridgeApiMode();
        $pageTitle = 'USBS / e-Nabız köprüsü';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'usbs_bridge/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function export(): void
    {
        if (!UsbsBridgeHelper::isReady()) {
            $_SESSION['error'] = 'USBS köprüsü kapalı.';
            header('Location: ' . esh_url('UsbsBridge', 'index'));
            exit;
        }

        $service = new UsbsBridgeService();
        $bundle = $service->exportBundle();
        $json = json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (!is_string($json)) {
            $_SESSION['error'] = 'Dışa aktarma oluşturulamadı.';
            header('Location: ' . esh_url('UsbsBridge', 'index'));
            exit;
        }

        $service->logSync('esh_to_usbs', 'success', 'esh-usbs-export.json', (array) ($bundle['meta'] ?? []));
        AuditLogHelper::log('usbs.export', 'usbs', null, null, (array) ($bundle['meta'] ?? []));

        $filename = 'esh-usbs-export-' . date('Y-m-d-His') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        echo $json;
        exit;
    }

    public function importRefs(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('UsbsBridge', 'index'));
            exit;
        }

        if (!UsbsBridgeHelper::isReady()) {
            $_SESSION['error'] = 'USBS köprüsü kapalı.';
            header('Location: ' . esh_url('UsbsBridge', 'index'));
            exit;
        }

        $upload = $_FILES['bundle_file'] ?? null;
        if (!is_array($upload) || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'JSON dosyası yüklenemedi.';
            header('Location: ' . esh_url('UsbsBridge', 'index'));
            exit;
        }

        $tmp = (string) ($upload['tmp_name'] ?? '');
        $origName = (string) ($upload['name'] ?? 'import.json');
        if ($tmp === '' || !is_readable($tmp)) {
            $_SESSION['error'] = 'Dosya okunamadı.';
            header('Location: ' . esh_url('UsbsBridge', 'index'));
            exit;
        }

        if ((int) ($upload['size'] ?? 0) > 8 * 1024 * 1024) {
            $_SESSION['error'] = 'Dosya boyutu 8 MB sınırını aşıyor.';
            header('Location: ' . esh_url('UsbsBridge', 'index'));
            exit;
        }

        $raw = file_get_contents($tmp);
        $bundle = UsbsBridgeHelper::parseUploadedJson(is_string($raw) ? $raw : null);
        if ($bundle === null) {
            $_SESSION['error'] = 'Geçersiz JSON dosyası.';
            header('Location: ' . esh_url('UsbsBridge', 'index'));
            exit;
        }

        $service = new UsbsBridgeService();
        $result = $service->importRefs($bundle);
        $stats = is_array($result['stats'] ?? null) ? $result['stats'] : [];
        $errors = is_array($result['errors'] ?? null) ? $result['errors'] : [];

        $status = 'failed';
        if (!empty($result['ok'])) {
            $updated = (int) ($stats['patients_updated'] ?? 0) + (int) ($stats['visits_updated'] ?? 0);
            $status = $errors !== [] ? 'partial' : ($updated > 0 ? 'success' : 'partial');
        }

        $service->logSync(
            'usbs_to_esh',
            $status,
            $origName,
            $stats,
            $errors !== [] ? implode('; ', array_slice($errors, 0, 5)) : null
        );

        AuditLogHelper::log('usbs.import', 'usbs', null, null, [
            'stats' => $stats,
            'errors' => count($errors),
        ]);

        if (!empty($result['ok']) && $status !== 'failed') {
            $_SESSION['success'] = sprintf(
                'USBS içe aktarma tamamlandı: %d hasta, %d izlem güncellendi.',
                (int) ($stats['patients_updated'] ?? 0),
                (int) ($stats['visits_updated'] ?? 0)
            );
            if ($errors !== []) {
                $_SESSION['success'] .= ' Bazı satırlar atlandı veya hata verdi.';
            }
        } else {
            $_SESSION['error'] = $errors !== []
                ? implode(' ', array_slice($errors, 0, 3))
                : 'İçe aktarma başarısız.';
        }

        header('Location: ' . esh_url('UsbsBridge', 'index'));
        exit;
    }

    public function syncLogRows(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!AuthHelper::sessionIsAdmin()) {
            http_response_code(403);
            echo json_encode(['ok' => false], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $kurumId = TenantContext::filterKurumId();
        $rows = (new UsbsSyncLog())->recent(25, $kurumId);

        ob_start();
        include ROOT_PATH . '/views/admin/usbs_bridge/partials/sync_log_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function retryQueue(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('UsbsBridge', 'index'));
            exit;
        }
        $service = new UsbsBridgeService();
        $retried = $service->retryFailedNotifications(250);
        $pushResult = $service->pushCurrentBundle();
        if (!empty($pushResult['ok'])) {
            $_SESSION['success'] = 'USBS bildirim kuyruğu tekrar denendi. Yeniden kuyruğa alınan kayıt: ' . $retried;
        } else {
            $_SESSION['error'] = 'USBS API push başarısız: ' . (string) ($pushResult['error'] ?? 'bilinmeyen hata');
        }
        header('Location: ' . esh_url('UsbsBridge', 'index'));
        exit;
    }
}
