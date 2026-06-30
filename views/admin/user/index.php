<?php
$admin_list_title = isset($pageTitle) ? (string) $pageTitle : 'Kullanıcı Yönetimi';
$admin_list_icon = 'fa-solid fa-users-gear';
ob_start();
?>
<?php if (\App\Helpers\AuthHelper::sessionIsSuperAdmin()): ?>
<a href="<?= htmlspecialchars(esh_url('User', 'create'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm shadow-sm">
    <i class="fa-solid fa-plus me-1"></i> Yeni Personel Ekle
</a>
<?php endif; ?>
<?php
$admin_list_actions = ob_get_clean();
$admin_list_body_class = 'p-0';
$admin_list_container_extra_classes = 'pt-0 pb-4';
include dirname(__DIR__, 2) . '/partials/admin/user_list_filters.php';
include dirname(__DIR__, 2) . '/partials/admin/list_page_open.php';
?>
<?php include __DIR__ . '/partials/index_table.php'; ?>
<?php include dirname(__DIR__, 2) . '/partials/admin/list_page_close.php'; ?>
