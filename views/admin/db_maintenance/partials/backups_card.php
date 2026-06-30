    <div class="card shadow-sm border-0 mt-4" id="yedekler">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h6 class="mb-0 fw-bold">Yedekler</h6>
                <p class="small text-muted mb-0">Varsayılan yedek motoru: PHP. İsterseniz <code>mysqldump</code> kutusunu işaretleyin (bulunamazsa veya hata olursa otomatik PHP). İstemci yolu: <?= htmlspecialchars((string) ($tools['dir'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <form method="post" action="<?= htmlspecialchars(esh_url('DbMaintenance', 'createBackup'), ENT_QUOTES, 'UTF-8') ?>" class="m-0 d-flex flex-wrap align-items-center gap-2 justify-content-end">
                <input type="hidden" name="maint_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="use_mysqldump" value="1" id="use_mysqldump_backup" <?= empty($tools['mysqldump']) ? 'disabled' : '' ?>>
                    <label class="form-check-label small" for="use_mysqldump_backup">Yedekte <code>mysqldump</code> kullan</label>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-cloud-arrow-down me-1"></i>Yeni SQL yedeği oluştur</button>
            </form>
            <form method="post" action="<?= htmlspecialchars(esh_url('DbMaintenance', 'createTableBackup'), ENT_QUOTES, 'UTF-8') ?>" class="m-0 d-flex flex-wrap align-items-center gap-2 justify-content-end border-start ps-2 ms-1">
                <input type="hidden" name="maint_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <select name="table" class="form-select form-select-sm" style="max-width: 220px;" required>
                    <option value="">Tablo seçin…</option>
                    <?php foreach ($tables as $t): ?>
                        <option value="<?= htmlspecialchars((string) ($t['t'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($t['t'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" name="use_mysqldump" value="1" id="use_mysqldump_table_backup" <?= empty($tools['mysqldump']) ? 'disabled' : '' ?>>
                    <label class="form-check-label small" for="use_mysqldump_table_backup"><code>mysqldump</code></label>
                </div>
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-table me-1"></i>Seçili tabloyu yedekle</button>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light"><tr><th class="ps-3">Dosya</th><th>Boyut</th><th>Tarih</th><th class="text-end pe-3">İşlemler</th></tr></thead>
                <tbody>
                    <?php if (empty($backups)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Henüz yedek yok.</td></tr>
                    <?php else: ?>
                        <?php foreach ($backups as $b): ?>
                            <tr>
                                <td class="ps-3">
                                    <code><?= htmlspecialchars((string) ($b['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
                                    <?php if (($b['scope'] ?? 'full') === 'table'): ?>
                                        <span class="badge bg-info text-dark ms-1">tablo<?= !empty($b['table']) ? ': ' . htmlspecialchars((string) $b['table'], ENT_QUOTES, 'UTF-8') : '' ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary ms-1">tam</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format((int) ($b['size'] ?? 0), 0, ',', '.') ?> bayt</td>
                                <td class="small"><?= \App\Helpers\DateHelper::unixToTrDotDateTimeOrEmpty(!empty($b['mtime']) ? (int) $b['mtime'] : null) ?></td>
                                <td class="text-end pe-3">
                                    <a class="btn btn-sm btn-outline-secondary" href="<?= htmlspecialchars(esh_url('DbMaintenance', 'download', ['file' => rawurlencode((string) ($b['name'] ?? ''))]), ENT_QUOTES, "UTF-8") ?>"><i class="fa-solid fa-download"></i></a>
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
