    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted text-uppercase fw-semibold mb-1">Aktif hasta</div>
                    <div class="fs-3 fw-bold text-primary"><?= (int) ($r['active_total'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                <div class="card-body">
                    <div class="small text-muted text-uppercase fw-semibold mb-1">VKİ hesaplanan</div>
                    <div class="fs-3 fw-bold text-success"><?= $computable ?></div>
                    <?php if ((int) ($r['active_total'] ?? 0) > 0): ?>
                        <div class="small text-muted"><?= round($computable / (int) $r['active_total'] * 100, 1) ?>% aktif havuz</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted text-uppercase fw-semibold mb-1">Boy/kilo eksik</div>
                    <div class="fs-3 fw-bold"><?= (int) ($r['without_anthro'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="small text-muted text-uppercase fw-semibold mb-1">Ortalama VKİ</div>
                    <div class="fs-3 fw-bold text-warning"><?= $computable > 0 ? htmlspecialchars((string) ($r['avg_bmi'] ?? '—'), ENT_QUOTES, 'UTF-8') : '—' ?></div>
                    <?php if ((int) ($r['invalid_anthro'] ?? 0) > 0): ?>
                        <div class="small text-danger"><?= (int) $r['invalid_anthro'] ?> geçersiz ölçüm</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>