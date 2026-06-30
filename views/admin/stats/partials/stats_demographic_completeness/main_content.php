<p class="mb-3">Aktif hasta: <strong><?= $aktif ?></strong> —
        <a href="<?= htmlspecialchars(esh_url('Stats', 'dataHealth'), ENT_QUOTES, 'UTF-8') ?>">Tam veri sağlığı raporu →</a></p>
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body esh-chart-wrap--lg">
            <?php
            $chartId = 'demoCompleteChart';
            $chartLabels = $labels;
            $chartValues = $values;
            $barColor = '#0dcaf0';
            require dirname(__DIR__) . '/chart_hbar.php';
            ?>
        </div>
    </div>
    <div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Demografik eksiklik tablosu', 'main'); ?>
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th class="ps-3">Eksik / hatalı alan</th><th class="text-end">Hasta</th><th class="text-end pe-3">%</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r):
                    $key = (string) ($r->key ?? '');
                    $listUrl = isset($metricMap[$key])
                        ? esh_url('Stats', 'dataHealthPatients', array (
  'metric' => '',
)) . rawurlencode($metricMap[$key])
                        : '';
                ?>
                    <tr>
                        <td class="ps-3"><?= htmlspecialchars((string) ($r->label ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end fw-bold"><?= (int) ($r->adet ?? 0) ?></td>
                        <td class="text-end">%<?= (float) ($r->pct ?? 0) ?></td>
                        <td class="text-end pe-3">
                            <?php if ($listUrl !== '' && (int) ($r->adet ?? 0) > 0): ?>
                                <a class="btn btn-xs btn-outline-secondary btn-sm" href="<?= htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') ?>">Liste</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>