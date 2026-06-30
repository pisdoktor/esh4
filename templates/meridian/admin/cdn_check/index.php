<?php
/**
 * @var int $timeout
 * @var bool $probe
 * @var list<array<string, mixed>> $compareRows
 * @var array<string, string> $suggested
 * @var string|null $probeReport
 */
/**
 * @var int $timeout
 * @var bool $probe
 * @var list<array<string, mixed>> $compareRows
 * @var array<string, string> $suggested
 * @var string|null $probeReport
 */
/**
 * @var int $timeout
 * @var bool $probe
 * @var list<array<string, mixed>> $compareRows
 * @var array<string, string> $suggested
 * @var string|null $probeReport
 */
$baseUrl = esh_url('CdnCheck', 'index');
$probeUrl = $baseUrl . '&amp;probe=1&amp;timeout=' . (int) $timeout;
?>
<article class="mr-page mr-admin-page mr-admin-cdn-check container-fluid mt-2" lang="tr">
<header class="visually-hidden"><h1>CDN sürüm kontrolü</h1></header>
    <div class="row mb-3">
        <div class="col-lg-8">
            <h3 class="fw-bold text-dark mb-1"><i class="fa-solid fa-cloud-arrow-down text-primary me-2"></i>CDN sürüm kontrolü</h3>
            <p class="text-muted small mb-0">
                Kaynak: <code>CdnAssetHelper</code> sabitleri · npm / cdnjs son sürüm ile karşılaştırma (timeout <?= (int) $timeout ?> sn).
            </p>
        </div>
        <div class="col-lg-4 text-lg-end mt-2 mt-lg-0 d-flex flex-wrap gap-2 justify-content-lg-end align-items-center">
            <a class="btn btn-outline-primary btn-sm" href="<?= $baseUrl ?>&amp;timeout=<?= (int) $timeout ?>"><i class="fa-solid fa-rotate me-1"></i>Yenile</a>
            <?php if ($probe): ?>
                <a class="btn btn-outline-secondary btn-sm" href="<?= $baseUrl ?>&amp;timeout=<?= (int) $timeout ?>">HEAD sondasını kapat</a>
            <?php else: ?>
                <a class="btn btn-outline-secondary btn-sm" href="<?= $probeUrl ?>"><i class="fa-solid fa-link me-1"></i>HEAD sondası ekle</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="alert alert-info border-0 shadow-sm small mb-3">
        <strong>Komut satırı</strong> (v<?= htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8') ?> — proje kökünden):
        <code class="d-block mt-1 user-select-all">php tools/check_cdn_versions.php</code>
        <span class="d-block mt-1">Seçenekler: <code>--probe</code> · <code>--timeout=15</code> · <code>--dry-apply</code> / <code>--apply</code> (dosyaya yazma yalnızca buradan; yedek alın).</span>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold">Kayıt defteri karşılaştırması</h6>
        </div>
        <div class="card-body p-0">
            <?php include ROOT_PATH . '/views/admin/cdn_check/compare_table.php'; ?>
        </div>
    </div>

    <?php if ($suggested !== []): ?>
        <div class="alert alert-warning border-0 shadow-sm small">
            <strong>Önerilen güncellemeler</strong> (registry &gt; pinned; web arayüzü dosyaya yazmaz):
            <ul class="mb-0 mt-2">
                <?php foreach ($suggested as $c => $v): ?>
                    <li><code><?= htmlspecialchars((string) $c, ENT_QUOTES, 'UTF-8') ?></code> → <code><?= htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($probe && $probeReport !== null): ?>
        <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold">CDN URL HEAD sondası</h6>
            </div>
            <div class="card-body p-0">
                <pre class="small mb-0 p-3 bg-light text-dark" style="max-height: 360px; overflow: auto; white-space: pre-wrap; word-break: break-word;"><?= htmlspecialchars($probeReport, ENT_QUOTES, 'UTF-8') ?></pre>
            </div>
        </div>
    <?php endif; ?>
</article>
