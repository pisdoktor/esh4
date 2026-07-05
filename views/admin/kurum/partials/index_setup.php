<?php
$admin_list_title = 'Kurum Yönetimi';
$admin_list_icon = 'fa-solid fa-building';
ob_start();
?>
<a href="<?= htmlspecialchars(esh_url('Kurum', 'create'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm">
    <i class="fas fa-plus me-1"></i>Yeni Kurum
</a>
<?php
$admin_list_actions = ob_get_clean();
include dirname(__DIR__, 3) . '/partials/admin/list_page_open.php';
?>

                    <p class="small text-muted mb-3">
                        Çoklu kurum yapılandırması. Her kurumun kendi hastaları, personeli ve referans verileri vardır.
                        <?= htmlspecialchars(\App\Helpers\AuthHelper::adminLevelLabel(\App\Helpers\AuthHelper::ROLE_SUPERADMIN), ENT_QUOTES, 'UTF-8') ?> tüm kurumları görür; liste filtrelemek için üst menüdeki kurum seçicisini kullanın.
                    </p>
