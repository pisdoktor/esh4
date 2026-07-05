<div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-3">
        <h1 class="h5 mb-1 fw-bold text-primary">
            <i class="fa-solid fa-map-pin me-2" aria-hidden="true"></i>Manuel koordinat düzeltme
        </h1>
        <p class="small text-muted mb-0">Hasta veya adres arayın; haritada doğru konumu işaretleyip kapı kaydına kaydedin.</p>
    </div>
    <div class="card-body">
        <label class="form-label fw-semibold" for="mk-search-input">Hasta / adres ara</label>
        <div class="input-group">
            <input type="search" class="form-control" id="mk-search-input" placeholder="TC, ad-soyad veya adres..." autocomplete="off" minlength="2">
            <button type="button" class="btn btn-primary" id="mk-search-btn" title="Ara">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
        </div>
        <p class="form-text small mb-0">En az 2 karakter. Aktif ve bekleyen hastalar listelenir.</p>
    </div>
    <div class="list-group list-group-flush esh-mk-search-results d-none" id="mk-search-results" role="listbox" aria-label="Arama sonuçları"></div>
</div>
