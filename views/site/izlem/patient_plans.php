<div class="esh-page esh-page--list esh-page-izlem container-fluid py-4">
<?php
/** @var object $patient */
/** @var string $tc */
use App\Helpers\FormHelper;
use App\Helpers\CinsiyetHelper;

/** @var string $durum */
/** @var int $limit */
/** @var int $page */
/** @var string $ordering */
/** @var string $patientPlansQuery sayfalama (ordering dahil) */
/** @var string $patientPlansQueryBase sıralama linkleri için (ordering hariç) */
/** @var array $plans */
/** @var int $totalItems */
/** @var int $totalPages */

$oncelikConfig = [
    '1' => ['text' => 'Normal', 'class' => 'success'],
    '2' => ['text' => 'Orta', 'class' => 'warning'],
    '3' => ['text' => 'Yüksek', 'class' => 'danger'],
    '0' => ['text' => '—', 'class' => 'secondary'],
];
$ordering = $ordering ?? 'p.planlanantarih-ASC';
$listBase = \App\Helpers\UrlHelper::fromQueryString((string)($patientPlansQueryBase ?? ''));

$eshPatientPlanSortBase = [];
parse_str((string) ($patientPlansQueryBase ?? ''), $eshPatientPlanSortBase);
$eshPatientPlanSortCfg = ['mode' => 'ordering', 'base' => $eshPatientPlanSortBase];

$pp_outer = $patientPlansOuterClass ?? 'container-fluid py-4 plan-patient-page';
$pp_h4_extra = trim((string) ($patientPlansH4Class ?? ''));
$pp_filter_card = $patientPlansFilterCardClass ?? 'card border-0 esh-list-filter-card mb-4';
$pp_table_card = $patientPlansTableCardClass ?? 'card border-0 esh-list-table-card';
$articleClass = $patientPlansArticleClass ?? '';
$tc = (string) ($tc ?? '');
$tcQ = rawurlencode($tc);
$patientPlansFiltersOpen = !empty($patientPlansFiltersOpen);
$patientIdForHeader = (string) ($patient->id ?? '');
$patientLabel = trim((string) ($patient->isim ?? '') . ' ' . (string) ($patient->soyisim ?? ''));
$histErkek = CinsiyetHelper::isErkek($patient->cinsiyet ?? null);
$histNameColor = CinsiyetHelper::nameColor($patient->cinsiyet ?? null);
$histAktif = \App\Models\Patient::isAktif($patient->pasif ?? null);
$ppPagelink = \App\Helpers\UrlHelper::fromQueryString((string)($patientPlansQueryBase ?? '')) . '&ordering=' . rawurlencode($ordering);
?>
<?php if ($articleClass !== ''): ?>
<article class="<?= htmlspecialchars($articleClass, ENT_QUOTES, 'UTF-8') ?>" lang="tr">
<header class="visually-hidden"><h1>Hastanın planlı izlemleri</h1></header>
<?php endif; ?>
<div class="<?= htmlspecialchars((string) $pp_outer, ENT_QUOTES, 'UTF-8') ?> izlem-history-page">
    <div class="mb-3 esh-patient-page-header">
        <h4 class="fw-bold text-dark mb-2<?= $pp_h4_extra !== '' ? ' ' . htmlspecialchars($pp_h4_extra, ENT_QUOTES, 'UTF-8') : '' ?>">
            <i class="fa-solid fa-calendar-week text-primary me-2"></i>Hastanın planlı izlemleri
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
                    $izlemPatientHeaderMenuActive = 'plans';
                    include ROOT_PATH . '/views/site/partials/izlem_patient_header_dropdown.php';
                    ?>
                </div>
            <?php endif; ?>
            <?php if ($tc !== ''): ?>
                <code class="text-dark small"><?= htmlspecialchars(\App\Helpers\ValidationHelper::formatTc($tc), ENT_QUOTES, 'UTF-8') ?></code>
            <?php endif; ?>
        </div>
    </div>

    <div class="<?= htmlspecialchars((string) $pp_filter_card, ENT_QUOTES, 'UTF-8') ?> overflow-hidden">
        <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0 esh-history-filter-icon">
                    <i class="fa-solid fa-sliders"></i>
                </span>
                <div class="min-w-0">
                    <span class="fw-semibold text-dark d-block">Liste filtreleri</span>
                    <span class="small text-muted">Durum ve sayfa başına kayıt sayısına göre süzün.</span>
                </div>
            </div>
            <button id="patient-plans-filter-toggle"
                    class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#patient-plans-filter-collapse"
                    aria-expanded="<?= $patientPlansFiltersOpen ? 'true' : 'false' ?>"
                    aria-controls="patient-plans-filter-collapse">
                <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text"><?= $patientPlansFiltersOpen ? 'Filtreleri Gizle' : 'Filtreleri Göster' ?></span>
            </button>
        </div>
        <div id="patient-plans-filter-collapse" class="collapse<?= $patientPlansFiltersOpen ? ' show' : '' ?>">
            <div class="card-body p-3 p-md-4 bg-body-tertiary bg-opacity-25">
                <form action="<?= htmlspecialchars(esh_form_action('PlannedVisit', 'patient'), ENT_QUOTES, 'UTF-8') ?>" method="get" class="row g-3 align-items-end esh-history-filter">
                <?= esh_form_route_hiddens('PlannedVisit', 'patient') ?>
                    <input type="hidden" name="tc" value="<?= htmlspecialchars($tc, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="ordering" value="<?= htmlspecialchars($ordering, ENT_QUOTES, 'UTF-8') ?>">
                    <?php
                    echo FormHelper::fieldSelect('durum', 'Durum', [
                        FormHelper::makeOption('', 'Tümü'),
                        FormHelper::makeOption('0', 'Bekleyen'),
                        FormHelper::makeOption('1', 'Yapıldı'),
                    ], (string) ($durum ?? ''), [
                        'col' => 'col-12 col-sm-6 col-md-4 col-lg-3',
                        'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                        'class' => 'form-select-sm esh-filter-control',
                        'tomSelect' => false,
                    ]);
                    $eshPlanPatientLimitOptions = [];
                    foreach ([10, 30, 50, 100] as $L) {
                        $eshPlanPatientLimitOptions[] = FormHelper::makeOption((string) $L, $L . ' kayıt');
                    }
                    echo FormHelper::fieldSelect('limit', 'Sayfa başı', $eshPlanPatientLimitOptions, (string) (int) ($limit ?? 30), [
                        'col' => 'col-12 col-sm-6 col-md-4 col-lg-3',
                        'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                        'class' => 'form-select-sm esh-filter-control',
                        'tomSelect' => false,
                    ]);
                    ?>
                    <div class="col-12 col-md-auto d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 esh-filter-control">
                            <i class="fa-solid fa-filter me-1"></i>Filtrele
                        </button>
                        <a href="<?= htmlspecialchars(esh_url('PlannedVisit', 'patient', ['tc' => $tc]), ENT_QUOTES, "UTF-8") ?>"
                           class="btn btn-outline-secondary btn-sm rounded-pill px-4 esh-filter-control">Sıfırla</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <div class="small text-muted">
            Bu hasta için toplam <strong><?= (int) ($totalItems ?? 0) ?></strong> plan kaydı
        </div>
    </div>

    <div class="<?= htmlspecialchars((string) $pp_table_card, ENT_QUOTES, 'UTF-8') ?>">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <?= \App\Helpers\UIHelper::renderSortTh('Plan tarihi', 'p.planlanantarih', (string) ($ordering ?? ''), $eshPatientPlanSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Zaman', 'p.zaman', (string) ($ordering ?? ''), array_merge($eshPatientPlanSortCfg, ['thClass' => 'text-center'])) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Öncelik', 'p.oncelik', (string) ($ordering ?? ''), array_merge($eshPatientPlanSortCfg, ['thClass' => 'text-center'])) ?>
                            <th>Yapılacak işlem</th>
                            <th>Planlayan(lar)</th>
                            <th class="text-center">Durum</th>
                        </tr>
                    </thead>
                    <tbody id="esh-patient-plans-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($patientPlansRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-patient-plans-loading-row">
                            <td colspan="6" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>Plan listesi yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top py-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="small text-muted">
                        <?= \App\Helpers\PaginationHelper::infoText((int) ($totalItems ?? 0), (int) ($page ?? 1), (int) ($limit ?? 20)) ?>
                    </div>
                    <div>
                        <?= \App\Helpers\PaginationHelper::limitSelector((int) ($limit ?? 20), $ppPagelink) ?>
                    </div>
                </div>
                <div>
                    <?= \App\Helpers\PaginationHelper::render((int) ($totalItems ?? 0), (int) ($page ?? 1), (int) ($limit ?? 20), $ppPagelink) ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if ($articleClass !== ''): ?>
</article>
<?php endif; ?>
</div>