<?php if (!empty($hasta->mama)): ?>
    <div class="rounded-3 px-3 py-2 mb-3 small fw-semibold d-flex align-items-center gap-2 bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-50">
        <i class="fa-solid fa-bottle-droplet flex-shrink-0" aria-hidden="true"></i>
        <span>Mama kullanımı</span>
    </div>
    <div class="esh-hasta-care-stack">
        <div class="esh-hasta-care-item">
            <div class="esh-hasta-care-item__icon bg-warning-subtle text-warning-emphasis" aria-hidden="true"><i class="fa-solid fa-bowl-food"></i></div>
            <div class="esh-hasta-care-item__body">
                <div class="esh-hasta-care-item__label">Çeşit</div>
                <div class="esh-hasta-care-item__value"><?= htmlspecialchars(\App\Helpers\PatientCareHelper::mamaCesitLabel($hasta->mamacesit ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
        <div class="esh-hasta-care-item">
            <div class="esh-hasta-care-item__icon bg-warning-subtle text-warning-emphasis" aria-hidden="true"><i class="fa-solid fa-file-circle-check"></i></div>
            <div class="esh-hasta-care-item__body">
                <div class="esh-hasta-care-item__label">Rapor bitiş</div>
                <div class="esh-hasta-care-item__value esh-hasta-care-item__value--date"><?= !empty($hasta->mamaraporbitis) ? htmlspecialchars(\App\Helpers\DateHelper::toTr($hasta->mamaraporbitis), ENT_QUOTES, 'UTF-8') : '—' ?></div>
            </div>
        </div>
        <div class="esh-hasta-care-item">
            <div class="esh-hasta-care-item__icon bg-warning-subtle text-warning-emphasis" aria-hidden="true"><i class="fa-solid fa-hospital"></i></div>
            <div class="esh-hasta-care-item__body">
                <div class="esh-hasta-care-item__label">Rapor yeri</div>
                <div class="esh-hasta-care-item__value"><?= htmlspecialchars(\App\Helpers\PatientCareHelper::mamaRaporYeriLabel($hasta->mamaraporyeri ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="esh-hasta-care-empty text-muted">
        <i class="fa-solid fa-wheat-awn-circle-exclamation fa-2x mb-2 opacity-50 d-block" aria-hidden="true"></i>
        <p class="small mb-0 fw-medium">Mama kaydı yok.</p>
    </div>
<?php endif; ?>
