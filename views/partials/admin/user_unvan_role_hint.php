<?php

declare(strict_types=1);

use App\Helpers\AuthHelper;
use App\Services\PermissionService;

/** @var string $eshUnvanSelectId select element id (varsayılan: unvan) */
$eshUnvanSelectId = isset($eshUnvanSelectId) ? (string) $eshUnvanSelectId : 'unvan';
$eshUnvanRoleMap = PermissionService::tablesReady() ? PermissionService::unvanRoleMapForUi() : [];
$eshShowUnvanRoleHint = $eshUnvanRoleMap !== [] && PermissionService::hasUnvanLinkColumn();

if (!$eshShowUnvanRoleHint) {
    return;
}

?>
<div class="col-12" id="eshUnvanRoleHintWrap">
    <div class="alert alert-light border small py-2 mb-0">
        <i class="fa-solid fa-user-shield text-primary me-1"></i>
        <span class="text-muted">Ünvana bağlı yetki rolü:</span>
        <strong id="eshUnvanRoleHintLabel">—</strong>
    </div>
</div>
<script>
(function () {
    var map = <?= json_encode($eshUnvanRoleMap, JSON_UNESCAPED_UNICODE) ?>;
    var select = document.getElementById(<?= json_encode($eshUnvanSelectId) ?>);
    var label = document.getElementById('eshUnvanRoleHintLabel');
    if (!select || !label) {
        return;
    }
    function sync() {
        var code = select.value || '';
        var entry = map[code] || map[''] || null;
        label.textContent = entry && entry.name ? entry.name : '—';
    }
    select.addEventListener('change', sync);
    sync();
})();
</script>
