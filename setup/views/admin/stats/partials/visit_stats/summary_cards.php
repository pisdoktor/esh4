        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="small text-muted">Toplam izlem</div>
                    <div class="h4 mb-0 fw-bold text-primary"><?= number_format($toplam, 0, ',', '.') ?></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="small text-muted">Yapıldı</div>
                    <div class="h4 mb-0 fw-bold text-success"><?= $countWithShare($yapilan, $toplam) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card border-0 shadow-sm text-center p-3 h-100">
                    <div class="small text-muted">Yapılmadı</div>
                    <div class="h4 mb-0 fw-bold text-warning"><?= $countWithShare($yapilmayan, $toplam) ?></div>
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
                    <div class="small text-muted">Yapılma oranı</div>
                    <div class="h4 mb-0 fw-bold text-info"><?= $oran !== null ? '%' . $oran : '—' ?></div>
                </div>
            </div>
        </div>