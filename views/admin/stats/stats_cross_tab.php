<?php /** @var array $report */ ?>
<?php /** @var int $months */ ?>
<?php /** @var string $tabId */ ?>
<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php include __DIR__ . '/partials/stats_cross_tab/bootstrap.php'; ?>
<div class="container-fluid mt-3 esh-stats-report">
    <?php require \App\Helpers\ThemeViewHelper::resolvePartial('admin/stats_breadcrumb'); ?>
<?php include __DIR__ . '/partials/stats_cross_tab/report_header.php'; ?>
<?php include __DIR__ . '/partials/stats_cross_tab/period_filter.php'; ?>
<?php include __DIR__ . '/partials/stats_cross_tab/matrix_section.php'; ?>
</div>
</div>
