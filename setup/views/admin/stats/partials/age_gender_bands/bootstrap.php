<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php
/** @var array $raw */
use App\Helpers\AgeBandHelper;
use App\Helpers\CinsiyetHelper;

$template = AgeBandHelper::emptyCounts();
$stats = [
    CinsiyetHelper::ERKEK => AgeBandHelper::emptyCounts(),
    CinsiyetHelper::KADIN => AgeBandHelper::emptyCounts(),
];
foreach ($raw as $r) {
    $c = CinsiyetHelper::normalize($r->cinsiyet ?? null) ?? CinsiyetHelper::KADIN;
    if (!isset($stats[$c])) {
        $c = CinsiyetHelper::KADIN;
    }
    foreach ($template as $key => $_) {
        $stats[$c][$key] = (int) ($r->$key ?? 0);
    }
}
$labels = AgeBandHelper::labelsOrdered();
?>
