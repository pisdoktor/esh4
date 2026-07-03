<?php
/**
 * İki kolonlu katalog seçici (platform ↔ kurum).
 *
 * @var string $catalogPickerShuttleId
 * @var string $catalogPickerShuttleFetchUrl
 * @var string $catalogPickerShuttleCatalogLabel
 * @var string $catalogPickerShuttleAssignedLabel
 * @var bool   $catalogPickerShuttleShowKota
 */
$shuttleId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($catalogPickerShuttleId ?? 'esh-catalog-shuttle'));
$showKota = !empty($catalogPickerShuttleShowKota);
?>
<?= function_exists('esh_csrf_field') ? esh_csrf_field() : '' ?>
<div id="<?= htmlspecialchars($shuttleId, ENT_QUOTES, 'UTF-8') ?>"
     class="esh-catalog-picker-shuttle"
     data-esh-fetch-url="<?= htmlspecialchars((string) ($catalogPickerShuttleFetchUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-esh-show-kota="<?= $showKota ? '1' : '0' ?>">
    <div class="esh-shuttle-form-fields" aria-hidden="true"></div>
    <div class="row g-3 align-items-stretch">
        <div class="col-md-5">
            <label class="form-label fw-semibold small mb-1" for="<?= $shuttleId ?>-catalog">
                <?= htmlspecialchars((string) ($catalogPickerShuttleCatalogLabel ?? 'Platform kataloğu'), ENT_QUOTES, 'UTF-8') ?>
            </label>
            <select id="<?= $shuttleId ?>-catalog"
                    class="form-select esh-shuttle-catalog"
                    multiple
                    size="16"
                    aria-label="Platform kataloğu"></select>
            <p class="small text-muted mt-1 mb-0">Çoklu seçim: Ctrl veya Shift ile birden fazla kayıt işaretleyin.</p>
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
            <?php if ($showKota): ?>
            <div class="d-none d-md-flex small text-muted border-bottom pb-1 mb-1 px-1">
                <span class="flex-grow-1">Branş</span>
                <span style="width:6.5rem">Günlük kota</span>
            </div>
            <?php endif; ?>
            <div class="esh-shuttle-assigned border rounded bg-light p-2"
                 style="min-height:22rem; max-height:22rem; overflow-y:auto;"
                 role="listbox"
                 aria-label="Kurum seçimleri">
                <p class="esh-shuttle-assigned-empty small text-muted text-center py-5 mb-0">Henüz seçim yok.</p>
            </div>
        </div>
    </div>
    <div class="esh-shuttle-status small text-danger mt-2 d-none" role="alert"></div>
    <div class="esh-shuttle-loading text-center text-muted py-5">
        <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
        <span class="ms-2">Liste yükleniyor…</span>
    </div>
</div>
<script src="<?= htmlspecialchars(ASSETS_URL . '/pages/js/catalog-picker-shuttle.js', ENT_QUOTES, 'UTF-8') ?>"></script>
