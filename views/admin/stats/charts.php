<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php include __DIR__ . '/partials/charts/bootstrap.php'; ?>
<div class="container-fluid mt-4 esh-stats-report">
    <?php require \App\Helpers\ThemeViewHelper::resolvePartial('admin/stats_breadcrumb'); ?>
<?php include __DIR__ . '/partials/charts/report_header.php'; ?>
<?php include __DIR__ . '/partials/charts/categories_chart_row.php'; ?>
<?php include __DIR__ . '/partials/charts/top_tani_table.php'; ?>
</div>
<?php include __DIR__ . '/partials/charts/chart_scripts.php'; ?>
</div>
