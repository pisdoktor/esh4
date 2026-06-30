<?php
/**
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
$linkParams = [
    'controller' => 'Stats',
    'action' => 'birIzlemliler',
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