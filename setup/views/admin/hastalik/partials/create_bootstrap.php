<?php
use App\Helpers\FormHelper;

$hastalikCatOptions = [];
foreach ($categories as $cat) {
    $catLabel = (string) $cat->name;
    $catRange = trim((string) ($cat->icd_range ?? ''));
    if ($catRange !== '') {
        $catLabel .= ' (' . $catRange . ')';
    }
    $hastalikCatOptions[] = FormHelper::makeOption((string) $cat->id, $catLabel);
}
?>