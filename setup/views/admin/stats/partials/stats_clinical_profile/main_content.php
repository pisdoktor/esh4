<p class="small text-muted">Aktif hasta: <strong><?= $aktif ?></strong> — NG, sonda, O₂ vb. bayraklar (bir hasta birden fazla işaret taşıyabilir).
        <a href="<?= htmlspecialchars(esh_url('Stats', 'specialDevices'), ENT_QUOTES, 'UTF-8') ?>">Cihaz listesi →</a></p>
    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Cihaz / özel durum (≥1 işaret)', 'grafik'); ?>
                <div class="card-body" style="height: min(400px, <?= max(220, count($fLabels) * 32) ?>px);">
                    <?php
                    $chartId = 'clinicalFlagsChart';
                    $chartLabels = $fLabels;
                    $chartValues = $fValues;
                    $barColor = '#fd7e14';
                    require dirname(__DIR__) . '/chart_hbar.php';
                    ?>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Hasta başına işaret sayısı', 'isaret_grafik'); ?>
                <div class="card-body esh-chart-wrap--280">
                    <?php
                    $chartId = 'clinicalMultiChart';
                    $chartLabels = $mLabels;
                    $chartValues = $mValues;
                    $barColor = '#6c757d';
                    require dirname(__DIR__) . '/chart_hbar.php';
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Klinik işaret tablosu', 'tablo'); ?>
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th class="ps-3">İşaret</th><th class="text-end pe-3">Hasta</th></tr></thead>
            <tbody>
                <?php foreach ($flags as $f): ?>
                    <tr>
                        <td class="ps-3"><?= htmlspecialchars((string) ($f->label ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end pe-3 fw-bold"><?= (int) ($f->adet ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>