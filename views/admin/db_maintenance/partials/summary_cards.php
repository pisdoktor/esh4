<?php
/** @var array $summary */
/** @var array $tools */
$tablesCount = (int) ($summary['tables_count'] ?? 0);
$totalMb = (float) ($summary['total_mb'] ?? 0);
$totalRows = (int) ($summary['total_est_rows'] ?? 0);
$backupsCount = (int) ($summary['backups_count'] ?? 0);
$lastBackup = !empty($summary['last_backup_mtime']) ? \App\Helpers\DateHelper::unixToTrDotDateTimeOrEmpty((int) $summary['last_backup_mtime']) : '—';
$mysqldumpOk = !empty($summary['mysqldump_available']);
$mysqlOk = !empty($summary['mysql_available']);
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Tablo</div>
                <div class="fs-5 fw-bold"><?= number_format($tablesCount, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Toplam boyut</div>
                <div class="fs-5 fw-bold"><?= number_format($totalMb, 1, ',', '.') ?> MB</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Tahm. satır</div>
                <div class="fs-5 fw-bold"><?= number_format($totalRows, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Yedek sayısı</div>
                <div class="fs-5 fw-bold"><?= number_format($backupsCount, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="text-muted small">Son yedek</div>
                <div class="small fw-semibold"><?= htmlspecialchars($lastBackup, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="text-muted small">MySQL araçları</div>
                <div class="small">
                    <span class="badge <?= $mysqldumpOk ? 'bg-success' : 'bg-warning text-dark' ?>">dump</span>
                    <span class="badge <?= $mysqlOk ? 'bg-success' : 'bg-warning text-dark' ?>">mysql</span>
                </div>
                <?php if (!$mysqldumpOk): ?>
                    <div class="text-muted mt-1" style="font-size:0.7rem;">Büyük DB için <code>ESH_MYSQL_BIN</code></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
