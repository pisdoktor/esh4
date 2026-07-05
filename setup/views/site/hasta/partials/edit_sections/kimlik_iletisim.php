<?php
declare(strict_types=1);
/** @var object $patient */
/** @var list<object> $guvenceList */
use App\Helpers\FormHelper;
use App\Helpers\CinsiyetHelper;
use App\Helpers\PatientCareHelper;

$eshFormIdPrefix = (string) ($eshFormIdPrefix ?? '');
$eshEditIsAdmin = true;
$guvenceList = $guvenceList ?? $guvence ?? [];

$guvenceOptions = [FormHelper::makeOption('', 'Seçiniz...')];
foreach ($guvenceList as $g) {
    $guvenceOptions[] = FormHelper::makeOption((string) $g->id, (string) ($g->guvenceadi ?? ''));
}

$eshKimlikTcOpts = [
    'col' => 'col-md-6',
    'id' => $eshFormIdPrefix . 'tckimlik',
    'labelFor' => '',
    'minlength' => '11',
    'maxlength' => '11',
    'pattern' => '\d{11}',
    'inputmode' => 'numeric',
    'autocomplete' => 'off',
    'spellcheck' => false,
    'required' => true,
    'title' => '11 haneli TC kimlik numarası giriniz.',
    'invalidFeedback' => 'Tam 11 haneli TC giriniz.',
    'invalidFeedbackClass' => 'invalid-feedback small',
];
if (empty($eshInPartialModal)) {
    $eshKimlikTcOpts['ariaDescribedby'] = 'tc-help sonuc';
    $eshKimlikTcOpts['afterInput'] = '<div id="tc-help" class="form-text small text-muted">Sadece rakam; tam 11 hane. Kontrol hanesi hatalı olsa da kayıt yapılır, uyarı gösterilir.</div><div id="sonuc" class="small mt-1"></div>';
}
?>
<div class="row g-3">
    <?= FormHelper::fieldInput('tckimlik', 'TC Kimlik No', $patient->tckimlik ?? '', $eshKimlikTcOpts) ?>
    <?= FormHelper::fieldInput('isim', 'Ad', $patient->isim ?? '', [
        'col' => 'col-md-3',
        'autocomplete' => 'off',
        'required' => true,
        'invalidFeedback' => 'Ad zorunludur.',
    ]) ?>
    <?= FormHelper::fieldInput('soyisim', 'Soyad', $patient->soyisim ?? '', [
        'col' => 'col-md-3',
        'autocomplete' => 'off',
        'required' => true,
        'invalidFeedback' => 'Soyad zorunludur.',
    ]) ?>
    <?= FormHelper::fieldInput('anneAdi', 'Anne Adı', $patient->anneAdi ?? '', [
        'col' => 'col-md-6',
        'autocomplete' => 'off',
        'required' => true,
        'invalidFeedback' => 'Anne adı zorunludur.',
    ]) ?>
    <?= FormHelper::fieldInput('babaAdi', 'Baba Adı', $patient->babaAdi ?? '', [
        'col' => 'col-md-6',
        'autocomplete' => 'off',
        'required' => true,
        'invalidFeedback' => 'Baba adı zorunludur.',
    ]) ?>
    <?= FormHelper::fieldInput('ailehekimi', 'Aile Hekimi', $patient->ailehekimi ?? '', [
        'col' => 'col-md-6',
        'autocomplete' => 'off',
        'maxlength' => '128',
        'placeholder' => 'Ad Soyad',
    ]) ?>
    <?= FormHelper::fieldPhone('ailehekimitel', 'Aile Hekimi Telefonu', $patient->ailehekimitel ?? '', [
        'col' => 'col-md-6',
        'autocomplete' => 'off',
        'placeholder' => '0 (___) ___ __ __',
    ]) ?>
    <?php
    $kanGrubuOpts = [FormHelper::makeOption('', 'Seçiniz...')];
    foreach (PatientCareHelper::kanGrubuOptions() as $kgVal => $kgLabel) {
        if ($kgVal === '') {
            continue;
        }
        $kanGrubuOpts[] = FormHelper::makeOption((string) $kgVal, (string) $kgLabel);
    }
    $kanGrubuSel = (string) (PatientCareHelper::normalizeKanGrubu($patient->kangrubu ?? '') ?? '');
    echo FormHelper::fieldSelect('kangrubu', 'Kan Grubu', $kanGrubuOpts, $kanGrubuSel, [
        'col' => 'col-md-4',
        'extraAttrs' => ['autocomplete' => 'off'],
    ]);
    ?>
    <?php include ROOT_PATH . '/views/partials/admin/patient_kurum_field.php'; ?>
    <?= FormHelper::btnCheckRadioGroup('cinsiyet', [
        [
            'value' => CinsiyetHelper::ERKEK,
            'id' => $eshFormIdPrefix . 'genderMale',
            'labelHtml' => '<i class="fa-solid fa-mars me-1"></i> Erkek',
            'btnClass' => 'btn btn-outline-primary shadow-sm py-2',
            'checked' => CinsiyetHelper::isErkek($patient->cinsiyet ?? null) || empty($patient->cinsiyet ?? null),
        ],
        [
            'value' => CinsiyetHelper::KADIN,
            'id' => $eshFormIdPrefix . 'genderFemale',
            'labelHtml' => '<i class="fa-solid fa-venus me-1"></i> Kadın',
            'btnClass' => 'btn btn-outline-danger shadow-sm py-2',
            'checked' => CinsiyetHelper::isKadin($patient->cinsiyet ?? null),
        ],
    ], [
        'label' => 'Cinsiyet',
        'col' => 'col-md-4',
        'ariaLabel' => 'Cinsiyet Seçimi',
        'required' => true,
        'autocomplete' => 'off',
    ]) ?>
    <?= FormHelper::fieldDate('dogumtarihi', 'Doğum Tarihi', $patient->dogumtarihi ?? '', [
        'col' => 'col-md-4',
        'required' => true,
        'invalidFeedback' => 'Doğum tarihi zorunludur.',
    ]) ?>
    <?= FormHelper::fieldDate('kayittarihi', 'Sisteme Kayıt Tarihi', $patient->kayittarihi ?? '', [
        'col' => 'col-md-4',
        'fallbackToday' => true,
        'required' => true,
        'invalidFeedback' => 'Kayıt tarihi zorunludur.',
    ]) ?>
    <div class="col-md-6">
        <?php
        echo FormHelper::fieldSelect('guvence', 'Güvence', $guvenceOptions, (string) ($patient->guvence ?? ''), [
            'col' => '',
            'id' => $eshFormIdPrefix . 'hasta-guvence',
            'labelClass' => 'form-label small fw-bold text-muted',
            'required' => true,
            'extraAttrs' => ['autocomplete' => 'off'],
        ]);
        ?>
        <div id="<?= htmlspecialchars($eshFormIdPrefix, ENT_QUOTES, 'UTF-8') ?>hasta-yupasno-wrap" class="mt-2 d-none">
            <?= FormHelper::fieldInput('yupasno', 'YUPAS No', $patient->yupasno ?? '', [
                'id' => $eshFormIdPrefix . 'hasta-yupasno',
                'maxlength' => '64',
                'autocomplete' => 'off',
                'placeholder' => 'YUPAS numarası',
            ]) ?>
        </div>
    </div>
    <?= FormHelper::fieldDate('randevutarihi', 'Randevu (İlk Ziyaret) Tarihi', $patient->randevutarihi ?? '', [
        'col' => 'col-md-6',
        'id' => $eshFormIdPrefix . 'hasta-randevutarihi',
        'required' => true,
        'invalidFeedback' => 'Randevu (İlk Ziyaret) Tarihi zorunludur.',
    ]) ?>
    <?= FormHelper::fieldPhone('ceptel1', 'Telefon 1 (Cep)', $patient->ceptel1 ?? '', [
        'col' => 'col-md-6',
        'autocomplete' => 'off',
        'required' => true,
        'placeholder' => '05XX XXX XX XX',
        'invalidFeedback' => 'Telefon 1 (cep) zorunludur.',
    ]) ?>
    <?= FormHelper::fieldInput('ceptel2', 'Telefon 2', $patient->ceptel2 ?? '', [
        'col' => 'col-md-6',
        'autocomplete' => 'off',
    ]) ?>
    <div class="col-12"><hr class="my-2 text-muted"></div>
    <div class="col-12">
        <div class="text-muted x-small fw-bold text-uppercase mb-2">Bakım veren / yakını</div>
    </div>
    <?= FormHelper::fieldInput('bakimveren_ad', 'Ad Soyad', $patient->bakimveren_ad ?? '', [
        'col' => 'col-md-4',
        'autocomplete' => 'off',
    ]) ?>
    <?= FormHelper::fieldInput('bakimveren_yakinlik', 'Yakınlık', $patient->bakimveren_yakinlik ?? '', [
        'col' => 'col-md-4',
        'autocomplete' => 'off',
        'placeholder' => 'Eş, çocuk, komşu…',
    ]) ?>
    <?= FormHelper::fieldPhone('bakimveren_tel', 'Telefon', $patient->bakimveren_tel ?? '', [
        'col' => 'col-md-4',
        'autocomplete' => 'off',
        'placeholder' => '05XX XXX XX XX',
    ]) ?>
    <?php if (\App\Helpers\AppSettings::isModuleEnabled('sms_bildirim')): ?>
    <div class="col-12">
        <input type="hidden" name="sms_bilgilendirme_onay" value="0">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="sms_bilgilendirme_onay" id="<?= htmlspecialchars($eshFormIdPrefix, ENT_QUOTES, 'UTF-8') ?>sms-bilgilendirme-onay" value="1"
                <?= !isset($patient->sms_bilgilendirme_onay) || (int) $patient->sms_bilgilendirme_onay !== 0 ? ' checked' : '' ?>>
            <label class="form-check-label small" for="<?= htmlspecialchars($eshFormIdPrefix, ENT_QUOTES, 'UTF-8') ?>sms-bilgilendirme-onay">
                <?= htmlspecialchars(\App\Helpers\SmsSettings::kvkkMetni(), ENT_QUOTES, 'UTF-8') ?>
            </label>
        </div>
    </div>
    <?php endif; ?>
</div>
