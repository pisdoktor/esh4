<?php
/**
 * Yavaş sorgu optimizasyonu — yerel doğrulama (CLI).
 * Kullanım: php tools/verify_slow_queries.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Core\Database;
use App\Models\Erapor;
use App\Models\Patient;

$db = Database::getInstance();

$tests = [
    'Patient unified active' => function () {
        $m = new Patient();
        $t0 = microtime(true);
        $m->getUnified(20, 0, 'h.isim ASC', 'active');
        return (microtime(true) - $t0) * 1000;
    },
    'Patient unified all' => function () {
        $m = new Patient();
        $t0 = microtime(true);
        $m->getUnified(20, 0, 'h.isim ASC', 'all');
        return (microtime(true) - $t0) * 1000;
    },
    'Erapor index page' => function () {
        $m = new Erapor();
        $t0 = microtime(true);
        $m->getReportsPage(20, 0, []);
        return (microtime(true) - $t0) * 1000;
    },
    'Stats adres patient filter' => function () {
        require_once dirname(__DIR__) . '/app/Models/Stats.php';
        $m = new \App\Models\Stats();
        $t0 = microtime(true);
        $m->getAdresPatientFilterRows(null, null, null, null, null, 'ORDER BY h.isim ASC, h.soyisim ASC', 50, 0);
        return (microtime(true) - $t0) * 1000;
    },
    'Stats mama rapor rows' => function () {
        require_once dirname(__DIR__) . '/app/Models/Stats.php';
        $m = new \App\Models\Stats();
        $t0 = microtime(true);
        $m->getMamaRaporRows(null, null, 50, 0);
        return (microtime(true) - $t0) * 1000;
    },
    'Harita active patients map' => function () {
        $m = new Patient();
        $t0 = microtime(true);
        $m->getActivePatientsWithCoordsForMap();
        return (microtime(true) - $t0) * 1000;
    },
    'Adres kapino coord stats' => function () {
        require_once dirname(__DIR__) . '/app/Models/Address.php';
        $m = new \App\Models\Address();
        $t0 = microtime(true);
        $m->getKapinoCoordStats();
        return (microtime(true) - $t0) * 1000;
    },
    'Stats erapor hasta uyum snapshot' => function () {
        require_once dirname(__DIR__) . '/app/Models/Stats.php';
        $m = new \App\Models\Stats();
        $t0 = microtime(true);
        $m->getEraporHastaUyumSnapshot();
        return (microtime(true) - $t0) * 1000;
    },
    'Stats data health snapshot' => function () {
        require_once dirname(__DIR__) . '/app/Models/Stats.php';
        $m = new \App\Models\Stats();
        $t0 = microtime(true);
        $m->getDataHealthSnapshot();
        return (microtime(true) - $t0) * 1000;
    },
];

echo "DB: " . DB_NAME . PHP_EOL;
foreach ($tests as $label => $fn) {
    $ms = round($fn(), 2);
    $ok = $ms < 500 ? 'OK' : 'SLOW';
    echo sprintf("[%s] %s — %.2f ms\n", $ok, $label, $ms);
}

$explainSql = "SELECT e.*, b.bransadi, COALESCE(tc_agg.tc_havuz_adet, 1) AS tc_havuz_adet
    FROM esh_erapor e
    LEFT JOIN esh_branslar b ON (e.brans = b.id OR e.brans = b.bransadi)
    LEFT JOIN (
        SELECT hastatckimlik, COUNT(*) AS tc_havuz_adet
        FROM esh_erapor
        WHERE hastatckimlik IS NOT NULL AND hastatckimlik <> ''
        GROUP BY hastatckimlik
    ) tc_agg ON tc_agg.hastatckimlik = e.hastatckimlik
    WHERE 1=1
    ORDER BY e.basvurutarihi DESC, e.id DESC
    LIMIT 20";

echo PHP_EOL . 'EXPLAIN (Erapor page):' . PHP_EOL;
$rows = $db->fetchObjectListPrepared('EXPLAIN ' . $explainSql);
foreach ($rows as $r) {
    echo '  ' . ($r->table ?? '') . ' | type=' . ($r->type ?? '') . ' | key=' . ($r->key ?? '') . PHP_EOL;
}
