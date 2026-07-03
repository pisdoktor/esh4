        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fa-solid fa-list-ul me-2 text-primary"></i>Dosya Kayıtları
                        <span class="badge bg-secondary ms-2 small fw-normal" style="font-size: 0.7rem;"><?= $total ?> Kayıt</span>
                    </h5>
                </div>
                
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                <th class="text-muted small" style="width: 4rem;">ID</th>
                                    <?= \App\Helpers\UIHelper::renderSortTh('Hasta adı / TC', 'h.isim', $ordering, array_merge($eshSortCfg, ['thClass' => 'ps-4 py-3 text-muted small'])) ?>
                                    <?= \App\Helpers\UIHelper::renderSortTh('Bölge / adres', 'mahalleadi', $ordering, array_merge($eshSortCfg, ['thClass' => 'text-muted small'])) ?>
                                    <?= \App\Helpers\UIHelper::renderSortTh('Toplam izlem', 'izlemsayisi', $ordering, array_merge($eshSortCfg, ['thClass' => 'text-center text-muted small'])) ?>
                                    <?= \App\Helpers\UIHelper::renderSortTh('Son izlem', 'sonizlemtarihi', $ordering, array_merge($eshSortCfg, ['thClass' => 'text-muted small'])) ?>
                                    <th class="text-end pe-4" style="width: 10%;">İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="esh-archive-list-tbody"
                                   data-esh-fetch-url="<?= htmlspecialchars($indexRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <tr class="esh-archive-list-loading-row">
                                    <td colspan="6" class="border-0 py-5 text-center text-muted">
                                        <div class="d-flex flex-column align-items-center gap-2">
                                            <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                            <span>Dosya kayıtları yükleniyor…</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if($totalPages > 1): ?>
                <div class="card-footer bg-white border-top-0 py-2">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="small text-muted">
                    <?= \App\Helpers\PaginationHelper::infoText($total, $page, $limit) ?>
                    </div>
                    <div>
                    <?= \App\Helpers\PaginationHelper::limitSelector($limit, $pagelink) ?>
                    </div>
                </div>
                <div>
                <?= \App\Helpers\PaginationHelper::render($total, $page, $limit, $pagelink) ?>
                </div>
            </div>
        </div>
                <?php endif; ?>
            </div>
        </div>
