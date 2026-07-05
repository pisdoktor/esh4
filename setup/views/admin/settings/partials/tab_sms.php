<?php
declare(strict_types=1);

/** @var list<array<string, mixed>> $smsFields */
/** @var array{configured:bool,provider:string,api_user:string,masked_password:string,masked_api_key:string,sender_id:string,test_phone:string,updated_at:string} $smsCredentialStatus */
/** @var bool $smsModuleEnabled */
/** @var bool $smsIsSuperAdmin */

$smsFields = $smsFields ?? [];
$smsCredentialStatus = $smsCredentialStatus ?? ['configured' => false, 'provider' => 'mock', 'api_user' => '', 'masked_password' => '', 'masked_api_key' => '', 'sender_id' => '', 'test_phone' => '', 'updated_at' => ''];
$smsIsSuperAdmin = $smsIsSuperAdmin ?? false;
?>
            <div class="alert alert-info border-0 small mb-4" role="alert">
                <i class="fa-solid fa-circle-info me-1"></i>
                Türkiye'de SMS, BTK lisanslı aracılar (Netgsm, İletiMerkezi, TurkeySMS vb.) üzerinden iletilir.
                Bilgilendirme SMS'leri İYS kapsamı dışındadır; BTK onaylı gönderici başlığı gereklidir.
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">Genel ayarlar</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php $operationalFields = $smsFields;
                        include __DIR__ . '/operational_fields.php'; ?>
                    </div>
                </div>
            </div>

            <?php if ($smsIsSuperAdmin): ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3"><h5 class="mb-0 fw-bold">SMS sağlayıcı</h5></div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        API kimlik bilgileri <code>storage/sms/credentials.json</code> dosyasına yazılır.
                    </p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="sms_provider">Sağlayıcı</label>
                            <select class="form-select" id="sms_provider" name="sms_provider">
                                <?php foreach (\App\Helpers\SmsSettings::PROVIDERS as $p): ?>
                                <option value="<?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?>"<?= ($smsCredentialStatus['provider'] ?? '') === $p ? ' selected' : '' ?>>
                                    <?= htmlspecialchars(\App\Helpers\SmsSettings::providerLabel($p), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="sms_sender_id">Gönderici başlığı (Sender ID)</label>
                            <input type="text" class="form-control" id="sms_sender_id" name="sms_sender_id" maxlength="11"
                                   value="<?= htmlspecialchars((string) ($smsCredentialStatus['sender_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="KURUMADI">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="sms_api_user">API kullanıcı adı</label>
                            <input type="text" class="form-control" id="sms_api_user" name="sms_api_user" autocomplete="off"
                                   value="<?= htmlspecialchars((string) ($smsCredentialStatus['api_user'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="sms_api_password">API şifre / hash</label>
                            <input type="password" class="form-control" id="sms_api_password" name="sms_api_password" autocomplete="new-password"
                                   placeholder="<?= !empty($smsCredentialStatus['masked_password']) ? 'Değiştirmek için yeni değer girin' : 'Şifre girin' ?>">
                            <?php if (!empty($smsCredentialStatus['masked_password'])): ?>
                                <div class="form-text">Kayıtlı: <code><?= htmlspecialchars((string) $smsCredentialStatus['masked_password'], ENT_QUOTES, 'UTF-8') ?></code></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="sms_api_key">API anahtarı</label>
                            <input type="password" class="form-control" id="sms_api_key" name="sms_api_key" autocomplete="new-password"
                                   placeholder="<?= !empty($smsCredentialStatus['masked_api_key']) ? 'Değiştirmek için yeni anahtar girin' : 'TurkeySMS / özel API' ?>">
                            <?php if (!empty($smsCredentialStatus['masked_api_key'])): ?>
                                <div class="form-text">Kayıtlı: <code><?= htmlspecialchars((string) $smsCredentialStatus['masked_api_key'], ENT_QUOTES, 'UTF-8') ?></code></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="sms_test_phone">Test telefonu</label>
                            <input type="text" class="form-control" id="sms_test_phone" name="sms_test_phone"
                                   value="<?= htmlspecialchars((string) ($smsCredentialStatus['test_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="05xxxxxxxxx">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Test SMS</h5>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="smsTestConnectionBtn">
                        <i class="fa-solid fa-paper-plane me-1"></i>Test gönder
                    </button>
                </div>
                <div class="card-body">
                    <pre id="smsTestConnectionResult" class="small bg-light border rounded p-3 mb-0 text-muted">Henüz test yapılmadı. Önce ayarları kaydedin.</pre>
                </div>
            </div>
            <script<?= esh_csp_nonce_attr() ?>>
            (function () {
                var btn = document.getElementById('smsTestConnectionBtn');
                var out = document.getElementById('smsTestConnectionResult');
                if (!btn || !out) return;
                btn.addEventListener('click', function () {
                    btn.disabled = true;
                    out.textContent = 'Test ediliyor…';
                    fetch(<?= json_encode(esh_url('Sms', 'testConnection'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({})
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            out.textContent = JSON.stringify(data, null, 2);
                        })
                        .catch(function (e) {
                            out.textContent = 'Hata: ' + e;
                        })
                        .finally(function () {
                            btn.disabled = false;
                        });
                });
            })();
            </script>
            <?php else: ?>
            <div class="alert alert-secondary small">
                SMS sağlayıcı kimlik bilgileri yalnızca platform yöneticisi tarafından yapılandırılır.
                Gönderici başlığı: <strong><?= htmlspecialchars((string) ($smsCredentialStatus['sender_id'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <?php endif; ?>

            <div class="alert alert-light border small mb-0">
                <strong>İleride:</strong> Ticari SMS ve İYS izin entegrasyonu Faz 2+ ile eklenecektir.
            </div>
