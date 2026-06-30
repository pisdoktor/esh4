<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/config.php';
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) require $file;
});
$t0 = microtime(true);
$m = new App\Models\Hastalik();
$roots = $m->getTreeChildren(null, null, 300);
$t1 = microtime(true);
echo 'getTreeRangeRoots: ' . round(($t1-$t0)*1000) . " ms, count=" . count($roots) . "\n";
$t0 = microtime(true);
$state = $m->getVirtualRangeAssignmentState(1);
$t1 = microtime(true);
echo 'getVirtualRangeAssignmentState (kurum 1): ' . round(($t1-$t0)*1000) . " ms, ranges=" . count($state) . "\n";
// simulate full treeNodes root path
$t0 = microtime(true);
$kh = new App\Models\KurumHastalik();
$assigned = array_flip($kh->getAssignedIds(1));
$range = $assigned !== [] ? $m->getVirtualRangeAssignmentState(1) : [];
$items = [];
foreach ($roots as $row) {
    $items[] = App\Models\Hastalik::mapRowToTreeNode($row, false, true);
}
$t1 = microtime(true);
echo 'simulated picker root treeNodes: ' . round(($t1-$t0)*1000) . " ms\n";
