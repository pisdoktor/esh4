<?php
/**
 * @var int $year
 * @var int $month
 * @var string $period_label
 * @var array<int, string> $turkce_aylar
 * @var string $ilce
 * @var string $mahalle
 * @var array $ilceler
 * @var array $mahalleler
 * @var int $page
 * @var int $pages
 * @var int $total
 * @var int $limit
 * @var string $orderby
 * @var string $orderdir
 */
$yearMax = (int) date('Y');
$yearMin = 2010;
$linkParams = [
    'controller' => 'Stats',
    'action' => 'aylikTekIzlemliler',
    'year' => (string) $year,
    'month' => (string) $month,
    'limit' => (string) $limit,
];
if ($ilce !== '' && $ilce !== '0') {
    $linkParams['ilce'] = $ilce;
}
if ($mahalle !== '' && $mahalle !== '0') {
    $linkParams['mahalle'] = $mahalle;
}
$pagelink = \App\Helpers\UrlHelper::fromRequestParams($linkParams);
$ordering = trim($orderby . ' ' . $orderdir);
$birPagelink = $pagelink . '&' . http_build_query(['orderby' => $orderby, 'orderdir' => $orderdir]);
$eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];
?>