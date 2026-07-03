<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold text-primary">
            <i class="fa fa-calendar-check me-2"></i><?= $pageTitle; ?>
        </h5>
        <div class="btn-group shadow-sm" id="dashboard-calendar-nav">
            <a href="<?= htmlspecialchars(esh_url('Dashboard', 'index', ['year' => $prevYear, 'month' => $prevMonth]), ENT_QUOTES, 'UTF-8') ?>"
               class="btn btn-sm btn-outline-primary"
               data-esh-calendar-nav="prev"
               data-year="<?= (int) $prevYear ?>"
               data-month="<?= (int) $prevMonth ?>">
                <i class="fa fa-chevron-left"></i>
            </a>
            <span class="btn btn-sm btn-primary fw-bold px-3" id="dashboard-calendar-month-label">
                <?= $currentMonthName . ' ' . $year ?>
            </span>
            <a href="<?= htmlspecialchars(esh_url('Dashboard', 'index', ['year' => $nextYear, 'month' => $nextMonth]), ENT_QUOTES, 'UTF-8') ?>"
               class="btn btn-sm btn-outline-primary"
               data-esh-calendar-nav="next"
               data-year="<?= (int) $nextYear ?>"
               data-month="<?= (int) $nextMonth ?>">
                <i class="fa fa-chevron-right"></i>
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" id="dashboard-calendar-container">
            <?= $calendarHtml ?>
        </div>
    </div>
    <div class="card-footer bg-light border-top-0 py-3">
        <?php include \App\Helpers\ThemeViewHelper::resolvePartial('site/dashboard_calendar_legend'); ?>
    </div>
</div>
