<?php

declare(strict_types=1);

/** @var object $patient */

use App\Helpers\FormHelper;

use App\Helpers\PatientClinicalFlagsHelper;



$eshFormIdPrefix = (string) ($eshFormIdPrefix ?? '');

$deviceGroups = PatientClinicalFlagsHelper::deviceEditGroups();

?>

<div class="row">

    <?php foreach ($deviceGroups as $group): ?>

        <div class="col-12">

            <div class="text-muted x-small fw-bold text-uppercase mb-2"><?= htmlspecialchars((string) $group['title'], ENT_QUOTES, 'UTF-8') ?></div>

        </div>

        <?php foreach ($group['keys'] as $key): ?>

            <?= FormHelper::switchWithHidden($key, PatientClinicalFlagsHelper::label($key), !empty($patient->$key), $eshFormIdPrefix, ['col' => 'col-md-4 mb-3']) ?>

        <?php endforeach; ?>

    <?php endforeach; ?>

    <div class="col-12"><hr class="my-2 text-muted"></div>

    <div class="col-md-6">

        <?= FormHelper::btnCheckYesNo('sonda', $patient->sonda ?? 0, $eshFormIdPrefix . 'sonda', [

            'label' => 'Sonda Takılı mı?',

            'groupMb' => 'mb-2',

        ]) ?>

        <div id="<?= htmlspecialchars($eshFormIdPrefix, ENT_QUOTES, 'UTF-8') ?>sondaTarihiArea" style="<?= !$patient->sonda ? 'display: none;' : '' ?>">

            <?= FormHelper::fieldDateGroup('sondatarihi', 'Sonda Değişim / Takılma Tarihi', $patient->sondatarihi ?? '', [

                'inputGroupSm' => true,

            ]) ?>

        </div>

    </div>

</div>

