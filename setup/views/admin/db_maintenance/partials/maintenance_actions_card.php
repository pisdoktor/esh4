<?php
/** @var array $tables */
/** @var string $token */
/** @var array|null $maintResults */
$tableNames = array_map(static fn ($t) => (string) ($t['t'] ?? ''), $tables);
$tableNames = array_values(array_filter($tableNames));
$mysqldumpOk = !empty($tools['mysqldump']);
?>
<div class="card shadow-sm border-0" id="bakim">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="mb-0 fw-bold">Tablo bakımı</h6>
        <p class="small text-muted mb-0 mt-1">InnoDB tablolarında OPTIMIZE tabloyu yeniden oluşturur. Önbellek tabloları (<code>esh_rota_cache</code>) tüm tablolar işleminde hariç tutulur.</p>
    </div>
    <div class="card-body">
        <h6 class="small fw-semibold text-muted text-uppercase mb-2">Tüm tablolar</h6>
        <div class="d-flex flex-wrap gap-2 mb-4">
            <form method="post" action="<?= htmlspecialchars(esh_url('DbMaintenance', 'check'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                <input type="hidden" name="maint_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-stethoscope me-1"></i>CHECK TABLE</button>
            </form>
            <form method="post" action="<?= htmlspecialchars(esh_url('DbMaintenance', 'analyze'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline">
                <input type="hidden" name="maint_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-outline-info btn-sm"><i class="fa-solid fa-chart-simple me-1"></i>ANALYZE TABLE</button>
            </form>
            <form method="post" action="<?= htmlspecialchars(esh_url('DbMaintenance', 'optimize'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline" onsubmit="return confirm('Tüm tablolarda OPTIMIZE çalıştırılsın mı? Büyük veritabanlarında uzun sürebilir.');">
                <input type="hidden" name="maint_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-outline-warning btn-sm"><i class="fa-solid fa-broom me-1"></i>OPTIMIZE TABLE</button>
            </form>
        </div>

        <h6 class="small fw-semibold text-muted text-uppercase mb-2">Seçili tablolar</h6>
        <form method="post" id="db-maint-selected-form" class="row g-2 align-items-end mb-4">
            <input type="hidden" name="maint_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
            <div class="col-md-8">
                <label class="form-label small mb-1">Tablolar (Ctrl ile çoklu seçim)</label>
                <select name="tables[]" id="db-maint-maint-tables" class="form-select form-select-sm" multiple size="6" required>
                    <?php foreach ($tableNames as $name): ?>
                        <option value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex flex-wrap gap-2">
                <button type="submit" formaction="<?= htmlspecialchars(esh_url('DbMaintenance', 'checkTable'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm">CHECK</button>
                <button type="submit" formaction="<?= htmlspecialchars(esh_url('DbMaintenance', 'analyzeTable'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-info btn-sm">ANALYZE</button>
                <button type="submit" formaction="<?= htmlspecialchars(esh_url('DbMaintenance', 'optimizeTable'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-warning btn-sm" onclick="return confirm('Seçili tablolarda OPTIMIZE çalıştırılsın mı?');">OPTIMIZE</button>
            </div>
        </form>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h6 class="small fw-semibold text-muted text-uppercase mb-0">Sonuçlar</h6>
            <?php if (!empty($maintResults) && is_array($maintResults)): ?>
                <select id="db-maint-result-filter" class="form-select form-select-sm" style="max-width: 180px;">
                    <option value="">Tüm sonuçlar</option>
                    <option value="status">Başarılı</option>
                    <option value="warning">Uyarı</option>
                    <option value="error">Hata</option>
                </select>
            <?php endif; ?>
        </div>
        <?php if (!empty($maintResults) && is_array($maintResults)): ?>
            <div class="table-responsive" style="max-height: 320px;">
                <table class="table table-sm table-bordered mb-0 small" id="db-maint-results-table">
                    <thead class="table-light"><tr><th>Tablo</th><th>İşlem</th><th>Tip</th><th>Mesaj</th></tr></thead>
                    <tbody>
                        <?php foreach ($maintResults as $row): ?>
                            <?php $msgType = strtolower((string) ($row['msg_type'] ?? '')); ?>
                            <tr class="db-maint-result-row" data-msg-type="<?= htmlspecialchars($msgType, ENT_QUOTES, 'UTF-8') ?>">
                                <td><code><?= htmlspecialchars((string) ($row['table'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><?= htmlspecialchars((string) ($row['op'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($row['msg_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-break"><?= htmlspecialchars((string) ($row['msg_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted small mb-0">Sonuç görmek için yukarıdan bir işlem seçin.</p>
        <?php endif; ?>
    </div>
</div>
