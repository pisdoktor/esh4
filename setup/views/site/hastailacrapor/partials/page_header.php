        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-0 text-primary fw-bold">
                    <i class="fa-solid fa-pills me-2"></i>İlaç / tanı raporu bilgileri
                </h5>
                <div class="small text-muted mt-1">
                    <a href="<?= htmlspecialchars(esh_url('Patient', 'view', ['id' => $patientId]), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(trim((string) ($patient->isim ?? '') . ' ' . (string) ($patient->soyisim ?? '')), ENT_QUOTES, 'UTF-8') ?></a>
                    <span class="font-monospace ms-1"><?= htmlspecialchars(\App\Helpers\ValidationHelper::formatTc($tcRaw), ENT_QUOTES, 'UTF-8') ?></span><?= htmlspecialchars($pasifEtiket, ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= htmlspecialchars(esh_url('Patient', 'view', ['id' => $patientId]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
                    <i class="fa-solid fa-id-card me-1"></i>Hasta kartı
                </a>
                <a href="javascript:history.back();" class="btn btn-outline-secondary btn-sm rounded-pill">Geri</a>
            </div>
        </div>
