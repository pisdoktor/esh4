<?php
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
$unifiedPasifTarihiInsteadOfRandevu = in_array($curStatus, ['passive', 'probable', 'araf', 'deleted'], true);
$unifiedPasifTarihiColumnTitle = match ($curStatus) {
    'deleted' => 'Silinme tarihi',
    'passive' => 'Pasif tarihi',
    default => 'Ölüm tarihi',
};
?>
<link rel="stylesheet" href="<?= htmlspecialchars(ASSETS_URL . '/pages/css/patient-unified.css', ENT_QUOTES, 'UTF-8') ?>">
<div class="fluent-page fluent-hasta fluent-hasta-unified container-fluid py-4 esh-page-patient-unified<?= $curStatus === 'waiting' ? ' esh-unified-status-waiting' : '' ?>">
    <div class="row align-items-start mb-3">
        <div class="col-lg-3 mb-2">
            <h4 class="fluent-page-title fw-bold text-dark mb-0">
                <i class="fa-solid fa-users text-primary me-2"></i><?= htmlspecialchars($pageTitle ?? 'Hasta Listesi') ?>
            </h4>
            <small class="text-muted fluent-subtitle">Birleşik liste • durum seçin; pasifte neden ve tarih filtreleri kullanılabilir.</small>
        </div>
        <div class="col-lg-9">
            <form action="<?= htmlspecialchars(esh_form_action('Patient', 'unified'), ENT_QUOTES, 'UTF-8') ?>" method="get" class="row g-2 g-xl-3 align-items-end p-3 bg-white border rounded-3 shadow-sm esh-unified-filter fluent-hasta-filter-panel fluent-layer-card">
                <?= esh_form_route_hiddens('Patient', 'unified') ?>
                <div class="col-12 col-md-4 col-xl-3">
                    <label class="form-label small text-muted mb-1">Kayıt durumu</label>
                    <select name="status" class="form-select form-select-sm shadow-sm esh-filter-control" onchange="this.form.submit()">
                        <?php foreach ($statusLabels as $k => $label): ?>
                            <option value="<?= htmlspecialchars($k) ?>" <?= ($curStatus === $k) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-8 col-xl-4">
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

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="small text-muted">
            Seçili: <strong><?= htmlspecialchars($statusLabels[$curStatus] ?? $curStatus) ?></strong>
            — Toplam <strong><?= (int)($totalPatients ?? 0) ?></strong> kayıt
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php include ROOT_PATH . '/views/site/hasta/partials/unified_pdf_button.php'; ?>
            <a href="<?= htmlspecialchars(esh_url('Patient', 'ilkkayit'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm shadow-sm rounded-pill">
                <i class="fa-solid fa-user-plus me-1"></i>Yeni İlk Kayıt
            </a>
        </div>
    </div>

    <div class="card fluent-layer-card fluent-hover-tilt border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
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

        <div class="card-footer bg-white border-top-0 py-2">
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
