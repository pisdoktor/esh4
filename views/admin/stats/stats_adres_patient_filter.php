<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php include __DIR__ . '/partials/stats_adres_patient_filter/bootstrap.php'; ?>
<div class="container-fluid mt-3 esh-stats-report esh-stats-adres-filter-page pb-4">
    <?php require \App\Helpers\ThemeViewHelper::resolvePartial('admin/stats_breadcrumb'); ?>
<?php include __DIR__ . '/partials/stats_adres_patient_filter/report_header.php'; ?>
<?php include __DIR__ . '/partials/stats_adres_patient_filter/filter_form.php'; ?>
<?php include __DIR__ . '/partials/stats_adres_patient_filter/results_table.php'; ?>
    </form>
</div>
</div>
