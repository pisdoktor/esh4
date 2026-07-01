<?php $scale = 'mna'; ?>
<?php include __DIR__ . '/partials/stats_clinical_scale/bootstrap.php'; ?>
<div class="container-fluid mt-3 esh-stats-report">
    <?php require \App\Helpers\ThemeViewHelper::resolvePartial('admin/stats_breadcrumb'); ?>
<?php include __DIR__ . '/partials/stats_clinical_scale/report_header.php'; ?>
<?php include __DIR__ . '/partials/stats_clinical_scale/main_content.php'; ?>
</div>
<?php include __DIR__ . '/partials/stats_clinical_scale/scripts.php'; ?>
</div>
