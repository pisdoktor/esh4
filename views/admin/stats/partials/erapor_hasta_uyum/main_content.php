<div class="card shadow-sm border-0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
            <h5 class="mb-0 fw-bold min-w-0"><i class="fa-solid fa-file-waveform me-2 text-info"></i>Havuz ↔ hasta kartı karşılaştırması</h5>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <span id="esh-erapor-hasta-uyum-header-badge" class="badge bg-secondary">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Yükleniyor…
                </span>
                <?php \App\Helpers\StatsViewPdfHelper::renderPdfButton('main'); ?>
            </div>
        </div>
        <div class="card-body">
            <div id="esh-erapor-hasta-uyum-summary"
                 class="mb-4"
                 data-esh-fetch-url="<?= htmlspecialchars($eraporHastaUyumSummaryFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                    <span class="spinner-border spinner-border-sm text-info mb-2" role="status" aria-hidden="true"></span>
                    <span>Özet kartlar yükleniyor…</span>
                </div>
            </div>
            <div id="esh-erapor-hasta-uyum-metrics"
                 data-esh-fetch-url="<?= htmlspecialchars($eraporHastaUyumMetricsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="d-flex flex-column align-items-center justify-content-center py-4 text-muted">
                    <span class="spinner-border spinner-border-sm text-info mb-2" role="status" aria-hidden="true"></span>
                    <span>Metrik tablosu yükleniyor…</span>