<div class="card border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
        <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="rounded-circle bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                    <i class="fa-solid fa-sliders"></i>
                </span>
                <div class="min-w-0">
                    <span class="fw-semibold text-dark d-block">Liste filtreleri</span>
                </div>
            </div>
            <button
                id="stats-sonda-changes-filter-toggle"
                class="btn btn-outline-secondary btn-sm rounded-pill px-3<?= $filterExpanded ? '' : ' collapsed' ?>"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#stats-sonda-changes-filter-collapse"
                aria-expanded="<?= $filterExpanded ? 'true' : 'false' ?>"
                aria-controls="stats-sonda-changes-filter-collapse"
            >
                <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text"><?= $filterExpanded ? 'Filtreleri Gizle' : 'Filtreleri Göster' ?></span>
            </button>
        </div>
        <div id="stats-sonda-changes-filter-collapse" class="collapse<?= $filterExpanded ? ' show' : '' ?>">
        <div class="card-body p-3 p-md-4 bg-body-tertiary bg-opacity-25">
            <form class="row g-3 g-xl-4 align-items-end" method="get" action="<?= htmlspecialchars(esh_form_action('Stats', 'sondaChanges'), ENT_QUOTES, 'UTF-8') ?>">
                <?= esh_form_route_hiddens('Stats', 'sondaChanges') ?>
                <?= \App\Helpers\FormHelper::fieldDateRangeFilter('date_from', 'date_to', $date_from, $date_to, [
                    'col' => 'col-12 col-md-6',
                    'label' => 'Sonda değişim tarihi aralığı',
                    'prefixIconClass' => 'bg-white text-warning border-end-0',
                ]) ?>
                <div class="col-12 col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm"><i class="fa-solid fa-filter me-1"></i>Uygula</button>
                    <a href="<?= htmlspecialchars(esh_url('Stats', 'sondaChanges'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Sıfırla</a>
                </div>
            </form>
        </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm rounded-3">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Sonda değişim listesi', 'main'); ?>
        <div class="card-body p-0">
            <div class="table-responsive" style="overflow: visible !important;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <?php include dirname(__DIR__) . '/shared/patient_drilldown_table_head.php'; ?>
                    </thead>
                    <tbody id="esh-sonda-changes-list-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($sondaRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-sonda-changes-list-loading-row">
                            <td colspan="10" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>Sonda listesi yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white border-top-0 py-2 mt-3 px-0">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="small text-muted">
                    <?= \App\Helpers\PaginationHelper::infoText((int) $total, (int) $page, (int) $limit) ?>
                </div>
                <div>
                    <?= \App\Helpers\PaginationHelper::limitSelector((int) $limit, $sondaPagelink) ?>
                </div>
            </div>
            <div>
                <?= \App\Helpers\PaginationHelper::render((int) $total, (int) $page, (int) $limit, $sondaPagelink) ?>