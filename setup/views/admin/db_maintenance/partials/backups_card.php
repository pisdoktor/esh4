<?php
/** @var array $backups */
/** @var array $tables */
/** @var array $backupGroups */
/** @var string $token */
/** @var array $tools */
$svc = new \App\Services\DbMaintenanceService();
$mysqldumpOk = !empty($tools['mysqldump']);
$mysqldumpChecked = $mysqldumpOk ? ' checked' : '';
$mysqldumpDisabled = $mysqldumpOk ? '' : ' disabled';
?>
<div class="card shadow-sm border-0" id="yedekler">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="mb-1 fw-bold">Yedek oluştur</h6>
        <p class="small text-muted mb-0">İstemci yolu: <code><?= htmlspecialchars((string) ($tools['dir'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></code>
            <?= $mysqldumpOk ? '· <span class="text-success">mysqldump önerilir</span>' : '· <span class="text-warning">mysqldump yok — PHP yedeği</span>' ?>
        </p>
    </div>
    <div class="card-body border-bottom">
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="border rounded p-3 h-100">
                    <h6 class="small fw-bold mb-2">Tam veritabanı</h6>
                    <form method="post" action="<?= htmlspecialchars(esh_url('DbMaintenance', 'createBackup'), ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-2">
                        <input type="hidden" name="maint_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="use_mysqldump" value="1" id="use_mysqldump_backup"<?= $mysqldumpChecked . $mysqldumpDisabled ?>>
                            <label class="form-check-label small" for="use_mysqldump_backup"><code>mysqldump</code> kullan</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="compress" value="1" id="compress_backup" checked>
                            <label class="form-check-label small" for="compress_backup">Gzip sıkıştır (.sql.gz)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="exclude_cache" value="1" id="exclude_cache_backup" checked>
                            <label class="form-check-label small" for="exclude_cache_backup">Önbellek tablolarını hariç tut</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-cloud-arrow-down me-1"></i>Tam yedek oluştur</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border rounded p-3 h-100">
                    <h6 class="small fw-bold mb-2">Tek tablo</h6>
                    <form method="post" action="<?= htmlspecialchars(esh_url('DbMaintenance', 'createTableBackup'), ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-2">
                        <input type="hidden" name="maint_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                        <select name="table" id="db-maint-backup-table-select" class="form-select form-select-sm" required>
                            <option value="">Tablo seçin…</option>
                            <?php foreach ($tables as $t): ?>
                                <option value="<?= htmlspecialchars((string) ($t['t'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($t['t'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="use_mysqldump" value="1" id="use_mysqldump_table_backup"<?= $mysqldumpChecked . $mysqldumpDisabled ?>>
                            <label class="form-check-label small" for="use_mysqldump_table_backup"><code>mysqldump</code></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="compress" value="1" id="compress_table_backup" checked>
                            <label class="form-check-label small" for="compress_table_backup">Gzip sıkıştır</label>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-table me-1"></i>Tablo yedeği</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border rounded p-3 h-100">
                    <h6 class="small fw-bold mb-2">Grup yedeği</h6>
                    <form method="post" action="<?= htmlspecialchars(esh_url('DbMaintenance', 'createGroupBackup'), ENT_QUOTES, 'UTF-8') ?>" class="d-grid gap-2">
                        <input type="hidden" name="maint_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                        <select name="group" class="form-select form-select-sm" required>
                            <option value="">Grup seçin…</option>
                            <?php foreach ($backupGroups as $groupKey => $groupMeta): ?>
                                <option value="<?= htmlspecialchars((string) $groupKey, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($groupMeta['label'] ?? $groupKey), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="use_mysqldump" value="1" id="use_mysqldump_group_backup"<?= $mysqldumpChecked . $mysqldumpDisabled ?>>
                            <label class="form-check-label small" for="use_mysqldump_group_backup"><code>mysqldump</code></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="compress" value="1" id="compress_group_backup" checked>
                            <label class="form-check-label small" for="compress_group_backup">Gzip sıkıştır</label>
                        </div>
                        <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-layer-group me-1"></i>Grup yedeği</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="card-header bg-white py-2 border-bottom">
        <h6 class="mb-0 fw-bold small">Yedek dosyaları</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle small">
            <thead class="table-light"><tr><th class="ps-3">Dosya</th><th>Boyut</th><th>Tarih</th><th class="text-end pe-3">İşlemler</th></tr></thead>
            <tbody>
                <?php if (empty($backups)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Henüz yedek yok.</td></tr>
                <?php else: ?>
                    <?php foreach ($backups as $b): ?>
                        <?php
                        $scope = (string) ($b['scope'] ?? 'full');
                        $size = (int) ($b['size'] ?? 0);
                        ?>
                        <tr>
                            <td class="ps-3">
                                <code><?= htmlspecialchars((string) ($b['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
                                <?php if ($scope === 'table'): ?>
                                    <span class="badge bg-info text-dark ms-1">tablo<?= !empty($b['table']) ? ': ' . htmlspecialchars((string) $b['table'], ENT_QUOTES, 'UTF-8') : '' ?></span>
                                <?php elseif ($scope === 'group'): ?>
                                    <span class="badge bg-primary ms-1">grup<?= !empty($b['group']) ? ': ' . htmlspecialchars((string) $b['group'], ENT_QUOTES, 'UTF-8') : '' ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary ms-1">tam</span>
                                <?php endif; ?>
                                <?php if (str_ends_with(strtolower((string) ($b['name'] ?? '')), '.gz')): ?>
                                    <span class="badge bg-dark ms-1">gzip</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($svc->formatByteSize($size), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= \App\Helpers\DateHelper::unixToTrDotDateTimeOrEmpty(!empty($b['mtime']) ? (int) $b['mtime'] : null) ?></td>
                            <td class="text-end pe-3 text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(esh_url('DbMaintenance', 'download', ['file' => rawurlencode((string) ($b['name'] ?? ''))]), ENT_QUOTES, 'UTF-8') ?>"><i class="fa-solid fa-download"></i></a>
                                <form method="post" action="<?= htmlspecialchars(esh_url('DbMaintenance', 'deleteBackup'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline" onsubmit="return confirm('Bu yedek silinsin mi?');">
                                    <input type="hidden" name="maint_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="file" value="<?= htmlspecialchars((string) ($b['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
