    <div class="card shadow-sm border-0 mb-3">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('<i class="fa-solid fa-chart-column me-2 text-success"></i>Aylık kayıt grafiği', 'grafik', 'h6', 'card-header bg-white py-3'); ?>
        <div class="card-body esh-chart-wrap--360">
            <?php if (empty($chartLabels) || $sumToplam === 0): ?>
                <p class="text-muted small mb-0">Grafik için yeterli kayıt verisi bulunamadı.</p>
            <?php else: ?>
                <canvas id="kayitMonthsChart"></canvas>
            <?php endif; ?>
        </div>
    </div>