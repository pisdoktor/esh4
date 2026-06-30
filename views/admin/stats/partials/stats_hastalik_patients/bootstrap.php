<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php
/**
 * @var object $hastalik
 * @var int $hastalik_id
 * @var array $rows
 * @var int $page
 * @var int $pages
 * @var int $total
 * @var int $limit
 * @var string $orderby
 * @var string $orderdir
 */
$hAd = htmlspecialchars((string) ($hastalik->hastalikadi ?? ''), ENT_QUOTES, 'UTF-8');

$sortBase = [
    'controller' => 'Stats',
    'action' => 'hastalikPatients',
    'id' => (string) $hastalik_id,
    'limit' => (string) $limit,
];
$ordering = trim($orderby . ' ' . $orderdir);
$eshSortCfg = ['mode' => 'orderby', 'base' => $sortBase];

$hpPagelink = \App\Helpers\UrlHelper::fromRequestParams(array_merge($sortBase, [
    'orderby' => $orderby,
    'orderdir' => $orderdir,
]));

$ayAdlar = [1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'];
?>