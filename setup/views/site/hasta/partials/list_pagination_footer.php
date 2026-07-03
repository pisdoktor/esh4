<div class="esh-patient-list-footer">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="small text-muted">
                <?= \App\Helpers\PaginationHelper::infoText($totalPatients, $page, $limit) ?>
            </div>
            <div>
                <?= \App\Helpers\PaginationHelper::limitSelector($limit, $pagelink) ?>
            </div>
        </div>
        <div>
            <?= \App\Helpers\PaginationHelper::render($totalPatients, $page, $limit, $pagelink) ?>
        </div>
    </div>
</div>
