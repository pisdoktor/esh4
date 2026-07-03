<p class="mb-3">Hasta başına ortalama tanı: <span class="badge bg-danger-subtle text-danger fs-6"><?= htmlspecialchars((string) $ortalama, ENT_QUOTES, 'UTF-8') ?></span></p>
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body esh-chart-wrap--280">
            <?php
            $chartId = 'hastalikCountChart';
            $chartLabels = $labels;
            $chartValues = $values;
            $barColor = '#dc3545';
            require dirname(__DIR__) . '/chart_hbar.php';
            ?>
        </div>
    </div>
    <div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Tanı sayısı tablosu', 'main'); ?>
        <table class="table mb-0">
            <thead class="table-light"><tr><th class="ps-3">Grup</th><th class="text-end">Hasta</th><th class="text-end pe-3">%</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="ps-3"><?= htmlspecialchars((string) ($r->label ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end fw-bold"><?= (int) ($r->adet ?? 0) ?></td>
                        <td class="text-end pe-3">%<?= (float) ($r->pct ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light"><tr><th class="ps-3">Aktif toplam</th><th class="text-end"><?= (int) $total ?></th><th class="pe-3"></th></tr></tfoot>
        </table>