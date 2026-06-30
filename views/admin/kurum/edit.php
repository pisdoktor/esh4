<?php /** @var \App\Models\Kurum $kurum */
use App\Helpers\FormHelper;

$ay = $kurum->ayarlarArray();
$eshKurumReadonlyKod = (int) ($kurum->id ?? 0) === 1;
?>
<div class="esh-page esh-page--form container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary"><i class="fas fa-building me-2"></i>Kurum Düzenle</h5>
                </div>
                <div class="card-body p-4">
                    <form method="post" action="<?= htmlspecialchars(esh_url('Kurum', 'store'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
                        <?= esh_csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) ($kurum->id ?? 0) ?>">
                        <?= FormHelper::fieldInput('ad', 'Kurum adı', $kurum->ad ?? '', [
                            'col' => 'col-md-8',
                            'labelClass' => 'form-label fw-bold',
                            'required' => true,
                        ]) ?>
                        <?= FormHelper::fieldInput('kod', 'Kod', $kurum->kod ?? '', [
                            'col' => 'col-md-4',
                            'labelClass' => 'form-label fw-bold',
                            'required' => true,
                            'extraAttrs' => $eshKurumReadonlyKod ? ['readonly' => 'readonly'] : [],
                        ]) ?>
                        <?= FormHelper::fieldInput('telefon', 'Telefon', $kurum->telefon ?? '', [
                            'col' => 'col-md-4',
                            'labelClass' => 'form-label',
                        ]) ?>
                        <?= FormHelper::fieldInput('adres', 'Adres', $kurum->adres ?? '', [
                            'col' => 'col-md-8',
                            'labelClass' => 'form-label',
                        ]) ?>
                        <?= FormHelper::fieldCheckbox('aktif', 'Aktif', !empty($kurum->aktif), [
                            'col' => 'col-12',
                            'id' => 'kurumAktif',
                        ]) ?>
                        <div class="col-12"><hr><h6 class="text-muted">Kurumsal görünüm</h6></div>
                        <?= FormHelper::fieldInput('esh_app_name', 'Uygulama adı', $ay['esh_app_name'] ?? '', [
                            'col' => 'col-12',
                            'labelClass' => 'form-label',
                        ]) ?>
                        <?= FormHelper::fieldTextarea('ek3_form_baslik', 'EK-3 form başlığı', (string) ($ay['ek3_form_baslik'] ?? ''), [
                            'col' => 'col-12',
                            'labelClass' => 'form-label',
                            'rows' => 3,
                        ]) ?>
                        <?= FormHelper::fieldTextarea('hekim_degerlendirme_form_baslik', 'Hekim değerlendirme form başlığı', (string) ($ay['hekim_degerlendirme_form_baslik'] ?? ''), [
                            'col' => 'col-12',
                            'labelClass' => 'form-label',
                            'rows' => 3,
                        ]) ?>
                        <div class="col-12 d-flex justify-content-between border-top pt-3">
                            <a href="<?= htmlspecialchars(esh_url('Kurum', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-light border">Vazgeç</a>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
