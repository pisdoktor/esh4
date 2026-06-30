<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php include __DIR__ . '/partials/ay_movement/bootstrap.php'; ?>
<div class="container-fluid mt-3 esh-stats-report pb-4">
    <?php require \App\Helpers\ThemeViewHelper::resolvePartial('admin/stats_breadcrumb'); ?>
<?php include __DIR__ . '/partials/ay_movement/report_header.php'; ?>
<?php include __DIR__ . '/partials/ay_movement/filter_card.php'; ?>
<?php include __DIR__ . '/partials/ay_movement/summary_cards.php'; ?>
<?php include __DIR__ . '/partials/ay_movement/charts_row.php'; ?>
<?php include __DIR__ . '/partials/ay_movement/movement_summary.php'; ?>
</div>
<?php include __DIR__ . '/partials/ay_movement/scripts.php'; ?>
</div>
