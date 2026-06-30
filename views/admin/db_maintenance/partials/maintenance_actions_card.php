        <div class="col-xl-6" id="bakim">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold">Tablo bakımı</h6>
                    <p class="small text-muted mb-0 mt-1">InnoDB tablolarında OPTIMIZE tabloyu yeniden oluşturur; yoğun saatlerde dikkatli kullanın.</p>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3">
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
                    <?php if (!empty($maintResults) && is_array($maintResults)): ?>
                        <div class="table-responsive" style="max-height: 280px;">
                            <table class="table table-sm table-bordered mb-0 small">
                                <thead class="table-light"><tr><th>Tablo</th><th>İşlem</th><th>Tip</th><th>Mesaj</th></tr></thead>
                                <tbody>
                                    <?php foreach ($maintResults as $row): ?>
                                        <tr>
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
        </div>
