<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php include __DIR__ . '/partials/planned_visit_stats/bootstrap.php'; ?>
<div class="container-fluid mt-3 esh-stats-report">
    <?php require \App\Helpers\ThemeViewHelper::resolvePartial('admin/stats_breadcrumb'); ?>
<?php include __DIR__ . '/partials/planned_visit_stats/report_header.php'; ?>
    <?php require dirname(__DIR__, 2) . '/partials/admin/stats_page_intro.php'; ?>
    <?php require dirname(__DIR__, 2) . '/partials/admin/stats_date_range_filters.php'; ?>
    <?php if (!$ok): ?>
        <div class="alert alert-warning">Planlı izlem verisi okunamadı.</div>
    <?php elseif ($toplam === 0): ?>
        <div class="alert alert-info mb-0">Seçilen dönemde (planlanan tarihe göre) aktif hastaya bağlı planlı izlem kaydı yok.</div>
    <?php else: ?>
<?php include __DIR__ . '/partials/planned_visit_stats/summary_cards.php'; ?>
<?php include __DIR__ . '/partials/planned_visit_stats/charts_row.php'; ?>
<?php include __DIR__ . '/partials/planned_visit_stats/tables_row.php'; ?>
<?php include __DIR__ . '/partials/planned_visit_stats/footnote.php'; ?>
    <?php endif; ?>
</div>
<?php if ($ok && $toplam > 0 && $chartLabels !== []): ?>
<?php include __DIR__ . '/partials/planned_visit_stats/chart_scripts.php'; ?>
<?php endif; ?>
</div>
