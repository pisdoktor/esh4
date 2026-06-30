    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <div class="small text-muted mb-1">İlçe</div>
                <div class="fs-4 fw-bold"><?= number_format((int) ($counts['ilce'] ?? 0), 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <div class="small text-muted mb-1">Mahalle</div>
                <div class="fs-4 fw-bold"><?= number_format((int) ($counts['mahalle'] ?? 0), 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <div class="small text-muted mb-1">Sokak</div>
                <div class="fs-4 fw-bold"><?= number_format((int) ($counts['sokak'] ?? 0), 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <div class="small text-muted mb-1">Kapı no</div>
                <div class="fs-4 fw-bold text-primary"><?= number_format((int) ($counts['kapino'] ?? 0), 0, ',', '.') ?></div>
            </div>
        </div>
    </div>