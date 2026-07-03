    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="small text-muted">7 gün izlem</div>
                <div class="h4 mb-0 fw-bold text-primary"><?= (int) ($trend->gun7 ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="small text-muted">30 gün izlem</div>
                <div class="h4 mb-0 fw-bold text-info"><?= (int) ($trend->gun30 ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="small text-muted">Bu ay izlem</div>
                <div class="h4 mb-0 fw-bold text-success"><?= (int) ($trend->bu_ay ?? 0) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="small text-muted">Bu ay bekleyen</div>
                <div class="h4 mb-0 fw-bold text-warning"><?= (int) $pendingMonth ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="small text-muted"><?= (int) date('Y') ?> yılı izlem</div>
                <div class="h4 mb-0 fw-bold text-dark"><?= (int) $visitsYear ?></div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <div class="small text-muted">Bekleyen hasta (-3)</div>
                <div class="h4 mb-0 fw-bold text-secondary"><?= (int) $waiting ?></div>
            </div>
        </div>
    </div>