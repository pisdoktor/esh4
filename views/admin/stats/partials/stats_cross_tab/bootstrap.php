<?php
$rowKeys = $report['row_keys'] ?? [];
$colKeys = $report['col_keys'] ?? [];
$rowLabels = $report['row_labels'] ?? [];
$colLabels = $report['col_labels'] ?? [];
$matrix = $report['matrix'] ?? [];
$rowTotals = $report['row_totals'] ?? [];
$colTotals = $report['col_totals'] ?? [];
$grand = (int) ($report['grand_total'] ?? 0);
$cellUnit = (string) ($report['cell_unit'] ?? 'adet');
$periodType = (string) ($report['period_type'] ?? '');
$months = \App\Helpers\StatsCrossTabBuilder::normalizePeriodMonths((int) ($months ?? 12));
$periodMonthChoices = \App\Helpers\StatsCrossTabBuilder::periodMonthChoices();
$action = 'xTab_' . (string) ($tabId ?? ($report['id'] ?? ''));
$periodFilterBaseQ = [
    'controller' => 'Stats',
    'action' => $action,
];
$groupByUnvan = !empty($report['group_rows_by_unvan']);
$rowUnvan = $report['row_unvan'] ?? [];
$colSpan = count($colKeys) + 2;
?>