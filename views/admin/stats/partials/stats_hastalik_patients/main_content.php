<div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-ranking-star text-primary me-2"></i><?= $hAd ?> <span class="text-secondary fw-normal">(<?= (int) $total ?> hasta)</span></h5>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <a href="<?= htmlspecialchars(esh_url('Stats', 'hastalik'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary">Tabloya dön</a>
                <?php \App\Helpers\StatsViewPdfHelper::renderPdfButton('main'); ?>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:72px">İzlem</th>
                            <?= \App\Helpers\UIHelper::renderSortTh('Hasta adı', 'h.isim', $ordering, $eshSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('TC', 'h.tckimlik', $ordering, $eshSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Mahalle / ilçe', 'h.mahalle', $ordering, $eshSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Kayıt yılı', 'h.kayittarihi', $ordering, $eshSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Doğum', 'h.dogumtarihi', $ordering, $eshSortCfg) ?>
                            <th>Yaş</th>
                            <?= \App\Helpers\UIHelper::renderSortTh('Son izlem', 'sonizlem', $ordering, $eshSortCfg) ?>
                        </tr>
                    </thead>
                    <tbody id="esh-stats-hp-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($hastalikPatientsRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Liste yükleniyor…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top-0 py-2 px-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="small text-muted">
                        <?= \App\Helpers\PaginationHelper::infoText((int) $total, (int) $page, (int) $limit) ?>
                    </div>
                    <div>
                        <?= \App\Helpers\PaginationHelper::limitSelector((int) $limit, $hpPagelink) ?>
                    </div>
                </div>
                <div>
                    <?= \App\Helpers\PaginationHelper::render((int) $total, (int) $page, (int) $limit, $hpPagelink) ?>