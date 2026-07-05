<div class="card shadow-sm border-0 h-100 sticky-top mr-dash-plan d-flex flex-column" style="top: 20px; min-height: 600px; max-height: 90vh;">
    <div class="card-header bg-white py-3 border-bottom-0 mr-dash-plan__head">
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
                <div class="bg-warning-subtle p-2 rounded-3 text-warning">
                    <i class="fa fa-list-check fs-4"></i>
                </div>
            </div>
            <div class="flex-grow-1 ms-3 d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Günün Planı</h6>
                    <small class="text-muted fw-bold" id="selected-date-label">
                        <i class="fa fa-clock me-1"></i><?= date('d-m-Y') ?>
                    </small>
                </div>
                <?php if (\App\Helpers\AuthHelper::sessionIsAdmin()): ?>
                <div class="d-flex gap-1 flex-shrink-0">
                <?php if (\App\Services\Sms\SmsService::canUseSms(\App\Helpers\AuthHelper::sessionUserId())): ?>
                <a href="#"
                    class="btn btn-outline-primary btn-sm rounded-pill disabled"
                    title="Günün planı yükleniyor…"
                    id="btn-daily-plan-sms"
                    role="button"
                    aria-disabled="true"
                    tabindex="-1">
                    <i class="fa-solid fa-comment-sms me-1"></i><span class="d-none d-md-inline">SMS</span>
                </a>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill" id="btn-daily-plan-mernis-scan" title="Seçili gündeki planlı hastalar için MERNİS vefat taraması" aria-label="MERNİS vefat taraması">
                    <i class="fa-solid fa-heart-pulse me-1"></i><span class="d-none d-md-inline">MERNİS</span>
                </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="daily-events-tabs" class="mr-dash-plan__tabs px-3 d-none" aria-hidden="true"></div>
    <div class="card-body p-3 mr-dash-plan__scroll" id="daily-events-body">
        <div id="daily-events-plan">
            <div class="text-center py-5 text-muted" id="daily-events-placeholder">
                <i class="fa fa-hand-pointer d-block mb-3 fs-1 opacity-25"></i>
                <p class="small">Detayları görmek için takvimden<br>bir gün seçiniz.</p>
            </div>
        </div>
    </div>

    <?php if (\App\Helpers\AuthHelper::sessionIsAdmin()): ?>
    <div class="card-footer bg-white border-top-0 p-3 d-none mr-dash-plan__foot" id="route-button-container">
        <a href="#" id="view-route-btn" class="btn btn-primary w-100 py-2 fw-bold shadow-sm rounded-3">
            <i class="fa fa-map-marked-alt me-2"></i>GÜNÜN ROTASINI ÇİZ
        </a>
    </div>
    <?php endif; ?>
</div>
