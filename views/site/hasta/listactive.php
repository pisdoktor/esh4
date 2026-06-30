<?php
$site_list_module = 'hasta';
$site_list_title = (string) ($pageTitle ?? 'Aktif Hastalar');
$site_list_subtitle = 'Aktif kayıtlar arasında arama yapın';
$site_list_icon = 'fa-solid fa-user-check text-success';
$site_list_body_class = 'p-0';

ob_start();
?>
<form action="<?= htmlspecialchars(esh_form_action('Patient', 'listactive'), ENT_QUOTES, 'UTF-8') ?>" method="GET" class="esh-page__search-bar w-100">
    <?= esh_form_route_hiddens('Patient', 'listactive') ?>
    <?= \App\Helpers\FormHelper::fieldInput('search', '', (string) ($search ?? ''), [
        'col' => '',
        'noLabel' => true,
        'class' => 'border-0 px-3 shadow-none',
        'placeholder' => 'İsim, soyisim veya TC ile ara...',
    ]) ?>
    <button type="submit" class="btn btn-primary px-4">
        <i class="fa-solid fa-magnifying-glass"></i>
    </button>
</form>
<?php
$site_list_search_form = ob_get_clean();

ob_start();
?>
<a href="<?= htmlspecialchars(esh_url('Patient', 'ilkkayit'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary shadow-sm rounded-pill px-4 esh-patient-list-actions">
    <i class="fa-solid fa-plus me-2"></i>Yeni İlk Kayıt
</a>
<?php
$site_list_actions = ob_get_clean();

include dirname(__DIR__, 2) . '/partials/site/list_page_open.php';
?>

    <?php if (!empty($search)): ?>
        <div class="alert alert-info py-2 d-flex justify-content-between align-items-center esh-patient-list-alert mx-3 mt-3 mb-0">
            <span><strong>"<?= htmlspecialchars($search) ?>"</strong> araması için <strong><?= $totalPatients ?></strong> sonuç bulundu.</span>
            <a href="<?= htmlspecialchars(esh_url('Patient', 'listactive'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-info rounded-pill">Aramayı Temizle</a>
        </div>
    <?php endif; ?>

    <div class="esh-page__table-wrap esh-page__table-wrap--overflow-visible esh-patient-list-table">
        <table class="table table-hover align-middle mb-0 esh-ui-table">
            <thead class="esh-page__table-head">
                <?php include __DIR__ . '/partials/list_active_table_head.php'; ?>
            </thead>
            <tbody>
                <?php foreach ($patients as $patient): ?>
                    <?php include __DIR__ . '/partials/list_table_row_active.php'; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php include __DIR__ . '/partials/list_pagination_footer.php'; ?>

<?php include dirname(__DIR__, 2) . '/partials/site/list_page_close.php'; ?>
