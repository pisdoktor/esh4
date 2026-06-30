<?php

declare(strict_types=1);

use App\Helpers\AuthHelper;
use App\Services\PermissionService;

/** @var object|null $user Kayıt yoksa create; varsa edit */

$eshTargetLevel = isset($user) && isset($user->isadmin) ? max(0, min(2, (int) $user->isadmin)) : AuthHelper::ROLE_STAFF;
$eshAssignableLevels = AuthHelper::assignableAdminLevels();
$eshShowRoleSelect = count($eshAssignableLevels) > 1;
$eshManualRoles = AuthHelper::sessionIsSuperAdmin() && PermissionService::tablesReady()
    ? PermissionService::manualAssignableRoles()
    : [];
$eshCurrentRoleId = 0;
$eshCurrentUnvan = isset($user) ? (string) ($user->unvan ?? '') : '';
$eshExpectedRoleId = PermissionService::roleIdForUnvanCode($eshCurrentUnvan !== '' ? $eshCurrentUnvan : null);
if (isset($user) && !empty($user->id) && PermissionService::tablesReady()) {
    $eshCurrentRoleId = PermissionService::roleIdForUser((int) $user->id);
}
$eshRoleOverrideId = 0;
if ($eshCurrentRoleId > 0 && $eshExpectedRoleId > 0 && $eshCurrentRoleId !== $eshExpectedRoleId) {
    $eshRoleOverrideId = $eshCurrentRoleId;
}

?>
<div class="col-md-6">
    <label class="form-label fw-semibold small">Yetki seviyesi</label>
    <?php if ($eshShowRoleSelect): ?>
        <select name="isadmin_level" class="form-select" id="eshIsadminLevelSelect">
            <?php foreach ($eshAssignableLevels as $level): ?>
                <option value="<?= $level ?>"<?= $eshTargetLevel === $level ? ' selected' : '' ?>><?= htmlspecialchars(AuthHelper::adminLevelLabel($level), ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    <?php else: ?>
        <input type="hidden" name="isadmin_level" value="0">
        <div class="form-control bg-light text-muted small"><?= htmlspecialchars(AuthHelper::adminLevelLabel(AuthHelper::ROLE_STAFF), ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</div>

<?php if ($eshManualRoles !== []): ?>
<div class="col-md-6" id="eshRoleOverrideField"<?= $eshTargetLevel >= AuthHelper::ROLE_ADMIN ? ' style="display:none"' : '' ?>>
    <label class="form-label fw-semibold small" for="eshRoleOverrideSelect">Özel yetki rolü (isteğe bağlı)</label>
    <select name="role_override_id" class="form-select" id="eshRoleOverrideSelect"<?= $eshTargetLevel >= AuthHelper::ROLE_ADMIN ? ' disabled' : '' ?>>
        <option value="">Ünvana göre otomatik</option>
        <?php foreach ($eshManualRoles as $role): ?>
            <option value="<?= (int) $role['id'] ?>"<?= $eshRoleOverrideId === (int) $role['id'] ? ' selected' : '' ?>>
                <?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
    <div class="form-text">Boş bırakılırsa ünvan alanındaki seçime göre rol atanır (doktor, hemşire vb.).</div>
</div>
<script>
(function () {
    var levelSelect = document.getElementById('eshIsadminLevelSelect');
    var overrideField = document.getElementById('eshRoleOverrideField');
    var overrideSelect = document.getElementById('eshRoleOverrideSelect');
    if (!levelSelect || !overrideField || !overrideSelect) {
        return;
    }
    function syncOverrideField() {
        var isStaff = parseInt(levelSelect.value, 10) === 0;
        overrideField.style.display = isStaff ? '' : 'none';
        overrideSelect.disabled = !isStaff;
    }
    levelSelect.addEventListener('change', syncOverrideField);
    syncOverrideField();
})();
</script>
<?php endif; ?>
