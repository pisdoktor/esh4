<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/config.php';
$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
$n = (int) $pdo->query("SELECT COUNT(*) FROM esh_hastaliklar WHERE kurum_id=0 AND parent_icd IS NOT NULL AND TRIM(parent_icd)!=''")->fetchColumn();
echo "with parent_icd: {$n}\n";
$roots = (int) $pdo->query("SELECT COUNT(*) FROM esh_hastaliklar h WHERE h.kurum_id=0 AND (
    h.parent_icd LIKE '%-%'
    OR h.seviye = 2
    OR (
        h.seviye IS NULL AND (h.parent_icd IS NULL OR TRIM(h.parent_icd)='')
        AND EXISTS (SELECT 1 FROM esh_hastaliklar c WHERE c.kurum_id=0 AND c.parent_icd=h.icd)
    )
    OR (
        h.seviye IS NOT NULL AND h.seviye <= 2
        AND h.parent_icd IS NOT NULL AND TRIM(h.parent_icd)!=''
        AND NOT EXISTS (SELECT 1 FROM esh_hastaliklar p WHERE p.kurum_id=0 AND p.icd=h.parent_icd)
    )
)")->fetchColumn();
echo "root nodes: {$roots}\n";
$sample = $pdo->query("SELECT icd, parent_icd, seviye FROM esh_hastaliklar WHERE parent_icd='A00' ORDER BY icd LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($sample);
