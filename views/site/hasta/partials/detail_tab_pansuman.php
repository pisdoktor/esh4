<?php if (!empty($hasta->pansuman)): ?>
    <div class="rounded-3 px-3 py-2 mb-3 small fw-semibold d-flex align-items-center gap-2 bg-primary text-white shadow-sm">
        <i class="fa-solid fa-circle-check flex-shrink-0 opacity-90" aria-hidden="true"></i>
        <span>Pansuman takibi açık</span>
    </div>
    <div class="esh-hasta-care-stack">
        <div class="esh-hasta-care-item">
            <div class="esh-hasta-care-item__icon bg-primary-subtle text-primary" aria-hidden="true"><i class="fa-solid fa-calendar-week"></i></div>
            <div class="esh-hasta-care-item__body">
                <div class="esh-hasta-care-item__label">Planlanan günler</div>
                <div class="esh-hasta-care-item__value"><?= htmlspecialchars(\App\Helpers\PatientCareHelper::formatPgunleriForDisplay($hasta->pgunleri ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
        <div class="esh-hasta-care-item">
            <div class="esh-hasta-care-item__icon bg-primary-subtle text-primary" aria-hidden="true"><i class="fa-solid fa-clock"></i></div>
            <div class="esh-hasta-care-item__body">
                <div class="esh-hasta-care-item__label">Tercih edilen zaman</div>
                <div class="esh-hasta-care-item__value"><?= htmlspecialchars(\App\Helpers\PatientCareHelper::pzamanLabel($hasta->pzaman ?? null), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="esh-hasta-care-empty text-muted">
        <i class="fa-solid fa-hand-dots fa-2x mb-2 opacity-50 d-block" aria-hidden="true"></i>
        <p class="small mb-0 fw-medium">Pansuman takibi yok.</p>
    </div>
<?php endif; ?>
