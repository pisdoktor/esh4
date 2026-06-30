<div class="esh-page esh-page--form esh-page-islem container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary"><i class="fas fa-edit me-2"></i>İşlem Bilgisini Güncelle</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= htmlspecialchars(esh_url('Islem', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                        
                        <input type="hidden" name="id" value="<?= $item->id ?>">

                        <div class="mb-4">
                            <?= \App\Helpers\FormHelper::fieldInput('islemadi', 'İşlem Adı', $item->islemadi, [
                                'id' => 'islemadi',
                                'labelClass' => 'form-label fw-bold text-secondary',
                                'class' => 'form-control-lg',
                                'required' => true,
                            ]) ?>
                        </div>

                        <div class="d-flex justify-content-between border-top pt-4">
                            <a href="<?= htmlspecialchars(esh_url('Islem', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border px-4">İptal</a>
                            <button type="submit" class="btn btn-primary px-5">Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>