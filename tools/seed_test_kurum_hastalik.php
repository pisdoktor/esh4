<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/config.php';
$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
$kid = (int) $pdo->query('SELECT id FROM esh_kurumlar ORDER BY id LIMIT 1')->fetchColumn();
$ids = $pdo->query('SELECT id FROM esh_hastaliklar WHERE kurum_id=0 AND parent_icd LIKE "A00-A09" LIMIT 20')->fetchAll(PDO::FETCH_COLUMN);
foreach ($ids as $hid) {
    $pdo->prepare('INSERT IGNORE INTO esh_kurum_hastalik (kurum_id, hastalik_id) VALUES (?,?)')->execute([$kid, $hid]);
}
echo "kurum {$kid}, assigned " . count($ids) . " under A00-A09\n";
