<?php
declare(strict_types=1);

/**
 * Modül CRUD action → controller method doğrulama (CLI).
 *
 * Registry rotaları zorunlu (FAIL); permission-crud-map drift uyarısı (DRIFT).
 *
 *   php tools/verify_module_crud.php
 *   php tools/verify_module_crud.php --check-registry
 *   php tools/verify_module_crud.php --json
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/config/config.php';

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

$jsonOut = in_array('--json', $argv ?? [], true);
$checkRegistry = in_array('--check-registry', $argv ?? [], true);

/** @var array<string, array<string, mixed>> $crudMap */
$crudMap = require $root . '/config/permission-crud-map.php';
/** @var array<string, array<string, mixed>> $registry */
$registry = require $root . '/config/app-modules.registry.php';

function actionResolvable(string $class, string $action): bool
{
    if (method_exists($class, $action)) {
        return true;
    }
    if (method_exists($class, '__call') && str_starts_with($action, 'xTab_')) {
        $id = substr($action, 5);

        return class_exists(\App\Helpers\StatsCrossTabRegistry::class)
            && \App\Helpers\StatsCrossTabRegistry::has($id);
    }

    return false;
}

/** @var list<array{module:string,controller:string,action:string}> $registryFails */
$registryFails = [];
/** @var list<array{module:string,controller:string,crud:string,action:string}> $crudDrifts */
$crudDrifts = [];
$registryActionTotal = 0;
$crudActionTotal = 0;
$crudOk = 0;

foreach ($registry as $moduleKey => $mod) {
    if (!is_array($mod['routes'] ?? null)) {
        continue;
    }
    foreach ($mod['routes'] as $controllerName => $actions) {
        if (!is_array($actions)) {
            continue;
        }
        $class = 'App\\Controllers\\' . $controllerName . 'Controller';
        if (!class_exists($class)) {
            foreach ($actions as $action) {
                $registryFails[] = [
                    'module' => (string) $moduleKey,
                    'controller' => (string) $controllerName,
                    'action' => (string) $action,
                ];
            }
            continue;
        }
        foreach ($actions as $action) {
            $action = (string) $action;
            if ($action === '') {
                continue;
            }
            $registryActionTotal++;
            if (!actionResolvable($class, $action)) {
                $registryFails[] = [
                    'module' => (string) $moduleKey,
                    'controller' => (string) $controllerName,
                    'action' => $action,
                ];
            }
        }
    }
}

foreach ($crudMap as $moduleKey => $def) {
    $routes = $registry[$moduleKey]['routes'] ?? [];
    if (!is_array($routes)) {
        $routes = [];
    }
    /** @var list<string> $allRegistryActions */
    $allRegistryActions = [];
    /** @var list<string> $controllerClasses */
    $controllerClasses = [];
    foreach ($routes as $controllerName => $actions) {
        if (!is_array($actions)) {
            continue;
        }
        $class = 'App\\Controllers\\' . $controllerName . 'Controller';
        $controllerClasses[] = $class;
        foreach ($actions as $actionName) {
            $allRegistryActions[] = (string) $actionName;
        }
    }
    $crudSections = is_array($def['crud'] ?? null) ? $def['crud'] : [];
    foreach ($crudSections as $crudType => $actions) {
        if (!is_array($actions)) {
            continue;
        }
        foreach ($actions as $action) {
            $action = (string) $action;
            if ($action === '') {
                continue;
            }
            $crudActionTotal++;
            $resolved = false;
            foreach ($controllerClasses as $class) {
                if (class_exists($class) && actionResolvable($class, $action)) {
                    $resolved = true;
                    break;
                }
            }
            if ($resolved || in_array($action, $allRegistryActions, true)) {
                $crudOk++;
                continue;
            }
            $primaryCtrl = $routes !== [] ? (string) array_key_first($routes) : '?';
            $crudDrifts[] = [
                'module' => $moduleKey,
                'controller' => $primaryCtrl,
                'crud' => $crudType,
                'action' => $action,
            ];
        }
    }
}

$registryCheckOk = true;
$registryCheckMsg = '';
if ($checkRegistry) {
    $cmd = escapeshellarg(PHP_BINARY ?: 'php') . ' ' . escapeshellarg($root . '/tools/build_app_modules_registry.php') . ' --check';
    passthru($cmd, $registryExit);
    $registryCheckOk = ($registryExit === 0);
    $registryCheckMsg = $registryCheckOk ? 'Registry güncel' : 'Registry drift — build_app_modules_registry.php çalıştırın';
}

$failCount = count($registryFails);
$ok = ($failCount === 0) && (!$checkRegistry || $registryCheckOk);

if ($jsonOut) {
    echo json_encode([
        'ok' => $ok,
        'registry_actions' => $registryActionTotal,
        'registry_failures' => $failCount,
        'crud_actions' => $crudActionTotal,
        'crud_ok' => $crudOk,
        'crud_drift' => count($crudDrifts),
        'registry_fails' => $registryFails,
        'crud_drifts' => $crudDrifts,
        'registry_check' => $checkRegistry ? $registryCheckOk : null,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit($ok ? 0 : 1);
}

echo "Modül CRUD wiring doğrulama\n";
echo str_repeat('-', 60) . "\n";
echo sprintf(
    "Registry action: %d | FAIL: %d | CRUD map: %d OK / %d DRIFT\n",
    $registryActionTotal,
    $failCount,
    $crudOk,
    count($crudDrifts)
);

if ($checkRegistry) {
    echo ($registryCheckOk ? '[OK] ' : '[FAIL] ') . $registryCheckMsg . "\n";
}

if ($registryFails !== []) {
    echo str_repeat('-', 60) . "\n";
    echo "Registry rotaları (FAIL — controller metodu yok):\n";
    foreach ($registryFails as $row) {
        echo sprintf(
            "  [FAIL] %s / %sController::%s\n",
            $row['module'],
            $row['controller'],
            $row['action']
        );
    }
}

if ($crudDrifts !== []) {
    echo str_repeat('-', 60) . "\n";
    echo "permission-crud-map drift (uyarı — registry/metod dışı, " . count($crudDrifts) . " adet):\n";
    foreach (array_slice($crudDrifts, 0, 15) as $row) {
        echo sprintf(
            "  [DRIFT] %s / %s::%s (%s)\n",
            $row['module'],
            $row['controller'],
            $row['action'],
            $row['crud']
        );
    }
    if (count($crudDrifts) > 15) {
        echo '  ... +' . (count($crudDrifts) - 15) . " daha\n";
    }
}

echo str_repeat('-', 60) . "\n";
echo $ok ? "Sonuç: OK\n" : "Sonuç: FAIL\n";
exit($ok ? 0 : 1);
