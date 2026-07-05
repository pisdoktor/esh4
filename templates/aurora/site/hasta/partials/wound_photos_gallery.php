<?php
/**
 * Yara fotoğrafları galerisi (üst: galeri + karşılaştırma; alt: yükleme ayrı partial ile).
 *
 * @var object $hasta
 * @var array $woundPhotos
 */
$hastaId = (int) ($hasta->id ?? 0);
$woundPhotos = isset($woundPhotos) && is_array($woundPhotos) ? $woundPhotos : [];
$visibleChunkSize = 12;
?>
<div class="esh-wound-gallery">
    <?php if (!empty($woundPhotos) && count($woundPhotos) >= 2): ?>
        <div class="mb-4 p-3 bg-white rounded border shadow-sm">
            <div class="small fw-bold text-muted mb-2"><i class="fa-solid fa-sliders me-1"></i> Seçilen 2 fotoğrafı karşılaştır</div>
            <div class="position-relative overflow-hidden rounded border" style="height: 280px;">
                <img id="beforeCompareImage" src="" alt="Önce" class="w-100 h-100" style="object-fit: cover;">
                <img id="afterCompareImage" src="" alt="Sonra" class="position-absolute top-0 start-0 h-100" style="width:50%; object-fit: cover;">
            </div>
            <input id="compareRange" type="range" min="0" max="100" value="50" class="form-range mt-2">
            <div class="d-flex justify-content-between small text-muted">
                <span id="beforeCompareLabel">Önce: -</span>
                <span id="afterCompareLabel">Sonra: -</span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($woundPhotos)): ?>
        <div class="row g-3">
            <?php foreach ($woundPhotos as $photoIndex => $photo): ?>
                <div class="col-6 col-md-4 col-lg-3 <?= ($photoIndex >= $visibleChunkSize) ? 'd-none wound-photo-hidden' : '' ?>" data-photo-card-index="<?= (int) $photoIndex ?>">
                    <div class="card border-0 shadow-sm h-100">
                        <a
                            href="#"
                            class="wound-photo-trigger"
                            data-bs-toggle="modal"
                            data-bs-target="#woundPhotoModal"
                            data-photo-index="<?= (int) $photo->id ?>"
                            data-full-url="<?= htmlspecialchars(esh_upload_url('wounds', (string) $photo->dosya_adi), ENT_QUOTES, 'UTF-8') ?>"
                            data-caption="<?= htmlspecialchars((string) ($photo->aciklama ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-meta="<?= htmlspecialchars(trim('Bölge: ' . (string) ($photo->yara_bolgesi ?? '') . ' | Evre: ' . (string) ($photo->yara_evresi ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                        >
                            <img
                                src="<?= htmlspecialchars(esh_upload_url('wounds', (string) $photo->dosya_adi), ENT_QUOTES, 'UTF-8') ?>"
                                alt="Yara fotoğrafı"
                                class="card-img-top"
                                style="height: 140px; object-fit: cover;"
                                loading="lazy"
                                decoding="async"
                            >
                        </a>
                        <div class="card-body p-2">
                            <div class="small text-muted mb-2">
                                <?php if (!empty($photo->cekim_tarihi)): ?>
                                    <span class="d-block"><strong>Çekim:</strong> <?= htmlspecialchars(date('d-m-Y', strtotime((string) $photo->cekim_tarihi)), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                                <span class="d-block"><strong>Yükleme:</strong> <?= htmlspecialchars(!empty($photo->created_at) ? date('d-m-Y', strtotime((string) $photo->created_at)) : '-', ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (!empty($photo->yukleyen_adi)): ?>
                                    <span class="d-block">Yükleyen: <?= htmlspecialchars((string) $photo->yukleyen_adi, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($photo->yara_bolgesi) || !empty($photo->yara_evresi)): ?>
                                <p class="small mb-1 text-muted">
                                    <?php if (!empty($photo->yara_bolgesi)): ?>Bölge: <?= htmlspecialchars((string) $photo->yara_bolgesi, ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                    <?php if (!empty($photo->yara_bolgesi) && !empty($photo->yara_evresi)): ?> | <?php endif; ?>
                                    <?php if (!empty($photo->yara_evresi)): ?>Evre: <?= htmlspecialchars((string) $photo->yara_evresi, ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($photo->aciklama)): ?>
                                <p class="small mb-2 text-dark"><?= htmlspecialchars((string) $photo->aciklama, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <div class="d-grid gap-1 mb-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm set-compare-before" data-url="<?= htmlspecialchars(esh_upload_url('wounds', (string) $photo->dosya_adi), ENT_QUOTES, 'UTF-8') ?>" data-label="<?= htmlspecialchars((string) (!empty($photo->cekim_tarihi) ? date('d-m-Y', strtotime((string) $photo->cekim_tarihi)) : date('d-m-Y', strtotime((string) $photo->created_at))), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fa-solid fa-arrow-left me-1"></i> Önce
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm set-compare-after" data-url="<?= htmlspecialchars(esh_upload_url('wounds', (string) $photo->dosya_adi), ENT_QUOTES, 'UTF-8') ?>" data-label="<?= htmlspecialchars((string) (!empty($photo->cekim_tarihi) ? date('d-m-Y', strtotime((string) $photo->cekim_tarihi)) : date('d-m-Y', strtotime((string) $photo->created_at))), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fa-solid fa-arrow-right me-1"></i> Sonra
                                </button>
                            </div>
                            <form action="<?= htmlspecialchars(esh_url('Patient', 'deleteWoundPhoto'), ENT_QUOTES, 'UTF-8') ?>" method="post" data-esh-confirm="Bu fotoğrafı silmek istediğinize emin misiniz?">
                                <input type="hidden" name="id" value="<?= $hastaId ?>">
                                <input type="hidden" name="photo_id" value="<?= (int) $photo->id ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                    <i class="fa-solid fa-trash me-1"></i> Sil
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($woundPhotos) > $visibleChunkSize): ?>
            <div class="text-center mt-3">
                <button type="button" id="loadMoreWoundPhotosBtn" class="btn btn-outline-danger" data-step="<?= (int) $visibleChunkSize ?>">
                    <i class="fa-solid fa-chevron-down me-1"></i> Daha fazla göster
                </button>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="text-center text-muted py-5 border rounded bg-white mb-0">
            <i class="fa-solid fa-image fa-3x mb-3 opacity-25"></i>
            <p class="mb-0">Henüz yara fotoğrafı yüklenmemiş.</p>
        </div>
    <?php endif; ?>
</div>
