<div class="card">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <strong><i class="fa fa-calendar-check me-1"></i><?= $pageTitle; ?></strong>
        <div class="btn-group" id="dashboard-calendar-nav">
            <a href="<?= htmlspecialchars(esh_url('Dashboard', 'index', ['year' => $prevYear, 'month' => $prevMonth]), ENT_QUOTES, 'UTF-8') ?>"
               class="btn btn-sm btn-outline-secondary"
               data-esh-calendar-nav="prev"
               data-year="<?= (int) $prevYear ?>"
               data-month="<?= (int) $prevMonth ?>"><i class="fa fa-chevron-left"></i></a>
            <span class="btn btn-sm btn-secondary" id="dashboard-calendar-month-label"><?= $currentMonthName . ' ' . $year ?></span>
            <a href="<?= htmlspecialchars(esh_url('Dashboard', 'index', ['year' => $nextYear, 'month' => $nextMonth]), ENT_QUOTES, 'UTF-8') ?>"
               class="btn btn-sm btn-outline-secondary"
               data-esh-calendar-nav="next"
               data-year="<?= (int) $nextYear ?>"
               data-month="<?= (int) $nextMonth ?>"><i class="fa fa-chevron-right"></i></a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" id="dashboard-calendar-container"><?= $calendarHtml ?></div>
    </div>
    <div class="card-footer py-2">
        <?php include \App\Helpers\ThemeViewHelper::resolvePartial('site/dashboard_calendar_legend'); ?>
    </div>
</div>
