<?php
/**
 * Stok store action'ları — CLI smoke test (oturum simülasyonu).
 * Kullanım: php tools/verify_stok_stores.php
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

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$db = \App\Core\Database::getInstance();
$admin = $db->fetchObjectPrepared(
    'SELECT id, kurum_id, isadmin FROM #__users WHERE isadmin >= 1 AND activated = 1 ORDER BY id ASC LIMIT 1'
);
if (!$admin) {
    fwrite(STDERR, "Admin user not found.\n");
    exit(1);
}

$_SESSION['user_id'] = (int) $admin->id;
$_SESSION['isadmin'] = (int) $admin->isadmin;
\App\Helpers\TenantContext::syncSessionFromUser(
    isset($admin->kurum_id) ? (int) $admin->kurum_id : 1,
    (int) $admin->isadmin
);

$kurumId = (int) ($admin->kurum_id ?? 1);
$malzeme = $db->fetchObjectPrepared(
    'SELECT id FROM #__stok_malzeme WHERE kurum_id = ? AND aktif = 1 ORDER BY id ASC LIMIT 1',
    [$kurumId]
);
if (!$malzeme) {
    fwrite(STDERR, "No active malzeme for kurum {$kurumId}.\n");
    exit(1);
}

$hasta = $db->fetchObjectPrepared(
    "SELECT id FROM #__hastalar WHERE kurum_id = ? AND pasif = '0' ORDER BY id ASC LIMIT 1",
    [$kurumId]
);

$service = new \App\Services\Stok\StokService();
$tests = [];

// Giriş
$r = $service->recordMovement([
    'kurum_id' => $kurumId,
    'malzeme_id' => (int) $malzeme->id,
    'hareket_tipi' => 'giris',
    'miktar' => 1,
    'hareket_tarihi' => date('Y-m-d'),
    'kullanici_id' => (int) $admin->id,
    'aciklama' => 'verify_stok_stores',
]);
$tests['giris'] = $r;

// Çıkış
$r = $service->recordMovement([
    'kurum_id' => $kurumId,
    'malzeme_id' => (int) $malzeme->id,
    'hareket_tipi' => 'cikis',
    'miktar' => 0.5,
    'hareket_tarihi' => date('Y-m-d'),
    'kullanici_id' => (int) $admin->id,
    'hasta_id' => $hasta ? (int) $hasta->id : null,
]);
$tests['cikis'] = $r;

// İade
$r = $service->recordMovement([
    'kurum_id' => $kurumId,
    'malzeme_id' => (int) $malzeme->id,
    'hareket_tipi' => 'iade',
    'miktar' => 0.25,
    'hareket_tarihi' => date('Y-m-d'),
    'kullanici_id' => (int) $admin->id,
    'hasta_id' => $hasta ? (int) $hasta->id : null,
]);
$tests['iade'] = $r;

// Sayım
$r = $service->recordSayimAdjustment([
    'kurum_id' => $kurumId,
    'malzeme_id' => (int) $malzeme->id,
    'sayilan_miktar' => $service->getCurrentStock($kurumId, (int) $malzeme->id),
    'hareket_tarihi' => date('Y-m-d'),
    'kullanici_id' => (int) $admin->id,
]);
$tests['sayim'] = $r;

$failed = 0;
foreach ($tests as $name => $result) {
    $ok = !empty($result['ok']);
    echo $name . ': ' . ($ok ? 'OK' : 'FAIL — ' . ($result['error'] ?? 'unknown')) . PHP_EOL;
    if (!$ok) {
        ++$failed;
    }
}

// assignKurumIdForStore superadmin edge
$_SESSION['user_id'] = 1;
$_SESSION['isadmin'] = 2;
\App\Helpers\TenantContext::syncSessionFromUser(null, 2);
unset($_SESSION[\App\Helpers\TenantContext::SESSION_KURUM_FILTER]);
try {
    $kid = \App\Helpers\TenantContext::assignKurumIdForStore(null);
    echo 'superadmin_assign_kurum: OK (' . $kid . ')' . PHP_EOL;
} catch (\Throwable $e) {
    echo 'superadmin_assign_kurum: EXCEPTION — ' . $e->getMessage() . PHP_EOL;
    ++$failed;
}

exit($failed > 0 ? 1 : 0);
