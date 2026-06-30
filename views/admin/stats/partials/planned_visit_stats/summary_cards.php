        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="small text-muted">Toplam plan</div>
                    <div class="h4 mb-0 fw-bold text-primary"><?= number_format($toplam, 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="small text-muted">Tamamlanan</div>
                    <div class="h4 mb-0 fw-bold text-success"><?= number_format($tamamlanan, 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="small text-muted">Bekleyen</div>
                    <div class="h4 mb-0 fw-bold text-warning"><?= number_format($bekleyen, 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="small text-muted">Gecikmiş (bekleyen)</div>
                    <div class="h4 mb-0 fw-bold text-danger"><?= number_format($gecikmis, 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="small text-muted">Benzersiz hasta</div>
                    <div class="h4 mb-0 fw-bold"><?= number_format($benzersiz, 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="small text-muted">Tamamlanma oranı</div>
                    <div class="h4 mb-0 fw-bold text-info"><?= $oran !== null ? '%' . $oran : '—' ?></div>
                </div>
            </div>
        </div>