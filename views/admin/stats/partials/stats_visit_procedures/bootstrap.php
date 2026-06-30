<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php
/** @var array $rows */
/** @var string $date_from */
/** @var string $date_to */
$top = array_slice(array_filter($rows, fn ($x) => ($x->adet ?? 0) > 0), 0, 12);
$filterExpanded = isset($_GET['date_from']) || isset($_GET['date_to']);
?>