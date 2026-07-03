<?php
/**
 * @var string $ilce
 * @var string $mahalle
 * @var string $sokak
 * @var string $kapino
 * @var string $ozellik
 * @var array<string, string> $ozellikLabels
 * @var list<object> $ilceler
 * @var list<object> $mahalleler
 * @var list<object> $sokaklar
 * @var list<object> $kapinolar
 * @var int $page
 * @var int $pages
 * @var int $total
 * @var int $limit
 * @var string $orderby
 * @var string $orderdir
 * @var string $adresPatientFilterRowsFetchUrl
 * @var string $adresFilterAjaxUrl
 * @var bool $filterExpanded
 */
$filterExpanded = !empty($filterExpanded);
$linkParams = [
    'controller' => 'Stats',
    'action' => 'adresPatientFilter',
    'limit' => (string) $limit,
];
foreach (['ilce', 'mahalle', 'sokak', 'kapino', 'ozellik'] as $fk) {
    if (!empty($$fk)) {
        $linkParams[$fk] = $$fk;
    }
}
$pagelink = \App\Helpers\UrlHelper::fromRequestParams($linkParams);
$ordering = trim($orderby . ' ' . $orderdir);
$sortPagelink = $pagelink . '&' . http_build_query(['orderby' => $orderby, 'orderdir' => $orderdir]);
$eshSortCfg = ['mode' => 'orderby', 'pagelink' => $pagelink];

$eshAdresFilterOptions = static function (array $rows, string $placeholder): array {
    $options = [\App\Helpers\FormHelper::makeOption('', $placeholder)];
    foreach ($rows as $row) {
        $id = (string) ($row->id ?? '');
        $label = (string) ($row->adi ?? '');
        $sayi = (int) ($row->sayi ?? 0);
        $options[] = \App\Helpers\FormHelper::makeOption($id, $label . ' [' . $sayi . ']');
    }

    return $options;
};
?>