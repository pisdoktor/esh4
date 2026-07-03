<p class="small text-muted mb-3">Aktif hasta: <strong><?= $aktif ?></strong></p>
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Dolu hasta sayısı</h6></div>
        <div class="card-body" style="height: min(360px, <?= max(220, count($labels) * 36) ?>px);">
            <?php
            $chartId = 'fieldCoverageChart';
            $chartLabels = $labels;
            $chartValues = $doluValues;
            $barColor = '#6c757d';
            require dirname(__DIR__) . '/chart_hbar.php';
            ?>
        </div>
    </div>
    <div class="card shadow-sm border-0">
        <?php \App\Helpers\StatsViewPdfHelper::renderCardHeader('Alan doluluk tablosu', 'main'); ?>
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Alan</th>
                    <th class="text-end">Dolu</th>
                    <th class="text-end">Boş</th>
                    <th class="text-end pe-3">Doluluk %</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r):
                    $isPlaceholder = !empty($r->placeholder);
                    $listMetric = trim((string) ($r->list_metric ?? ''));
                    $dolu = (int) ($r->dolu ?? 0);
                    $listUrl = ($listMetric !== '' && $dolu > 0)
                        ? esh_url('Stats', 'fieldCoveragePatients', array (
  'metric' => '',
)) . rawurlencode($listMetric)
                        : '';
                ?>
                    <tr<?= $isPlaceholder ? ' class="table-warning"' : '' ?>>
                        <td class="ps-3"><?= htmlspecialchars((string) ($r->label ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end fw-bold <?= $isPlaceholder ? 'text-warning-emphasis' : 'text-success' ?>">
                            <?php if ($listUrl !== ''): ?>
                                <a href="<?= htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none <?= $isPlaceholder ? 'text-warning-emphasis' : 'text-success' ?>" title="Hasta listesini aç"><?= $dolu ?></a>
                            <?php else: ?>
                                <?= $dolu ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-muted"><?= $isPlaceholder ? '—' : (int) ($r->bos ?? 0) ?></td>
                        <td class="text-end pe-3">%<?= (float) ($r->pct ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="card-footer small text-muted">
            Anne/baba adında yalnızca «.», «..» veya «...» olan kayıtlar ayrı satırda gösterilir; «Anne adı» / «Baba adı» satırları bu placeholder değerleri içermez.
            <?php
            $showHerhangiLink = false;
            foreach ($rows as $rr) {
                if (!empty($rr->placeholder) && (int) ($rr->dolu ?? 0) > 0) {
                    $showHerhangiLink = true;
                    break;
                }
            }
            if ($showHerhangiLink): ?>
                · <a href="<?= htmlspecialchars(esh_url('Stats', 'fieldCoveragePatients', array (
  'metric' => 'herhangi',
)), ENT_QUOTES, 'UTF-8') ?>">Anne veya baba placeholder — tüm liste</a>
            <?php endif; ?>