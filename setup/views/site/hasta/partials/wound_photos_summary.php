<?php
/**
 * Hasta kartı — yara fotoğrafları özet kartı.
 *
 * @var object $hasta
 * @var int $woundPhotoCount
 * @var array $woundPhotosPreview İlk birkaç kayıt (en yeni)
 * @var bool $pasifDosyaKapali
 */
$hastaId = (int) ($hasta->id ?? 0);
$woundUrl = esh_url('Patient', 'wounds', ['id' => $hastaId]);
$preview = isset($woundPhotosPreview) && is_array($woundPhotosPreview) ? $woundPhotosPreview : [];
$count = (int) ($woundPhotoCount ?? count($preview));
?>
<div class="card esh-wound-summary-card shadow-sm border-0 border-top border-danger border-3 rounded mb-0">
    <div class="card-body p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h6 class="mb-1 fw-bold text-danger">
                    <i class="fa-solid fa-camera me-2"></i>Yara Fotoğrafları
                </h6>
                <p class="small text-muted mb-0">
                    <?php if ($count > 0): ?>
                        <strong class="text-dark"><?= $count ?></strong> kayıtlı fotoğraf
                    <?php else: ?>
                        Henüz fotoğraf yüklenmemiş
                    <?php endif; ?>
                </p>
            </div>
            <a href="<?= htmlspecialchars($woundUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-danger btn-sm fw-semibold shadow-sm">
                <i class="fa-solid fa-images me-1"></i>Tümünü gör
            </a>
        </div>

        <?php if ($count > 0 && $preview !== []): ?>
            <div class="d-flex flex-wrap gap-2 mb-2">
                <?php foreach ($preview as $photo): ?>
                    <?php
                    $thumbUrl = esh_upload_url('wounds', (string) ($photo->dosya_adi ?? ''));
                    $cekim = !empty($photo->cekim_tarihi)
                        ? date('d-m-Y', strtotime((string) $photo->cekim_tarihi))
                        : (!empty($photo->created_at) ? date('d-m-Y', strtotime((string) $photo->created_at)) : '');
                    ?>
                    <a href="<?= htmlspecialchars($woundUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none" title="Galeriye git">
                        <div class="rounded border overflow-hidden bg-light" style="width:72px;height:72px;">
                            <img src="<?= htmlspecialchars($thumbUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" class="w-100 h-100" style="object-fit:cover;" loading="lazy" decoding="async">
                        </div>
                        <?php if ($cekim !== ''): ?>
                            <div class="text-center small text-muted mt-1" style="font-size:0.7rem;"><?= htmlspecialchars($cekim, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
                <?php if ($count > count($preview)): ?>
                    <a href="<?= htmlspecialchars($woundUrl, ENT_QUOTES, 'UTF-8') ?>" class="d-flex align-items-center justify-content-center rounded border bg-danger-subtle text-danger fw-bold text-decoration-none" style="width:72px;height:72px;font-size:0.85rem;">
                        +<?= (int) ($count - count($preview)) ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="small text-muted mb-2">
                <i class="fa-solid fa-image me-1 opacity-50"></i>
                Yara takibi için fotoğraf yükleyebilirsiniz.
            </p>
        <?php endif; ?>

        <?php if (empty($pasifDosyaKapali)): ?>
            <a href="<?= htmlspecialchars($woundUrl, ENT_QUOTES, 'UTF-8') ?>#wound-upload" class="btn btn-outline-danger btn-sm">
                <i class="fa-solid fa-upload me-1"></i>Fotoğraf yükle
            </a>
        <?php else: ?>
            <span class="small text-muted"><i class="fa-solid fa-lock me-1"></i>Pasif dosyada yükleme kapalı; görüntüleme açık.</span>
        <?php endif; ?>
    </div>
</div>
