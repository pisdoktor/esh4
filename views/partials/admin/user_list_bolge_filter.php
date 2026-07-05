<?php

declare(strict_types=1);

/**
 * Admin kullanıcı listesi — sistem yöneticisi bölge süzgeci.
 * Controller: $eshUserListBolge
 */
use App\Helpers\AuthHelper;
use App\Helpers\FormHelper;
use App\Helpers\FederationHelper;
use App\Models\FederationRegion;

if (!AuthHelper::sessionIsPlatformOwner() || !FederationHelper::columnsReady()) {
    return;
}

$b = isset($eshUserListBolge) ? (string) $eshUserListBolge : '';
if ($b !== '' && (!ctype_digit($b) || (int) $b <= 0)) {
    $b = '';
}

$eshBolgeFilterOptions = [FormHelper::makeOption('', 'Tüm bölgeler')];
foreach ((new FederationRegion())->getList(true) as $bolgeRow) {
    $bid = (int) ($bolgeRow->id ?? 0);
    if ($bid <= 0) {
        continue;
    }
    $label = trim((string) ($bolgeRow->ad ?? ''));
    if (!empty($bolgeRow->kod)) {
        $label = $label !== '' ? $label . ' (' . (string) $bolgeRow->kod . ')' : (string) $bolgeRow->kod;
    }
    $eshBolgeFilterOptions[] = FormHelper::makeOption((string) $bid, $label !== '' ? $label : ('Bölge #' . $bid));
}

echo FormHelper::fieldSelect('bolge_id', 'Bölge', $eshBolgeFilterOptions, $b, [
    'col' => 'col-12 col-sm-6 col-lg-4 col-xl-2',
    'id' => 'esh-user-filter-bolge',
    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
    'class' => 'form-select-sm shadow-sm esh-filter-control',
    'tomSelect' => false,
]);
