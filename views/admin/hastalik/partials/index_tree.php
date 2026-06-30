<?php
/**
 * ICD-10 hiyerarşik ağaç (platform kataloğu / kurum seçimi).
 *
 * @var bool $isCatalogPickerMode
 * @var string $saveSelectionUrl
 * @var array $hastalikTreeConfig
 */
$isCatalogPickerMode = !empty($isCatalogPickerMode);
$hastalikTreeConfig = is_array($hastalikTreeConfig ?? null) ? $hastalikTreeConfig : [];
?>
<?php if ($isCatalogPickerMode): ?>
<div class="alert alert-info small py-2 mx-3 mt-3 mb-0" role="status">
    Ağaçta 1., 2. veya 3. seviyeden tanı seçebilirsiniz; 1. seviye blok tüm alt tanıları ekler. Sağdaki listeden kontrol edip <strong>Seçimi kaydet</strong> ile onaylayın.
</div>
<form method="post" action="<?= htmlspecialchars($saveSelectionUrl ?? '', ENT_QUOTES, 'UTF-8') ?>" id="esh-hastalik-tree-form" class="esh-hastalik-tree-form">
<?= function_exists('esh_csrf_field') ? esh_csrf_field() : '' ?>
<?php endif; ?>

<div class="row g-0 esh-hastalik-tree-layout<?= $isCatalogPickerMode ? ' esh-hastalik-tree-layout--picker' : '' ?>">
    <div class="<?= $isCatalogPickerMode ? 'col-lg-7 border-end' : 'col-12' ?>">
        <div class="esh-hastalik-tree-panel px-3 py-3">
            <?php if (!$isCatalogPickerMode): ?>
            <p class="small text-muted mb-3 mb-md-2">Platform ICD-10 kataloğu — dalları genişleterek gezinin veya arayın.</p>
            <?php endif; ?>
            <div class="input-group input-group-sm mb-3 esh-hastalik-tree-search-wrap">
                <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="search" id="hastalikTreeSearch" class="form-control" placeholder="ICD kodu veya tanı adı ara (en az 2 karakter)…" autocomplete="off" value="<?= htmlspecialchars((string) ($hastalikTreeConfig['searchQ'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <button type="button" class="btn btn-outline-secondary" id="hastalikTreeSearchClear" title="Aramayı temizle"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div id="hastalikTreeMeta" class="small text-muted mb-2 d-none" aria-live="polite"></div>
            <ul id="hastalikTreeRoot" class="esh-hastalik-tree-root list-unstyled mb-0" role="tree" aria-label="ICD-10 tanı ağacı">
                <li class="text-muted small py-2">Ağaç yükleniyor…</li>
            </ul>
        </div>
    </div>
    <?php if ($isCatalogPickerMode): ?>
    <div class="col-lg-5">
        <div class="esh-hastalik-assigned-panel px-3 py-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="mb-0 fw-semibold"><i class="fa-solid fa-hospital-user me-1 text-primary"></i> Kurum tanıları</h6>
                <span class="badge text-bg-primary rounded-pill" id="hastalikAssignedCount">0</span>
            </div>
            <p class="small text-muted mb-2">Seçili tanılar kayıt öncesi burada listelenir.</p>
            <ul id="hastalikAssignedList" class="list-group list-group-flush esh-hastalik-assigned-list mb-0"></ul>
            <div id="hastalikAssignedEmpty" class="text-muted small py-3 text-center">Henüz tanı seçilmedi.</div>
            <div id="hastalikAssignedHidden" class="d-none"></div>
        </div>
        <div class="text-end px-3 pb-3">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-floppy-disk me-1"></i> Seçimi kaydet
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($isCatalogPickerMode): ?>
</form>
<?php endif; ?>

<script>
window.ESH_PAGE = window.ESH_PAGE || {};
window.ESH_PAGE.hastalikTree = <?= json_encode($hastalikTreeConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
