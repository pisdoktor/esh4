            <div class="alert alert-info border-0 small mb-4" role="alert">
                <i class="fa-solid fa-id-card me-1"></i>
                <strong>KPS TC kimlik doğrulama.</strong>
                SOAP sorgusu yetki ve resmi WSDL sonrası etkinleştirilecektir.
                Vefat sorgusu varsayılan olarak kapalıdır; açıldığında stub döneminde belediye yedeği kullanılabilir.
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Servis ayarları</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php $operationalFields = $kpsFields;
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Web servis kimlik bilgileri</h5></div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        Kullanıcı adı ve şifre <code>storage/kps/credentials.json</code> dosyasına yazılır; <code>public/assets/data/app-settings.json</code> içine kaydedilmez.
                    </p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="kps_username">Web servis kullanıcı adı</label>
                            <input type="text" class="form-control" id="kps_username" name="kps_username"
                                   value="<?= htmlspecialchars((string) ($kpsCredentialStatus['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                   autocomplete="off" placeholder="kpsyonetimv2 üzerinden alınan kullanıcı">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="kps_password">Web servis şifresi</label>
                            <input type="password" class="form-control" id="kps_password" name="kps_password"
                                   autocomplete="new-password" placeholder="<?= ($kpsCredentialStatus['configured'] ?? false) ? 'Değiştirmek için yeni şifre girin' : 'Şifre girin' ?>">
                            <?php if (!empty($kpsCredentialStatus['masked_password'])): ?>
                                <div class="form-text">Kayıtlı: <code><?= htmlspecialchars((string) $kpsCredentialStatus['masked_password'], ENT_QUOTES, 'UTF-8') ?></code></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Firma kodu (salt okunur)</h5></div>
                <div class="card-body">
                    <p class="small text-muted mb-2">
                        Yazılım firmasına Sağlık Bakanlığı tarafından verilen kod yalnızca <code>config.local.php</code> → <code>kps_firma_kodu</code> ile tanımlanır.
                    </p>
                    <?php if ($kpsFirmaStatus['configured'] ?? false): ?>
                        <p class="mb-1"><strong>Kod:</strong> <code><?= htmlspecialchars((string) ($kpsFirmaStatus['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></p>
                        <p class="small text-muted mb-0"><?= htmlspecialchars((string) ($kpsFirmaStatus['source'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php else: ?>
                        <p class="text-warning small mb-0"><i class="fa-solid fa-triangle-exclamation me-1"></i>Firma kodu tanımlı değil.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Bağlantı denemesi</h5>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="kpsTestConnectionBtn">
                        <i class="fa-solid fa-plug me-1"></i>Bağlantıyı dene
                    </button>
                </div>
                <div class="card-body">
                    <pre id="kpsTestConnectionResult" class="small bg-light border rounded p-3 mb-0 text-muted">Henüz test yapılmadı.</pre>
                </div>
            </div>
            <script<?= esh_csp_nonce_attr() ?>>
            (function () {
                var btn = document.getElementById('kpsTestConnectionBtn');
                var out = document.getElementById('kpsTestConnectionResult');
                if (!btn || !out) return;
                btn.addEventListener('click', function () {
                    btn.disabled = true;
                    out.textContent = 'Test ediliyor…';
                    fetch(<?= json_encode(esh_url('Kps', 'testConnection'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            out.textContent = JSON.stringify(data, null, 2);
                        })
                        .catch(function (err) {
                            out.textContent = 'İstek başarısız: ' + (err && err.message ? err.message : String(err));
                        })
                        .finally(function () { btn.disabled = false; });
                });
            })();
            </script>

