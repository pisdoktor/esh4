    <div class="card border-0 shadow-sm rounded-3">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader(htmlspecialchars($raporBitisLabel, ENT_QUOTES, 'UTF-8') . ' — hasta listesi', 'main'); ?>
        <div class="card-body p-0">
            <div class="table-responsive" style="overflow: visible !important;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <?php include dirname(__DIR__) . '/shared/patient_drilldown_table_head.php'; ?>
                    </thead>
                    <tbody id="esh-supply-reports-list-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($supplyRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-supply-reports-list-loading-row">
                            <td colspan="11" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span><?= $tab === 'bez' ? 'Bez' : 'Mama' ?> rapor listesi yükleniyor…</span>
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
                    <?= \App\Helpers\PaginationHelper::limitSelector((int) $limit, $supplyPagelink) ?>
                </div>
            </div>
            <div>
                <?= \App\Helpers\PaginationHelper::render((int) $total, (int) $page, (int) $limit, $supplyPagelink) ?>
            </div>
        </div>
    </div>