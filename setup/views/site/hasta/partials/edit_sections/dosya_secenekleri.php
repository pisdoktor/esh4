<?php
declare(strict_types=1);
/** @var object $patient */
use App\Helpers\FormHelper;

$eshFormIdPrefix = (string) ($eshFormIdPrefix ?? '');
?>
<div class="row g-3">
    <?= FormHelper::btnCheckYesNo('gecici', $patient->gecici ?? 0, $eshFormIdPrefix . 'gecici', [
        'label' => 'Geçici Takipli',
        'col' => 'col-md-6',
    ]) ?>
    <?= FormHelper::btnCheckYesNo('erapor', $patient->erapor ?? 0, $eshFormIdPrefix . 'erapor', [
        'label' => 'E-Rapor Hastası',
        'col' => 'col-md-6',
    ]) ?>
</div>
