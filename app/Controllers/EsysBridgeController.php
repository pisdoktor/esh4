<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLogHelper;
use App\Helpers\AuthHelper;
use App\Helpers\EsysBridgeHelper;
use App\Helpers\EsysComplianceHelper;
use App\Helpers\TenantContext;
use App\Helpers\ThemeViewHelper;
use App\Models\EsysSyncLog;
use App\Services\Esys\EsysBridgeService;

/**
 * ESYS / AHBS entegrasyon köprüsü — JSON dışa/içe aktarma.
 */
class EsysBridgeController
{
    public function __construct()
    {
        AuthHelper::requirePlatformOwner();
        if (!EsysComplianceHelper::enabled()) {
            $_SESSION['error'] = 'ESYS referans sütunları kurulu değil. Önce migrate_esh_esys_refs.sql çalıştırın.';
            header('Location: ' . esh_url('EsysCompliance', 'index'));
            exit;
        }
    }

    public function index(): void
    {
        if (!EsysBridgeHelper::isReady()) {
            $_SESSION['error'] = 'ESYS köprüsü kapalı. Ayarlar → Güvenlik bölümünden açabilirsiniz.';
            header('Location: ' . esh_url('EsysCompliance', 'index'));
            exit;
        }

        $kurumId = TenantContext::filterKurumId();
        $syncLogs = (new EsysSyncLog())->recent(20, $kurumId);
        $apiConfigured = EsysBridgeHelper::apiConfigured();
        $apiMode = \App\Helpers\OperationalSettings::esysBridgeApiMode();
        $pageTitle = 'ESYS / AHBS köprüsü';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'esys_bridge/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function export(): void
    {
        if (!EsysBridgeHelper::isReady()) {
            $_SESSION['error'] = 'ESYS köprüsü kapalı.';
            header('Location: ' . esh_url('EsysBridge', 'index'));
            exit;
        }

        $service = new EsysBridgeService();
        $bundle = $service->exportBundle();
        $json = json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (!is_string($json)) {
            $_SESSION['error'] = 'Dışa aktarma oluşturulamadı.';
            header('Location: ' . esh_url('EsysBridge', 'index'));
            exit;
        }

        $service->logSync('esh_to_esys', 'success', 'esh-esys-export.json', (array) ($bundle['meta'] ?? []));

        AuditLogHelper::log('esys.export', 'esys', null, null, (array) ($bundle['meta'] ?? []));

        $filename = 'esh-esys-export-' . date('Y-m-d-His') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        echo $json;
        exit;
    }

    public function importRefs(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('EsysBridge', 'index'));
            exit;
        }

        if (!EsysBridgeHelper::isReady()) {
            $_SESSION['error'] = 'ESYS köprüsü kapalı.';
            header('Location: ' . esh_url('EsysBridge', 'index'));
            exit;
        }

        $upload = $_FILES['bundle_file'] ?? null;
        if (!is_array($upload) || (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'JSON dosyası yüklenemedi.';
            header('Location: ' . esh_url('EsysBridge', 'index'));
            exit;
        }

        $tmp = (string) ($upload['tmp_name'] ?? '');
        $origName = (string) ($upload['name'] ?? 'import.json');
        if ($tmp === '' || !is_readable($tmp)) {
            $_SESSION['error'] = 'Dosya okunamadı.';
            header('Location: ' . esh_url('EsysBridge', 'index'));
            exit;
        }

        if ((int) ($upload['size'] ?? 0) > 8 * 1024 * 1024) {
            $_SESSION['error'] = 'Dosya boyutu 8 MB sınırını aşıyor.';
            header('Location: ' . esh_url('EsysBridge', 'index'));
            exit;
        }

        $raw = file_get_contents($tmp);
        $bundle = EsysBridgeHelper::parseUploadedJson(is_string($raw) ? $raw : null);
        if ($bundle === null) {
            $_SESSION['error'] = 'Geçersiz JSON dosyası.';
            header('Location: ' . esh_url('EsysBridge', 'index'));
            exit;
        }

        $service = new EsysBridgeService();
        $result = $service->importRefs($bundle);
        $stats = is_array($result['stats'] ?? null) ? $result['stats'] : [];
        $errors = is_array($result['errors'] ?? null) ? $result['errors'] : [];

        $status = 'failed';
        if (!empty($result['ok'])) {
            $updated = (int) ($stats['patients_updated'] ?? 0)
                + (int) ($stats['visits_updated'] ?? 0)
                + (int) ($stats['plans_updated'] ?? 0);
            $status = $errors !== [] ? 'partial' : ($updated > 0 ? 'success' : 'partial');
        }

        $service->logSync(
            (string) ($result['direction'] ?? 'esys_to_esh'),
            $status,
            $origName,
            $stats,
            $errors !== [] ? implode('; ', array_slice($errors, 0, 5)) : null
        );

        AuditLogHelper::log('esys.import', 'esys', null, null, [
            'stats' => $stats,
            'errors' => count($errors),
        ]);

        if (!empty($result['ok']) && $status !== 'failed') {
            $_SESSION['success'] = sprintf(
                'Referans içe aktarma tamamlandı: %d hasta, %d izlem, %d plan güncellendi.',
                (int) ($stats['patients_updated'] ?? 0),
                (int) ($stats['visits_updated'] ?? 0),
                (int) ($stats['plans_updated'] ?? 0)
            );
            if ($errors !== []) {
                $_SESSION['success'] .= ' Bazı satırlar atlandı veya hata verdi.';
            }
        } else {
            $_SESSION['error'] = $errors !== []
                ? implode(' ', array_slice($errors, 0, 3))
                : 'İçe aktarma başarısız.';
        }

        header('Location: ' . esh_url('EsysBridge', 'index'));
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
        $rows = (new EsysSyncLog())->recent(25, $kurumId);

        ob_start();
        include ROOT_PATH . '/views/admin/esys_bridge/partials/sync_log_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function retryQueue(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('EsysBridge', 'index'));
            exit;
        }
        $service = new EsysBridgeService();
        $retried = $service->retryFailedSyncs(10);
        $pushResult = $service->pushCurrentBundle();
        if (!empty($pushResult['ok'])) {
            $_SESSION['success'] = 'ESYS API kuyruğu tekrar denendi. Başarılı tekrar: ' . $retried;
        } else {
            $_SESSION['error'] = 'ESYS API push başarısız: ' . (string) ($pushResult['error'] ?? 'bilinmeyen hata');
        }
        header('Location: ' . esh_url('EsysBridge', 'index'));
        exit;
    }
}
