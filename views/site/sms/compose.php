<div class="esh-page esh-page--sms-compose container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="fa-solid fa-paper-plane me-2 text-primary"></i>SMS Gönder</h1>
            <p class="text-muted small mb-0">Kitle seçin, mesajı hazırlayın, önizleyip gönderin</p>
        </div>
        <a href="<?= htmlspecialchars(esh_url('Sms', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Geçmiş</a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger small"><?= htmlspecialchars((string) $_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="post" action="<?= htmlspecialchars($sendUrl ?? '', ENT_QUOTES, 'UTF-8') ?>" id="sms-compose-form">
        <?= esh_csrf_field() ?>
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">1. Kitle</div>
                    <div class="card-body">
                        <?php foreach (($segments ?? []) as $seg): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input sms-segment-radio" type="radio" name="segment" id="seg-<?= htmlspecialchars($seg, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($seg, ENT_QUOTES, 'UTF-8') ?>"
                                <?= ($presetSegment ?? '') === $seg || ($seg === 'tek_hasta' && ($hastaId ?? 0) > 0 && ($presetSegment ?? '') === '') ? ' checked' : '' ?>>
                            <label class="form-check-label" for="seg-<?= htmlspecialchars($seg, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(\App\Helpers\SmsSettings::segmentLabel($seg), ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        </div>
                        <?php endforeach; ?>

                        <div class="sms-segment-params mt-3 border-top pt-3">
                            <div class="mb-2 sms-param sms-param-tek_hasta sms-param-coklu_hasta">
                                <label class="form-label small">Hasta ID</label>
                                <input type="number" class="form-control form-control-sm" name="hasta_id" value="<?= (int) ($hastaId ?? 0) ?>" min="1">
                            </div>
                            <div class="mb-2 sms-param sms-param-gunun_plani sms-param-planli_izlem sms-param-ilk_ziyaret">
                                <label class="form-label small">Tarih</label>
                                <input type="date" class="form-control form-control-sm" name="tarih" value="<?= htmlspecialchars($presetTarih ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="mb-2 sms-param sms-param-gunun_plani">
                                <label class="form-label small">Zaman dilimi (0=tümü)</label>
                                <select class="form-select form-select-sm" name="zaman">
                                    <option value="0">Tümü</option>
                                    <?php foreach (\App\Helpers\ZamanDilimiHelper::uiSections() as $zSec): ?>
                                        <option value="<?= (int) $zSec['code'] ?>"><?= htmlspecialchars((string) $zSec['label'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-2 sms-param sms-param-sonda_yaklasan">
                                <label class="form-label small">Yaklaşan gün aralığı</label>
                                <input type="number" class="form-control form-control-sm" name="gun_araligi" value="7" min="1" max="90">
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm w-100 mt-2" id="sms-preview-btn">
                            <i class="fa-solid fa-eye me-1"></i>Alıcıları önizle
                        </button>
                        <div id="sms-preview-stats" class="small text-muted mt-2"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">2. Alıcı rolleri & mesaj</div>
                    <div class="card-body">
                        <?php foreach (\App\Helpers\SmsSettings::ROLES as $role): ?>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="roles[]" value="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>" id="role-<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>"
                                <?= in_array($role, $defaultRoles ?? [], true) ? ' checked' : '' ?>>
                            <label class="form-check-label" for="role-<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(\App\Helpers\SmsSettings::roleLabel($role), ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        </div>
                        <?php endforeach; ?>

                        <div class="mt-3">
                            <label class="form-label small">Şablon</label>
                            <select class="form-select form-select-sm" id="sms-sablon-select">
                                <option value="">— Serbest metin —</option>
                                <?php foreach (($sablonlar ?? []) as $s): ?>
                                <option value="<?= (int) ($s->id ?? 0) ?>">
                                    <?= htmlspecialchars((string) ($s->baslik ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="sablon_id" id="sms-sablon-id" value="">
                        </div>
                        <div class="mt-2">
                            <label class="form-label small d-inline-flex align-items-center" for="sms-govde">
                                Mesaj
                                <?php require __DIR__ . '/partials/sablon_degiskenleri_tooltip.php'; ?>
                            </label>
                            <textarea class="form-control" name="govde" id="sms-govde" rows="8" maxlength="1600" required placeholder="Sayın {{bakimveren_ad}}, {{hasta_ad_soyad}} için {{tarih}} …"></textarea>
                            <div class="form-text"><span id="sms-char-count">0</span> karakter · <span id="sms-part-count">0</span> SMS parçası</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">3. Önizleme & gönder</div>
                    <div class="card-body p-0 d-flex flex-column" style="min-height:320px">
                        <div class="table-responsive flex-grow-1" style="max-height:400px;overflow:auto">
                            <table class="table table-sm mb-0">
                                <thead class="table-light sticky-top"><tr><th>Hasta</th><th>Rol</th><th>Tel</th><th>Durum</th></tr></thead>
                                <tbody id="sms-preview-rows">
                                    <tr><td colspan="4" class="text-muted small p-3">Önizleme için «Alıcıları önizle» kullanın.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top">
                            <button type="submit" class="btn btn-primary w-100" id="sms-send-btn">
                                <i class="fa-solid fa-paper-plane me-1"></i>Gönder
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
window.ESH_SMS_COMPOSE = {
    previewUrl: <?= json_encode($previewUrl ?? '', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
    sablonMap: <?= json_encode((object) ($smsSablonMap ?? []), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP) ?>
};
</script>
