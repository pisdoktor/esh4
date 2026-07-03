            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small text-muted">
                        <tr>
                            <?= \App\Helpers\UIHelper::renderSortTh('ICD kodu', 'icd', $ordering, array_merge($eshSortCfg, ['thClass' => 'ps-4'])) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Hastalık / tanı adı', 'name', $ordering, $eshSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Kategori', 'cat', $ordering, $eshSortCfg) ?>
                            <th class="text-end pe-4" style="width: 120px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody id="esh-hastalik-list-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($indexRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-hastalik-list-loading-row">
                            <td colspan="4" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>Hastalık listesi yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>