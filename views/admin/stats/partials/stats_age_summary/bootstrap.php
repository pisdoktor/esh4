<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php
/** @var array $report */
use App\Helpers\AgeBandHelper;
$bands = $report['bands'] ?? [];
$bandLabels = [];
$bandValues = [];
foreach (AgeBandHelper::keys() as $k) {
    $bandLabels[] = AgeBandHelper::label($k);
    $bandValues[] = (int) ($bands[$k] ?? 0);
}
?>