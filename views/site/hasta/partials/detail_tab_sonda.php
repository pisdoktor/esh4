<?php if (!empty($hasta->sonda)): ?>
    <div class="rounded-3 px-3 py-2 mb-3 small fw-semibold d-flex align-items-center gap-2 bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
        <i class="fa-solid fa-syringe flex-shrink-0" aria-hidden="true"></i>
        <span>Sonda mevcut</span>
    </div>
    <div class="esh-hasta-care-stack">
        <div class="esh-hasta-care-item">
            <div class="esh-hasta-care-item__icon bg-danger-subtle text-danger" aria-hidden="true"><i class="fa-solid fa-calendar-day"></i></div>
            <div class="esh-hasta-care-item__body">
                <div class="esh-hasta-care-item__label">Takılma / değişim tarihi</div>
                <div class="esh-hasta-care-item__value esh-hasta-care-item__value--date"><?= !empty($hasta->sondatarihi) ? htmlspecialchars(\App\Helpers\DateHelper::toTr($hasta->sondatarihi), ENT_QUOTES, 'UTF-8') : '—' ?></div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="esh-hasta-care-empty text-muted">
        <i class="fa-solid fa-circle-xmark fa-2x mb-2 opacity-50 d-block" aria-hidden="true"></i>
        <p class="small mb-0 fw-medium">Sonda kaydı yok.</p>
    </div>
<?php endif; ?>
