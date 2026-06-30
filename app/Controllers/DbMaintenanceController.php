<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\ThemeViewHelper;
use App\Services\DbMaintenanceService;

/**
 * Yönetici: veritabanı yedek / geri yükleme / OPTIMIZE-CHECK-ANALYZE.
 */
class DbMaintenanceController {

    public function __construct() {
        AuthHelper::requireSuperAdmin();
    }

    private function ensureToken(): string {
        if (empty($_SESSION['db_maint_token'])) {
            $_SESSION['db_maint_token'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['db_maint_token'];
    }

    private function verifyToken(): bool {
        $t = $_POST['maint_token'] ?? '';
        return is_string($t) && $t !== '' && isset($_SESSION['db_maint_token']) && hash_equals($_SESSION['db_maint_token'], $t);
    }

    private function redirectIndex(string $hash = ''): void {
        header('Location: ' . esh_url('DbMaintenance', 'index') . $hash);
        exit;
    }

    public function index() {
        $svc = new DbMaintenanceService();
        $tools = $svc->getMysqlToolPaths();
        $backups = $svc->listBackups();
        $tables = $svc->getTableOverview();
        $token = $this->ensureToken();
        $maintResults = $_SESSION['db_maint_results'] ?? null;
        unset($_SESSION['db_maint_results']);
        $pageTitle = 'Veritabanı bakımı ve yedek';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'db_maintenance/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function download() {
        $name = $_GET['file'] ?? '';
        $svc = new DbMaintenanceService();
        $path = $svc->resolveBackupFile((string) $name);
        if (!$path) {
            $_SESSION['error'] = 'Dosya bulunamadı veya geçersiz ad.';
            $this->redirectIndex('#yedekler');
        }
        $fn = basename($path);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', $fn) . '"');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }

    public function createBackup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyToken()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            $this->redirectIndex();
        }
        $svc = new DbMaintenanceService();
        $useDump = isset($_POST['use_mysqldump']) && (string) $_POST['use_mysqldump'] === '1';
        $r = $svc->createSqlBackup($useDump);
        if (!empty($r['ok'])) {
            $_SESSION['success'] = $r['message'] . (isset($r['file']) ? ' — ' . $r['file'] : '');
        } else {
            $_SESSION['error'] = $r['message'] ?? 'Yedek oluşturulamadı.';
        }
        $this->redirectIndex('#yedekler');
    }

    public function createTableBackup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyToken()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            $this->redirectIndex();
        }
        $table = (string) ($_POST['table'] ?? '');
        if ($table === '') {
            $_SESSION['error'] = 'Yedeklenecek tablo seçilmelidir.';
            $this->redirectIndex('#yedekler');
        }
        $svc = new DbMaintenanceService();
        $useDump = isset($_POST['use_mysqldump']) && (string) $_POST['use_mysqldump'] === '1';
        $r = $svc->createTableSqlBackup($table, $useDump);
        if (!empty($r['ok'])) {
            $_SESSION['success'] = $r['message'] . (isset($r['file']) ? ' — ' . $r['file'] : '');
        } else {
            $_SESSION['error'] = $r['message'] ?? 'Tablo yedeği oluşturulamadı.';
        }
        $this->redirectIndex('#yedekler');
    }

    public function deleteBackup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyToken()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            $this->redirectIndex();
        }
        $name = (string) ($_POST['file'] ?? '');
        $svc = new DbMaintenanceService();
        $r = $svc->deleteBackup($name);
        $_SESSION[($r['ok'] ?? false) ? 'success' : 'error'] = $r['message'] ?? '';
        $this->redirectIndex('#yedekler');
    }

    public function restoreBackup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyToken()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            $this->redirectIndex();
        }
        if (($_POST['restore_confirm'] ?? '') !== 'YEDEKTEN_YUKLE') {
            $_SESSION['error'] = 'Geri yükleme için onay kutusuna tam olarak YEDEKTEN_YUKLE yazmalısınız.';
            $this->redirectIndex('#geri');
        }
        $name = (string) ($_POST['file'] ?? '');
        $svc = new DbMaintenanceService();
        $r = $svc->restoreFromBackup($name);
        $_SESSION[($r['ok'] ?? false) ? 'success' : 'error'] = $r['message'] ?? '';
        $this->redirectIndex('#geri');
    }

    public function restoreTableBackup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyToken()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            $this->redirectIndex();
        }
        if (($_POST['restore_confirm'] ?? '') !== 'YEDEKTEN_YUKLE') {
            $_SESSION['error'] = 'Geri yükleme için onay kutusuna tam olarak YEDEKTEN_YUKLE yazmalısınız.';
            $this->redirectIndex('#geri');
        }
        $table = (string) ($_POST['table'] ?? '');
        if ($table === '') {
            $_SESSION['error'] = 'Geri yüklenecek tablo seçilmelidir.';
            $this->redirectIndex('#geri');
        }
        $name = (string) ($_POST['file'] ?? '');
        $svc = new DbMaintenanceService();
        $r = $svc->restoreTableFromBackup($name, $table);
        $_SESSION[($r['ok'] ?? false) ? 'success' : 'error'] = $r['message'] ?? '';
        $this->redirectIndex('#geri');
    }

    public function optimize() {
        $this->runMaintenanceOp('OPTIMIZE');
    }

    public function analyze() {
        $this->runMaintenanceOp('ANALYZE');
    }

    public function check() {
        $this->runMaintenanceOp('CHECK');
    }

    private function runMaintenanceOp(string $op): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyToken()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            $this->redirectIndex();
        }
        @set_time_limit(0);
        $svc = new DbMaintenanceService();
        $_SESSION['db_maint_results'] = $svc->runTableMaintenance($op);
        $_SESSION['success'] = $op . ' TABLE işlemi tamamlandı. Aşağıdaki sonuçları kontrol edin.';
        $this->redirectIndex('#bakim');
    }
}
