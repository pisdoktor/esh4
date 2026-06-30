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
$db = App\Core\Database::getInstance();
$platform = (int) $db->loadResultPrepared('SELECT COUNT(*) FROM esh_hastaliklar WHERE kurum_id = 0', []);
echo "Platform katalog: {$platform}\n";
$rows = $db->fetchObjectListPrepared(
    'SELECT icd, hastalikadi FROM esh_hastaliklar WHERE kurum_id = 0 AND icd LIKE ? ORDER BY icd LIMIT 15',
    ['I10%']
);
foreach ($rows as $r) {
    echo $r->icd . ' | ' . $r->hastalikadi . "\n";
}
$garbage = (int) $db->loadResultPrepared(
    "SELECT COUNT(*) FROM esh_hastaliklar WHERE kurum_id = 0 AND icd NOT REGEXP '^[A-Z][0-9]{2}([.][0-9A-Z]+)*$'",
    []
);
echo "Geçersiz ICD formatı: {$garbage}\n";

if (isset($argv[1]) && $argv[1] === '--cleanup' && $garbage > 0) {
    $db->executePrepared(
        "DELETE FROM esh_hastaliklar WHERE kurum_id = 0 AND icd NOT REGEXP '^[A-Z][0-9]{2}([.][0-9A-Z]+)*$'",
        []
    );
    $after = (int) $db->loadResultPrepared('SELECT COUNT(*) FROM esh_hastaliklar WHERE kurum_id = 0', []);
    echo "Temizlik sonrası platform katalog: {$after}\n";
}
