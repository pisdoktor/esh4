<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * İstatistik PDF — ortak tablo biçimlendirme.
 */
final class StatsReportPdfFormatHelper {
    /**
     * @param list<object|array<string, mixed>> $items
     * @param list<string> $fields [labelKey, countKey, pctKey?]
     * @return list<list<string>>
     */
    public static function rowsFromItems(array $items, array $fields): array {
        $labelKey = $fields[0];
        $countKey = $fields[1];
        $pctKey = $fields[2] ?? null;
        $out = [];
        foreach ($items as $item) {
            $row = is_array($item) ? $item : (array) $item;
            $line = [
                (string) ($row[$labelKey] ?? $row['label'] ?? ''),
                (string) (int) ($row[$countKey] ?? $row['adet'] ?? 0),
            ];
            if ($pctKey !== null) {
                $pct = $row[$pctKey] ?? $row['pct'] ?? '';
                $line[] = $pct === '' ? '' : (is_numeric($pct) ? (string) $pct : (string) $pct);
            }
            $out[] = $line;
        }

        return $out;
    }

    /**
     * @param array{rows?: list<object>, total?: int, bilinmeyen?: int} $pack
     * @return array{headers: list<string>, rows: list<list<string>>}
     */
    public static function labelAdetPack(array $pack, bool $withPct = true): array {
        $headers = $withPct ? ['Kırılım', 'Adet', '%'] : ['Kırılım', 'Adet'];
        $rows = self::rowsFromItems($pack['rows'] ?? [], ['label', 'adet', 'pct']);
        if (!empty($pack['bilinmeyen'])) {
            $rows[] = ['Bilinmeyen / eksik', (string) (int) $pack['bilinmeyen'], ''];
        }
        if (!empty($pack['total'])) {
            $rows[] = ['Toplam', (string) (int) $pack['total'], ''];
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * @param array<string, mixed> $card
     * @return array{headers: list<string>, rows: list<list<string>>, meta: array<string, string>, filename: string, title: string, headerLeft: string}
     */
    public static function stubFromCard(array $card, string $reason = ''): array {
        $title = (string) ($card['title'] ?? 'İstatistik raporu');
        $desc = (string) ($card['desc'] ?? '');
        $action = (string) ($card['action'] ?? '');
        $url = StatsNavHelper::statsPageUrl($action);

        $rows = [
            ['Rapor', $title],
            ['Açıklama', $desc],
            ['Web adresi', $url],
        ];
        if ($reason !== '') {
            $rows[] = ['Not', $reason];
        }

        return self::payload(
            $title,
            ['Alan', 'Değer'],
            $rows,
            'Bu kart için özet PDF. Tam tablo/grafik çıktısı rapor sayfasından alınabilir.',
            self::filename($action)
        );
    }

    /**
     * @param list<list<string>> $rows
     * @return array{headers: list<string>, rows: list<list<string>>, meta: array<string, string>, filename: string, title: string, headerLeft: string, widths?: list<mixed>}
     */
    public static function payload(
        string $title,
        array $headers,
        array $rows,
        string $filterSummary,
        string $filename,
        ?array $widths = null
    ): array {
        $out = [
            'headers' => $headers,
            'rows' => $rows,
            'meta' => [
                'filterSummary' => $filterSummary,
                'generatedAt' => DateHelper::nowTrDateTime(),
            ],
            'filename' => $filename,
            'title' => mb_strtoupper($title, 'UTF-8'),
            'headerLeft' => 'ESH — ' . $title,
        ];
        if ($widths !== null) {
            $out['widths'] = $widths;
        }

        return $out;
    }

    public static function filename(string $action): string {
        $slug = preg_replace('/[^a-z0-9]+/i', '_', $action) ?: 'rapor';
        $slug = trim((string) $slug, '_');

        return 'Istatistik_' . $slug . '_' . date('Y-m-d') . '.pdf';
    }

    /**
     * @param array<string, string> $pairs
     * @return list<list<string>>
     */
    public static function keyValueRows(array $pairs): array {
        $rows = [];
        foreach ($pairs as $k => $v) {
            $rows[] = [(string) $k, (string) $v];
        }

        return $rows;
    }

    public static function dateRangeFromQuery(array $query, string $defaultFromExpr = 'first day of this month'): array {
        $from = isset($query['date_from']) ? trim((string) $query['date_from']) : '';
        $to = isset($query['date_to']) ? trim((string) $query['date_to']) : '';
        $today = date('Y-m-d');
        $fromYmd = DateHelper::trDateToYmd($from) ?: DateHelper::parseFilterDate($from, $today);
        $toYmd = DateHelper::trDateToYmd($to) ?: DateHelper::parseFilterDate($to, $today);
        if ($fromYmd === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromYmd)) {
            $fromYmd = (new \DateTimeImmutable($defaultFromExpr))->format('Y-m-d');
        }
        if ($toYmd === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toYmd)) {
            $toYmd = $today;
        }
        if ($fromYmd > $toYmd) {
            [$fromYmd, $toYmd] = [$toYmd, $fromYmd];
        }

        return [$fromYmd, $toYmd];
    }
}
