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
$model = new App\Models\Hastalik();
$roots = $model->getTreeChildren(null, null, 10);
echo 'root count sample: ' . count($roots) . "\n";
foreach ($roots as $r) {
    echo ($r->icd ?? '') . ' | ' . ($r->hastalikadi ?? '') . ' | children=' . ($r->child_count ?? 0) . "\n";
}
$kids = $model->getTreeChildren('A00-A09', null, 10);
echo "\nA00-A09 children (seviye 2): " . count($kids) . "\n";
foreach ($kids as $r) {
    echo ($r->icd ?? '') . ' — ' . ($r->hastalikadi ?? '') . ' | seviye=' . ($r->seviye ?? '') . "\n";
}
$kids = $model->getTreeChildren('A00', null, 10);
echo "\nA00 children: " . count($kids) . "\n";
foreach ($kids as $r) {
    echo ($r->icd ?? '') . ' — ' . ($r->hastalikadi ?? '') . "\n";
}
