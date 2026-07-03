<section class="au-panel au-panel--calendar">
    <div class="au-panel__head au-panel__head--split">
        <div class="d-flex align-items-center gap-2">
            <span class="au-icon-chip au-icon-chip--soft"><i class="fa-solid fa-calendar-check"></i></span>
            <h2 class="au-panel__title mb-0"><?= $pageTitle; ?></h2>
        </div>
        <div class="au-cal-nav" id="dashboard-calendar-nav">
            <a href="<?= htmlspecialchars(esh_url('Dashboard', 'index', ['year' => $prevYear, 'month' => $prevMonth]), ENT_QUOTES, 'UTF-8') ?>"
               class="au-cal-nav__btn" data-esh-calendar-nav="prev"
               data-year="<?= (int) $prevYear ?>" data-month="<?= (int) $prevMonth ?>" aria-label="Önceki ay">
                <i class="fa fa-chevron-left"></i>
            </a>
            <span class="au-cal-nav__label" id="dashboard-calendar-month-label"><?= $currentMonthName . ' ' . $year ?></span>
            <a href="<?= htmlspecialchars(esh_url('Dashboard', 'index', ['year' => $nextYear, 'month' => $nextMonth]), ENT_QUOTES, 'UTF-8') ?>"
               class="au-cal-nav__btn" data-esh-calendar-nav="next"
               data-year="<?= (int) $nextYear ?>" data-month="<?= (int) $nextMonth ?>" aria-label="Sonraki ay">
                <i class="fa fa-chevron-right"></i>
            </a>
        </div>
    </div>
    <div class="au-panel__body p-0">
        <div class="table-responsive" id="dashboard-calendar-container">
            <?= $calendarHtml ?>
        </div>
    </div>
    <div class="au-panel__foot">
        <?php include \App\Helpers\ThemeViewHelper::resolvePartial('site/dashboard_calendar_legend'); ?>
    </div>
</section>
