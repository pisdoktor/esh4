<?php
/**
 * @var string $tab mama|bez
 * @var string $date_from
 * @var string $date_to
 * @var int $page
 * @var int $pages
 * @var int $total
 * @var int $limit
 * @var bool $filterExpanded
 */
$raporBitisLabel = $tab === 'bez' ? 'Bez rapor bitiş' : 'Mama rapor bitiş';
$filterExpanded = !empty($filterExpanded);
$orderby = (string) ($orderby ?? ($tab === 'bez' ? 'h.bezraporbitis' : 'h.mamaraporbitis'));
$orderdir = (string) ($orderdir ?? 'ASC');
$sortBase = array_filter([
    'controller' => 'Stats',
    'action' => 'supplyReports',
    'tab' => $tab,
    'date_from' => $date_from ?? '',
    'date_to' => $date_to ?? '',
    'limit' => (string) ($limit ?? 50),
]);
$pagelink = \App\Helpers\UrlHelper::fromRequestParams($sortBase);
$ordering = trim($orderby . ' ' . $orderdir);
$eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];
$supplyPagelink = $pagelink . '&' . http_build_query(['orderby' => $orderby, 'orderdir' => $orderdir]);
$eshPatientDrilldownDateColumn = $tab === 'bez' ? 'bez_bitis' : 'mama_bitis';
$eshPatientDrilldownDateLabel = $raporBitisLabel;
?>