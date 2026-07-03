<?php

declare(strict_types=1);

/**
 * Admin kullanıcı listesi — süper yönetici kurum süzgeci.
 * Controller: $eshUserListKurum, $eshUserListBolge (isteğe bağlı, PS bölge süzgeci)
 */
use App\Helpers\AuthHelper;
use App\Helpers\FormHelper;
use App\Helpers\FederationHelper;
use App\Helpers\TenantContext;
use App\Models\Kurum;

if (!AuthHelper::sessionIsSuperAdmin() || !Kurum::tableExists()) {
    return;
}

$k = isset($eshUserListKurum) ? (string) $eshUserListKurum : '';
if ($k !== '' && (!ctype_digit($k) || (int) $k <= 0)) {
    $k = '';
}

$eshIsPsKurumFilter = AuthHelper::sessionIsPlatformOwner() && FederationHelper::columnsReady();
$eshKurumListForUserFilter = $eshIsPsKurumFilter
    ? (new Kurum())->getList(true, 'ad ASC', null)
    : TenantContext::kurumListForScope(true);

$colClass = 'col-12 col-sm-6 col-lg-4 col-xl-2';
$labelClass = 'form-label fw-semibold small text-secondary mb-1';
$selectClass = 'form-select form-select-sm shadow-sm esh-filter-control';
$selectId = 'esh-user-filter-kurum';

if ($eshIsPsKurumFilter) {
    ?>
<div class="<?= htmlspecialchars($colClass, ENT_QUOTES, 'UTF-8') ?>">
    <label class="<?= htmlspecialchars($labelClass, ENT_QUOTES, 'UTF-8') ?>" for="<?= htmlspecialchars($selectId, ENT_QUOTES, 'UTF-8') ?>">Kurum</label>
    <select name="kurum_id" id="<?= htmlspecialchars($selectId, ENT_QUOTES, 'UTF-8') ?>" class="<?= htmlspecialchars($selectClass, ENT_QUOTES, 'UTF-8') ?>" data-esh-bolge-cascade="1">
        <option value="">Tüm kurumlar</option>
        <?php foreach ($eshKurumListForUserFilter as $kurumRow): ?>
            <?php
            $kid = (int) ($kurumRow->id ?? 0);
            if ($kid <= 0) {
                continue;
            }
            $bolgeId = isset($kurumRow->bolge_id) && $kurumRow->bolge_id !== null ? (int) $kurumRow->bolge_id : 0;
            $label = (string) ($kurumRow->ad ?? '') . ' (' . (string) ($kurumRow->kod ?? '') . ')';
            ?>
        <option value="<?= $kid ?>" data-bolge-id="<?= $bolgeId ?>"<?= $k === (string) $kid ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
    </select>
</div>
    <?php
    return;
}

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
    'col' => $colClass,
    'id' => $selectId,
    'labelClass' => $labelClass,
    'class' => 'form-select-sm shadow-sm esh-filter-control',
    'tomSelect' => false,
]);
