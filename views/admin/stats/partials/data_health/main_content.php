<div class="card shadow-sm border-0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center gap-2 py-3">
            <h5 class="mb-0 fw-bold min-w-0"><i class="fa-solid fa-clipboard-check me-2 text-primary"></i>Sistem veri sağlığı</h5>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <span id="esh-data-health-header-badge" class="badge bg-secondary">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Yükleniyor…
                </span>
                <?php \App\Helpers\StatsViewPdfHelper::renderPdfButton('main'); ?>
            </div>
        </div>
        <div class="card-body"
             id="esh-data-health-content"
             data-esh-fetch-url="<?= htmlspecialchars($dataHealthContentFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                <span class="spinner-border spinner-border-sm text-primary mb-2" role="status" aria-hidden="true"></span>
                <span>Veri sağlığı özeti yükleniyor…</span>