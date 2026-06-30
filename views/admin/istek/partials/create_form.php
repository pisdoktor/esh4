<div class="esh-page esh-page--form esh-page-istek container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-success"><i class="fa-solid fa-plus-circle me-2"></i>Yeni başvuru amacı</h5>
                    <p class="small text-muted mb-0 mt-2">EK-3 konsültasyon ekranında çoklu seçim olarak gösterilir.</p>
                </div>
                <div class="card-body p-4">
                    <form action="<?= htmlspecialchars(esh_url('Istek', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                        <div class="mb-4">
                            <?= \App\Helpers\FormHelper::fieldInput('istek_adi', 'Başvuru amacı', '', [
                                'id' => 'istek_adi',
                                'labelClass' => 'form-label fw-bold text-secondary',
                                'class' => 'form-control-lg',
                                'placeholder' => 'Örn: MAMA RAPORU YENİLEME',
                                'required' => true,
                                'maxlength' => '255',
                                'extraAttrs' => ['autofocus' => 'autofocus'],
                            ]) ?>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-4">
                            <a href="<?= htmlspecialchars(esh_url('Istek', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary px-4">Geri</a>
                            <button type="submit" class="btn btn-success px-5">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>