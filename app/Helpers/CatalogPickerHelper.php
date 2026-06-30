<?php

declare(strict_types=1);

namespace App\Helpers;

/** Kurum kataloğu seçim ekranı (shuttle) JSON yardımcıları. */
final class CatalogPickerHelper
{
    /**
     * @param list<object> $items getListWithAssignmentState çıktısı
     * @return array{catalog: list<array{id: int, label: string}>, assigned: list<array{id: int, label: string, kota?: ?int}>}
     */
    public static function pickerJsonFromItems(array $items, string $labelField, bool $withKota = false): array
    {
        $catalog = [];
        $assigned = [];

        foreach ($items as $row) {
            $id = (int) ($row->id ?? 0);
            if ($id <= 0) {
                continue;
            }
            $label = trim((string) ($row->{$labelField} ?? ''));
            $entry = ['id' => $id, 'label' => $label !== '' ? $label : ('#' . $id)];

            if (!empty($row->assigned)) {
                if ($withKota) {
                    $kotaRaw = $row->hasta_kotasi ?? null;
                    $kota = ($kotaRaw !== null && (int) $kotaRaw > 0) ? (int) $kotaRaw : null;
                    $entry['kota'] = $kota;
                }
                $assigned[] = $entry;
            } else {
                $catalog[] = $entry;
            }
        }

        $sort = static function (array $a, array $b): int {
            return strcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        };
        usort($catalog, $sort);
        usort($assigned, $sort);

        return ['catalog' => $catalog, 'assigned' => $assigned];
    }
}
