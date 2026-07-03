<?php
declare(strict_types=1);
/** @var object|null $patient */
use App\Helpers\FormHelper;

$eshBekleyenMode = (string) ($eshBekleyenMode ?? 'bedit');
$patient = $patient ?? (object) [];
$eshIsBedit = $eshBekleyenMode === 'bedit';

$ceptel1 = $eshIsBedit ? (string) ($patient->ceptel1 ?? '') : '';
$ceptel2 = $eshIsBedit ? (string) ($patient->ceptel2 ?? '') : '';
?>
<div class="row g-3">
    <?= FormHelper::fieldInputGroup('ceptel1', 'Cep Telefonu 1:', $ceptel1, [
        'col' => 'col-md-6',
        'id' => 'telefon1',
        'labelClass' => 'form-label fw-bold',
        'prefixIcon' => 'fa-solid fa-mobile-screen',
        'class' => 'tel-mask',
        'placeholder' => '0 (5xx) xxx xx xx',
        'required' => true,
    ]) ?>
    <?= FormHelper::fieldInputGroup('ceptel2', 'Cep Telefonu 2:', $ceptel2, [
        'col' => 'col-md-6',
        'id' => 'telefon2',
        'labelClass' => 'form-label fw-bold',
        'prefixIcon' => 'fa-solid fa-phone',
        'class' => 'tel-mask',
        'placeholder' => '0 (5xx) xxx xx xx',
    ]) ?>
</div>
