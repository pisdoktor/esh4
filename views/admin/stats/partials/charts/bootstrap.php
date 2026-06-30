<?php
/** @var array $data */
$has = $data['hastaliklar'] ?? [];
$categories = $data['hastalik_kategorileri'] ?? [];
$totalAktif = (int) ($data['total_aktif'] ?? 0);
$topTani = array_slice($has, 0, 15);
$hastalikLabels = [];
$hastalikValues = [];
foreach (array_slice($has, 0, 12) as $h) {
    $hastalikLabels[] = $h->etiket ?? '—';
    $hastalikValues[] = (int) ($h->sayi ?? 0);
}
?>