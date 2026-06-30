<?php
declare(strict_types=1);
$pdo = new PDO('sqlsrv:Server=localhost\\SQLEXPRESS;Database=esh4;TrustServerCertificate=1', '', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$tables = $pdo->query("SELECT name FROM sys.tables WHERE name LIKE 'esh_%' ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
echo 'MSSQL tables: ' . count($tables) . PHP_EOL;
foreach (['esh_kurumlar','esh_hastalar','esh_users','esh_izlemler','esh_adrestablosu','esh_rehber_ilac'] as $t) {
    $c = (int) $pdo->query('SELECT COUNT(*) FROM [' . str_replace(']', ']]', $t) . ']')->fetchColumn();
    echo $t . "\t" . $c . PHP_EOL;
}
