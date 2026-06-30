<div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3 border-bottom bg-body-tertiary bg-opacity-50"
             id="esh-erapor-hasta-uyum-list-metrics"
             data-esh-fetch-url="<?= htmlspecialchars($eraporHastaUyumMetricsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="d-flex flex-column align-items-center justify-content-center py-3 text-muted small">
                <span class="spinner-border spinner-border-sm text-info mb-2" role="status" aria-hidden="true"></span>
                Metrik sayıları yükleniyor…
            </div>
        </div>
        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="min-w-0">
                <h5 class="mb-1 fw-bold">
                    <i class="fa-solid fa-list text-info me-2"></i><?= $labelEsc ?>
                    <span class="text-secondary fw-normal">(<?= (int) $total ?> kayıt)</span>
                </h5>
                <div class="small text-muted">
                    Metrik: <code class="user-select-all"><?= $metricEsc ?></code>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <a href="<?= htmlspecialchars(esh_url('Stats', 'eraporHastaUyum'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary">Özete dön</a>
                <?php \App\Helpers\StatsViewPdfHelper::renderPdfButton('main'); ?>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <?php if ($patientPrimary): ?>
                                <th class="text-center" style="width:100px">İzlem</th>
                                <?= \App\Helpers\UIHelper::renderSortTh('Hasta', 'h.isim', $ordering, $eshSortCfg) ?>
                                <?= \App\Helpers\UIHelper::renderSortTh('TC', 'h.tckimlik', $ordering, $eshSortCfg) ?>
                                <th>Durum</th>
                                <th>e-Rapor kart</th>
                                <th>Havuz</th>
                            <?php else: ?>
                                <?= \App\Helpers\UIHelper::renderSortTh('Başvuru', 'e.basvurutarihi', $ordering, $eshSortCfg) ?>
                                <th>Havuz ad soyad</th>
                                <?= \App\Helpers\UIHelper::renderSortTh('TC', 'e.hastatckimlik', $ordering, $eshSortCfg) ?>
                                <th>Kayıtlı?</th>
                                <th>Yenilendi</th>
                                <th>Branş</th>
                                <th>Hasta kartı</th>
                                <th>Durum</th>
                            <?php endif; ?>
                            <th style="width:140px"></th>
                        </tr>
                    </thead>
                    <tbody id="esh-erapor-hasta-uyum-list-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($eraporHastaUyumListRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr>
                            <td colspan="<?= $patientPrimary ? 7 : 9 ?>" class="text-center text-muted py-4">
                                <span class="spinner-border spinner-border-sm text-info me-2" role="status" aria-hidden="true"></span>Liste yükleniyor…
                            </td>
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