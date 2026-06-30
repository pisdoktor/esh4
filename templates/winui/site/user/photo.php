<div class="fluent-page fluent-user fluent-user-photo container py-4 text-center">
    <?php if (isset($_SESSION['temp_image'])): ?>
        <?php $__eshCropUrl = (string) ($_SESSION['temp_image'] ?? ''); ?>
        <h4>Resmi Kırp</h4>
        <link rel="stylesheet" href="<?= htmlspecialchars(\App\Helpers\CdnAssetHelper::cropperCssHref(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="mb-4" style="max-width: 100%;">
            <img src="<?= htmlspecialchars($__eshCropUrl, ENT_QUOTES, 'UTF-8') ?>" id="cropbox" alt="Kırpılacak profil önizlemesi" style="max-width: 100%; display: block;">
        </div>

        <form action="<?= htmlspecialchars(esh_url('User', 'cropsave'), ENT_QUOTES, 'UTF-8') ?>" method="post">
            <input type="hidden" id="x" name="x" />
            <input type="hidden" id="y" name="y" />
            <input type="hidden" id="w" name="w" />
            <input type="hidden" id="h" name="h" />
            <button type="submit" class="btn btn-success">Seçimi Kaydet</button>
            <a href="<?= htmlspecialchars(esh_url('User', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary">İptal</a>
        </form>
        <script src="<?= htmlspecialchars(\App\Helpers\CdnAssetHelper::cropperJsHref(), ENT_QUOTES, 'UTF-8') ?>"></script>

    <?php else: ?>
        <div class="card fluent-layer-card fluent-hover-tilt p-5 shadow-sm mx-auto" style="max-width: 500px;">
            <i class="fa-solid fa-image fa-3x text-muted mb-3"></i>
            <h5>Yeni Profil Resmi Yükle</h5>
            <form action="<?= htmlspecialchars(esh_url('User', 'upload'), ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data">
                <input type="file" name="image" class="form-control mb-3" required accept="image/jpeg,image/png,.jpg,.jpeg,.png">
                <button type="submit" class="btn btn-primary w-100">Resmi Yükle ve Devam Et</button>
            </form>
        </div>
    <?php endif; ?>
</div>
