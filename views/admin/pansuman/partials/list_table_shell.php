<?php
include dirname(__DIR__, 2) . '/partials/admin/list_page_open.php';
?>

            <form action="<?= htmlspecialchars(esh_url('Pansuman', 'saveDays'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="filter_day" value="<?= htmlspecialchars($filter_day, ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($eshPansumanListKurum !== ''): ?>
                <input type="hidden" name="kurum_id" value="<?= htmlspecialchars($eshPansumanListKurum, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
                <input type="hidden" name="page" value="<?= (int) $page ?>">
                <input type="hidden" name="current_limit" value="<?= (int) $limit ?>">
                <input type="hidden" name="orderby" value="<?= htmlspecialchars((string) ($orderby ?? 'h.isim'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="orderdir" value="<?= htmlspecialchars((string) ($orderdir ?? 'ASC'), ENT_QUOTES, 'UTF-8') ?>">
                <div class="d-flex justify-content-end p-3 border-bottom bg-light-subtle">
                    <button type="submit" class="btn btn-sm btn-primary shadow-sm">
                        <i class="fa-solid fa-layer-group me-1"></i> Toplu Kaydet (Bu Sayfa)
                    </button>
                </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 esh-pansuman-list-table">
                    <colgroup>
                        <col class="esh-pansuman-col-hasta">
                        <col class="esh-pansuman-col-tc">
                        <col class="esh-pansuman-col-mahalle">
                        <col class="esh-pansuman-col-gunler">
                        <col class="esh-pansuman-col-zaman">
                        <col class="esh-pansuman-col-kaydet">
                    </colgroup>
                    <thead class="bg-light small text-muted">
                        <tr>
                            <?= \App\Helpers\UIHelper::renderSortTh('Hasta bilgisi', 'h.isim', $ordering, array_merge($eshSortCfg, ['thClass' => 'ps-4 esh-pansuman-th-hasta'])) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('TC', 'h.tckimlik', $ordering, array_merge($eshSortCfg, ['thClass' => 'esh-pansuman-th-tc'])) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Mahalle', 'mahalle', $ordering, array_merge($eshSortCfg, ['thClass' => 'esh-pansuman-th-mahalle'])) ?>
                            <th class="esh-pansuman-th-gunler">Uygulama günleri</th>
                            <th class="esh-pansuman-th-zaman">Zaman</th>
                            <th class="text-end pe-4 esh-pansuman-th-kaydet">Kaydet</th>
                        </tr>
                    </thead>
                    <tbody id="esh-pansuman-list-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($indexRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-pansuman-list-loading-row">
                            <td colspan="6" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>Pansuman listesi yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </form>

<?php
$admin_list_close_part = 'body_only';
include dirname(__DIR__, 2) . '/partials/admin/list_page_close.php';
?>

        <div class="card-footer bg-white border-top-0 py-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="small text-muted">
                        <?= \App\Helpers\PaginationHelper::infoText((int) $total, (int) $page, (int) $limit) ?>
                    </div>
                    <div>
                        <?= \App\Helpers\PaginationHelper::limitSelector((int) $limit, $pansumanPagelink) ?>
                    </div>
                </div>
                <div>
                    <?= \App\Helpers\PaginationHelper::render((int) $total, (int) $page, (int) $limit, $pansumanPagelink) ?>
                </div>
            </div>
        </div>

<?php
$admin_list_close_part = 'card_only';
include dirname(__DIR__, 2) . '/partials/admin/list_page_close.php';
unset($admin_list_close_part);
?>