<?php

declare(strict_types=1);

/**

 * Admin kullanıcı listesi — süper yönetici kurum süzgeci.

 * Controller: $eshUserListKurum

 */

use App\Helpers\AuthHelper;

use App\Helpers\FormHelper;



if (!AuthHelper::sessionIsSuperAdmin() || !\App\Models\Kurum::tableExists()) {

    return;

}

$k = isset($eshUserListKurum) ? (string) $eshUserListKurum : '';

if ($k !== '' && (!ctype_digit($k) || (int) $k <= 0)) {

    $k = '';

}

$eshKurumListForUserFilter = (new \App\Models\Kurum())->getList(true);

$eshKurumFilterOptions = [FormHelper::makeOption('', 'Tüm kurumlar')];

foreach ($eshKurumListForUserFilter as $kurumRow) {

    $kid = (int) ($kurumRow->id ?? 0);

    if ($kid <= 0) {

        continue;

    }

    $label = (string) ($kurumRow->ad ?? '') . ' (' . (string) ($kurumRow->kod ?? '') . ')';

    $eshKurumFilterOptions[] = FormHelper::makeOption((string) $kid, $label);

}

echo FormHelper::fieldSelect('kurum_id', 'Kurum', $eshKurumFilterOptions, $k, [

    'col' => 'col-12 col-sm-6 col-lg-4 col-xl-2',

    'id' => 'esh-user-filter-kurum',

    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',

    'class' => 'form-select-sm shadow-sm esh-filter-control',

    'tomSelect' => false,

]);

