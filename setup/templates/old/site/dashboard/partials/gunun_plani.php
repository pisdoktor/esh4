<div class="card sticky-top mr-dash-plan d-flex flex-column" style="top: 20px; min-height: 600px; max-height: 90vh;">
    <div class="card-header py-2 d-flex flex-wrap align-items-start justify-content-between gap-2">
        <div>
            <strong>Günün Planı</strong>
            <div class="small text-muted" id="selected-date-label"><?= date('d-m-Y') ?></div>
        </div>
        <?php if (\App\Helpers\AuthHelper::sessionIsAdmin()): ?>
        <button type="button" class="btn btn-outline-danger btn-sm flex-shrink-0" id="btn-daily-plan-mernis-scan" title="Seçili gündeki planlı hastalar için MERNİS vefat taraması" aria-label="MERNİS vefat taraması">
            <i class="fa-solid fa-heart-pulse me-1"></i>MERNİS
        </button>
        <?php endif; ?>
    </div>
    <div id="daily-events-tabs" class="mr-dash-plan__tabs px-2 d-none" aria-hidden="true"></div>
    <div class="card-body p-2 mr-dash-plan__scroll" id="daily-events-body">
        <div id="daily-events-plan">
            <div class="text-center py-4 text-muted" id="daily-events-placeholder">
                <p class="small mb-0">Detayları görmek için takvimden bir gün seçiniz.</p>
            </div>
        </div>
    </div>
    <?php if (\App\Helpers\AuthHelper::sessionIsAdmin()): ?>
    <div class="card-footer p-2 d-none" id="route-button-container">
        <a href="#" id="view-route-btn" class="btn btn-primary w-100 btn-sm">
            <i class="fa fa-map-marked-alt me-1"></i>Günün Rotasını Çiz
        </a>
    </div>
    <?php endif; ?>
</div>
