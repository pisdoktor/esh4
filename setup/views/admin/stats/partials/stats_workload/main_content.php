<div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom py-3 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <ul class="nav esh-workload-tabs mb-0" role="tablist">
                <?php foreach ($workloadTabs as $tab):
                    $cnt = (int) ($groupCounts[$tab['group']] ?? 0);
                    $isActive = $activeGroup === $tab['group'];
                    $tabUrl = esh_url('Stats', 'workload', array (
  'tab' => '',
))
                        . rawurlencode($tab['slug'])
                        . '&limit=' . (int) $limit;
                    ?>
                    <li class="nav-item" role="presentation">
                        <a
                            class="nav-link <?= $isActive ? 'active tab-' . htmlspecialchars($tab['slug'], ENT_QUOTES, 'UTF-8') : '' ?>"
                            href="<?= htmlspecialchars($tabUrl, ENT_QUOTES, 'UTF-8') ?>"
                            role="tab"
                            aria-selected="<?= $isActive ? 'true' : 'false' ?>"
                        >
                            <?= htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8') ?>
                            <span class="badge rounded-pill ms-1"><?= number_format($cnt, 0, ',', '.') ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php \App\Helpers\StatsViewPdfHelper::renderPdfButton('main'); ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="overflow: visible !important;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light sticky-top">
                        <?php include dirname(__DIR__) . '/shared/workload_table_head.php'; ?>
                    </thead>
                    <tbody id="esh-workload-list-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($workloadRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           data-esh-colspan="<?= (int) $workloadColspan ?>">
                        <tr class="esh-workload-list-loading-row">
                            <td colspan="<?= (int) $workloadColspan ?>" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>Hizmet listesi yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($total > 0): ?>
        <div class="card-footer bg-white border-top py-2 px-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-3">
                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="small text-muted">
                        <?= \App\Helpers\PaginationHelper::infoText($total, $page, $limit) ?>
                    </div>
                    <div>
                        <?= \App\Helpers\PaginationHelper::limitSelector($limit, $pagelinkWithSort) ?>
                    </div>
                </div>
                <div>
                    <?= \App\Helpers\PaginationHelper::render($total, $page, $limit, $pagelinkWithSort) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>