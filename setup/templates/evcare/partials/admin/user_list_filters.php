<?php
declare(strict_types=1);
/**
 * Admin kullanıcı listesi — GET süzgeçleri (collapse kart).
 * Controller: $eshUserListActivated, $eshUserListRole, $eshUserListUnvan, $filterExpanded
 */
$a = isset($eshUserListActivated) ? (string) $eshUserListActivated : '';
if ($a !== '1' && $a !== '0') {
    $a = '';
}
$r = isset($eshUserListRole) ? (string) $eshUserListRole : '';
if ($r !== 'admin' && $r !== 'staff') {
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
<link rel="stylesheet" href="<?= htmlspecialchars(ASSETS_URL . '/pages/css/user-list.css', ENT_QUOTES, 'UTF-8') ?>">
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

                    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                        <label for="esh-user-filter-activated" class="form-label fw-semibold small text-secondary mb-1">Hesap durumu</label>
                        <select name="activated" id="esh-user-filter-activated" class="form-select form-select-sm shadow-sm esh-filter-control">
                            <option value=""<?= $a === '' ? ' selected' : '' ?>>Tümü</option>
                            <option value="1"<?= $a === '1' ? ' selected' : '' ?>>Aktif</option>
                            <option value="0"<?= $a === '0' ? ' selected' : '' ?>>Pasif</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                        <label for="esh-user-filter-role" class="form-label fw-semibold small text-secondary mb-1">Yetki</label>
                        <select name="role" id="esh-user-filter-role" class="form-select form-select-sm shadow-sm esh-filter-control">
                            <option value=""<?= $r === '' ? ' selected' : '' ?>>Tümü</option>
                            <option value="admin"<?= $r === 'admin' ? ' selected' : '' ?>>Yönetici</option>
                            <option value="staff"<?= $r === 'staff' ? ' selected' : '' ?>>Personel</option>
                        </select>
                    </div>
                    <?php include ROOT_PATH . '/views/partials/admin/user_list_kurum_filter.php'; ?>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <label for="esh-user-filter-unvan" class="form-label fw-semibold small text-secondary mb-1">Ünvan</label>
                        <select name="unvan" id="esh-user-filter-unvan" class="form-select form-select-sm shadow-sm esh-filter-control">
                            <option value=""<?= $u === '' ? ' selected' : '' ?>>Tümü</option>
                            <option value="__none"<?= $u === '__none' ? ' selected' : '' ?>>Ünvan atanmamış</option>
                            <?php foreach (\App\Models\User::unvanChoices() as $code => $label): ?>
                                <?php if ($code === '') {
                                    continue;
                                } ?>
                                <option value="<?= htmlspecialchars((string) $code, ENT_QUOTES, 'UTF-8') ?>"<?= $u === (string) $code ? ' selected' : '' ?>><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-lg-12 col-xl-5 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary btn-sm shadow-sm px-4 rounded-pill esh-filter-control"><i class="fa-solid fa-filter me-1"></i>Filtrele</button>
                        <a href="<?= htmlspecialchars(esh_url('User', 'list'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 esh-filter-control">Sıfırla</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
