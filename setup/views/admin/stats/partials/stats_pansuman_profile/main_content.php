<p class="mb-3">Aktif pansuman hastası: <strong><?= $total ?></strong> —
        Gün bilgisi dolu: <strong><?= (int) ($report['pansuman_gunlu'] ?? 0) ?></strong>,
        belirsiz: <strong><?= (int) ($report['gun_belirsiz'] ?? 0) ?></strong></p>
    <div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Ziyaret zaman dilimi (pzaman)', 'grafik'); ?>
        <div class="card-body esh-chart-wrap--260">
            <?php
            $chartId = 'pansumanZamanChart';
            $chartLabels = $labels;
            $chartValues = $values;
            $barColor = '#198754';
            require dirname(__DIR__) . '/chart_hbar.php';
            ?>