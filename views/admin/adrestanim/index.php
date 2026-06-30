<?php
$eshAdrestanimCanManageIlce = $eshAdrestanimCanManageIlce ?? \App\Helpers\AuthHelper::sessionIsSuperAdmin();
$admin_list_title = 'Hiyerarşik Adres Yönetimi';
$admin_list_icon = 'fa-solid fa-map-marked-alt';
$admin_list_container_id = 'adres-hierarchy';
$admin_list_container_extra_classes = 'mt-3 py-4';
$admin_list_header_aside = $eshAdrestanimCanManageIlce
    ? 'Eklemek istediğiniz seviyeye kadar soldan sağa seçim yapın; ilgili sütunun '
        . '<i class="fa-solid fa-plus text-success"></i> düğmesiyle yeni kayıt ekleyin veya satırdaki kalem / çöp kutusu ile düzenleyin / silin.'
    : 'Soldan sağa ilçe seçip alt seviyelerde (mahalle, sokak, kapı) ekleme, düzenleme ve silme yapabilirsiniz.';
$admin_list_body_class = 'bg-light py-3';
include dirname(__DIR__, 2) . '/partials/admin/list_page_open.php';
?>

<?php include __DIR__ . '/partials/hierarchy_columns.php'; ?>

<?php include dirname(__DIR__, 2) . '/partials/admin/list_page_close.php'; ?>

<?php include __DIR__ . '/partials/editor_modal.php'; ?>
