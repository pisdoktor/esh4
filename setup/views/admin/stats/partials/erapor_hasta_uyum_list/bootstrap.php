<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php
/**
 * @var string $metric
 * @var string $metricLabel
 * @var bool $patientPrimary
 * @var int $page
 * @var int $pages
 * @var int $total
 * @var int $limit
 * @var string $orderby
 * @var string $orderdir
 * @var string $eraporHastaUyumMetricsFetchUrl
 * @var string $eraporHastaUyumListRowsFetchUrl
 */
$labelEsc = htmlspecialchars($metricLabel, ENT_QUOTES, 'UTF-8');
$metricEsc = htmlspecialchars($metric, ENT_QUOTES, 'UTF-8');
$sortBase = [
    'controller' => 'Stats',
    'action' => 'eraporHastaUyumList',
    'metric' => $metric,
    'limit' => (string) $limit,
];
$pagelink = \App\Helpers\UrlHelper::fromRequestParams($sortBase);
$hpPagelink = $pagelink . '&' . http_build_query(['orderby' => $orderby, 'orderdir' => $orderdir]);
$ordering = trim($orderby . ' ' . $orderdir);
$eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];
?>