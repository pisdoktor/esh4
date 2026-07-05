<?php
/**
 * Hasta yara fotoğrafları — tam galeri sayfası.
 *
 * @var object $hasta
 * @var array $woundPhotos
 * @var bool $pasifDosyaKapali
 */
$hastaId = (string) ($hasta->id ?? '');
$viewUrl = esh_url('Patient', 'view', ['id' => $hastaId]);
$photoCount = (int) count($woundPhotos ?? []);
?>
<div class="container-lg py-4 esh-page-patient-wounds">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div class="min-w-0">
                    <h1 class="h5 mb-0 fw-bold text-danger">
                        <i class="fa-solid fa-camera me-2"></i>Yara fotoğrafları
                    </h1>
                    <p class="small text-muted mb-0 mt-1">
                        <?= htmlspecialchars(trim(($hasta->isim ?? '') . ' ' . ($hasta->soyisim ?? '')), ENT_QUOTES, 'UTF-8') ?>
                        · <span class="font-monospace"><?= \App\Helpers\ValidationHelper::formatTc((string) ($hasta->tckimlik ?? '')) ?></span>
                    </p>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-auto flex-shrink-0">
                    <span class="badge bg-danger-subtle text-danger border fs-6 px-3 py-2">
                        <?= $photoCount ?> fotoğraf
                    </span>
                    <a href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-left me-1"></i>Hasta kartı
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 border-top border-danger border-4">
        <div class="card-body p-3 p-md-4">
            <?php include ROOT_PATH . '/views/site/hasta/partials/wound_photos_gallery.php'; ?>
            <?php include ROOT_PATH . '/views/site/hasta/partials/wound_photos_upload.php'; ?>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/views/site/hasta/partials/wound_photo_modal.php'; ?>
