<?php include __DIR__ . '/partials/stats_erapor_list/bootstrap.php'; ?>
<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<div class="container-fluid mt-3 esh-stats-report pb-4">
    <?php require \App\Helpers\ThemeViewHelper::resolvePartial('admin/stats_breadcrumb'); ?>
<?php include __DIR__ . '/partials/stats_erapor_list/report_header.php'; ?>
<?php include __DIR__ . '/partials/stats_erapor_list/filter_card.php'; ?>
<?php include __DIR__ . '/partials/stats_erapor_list/results_table.php'; ?>
</div>
</div>
