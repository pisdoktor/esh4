        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0">
                    <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Araç kullanımına göre', 'tablo', 'h6', 'card-header bg-white py-3 border-bottom'); ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Kategori</th>
                                    <th class="text-end">Toplam</th>
                                    <th class="text-end text-success">Yapıldı</th>
                                    <th class="text-end text-warning pe-3">Yapılmadı</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ([1, 0] as $k): ?>
                                    <?php
                                    $row = $aracMap[$k] ?? null;
                                    $meta = $aracMeta[$k];
                                    $rowToplam = (int) ($row->toplam ?? 0);
                                    $rowYapilan = (int) ($row->yapilan ?? 0);
                                    $rowYapilmayan = (int) ($row->yapilmayan ?? 0);
                                    ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="badge rounded-pill <?= htmlspecialchars($meta['badge'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="text-end fw-semibold"><?= number_format($rowToplam, 0, ',', '.') ?></td>
                                        <td class="text-end text-success"><?= $countWithShare($rowYapilan, $rowToplam) ?></td>
                                        <td class="text-end text-warning pe-3"><?= $countWithShare($rowYapilmayan, $rowToplam) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 mb-4">
                    <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Zaman dilimine göre', 'zaman', 'h6', 'card-header bg-white py-3 border-bottom'); ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Dilim</th>
                                    <th class="text-end">Toplam</th>
                                    <th class="text-end text-success">Yapıldı</th>
                                    <th class="text-end text-warning pe-3">Yapılmadı</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($byZaman === []): ?>
                                    <tr><td colspan="4" class="text-muted small ps-3">—</td></tr>
                                <?php else: ?>
                                    <?php foreach ($byZaman as $z): ?>
                                        <?php
                                        $zk = (int) ($z->zaman_kod ?? 1);
                                        $zToplam = (int) ($z->toplam ?? 0);
                                        $zYapilan = (int) ($z->yapilan ?? 0);
                                        $zYapilmayan = (int) ($z->yapilmayan ?? 0);
                                        ?>
                                        <tr>
                                            <td class="ps-3"><?= htmlspecialchars(ZamanDilimiHelper::label($zk), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-end fw-semibold"><?= number_format($zToplam, 0, ',', '.') ?></td>
                                            <td class="text-end text-success"><?= $countWithShare($zYapilan, $zToplam) ?></td>
                                            <td class="text-end text-warning pe-3"><?= $countWithShare($zYapilmayan, $zToplam) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card shadow-sm border-0">
                    <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Yapılmama nedeni (yapılmadı kayıtları)', 'neden', 'h6', 'card-header bg-white py-3 border-bottom'); ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Neden</th>
                                    <th class="text-end pe-3">Adet</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($nedenLabels as $k => $label): ?>
                                    <tr>
                                        <td class="ps-3 small"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-end fw-semibold pe-3"><?= (int) ($nedenMap[$k] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>