<article class="mr-page mr-page--user-photo container py-4" lang="tr" aria-labelledby="mr-photo-title">
    <header class="text-center mb-4">
        <h1 id="mr-photo-title" class="h4 mb-1 fw-bold text-primary">Profil fotoğrafı</h1>
        <p class="text-muted small mb-0">Kırpma veya yeni yükleme adımlarını izleyin.</p>
    </header>

    <?php if (isset($_SESSION['temp_image'])): ?>
        <?php $__eshCropUrl = (string) ($_SESSION['temp_image'] ?? ''); ?>
        <section class="mr-photo-crop mx-auto" style="max-width: 720px;" aria-labelledby="mr-crop-title">
            <h2 id="mr-crop-title" class="h6 fw-semibold mb-3">Görüntüyü kırp</h2>
            <link rel="stylesheet" href="<?= htmlspecialchars(\App\Helpers\CdnAssetHelper::cropperCssHref(), ENT_QUOTES, 'UTF-8') ?>">
            <figure class="mb-4 text-center bg-body-secondary rounded-3 p-3 border">
                <img src="<?= htmlspecialchars($__eshCropUrl, ENT_QUOTES, 'UTF-8') ?>" id="cropbox" class="img-fluid" alt="Kırpılacak profil önizlemesi" style="max-width: 100%; display: block;">
            </figure>

            <form action="<?= htmlspecialchars(esh_url('User', 'cropsave'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="d-flex flex-wrap justify-content-center gap-2">
                <input type="hidden" id="x" name="x" />
                <input type="hidden" id="y" name="y" />
                <input type="hidden" id="w" name="w" />
                <input type="hidden" id="h" name="h" />
                <button type="submit" class="btn btn-success px-4">Seçimi kaydet</button>
                <a href="<?= htmlspecialchars(esh_url('User', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary px-4">İptal</a>
            </form>
            <script src="<?= htmlspecialchars(\App\Helpers\CdnAssetHelper::cropperJsHref(), ENT_QUOTES, 'UTF-8') ?>"></script>
        </section>

    <?php else: ?>
        <section class="mx-auto" style="max-width: 520px;" aria-labelledby="mr-upload-title">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="card-body p-4 p-md-5 text-center">
                    <h2 id="mr-upload-title" class="h5 fw-bold mb-2">Yeni profil resmi</h2>
                    <p class="text-muted small mb-4">Kare kırpma için dosya yükleyin.</p>
                    <i class="fa-solid fa-image fa-3x text-primary opacity-50 mb-4 d-block" aria-hidden="true"></i>
                    <form action="<?= htmlspecialchars(esh_url('User', 'upload'), ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data" class="text-start">
                        <label for="mr-profile-image" class="form-label small">Dosya seç</label>
                        <input type="file" id="mr-profile-image" name="image" class="form-control mb-3" required accept="image/*">
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Yükle ve devam et</button>
                    </form>
                </div>
            </div>
        </section>
    <?php endif; ?>
</article>
