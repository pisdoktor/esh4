<?php
$isCatalogPickerMode = $isCatalogPickerMode ?? false;
$admin_list_title = $isCatalogPickerMode ? 'Kurum EK-3 Başvuru Amaçları Seçimi' : 'EK-3 başvuru amaçları (Platform Kataloğu)';
$admin_list_icon = 'fa-solid fa-list-check';
$admin_list_subtitle = $isCatalogPickerMode
    ? 'Konsültasyon formlarında kullanılacak başvuru amaçlarını seçin.'
    : 'Konsültasyon EK-3 formunda işaretlenen seçenekler (esh_istekler).';
ob_start();
if (!$isCatalogPickerMode): ?>
<a href="<?= htmlspecialchars(esh_url('Istek', 'create'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm">
    <i class="fas fa-plus me-1"></i>Yeni başvuru amacı
</a>
<?php endif;
$admin_list_actions = ob_get_clean();
include dirname(__DIR__, 3) . '/partials/admin/list_page_open.php';
?>