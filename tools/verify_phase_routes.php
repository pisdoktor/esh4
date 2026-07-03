<?php
declare(strict_types=1);

/**
 * Faz 1+2 kritik rota / sınıf doğrulama (CLI, HTTP gerektirmez).
 *
 *   php tools/verify_phase_routes.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $base = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $file = $base . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

/** @var list<array{label:string,controller:class-string,method:string}> */
$routes = [
    ['label' => 'Dashboard', 'controller' => \App\Controllers\DashboardController::class, 'method' => 'index'],
    ['label' => 'ESYS Compliance', 'controller' => \App\Controllers\EsysComplianceController::class, 'method' => 'index'],
    ['label' => 'ESYS Bridge export', 'controller' => \App\Controllers\EsysBridgeController::class, 'method' => 'export'],
    ['label' => 'REST API router', 'controller' => \App\Helpers\Api\ApiRouter::class, 'method' => 'dispatch'],
    ['label' => 'Patient Portal login', 'controller' => \App\Controllers\PatientPortalController::class, 'method' => 'login'],
    ['label' => 'Audit log export', 'controller' => \App\Controllers\AuditLogController::class, 'method' => 'exportCsv'],
    ['label' => 'Planning rota', 'controller' => \App\Controllers\PlanningController::class, 'method' => 'index'],
    ['label' => 'Federation manifest', 'controller' => \App\Controllers\FederationBridgeController::class, 'method' => 'index'],
    ['label' => 'UHDS day rows', 'controller' => \App\Controllers\UhdsController::class, 'method' => 'dayAppointmentRows'],
    ['label' => 'Portal appointment queue', 'controller' => \App\Controllers\PortalAppointmentController::class, 'method' => 'index'],
];

$ok = true;
echo "Faz 1+2 kritik rota kontrolü\n";
echo str_repeat('-', 50) . "\n";

foreach ($routes as $route) {
    $controller = $route['controller'];
    $method = $route['method'];
    $label = $route['label'];
    if (!class_exists($controller)) {
        echo "[FAIL] {$label} — sınıf yok: {$controller}\n";
        $ok = false;
        continue;
    }
    if (!method_exists($controller, $method)) {
        echo "[FAIL] {$label} — metod yok: {$controller}::{$method}\n";
        $ok = false;
        continue;
    }
    echo "[OK] {$label} — {$controller}::{$method}\n";
}

echo str_repeat('-', 50) . "\n";

// Yardımcı SQL smoke (oturumsuz)
$sqlChecks = [
    'ESYS KPI plans' => static function (): bool {
        if (!\App\Helpers\EsysComplianceHelper::columnsReady()) {
            return true;
        }
        $db = \App\Core\Database::getInstance();
        $db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__pizlemler WHERE COALESCE(durum, 0) = 0 LIMIT 1',
            []
        );

        return true;
    },
    'CDS overdue count' => static function (): bool {
        if (!\App\Helpers\ClinicalDecisionSupportHelper::enabled()) {
            return true;
        }
        (new \App\Services\Clinical\ClinicalDecisionSupportService())->countOverdueHighRisk();

        return true;
    },
    'UHDS kons randevu' => static function (): bool {
        (new \App\Models\Uhds())->getByDate(date('Y-m-d'));

        return true;
    },
];

foreach ($sqlChecks as $name => $fn) {
    try {
        $fn();
        echo "[OK] SQL: {$name}\n";
    } catch (\Throwable $e) {
        echo "[FAIL] SQL: {$name} — " . $e->getMessage() . "\n";
        $ok = false;
    }
}

exit($ok ? 0 : 1);
