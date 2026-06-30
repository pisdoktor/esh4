<?php
$admin_list_title = 'Sağlık Güvencesi Tanımları';
$admin_list_icon = 'fa-solid fa-shield-halved';
ob_start();
?>
<a href="<?= htmlspecialchars(esh_url('Guvence', 'create'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm">
    <i class="fas fa-plus me-1"></i>Yeni Güvence Ekle
</a>
<?php
$admin_list_actions = ob_get_clean();
include dirname(__DIR__, 3) . '/partials/admin/list_page_open.php';
?>