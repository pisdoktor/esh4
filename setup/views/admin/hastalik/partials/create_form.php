<?php use App\Helpers\FormHelper; ?>
<div class="esh-page esh-page--form esh-page-hastalik container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-success"><i class="fas fa-plus-circle me-2"></i>Yeni Tanı/Hastalık Ekle</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= htmlspecialchars(esh_url('Hastalik', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                        <div class="row">
                            <?= FormHelper::fieldInput('icd', 'ICD-10 Kodu', '', [
                                'col' => 'col-md-4 mb-3',
                                'labelClass' => 'form-label fw-bold text-secondary',
                                'class' => 'form-control-lg',
                                'placeholder' => 'Örn: I10',
                                'required' => true,
                            ]) ?>
                            <?= FormHelper::fieldInput('hastalikadi', 'Hastalık / Tanı Adı', '', [
                                'col' => 'col-md-8 mb-3',
                                'labelClass' => 'form-label fw-bold text-secondary',
                                'class' => 'form-control-lg',
                                'placeholder' => 'Hastalığın tam adını yazınız',
                                'required' => true,
                            ]) ?>
                        </div>

                        <?= FormHelper::fieldSelect('cat', 'Hastalık Kategorisi', $hastalikCatOptions, '', [
                            'col' => 'mb-4',
                            'labelClass' => 'form-label fw-bold text-secondary',
                            'placeholder' => 'Kategori Seçiniz...',
                            'tomSelect' => false,
                            'required' => true,
                            'helpText' => 'Tanı koduna uygun kategoriyi seçtiğinizden emin olun.',
                        ]) ?>

                        <div class="d-flex justify-content-between border-top pt-4">
                            <a href="<?= htmlspecialchars(esh_url('Hastalik', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary px-4">Vazgeç</a>
                            <button type="submit" class="btn btn-success px-5">
                                <i class="fas fa-save me-2"></i>Kütüphaneye Ekle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>