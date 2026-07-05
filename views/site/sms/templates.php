<div class="esh-page container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0"><i class="fa-solid fa-file-lines me-2"></i>SMS Şablonları</h1>
        <a href="<?= htmlspecialchars(esh_url('Sms', 'compose'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">Gönderime dön</a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success small"><?= htmlspecialchars((string) $_SESSION['success'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger small"><?= htmlspecialchars((string) $_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light"><tr><th>Başlık</th><th>Kod</th><th>Aktif</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach (($sablonlar ?? []) as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($s->baslik ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><code><?= htmlspecialchars((string) ($s->kod ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                                <td><?= (int) ($s->aktif ?? 0) ? 'Evet' : 'Hayır' ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary sms-edit-template"
                                        data-id="<?= (int) ($s->id ?? 0) ?>">Düzenle</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Şablon kaydet</div>
                <div class="card-body">
                    <form method="post" action="<?= htmlspecialchars($saveTemplateUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <?= esh_csrf_field() ?>
                        <input type="hidden" name="id" id="tpl-id" value="0">
                        <div class="mb-2">
                            <label class="form-label">Başlık</label>
                            <input type="text" class="form-control" name="baslik" id="tpl-baslik" required maxlength="255">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Kod</label>
                            <input type="text" class="form-control" name="kod" id="tpl-kod" maxlength="64">
                        </div>
                        <div class="mb-2">
                            <label class="form-label d-inline-flex align-items-center">
                                Mesaj gövdesi
                                <?php require __DIR__ . '/partials/sablon_degiskenleri_tooltip.php'; ?>
                            </label>
                            <textarea class="form-control" name="govde" id="tpl-govde" rows="6" maxlength="1600" required></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="aktif" id="tpl-aktif" value="1" checked>
                            <label class="form-check-label" for="tpl-aktif">Aktif</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script<?= esh_csp_nonce_attr() ?>>
window.ESH_SMS_TEMPLATES = {
    sablonMap: <?= json_encode((object) ($smsSablonMap ?? []), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP) ?>
};
(function () {
    function normalizeSablonMap(raw) {
        var map = {};
        if (!raw || typeof raw !== 'object') return map;
        Object.keys(raw).forEach(function (key) {
            var row = raw[key];
            var rid = parseInt(String((row && row.id) || key || 0), 10);
            if (rid > 0) map[rid] = row;
        });
        return map;
    }
    var map = normalizeSablonMap((window.ESH_SMS_TEMPLATES || {}).sablonMap);
    document.querySelectorAll('.sms-edit-template').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = parseInt(btn.getAttribute('data-id') || '0', 10);
            var row = map[id] || map[String(id)] || null;
            if (!row) return;
            document.getElementById('tpl-id').value = String(row.id || id);
            document.getElementById('tpl-baslik').value = row.baslik || '';
            document.getElementById('tpl-kod').value = row.kod || '';
            document.getElementById('tpl-govde').value = row.govde || '';
            document.getElementById('tpl-aktif').checked = parseInt(String(row.aktif || 0), 10) === 1;
        });
    });
})();
</script>
