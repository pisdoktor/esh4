<?php
/**
 * Araç listesi tablo kabuğu (thead + yükleme satırı).
 * @var string $indexRowsFetchUrl
 */
$eshAracListColspan = 5;
?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <?= \App\Helpers\UIHelper::renderSortTh('ID', 'id', $ordering, $eshSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Plaka', 'plaka', $ordering, $eshSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Araç bilgisi', 'arac_bilgisi', $ordering, $eshSortCfg) ?>
                            <th>Kurum</th>
                            <th width="150" class="text-center">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody id="esh-arac-list-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($indexRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           data-esh-colspan="<?= (int) $eshAracListColspan ?>">
                        <tr class="esh-arac-list-loading-row">
                            <td colspan="<?= (int) $eshAracListColspan ?>" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>Araç listesi yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
