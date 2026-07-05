<?php

declare(strict_types=1);

use App\Helpers\FormHelper;
use App\Helpers\AuthHelper;
use App\Helpers\FederationHelper;
use App\Models\FederationRegion;

/** @var object|null $user */
$eshShowBolgeScope = AuthHelper::sessionIsPlatformOwner()
    && FederationHelper::columnsReady()
    && FederationHelper::enabled();
$eshTargetLevel = isset($user) && isset($user->isadmin)
    ? AuthHelper::clampLevel((int) $user->isadmin)
    : AuthHelper::ROLE_STAFF;
$eshUserBolgeId = isset($user) && isset($user->bolge_id) && $user->bolge_id !== null && (int) $user->bolge_id > 0
    ? (int) $user->bolge_id
    : 0;
$eshBolgeOptions = [FormHelper::makeOption('', 'Bölge seçiniz…')];
foreach ((new FederationRegion())->getList(true) as $bolgeRow) {
    $bid = (int) ($bolgeRow->id ?? 0);
    if ($bid <= 0) {
        continue;
    }
    $label = trim((string) ($bolgeRow->ad ?? ''));
    if (!empty($bolgeRow->kod)) {
        $label = $label !== '' ? $label . ' (' . (string) $bolgeRow->kod . ')' : (string) $bolgeRow->kod;
    }
    $eshBolgeOptions[] = FormHelper::makeOption((string) $bid, $label !== '' ? $label : ('Bölge #' . $bid));
}
?>
<?php if ($eshShowBolgeScope): ?>
<div class="col-md-6" id="eshUserBolgeScopeWrap"<?= $eshTargetLevel !== AuthHelper::ROLE_SUPERADMIN ? ' style="display:none"' : '' ?>>
    <?= FormHelper::fieldSelect('bolge_id', 'Bölge kapsamı', $eshBolgeOptions, (string) $eshUserBolgeId, [
        'id' => 'eshUserBolgeScopeSelect',
        'labelClass' => 'form-label fw-semibold small',
        'class' => 'form-select',
        'col' => '',
        'tomSelect' => false,
        'required' => true,
    ]) ?>
    <div class="form-text"><?= htmlspecialchars(AuthHelper::adminLevelLabel(AuthHelper::ROLE_SUPERADMIN), ENT_QUOTES, 'UTF-8') ?> yalnızca seçilen bölgedeki kurumların verisini görür ve yönetir. Bölge seçimi zorunludur.</div>
</div>
<script<?= esh_csp_nonce_attr() ?>>
(function () {
    var levelSelect = document.getElementById('eshIsadminLevelSelect');
    var bolgeWrap = document.getElementById('eshUserBolgeScopeWrap');
    var bolgeSelect = document.getElementById('eshUserBolgeScopeSelect');
    if (!levelSelect || !bolgeWrap || !bolgeSelect) {
        return;
    }
    var superLevel = '<?= (int) AuthHelper::ROLE_SUPERADMIN ?>';
    function syncBolgeField() {
        var isSuperOnly = String(levelSelect.value) === superLevel;
        bolgeWrap.style.display = isSuperOnly ? '' : 'none';
        bolgeSelect.disabled = !isSuperOnly;
        bolgeSelect.required = isSuperOnly;
        if (!isSuperOnly) {
            bolgeSelect.value = '';
        }
    }
    levelSelect.addEventListener('change', syncBolgeField);
    syncBolgeField();
})();
</script>
<?php endif; ?>
