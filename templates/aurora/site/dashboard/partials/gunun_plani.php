<aside class="au-panel au-dash-plan d-flex flex-column" id="au-dash-plan">
    <div class="au-panel__head au-dash-plan__head">
        <span class="au-icon-chip au-icon-chip--amber"><i class="fa fa-list-check"></i></span>
        <div class="flex-grow-1 d-flex flex-wrap align-items-start justify-content-between gap-2">
            <div>
                <h2 class="au-panel__title mb-0">Günün Planı</h2>
                <small class="text-muted fw-semibold" id="selected-date-label">
                    <i class="fa fa-clock me-1"></i><?= date('d-m-Y') ?>
                </small>
            </div>
            <?php if (\App\Helpers\AuthHelper::sessionIsAdmin()): ?>
            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill flex-shrink-0" id="btn-daily-plan-mernis-scan" title="Seçili gündeki planlı hastalar için MERNİS vefat taraması" aria-label="MERNİS vefat taraması">
                <i class="fa-solid fa-heart-pulse me-1"></i><span class="d-none d-md-inline">MERNİS</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div id="daily-events-tabs" class="au-dash-plan__tabs d-none" aria-hidden="true"></div>
    <div class="au-panel__body au-dash-plan__scroll" id="daily-events-body">
        <div id="daily-events-plan">
            <div class="text-center py-5 text-muted" id="daily-events-placeholder">
                <i class="fa fa-hand-pointer d-block mb-3 fs-1 opacity-25"></i>
                <p class="small mb-0">Detayları görmek için takvimden<br>bir gün seçiniz.</p>
            </div>
        </div>
    </div>

    <?php if (\App\Helpers\AuthHelper::sessionIsAdmin()): ?>
    <div class="au-dash-plan__foot d-none" id="route-button-container">
        <a href="#" id="view-route-btn" class="btn btn-primary w-100 py-2 fw-semibold rounded-pill">
            <i class="fa fa-map-marked-alt me-2"></i>GÜNÜN ROTASINI ÇİZ
        </a>
    </div>
    <?php endif; ?>
</aside>
