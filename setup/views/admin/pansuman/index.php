<?php
/**
 * @var string $search
 * @var string $filter_day
 * @var string $eshPansumanListKurum
 * @var bool $filterExpanded
 * @var int $page
 * @var int $limit
 * @var int $total
 * @var int $totalPages
 * @var array $rows
 * @var array $gunler
 * @var array $zamanlar
 */
use App\Helpers\FormHelper;

$search = (string) ($search ?? '');
$filter_day = (string) ($filter_day ?? '');
$eshPansumanListKurum = (string) ($eshPansumanListKurum ?? '');
$filterExpanded = !empty($filterExpanded);
$pansumanListQuery = static function (?int $pageNum = null) use ($search, $filter_day, $eshPansumanListKurum, $limit, $orderby, $orderdir): array {
    $q = [
        'controller' => 'Pansuman',
        'action' => 'index',
    ];
    if ($search !== '') {
        $q['search'] = $search;
    }
    if ($filter_day !== '') {
        $q['filter_day'] = $filter_day;
    }
    if ($eshPansumanListKurum !== '') {
        $q['kurum_id'] = $eshPansumanListKurum;
    }
    if ((int) $limit !== 20) {
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
$pansumanPagelink = \App\Helpers\UrlHelper::fromRequestParams($pansumanListQuery());
$ordering = trim(($orderby ?? 'h.isim') . ' ' . ($orderdir ?? 'ASC'));
$eshSortCfg = $eshSortCfg ?? ['mode' => 'orderby', 'pagelink' => \App\Helpers\UrlHelper::fromRequestParams($pansumanListQuery(null))];

$admin_list_title = 'Pansuman Planlama';
$admin_list_icon = 'fa-solid fa-hand-holding-medical';
$admin_list_subtitle = 'Pansuman hastalarının haftalık uygulama günleri ve zaman dilimini düzenleyin.';
$admin_list_body_class = 'p-0';
$admin_list_container_extra_classes = 'pt-0 pb-4';
?>
<?php include __DIR__ . '/partials/filter_card.php'; ?>
<?php include __DIR__ . '/partials/list_table_shell.php'; ?>
