<?php

declare(strict_types=1);

use App\Helpers\AuthHelper;
use App\Helpers\TenantContext;
use App\Models\Kurum;

/** @var object|null $user */
$eshIsSuperAdminEditor = AuthHelper::sessionIsSuperAdmin();
$eshKurumList = $eshIsSuperAdminEditor ? TenantContext::kurumListForScope(true) : [];
$eshUserKurumId = isset($user) && isset($user->kurum_id) && $user->kurum_id !== null && (int) $user->kurum_id > 0
    ? (int) $user->kurum_id
    : 0;
$eshTargetIsPlatform = isset($user) && AuthHelper::isPlatformLevel((int) ($user->isadmin ?? 0));
?>
<?php if ($eshIsSuperAdminEditor && $eshKurumList !== []): ?>
<div class="col-md-6" id="eshUserKurumFieldWrap"<?= $eshTargetIsPlatform ? ' style="display:none"' : '' ?>>
    <label class="form-label fw-semibold" for="eshUserKurumSelect">Kurum</label>
    <select name="kurum_id" class="form-select" id="eshUserKurumSelect">
        <option value="0"<?= $eshUserKurumId <= 0 ? ' selected' : '' ?>>
            — Platform (süper yönetici / sistem sahibi) —
        </option>
        <?php foreach ($eshKurumList as $k): ?>
            <option value="<?= (int) ($k->id ?? 0) ?>"<?= $eshUserKurumId === (int) ($k->id ?? 0) ? ' selected' : '' ?>>
                <?= htmlspecialchars((string) ($k->ad ?? ''), ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($k->kod)): ?>
                    (<?= htmlspecialchars((string) $k->kod, ENT_QUOTES, 'UTF-8') ?>)
                <?php endif; ?>
            </option>
        <?php endforeach; ?>
    </select>
    <div class="form-text" id="eshUserKurumHelp">
        Personel ve yöneticiler bir kuruma bağlı olmalıdır. Süper yönetici veya sistem sahibi seçildiğinde kurum atanmaz.
    </div>
</div>
<script>
(function () {
    var levelSelect = document.getElementById('eshIsadminLevelSelect');
    var kurumWrap = document.getElementById('eshUserKurumFieldWrap');
    var kurumSelect = document.getElementById('eshUserKurumSelect');
    if (!levelSelect || !kurumWrap || !kurumSelect) {
        return;
    }
    var superLevel = '<?= (int) AuthHelper::ROLE_SUPERADMIN ?>';
    var ownerLevel = '<?= (int) AuthHelper::ROLE_PLATFORM_OWNER ?>';
    function syncKurumField() {
        var level = String(levelSelect.value);
        var isPlatform = level === superLevel || level === ownerLevel;
        kurumWrap.style.display = isPlatform ? 'none' : '';
        kurumSelect.disabled = isPlatform;
        kurumSelect.required = !isPlatform;
        if (isPlatform) {
            kurumSelect.value = '0';
        } else if (kurumSelect.value === '0' || kurumSelect.value === '') {
            var firstOrg = kurumSelect.querySelector('option[value]:not([value="0"])');
            if (firstOrg) {
                kurumSelect.value = firstOrg.value;
            }
        }
    }
    levelSelect.addEventListener('change', syncKurumField);
    syncKurumField();
})();
</script>
<?php elseif (!$eshIsSuperAdminEditor): ?>
<div class="col-md-6">
    <label class="form-label fw-semibold" for="eshUserKurumReadonly">Kurum</label>
    <div class="form-control bg-light text-body" id="eshUserKurumReadonly" readonly tabindex="-1" aria-readonly="true">
        <?= htmlspecialchars(\App\Models\User::kurumDisplayLabel($user ?? new \stdClass()), ENT_QUOTES, 'UTF-8') ?>
    </div>
</div>
    <input type="hidden" name="kurum_id" value="<?= (int) (TenantContext::sessionKurumId() ?? 1) ?>">
<?php endif; ?>
