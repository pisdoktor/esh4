<?php
/** @var string $date_from */
/** @var string $date_to */
/** @var int $page */
/** @var int $pages */
/** @var int $total */
/** @var int $limit */
/** @var bool $filterExpanded */
$filterExpanded = !empty($filterExpanded);
$orderby = (string) ($orderby ?? 'sonda_degisim');
$orderdir = (string) ($orderdir ?? 'ASC');
$sortBase = array_filter([
    'controller' => 'Stats',
    'action' => 'sondaChanges',
    'date_from' => $date_from ?? '',
    'date_to' => $date_to ?? '',
    'limit' => (string) ($limit ?? 50),
]);
$pagelink = \App\Helpers\UrlHelper::fromRequestParams($sortBase);
$ordering = trim($orderby . ' ' . $orderdir);
$eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];
$sondaPagelink = $pagelink . '&' . http_build_query(['orderby' => $orderby, 'orderdir' => $orderdir]);
$eshPatientDrilldownDateColumn = 'sonda_degisim';
?>