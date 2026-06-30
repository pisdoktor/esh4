<?php
/** @var array<string, mixed> $r */
use App\Helpers\AgeBandHelper;
use App\Helpers\BmiHelper;

$computable = (int) ($r['computable'] ?? 0);
$categories = $r['categories'] ?? [];
$catKeys = $r['cat_keys'] ?? [];
$byGender = $r['by_gender'] ?? [];
$byAge = $r['by_age'] ?? [];
$ageBandKeys = $r['age_band_keys'] ?? [];
$catMeta = $r['cat_meta'] ?? [];
$chartLabels = [];
$chartValues = [];
$chartColors = [];
foreach ($categories as $c) {
    $chartLabels[] = $c['label'];
    $chartValues[] = (int) $c['count'];
    $chartColors[] = $c['color'];
}
$ageChartLabels = array_map(static fn ($k) => $catMeta[$k]['label'] ?? $k, $catKeys);
$ageBandLabels = array_map(static fn ($k) => AgeBandHelper::label($k), $ageBandKeys);
$catChartColors = array_map(static fn ($k) => $catMeta[$k]['color'] ?? '#6c757d', $catKeys);
?>