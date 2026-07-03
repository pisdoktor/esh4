<?php
/**
 * ICD-10 katalog seçici — arama API + shuttle.
 *
 * @var string $catalogPickerShuttleId
 * @var string $catalogPickerShuttleFetchUrl
 * @var string $catalogPickerShuttleSearchUrl
 * @var string $catalogPickerShuttleCatalogLabel
 * @var string $catalogPickerShuttleAssignedLabel
 * @var array  $categories
 * @var string $eshHastalikListCat
 */
$shuttleId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($catalogPickerShuttleId ?? 'esh-hastalik-shuttle'));
$catVal = isset($eshHastalikListCat) ? (string) $eshHastalikListCat : '';
?>
<?= function_exists('esh_csrf_field') ? esh_csrf_field() : '' ?>
<div id="<?= htmlspecialchars($shuttleId, ENT_QUOTES, 'UTF-8') ?>"
     class="esh-hastalik-catalog-picker"
     data-esh-fetch-url="<?= htmlspecialchars((string) ($catalogPickerShuttleFetchUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-esh-search-url="<?= htmlspecialchars((string) ($catalogPickerShuttleSearchUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-esh-cat-filter="<?= htmlspecialchars($catVal, ENT_QUOTES, 'UTF-8') ?>">
    <div class="esh-shuttle-form-fields" aria-hidden="true"></div>
    <div class="row g-2 mb-3">
        <div class="col-md-8">
            <label class="form-label fw-semibold small mb-1" for="<?= $shuttleId ?>-search">ICD / tanı ara</label>
            <div class="input-group input-group-sm">
                <input type="search"
                       id="<?= $shuttleId ?>-search"
                       class="form-control esh-hastalik-picker-search"
                       placeholder="En az 2 karakter (ör. I10, diyabet)"
                       autocomplete="off"
                       aria-label="Tanı ara">
                <button type="button" class="btn btn-outline-primary esh-hastalik-picker-search-btn">Ara</button>
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold small mb-1" for="<?= $shuttleId ?>-cat">Kategori</label>
            <select id="<?= $shuttleId ?>-cat" class="form-select form-select-sm esh-hastalik-picker-cat">
                <option value="">Tüm kategoriler</option>
                <?php foreach (($categories ?? []) as $cat): ?>
                    <?php $cid = (int) ($cat->id ?? 0); if ($cid <= 0) { continue; } ?>
                    <option value="<?= $cid ?>"<?= $catVal === (string) $cid ? ' selected' : '' ?>><?= htmlspecialchars((string) ($cat->name ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row g-3 align-items-stretch esh-hastalik-picker-grid">
        <div class="col-md-5">
            <label class="form-label fw-semibold small mb-1" for="<?= $shuttleId ?>-catalog">
                <?= htmlspecialchars((string) ($catalogPickerShuttleCatalogLabel ?? 'Platform kataloğu'), ENT_QUOTES, 'UTF-8') ?>
            </label>
            <select id="<?= $shuttleId ?>-catalog"
                    class="form-select esh-shuttle-catalog"
                    multiple
                    size="14"
                    aria-label="Platform kataloğu"></select>
            <p class="small text-muted mt-1 mb-0 esh-hastalik-picker-catalog-hint">Arama sonuçları burada listelenir.</p>
        </div>
        <div class="col-md-2 d-flex flex-column justify-content-center align-items-center gap-2 py-2">
            <button type="button"
                    class="btn btn-outline-primary esh-shuttle-add"
                    title="Seçilenleri kuruma ekle"
                    aria-label="Kuruma ekle">
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
            <button type="button"
                    class="btn btn-outline-secondary esh-shuttle-remove"
                    title="Seçilenleri kurumdan çıkar"
                    aria-label="Kurumdan çıkar">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>
        </div>
        <div class="col-md-5">
            <label class="form-label fw-semibold small mb-1">
                <?= htmlspecialchars((string) ($catalogPickerShuttleAssignedLabel ?? 'Kurum seçimleri'), ENT_QUOTES, 'UTF-8') ?>
            </label>
            <div class="esh-shuttle-assigned border rounded bg-light p-2"
                 style="min-height:20rem; max-height:20rem; overflow-y:auto;"
                 role="listbox"
                 aria-label="Kurum seçimleri">
                <p class="esh-shuttle-assigned-empty small text-muted text-center py-5 mb-0">Henüz seçim yok.</p>
            </div>
        </div>
    </div>
    <div class="esh-shuttle-status small text-danger mt-2 d-none" role="alert"></div>
    <div class="esh-shuttle-loading text-center text-muted py-5">
        <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
        <span class="ms-2">Kurum seçimleri yükleniyor…</span>
    </div>
</div>
<script src="<?= htmlspecialchars(ASSETS_URL . '/pages/js/hastalik-catalog-picker.js', ENT_QUOTES, 'UTF-8') ?>"></script>
