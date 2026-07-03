    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted text-uppercase">Ulaşılan hasta</div>
                    <div class="h2 fw-bold"><?= number_format((int) ($gen->total_reached ?? 0), 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted text-uppercase">Aktif (E / K)</div>
                    <div class="h4 fw-bold"><?= (int) ($gen->active_total ?? 0) ?></div>
                    <span class="badge bg-primary"><?= (int) ($gen->active_male ?? 0) ?> E</span>
                    <span class="badge bg-danger"><?= (int) ($gen->active_female ?? 0) ?> K</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="small text-muted text-uppercase">Tam bağımlı aktif</div>
                    <div class="h2 fw-bold text-indigo"><?= (int) ($gen->fully_dependent ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>