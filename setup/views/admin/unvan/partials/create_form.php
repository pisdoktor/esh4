<?php
/**
 * @var array<string, string> $kategoriChoices
 * @var array<string, string> $izinSablonuChoices
 */
use App\Helpers\FormHelper;

$kategoriOptions = [FormHelper::makeOption('', 'Seçiniz…')];
foreach ($kategoriChoices as $val => $label) {
    $kategoriOptions[] = FormHelper::makeOption((string) $val, $label);
}
$izinOptions = [];
foreach ($izinSablonuChoices as $val => $label) {
    $izinOptions[] = FormHelper::makeOption((string) $val, $label);
}
?>
<div class="esh-page esh-page--form esh-page-unvan container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-success"><i class="fas fa-plus-circle me-2"></i>Yeni Personel Ünvanı</h5>
                </div>
                <div class="card-body p-4">
                    <form action="<?= htmlspecialchars(esh_url('Unvan', 'store'), ENT_QUOTES, 'UTF-8') ?>" method="POST">
                        <?= \App\Helpers\CsrfHelper::hiddenField() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <?= FormHelper::fieldInput('kod', 'Kod', '', [
                                    'required' => true,
                                    'placeholder' => 'ornek_unvan',
                                    'afterInput' => '<div class="form-text">Küçük harf, rakam ve alt çizgi. Rol slug ile eşleşir.</div>',
                                ]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= FormHelper::fieldInput('ad', 'Görünen ad', '', ['required' => true]) ?>
                            </div>
                            <div class="col-md-6">
                                <?= FormHelper::fieldSelect('kategori', 'Kategori', $kategoriOptions, 'diger') ?>
                            </div>
                            <div class="col-md-6">
                                <?= FormHelper::fieldSelect('izin_sablonu', 'İzin şablonu', $izinOptions, 'personel', [
                                    'afterInput' => '<div class="form-text">Yeni rol oluşturulurken klonlanacak izin seti.</div>',
                                ]) ?>
                            </div>
                            <div class="col-md-4">
                                <?= FormHelper::fieldInput('sort_order', 'Sıra', '100', ['type' => 'number']) ?>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <?= FormHelper::fieldSwitch('aktif', 'Aktif', true) ?>
                            </div>
                            <div class="col-12">
                                <?= FormHelper::textarea('mevzuat_notu', 'Mevzuat / not', '', ['rows' => 2]) ?>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-4 mt-3">
                            <a href="<?= htmlspecialchars(esh_url('Unvan', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary px-4">Listeye Dön</a>
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
