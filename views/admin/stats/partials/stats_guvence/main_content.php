<div class="card shadow-sm border-0 mb-3">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('<i class="fa-solid fa-chart-column me-2 text-success"></i>Güvence dağılım grafiği', 'grafik', 'h6', 'card-header bg-white py-3'); ?>
        <div class="card-body esh-chart-wrap--lg">
            <?php if (empty($chartValues) || array_sum($chartValues) === 0): ?>
                <p class="text-muted small mb-0">Grafik için yeterli güvence verisi bulunamadı.</p>
            <?php else: ?>
                <canvas id="guvenceDistChart"></canvas>
            <?php endif; ?>
        </div>
    </div>
    <div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Güvence tablosu', 'main'); ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th class="ps-3">Güvence</th><th class="text-end pe-3">Hasta</th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td class="ps-3"><?= htmlspecialchars((string) ($r->guvence_adi ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end pe-3 fw-bold"><?= (int) ($r->hastasayisi ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="2" class="text-center text-muted py-4">Kayıt yok</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>