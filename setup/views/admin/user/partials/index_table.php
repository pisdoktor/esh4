            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <?= \App\Helpers\UIHelper::renderSortTh('Personel', 'name', $ordering, array_merge($eshSortCfg, ['thClass' => 'ps-4 py-3'])) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Kullanıcı adı / TC', 'username', $ordering, $eshSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Ünvan', 'unvan', $ordering, $eshSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Kurum', 'kurum', $ordering, $eshSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('E-posta', 'email', $ordering, $eshSortCfg) ?>
                            <th>Tema</th>
                            <th class="text-center">Yetki</th>
                            <?= \App\Helpers\UIHelper::renderSortTh('Durum', 'activated', $ordering, array_merge($eshSortCfg, ['thClass' => 'text-center'])) ?>
                            <th class="text-end pe-4">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody id="esh-user-list-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($listRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-user-list-loading-row">
                            <td colspan="9" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>Kullanıcı listesi yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
