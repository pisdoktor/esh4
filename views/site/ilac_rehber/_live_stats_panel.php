<?php
/** @var string $statsAjaxUrl */
$statsAjaxUrl = (string) ($statsAjaxUrl ?? '');
$initialEtken = (int) ($etkenCount ?? 0);
$initialIlac = (int) ($ilacCount ?? 0);
$initialWithout = (int) ($etkenWithoutIlacCount ?? 0);
$showImportBadge = !empty($inProgressImportLog);
?>
<?php if ($statsAjaxUrl !== ''): ?>
<section class="card border-0 shadow-sm mb-4" id="esh-ilac-rehber-live-stats"
         data-stats-ajax-url="<?= htmlspecialchars($statsAjaxUrl, ENT_QUOTES, 'UTF-8') ?>">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <h2 class="h6 fw-bold mb-0">Canlı veri özeti</h2>
            <span class="badge bg-primary<?= $showImportBadge ? '' : ' d-none' ?>" id="esh-rehber-import-badge"
                  <?= $showImportBadge ? '' : ' aria-hidden="true"' ?>>
                Aktarım devam ediyor
            </span>
            <span class="small text-muted ms-auto" id="esh-rehber-stats-updated" aria-live="polite"></span>
        </div>
        <div class="row g-3 small">
            <div class="col-sm-4">
                <div class="text-muted">Toplam etken</div>
                <div class="fs-5 fw-semibold" id="esh-rehber-stat-etken"><?= $initialEtken ?></div>
            </div>
            <div class="col-sm-4">
                <div class="text-muted">Toplam ilaç</div>
                <div class="fs-5 fw-semibold" id="esh-rehber-stat-ilac"><?= $initialIlac ?></div>
            </div>
            <div class="col-sm-4">
                <div class="text-muted">İlaçsız etken</div>
                <div class="fs-5 fw-semibold" id="esh-rehber-stat-without"><?= $initialWithout ?></div>
            </div>
        </div>
        <p class="small text-muted mb-0 mt-2 d-none" id="esh-rehber-log-line-wrap">
            <span class="text-secondary">Son log:</span>
            <code class="user-select-all" id="esh-rehber-log-line"></code>
        </p>
    </div>
</section>
<script src="<?= htmlspecialchars(ASSETS_URL . '/pages/js/ilacrehber-progress.js', ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endif; ?>
