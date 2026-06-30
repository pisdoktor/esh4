<?php
/**
 * Registry ve permission-crud-map arasındaki eksik action'ları raporlar.
 * Kullanım: php tools/sync_permissions_catalog.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/config/config.php';

spl_autoload_register(function ($class) use ($root) {
    $prefix = 'App\\';
    $base_dir = $root . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

\App\Helpers\AppSettings::boot();
$registry = \App\Helpers\AppSettings::registry();
$crudMap = require $root . '/config/permission-crud-map.php';

$missing = [];
foreach ($registry as $moduleKey => $entry) {
    if (!is_array($entry)) {
        continue;
    }
    $group = (string) ($entry['group'] ?? '');
    if (!in_array($group, ['core', 'site'], true)) {
        continue;
    }
    if (!isset($crudMap[$moduleKey])) {
        $missing[$moduleKey] = ['__module__' => ['not in crud map']];
        continue;
    }
    $mapped = [];
    foreach (($crudMap[$moduleKey]['crud'] ?? []) as $actions) {
        if (is_array($actions)) {
            foreach ($actions as $a) {
                $mapped[$a] = true;
            }
        }
    }
    foreach (($entry['routes'] ?? []) as $controller => $actions) {
        if (!is_array($actions)) {
            continue;
        }
        foreach ($actions as $action) {
            $action = (string) $action;
            if ($action !== '' && !isset($mapped[$action])) {
                $missing[$moduleKey][] = $controller . '::' . $action;
            }
        }
    }
}

if ($missing === []) {
    echo "OK — core/site modüllerinde crud-map dışı action yok.\n";
    exit(0);
}

echo "Eksik action'lar (fallback: {module}.read):\n";
foreach ($missing as $mod => $items) {
    echo "\n[$mod]\n";
    foreach ($items as $item) {
        echo "  - $item\n";
    }
}
exit(1);
