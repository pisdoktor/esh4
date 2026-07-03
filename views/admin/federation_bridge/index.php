<?php
declare(strict_types=1);

use App\Helpers\FederationBridgeHelper;
use App\Helpers\OperationalSettings;

/** @var list<object> $syncLogs */
$syncLogs = $syncLogs ?? [];
$apiConfigured = (bool) ($apiConfigured ?? false);
$manifestState = is_array($manifestState ?? null) ? $manifestState : null;
$regionComplianceRows = is_array($regionComplianceRows ?? null) ? $regionComplianceRows : [];
?>
<div class="esh-page esh-page--list container-fluid py-4">
    <header class="esh-page__header mb-4 d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="h4 mb-1"><i class="fa-solid fa-diagram-project me-2"></i>Federasyon dosya köprüsü</h1>
            <p class="small text-muted mb-0">Klinik veri içermez; bölge, kurum meta ve özet istatistik senkronu.</p>
        </div>
        <a href="<?= htmlspecialchars(esh_url('Federation', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">Özet</a>
    </header>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3"><h2 class="h6 mb-0">Düğüm anlık görüntüsü (dışa aktar)</h2></div>
                <div class="card-body small">
                    <p class="text-muted">Bölgeler, kurumlar<?= OperationalSettings::federationExportIncludeStats() ? ' ve özet istatistik' : '' ?> JSON paketine dönüştürülür.</p>
                    <a href="<?= htmlspecialchars(esh_url('FederationBridge', 'export'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary rounded-pill btn-sm">
                        <i class="fa-solid fa-download me-1"></i>JSON indir
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3"><h2 class="h6 mb-0">Hub → düğüm (içe aktar)</h2></div>
                <div class="card-body">
                    <form method="post" action="<?= htmlspecialchars(esh_url('FederationBridge', 'importBundle'), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data">
                        <?= esh_csrf_field() ?>
                        <input type="file" class="form-control form-control-sm mb-3" name="bundle_file" accept=".json,application/json" required>
                        <button type="submit" class="btn btn-success rounded-pill btn-sm"><i class="fa-solid fa-upload me-1"></i>İçe aktar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body small text-muted">
            Hub API: <?= $apiConfigured ? 'Yapılandırıldı' : 'Kapalı' ?>
            <?php if (OperationalSettings::federationCentralManifestUrl() !== ''): ?>
                · Manifest: <code><?= htmlspecialchars(OperationalSettings::federationCentralManifestUrl(), ENT_QUOTES, 'UTF-8') ?></code>
            <?php endif; ?>
            <?php if ($manifestState !== null): ?>
                · Son kontrol: <strong><?= htmlspecialchars((string) ($manifestState['checked_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong>
            <?php endif; ?>
            <form method="post" action="<?= htmlspecialchars(esh_url('FederationBridge', 'manualSyncManifest'), ENT_QUOTES, 'UTF-8') ?>" class="mt-2">
                <?= esh_csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill">
                    <i class="fa-solid fa-arrows-rotate me-1"></i>Manifest senkronunu çalıştır
                </button>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/partials/region_compliance.php'; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3"><h2 class="h6 mb-0">Son senkron işlemleri</h2></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Tarih</th><th>Yön</th><th>Durum</th><th>Dosya</th><th>Özet</th></tr></thead>
                <tbody><?php include __DIR__ . '/partials/sync_log_rows.php'; ?></tbody>
            </table>
        </div>
    </div>
</div>
