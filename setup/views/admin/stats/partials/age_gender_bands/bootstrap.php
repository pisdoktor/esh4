<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php
/** @var array $raw */
use App\Helpers\AgeBandHelper;

$template = AgeBandHelper::emptyCounts();
$stats = ['E' => $template, 'K' => $template];
foreach ($raw as $r) {
    $c = ($r->cinsiyet == '1' || $r->cinsiyet === 'E' || $r->cinsiyet === 1) ? 'E' : 'K';
    if (!isset($stats[$c])) {
        $c = 'K';
    }
    foreach ($template as $key => $_) {
        $stats[$c][$key] = (int) ($r->$key ?? 0);
    }
}
$labels = AgeBandHelper::labelsOrdered();
?>