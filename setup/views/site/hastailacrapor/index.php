<div class="esh-page esh-page--list esh-page-hastailacrapor container-fluid py-4" data-preserve-case>
<?php include __DIR__ . '/partials/hastailacrapor_bootstrap.php'; ?>
<div class="container-fluid py-4 hastailacrapor-index fluent-hastailacrapor-index" data-ilac-listesi-url="<?= htmlspecialchars(ASSETS_URL . '/data/ilac-listesi.json', ENT_QUOTES, 'UTF-8') ?>">
    <div class="card fluent-layer-card fluent-hover-tilt shadow-sm border-0 rounded-3 mb-3">
        <?php include __DIR__ . '/partials/page_header.php'; ?>
        <div class="card-body p-0">
            <ul class="nav nav-tabs px-3 pt-3 border-bottom-0 hastailacrapor-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-raporlar-btn" data-bs-toggle="tab" data-bs-target="#tab-raporlar" type="button" role="tab" aria-controls="tab-raporlar" aria-selected="true">Tanı raporları</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-ilaclar-btn" data-bs-toggle="tab" data-bs-target="#tab-ilaclar" type="button" role="tab" aria-controls="tab-ilaclar" aria-selected="false">Kullandığı ilaçlar</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-raporlar" role="tabpanel" aria-labelledby="tab-raporlar-btn" tabindex="0">
                    <?php include __DIR__ . '/partials/tab_tani_raporlari.php'; ?>
                </div>
                <div class="tab-pane fade" id="tab-ilaclar" role="tabpanel" aria-labelledby="tab-ilaclar-btn" tabindex="0">
                    <?php include __DIR__ . '/partials/tab_kullandigi_ilaclar.php'; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/partials/modal_rapor.php'; ?>
    <?php include __DIR__ . '/partials/modal_ilac.php'; ?>
</div>
</div>
