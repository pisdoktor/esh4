<?php
declare(strict_types=1);
/** @var object|null $patient */
use App\Helpers\FormHelper;

$eshBekleyenMode = (string) ($eshBekleyenMode ?? 'bedit');
$patient = $patient ?? (object) [];
$eshIsBedit = $eshBekleyenMode === 'bedit';

$eraporChecked = $eshIsBedit && !empty($patient->erapor);
$kayitValue = $eshIsBedit
    ? (!empty($patient->kayittarihi) ? $patient->kayittarihi : \App\Helpers\DateHelper::todayTr())
    : \App\Helpers\DateHelper::todayTr();
$randevuValue = $eshIsBedit ? ($patient->randevutarihi ?? '') : '';
$zamanValue = $eshIsBedit ? ($patient->zaman ?? 1) : 1;
$zamanGroupId = $eshIsBedit ? 'hasta-bed-' . (string) ($patient->id ?? '') : 'hasta-ilkkayit';

$kayitOpts = [
    'col' => 'col-md-6',
    'labelClass' => 'form-label fw-bold',
    'icon' => 'fa-solid fa-calendar-check text-success',
    'required' => true,
    'extraAttrs' => ['style' => 'background-color: #fff;'],
];
if ($eshIsBedit) {
    $kayitOpts['extraAttrs']['readonly'] = 'readonly';
} else {
    $kayitOpts['rawValue'] = \App\Helpers\DateHelper::todayTr();
}

$randevuOpts = [
    'col' => 'col-md-6',
    'labelClass' => 'form-label fw-bold',
    'icon' => 'fa-solid fa-calendar-plus text-info',
    'extraAttrs' => ['style' => 'background-color: #fff;'],
];
?>
<div class="mb-4">
    <label class="form-label fw-bold">E-Rapor Durumu:</label>
    <div class="p-3 border rounded bg-white shadow-sm">
        <?= FormHelper::switchWithHidden('erapor', 'E-Rapor Hastası', $eraporChecked, '', [
            'id' => 'erapor',
            'col' => '',
            'switchClass' => 'form-check form-switch mb-3 custom-switch',
        ]) ?>
    </div>
</div>

<div class="row g-3">
    <?= FormHelper::fieldDateGroup('kayittarihi', 'Sisteme Kayıt Tarihi:', $kayitValue, $kayitOpts) ?>
    <?= FormHelper::fieldDateGroup('randevutarihi', 'İlk Randevu Tarihi:', $randevuValue, $randevuOpts) ?>
    <div class="col-12">
        <label class="form-label fw-bold">Randevu Zaman Dilimi:</label>
        <?= \App\Helpers\UIHelper::zamanDilimiRadios('zaman', $zamanGroupId, $zamanValue) ?>
        <?php if (!$eshIsBedit): ?>
            <div id="randevu-doluluk" class="small fw-semibold mt-2 min-h-20"></div>
        <?php endif; ?>
    </div>
</div>
