<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php include __DIR__ . '/partials/stats_visit_consultation_monthly/bootstrap.php'; ?>
<div class="container-fluid mt-3 esh-stats-report pb-4">
    <?php require \App\Helpers\ThemeViewHelper::resolvePartial('admin/stats_breadcrumb'); ?>
<?php include __DIR__ . '/partials/stats_visit_consultation_monthly/report_header.php'; ?>
<?php include __DIR__ . '/partials/stats_visit_consultation_monthly/filter_card.php'; ?>
<?php include __DIR__ . '/partials/stats_visit_consultation_monthly/top_tables.php'; ?>
<?php include __DIR__ . '/partials/stats_visit_consultation_monthly/pair_table.php'; ?>
<?php include __DIR__ . '/partials/stats_visit_consultation_monthly/monthly_table.php'; ?>
<?php include __DIR__ . '/partials/stats_visit_consultation_monthly/scripts.php'; ?>
</div>
</div>
