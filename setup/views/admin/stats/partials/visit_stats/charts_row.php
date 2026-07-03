        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0 h-100">
                    <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Aylık dağılım (izlem tarihi)', 'grafik', 'h6', 'card-header bg-white py-3 border-bottom'); ?>
                    <div class="card-body esh-chart-wrap--280">
                        <?php if ($chartLabels !== []): ?>
                            <canvas id="visitMonthChart"></canvas>
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
                                <span><i class="fa-solid fa-circle-check text-success me-2"></i>Yapıldı</span>
                                <strong class="text-success"><?= $countWithShare($yapilan, $toplam) ?></strong>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-2">
                                <span><i class="fa-solid fa-hourglass-half text-warning me-2"></i>Yapılmadı</span>
                                <strong class="text-warning"><?= $countWithShare($yapilmayan, $toplam) ?></strong>
                            </li>
                        </ul>
                        <?php if ($toplam > 0): ?>
                            <div class="esh-chart-wrap--sm">
                                <canvas id="visitDurumChart"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>