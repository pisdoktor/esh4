<?php if ($bilinmeyen > 0): ?>
        <p class="alert alert-light border small py-2">Kayıt tarihi bilinmeyen aktif hasta: <strong><?= (int) $bilinmeyen ?></strong> (tablo dışı).</p>
    <?php endif; ?>
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body esh-chart-wrap--280">
            <?php
            $chartId = 'tenureChart';
            $chartLabels = $labels;
            $chartValues = $values;
            $barColor = '#20c997';
            require dirname(__DIR__) . '/chart_hbar.php';
            ?>
        </div>
    </div>
    <div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Kayıt süresi tablosu', 'main'); ?>
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th class="ps-3">Süre</th><th class="text-end">Hasta</th><th class="text-end pe-3">Oran*</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="ps-3"><?= htmlspecialchars((string) ($r->label ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end fw-bold"><?= (int) ($r->adet ?? 0) ?></td>
                        <td class="text-end pe-3">%<?= (float) ($r->pct ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light"><tr><td class="ps-3">Toplam (tarihli)</td><td class="text-end"><?= (int) $total ?></td><td class="pe-3"></td></tr></tfoot>
        </table>
        <div class="card-footer small text-muted">* Oran, kayıt tarihi bilinen aktif hastalar içinde.</div>