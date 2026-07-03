<div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Aktif hasta</div><div class="fs-3 fw-bold"><?= $aktif ?></div></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">VKİ hesaplanabilir</div><div class="fs-3 fw-bold text-success"><?= (int) ($r['computable_bmi'] ?? 0) ?></div><div class="small text-muted">%<?= (float) ($r['pct_computable'] ?? 0) ?></div></div></div></div>
        <div class="col-md-4"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted small">Detay rapor</div><a class="btn btn-sm btn-outline-primary mt-1" href="<?= htmlspecialchars(esh_url('Stats', 'bmiVki'), ENT_QUOTES, 'UTF-8') ?>">VKİ dağılımı →</a></div></div></div>
    </div>
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body esh-chart-wrap--260">
            <?php
            $chartId = 'anthroChart';
            $chartLabels = array_column($rows, 0);
            $chartValues = array_column($rows, 1);
            $barColor = '#ffc107';
            require dirname(__DIR__) . '/chart_hbar.php';
            ?>
        </div>
    </div>
    <div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Antropometri kapsam tablosu', 'main'); ?>
        <table class="table mb-0">
            <thead class="table-light"><tr><th class="ps-3">Kriter</th><th class="text-end">Hasta</th><th class="text-end pe-3">Aktif içinde %</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="ps-3"><?= htmlspecialchars($row[0], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end fw-bold"><?= $row[1] ?></td>
                        <td class="text-end pe-3">%<?= $row[2] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>