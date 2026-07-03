<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/config.php';
$port = defined('DB_PORT') && (string) DB_PORT !== '' ? ';port=' . (int) DB_PORT : '';
$pdo = new PDO('mysql:host=' . DB_HOST . $port . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$sql = file_get_contents(dirname(__DIR__) . '/database/migrate_esh_hastaliklar_tree_columns.sql');
foreach (array_filter(array_map('trim', explode(';', $sql))) as $q) {
    if ($q === '') {
        continue;
    }
    try {
        $pdo->exec($q);
        echo "OK: " . substr($q, 0, 70) . "...\n";
    } catch (Throwable $e) {
        echo "SKIP: " . $e->getMessage() . "\n";
    }
}
