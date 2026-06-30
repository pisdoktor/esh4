<?php
$admin_list_title = 'Araç tanımları';
$admin_list_icon = 'fa-solid fa-car-side';
ob_start();
?>
<a href="<?= htmlspecialchars(esh_url('Arac', 'create'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm">
    <i class="fas fa-plus me-1"></i>Yeni araç
</a>
<?php
$admin_list_actions = ob_get_clean();
include dirname(__DIR__, 3) . '/partials/admin/list_page_open.php';
?>