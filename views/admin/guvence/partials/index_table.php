            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <?= \App\Helpers\UIHelper::renderSortTh('ID', 'id', $ordering, $eshSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Güvence adı', 'name', $ordering, $eshSortCfg) ?>
                            <th width="150" class="text-center">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody id="esh-guvence-list-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($indexRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-guvence-list-loading-row">
                            <td colspan="3" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>Güvence listesi yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>