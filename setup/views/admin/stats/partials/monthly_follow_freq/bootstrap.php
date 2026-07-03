<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php
/**
 * @var object $monthly
 * @var array<int, object> $history
 */
$th = (int) ($monthly->toplamhasta ?? 0);
$ti = (int) ($monthly->toplamizlem ?? 0);
$sik = $th > 0 ? round($ti / $th, 2) : 0;

$turkceAylar = [
    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
    7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
];
$history = is_array($history) ? $history : [];
?>