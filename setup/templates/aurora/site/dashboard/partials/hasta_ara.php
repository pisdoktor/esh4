<section class="au-panel au-panel--search mb-4">
    <div class="au-panel__head">
        <span class="au-icon-chip au-icon-chip--grad"><i class="fa-solid fa-id-card"></i></span>
        <div class="min-w-0">
            <h2 class="au-panel__title">Hasta ara</h2>
            <p class="au-panel__sub">TC veya ad/soyad ile arayın (en az 2 karakter).</p>
        </div>
    </div>
    <div class="au-panel__body">
        <form id="dashboard-tc-lookup-form" action="#" method="get" class="row g-2 align-items-end">
            <div class="col-12 col-md-7 col-xl-6">
                <label class="form-label small fw-semibold text-secondary mb-1" for="dashboard-tc-lookup-input">TC veya ad/soyad</label>
                <div class="input-group au-search-group">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" id="dashboard-tc-lookup-input" maxlength="80" class="form-control"
                           placeholder="TC veya ad soyad…" autocomplete="off">
                </div>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-3" id="dashboard-tc-lookup-btn">
                    <i class="fa-solid fa-magnifying-glass me-1"></i>Ara
                </button>
                <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-esh-action="clear-focus" data-esh-clear-input="#dashboard-tc-lookup-input">
                    <i class="fa-solid fa-eraser me-1"></i>Temizle
                </button>
            </div>
        </form>
        <div id="dashboard-tc-lookup-exact" class="mt-3 d-none"></div>
        <div id="dashboard-tc-lookup-suggestions" class="mt-2 d-none"></div>
    </div>
</section>
