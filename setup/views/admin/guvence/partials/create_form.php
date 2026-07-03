<div class="esh-page esh-page--form esh-page-guvence container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-success"><i class="fas fa-plus-circle me-2"></i>Yeni Güvence Türü Tanımla</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= htmlspecialchars(esh_url('Guvence', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                        <div class="mb-4">
                            <?= \App\Helpers\FormHelper::fieldInput('guvenceadi', 'Güvence Adı', '', [
                                'id' => 'guvenceadi',
                                'labelClass' => 'form-label fw-bold text-secondary',
                                'class' => 'form-control-lg',
                                'placeholder' => 'Örn: SGK (4A), Bağ-Kur, Özel Sigorta',
                                'required' => true,
                                'extraAttrs' => ['autofocus' => 'autofocus'],
                                'afterInput' => '<div class="form-text small">Hastaların kayıtlarında seçilecek güvence adını giriniz.</div>',
                            ]) ?>
                        </div>

                        <div class="d-flex justify-content-between border-top pt-4">
                            <a href="<?= htmlspecialchars(esh_url('Guvence', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary px-4">Listeye Dön</a>
                            <button type="submit" class="btn btn-success px-5">
                                <i class="fas fa-save me-2"></i>Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>