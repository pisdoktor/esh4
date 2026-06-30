<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php include __DIR__ . '/partials/stats_bmi_vki/bootstrap.php'; ?>
<div class="container-fluid mt-3 esh-stats-report">
    <?php require \App\Helpers\ThemeViewHelper::resolvePartial('admin/stats_breadcrumb'); ?>
<?php include __DIR__ . '/partials/stats_bmi_vki/report_header.php'; ?>
<?php include __DIR__ . '/partials/stats_bmi_vki/summary_cards.php'; ?>
<?php include __DIR__ . '/partials/stats_bmi_vki/charts_and_gender_table.php'; ?>
<?php include __DIR__ . '/partials/stats_bmi_vki/age_band_card.php'; ?>
</div>
<?php include __DIR__ . '/partials/stats_bmi_vki/chart_scripts.php'; ?>
</div>
