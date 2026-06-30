<?php
$isCatalogPickerMode = $isCatalogPickerMode ?? false;
$admin_list_title = $isCatalogPickerMode ? 'Kurum İşlem Seçimi' : 'Tıbbi İşlem Tanımları (Platform Kataloğu)';
$admin_list_icon = 'fa-solid fa-hand-holding-medical';
ob_start();
if (!$isCatalogPickerMode): ?>
<a href="<?= htmlspecialchars(esh_url('Islem', 'create'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm">
    <i class="fas fa-plus me-1"></i>Yeni İşlem Ekle
</a>
<?php endif;
$admin_list_actions = ob_get_clean();
include dirname(__DIR__, 3) . '/partials/admin/list_page_open.php';
?>