<div class="card shadow-sm border-0 mb-3">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Grafik', 'grafik', 'h6', 'card-header bg-white py-3'); ?>
        <div class="card-body esh-chart-wrap--280">
            <?php
            $chartId = 'bagimlilikChart';
            $chartLabels = $labels;
            $chartValues = $values;
            $barColor = '#6f42c1';
            require dirname(__DIR__) . '/chart_hbar.php';
            ?>
        </div>
    </div>
    <div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Tablo', 'main'); ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th class="ps-3">Bağımlılık</th><th class="text-end">Hasta</th><th class="text-end pe-3">Oran</th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $r):
                        $adet = (int) ($r->adet ?? 0);
                        $pct = $total > 0 ? round($adet / $total * 100, 1) : 0;
                    ?>
                        <tr>
                            <td class="ps-3"><?= htmlspecialchars((string) ($r->label ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end fw-bold"><?= $adet ?></td>
                            <td class="text-end pe-3 text-muted">%<?= $pct ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light"><tr><th class="ps-3">Toplam (aktif)</th><th class="text-end"><?= (int) $total ?></th><th class="pe-3"></th></tr></tfoot>
            </table>