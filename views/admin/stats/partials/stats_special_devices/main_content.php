<div class="row g-3">
        <?php foreach ($labels as $it): ?>
            <?php $k = $it['k']; ?>
            <div class="col-6 col-md-4 col-xl-3">
                <a href="<?= htmlspecialchars(esh_url('Stats', 'specialDevices', ['device' => urlencode((string) $k)]), ENT_QUOTES, "UTF-8") ?>" class="text-decoration-none">
                    <div class="card border-0 shadow-sm h-100 <?= (($selectedDevice ?? '') === $k) ? 'border border-primary' : '' ?>">
                        <div class="card-body py-3 text-center">
                            <div class="small text-muted"><?= htmlspecialchars($it['t'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="h4 mb-0 fw-bold text-primary"><?= (int) ($s->$k ?? 0) ?></div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($selectedDeviceLabel)): ?>
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center gap-2">
                <div class="min-w-0">
                    <strong><?= htmlspecialchars((string) $selectedDeviceLabel, ENT_QUOTES, 'UTF-8') ?></strong> işaretli aktif hastalar
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <a href="<?= htmlspecialchars(esh_url('Stats', 'specialDevices'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary">Temizle</a>
                    <?php \App\Helpers\StatsViewPdfHelper::renderPdfButton('main'); ?>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 520px;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr class="align-middle">
                                <th class="text-center">İzlem</th>
                                <th>Hasta</th>
                                <th>TC</th>
                                <th>İlçe / mahalle</th>
                                <th class="text-muted">Anne/Baba</th>
                                <th>D.Tarihi</th>
                                <th>İletişim</th>
                                <th>Kayıt</th>
                                <th class="text-end pe-3">Son izlem</th>
                            </tr>
                        </thead>
                        <tbody id="esh-special-devices-list-tbody"
                               data-esh-fetch-url="<?= htmlspecialchars($specialDevicesRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <tr class="esh-special-devices-list-loading-row">
                                <td colspan="9" class="border-0 py-5 text-center text-muted">
                                    <div class="d-flex flex-column align-items-center gap-2">
                                        <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                        <span>Cihaz listesi yukleniyor...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>