                    <div class="hastailacrapor-summary-strip px-3 py-3 border-bottom bg-body-tertiary bg-opacity-25">
                        <div class="row g-2 text-center small">
                            <div class="col-4 col-md-4">
                                <div class="hastailacrapor-summary-strip__value text-success fw-bold" id="esh-hir-cnt-raporlu"><?= (int) $cntRaporlu ?></div>
                                <div class="text-muted">Raporlu tanı</div>
                            </div>
                            <div class="col-4 col-md-4">
                                <div class="hastailacrapor-summary-strip__value text-danger fw-bold" id="esh-hir-cnt-expired"><?= (int) $cntExpired ?></div>
                                <div class="text-muted">Süresi dolmuş</div>
                            </div>
                            <div class="col-4 col-md-4">
                                <div class="hastailacrapor-summary-strip__value text-warning fw-bold" id="esh-hir-cnt-expiring"><?= (int) $cntExpiring ?></div>
                                <div class="text-muted">30 gün içinde biten</div>
                            </div>
                        </div>
                    </div>

                    <div class="px-3 py-2 border-bottom d-flex flex-wrap gap-2 align-items-center hastailacrapor-filter-bar">
                        <span class="small text-muted me-1">Filtre:</span>
                        <div class="btn-group btn-group-sm hastailacrapor-filter-group" role="group" aria-label="Rapor filtresi">
                            <button type="button" class="btn btn-outline-secondary active" data-hastailac-filter="all">Tümü</button>
                            <button type="button" class="btn btn-outline-secondary" data-hastailac-filter="1">Raporlu</button>
                            <button type="button" class="btn btn-outline-secondary" data-hastailac-filter="0">Raporlu değil</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanı</th>
                                    <th>Durum</th>
                                    <th>Bitiş tarihi</th>
                                    <th>Branşlar</th>
                                    <th>Rapor yeri</th>
                                    <th class="text-end">İşlem</th>
                                </tr>
                            </thead>
                            <tbody id="esh-hir-rapor-tbody"
                                   data-esh-fetch-url="<?= htmlspecialchars($raporRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Liste yükleniyor…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
