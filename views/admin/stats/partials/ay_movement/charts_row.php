    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Aktif cinsiyet dağılımı (güncel)', 'grafik', 'h6', 'card-header bg-white'); ?>
                <div class="card-body esh-chart-wrap--200">
                    <canvas id="ayCinsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader(htmlspecialchars($period_label ?? '', ENT_QUOTES, 'UTF-8') . ': yeni kayıt vs takipten çıkan', 'hareket_grafik', 'h6', 'card-header bg-white'); ?>
                <div class="card-body esh-chart-wrap--200">
                    <canvas id="ayHareketChart"></canvas>
                </div>
            </div>
        </div>
    </div>