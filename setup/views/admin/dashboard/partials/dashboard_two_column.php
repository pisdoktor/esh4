<?php
/**
 * Yönetim paneli ana gövde: sol hızlı kısayollar, sağ hızlı istatistikler.
 *
 * @var array<int, array<string, string>> $quickCards
 * @var array<int, array<string, mixed>> $quickStatGroups
 * @var string|null $quickStatsPanelPath
 */
$shortcutCardClass = $shortcutCardClass ?? 'card h-100 shadow-sm border-0';
$shortcutItemColClass = $shortcutItemColClass ?? 'col-12';
$shortcutsPanelOuterClass = $shortcutsPanelOuterClass ?? 'card shadow-sm border-0 h-100';
$statsPanelOuterClass = $statsPanelOuterClass ?? 'card shadow-sm border-0 h-100';
$innerWrapClass = $innerWrapClass ?? 'border rounded-3 p-3 h-100';
$statsGroupColClass = $statsGroupColClass ?? 'col-12';
$quickStatsPanelPath = $quickStatsPanelPath ?? __DIR__ . '/quick_stats_panel.php';
?>
<div class="row g-4 align-items-stretch esh-admin-dashboard-two-col mb-4">
    <div class="col-lg-6">
        <?php require __DIR__ . '/quick_shortcuts_panel.php'; ?>
    </div>
    <div class="col-lg-6">
        <?php require $quickStatsPanelPath; ?>
    </div>
</div>
