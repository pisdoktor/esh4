<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white py-3 border-0">
        <div class="d-flex align-items-center gap-2">
            <span class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center esh-dash-icon-chip">
                <i class="fa-solid fa-id-card"></i>
            </span>
            <div>
                <h6 class="mb-0 fw-bold text-dark">Hasta ara</h6>
                <small class="text-muted">TC veya ad/soyad ile arayın (en az 2 karakter).</small>
            </div>
        </div>
    </div>
    <div class="card-body pt-0 pb-3">
        <form id="dashboard-tc-lookup-form" action="#" method="get" class="row g-2 align-items-end">
            <div class="col-12 col-md-7 col-xl-6">
                <label class="form-label small text-muted mb-1" for="dashboard-tc-lookup-input">TC veya ad/soyad</label>
                <div class="input-group input-group-sm shadow-sm">
                    <span class="input-group-text bg-white text-primary border-end-0">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="dashboard-tc-lookup-input" maxlength="80" class="form-control border-start-0"
                           placeholder="TC veya ad soyad…" autocomplete="off">
                </div>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" id="dashboard-tc-lookup-btn">
                    <i class="fa-solid fa-magnifying-glass me-1"></i>Ara
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="var i=document.getElementById('dashboard-tc-lookup-input'); if(i){i.value=''; i.focus();}">
                    <i class="fa-solid fa-eraser me-1"></i>Temizle
                </button>
            </div>
        </form>
        <div id="dashboard-tc-lookup-exact" class="mt-3 d-none"></div>
        <div id="dashboard-tc-lookup-suggestions" class="mt-2 d-none"></div>
    </div>
</div>
