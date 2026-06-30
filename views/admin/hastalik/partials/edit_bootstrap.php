<?php
use App\Helpers\FormHelper;

$hastalikCatOptions = [];
foreach ($categories as $cat) {
    $hastalikCatOptions[] = FormHelper::makeOption((string) $cat->id, (string) $cat->name);
}
?>