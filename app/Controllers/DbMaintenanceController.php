<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\ThemeViewHelper;
use App\Services\DbMaintenanceService;

/**
 * Yönetici: veritabanı yedek / geri yükleme / OPTIMIZE-CHECK-ANALYZE.
 */
class DbMaintenanceController {

    private const VALID_TABS = ['ozet', 'tablolar', 'bakim', 'yedekler', 'geri'];

    public function __construct() {
        AuthHelper::requirePlatformOwner();
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

    private function resolveTab(?string $tab): string {
        $tab = strtolower(trim((string) $tab));
        if ($tab === '' && isset($_GET['tab'])) {
            $tab = strtolower(trim((string) $_GET['tab']));
        }
        return in_array($tab, self::VALID_TABS, true) ? $tab : 'ozet';
    }

    private function redirectIndex(string $tab = 'ozet'): void {
        $tab = in_array($tab, self::VALID_TABS, true) ? $tab : 'ozet';
        $url = esh_url('DbMaintenance', 'index');
        if ($tab !== 'ozet') {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . 'tab=' . rawurlencode($tab);
        }
        header('Location: ' . $url);
        exit;
    }

    private function backupOptionsFromPost(): array {
        return [
            'use_mysqldump' => isset($_POST['use_mysqldump']) && (string) $_POST['use_mysqldump'] === '1',
            'compress' => isset($_POST['compress']) && (string) $_POST['compress'] === '1',
            'exclude_cache' => isset($_POST['exclude_cache']) && (string) $_POST['exclude_cache'] === '1',
        ];
    }

    public function index() {
        $svc = new DbMaintenanceService();
        $tools = $svc->getMysqlToolPaths();
        $backups = $svc->listBackups();
        $tables = $svc->getEnrichedTableOverview();
        $summary = $svc->getDatabaseSummary();
        $tableGroupSummary = $svc->getTableGroupSummary($tables);
        $groupFilterLabels = $svc->orderedGroupLabels(array_column($tableGroupSummary, 'label'));
        $backupGroups = $svc->getBackupGroups();
        $token = $this->ensureToken();
        $maintResults = $_SESSION['db_maint_results'] ?? null;
        unset($_SESSION['db_maint_results']);
        $activeTab = $this->resolveTab($_GET['tab'] ?? null);
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
            $this->redirectIndex('yedekler');
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
        $opts = $this->backupOptionsFromPost();
        $svc = new DbMaintenanceService();
        $r = $svc->createSqlBackup($opts['use_mysqldump'], $opts['compress'], $opts['exclude_cache']);
        if (!empty($r['ok'])) {
            $_SESSION['success'] = $r['message'] . (isset($r['file']) ? ' — ' . $r['file'] : '');
        } else {
            $_SESSION['error'] = $r['message'] ?? 'Yedek oluşturulamadı.';
        }
        $this->redirectIndex('yedekler');
    }

    public function createTableBackup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyToken()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            $this->redirectIndex();
        }
        $table = (string) ($_POST['table'] ?? '');
        if ($table === '') {
            $_SESSION['error'] = 'Yedeklenecek tablo seçilmelidir.';
            $this->redirectIndex('yedekler');
        }
        $opts = $this->backupOptionsFromPost();
        $svc = new DbMaintenanceService();
        $r = $svc->createTableSqlBackup($table, $opts['use_mysqldump'], $opts['compress']);
        if (!empty($r['ok'])) {
            $_SESSION['success'] = $r['message'] . (isset($r['file']) ? ' — ' . $r['file'] : '');
        } else {
            $_SESSION['error'] = $r['message'] ?? 'Tablo yedeği oluşturulamadı.';
        }
        $this->redirectIndex('yedekler');
    }

    public function createGroupBackup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyToken()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            $this->redirectIndex();
        }
        $group = (string) ($_POST['group'] ?? '');
        if ($group === '') {
            $_SESSION['error'] = 'Yedeklenecek grup seçilmelidir.';
            $this->redirectIndex('yedekler');
        }
        $opts = $this->backupOptionsFromPost();
        $svc = new DbMaintenanceService();
        $r = $svc->createGroupSqlBackup($group, $opts['use_mysqldump'], $opts['compress']);
        if (!empty($r['ok'])) {
            $_SESSION['success'] = $r['message'] . (isset($r['file']) ? ' — ' . $r['file'] : '');
        } else {
            $_SESSION['error'] = $r['message'] ?? 'Grup yedeği oluşturulamadı.';
        }
        $this->redirectIndex('yedekler');
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
        $this->redirectIndex('yedekler');
    }

    public function restoreBackup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyToken()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            $this->redirectIndex();
        }
        if (($_POST['restore_confirm'] ?? '') !== 'YEDEKTEN_YUKLE') {
            $_SESSION['error'] = 'Geri yükleme için onay kutusuna tam olarak YEDEKTEN_YUKLE yazmalısınız.';
            $this->redirectIndex('geri');
        }
        $name = (string) ($_POST['file'] ?? '');
        $svc = new DbMaintenanceService();
        $r = $svc->restoreFromBackup($name);
        $_SESSION[($r['ok'] ?? false) ? 'success' : 'error'] = $r['message'] ?? '';
        $this->redirectIndex('geri');
    }

    public function restoreTableBackup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyToken()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            $this->redirectIndex();
        }
        if (($_POST['restore_confirm'] ?? '') !== 'YEDEKTEN_YUKLE') {
            $_SESSION['error'] = 'Geri yükleme için onay kutusuna tam olarak YEDEKTEN_YUKLE yazmalısınız.';
            $this->redirectIndex('geri');
        }
        $table = (string) ($_POST['table'] ?? '');
        if ($table === '') {
            $_SESSION['error'] = 'Geri yüklenecek tablo seçilmelidir.';
            $this->redirectIndex('geri');
        }
        $name = (string) ($_POST['file'] ?? '');
        $svc = new DbMaintenanceService();
        $r = $svc->restoreTableFromBackup($name, $table);
        $_SESSION[($r['ok'] ?? false) ? 'success' : 'error'] = $r['message'] ?? '';
        $this->redirectIndex('geri');
    }

    public function optimize() {
        $this->runMaintenanceOp('OPTIMIZE', null);
    }

    public function analyze() {
        $this->runMaintenanceOp('ANALYZE', null);
    }

    public function check() {
        $this->runMaintenanceOp('CHECK', null);
    }

    public function optimizeTable() {
        $this->runMaintenanceOp('OPTIMIZE', $this->tablesFromPost());
    }

    public function analyzeTable() {
        $this->runMaintenanceOp('ANALYZE', $this->tablesFromPost());
    }

    public function checkTable() {
        $this->runMaintenanceOp('CHECK', $this->tablesFromPost());
    }

    /**
     * @return list<string>
     */
    private function tablesFromPost(): array {
        $raw = $_POST['tables'] ?? $_POST['table'] ?? [];
        if (is_string($raw)) {
            $raw = $raw !== '' ? [$raw] : [];
        }
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $t) {
            $t = trim((string) $t);
            if ($t !== '') {
                $out[] = $t;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * @param list<string>|null $tables
     */
    private function runMaintenanceOp(string $op, ?array $tables): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyToken()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            $this->redirectIndex();
        }
        if ($tables !== null && $tables === []) {
            $_SESSION['error'] = 'En az bir tablo seçilmelidir.';
            $this->redirectIndex('bakim');
        }
        @set_time_limit(0);
        $svc = new DbMaintenanceService();
        $_SESSION['db_maint_results'] = $svc->runTableMaintenance($op, $tables);
        $scope = $tables === null ? 'tüm tablolar' : count($tables) . ' tablo';
        $_SESSION['success'] = $op . ' TABLE işlemi tamamlandı (' . $scope . '). Sonuçları kontrol edin.';
        $this->redirectIndex('bakim');
    }
}
