<?php
/**
 * @var array $categories
 * @var string $eshHastalikListCat
 * @var bool $filterExpanded
 * @var bool $isCatalogPickerMode
 * @var string $searchQ
 */
$isCatalogPickerMode = $isCatalogPickerMode ?? false;
$searchQ = isset($searchQ) ? (string) $searchQ : '';
$admin_list_title = $isCatalogPickerMode ? 'Kurum Tanı Seçimi' : 'Hastalık ve Tanı Kütüphanesi (Platform)';
$admin_list_icon = 'fa-solid fa-microscope';
$admin_list_subtitle = $isCatalogPickerMode
    ? 'Platform ICD-10 kataloğundan kurumunuza tanı ekleyin (ağaç görünümü).'
    : 'ICD-10 tanıları hiyerarşik ağaçta; kategori ve arama ile süzebilirsiniz.';
ob_start();
if (!$isCatalogPickerMode): ?>
<a href="<?= htmlspecialchars(esh_url('Hastalik', 'create'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm shadow-sm">
    <i class="fa-solid fa-plus me-1"></i>Yeni Tanı Ekle
</a>
<?php endif;
$admin_list_actions = ob_get_clean();
$admin_list_body_class = 'p-0';
$admin_list_container_extra_classes = 'pt-0 pb-4';
include dirname(__DIR__, 3) . '/partials/admin/hastalik_list_filters.php';
include dirname(__DIR__, 3) . '/partials/admin/list_page_open.php';
?>
