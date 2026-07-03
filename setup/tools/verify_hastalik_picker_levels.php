<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/config.php';
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
$m = new App\Models\Hastalik();
$l1 = $m->resolvePickerIds('A00-A09', true, 0);
$l2 = $m->resolvePickerIds('A00', false, 0);
$l3 = $m->resolvePickerIds('A00.0', false, 0);
echo 'L1 A00-A09 ids: ' . count($l1) . "\n";
echo 'L2 A00 ids: ' . count($l2) . ' -> ' . implode(',', $l2) . "\n";
echo 'L3 A00.0 ids: ' . count($l3) . ' -> ' . implode(',', $l3) . "\n";
