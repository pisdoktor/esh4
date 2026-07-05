<div class="card mb-2">
    <div class="card-header py-2">
        <strong>TC Kimlik ile Hasta Ara</strong>
    </div>
    <div class="card-body py-2">
        <form id="dashboard-tc-lookup-form" action="#" method="get" class="row g-2 align-items-end">
            <div class="col-12 col-md-7">
                <label class="form-label small mb-1">TC Kimlik No</label>
                <input type="text" id="dashboard-tc-lookup-input" maxlength="14" class="form-control form-control-sm"
                       placeholder="Örn: 12345678901" autocomplete="off">
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary" id="dashboard-tc-lookup-btn">Ara</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-esh-action="clear-focus" data-esh-clear-input="#dashboard-tc-lookup-input">Temizle</button>
            </div>
        </form>
        <div id="dashboard-tc-lookup-exact" class="mt-2 d-none"></div>
        <div id="dashboard-tc-lookup-suggestions" class="mt-2 d-none"></div>
    </div>
</div>
