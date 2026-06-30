<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
        <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($linkCal($prev['y'], $prev['m'], null), ENT_QUOTES, 'UTF-8') ?>">
            <i class="fa-solid fa-chevron-left me-1"></i> Önceki ay
        </a>
        <span class="fw-bold fs-5"><?= htmlspecialchars($monthTitle, ENT_QUOTES, 'UTF-8') ?></span>
        <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($linkCal($next['y'], $next['m'], null), ENT_QUOTES, 'UTF-8') ?>">
            Sonraki ay <i class="fa-solid fa-chevron-right ms-1"></i>
        </a>
    </div>
    <div class="card-body p-3 p-md-4">
        <div class="row g-1 text-center small fw-semibold text-muted mb-1">
            <?php foreach ($weekdayLabels as $wd): ?>
                <div class="col"><?= htmlspecialchars($wd, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
        </div>
        <?php
        $cell = $gridStart;
        for ($week = 0; $week < 6; $week++):
            ?>
        <div class="row g-1">
            <?php
            for ($d = 0; $d < 7; $d++):
                $key = $cell->format('Y-m-d');
                $inMonth = ((int) $cell->format('n') === $m) && ((int) $cell->format('Y') === $y);
                $cnt = (int) ($countsByDay[$key] ?? 0);
                $isSel = $selectedDate !== '' && $key === $selectedDate;
                $isToday = $key === $todayYmd;
                $isWeekend = (int) $cell->format('N') >= 6;
                $cy = (int) $cell->format('Y');
                $cm = (int) $cell->format('n');
                $href = htmlspecialchars($linkCal($cy, $cm, $key), ENT_QUOTES, 'UTF-8');
                $dayNum = (int) $cell->format('j');
                $cellClasses = 'esh-randevu-cal__cell border rounded-3 text-decoration-none h-100 ';
                $cellClasses .= $inMonth ? 'bg-white ' : 'bg-light text-muted ';
                if ($isWeekend) {
                    $cellClasses .= 'esh-randevu-cal__cell--weekend ';
                }
                if ($isSel) {
                    $cellClasses .= 'border-primary border-2 shadow-sm ';
                } else {
                    $cellClasses .= 'border-light-subtle ';
                }
                if ($isToday) {
                    $cellClasses .= 'ring-today ';
                }
                ?>
            <div class="col" style="min-height: 4.65rem;">
                <a class="<?= $cellClasses ?>" href="<?= $href ?>">
                    <span class="esh-randevu-cal__day-num"><?= $dayNum ?></span>
                    <?php if ($cnt > 0): ?>
                        <span class="esh-randevu-cal__day-count"><?= (int) $cnt ?></span>
                    <?php endif; ?>
                </a>
            </div>
                <?php
                $cell = $cell->modify('+1 day');
            endfor;
            ?>
        </div>
            <?php
        endfor;
        ?>
    </div>
</div>
