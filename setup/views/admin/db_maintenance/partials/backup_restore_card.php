<?php
/** @var array $backups */
/** @var array $tables */
/** @var string $token */
/** @var array $tools */
/** @var array $summary */
$svc = new \App\Services\DbMaintenanceService();
$mysqlOk = !empty($summary['mysql_available']);
?>
<div class="card shadow-sm border-0 border-danger border-2" id="geri">
    <div class="card-header bg-danger-subtle py-3 border-bottom">
        <h6 class="mb-0 fw-bold text-danger"><i class="fa-solid fa-rotate-left me-1"></i>Yedekten geri yükle</h6>
    </div>
    <div class="card-body">
        <ol class="small text-muted">
            <li>Önce <strong>Yedekler</strong> sekmesinden yeni yedek alın.</li>
            <li>Listeden dosyayı seçin; onay kutusuna tam olarak <code>YEDEKTEN_YUKLE</code> yazın.</li>
            <li>Geri yükleme için sunucuda <code>mysql</code> istemcisi gerekir<?= $mysqlOk ? ' (mevcut).' : ' — <strong class="text-danger">bulunamadı</strong>; yalnızca &lt;50 MB dosyalar mysqli ile denenir.' ?></li>
        </ol>

        <h6 class="fw-bold mb-2">Tam veritabanı geri yükleme</h6>
        <form method="post" action="<?= htmlspecialchars(esh_url('DbMaintenance', 'restoreBackup'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end mb-4" id="restore-full-form" data-esh-confirm="Mevcut veritabanı bu yedekle değiştirilecek. Emin misiniz?">
            <input type="hidden" name="maint_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Yedek dosyası</label>
                <select name="file" id="restore-full-file" class="form-select form-select-sm db-maint-restore-file" required <?= empty($backups) ? 'disabled' : '' ?> data-restore-form="full">
                    <option value="" data-size="0">Seçin…</option>
                    <?php foreach ($backups as $b): ?>
                        <?php if (($b['scope'] ?? 'full') === 'full'): ?>
                            <option value="<?= htmlspecialchars((string) ($b['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-size="<?= (int) ($b['size'] ?? 0) ?>">
                                <?= htmlspecialchars((string) ($b['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                (<?= htmlspecialchars($svc->formatByteSize((int) ($b['size'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>)
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <div id="restore-full-warning" class="small mt-1 d-none"></div>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-semibold">Onay (tam metin)</label>
                <input type="text" name="restore_confirm" class="form-control form-control-sm" placeholder="YEDEKTEN_YUKLE" autocomplete="off" required <?= empty($backups) ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-danger w-100" <?= empty($backups) ? 'disabled' : '' ?>><i class="fa-solid fa-rotate-left me-1"></i>Geri yükle</button>
            </div>
        </form>

        <hr class="my-4">
        <h6 class="fw-bold text-danger mb-2"><i class="fa-solid fa-table me-1"></i>Tek tablo geri yükle</h6>
        <p class="small text-muted">Yalnızca tablo yedeği dosyalarını kullanın. Seçili tablo yedekteki hâle getirilir (DROP + CREATE + veri).</p>
        <form method="post" action="<?= htmlspecialchars(esh_url('DbMaintenance', 'restoreTableBackup'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end" id="restore-table-form" data-esh-confirm="Yalnızca seçili tablo yedekteki hâle getirilecek. Emin misiniz?">
            <input type="hidden" name="maint_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Tablo</label>
                <select name="table" id="restore-table-select" class="form-select form-select-sm" required <?= empty($backups) ? 'disabled' : '' ?>>
                    <option value="">Seçin…</option>
                    <?php foreach ($tables as $t): ?>
                        <option value="<?= htmlspecialchars((string) ($t['t'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($t['t'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Tablo yedeği</label>
                <select name="file" id="restore-table-file" class="form-select form-select-sm db-maint-restore-file" required <?= empty($backups) ? 'disabled' : '' ?> data-restore-form="table">
                    <option value="" data-size="0">Seçin…</option>
                    <?php foreach ($backups as $b): ?>
                        <option value="<?= htmlspecialchars((string) ($b['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-scope="<?= htmlspecialchars((string) ($b['scope'] ?? 'full'), ENT_QUOTES, 'UTF-8') ?>"
                            data-table="<?= htmlspecialchars((string) ($b['table'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-size="<?= (int) ($b['size'] ?? 0) ?>">
                            <?= htmlspecialchars((string) ($b['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div id="restore-table-warning" class="small mt-1 d-none"></div>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Onay (tam metin)</label>
                <input type="text" name="restore_confirm" class="form-control form-control-sm" placeholder="YEDEKTEN_YUKLE" autocomplete="off" required <?= empty($backups) ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-danger w-100" <?= empty($backups) ? 'disabled' : '' ?>><i class="fa-solid fa-rotate-left me-1"></i>Tabloyu geri yükle</button>
            </div>
        </form>
        <?php if (empty($backups)): ?>
            <p class="small text-muted mb-0 mt-2">Geri yükleme için önce bir yedek oluşturun.</p>
        <?php endif; ?>
    </div>
</div>
