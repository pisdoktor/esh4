<?php
/** @var array $tables */
/** @var array $tools */
/** @var array $summary */
$topTables = array_slice($tables, 0, 10);
$mysqldumpOk = !empty($summary['mysqldump_available']);
$baseUrl = esh_url('DbMaintenance', 'index');
$tabUrl = static function (string $tab) use ($baseUrl): string {
    if ($tab === 'ozet') {
        return $baseUrl;
    }
    return $baseUrl . (strpos($baseUrl, '?') !== false ? '&' : '?') . 'tab=' . rawurlencode($tab);
};
?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">En büyük tablolar</h6>
                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars($tabUrl('tablolar'), ENT_QUOTES, 'UTF-8') ?>">Tümünü gör</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 small">
                        <thead class="table-light"><tr><th class="ps-3">Tablo</th><th>Grup</th><th class="text-end">Satır</th><th class="text-end pe-3">MB</th></tr></thead>
                        <tbody>
                            <?php if ($topTables === []): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">Tablo bulunamadı.</td></tr>
                            <?php else: ?>
                                <?php foreach ($topTables as $t): ?>
                                    <?php
                                    $mb = (float) ($t['mb'] ?? 0);
                                    $rows = (int) ($t['est_rows'] ?? 0);
                                    $large = $mb >= 100 || $rows >= 1000000;
                                    ?>
                                    <tr class="<?= $large ? 'table-warning' : '' ?>">
                                        <td class="ps-3"><code><?= htmlspecialchars((string) ($t['t'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars((string) ($t['group_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td class="text-end"><?= number_format($rows, 0, ',', '.') ?></td>
                                        <td class="text-end pe-3"><?= htmlspecialchars((string) ($t['mb'] ?? '0'), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold">Hızlı işlemler</h6>
            </div>
            <div class="card-body d-grid gap-2">
                <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($tabUrl('yedekler'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-cloud-arrow-down me-1"></i>Yedek oluştur</a>
                <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars($tabUrl('bakim'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-stethoscope me-1"></i>Tablo bakımı</a>
                <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($tabUrl('tablolar'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-table me-1"></i>Tablo listesi</a>
            </div>
        </div>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold">Ölçek notları</h6>
            </div>
            <div class="card-body small text-muted">
                <ul class="mb-0 ps-3">
                    <li>Büyük veritabanlarında <strong>mysqldump</strong> kullanın<?= $mysqldumpOk ? ' (sunucuda mevcut).' : ' — şu an bulunamadı, PHP yedeği yavaştır.' ?></li>
                    <li>Tam yedekte <strong>gzip</strong> sıkıştırma disk alanı kazandırır.</li>
                    <li>Grup yedekleri (hasta, izlem vb.) kısmi geri yükleme için uygundur.</li>
                    <li>OPTIMIZE büyük InnoDB tablolarında uzun sürebilir; tek tablo seçin.</li>
                    <li>Otomatik zamanlanmış yedek (cron) — <em>yakında</em>.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
