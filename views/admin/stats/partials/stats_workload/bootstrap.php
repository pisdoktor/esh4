<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
﻿<?php
/** @var array $rows */
/** @var string $activeGroup */
/** @var string $tabSlug */
/** @var array{KRITIK:int,KRONIK:int,STANDART:int} $groupCounts */
/** @var int $page */
/** @var int $pages */
/** @var int $limit */
/** @var int $total */
/** @var string $pagelink */

$workloadTabs = [
    ['slug' => 'kritik', 'group' => 'KRITIK', 'label' => 'Kritik', 'outline' => 'danger'],
    ['slug' => 'kronik', 'group' => 'KRONIK', 'label' => 'Kronik', 'outline' => 'warning'],
    ['slug' => 'standart', 'group' => 'STANDART', 'label' => 'Standart', 'outline' => 'primary'],
];
$activeGroup = (string) ($activeGroup ?? 'KRITIK');
$tabSlug = (string) ($tabSlug ?? 'kritik');
$groupCounts = $groupCounts ?? ['KRITIK' => 0, 'KRONIK' => 0, 'STANDART' => 0];
$workloadColspan = 9;
$rowClass = $activeGroup === 'KRITIK' ? 'table-danger' : ($activeGroup === 'KRONIK' ? 'table-warning' : '');
$limit = (int) ($limit ?? 50);
$page = (int) ($page ?? 1);
$total = (int) ($total ?? 0);
$orderby = (string) ($orderby ?? 'hizmet_suresi_gun');
$orderdir = (string) ($orderdir ?? 'DESC');
$sortBase = [
    'controller' => 'Stats',
    'action' => 'workload',
    'tab' => $tabSlug,
    'limit' => (string) $limit,
];
$pagelink = \App\Helpers\UrlHelper::fromRequestParams($sortBase);
$ordering = trim($orderby . ' ' . $orderdir);
$eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];
$pagelinkWithSort = $pagelink . '&' . http_build_query(['orderby' => $orderby, 'orderdir' => $orderdir]);
?>