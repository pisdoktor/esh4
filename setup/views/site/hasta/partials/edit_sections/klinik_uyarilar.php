<?php

declare(strict_types=1);

/** @var object $patient */

use App\Helpers\FormHelper;



$eshFormIdPrefix = (string) ($eshFormIdPrefix ?? '');

?>

<div class="row g-3">

    <?= FormHelper::fieldTextarea('alerji', 'Alerji özeti', $patient->alerji ?? '', [

        'col' => 'col-12',

        'rows' => 3,

        'placeholder' => 'İlaç, gıda veya diğer alerjiler…',

        'class' => 'form-control-sm',

    ]) ?>

    <?= FormHelper::fieldTextarea('acil_not', 'Acil klinik not', $patient->acil_not ?? '', [

        'col' => 'col-12',

        'rows' => 3,

        'placeholder' => '112 / acil müdahale için kısa not (vent kesilmemeli, antikoagülan vb.)',

        'class' => 'form-control-sm',

    ]) ?>

    <?= FormHelper::switchWithHidden('izolasyon', 'İzolasyon / enfeksiyon önlemi', !empty($patient->izolasyon), $eshFormIdPrefix, ['col' => 'col-12 mb-2']) ?>

</div>

