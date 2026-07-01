<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php

/** @var string $scale @var object $report */

$scaleMeta = [
    'braden' => [
        'pageTitle' => 'Braden risk dağılımı',
        'icon' => 'fa-kit-medical',
        'color' => 'warning',
        'chartId' => 'chartClinicalScale',
        'chartColors' => ['#dc3545', '#f8d7da', '#fd7e14', '#ffc107', '#0dcaf0', '#198754'],
        'scoreLabel' => 'Ortalama skor',
    ],
    'itaki' => [
        'pageTitle' => 'İTAKİ II risk dağılımı',
        'icon' => 'fa-person-falling',
        'color' => 'danger',
        'chartId' => 'chartClinicalScale',
        'chartColors' => ['#198754', '#dc3545'],
        'scoreLabel' => 'Ortalama skor',
    ],
    'harizmi' => [
        'pageTitle' => 'Harizmi II risk dağılımı',
        'icon' => 'fa-child',
        'color' => 'info',
        'chartId' => 'chartClinicalScale',
        'chartColors' => ['#198754', '#dc3545'],
        'scoreLabel' => 'Ortalama skor',
    ],
    'mna' => [
        'pageTitle' => 'MNA-SF durum dağılımı',
        'icon' => 'fa-utensils',
        'color' => 'success',
        'chartId' => 'chartClinicalScale',
        'chartColors' => ['#198754', '#ffc107', '#dc3545'],
        'scoreLabel' => 'Ortalama skor',
    ],
];

$meta = $scaleMeta[$scale] ?? $scaleMeta['braden'];
$grup = (array) ($report->gruplar ?? []);
$tot = max(1, (int) ($report->degerlendirilen_hasta ?? 0));

?>
