<?php
/**
 * MERNIS vefat taraması — aktif / bekleyen kapsam seçici (SEF yönlendirme).
 *
 * @var string $scanScope active|waiting
 */
$eshScanScope = (string) ($scanScope ?? 'active');
$eshScanScopeUrls = [
    'active' => esh_url('Patient', 'scan'),
    'waiting' => esh_url('Patient', 'scanWaiting'),
];
?>
<div class="mb-3 d-flex flex-wrap align-items-end gap-2 esh-scan-scope-filter">
    <div class="col-auto">
        <label class="form-label small text-muted mb-1" for="esh-scan-scope-select">Tarama kapsamı</label>
        <select id="esh-scan-scope-select" class="form-select form-select-sm shadow-sm esh-filter-control" aria-label="Tarama kapsamı">
            <option value="<?= htmlspecialchars($eshScanScopeUrls['active'], ENT_QUOTES, 'UTF-8') ?>"<?= $eshScanScope === 'active' ? ' selected' : '' ?>>Aktif hastalar</option>
            <option value="<?= htmlspecialchars($eshScanScopeUrls['waiting'], ENT_QUOTES, 'UTF-8') ?>"<?= $eshScanScope === 'waiting' ? ' selected' : '' ?>>Bekleyen (ilk kayıt) hastalar</option>
        </select>
    </div>
</div>
<script>
(function () {
    var sel = document.getElementById('esh-scan-scope-select');
    if (!sel) {
        return;
    }
    sel.addEventListener('change', function () {
        if (this.value) {
            window.location.href = this.value;
        }
    });
})();
</script>
