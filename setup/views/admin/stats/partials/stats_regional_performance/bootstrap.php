<div class="esh-page esh-page--list esh-page-stats container-fluid py-4">
<?php
/** @var array $rows */
/** @var string $since_ymd */
/** @var int $rolling_months */
$sinceTr = \App\Helpers\DateHelper::toTrOrEmpty($since_ymd ?? '');
?>