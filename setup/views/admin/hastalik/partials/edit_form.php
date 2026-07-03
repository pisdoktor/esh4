<?php use App\Helpers\FormHelper; ?>
<div class="esh-page esh-page--form esh-page-hastalik container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary"><i class="fas fa-edit me-2"></i>Tanı Bilgilerini Güncelle</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= htmlspecialchars(esh_url('Hastalik', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                        
                        <input type="hidden" name="id" value="<?= $item->id ?>">

                        <div class="row">
                            <?= FormHelper::fieldInput('icd', 'ICD-10 Kodu', $item->icd, [
                                'col' => 'col-md-4 mb-3',
                                'labelClass' => 'form-label fw-bold text-secondary',
                                'required' => true,
                            ]) ?>
                            <?= FormHelper::fieldInput('hastalikadi', 'Hastalık / Tanı Adı', $item->hastalikadi, [
                                'col' => 'col-md-8 mb-3',
                                'labelClass' => 'form-label fw-bold text-secondary',
                                'required' => true,
                            ]) ?>
                        </div>

                        <?= FormHelper::fieldSelect('cat', 'Hastalık Kategorisi', $hastalikCatOptions, $item->cat ?? '', [
                            'col' => 'mb-4',
                            'labelClass' => 'form-label fw-bold text-secondary',
                            'placeholder' => 'Kategori Seçiniz...',
                            'tomSelect' => false,
                            'required' => true,
                        ]) ?>

                        <div class="d-flex justify-content-between border-top pt-4">
                            <a href="<?= htmlspecialchars(esh_url('Hastalik', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border px-4">İptal</a>
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-sync-alt me-2"></i>Değişiklikleri Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>