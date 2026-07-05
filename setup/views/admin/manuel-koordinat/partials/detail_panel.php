<div class="card shadow-sm border-0 esh-mk-detail-card d-none" id="mk-detail-card">
    <div class="card-header bg-light py-2">
        <h2 class="h6 mb-0 fw-bold">Seçili kayıt</h2>
    </div>
    <div class="card-body">
        <div id="mk-detail-hasta" class="mb-3 d-none">
            <div class="small text-muted">Hasta</div>
            <div class="fw-semibold" id="mk-detail-hasta-isim"></div>
            <div class="font-monospace small text-secondary" id="mk-detail-hasta-tc"></div>
            <a href="#" class="btn btn-sm btn-outline-primary mt-2 d-none" id="mk-detail-hasta-link" target="_blank" rel="noopener noreferrer">
                <i class="fa-solid fa-id-card me-1"></i>Hasta kartı
            </a>
        </div>
        <div class="mb-3">
            <div class="small text-muted">Adres (kapı no)</div>
            <div class="fw-semibold" id="mk-detail-adres"></div>
        </div>
        <div class="mb-3">
            <div class="small text-muted">Mevcut koordinat</div>
            <div class="font-monospace small text-primary" id="mk-detail-coords">Girilmemiş</div>
        </div>
        <div class="alert alert-warning py-2 small mb-3" role="status">
            <i class="fa-solid fa-triangle-exclamation me-1"></i>
            Koordinat <strong>kapı no</strong> kaydına yazılır; aynı kapıdaki
            <span id="mk-detail-hasta-sayisi">0</span> hasta paylaşır.
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold small" for="mk-coords-input">Seçilen koordinat</label>
            <input type="text" class="form-control form-control-sm font-monospace" id="mk-coords-input" placeholder="38.123456,29.123456" readonly>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="mk-geocode-preview-btn" title="Adresten öneri koordinat göster">
                <i class="fa-solid fa-location-crosshairs me-1"></i>Adresten öner
            </button>
            <button type="button" class="btn btn-sm btn-primary" id="mk-save-btn" disabled>
                <i class="fa-solid fa-floppy-disk me-1"></i>Kaydet
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="mk-clear-btn" disabled>
                <i class="fa-solid fa-eraser me-1"></i>Temizle
            </button>
        </div>
    </div>
</div>
<div class="card shadow-sm border-0 esh-mk-empty-hint" id="mk-empty-hint">
    <div class="card-body text-center text-muted py-5">
        <i class="fa-solid fa-hand-pointer fa-2x mb-3 opacity-50" aria-hidden="true"></i>
        <p class="mb-0 small">Soldan hasta arayın veya haritada bir noktaya tıklayarak başlayın.</p>
    </div>
</div>
