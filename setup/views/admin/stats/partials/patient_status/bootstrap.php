<?php
/** @var array $rows */
$tanimlar = [
    '0' => ['label' => 'Aktif', 'badge' => 'bg-success'],
    '1' => ['label' => 'Pasif', 'badge' => 'bg-danger'],
    '-1' => ['label' => 'Muhtemel ölen', 'badge' => 'bg-secondary'],
    '5' => ['label' => 'Silinen', 'badge' => 'bg-dark'],
    '4' => ['label' => 'Arafta', 'badge' => 'bg-warning text-dark'],
    '-3' => ['label' => 'Bekleyen', 'badge' => 'bg-primary'],
];
$veriler = [];
foreach ($rows as $r) {
    $veriler[(string) $r->pasif] = (int) $r->adet;
}
$genelToplam = array_sum($veriler);
?>