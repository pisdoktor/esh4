<div class="card shadow-sm border-0 h-100">
    <div class="card-header bg-dark text-white py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="small fw-semibold">
            <i class="fa-solid fa-map me-1"></i>Harita
            <span class="opacity-75 ms-1">— tıklayarak konum seçin</span>
        </span>
        <span class="badge bg-secondary font-monospace" id="mk-map-coords-badge">—</span>
    </div>
    <div class="card-body p-0 position-relative">
<?php if (empty($mapProviderConfigured)): ?>
        <div class="alert alert-danger py-2 small mb-0 rounded-0 border-0">
            Aktif harita sağlayıcısı (<?= htmlspecialchars($providerLabel ?? 'Harita', ENT_QUOTES, 'UTF-8') ?>) için API anahtarı tanımlı değil.
        </div>
<?php endif; ?>
        <div id="esh-mk-map" class="esh-mk-map" role="application" aria-label="Koordinat seçim haritası"></div>
        <div class="esh-mk-map-legend small text-muted px-3 py-2 border-top">
            <span class="d-inline-block me-3"><span class="esh-mk-legend-dot esh-mk-legend-dot--primary"></span> Seçilen konum</span>
            <span class="d-inline-block"><span class="esh-mk-legend-dot esh-mk-legend-dot--ghost"></span> Adresten öneri</span>
        </div>
    </div>
</div>
