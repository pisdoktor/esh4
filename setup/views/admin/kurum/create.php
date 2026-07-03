<?php
use App\Helpers\FormHelper;
?>
<div class="esh-page esh-page--form container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-success"><i class="fas fa-building me-2"></i>Yeni Kurum</h5>
                </div>
                <div class="card-body p-4">
                    <form method="post" action="<?= htmlspecialchars(esh_url('Kurum', 'store'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
                        <?= esh_csrf_field() ?>
                        <?= FormHelper::fieldInput('ad', 'Kurum adı', '', [
                            'col' => 'col-md-8',
                            'labelClass' => 'form-label fw-bold',
                            'labelHtml' => 'Kurum adı <span class="text-danger">*</span>',
                            'required' => true,
                            'maxlength' => '255',
                        ]) ?>
                        <?= FormHelper::fieldInput('kod', 'Kod (slug)', '', [
                            'col' => 'col-md-4',
                            'labelClass' => 'form-label fw-bold',
                            'labelHtml' => 'Kod (slug) <span class="text-danger">*</span>',
                            'required' => true,
                            'maxlength' => '64',
                            'placeholder' => 'ornek-kurum',
                        ]) ?>
                        <?= FormHelper::fieldInput('telefon', 'Telefon', '', [
                            'col' => 'col-md-4',
                            'labelClass' => 'form-label',
                            'maxlength' => '64',
                        ]) ?>
                        <?= FormHelper::fieldInput('adres', 'Adres', '', [
                            'col' => 'col-md-8',
                            'labelClass' => 'form-label',
                        ]) ?>
                        <?= FormHelper::fieldCheckbox('aktif', 'Aktif', true, [
                            'col' => 'col-12',
                            'id' => 'kurumAktif',
                        ]) ?>
                        <?php include __DIR__ . '/partials/federation_fields.php'; ?>
                        <div class="col-12"><hr><h6 class="text-muted">Kurumsal görünüm</h6></div>
                        <?= FormHelper::fieldInput('esh_app_name', 'Uygulama adı', '', [
                            'col' => 'col-12',
                            'labelClass' => 'form-label',
                        ]) ?>
                        <?= FormHelper::fieldTextarea('ek3_form_baslik', 'EK-3 form başlığı', '', [
                            'col' => 'col-12',
                            'labelClass' => 'form-label',
                            'rows' => 3,
                        ]) ?>
                        <?= FormHelper::fieldTextarea('hekim_degerlendirme_form_baslik', 'Hekim değerlendirme form başlığı', '', [
                            'col' => 'col-12',
                            'labelClass' => 'form-label',
                            'rows' => 3,
                        ]) ?>
                        <div class="col-12 d-flex justify-content-between border-top pt-3">
                            <a href="<?= htmlspecialchars(esh_url('Kurum', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border">Vazgeç</a>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
