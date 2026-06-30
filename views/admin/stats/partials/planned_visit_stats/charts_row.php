        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 h-100">
                    <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Aylık dağılım (planlanan tarih)', 'grafik', 'h6', 'card-header bg-white py-3 border-bottom'); ?>
                    <div class="card-body esh-chart-wrap--280">
                        <?php if ($chartLabels !== []): ?>
                            <canvas id="plannedVisitMonthChart"></canvas>
                        <?php else: ?>
                            <p class="text-muted small mb-0">Aylık veri yok.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card shadow-sm border-0 h-100">
                    <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Durum özeti', 'durum', 'h6', 'card-header bg-white py-3 border-bottom'); ?>
                    <div class="card-body d-flex flex-column justify-content-center">
                        <ul class="list-unstyled mb-3">
                            <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span><i class="fa-solid fa-circle-check text-success me-2"></i>Tamamlanan (yapıldı)</span>
                                <strong class="text-success"><?= number_format($tamamlanan, 0, ',', '.') ?></strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span><i class="fa-solid fa-hourglass-half text-warning me-2"></i>Bekleyen (yapılmadı)</span>
                                <strong class="text-warning"><?= number_format($bekleyen, 0, ',', '.') ?></strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-2">
                                <span><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>Gecikmiş bekleyen</span>
                                <strong class="text-danger"><?= number_format($gecikmis, 0, ',', '.') ?></strong>
                            </li>
                        </ul>
                        <?php if ($chartLabels !== []): ?>
                            <div class="esh-chart-wrap--sm">
                                <canvas id="plannedVisitDurumChart"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>