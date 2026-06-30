        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-users text-success me-2"></i>Hasta listesi</h6>
                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                        <span class="badge text-bg-light border text-secondary"><?= (int) $total ?> kayıt</span>
                        <?php \App\Helpers\StatsViewPdfHelper::renderPdfButton('main'); ?>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width:76px"><span class="small text-muted">İzlem</span></th>
                                    <?= \App\Helpers\UIHelper::renderSortTh('Hasta adı', 'h.isim', $ordering, $eshSortCfg) ?>
                                    <?= \App\Helpers\UIHelper::renderSortTh('TC', 'h.tckimlik', $ordering, $eshSortCfg) ?>
                                    <?= \App\Helpers\UIHelper::renderSortTh('İlçe', 'ilceadi', $ordering, $eshSortCfg) ?>
                                    <?= \App\Helpers\UIHelper::renderSortTh('Mahalle', 'mahalleadi', $ordering, $eshSortCfg) ?>
                                    <?= \App\Helpers\UIHelper::renderSortTh('Sokak', 'sokakadi', $ordering, $eshSortCfg) ?>
                                    <?= \App\Helpers\UIHelper::renderSortTh('Kapı no', 'kapinoadi', $ordering, $eshSortCfg) ?>
                                    <?= \App\Helpers\UIHelper::renderSortTh('Son izlem', 'sonizlemtarihi', $ordering, $eshSortCfg) ?>
                                </tr>
                            </thead>
                            <tbody id="esh-adres-patient-filter-tbody"
                                   data-esh-fetch-url="<?= htmlspecialchars($adresPatientFilterRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <tr class="esh-adres-patient-filter-loading-row">
                                    <td colspan="8" class="border-0 py-5 text-center text-muted">
                                        <div class="d-flex flex-column align-items-center gap-2">
                                            <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                            <span>Liste yükleniyor…</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-top py-2">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-1">
                        <div class="d-flex align-items-center gap-3">
                            <div class="small text-muted">
                                <?= \App\Helpers\PaginationHelper::infoText((int) $total, (int) $page, (int) $limit) ?>
                            </div>
                            <div>
                                <?= \App\Helpers\PaginationHelper::limitSelector((int) $limit, $sortPagelink) ?>
                            </div>
                        </div>
                        <div>
                            <?= \App\Helpers\PaginationHelper::render((int) $total, (int) $page, (int) $limit, $sortPagelink) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>