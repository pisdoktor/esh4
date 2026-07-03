<?php
/**
 * @var string $date_from
 * @var string $date_to
 * @var string $date_from_ymd
 * @var string $date_to_ymd
 * @var array $report
 */
$partial = __DIR__ . '/partials/randevu_kayit_gap_section.php';
$sectionTitle = 'Hasta kartı — kayıt ve randevu tarihi';
$sectionIcon = 'fa-id-card';
$chartHistId = 'hastaKayitRandevuHist';
$chartMonthId = 'hastaKayitRandevuMonth';
$filterExpanded = isset($_GET['date_from']) || isset($_GET['date_to']);
?>