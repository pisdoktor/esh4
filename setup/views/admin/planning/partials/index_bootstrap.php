<?php
/**
 * @var string $ilce_id
 * @var string $bolge_filter
 * @var bool $filterExpanded
 * @var int $page
 * @var int $limit
 * @var int $total
 * @var int $totalPages
 * @var array $rows
 * @var array $districts
 * @var array $gunler val => kısa etiket
 * @var int $bolge_max
 */
$gunSirasi = ['1', '2', '3', '4', '5', '6', '0'];
$bolge_filter = (string) ($bolge_filter ?? '');
$filterExpanded = !empty($filterExpanded);
$planningListQuery = static function (?int $pageNum = null) use ($ilce_id, $bolge_filter, $limit, $orderby, $orderdir): array {
    $q = [
        'controller' => 'Planning',
        'action' => 'index',
    ];
    if ($ilce_id !== '' && $ilce_id !== '0') {
        $q['ilce'] = $ilce_id;
    }
    if ($bolge_filter !== '') {
        $q['bolge'] = $bolge_filter;
    }
    if ((int) $limit !== 30) {
        $q['limit'] = (int) $limit;
    }
    if ($pageNum !== null && $pageNum > 1) {
        $q['page'] = $pageNum;
    }
    if (!empty($orderby)) {
        $q['orderby'] = (string) $orderby;
        $q['orderdir'] = (string) ($orderdir ?? 'ASC');
    }
    return $q;
};
$planningPagelink = \App\Helpers\UrlHelper::fromRequestParams($planningListQuery());
$ordering = trim(($orderby ?? 'bolge') . ' ' . ($orderdir ?? 'ASC'));
$eshSortCfg = $eshSortCfg ?? ['mode' => 'orderby', 'pagelink' => \App\Helpers\UrlHelper::fromRequestParams($planningListQuery(null))];

$admin_list_title = 'Mahalle bölge ve gün planlaması';
$admin_list_icon = 'fa-solid fa-map-location-dot';
$admin_list_subtitle = 'Her mahalle için ekip bölgesi ve haftanın hangi günleri ziyaret edileceğini ayarlayın (eski admin planlama).';
ob_start();
?>
    <a href="<?= htmlspecialchars(esh_url('Planning', 'table'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-info">
        <i class="fa-solid fa-calendar-week me-1"></i> Haftalık tablo
    </a>
<?php
$admin_list_actions = ob_get_clean();
$admin_list_body_class = 'p-0';
$admin_list_container_extra_classes = 'pt-0 pb-4';
?>