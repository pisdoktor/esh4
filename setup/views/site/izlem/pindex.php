<div class="esh-page esh-page--list esh-page-izlem container-fluid py-4 plan-list-page" id="esh-planned-visit-index-root"
     data-esh-pdf-url="<?= htmlspecialchars($plannedVisitIndexPdfDataUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
<?php
use App\Helpers\AuthHelper;
use App\Helpers\FormHelper;

/** @var string $planFilterQuery */
$oncelikConfig = [
    '1' => ['text' => 'Normal', 'class' => 'success'],
    '2' => ['text' => 'Orta', 'class' => 'warning'],
    '3' => ['text' => 'Yüksek', 'class' => 'danger'],
    '0' => ['text' => '—', 'class' => 'secondary'],
];
$ordering = $ordering ?? '';
$planListBase = \App\Helpers\UrlHelper::fromQueryString($planFilterQuery);
$planDeleteRetq = http_build_query(array_merge($planFilterBase ?? [], [
    'page' => (int) ($page ?? 1),
    'ordering' => (string) ($ordering ?? ''),
]));
$eshPlanSortCfg = ['mode' => 'ordering', 'base' => $planFilterBase ?? []];
?>
    <div class="mb-3 d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-calendar-check text-primary me-2"></i>Planlı izlem listesi
            </h4>
            <p class="text-muted small mb-0">Planlanmış izlemleri tarih, işlem ve bekleyen veya yapıldı durumuna göre listeler.</p>
        </div>
        <div class="flex-shrink-0">
            <?php
            $eshListPdfBtnId = 'esh-planned-visit-index-pdf-btn';
            $eshListPdfBtnDisabled = false;
            include ROOT_PATH . '/views/site/partials/list_pdf_button.php';
            ?>
        </div>
    </div>

    <?php include __DIR__ . '/partials/modern_planning_pilot.php'; ?>

    <?php if (AuthHelper::sessionIsAdmin() && !empty($passivePendingCount) && (int) $passivePendingCount > 0): ?>
        <div class="alert alert-warning border-0 shadow-sm d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <div class="small mb-0">
                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                <strong><?= (int) $passivePendingCount ?></strong> bekleyen planlı izlem pasif veya bekleyen-dışı hasta dosyalarında kayıtlı.
            </div>
            <a href="<?= htmlspecialchars(esh_url('PlannedVisit', 'passivePendingPlans'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-danger rounded-pill">
                <i class="fa-solid fa-list me-1"></i>Listele ve yönet
            </a>
        </div>
    <?php endif; ?>

    <div class="card border-0 esh-list-filter-card mb-4">
        <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
            <span class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                <i class="fa-solid fa-sliders"></i>
            </span>
            <div class="min-w-0">
                <span class="fw-semibold text-dark d-block">Filtreler</span>
            </div>
            </div>
            <button id="plan-filter-toggle" class="btn btn-outline-secondary btn-sm rounded-pill px-3" type="button" data-bs-toggle="collapse" data-bs-target="#plan-filter-collapse" aria-expanded="false" aria-controls="plan-filter-collapse">
                <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text">Filtreleri Göster</span>
            </button>
        </div>
        <div id="plan-filter-collapse" class="collapse">
        <div class="card-body p-3 p-md-4 bg-body-tertiary bg-opacity-25">
            <form id="form-plan-index-filter" action="<?= htmlspecialchars(esh_form_action('PlannedVisit', 'index'), ENT_QUOTES, 'UTF-8') ?>" method="get" class="row g-3 g-xl-4 align-items-end esh-plan-filter">
                <?= esh_form_route_hiddens('PlannedVisit', 'index') ?>

                <?= FormHelper::fieldDateRangeFilter('date_from', 'date_to', $dateFrom ?? date('Y-m-d'), $dateTo ?? date('Y-m-d'), [
                    'col' => 'col-12',
                    'label' => 'Plan tarihi aralığı',
                    'fromId' => 'plan-filter-date-from',
                    'toId' => 'plan-filter-date-to',
                    'fromRawValue' => \App\Helpers\DateHelper::toTrOrEmpty($dateFrom ?? date('Y-m-d')),
                    'toRawValue' => \App\Helpers\DateHelper::toTrOrEmpty($dateTo ?? date('Y-m-d')),
                    'class' => 'form-control-sm border-start-0 esh-filter-control',
                ]) ?>

                <?php
                $eshPlanIslemOptions = [FormHelper::makeOption('0', 'Tüm işlemler')];
                foreach ($islemler ?? [] as $is) {
                    $eshPlanIslemOptions[] = FormHelper::makeOption((string) (int) $is->id, (string) $is->islemadi);
                }
                echo FormHelper::fieldSelect('secim', 'Yapılacak işlem', $eshPlanIslemOptions, (string) (int) ($secim ?? 0), [
                    'col' => 'col-12 col-sm-6 col-lg-4 col-xl-3',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'class' => 'form-select-sm shadow-sm esh-filter-control',
                    'tomSelect' => false,
                ]);
                echo FormHelper::fieldSelect('durum', 'Durum', [
                    FormHelper::makeOption('', 'Tümü'),
                    FormHelper::makeOption('0', 'Bekleyen'),
                    FormHelper::makeOption('1', 'Yapıldı'),
                ], (string) ($durum ?? ''), [
                    'col' => 'col-12 col-sm-6 col-lg-4 col-xl-3',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'class' => 'form-select-sm shadow-sm esh-filter-control',
                    'tomSelect' => false,
                ]);
                $eshPlanLimitOptions = [];
                foreach ([20, 50, 100, 200] as $L) {
                    $eshPlanLimitOptions[] = FormHelper::makeOption((string) $L, $L . ' kayıt');
                }
                echo FormHelper::fieldSelect('limit', 'Sayfa başı', $eshPlanLimitOptions, (string) (int) ($limit ?? 50), [
                    'col' => 'col-12 col-sm-6 col-lg-4 col-xl-2',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'class' => 'form-select-sm shadow-sm esh-filter-control',
                    'tomSelect' => false,
                ]);
                echo FormHelper::fieldInput('search', 'Arama', (string) ($search ?? ''), [
                    'col' => 'col-12 col-xl-4',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'labelHtml' => 'Arama <span class="fw-normal text-muted">(TC, ad)</span>',
                    'class' => 'form-control-sm shadow-sm esh-filter-control',
                    'placeholder' => 'İsteğe bağlı…',
                ]);
                ?>

                <div class="col-12 pt-1 d-flex flex-wrap gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary btn-sm shadow-sm px-4 rounded-pill esh-filter-control">
                        <i class="fa-solid fa-filter me-1"></i>Getir
                    </button>
                    <a href="<?= htmlspecialchars(esh_url('PlannedVisit', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 esh-filter-control">Sıfırla</a>
                </div>
            </form>
        </div>
        </div>
    </div>

    <div class="card border-0 esh-list-table-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-center" style="width:88px" title="Hasta bazlı özet">
                                <i class="fa-solid fa-chart-line text-primary"></i><br><small class="text-muted">Özet</small>
                            </th>
                            <?= \App\Helpers\UIHelper::renderSortTh('Hasta adı', 'h.isim', (string) ($ordering ?? ''), $eshPlanSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('TC', 'h.tckimlik', (string) ($ordering ?? ''), $eshPlanSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Mahalle / ilçe', 'a2.adi', (string) ($ordering ?? ''), $eshPlanSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Plan tarihi', 'p.planlanantarih', (string) ($ordering ?? ''), $eshPlanSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Zaman', 'p.zaman', (string) ($ordering ?? ''), array_merge($eshPlanSortCfg, ['thClass' => 'text-center'])) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Öncelik', 'p.oncelik', (string) ($ordering ?? ''), array_merge($eshPlanSortCfg, ['thClass' => 'text-center'])) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Yapılacak işlem', 'p.yapilacak', (string) ($ordering ?? ''), $eshPlanSortCfg) ?>
                            <th>Planlayan(lar)</th>
                            <th class="text-center">Durum</th>
                        </tr>
                    </thead>
                    <tbody id="esh-planned-visit-list-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($indexRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-planned-visit-list-loading-row">
                            <td colspan="10" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>Planlı izlem listesi yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php $planPagelink = \App\Helpers\UrlHelper::fromQueryString($planFilterQuery . '&ordering=' . rawurlencode($ordering)); ?>
        <div class="card-footer bg-white border-top-0 py-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="small text-muted">
                        <?= \App\Helpers\PaginationHelper::infoText((int) ($totalPlans ?? 0), (int) ($page ?? 1), (int) ($limit ?? 50)) ?>
                    </div>
                    <div>
                        <?= \App\Helpers\PaginationHelper::limitSelector((int) ($limit ?? 50), $planPagelink) ?>
                    </div>
                </div>
                <div>
                    <?= \App\Helpers\PaginationHelper::render((int) ($totalPlans ?? 0), (int) ($page ?? 1), (int) ($limit ?? 50), $planPagelink) ?>
                </div>
            </div>
        </div>
    </div>
<?php include ROOT_PATH . '/views/site/partials/list_pdf_assets.php'; ?>
</div>