<div class="esh-page esh-page--list esh-page-izlem container-fluid py-4">
<?php
use App\Helpers\FormHelper;
use App\Helpers\CinsiyetHelper;

$outer = $izlemHistoryOuterClass ?? 'container-fluid py-4';
$card = $izlemHistoryCardClass ?? 'card border-0 esh-list-table-card';
$articleClass = $izlemHistoryArticleClass ?? '';
$hist_h4_extra = trim((string) ($izlemHistoryH4Class ?? ''));
$tc = (string) ($tc ?? '');
$tcQ = rawurlencode($tc);
$historyFiltersOpen = !empty($historyFiltersOpen);
$patientIdForHeader = (string) ($patientIdForHeader ?? '');
$historyPatient = $historyPatient ?? null;
$patientLabel = '';
if ($historyPatient) {
    $patientLabel = trim((string) ($historyPatient->isim ?? '') . ' ' . (string) ($historyPatient->soyisim ?? ''));
}
if ($patientLabel === '' && !empty($visits[0])) {
    $patientLabel = trim((string) ($visits[0]->isim ?? '') . ' ' . (string) ($visits[0]->soyisim ?? ''));
}
$histErkek = $historyPatient
    ? CinsiyetHelper::isErkek($historyPatient->cinsiyet ?? null)
    : (!empty($visits[0]) && CinsiyetHelper::isErkek($visits[0]->cinsiyet ?? null));
$histNameColor = CinsiyetHelper::nameColor($historyPatient->cinsiyet ?? ($visits[0]->cinsiyet ?? null));
$histAktif = $historyPatient ? \App\Models\Patient::isAktif($historyPatient->pasif ?? null) : true;
$historyPagelinkParams = ['tc' => $tc];
if ($status !== '') {
    $historyPagelinkParams['status'] = (string) $status;
}
$historyPagelink = esh_url('Visit', 'history', $historyPagelinkParams);
?>
<?php if ($articleClass !== ''): ?>
<article class="<?= htmlspecialchars($articleClass, ENT_QUOTES, 'UTF-8') ?>" lang="tr">
<header class="visually-hidden"><h1>İzlem geçmişi</h1></header>
<?php endif; ?>
<div class="<?= htmlspecialchars((string) $outer, ENT_QUOTES, 'UTF-8') ?> izlem-history-page">
    <div class="mb-3 esh-patient-page-header">
        <h4 class="fw-bold text-dark mb-2<?= $hist_h4_extra !== '' ? ' ' . htmlspecialchars($hist_h4_extra, ENT_QUOTES, 'UTF-8') : '' ?>">
            <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>İzlem geçmişi
        </h4>
        <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 gap-md-3 esh-patient-page-header__meta">
            <?php if ($patientLabel !== ''): ?>
                <div class="dropdown esh-history-patient-dropdown">
                    <a href="#"
                       class="patient-link esh-patient-page-header__name dropdown-toggle d-inline-block text-decoration-none fw-bold"
                       role="button"
                       data-bs-toggle="dropdown"
                       data-bs-display="static"
                       aria-expanded="false"
                       style="color:<?= htmlspecialchars($histNameColor, ENT_QUOTES, 'UTF-8') ?>;">
                        <?= htmlspecialchars($patientLabel, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                    <?php
                    $izlemPatientHeaderMenuActive = 'history';
                    include ROOT_PATH . '/views/site/partials/izlem_patient_header_dropdown.php';
                    ?>
                </div>
            <?php endif; ?>
            <?php if ($tc !== ''): ?>
                <code class="text-dark small"><?= htmlspecialchars(\App\Helpers\ValidationHelper::formatTc($tc), ENT_QUOTES, 'UTF-8') ?></code>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 esh-list-filter-card mb-4">
        <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0 esh-history-filter-icon">
                    <i class="fa-solid fa-sliders"></i>
                </span>
                <div class="min-w-0">
                    <span class="fw-semibold text-dark d-block">Liste filtreleri</span>
                    <span class="small text-muted">Duruma göre izlem kayıtlarını süzün.</span>
                </div>
            </div>
            <button id="history-filter-toggle"
                    class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#history-filter-collapse"
                    aria-expanded="<?= $historyFiltersOpen ? 'true' : 'false' ?>"
                    aria-controls="history-filter-collapse">
                <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text"><?= $historyFiltersOpen ? 'Filtreleri Gizle' : 'Filtreleri Göster' ?></span>
            </button>
        </div>
        <div id="history-filter-collapse" class="collapse<?= $historyFiltersOpen ? ' show' : '' ?>">
            <div class="card-body p-3 p-md-4 bg-body-tertiary bg-opacity-25">
                <form method="get" action="<?= htmlspecialchars(esh_form_action('Visit', 'history'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end esh-history-filter">
                <?= esh_form_route_hiddens('Visit', 'history') ?>
                    <input type="hidden" name="tc" value="<?= htmlspecialchars($tc, ENT_QUOTES, 'UTF-8') ?>">
                    <?php
                    echo FormHelper::fieldSelect('status', 'Durum', [
                        FormHelper::makeOption('', 'Tümü'),
                        FormHelper::makeOption('1', 'Yapıldı'),
                        FormHelper::makeOption('0', 'Yapılmadı'),
                    ], (string) ($status ?? ''), [
                        'col' => 'col-12 col-sm-6 col-md-4 col-lg-3',
                        'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                        'class' => 'form-select-sm esh-filter-control',
                        'tomSelect' => false,
                    ]);
                    ?>
                    <div class="col-12 col-sm-auto d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 esh-filter-control">
                            <i class="fa-solid fa-filter me-1"></i>Filtrele
                        </button>
                        <a href="<?= htmlspecialchars(esh_url('Visit', 'history', ['tc' => $tcQ]), ENT_QUOTES, "UTF-8") ?>"
                           class="btn btn-outline-secondary btn-sm rounded-pill px-4 esh-filter-control">Sıfırla</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="<?= htmlspecialchars((string) $card, ENT_QUOTES, 'UTF-8') ?>">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <?php include __DIR__ . '/partials/history_table_head.php'; ?>
                    </thead>
                    <tbody id="esh-visit-history-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($historyRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-visit-history-loading-row">
                            <td colspan="6" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>İzlem geçmişi yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white border-top py-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="small text-muted">
                            <?= \App\Helpers\PaginationHelper::infoText((int) ($total ?? 0), (int) ($page ?? 1), (int) ($limit ?? 20)) ?>
                        </div>
                        <div>
                            <?= \App\Helpers\PaginationHelper::limitSelector((int) ($limit ?? 20), $historyPagelink) ?>
                        </div>
                    </div>
                    <div>
                        <?= \App\Helpers\PaginationHelper::render((int) ($total ?? 0), (int) ($page ?? 1), (int) ($limit ?? 20), $historyPagelink) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$ek3OpenVisitId = (int) ($ek3OpenVisitId ?? 0);
include __DIR__ . '/partials/ek3_open_modal.php';
?>
<?= esh_csp_script_src_tag(ASSETS_URL . '/pages/js/visit-history.js') ?>
<?php if ($articleClass !== ''): ?>
</article>
<?php endif; ?>
</div>