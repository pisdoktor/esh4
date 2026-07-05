<div class="container py-4 au-page-user-photo">
    <?php if (isset($_SESSION['temp_image'])): ?>
        <?php $__eshCropUrl = (string) ($_SESSION['temp_image'] ?? ''); ?>
        <link rel="stylesheet" href="<?= htmlspecialchars(\App\Helpers\CdnAssetHelper::cropperCssHref(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="au-panel mx-auto" style="max-width: 640px;">
            <div class="au-panel__head">
                <span class="au-icon-chip au-icon-chip--grad"><i class="fa-solid fa-crop-simple"></i></span>
                <h2 class="au-panel__title mb-0">Resmi Kırp</h2>
            </div>
            <div class="au-panel__body esh-cropper-page text-center">
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
                    <button type="submit" class="btn btn-success rounded-pill px-4">Seçimi Kaydet</button>
                    <a href="<?= htmlspecialchars(esh_url('User', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary rounded-pill px-4">İptal</a>
                </form>
            </div>
        </div>
        <?= esh_csp_script_src_tag(\App\Helpers\CdnAssetHelper::cropperJsHref()) ?>

    <?php else: ?>
        <div class="au-panel mx-auto text-center esh-cropper-page__upload-card" style="max-width: 480px;">
            <div class="au-panel__body p-5">
                <span class="au-icon-chip au-icon-chip--soft mx-auto mb-3"><i class="fa-solid fa-image fa-lg"></i></span>
                <h5 class="fw-bold mb-3">Yeni Profil Resmi Yükle</h5>
                <form action="<?= htmlspecialchars(esh_url('User', 'upload'), ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data">
                    <input type="file" name="image" class="form-control mb-3" required accept="image/jpeg,image/png,.jpg,.jpeg,.png">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">Resmi Yükle ve Devam Et</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
