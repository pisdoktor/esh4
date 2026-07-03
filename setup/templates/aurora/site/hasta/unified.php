<?php
/*
 * Aurora Light teması — site/hasta/unified
 * Canonical: views/site/hasta/unified.php
 */
$statusLabels = [
    'all' => 'Tüm durumlar',
    'active' => 'Aktif',
    'passive' => 'Pasif (dosya kapalı)',
    'waiting' => 'Bekleyen (ilk kayıt)',
    'deleted' => 'Silinen (manuel)',
    'araf' => 'Araf',
    'probable' => 'Muhtemel ölen',
];
$isAdminUnified = !empty($_SESSION['isadmin']);
if (!$isAdminUnified) {
    $statusLabels = [
        'active' => 'Aktif',
        'passive' => 'Pasif (dosya kapalı)',
        'waiting' => 'Bekleyen (ilk kayıt)',
    ];
}
$curStatus = $status ?? 'active';
$curFeature = isset($feature) ? (string) $feature : '';
$unifiedPasifTarihiInsteadOfRandevu = in_array($curStatus, ['passive', 'probable', 'araf', 'deleted'], true);
$unifiedPasifTarihiColumnTitle = match ($curStatus) {
    'deleted' => 'Silinme tarihi',
    'passive' => 'Pasif tarihi',
    default => 'Ölüm tarihi',
};
?>
<link rel="stylesheet" href="<?= htmlspecialchars(ASSETS_URL . '/pages/css/patient-unified.css', ENT_QUOTES, 'UTF-8') ?>">
<div class="container-fluid py-4 esh-page-patient-unified<?= $curStatus === 'waiting' ? ' esh-unified-status-waiting' : '' ?>">
    <section class="au-panel mb-3">
        <div class="au-panel__head au-panel__head--split flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3 min-w-0">
                <span class="au-icon-chip au-icon-chip--grad"><i class="fa-solid fa-users"></i></span>
                <div class="min-w-0">
                    <h1 class="au-panel__title mb-0"><?= htmlspecialchars($pageTitle ?? 'Hasta Listesi') ?></h1>
                    <p class="au-panel__sub mb-0">Durum seçin; pasif için neden ve tarih filtreleri kullanılabilir.</p>
                </div>
            </div>
            <div class="flex-grow-1" style="min-width:280px;">
                <form action="<?= htmlspecialchars(esh_form_action('Patient', 'unified'), ENT_QUOTES, 'UTF-8') ?>" method="get" class="row g-2 g-xl-3 align-items-end esh-unified-filter">
                <?= esh_form_route_hiddens('Patient', 'unified') ?>
                    <div class="col-12 col-md-6 col-xl-<?= $isAdminUnified ? '2' : '3' ?>">
                        <label class="form-label small text-muted mb-1">Kayıt durumu</label>
                        <select name="status" class="form-select form-select-sm shadow-sm esh-filter-control" onchange="this.form.submit()">
                            <?php foreach ($statusLabels as $k => $label): ?>
                                <option value="<?= htmlspecialchars($k) ?>" <?= ($curStatus === $k) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php include \App\Helpers\ThemeViewHelper::resolveAreaView('site', 'hasta/partials/unified_filter_feature'); ?>

                    <div class="col-12 col-md-6 col-xl-<?= $isAdminUnified ? '3' : '4' ?>">
                        <label class="form-label small text-muted mb-1">Arama</label>
                        <input type="text" name="search" class="form-control form-control-sm shadow-sm esh-filter-control"
                               placeholder="İsim, soyisim veya TC..."
                               value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>

                    <?php if (!empty($showPassiveFilters)): ?>
                    <div class="col-12 col-md-4 col-xl-2">
                        <label class="form-label small text-muted mb-1">Pasif nedeni</label>
                        <select name="reason" class="form-select form-select-sm shadow-sm esh-filter-control">
                            <option value="">Tümü</option>
                            <?php foreach ($pasifListesi as $k => $neden): ?>
                                <option value="<?= $k ?>" <?= (($reason ?? '') == $k) ? 'selected' : '' ?>><?= htmlspecialchars($neden) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-8 col-xl-3">
                        <label class="form-label small text-muted mb-1">Pasif tarihi</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="startDate" class="form-control form-control-sm datepicker esh-filter-control" placeholder="Başlangıç" value="<?= htmlspecialchars($startDate ?? '') ?>" autocomplete="off">
                            <span class="input-group-text">-</span>
                            <input type="text" name="endDate" class="form-control form-control-sm datepicker esh-filter-control" placeholder="Bitiş" value="<?= htmlspecialchars($endDate ?? '') ?>" autocomplete="off">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="col-12 col-md-auto ms-md-auto d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm shadow-sm px-3 rounded-pill esh-filter-control">
                            <i class="fa-solid fa-filter me-1"></i>Uygula
                        </button>
                        <a href="<?= htmlspecialchars(esh_url('Patient', 'unified', ['status' => $curStatus]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-outline-secondary btn-sm rounded-pill esh-filter-control">Sıfırla</a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="small text-muted">
            Seçili: <strong><?= htmlspecialchars($statusLabels[$curStatus] ?? $curStatus) ?></strong>
            <?php if ($isAdminUnified && $curFeature !== '') : ?>
                <span class="text-muted">·</span> <strong><?= htmlspecialchars(\App\Helpers\BadgeHelper::patientFeatureFilterLabel($curFeature), ENT_QUOTES, 'UTF-8') ?></strong>
            <?php endif; ?>
            — Toplam <strong><?= (int)($totalPatients ?? 0) ?></strong> kayıt
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php include ROOT_PATH . '/views/site/hasta/partials/unified_pdf_button.php'; ?>
            <a href="<?= htmlspecialchars(esh_url('Patient', 'ilkkayit'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm shadow-sm rounded-pill">
                <i class="fa-solid fa-user-plus me-1"></i>Yeni İlk Kayıt
            </a>
        </div>
    </div>

    <?php if ($curStatus === 'waiting'): ?>
    <p class="small text-muted mb-2 esh-waiting-kayit-legend">
        <span class="fw-semibold me-1">Kayıt bekleme süresi (satır rengi):</span>
        <span><span class="esh-waiting-kayit-legend__swatch esh-waiting-kayit-legend__swatch--1ay" aria-hidden="true"></span> ≥ 1 ay</span>
        <span><span class="esh-waiting-kayit-legend__swatch esh-waiting-kayit-legend__swatch--3ay" aria-hidden="true"></span> ≥ 3 ay</span>
        <span><span class="esh-waiting-kayit-legend__swatch esh-waiting-kayit-legend__swatch--6ay" aria-hidden="true"></span> ≥ 6 ay</span>
        <span><span class="esh-waiting-kayit-legend__swatch esh-waiting-kayit-legend__swatch--12ay" aria-hidden="true"></span> ≥ 1 yıl</span>
    </p>
    <?php endif; ?>

    <div class="au-panel">
        <div class="au-panel__body p-0">
            <div class="table-responsive" style="overflow: visible !important;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <?php include ROOT_PATH . '/views/site/hasta/partials/unified_table_head.php'; ?>
                    </thead>
                    <tbody id="esh-unified-list-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($unifiedRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           data-esh-pdf-url="<?= htmlspecialchars($unifiedPdfDataUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-unified-list-loading-row">
                            <td colspan="11" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>Hasta listesi yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="au-panel__foot">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="small text-muted">
                        <?= \App\Helpers\PaginationHelper::infoText($totalPatients, $page, $limit) ?>
                    </div>
                    <div>
                        <?= \App\Helpers\PaginationHelper::limitSelector($limit, $pagelink) ?>
                    </div>
                </div>
                <div>
                    <?= \App\Helpers\PaginationHelper::render($totalPatients, $page, $limit, $pagelink) ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include ROOT_PATH . '/views/site/hasta/partials/unified_pdf_assets.php'; ?>
