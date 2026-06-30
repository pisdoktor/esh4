    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('<i class="fa-solid fa-folder-tree me-2 text-primary"></i>Tanı kategorileri (aktif hasta)', 'tablo'); ?>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 320px;">
                        <table class="table table-sm table-hover mb-0 align-middle w-100 esh-stats-cat-table">
                            <thead class="table-light sticky-top">
                                <tr class="small text-muted">
                                    <th class="ps-3 py-2">Kategori</th>
                                    <th class="text-center px-3 py-2">Hastası olan tanı</th>
                                    <th class="text-end pe-3 py-2">Hasta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($categories === []): ?>
                                    <tr><td colspan="3" class="text-center text-muted small py-4">Kategori kaydı yok.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $cat):
                                        $hastaSay = (int) ($cat->hasta_sayisi ?? 0);
                                        $taniSay = (int) ($cat->tani_kayitli_sayisi ?? 0);
                                        $oran = $totalAktif > 0 ? round(100 * $hastaSay / $totalAktif, 1) : 0.0;
                                        ?>
                                        <tr>
                                            <td class="ps-3 small fw-semibold"><?= htmlspecialchars((string) ($cat->cat_name ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="text-center px-3 small"><?= number_format($taniSay, 0, ',', '.') ?></td>
                                            <td class="text-end pe-3 small">
                                                <span class="fw-bold"><?= number_format($hastaSay, 0, ',', '.') ?></span>
                                                <?php if ($totalAktif > 0 && $hastaSay > 0): ?>
                                                    <span class="text-muted">(%<?= htmlspecialchars((string) $oran, ENT_QUOTES, 'UTF-8') ?>)</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('<i class="fa-solid fa-notes-medical me-2 text-danger"></i>En sık tanılar (üst ' . count($hastalikLabels) . ')', 'grafik'); ?>
                <div class="card-body esh-chart-wrap--lg">
                    <?php if (empty($hastalikValues) || array_sum($hastalikValues) === 0): ?>
                        <p class="text-muted small mb-0">Hastalık alanı dolu aktif hasta kaydı yok veya veri yetersiz.</p>
                    <?php else: ?>
                        <canvas id="statsHastalikChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>