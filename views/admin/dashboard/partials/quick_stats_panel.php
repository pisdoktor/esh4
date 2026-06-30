<?php
/**
 * Hızlı istatistikler panel (kart + başlık).
 * @var array<int, array<string, mixed>> $quickStatGroups
 * @var string $innerWrapClass
 * @var string $statsPanelOuterClass
 */
$innerWrapClass = $innerWrapClass ?? 'border rounded-3 p-3 h-100';
$statsPanelOuterClass = $statsPanelOuterClass ?? 'card shadow-sm border-0 h-100';
?>
<div class="<?= htmlspecialchars($statsPanelOuterClass, ENT_QUOTES, 'UTF-8') ?>">
    <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h6 class="m-0 fw-bold text-dark"><i class="fa-solid fa-chart-simple me-2"></i>Hızlı istatistikler</h6>
        <a href="<?= htmlspecialchars(esh_url('Stats', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Tüm raporlar
        </a>
    </div>
    <div class="card-body">
        <?php require __DIR__ . '/quick_stats_panel_body.php'; ?>
    </div>
</div>
