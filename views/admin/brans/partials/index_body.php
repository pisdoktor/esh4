                    <?php if ($isCatalogPickerMode): ?>
                    <div class="alert alert-info small py-2 mb-3" role="status">
                        Soldaki platform branş listesinden seçip <strong>&gt;</strong> ile kuruma ekleyin;
                        <strong>&lt;</strong> ile kurumdan çıkarın. Günlük hasta kotası sağ kolonda kurumunuza özeldir.
                    </div>
                    <form method="post" action="<?= htmlspecialchars($saveSelectionUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <?php
                    $catalogPickerShuttleId = 'esh-brans-shuttle';
                    $catalogPickerShuttleFetchUrl = $indexRowsFetchUrl ?? '';
                    $catalogPickerShuttleCatalogLabel = 'Platform branşları (esh_branslar)';
                    $catalogPickerShuttleAssignedLabel = 'Kurum branşları';
                    $catalogPickerShuttleShowKota = true;
                    include dirname(__DIR__, 2) . '/partials/catalog_picker_shuttle.php';
                    ?>
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Seçimi kaydet
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                    <p class="small text-muted mb-3">
                        Platform geneli branş kataloğu. Kurumlar bu listeden kendi branşlarını seçer; günlük hasta kotası kurum yöneticisi tarafından belirlenir.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <?= \App\Helpers\UIHelper::renderSortTh('ID', 'id', $ordering, $eshSortCfg) ?>
                                    <?= \App\Helpers\UIHelper::renderSortTh('Branş adı', 'name', $ordering, $eshSortCfg) ?>
                                    <th width="150" class="text-center">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody id="esh-brans-list-tbody"
                                   data-esh-fetch-url="<?= htmlspecialchars($indexRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   data-esh-picker-mode="0">
                                <tr class="esh-brans-list-loading-row">
                                    <td colspan="3" class="border-0 py-5 text-center text-muted">
                                        <div class="d-flex flex-column align-items-center gap-2">
                                            <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                            <span>Branş listesi yükleniyor…</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>