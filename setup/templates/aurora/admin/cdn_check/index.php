<?php
/**
 * Aurora Light — admin/cdn_check/index
 * @var int $timeout
 * @var bool $probe
 * @var list<array<string, mixed>> $compareRows
 * @var array<string, string> $suggested
 * @var string|null $probeReport
 */
$baseUrl = esh_url('CdnCheck', 'index');
$probeUrl = $baseUrl . '&amp;probe=1&amp;timeout=' . (int) $timeout;
?>
<div class="container-fluid py-4">
    <section class="au-panel mb-4">
        <div class="au-panel__head au-panel__head--split">
            <div class="d-flex align-items-start gap-2">
                <span class="au-icon-chip au-icon-chip--grad"><i class="fa-solid fa-cloud-arrow-down"></i></span>
                <div>
                    <h2 class="au-panel__title mb-1">CDN sürüm kontrolü</h2>
                    <p class="au-panel__sub mb-0">
                        Kaynak: <code>CdnAssetHelper</code> sabitleri · npm / cdnjs son sürüm ile karşılaştırma (timeout <?= (int) $timeout ?> sn).
                    </p>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-primary btn-sm rounded-pill" href="<?= $baseUrl ?>&amp;timeout=<?= (int) $timeout ?>"><i class="fa-solid fa-rotate me-1"></i>Yenile</a>
                <?php if ($probe): ?>
                    <a class="btn btn-outline-secondary btn-sm rounded-pill" href="<?= $baseUrl ?>&amp;timeout=<?= (int) $timeout ?>">HEAD sondasını kapat</a>
                <?php else: ?>
                    <a class="btn btn-outline-secondary btn-sm rounded-pill" href="<?= $probeUrl ?>"><i class="fa-solid fa-link me-1"></i>HEAD sondası ekle</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="alert alert-info border-0 shadow-sm small mb-3">
        <strong>Komut satırı</strong> (v<?= htmlspecialchars(esh_app_version(), ENT_QUOTES, 'UTF-8') ?> — proje kökünden):
        <code class="d-block mt-1 user-select-all">php tools/check_cdn_versions.php</code>
        <span class="d-block mt-1">Seçenekler: <code>--probe</code> · <code>--timeout=15</code> · <code>--dry-apply</code> / <code>--apply</code> (dosyaya yazma yalnızca buradan; yedek alın).</span>
    </div>

    <section class="au-panel mb-4">
        <div class="au-panel__head">
            <h2 class="au-panel__title mb-0">Kayıt defteri karşılaştırması</h2>
        </div>
        <div class="au-panel__body p-0">
            <?php include ROOT_PATH . '/views/admin/cdn_check/compare_table.php'; ?>
        </div>
    </section>

    <?php if ($suggested !== []): ?>
        <div class="alert alert-warning border-0 shadow-sm small mb-3">
            <strong>Önerilen güncellemeler</strong> (web arayüzü dosyaya yazmaz):
            <ul class="mb-0 mt-2">
                <?php foreach ($suggested as $c => $v): ?>
                    <li><code><?= htmlspecialchars((string) $c, ENT_QUOTES, 'UTF-8') ?></code> → <code><?= htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') ?></code></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($probe && $probeReport !== null): ?>
        <section class="au-panel">
            <div class="au-panel__head">
                <h2 class="au-panel__title mb-0">CDN URL HEAD sondası</h2>
            </div>
            <div class="au-panel__body p-0">
                <pre class="small mb-0 p-3 bg-light text-dark" style="max-height: 360px; overflow: auto; white-space: pre-wrap; word-break: break-word;"><?= htmlspecialchars($probeReport, ENT_QUOTES, 'UTF-8') ?></pre>
            </div>
        </section>
    <?php endif; ?>
</div>
