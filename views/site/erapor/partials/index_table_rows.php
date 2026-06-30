<?php
/**
 * e-Rapor havuzu tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * Aynı TC birden fazla kayıt içeriyorsa üst satır + genişletilebilir alt satırlar.
 *
 * @var list<object> $reports
 */
require __DIR__ . '/index_table_row.inc.php';

if (empty($reports)): ?>
    <tr>
        <td colspan="7" class="text-center text-muted py-4">Kayıt bulunamadı.</td>
    </tr>
<?php else:
    $groups = [];
    $groupOrder = [];
    foreach ($reports as $row) {
        $tc = trim((string) ($row->hastatckimlik ?? ''));
        $key = $tc !== '' ? $tc : '__id_' . (int) ($row->id ?? 0);
        if (!isset($groups[$key])) {
            $groups[$key] = [];
            $groupOrder[] = $key;
        }
        $groups[$key][] = $row;
    }

    foreach ($groupOrder as $groupKey):
        $groupRows = $groups[$groupKey];
        $primary = $groupRows[0];
        $children = array_slice($groupRows, 1);
        $tcHavuzAdet = (int) ($primary->tc_havuz_adet ?? count($groupRows));
        $isMulti = $tcHavuzAdet > 1 && $groupKey !== '' && !str_starts_with($groupKey, '__id_');
        $tcGroup = $isMulti ? $groupKey : '';

        $renderEraporRow($primary, [
            'isExpandable' => $isMulti,
            'tcGroup' => $tcGroup,
            'tcHavuzAdet' => $tcHavuzAdet,
            'childCountOnPage' => count($children),
        ]);

        foreach ($children as $childRow) {
            $renderEraporRow($childRow, [
                'isChild' => true,
                'tcGroup' => $tcGroup,
            ]);
        }
    endforeach;
endif;
