<?php
/**
 * @var array<int, object> $rows
 * @var int $limit 0 = tüm aylar
 */
$turkceAylar = [
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
    7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
];

$chartRows = $rows;
if (count($chartRows) > 1) {
    $chartRows = array_reverse($chartRows);
}

$chartLabels = [];
$chartErkek = [];
$chartKadin = [];
$chartToplam = [];
$sumErkek = 0;
$sumKadin = 0;
$sumToplam = 0;

foreach ($chartRows as $r) {
    $yil = (int) ($r->kayityili ?? 0);
    $ayNo = (int) ($r->kayitay ?? 0);
    $ayAdi = $turkceAylar[$ayNo] ?? '—';
    $chartLabels[] = $ayAdi . ' ' . $yil;
    $chartErkek[] = (int) ($r->erkek_sayisi ?? 0);
    $chartKadin[] = (int) ($r->kadin_sayisi ?? 0);
    $chartToplam[] = (int) ($r->toplam_sayi ?? 0);
}

foreach ($rows as $r) {
    $sumErkek += (int) ($r->erkek_sayisi ?? 0);
    $sumKadin += (int) ($r->kadin_sayisi ?? 0);
    $sumToplam += (int) ($r->toplam_sayi ?? 0);
}

$limitOptions = [12 => 'Son 12 ay', 24 => 'Son 24 ay', 48 => 'Son 48 ay', 0 => 'Tüm aylar'];
$baseQ = ['controller' => 'Stats', 'action' => 'kayitMonths'];
?>