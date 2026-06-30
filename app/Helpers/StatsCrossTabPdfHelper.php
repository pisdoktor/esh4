<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Çapraz tablo raporu → pdfMake tablo gövdesi.
 */
final class StatsCrossTabPdfHelper {
    /**
     * @param array<string, mixed> $report StatsCrossTabBuilder::build çıktısı
     * @return array{headers: list<string>, rows: list<list<string>>, meta: array<string, string>, filename: string, title: string, headerLeft: string, widths: list<mixed>}
     */
    public static function buildPayload(array $report, int $months): array {
        $rowKeys = $report['row_keys'] ?? [];
        $colKeys = $report['col_keys'] ?? [];
        $rowLabels = $report['row_labels'] ?? [];
        $colLabels = $report['col_labels'] ?? [];
        $matrix = $report['matrix'] ?? [];
        $rowTotals = $report['row_totals'] ?? [];
        $colTotals = $report['col_totals'] ?? [];
        $grand = (int) ($report['grand_total'] ?? 0);
        $title = (string) ($report['title'] ?? 'Çapraz tablo');

        $headers = array_merge([''], array_map('strval', array_values($colLabels)));
        $headers[] = 'Toplam';

        $rows = [];
        if ($rowKeys === [] || $colKeys === []) {
            $rows[] = ['Bu kırılım için veri yok.'];
        } else {
            foreach ($rowKeys as $rk) {
                $line = [(string) ($rowLabels[$rk] ?? $rk)];
                foreach ($colKeys as $ck) {
                    $line[] = (string) (int) ($matrix[$rk][$ck] ?? 0);
                }
                $line[] = (string) (int) ($rowTotals[$rk] ?? 0);
                $rows[] = $line;
            }
            $totalLine = ['Toplam'];
            foreach ($colKeys as $ck) {
                $totalLine[] = (string) (int) ($colTotals[$ck] ?? 0);
            }
            $totalLine[] = (string) $grand;
            $rows[] = $totalLine;
        }

        $parts = [$title];
        if (!empty($report['period_label'])) {
            $parts[] = (string) $report['period_label'];
        } else {
            $parts[] = 'Dönem: son ' . $months . ' ay';
        }
        $parts[] = 'Toplam: ' . $grand . ' ' . (string) ($report['cell_unit'] ?? 'adet');

        $colCount = count($headers);
        $widths = ['*'];
        for ($i = 1; $i < $colCount; $i++) {
            $widths[] = 36;
        }

        $id = (string) ($report['id'] ?? 'crosstab');

        return [
            'headers' => $headers,
            'rows' => $rows,
            'meta' => [
                'filterSummary' => implode(' · ', $parts),
                'generatedAt' => DateHelper::nowTrDateTime(),
            ],
            'filename' => 'Istatistik_' . preg_replace('/[^a-z0-9]+/i', '_', $id) . '_' . date('Y-m-d') . '.pdf',
            'title' => mb_strtoupper($title, 'UTF-8'),
            'headerLeft' => 'ESH — Çapraz tablo',
            'widths' => $widths,
        ];
    }
}
