    <div class="card shadow-sm border-0 border-danger border-2 mt-4" id="geri">
        <div class="card-header bg-danger-subtle py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-danger"><i class="fa-solid fa-rotate-left me-1"></i>Yedekten geri yükle</h6>
        </div>
        <div class="card-body">
            <ol class="small text-muted">
                <li>Önce yukarıdan <strong>yeni yedek</strong> alın.</li>
                <li>Listeden dosyayı seçin; onay kutusuna tam olarak <code>YEDEKTEN_YUKLE</code> yazın.</li>
                <li>Geri yükleme için sunucuda <code>mysql</code> istemcisi (ör. XAMPP <code>mysql\bin</code>) gerekir; yoksa küçük dosyalar (&lt;50 MB) mysqli ile denenir.</li>
            </ol>
            <form method="post" action="<?= htmlspecialchars(esh_url('DbMaintenance', 'restoreBackup'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end" onsubmit="return confirm('Mevcut veritabanı bu yedekle değiştirilecek. Emin misiniz?');">
                <input type="hidden" name="maint_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Yedek dosyası</label>
                    <select name="file" class="form-select form-select-sm" required <?= empty($backups) ? 'disabled' : '' ?>>
                        <option value="">Seçin…</option>
                        <?php foreach ($backups as $b): ?>
                            <option value="<?= htmlspecialchars((string) ($b['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($b['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Onay (tam metin)</label>
                    <input type="text" name="restore_confirm" class="form-control form-control-sm" placeholder="YEDEKTEN_YUKLE" autocomplete="off" required <?= empty($backups) ? 'disabled' : '' ?>>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-danger w-100" <?= empty($backups) ? 'disabled' : '' ?>><i class="fa-solid fa-rotate-left me-1"></i>Geri yükle</button>
                </div>
            </form>
            <?php if (empty($backups)): ?>
                <p class="small text-muted mb-0 mt-2">Geri yükleme için önce bir yedek oluşturun.</p>
            <?php endif; ?>

            <hr class="my-4">
            <h6 class="fw-bold text-danger mb-2"><i class="fa-solid fa-table me-1"></i>Tek tablo geri yükle</h6>
            <p class="small text-muted">Yalnızca tablo yedeği dosyalarını kullanın. Seçili tablo yedekteki hâle getirilir (DROP + CREATE + veri).</p>
            <form method="post" action="<?= htmlspecialchars(esh_url('DbMaintenance', 'restoreTableBackup'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 align-items-end" id="restore-table-form" onsubmit="return confirm('Yalnızca seçili tablo yedekteki hâle getirilecek. Emin misiniz?');">
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
                    <select name="file" id="restore-table-file" class="form-select form-select-sm" required <?= empty($backups) ? 'disabled' : '' ?>>
                        <option value="">Seçin…</option>
                        <?php foreach ($backups as $b): ?>
                            <option value="<?= htmlspecialchars((string) ($b['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                data-scope="<?= htmlspecialchars((string) ($b['scope'] ?? 'full'), ENT_QUOTES, 'UTF-8') ?>"
                                data-table="<?= htmlspecialchars((string) ($b['table'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars((string) ($b['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Onay (tam metin)</label>
                    <input type="text" name="restore_confirm" class="form-control form-control-sm" placeholder="YEDEKTEN_YUKLE" autocomplete="off" required <?= empty($backups) ? 'disabled' : '' ?>>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-danger w-100" <?= empty($backups) ? 'disabled' : '' ?>><i class="fa-solid fa-rotate-left me-1"></i>Seçili tabloyu geri yükle</button>
                </div>
            </form>
            <script>
            (function () {
                var tableSel = document.getElementById('restore-table-select');
                var fileSel = document.getElementById('restore-table-file');
                if (!tableSel || !fileSel) return;
                var allOpts = Array.prototype.slice.call(fileSel.querySelectorAll('option'));
                function filterFiles() {
                    var t = tableSel.value;
                    var cur = fileSel.value;
                    fileSel.innerHTML = '';
                    allOpts.forEach(function (opt) {
                        if (!opt.value) {
                            fileSel.appendChild(opt.cloneNode(true));
                            return;
                        }
                        if (opt.getAttribute('data-scope') !== 'table') return;
                        if (t && opt.getAttribute('data-table') !== t) return;
                        fileSel.appendChild(opt.cloneNode(true));
                    });
                    if (cur && fileSel.querySelector('option[value="' + CSS.escape(cur) + '"]')) {
                        fileSel.value = cur;
                    }
                }
                tableSel.addEventListener('change', filterFiles);
                filterFiles();
            })();
            </script>
        </div>
    </div>
