<?php
declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php tools/run_sql_migration.php database/migrate_xxx.sql\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/app/Core/DbSqlHelper.php';

$path = dirname(__DIR__) . '/' . ltrim($argv[1], '/');
if (!is_readable($path)) {
    fwrite(STDERR, "File not found: {$path}\n");
    exit(1);
}

$driver = \App\Core\DbSqlHelper::driver();
if ($driver === 'sqlsrv') {
    $server = (string) DB_HOST;
    if (defined('DB_PORT') && (string) DB_PORT !== '' && strpos($server, ',') === false && strpos($server, '\\') === false) {
        $server .= ',' . (string) DB_PORT;
    }
    $dsn = 'sqlsrv:Server=' . $server . ';Database=' . DB_NAME . ';TrustServerCertificate=1';
} else {
    $port = defined('DB_PORT') && (string) DB_PORT !== '' ? ';port=' . (int) DB_PORT : '';
    $dsn = 'mysql:host=' . DB_HOST . $port . ';dbname=' . DB_NAME . ';charset=utf8mb4';
}

$pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$sql = file_get_contents($path);
if (!is_string($sql)) {
    exit(1);
}

$pdo->exec($sql);
echo "OK: {$argv[1]} on " . DB_NAME . " (" . $driver . ")" . PHP_EOL;
