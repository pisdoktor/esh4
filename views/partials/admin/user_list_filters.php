<?php
declare(strict_types=1);
/**
 * Admin kullanıcı listesi — GET süzgeçleri (collapse kart).
 * Controller: $eshUserListActivated, $eshUserListRole, $eshUserListUnvan, $filterExpanded
 */
use App\Helpers\FormHelper;

$a = isset($eshUserListActivated) ? (string) $eshUserListActivated : '';
if ($a !== '1' && $a !== '0') {
    $a = '';
}
$r = isset($eshUserListRole) ? (string) $eshUserListRole : '';
if ($r !== 'admin' && $r !== 'staff' && $r !== 'superadmin') {
    $r = '';
}
$u = isset($eshUserListUnvan) ? (string) $eshUserListUnvan : '';
if ($u === '__none') {
    // bırak
} elseif ($u !== '' && \App\Models\User::normalizeUnvan($u) === null) {
    $u = '';
}
$filterExpanded = !empty($filterExpanded);
?>
<div class="container-fluid py-4 admin-list-page esh-page-user-list">
    <div class="card border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
        <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="rounded-circle bg-info-subtle text-info d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                    <i class="fa-solid fa-sliders"></i>
                </span>
                <div class="min-w-0">
                    <span class="fw-semibold text-dark d-block">Liste filtreleri</span>
                    <span class="small text-muted">Hesap durumu, yetki ve ünvan seçip «Filtrele» ile uygulayın.</span>
                </div>
            </div>
            <button
                id="user-list-filter-toggle"
                class="btn btn-outline-secondary btn-sm rounded-pill px-3<?= $filterExpanded ? '' : ' collapsed' ?>"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#user-list-filter-collapse"
                aria-expanded="<?= $filterExpanded ? 'true' : 'false' ?>"
                aria-controls="user-list-filter-collapse"
            >
                <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text"><?= $filterExpanded ? 'Filtreleri Gizle' : 'Filtreleri Göster' ?></span>
            </button>
        </div>
        <div id="user-list-filter-collapse" class="collapse<?= $filterExpanded ? ' show' : '' ?>">
            <div class="card-body p-3 p-md-4 bg-body-tertiary bg-opacity-25">
                <form method="get" action="<?= htmlspecialchars(esh_form_action('User', 'list'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 g-xl-4 align-items-end esh-user-filter">
                <?= esh_form_route_hiddens('User', 'list') ?>

                    <?php
                    echo FormHelper::fieldSelect('activated', 'Hesap durumu', [
                        FormHelper::makeOption('', 'Tümü'),
                        FormHelper::makeOption('1', 'Aktif'),
                        FormHelper::makeOption('0', 'Pasif'),
                    ], $a, [
                        'col' => 'col-12 col-sm-6 col-lg-4 col-xl-2',
                        'id' => 'esh-user-filter-activated',
                        'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                        'class' => 'form-select-sm shadow-sm esh-filter-control',
                        'tomSelect' => false,
                    ]);
                    $eshUserRoleOptions = [
                        FormHelper::makeOption('', 'Tümü'),
                        FormHelper::makeOption('admin', 'Yönetici (admin+)'),
                        FormHelper::makeOption('staff', 'Personel'),
                    ];
                    if (\App\Helpers\AuthHelper::sessionIsSuperAdmin()) {
                        $eshUserRoleOptions[] = FormHelper::makeOption('superadmin', 'Süper yönetici');
                    }
                    echo FormHelper::fieldSelect('role', 'Yetki', $eshUserRoleOptions, $r, [
                        'col' => 'col-12 col-sm-6 col-lg-4 col-xl-2',
                        'id' => 'esh-user-filter-role',
                        'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                        'class' => 'form-select-sm shadow-sm esh-filter-control',
                        'tomSelect' => false,
                    ]);
                    ?>
                    <?php include ROOT_PATH . '/views/partials/admin/user_list_kurum_filter.php'; ?>
                    <?php
                    $eshUserUnvanOptions = [
                        FormHelper::makeOption('', 'Tümü'),
                        FormHelper::makeOption('__none', 'Ünvan atanmamış'),
                    ];
                    foreach (\App\Models\User::unvanChoices() as $code => $label) {
                        if ($code === '') {
                            continue;
                        }
                        $eshUserUnvanOptions[] = FormHelper::makeOption((string) $code, (string) $label);
                    }
                    echo FormHelper::fieldSelect('unvan', 'Ünvan', $eshUserUnvanOptions, $u, [
                        'col' => 'col-12 col-sm-6 col-lg-4 col-xl-3',
                        'id' => 'esh-user-filter-unvan',
                        'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                        'class' => 'form-select-sm shadow-sm esh-filter-control',
                        'tomSelect' => false,
                    ]);
                    ?>
                    <div class="col-12 col-lg-12 col-xl-5 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary btn-sm shadow-sm px-4 rounded-pill esh-filter-control"><i class="fa-solid fa-filter me-1"></i>Filtrele</button>
                        <a href="<?= htmlspecialchars(esh_url('User', 'list'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 esh-filter-control">Sıfırla</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
