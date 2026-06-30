<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php include __DIR__ . '/partials/kayit_months/bootstrap.php'; ?>
<div class="container-fluid mt-3 esh-stats-report">
    <?php require \App\Helpers\ThemeViewHelper::resolvePartial('admin/stats_breadcrumb'); ?>
<?php include __DIR__ . '/partials/kayit_months/report_header.php'; ?>
<?php include __DIR__ . '/partials/kayit_months/summary_cards.php'; ?>
<?php include __DIR__ . '/partials/kayit_months/chart_card.php'; ?>
<?php include __DIR__ . '/partials/kayit_months/detail_table.php'; ?>
</div>
<?php if (!empty($chartLabels) && $sumToplam > 0): ?>
<?php include __DIR__ . '/partials/kayit_months/chart_scripts.php'; ?>
<?php endif; ?>
</div>
