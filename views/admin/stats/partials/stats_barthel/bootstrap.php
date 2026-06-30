<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php

/** @var object $b */

$grup = [

    'Tam bağımlı (0–20)' => (int) ($b->g_0_20 ?? 0),

    'Ağır (21–61)' => (int) ($b->g_21_61 ?? 0),

    'Orta (62–90)' => (int) ($b->g_62_90 ?? 0),

    'Hafif (91–99)' => (int) ($b->g_91_99 ?? 0),

    'Tam bağımsız (100)' => (int) ($b->g_100 ?? 0),

];

$tot = max(1, (int) ($b->toplam_hasta ?? 0));

?>