<?php
/**
 * Tek TC grubu alt satırları (XHR; yalnızca <tr>…</tr>).
 *
 * @var list<object> $reports
 * @var string $tcGroup
 */
require __DIR__ . '/index_table_row.inc.php';

foreach ($reports as $childRow) {
    $renderEraporRow($childRow, [
        'isChild' => true,
        'isChildVisible' => true,
        'tcGroup' => (string) ($tcGroup ?? ''),
    ]);
}
