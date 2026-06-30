<div class="esh-page esh-page--list esh-page-user container-fluid py-4">
<div class="container py-4">
    <?php if (isset($_SESSION['temp_image'])): ?>
        <?php $__eshCropUrl = (string) ($_SESSION['temp_image'] ?? ''); ?>
        <link rel="stylesheet" href="<?= htmlspecialchars(\App\Helpers\CdnAssetHelper::cropperCssHref(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="esh-cropper-page text-center">
            <h4 class="mb-3">Resmi Kırp</h4>
            <div class="esh-cropper-page__stage mb-3">
                <div id="esh-cropper-host">
                    <img src="<?= htmlspecialchars($__eshCropUrl, ENT_QUOTES, 'UTF-8') ?>" id="cropbox" alt="Kırpılacak profil önizlemesi">
                </div>
            </div>
            <form action="<?= htmlspecialchars(esh_url('User', 'cropsave'), ENT_QUOTES, 'UTF-8') ?>" method="post" class="d-flex flex-wrap gap-2 justify-content-center">
                <input type="hidden" id="x" name="x" value="0" />
                <input type="hidden" id="y" name="y" value="0" />
                <input type="hidden" id="w" name="w" value="0" />
                <input type="hidden" id="h" name="h" value="0" />
                <button type="submit" class="btn btn-success">Seçimi Kaydet</button>
                <a href="<?= htmlspecialchars(esh_url('User', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary">İptal</a>
            </form>
        </div>
        <script src="<?= htmlspecialchars(\App\Helpers\CdnAssetHelper::cropperJsHref(), ENT_QUOTES, 'UTF-8') ?>"></script>

    <?php else: ?>
        <div class="card p-5 shadow-sm mx-auto text-center esh-cropper-page__upload-card">
            <i class="fa-solid fa-image fa-3x text-muted mb-3"></i>
            <h5>Yeni Profil Resmi Yükle</h5>
            <form action="<?= htmlspecialchars(esh_url('User', 'upload'), ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data">
                <input type="file" name="image" class="form-control mb-3" required accept="image/jpeg,image/png,.jpg,.jpeg,.png">
                <button type="submit" class="btn btn-primary w-100">Resmi Yükle ve Devam Et</button>
            </form>
        </div>
    <?php endif; ?>
</div>
</div>