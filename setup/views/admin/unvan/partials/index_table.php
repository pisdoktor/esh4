            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <?= \App\Helpers\UIHelper::renderSortTh('ID', 'id', $ordering, $eshSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Kod', 'kod', $ordering, $eshSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Ünvan', 'name', $ordering, $eshSortCfg) ?>
                            <th>Kategori</th>
                            <th>İzin şablonu</th>
                            <th class="text-center">Durum</th>
                            <th width="150" class="text-center">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody id="esh-unvan-list-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($indexRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-unvan-list-loading-row">
                            <td colspan="7" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>Ünvan listesi yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
