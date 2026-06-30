<?php
/**
 * Aurora Light teması — site/hasta/wounds
 * Yol: templates/aurora/site/hasta/wounds.php
 * Canonical: views/site/hasta/wounds.php
 *
 * @var object $hasta
 * @var array $woundPhotos
 * @var bool $pasifDosyaKapali
 */
$hastaId = (int) ($hasta->id ?? 0);
$viewUrl = esh_url('Patient', 'view', ['id' => $hastaId]);
$photoCount = (int) count($woundPhotos ?? []);
?>
<div class="container-lg py-4 esh-page-patient-wounds">
    <section class="au-panel mb-4">
        <div class="au-panel__head au-panel__head--split">
            <div class="d-flex align-items-center gap-3 min-w-0">
                <span class="au-icon-chip au-icon-chip--amber"><i class="fa-solid fa-camera"></i></span>
                <div class="min-w-0">
                    <h1 class="au-panel__title mb-0">Yara fotoğrafları</h1>
                    <p class="au-panel__sub mb-0">
                        <?= htmlspecialchars(trim(($hasta->isim ?? '') . ' ' . ($hasta->soyisim ?? '')), ENT_QUOTES, 'UTF-8') ?>
                        · <span class="font-monospace"><?= \App\Helpers\ValidationHelper::formatTc((string) ($hasta->tckimlik ?? '')) ?></span>
                    </p>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
                <span class="badge bg-danger-subtle text-danger border fs-6 px-3 py-2">
                    <?= $photoCount ?> fotoğraf
                </span>
                <a href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
                    <i class="fa-solid fa-arrow-left me-1"></i>Hasta kartı
                </a>
            </div>
        </div>
    </section>

    <div class="au-panel">
        <div class="au-panel__body">
            <?php include \App\Helpers\ThemeViewHelper::resolveAreaView('site', 'hasta/partials/wound_photos_gallery'); ?>
            <?php include \App\Helpers\ThemeViewHelper::resolveAreaView('site', 'hasta/partials/wound_photos_upload'); ?>
        </div>
    </div>
</div>

<?php include \App\Helpers\ThemeViewHelper::resolveAreaView('site', 'hasta/partials/wound_photo_modal'); ?>
