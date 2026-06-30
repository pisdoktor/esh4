<?php
/**
 * @var array $ilceler
 * @var array $mahalleler
 * @var string $ilce
 * @var string $mahalle
 * @var int $page
 * @var int $pages
 * @var int $total
 * @var int $limit
 * @var bool $filterExpanded
 */
$filterExpanded = !empty($filterExpanded);
$orderby = (string) ($orderby ?? 'h.isim');
$orderdir = (string) ($orderdir ?? 'ASC');
$sortBase = array_filter([
    'controller' => 'Stats',
    'action' => 'eraporList',
    'ilce' => $ilce ?? '',
    'mahalle' => $mahalle ?? '',
    'limit' => (string) ($limit ?? 50),
]);
$pagelink = \App\Helpers\UrlHelper::fromRequestParams($sortBase);
$ordering = trim($orderby . ' ' . $orderdir);
$eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];
$eraporPagelink = $pagelink . '&' . http_build_query(['orderby' => $orderby, 'orderdir' => $orderdir]);
$eshPatientDrilldownDateColumn = 'randevu';
?>