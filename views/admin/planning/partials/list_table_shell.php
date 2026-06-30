<?php
include dirname(__DIR__, 3) . '/partials/admin/list_page_open.php';
?>

            <form action="<?= htmlspecialchars(esh_url('Planning', 'save'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                <input type="hidden" name="current_ilce" value="<?= htmlspecialchars((string) $ilce_id, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="current_bolge" value="<?= htmlspecialchars($bolge_filter, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="current_page" value="<?= (int) $page ?>">
                <input type="hidden" name="current_limit" value="<?= (int) $limit ?>">
                <input type="hidden" name="orderby" value="<?= htmlspecialchars((string) ($orderby ?? 'bolge'), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="orderdir" value="<?= htmlspecialchars((string) ($orderdir ?? 'ASC'), ENT_QUOTES, 'UTF-8') ?>">
                <div class="d-flex justify-content-end p-3 border-bottom bg-light-subtle">
                    <button type="submit" class="btn btn-sm btn-primary shadow-sm">
                        <i class="fa-solid fa-layer-group me-1"></i> Toplu Kaydet (Bu Sayfa)
                    </button>
                </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small text-muted">
                        <tr>
                            <?= \App\Helpers\UIHelper::renderSortTh('İlçe / mahalle', 'ilce', $ordering, array_merge($eshSortCfg, ['thClass' => 'ps-4'])) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Bölge', 'bolge', $ordering, $eshSortCfg) ?>
                            <th>Ziyaret günleri</th>
                            <th class="text-end pe-4" style="width: 100px;">Kaydet</th>
                        </tr>
                    </thead>
                    <tbody id="esh-planning-list-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($indexRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-planning-list-loading-row">
                            <td colspan="4" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>Mahalle listesi yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </form>

<?php
$admin_list_close_part = 'body_only';
include dirname(__DIR__, 3) . '/partials/admin/list_page_close.php';
?>

        <div class="card-footer bg-white border-top-0 py-2">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="small text-muted">
                        <?= \App\Helpers\PaginationHelper::infoText((int) $total, (int) $page, (int) $limit) ?>
                    </div>
                    <div>
                        <?= \App\Helpers\PaginationHelper::limitSelector((int) $limit, $planningPagelink) ?>
                    </div>
                </div>
                <div>
                    <?= \App\Helpers\PaginationHelper::render((int) $total, (int) $page, (int) $limit, $planningPagelink) ?>
                </div>
            </div>
        </div>

<?php
$admin_list_close_part = 'card_only';
include dirname(__DIR__, 3) . '/partials/admin/list_page_close.php';
unset($admin_list_close_part);