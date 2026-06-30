<?php include __DIR__ . '/partials/operations_pulse/bootstrap.php'; ?>
<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<div class="container-fluid mt-4 esh-stats-report">
    <?php require \App\Helpers\ThemeViewHelper::resolvePartial('admin/stats_breadcrumb'); ?>
<?php include __DIR__ . '/partials/operations_pulse/report_header.php'; ?>
<?php include __DIR__ . '/partials/operations_pulse/summary_cards.php'; ?>
<?php include __DIR__ . '/partials/operations_pulse/charts_row.php'; ?>
<?php include __DIR__ . '/partials/operations_pulse/ranking_tables.php'; ?>
<?php include __DIR__ . '/partials/operations_pulse/brans_table.php'; ?>
    </div>
<?php include __DIR__ . '/partials/operations_pulse/chart_scripts.php'; ?>
</div>
