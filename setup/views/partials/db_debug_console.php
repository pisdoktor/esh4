<?php
/**
 * DB_DEBUG açıkken sayfa altında SQL konsolu (süre + bellek).
 * Dahil: footer partial'ları.
 */
$db = \App\Core\Database::getInstance();
if (!$db->isDebug()) {
    return;
}
$logs = $db->getQueryLog();
$thresholdMs = $db->getSlowQueryThresholdMs();
$slowCount = 0;
$totalMs = 0.0;
foreach ($logs as $entry) {
    if (!is_array($entry)) {
        continue;
    }
    $totalMs += (float) ($entry['duration_ms'] ?? 0);
    if (!empty($entry['slow'])) {
        $slowCount++;
    }
}
?>
    <div class="container-fluid mt-5">
        <div class="card border-danger mb-4 shadow">
            <div class="card-header bg-danger d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0"><i class="fa-solid fa-bug me-2"></i> SQL Debug Console</h6>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-white text-danger"><?= count($logs) ?> sorgu</span>
                    <span class="badge bg-white text-dark" title="Toplam SQL süresi"><?= number_format($totalMs, 2) ?> ms SQL</span>
                    <?php if ($slowCount > 0): ?>
                        <span class="badge bg-warning text-dark" title="Eşik: <?= (int) $thresholdMs ?> ms — logs/db_slow_queries.log">
                            <?= $slowCount ?> yavaş sorgu
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body bg-dark p-0">
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-dark table-striped table-hover mb-0 small">
                        <thead>
                            <tr>
                                <th width="50" class="text-center">#</th>
                                <th width="90" class="text-end">Süre</th>
                                <th width="80" class="text-end">Bellek</th>
                                <th>Sorgu (SQL)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $index => $entry):
                                $sql = is_array($entry) ? (string) ($entry['sql'] ?? '') : (string) $entry;
                                $durationMs = is_array($entry) ? ($entry['duration_ms'] ?? null) : null;
                                $memoryMb = is_array($entry) ? ($entry['memory_mb'] ?? null) : null;
                                $isSlow = is_array($entry) && !empty($entry['slow']);
                                $durationClass = 'text-success';
                                if ($durationMs !== null) {
                                    if ($durationMs >= $thresholdMs) {
                                        $durationClass = 'text-danger fw-bold';
                                    } elseif ($durationMs >= $thresholdMs / 2) {
                                        $durationClass = 'text-warning';
                                    }
                                }
                            ?>
                                <tr<?= $isSlow ? ' class="table-warning"' : '' ?>>
                                    <td class="text-center text-muted"><?= $index + 1 ?></td>
                                    <td class="text-end <?= htmlspecialchars($durationClass, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= $durationMs !== null ? number_format((float) $durationMs, 2) . ' ms' : '—' ?>
                                    </td>
                                    <td class="text-end text-muted">
                                        <?= $memoryMb !== null ? number_format((float) $memoryMb, 2) . ' MB' : '—' ?>
                                    </td>
                                    <td>
                                        <code class="text-info" style="word-break: break-all;">
                                            <?= htmlspecialchars($sql, ENT_QUOTES, 'UTF-8'); ?>
                                        </code>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light py-1 small text-muted">
                <strong>Hafıza (şu an):</strong> <?= round(memory_get_usage(true) / 1024 / 1024, 2) ?> MB
                · <strong>Yavaş sorgu eşiği:</strong> <?= (int) $thresholdMs ?> ms
                <?php if ($slowCount > 0): ?>
                    · <strong>Günlük:</strong> <code>logs/db_slow_queries.log</code>
                <?php endif; ?>
            </div>
        </div>
    </div>
