<div class="esh-page esh-page--form esh-page-istek container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary"><i class="fas fa-edit me-2"></i>Başvuru amacını düzenle</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= htmlspecialchars(esh_url('Istek', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="post">
                        <input type="hidden" name="id" value="<?= (int) ($item->id ?? 0) ?>">

                        <div class="mb-4">
                            <?= \App\Helpers\FormHelper::fieldInput('istek_adi', 'Başvuru amacı', $item->istek_adi ?? '', [
                                'id' => 'istek_adi',
                                'labelClass' => 'form-label fw-bold',
                                'class' => 'form-control-lg',
                                'required' => true,
                                'maxlength' => '255',
                            ]) ?>
                        </div>

                        <div class="d-flex justify-content-between border-top pt-4">
                            <a href="<?= htmlspecialchars(esh_url('Istek', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary px-4">İptal</a>
                            <button type="submit" class="btn btn-primary px-5">Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>