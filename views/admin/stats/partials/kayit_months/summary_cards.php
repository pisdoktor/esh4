    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small">Toplam (tablodaki dönem)</div>
                    <div class="fs-3 fw-bold text-primary"><?= number_format($sumToplam, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small">Erkek (E)</div>
                    <div class="fs-3 fw-bold"><?= number_format($sumErkek, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body text-center py-3">
                    <div class="text-muted small">Kadın (K)</div>
                    <div class="fs-3 fw-bold"><?= number_format($sumKadin, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>