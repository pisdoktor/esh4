<?php
declare(strict_types=1);
/** @var object $patient */
use App\Helpers\FormHelper;
use App\Helpers\ValidationHelper;

$eshFormIdPrefix = (string) ($eshFormIdPrefix ?? '');
$eshBoyDisplay = '';
$eshBoyStored = (float) ($patient->boy ?? 0);
if ($eshBoyStored > 0) {
    $eshBoyDisplay = ($eshBoyStored > 0 && $eshBoyStored <= 3)
        ? ValidationHelper::normalizeDecimalDotInput((string) round($eshBoyStored * 100))
        : ValidationHelper::normalizeDecimalDotInput($patient->boy);
}
$eshKiloDisplay = ValidationHelper::normalizeDecimalDotInput($patient->kilo ?? '');
$vkiInit = null;
$vkiBg = 'secondary';
$vkiLabel = '—';
$boyStored = (float) ($patient->boy ?? 0);
$boyCm = ($boyStored > 0 && $boyStored <= 3) ? ($boyStored * 100) : $boyStored;
if ($boyCm > 0 && (float) ($patient->kilo ?? 0) > 0) {
    $boyM = $boyCm / 100;
    $vkiInit = round((float) $patient->kilo / ($boyM * $boyM), 1);
    if ($vkiInit < 18.5) {
        $vkiBg = 'warning';
        $vkiLabel = 'Zayıf';
    } elseif ($vkiInit <= 24.9) {
        $vkiBg = 'success';
        $vkiLabel = 'Normal';
    } elseif ($vkiInit <= 29.9) {
        $vkiBg = 'warning';
        $vkiLabel = 'Obez';
    } else {
        $vkiBg = 'danger';
        $vkiLabel = 'Morbid obez';
    }
}
?>
<div class="row g-3">
    <?= FormHelper::fieldInputGroup('boy', 'Boy (cm)', $eshBoyDisplay, [
        'col' => 'col-md-6',
        'class' => 'esh-decimal-dot',
        'inputGroupSm' => true,
        'prefixIcon' => 'fa-solid fa-ruler-vertical',
        'suffixText' => 'cm',
        'inputmode' => 'decimal',
        'autocomplete' => 'off',
        'placeholder' => 'Örn: 175',
        'required' => true,
        'invalidFeedback' => 'Boy zorunludur.',
    ]) ?>
    <?= FormHelper::fieldInputGroup('kilo', 'Kilo (kg)', $eshKiloDisplay, [
        'col' => 'col-md-6',
        'class' => 'esh-decimal-dot',
        'inputGroupSm' => true,
        'prefixIcon' => 'fa-solid fa-weight-hanging',
        'suffixText' => 'kg',
        'inputmode' => 'decimal',
        'autocomplete' => 'off',
        'placeholder' => 'Örn: 70.5',
        'required' => true,
        'invalidFeedback' => 'Kilo zorunludur.',
    ]) ?>
    <?php if (!empty($eshInPartialModal)): ?>
    <?php
    $eshBagimlilikOptions = [
        FormHelper::makeOption('', 'Seçiniz...'),
        FormHelper::makeOption('1', 'Bağımsız'),
        FormHelper::makeOption('2', 'Yarı Bağımlı'),
        FormHelper::makeOption('3', 'Tam Bağımlı'),
    ];
    echo FormHelper::fieldSelect('bagimlilik', 'Bağımlılık Durumu', $eshBagimlilikOptions, (string) ($patient->bagimlilik ?? ''), [
        'col' => 'col-12',
        'id' => $eshFormIdPrefix . 'bagimlilik',
        'labelFor' => $eshFormIdPrefix . 'bagimlilik',
        'tomSelect' => false,
        'helpText' => 'Barthel indeksine göre bağımlılık düzeyi.',
    ]);
    ?>
    <?php endif; ?>
    <div class="col-12 mt-2 <?= $vkiInit === null ? 'd-none' : '' ?>" id="<?= htmlspecialchars($eshFormIdPrefix, ENT_QUOTES, 'UTF-8') ?>vki-container">
        <div class="p-2 rounded bg-light d-flex align-items-center justify-content-between">
            <span class="small text-muted"><i class="fa-solid fa-calculator me-1"></i> Vücut Kitle İndeksi (VKİ):</span>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-<?= htmlspecialchars($vkiBg, ENT_QUOTES, 'UTF-8') ?> <?= $vkiBg === 'warning' ? 'text-dark' : '' ?>" id="<?= htmlspecialchars($eshFormIdPrefix, ENT_QUOTES, 'UTF-8') ?>vki-badge">
                    <?= $vkiInit !== null ? htmlspecialchars((string) $vkiInit, ENT_QUOTES, 'UTF-8') : '—' ?>
                </span>
                <span class="badge bg-<?= htmlspecialchars($vkiBg, ENT_QUOTES, 'UTF-8') ?> <?= $vkiBg === 'warning' ? 'text-dark' : '' ?>" id="<?= htmlspecialchars($eshFormIdPrefix, ENT_QUOTES, 'UTF-8') ?>vki-label">
                    <?= htmlspecialchars((string) $vkiLabel, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </div>
    </div>
</div>
