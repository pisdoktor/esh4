        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0">
                    <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Öncelik durumuna göre', 'tablo', 'h6', 'card-header bg-white py-3 border-bottom'); ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Öncelik</th>
                                    <th class="text-end">Toplam</th>
                                    <th class="text-end text-success">Tamamlanan</th>
                                    <th class="text-end text-warning">Bekleyen</th>
                                    <th class="text-end text-danger pe-3">Gecikmiş</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ([1, 2, 3] as $k): ?>
                                    <?php
                                    $row = $priorityMap[$k] ?? null;
                                    $meta = $oncelikMeta[$k];
                                    ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge rounded-pill <?= htmlspecialchars($meta['badge'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="text-end fw-semibold"><?= (int) ($row->toplam ?? 0) ?></td>
                                        <td class="text-end text-success"><?= (int) ($row->tamamlanan ?? 0) ?></td>
                                        <td class="text-end text-warning"><?= (int) ($row->bekleyen ?? 0) ?></td>
                                        <td class="text-end text-danger pe-3"><?= (int) ($row->gecikmis ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card shadow-sm border-0">
                    <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Zaman dilimine göre', 'zaman', 'h6', 'card-header bg-white py-3 border-bottom'); ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Dilim</th>
                                    <th class="text-end">Toplam</th>
                                    <th class="text-end text-success">Tamamlanan</th>
                                    <th class="text-end text-warning pe-3">Bekleyen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($byZaman === []): ?>
                                    <tr><td colspan="4" class="text-muted small ps-3">—</td></tr>
                                <?php else: ?>
                                    <?php foreach ($byZaman as $z): ?>
                                        <?php $zk = (int) ($z->zaman_kod ?? 1); ?>
                                        <tr>
                                            <td class="ps-3"><?= htmlspecialchars(ZamanDilimiHelper::label($zk), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-end fw-semibold"><?= (int) ($z->toplam ?? 0) ?></td>
                                            <td class="text-end text-success"><?= (int) ($z->tamamlanan ?? 0) ?></td>
                                            <td class="text-end text-warning pe-3"><?= (int) ($z->bekleyen ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>