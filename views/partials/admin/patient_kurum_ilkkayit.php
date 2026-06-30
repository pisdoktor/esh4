<?php



declare(strict_types=1);



use App\Helpers\AuthHelper;

use App\Helpers\FormHelper;

use App\Helpers\TenantContext;

use App\Models\Kurum;



if (!Kurum::tableExists()) {

    return;

}



$eshAssignKurumId = TenantContext::assignKurumIdForStore();



if (AuthHelper::sessionIsSuperAdmin()) {

    $eshKurumList = (new Kurum())->getList(true);

    if ($eshKurumList === []) {

        return;

    }

    $eshDefaultKurumId = TenantContext::sessionKurumFilter() ?? $eshAssignKurumId;

    $eshKurumOptions = [];

    foreach ($eshKurumList as $k) {

        $label = (string) ($k->ad ?? '');

        if (!empty($k->kod)) {

            $label .= ' (' . (string) $k->kod . ')';

        }

        $eshKurumOptions[] = FormHelper::makeOption((string) (int) ($k->id ?? 0), $label);

    }

    ?>

    <div class="mt-3" id="eshPatientKurumFieldWrap">

        <?php

        echo FormHelper::fieldSelect('kurum_id', 'Kurum', $eshKurumOptions, (string) (int) $eshDefaultKurumId, [

            'col' => '',

            'id' => 'eshPatientKurumSelect',

            'labelClass' => 'form-label fw-bold',

            'labelHtml' => '<i class="fa-solid fa-building me-1"></i>Kurum',

            'tomSelect' => false,

            'required' => true,

            'helpText' => 'Yeni hasta kaydı seçilen kuruma açılır.',

            'helpClass' => 'form-text',

        ]);

        ?>

    </div>

    <?php



    return;

}

?>

<input type="hidden" name="kurum_id" value="<?= (int) $eshAssignKurumId ?>">

