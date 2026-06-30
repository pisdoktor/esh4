<?php if (!empty($hasta->bez)): ?>
    <div class="rounded-3 px-3 py-2 mb-3 small fw-semibold d-flex align-items-center gap-2 bg-info bg-opacity-10 text-info-emphasis border border-info border-opacity-50">
        <i class="fa-solid fa-baby-carriage flex-shrink-0" aria-hidden="true"></i>
        <span>Bez kullanımı</span>
    </div>
    <div class="esh-hasta-care-stack">
        <div class="esh-hasta-care-item">
            <div class="esh-hasta-care-item__icon bg-info-subtle text-info-emphasis" aria-hidden="true"><i class="fa-solid fa-file-waveform"></i></div>
            <div class="esh-hasta-care-item__body">
                <div class="esh-hasta-care-item__label">Bez raporu</div>
                <div class="esh-hasta-care-item__value"><?= \App\Helpers\BadgeHelper::yesNoEvetHayir($hasta->bezrapor ?? null) ?></div>
            </div>
        </div>
        <?php if (!empty($hasta->bezrapor)): ?>
        <div class="esh-hasta-care-item">
            <div class="esh-hasta-care-item__icon bg-info-subtle text-info-emphasis" aria-hidden="true"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="esh-hasta-care-item__body">
                <div class="esh-hasta-care-item__label">Rapor bitiş</div>
                <div class="esh-hasta-care-item__value esh-hasta-care-item__value--date"><?= !empty($hasta->bezraporbitis) ? htmlspecialchars(\App\Helpers\DateHelper::toTr($hasta->bezraporbitis), ENT_QUOTES, 'UTF-8') : '—' ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="esh-hasta-care-empty text-muted">
        <i class="fa-solid fa-box fa-2x mb-2 opacity-50 d-block" aria-hidden="true"></i>
        <p class="small mb-0 fw-medium">Bez kaydı yok.</p>
    </div>
<?php endif; ?>
