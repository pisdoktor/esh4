<?php
/**
 * Dashboard ana sayfa (canonical).
 * Controller: DashboardController::index
 * Değişkenler: $pageTitle, $calendarHtml, $year, $month, $currentMonthName,
 *   $prevMonth, $prevYear, $nextMonth, $nextYear
 */
?>
<div class="esh-page esh-page--dashboard container-fluid py-4 esh-page-dashboard">
    <div class="row g-4">
        <div class="col-lg-8">
            <?php include __DIR__ . '/partials/hasta_ara.php'; ?>
            <?php include __DIR__ . '/partials/takvim.php'; ?>
        </div>
        <div class="col-lg-4">
            <?php include __DIR__ . '/partials/gunun_plani.php'; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/esh_page_config.php'; ?>
