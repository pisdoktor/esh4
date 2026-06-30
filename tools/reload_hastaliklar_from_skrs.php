<?php
/**
 * esh_hastaliklar + esh_kurum_hastalik temizle, ardından SKRS import çalıştır.
 * Kullanım: php tools/reload_hastaliklar_from_skrs.php [xlsx]
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI gerekli.\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/config/config.php';

$skrs = isset($argv[1]) && trim((string) $argv[1]) !== ''
    ? trim((string) $argv[1])
    : $root . '/storage/ICD10Listesi.xlsx';
if (!is_readable($skrs)) {
    fwrite(STDERR, "SKRS dosyası okunamadı: {$skrs}\n");
    exit(1);
}

$port = defined('DB_PORT') && (string) DB_PORT !== '' ? ';port=' . (int) DB_PORT : '';
$dsn = 'mysql:host=' . DB_HOST . $port . ';dbname=' . DB_NAME . ';charset=utf8mb4';
$pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$beforeH = (int) $pdo->query('SELECT COUNT(*) FROM esh_hastaliklar')->fetchColumn();
$beforeK = 0;
try {
    $beforeK = (int) $pdo->query('SELECT COUNT(*) FROM esh_kurum_hastalik')->fetchColumn();
} catch (Throwable) {
    // tablo yoksa import sonrası oluşturulur
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
try {
    $pdo->exec('TRUNCATE TABLE esh_kurum_hastalik');
} catch (Throwable $e) {
    fwrite(STDERR, 'esh_kurum_hastalik: ' . $e->getMessage() . "\n");
}
$pdo->exec('TRUNCATE TABLE esh_hastaliklar');
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "Temizlendi: esh_hastaliklar ({$beforeH} satır), esh_kurum_hastalik ({$beforeK} satır)\n";

$php = PHP_BINARY ?: 'php';
$importScript = $root . '/tools/migrate_import_icd10_hastaliklar.php';
$cmd = escapeshellarg($php) . ' ' . escapeshellarg($importScript) . ' ' . escapeshellarg($skrs);
passthru($cmd, $code);
exit($code);
