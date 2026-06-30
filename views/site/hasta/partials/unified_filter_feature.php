<?php

/**

 * Birleşik hasta listesi — admin özellik filtresi (G / N / E rozetleri).

 *

 * @var string $feature Seçili filtre anahtarı

 */

use App\Helpers\AuthHelper;
use App\Helpers\BadgeHelper;
use App\Helpers\FormHelper;



if (!AuthHelper::sessionIsAdmin()) {

    return;

}



$eshFeature = isset($feature) ? (string) $feature : '';

$eshFeatureOptions = [];

foreach (BadgeHelper::patientFeatureFilterChoices() as $k => $label) {

    $eshFeatureOptions[] = FormHelper::makeOption((string) $k, $label);

}

echo FormHelper::fieldSelect('feature', 'Özellik (rozet)', $eshFeatureOptions, $eshFeature, [

    'col' => 'col-12 col-md-6 col-xl-3',

    'id' => 'esh-unified-feature-filter',

    'labelClass' => 'form-label small text-muted mb-1',

    'class' => 'form-select-sm shadow-sm esh-filter-control',

    'tomSelect' => false,

]);

