<?php
/** @var array $data */
/** @var string $date_from */
/** @var string $date_to */
$monthRows = $data['month_rows'] ?? [];
$monthLabels = $data['month_labels'] ?? [];
$bransTop = $data['brans_top'] ?? [];
$istekTop = $data['istek_top'] ?? [];
$pairTop = $data['pair_top'] ?? [];
$filterExpanded = isset($_GET['date_from']) || isset($_GET['date_to']);
?>