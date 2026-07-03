<?php
declare(strict_types=1);
/** @var object|null $patient */
use App\Helpers\FormHelper;

$eshBekleyenMode = (string) ($eshBekleyenMode ?? 'bedit');
$patient = $patient ?? (object) [];
$eshIsBedit = $eshBekleyenMode === 'bedit';

$tckValue = $eshIsBedit ? (string) ($patient->tckimlik ?? '') : '';
$isimValue = $eshIsBedit ? (string) ($patient->isim ?? '') : '';
$soyisimValue = $eshIsBedit ? (string) ($patient->soyisim ?? '') : '';
$cinsiyetE = $eshIsBedit && ((string) $patient->cinsiyet === '1' || (int) ($patient->cinsiyet ?? 0) === 1 || (string) $patient->cinsiyet === 'E');
$cinsiyetK = $eshIsBedit && ((string) $patient->cinsiyet === '2' || (int) ($patient->cinsiyet ?? 0) === 2 || (string) $patient->cinsiyet === 'K');
$dogumValue = $eshIsBedit ? ($patient->dogumtarihi ?? '') : '';
$dogumExtra = $eshIsBedit ? ['readonly' => 'readonly', 'style' => 'background-color: #fff;'] : ['style' => 'background-color: #fff;'];
?>
<div class="mb-3">
    <?= FormHelper::fieldInputGroup('tckimlik', 'TC Kimlik Numarası:', $tckValue, [
        'col' => '',
        'id' => 'tckimlik',
        'labelClass' => 'form-label fw-bold',
        'labelFor' => 'tckimlik',
        'prefixIcon' => 'fa-solid fa-fingerprint text-primary',
        'prefixIconClass' => 'bg-white',
        'inputGroupExtraClass' => 'has-validation',
        'minlength' => '11',
        'maxlength' => '11',
        'pattern' => '\d{11}',
        'required' => true,
        'placeholder' => '11 Haneli TC Kimlik No',
        'inputmode' => 'numeric',
        'autocomplete' => 'off',
        'spellcheck' => false,
        'ariaDescribedby' => 'sonuc',
        'invalidFeedback' => 'Geçerli bir TC Kimlik numarası giriniz.',
        'afterInput' => '<div id="sonuc" class="form-text mt-1"></div>',
    ]) ?>
</div>

<div class="row g-3">
    <?= FormHelper::fieldInput('isim', 'Adı:', $isimValue, [
        'col' => 'col-md-6 mb-3',
        'labelClass' => 'form-label fw-bold',
        'required' => true,
        'placeholder' => 'Örn: AHMET',
        'extraAttrs' => ['style' => 'text-transform: uppercase;'],
    ]) ?>
    <?= FormHelper::fieldInput('soyisim', 'Soyadı:', $soyisimValue, [
        'col' => 'col-md-6 mb-3',
        'labelClass' => 'form-label fw-bold',
        'required' => true,
        'placeholder' => 'Örn: YILMAZ',
        'extraAttrs' => ['style' => 'text-transform: uppercase;'],
    ]) ?>
</div>

<div class="row g-3 align-items-end">
    <div class="col-md-6 mb-3 mb-md-0">
        <?= FormHelper::btnCheckRadioGroup('cinsiyet', [
            [
                'value' => 'E',
                'id' => 'gender-m',
                'labelHtml' => '<i class="fa-solid fa-mars me-1"></i> Erkek',
                'btnClass' => 'btn btn-outline-primary shadow-sm py-2',
                'checked' => $cinsiyetE,
            ],
            [
                'value' => 'K',
                'id' => 'gender-f',
                'labelHtml' => '<i class="fa-solid fa-venus me-1"></i> Kadın',
                'btnClass' => 'btn btn-outline-danger shadow-sm py-2',
                'checked' => $cinsiyetK,
            ],
        ], [
            'label' => 'Cinsiyeti:',
            'labelClass' => 'form-label fw-bold d-block',
            'required' => true,
        ]) ?>
    </div>
    <?= FormHelper::fieldDateGroup('dogumtarihi', 'Doğum Tarihi:', $dogumValue, [
        'col' => 'col-md-6 mb-0',
        'id' => 'dogumtarihi',
        'labelClass' => 'form-label fw-bold',
        'labelFor' => 'dogumtarihi',
        'prefixIconClass' => 'bg-white',
        'required' => true,
        'extraAttrs' => $dogumExtra,
    ]) ?>
</div>
<?php if ($eshIsBedit): ?>
<div class="row g-3 mt-1">
    <?php $eshKurumFieldContext = 'bedit'; include ROOT_PATH . '/views/partials/admin/patient_kurum_field.php'; ?>
</div>
<?php else: ?>
    <?php include ROOT_PATH . '/views/partials/admin/patient_kurum_ilkkayit.php'; ?>
<?php endif; ?>
