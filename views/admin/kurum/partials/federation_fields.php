<?php
declare(strict_types=1);

use App\Helpers\FormHelper;
use App\Helpers\FederationHelper;

if (!FederationHelper::columnsReady()) {
    return;
}

/** @var object|null $kurum */
$kurum = $kurum ?? null;
$bolgeOptions = [FormHelper::makeOption('', '— Bölge seçin —')];
foreach (FederationHelper::bolgeSelectOptions(true) as $id => $label) {
    $bolgeOptions[] = FormHelper::makeOption((string) $id, $label);
}
$selectedBolge = (string) (int) ($kurum->bolge_id ?? 0);
if ($selectedBolge === '0') {
    $selectedBolge = '';
}
?>
<div class="col-12"><hr><h6 class="text-muted"><i class="fa-solid fa-diagram-project me-1"></i>Federasyon</h6></div>
<?= FormHelper::fieldSelect('bolge_id', 'Bölge', $bolgeOptions, $selectedBolge, [
    'col' => 'col-md-6',
    'labelClass' => 'form-label',
    'class' => 'form-select-sm',
]) ?>
<?= FormHelper::fieldInput('federation_ref', 'Federasyon düğüm ref', (string) ($kurum->federation_ref ?? ''), [
    'col' => 'col-md-6',
    'labelClass' => 'form-label',
    'class' => 'form-control-sm font-monospace',
    'placeholder' => 'hub-kurum-ref',
    'maxlength' => 64,
]) ?>
