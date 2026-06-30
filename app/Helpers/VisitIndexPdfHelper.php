<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Aktif izlem listesi (Visit::index) — pdfMake satır verisi.
 */
final class VisitIndexPdfHelper {
    /**
     * @return list<string>
     */
    public static function tableHeaders(): array {
        return [
            'Hasta adı',
            'TC',
            'Mahalle / ilçe',
            'İzlem tarihi',
            'Zaman',
            'Araç',
            'Yapılan işlem',
            'İzlemi yapan(lar)',
        ];
    }

    /**
     * @param array<string, mixed> $st indexListRequestState
     */
    public static function buildFilterSummary(array $st, int $total, string $islemLabel): string {
        $parts = [];
        $parts[] = 'Tarih: ' . DateHelper::toTrOrEmpty($st['dateFrom'] ?? '')
            . ' – ' . DateHelper::toTrOrEmpty($st['dateTo'] ?? '');
        $parts[] = ($st['yap'] ?? '1') === '0' ? 'Yapılmayan' : 'Yapılan';
        $parts[] = 'İşlem: ' . $islemLabel;
        $parts[] = 'Toplam: ' . $total;
        if (($st['search'] ?? '') !== '') {
            $parts[] = 'Arama: ' . $st['search'];
        }
        $page = (int) ($st['page'] ?? 1);
        $limit = (int) ($st['limit'] ?? 50);
        $parts[] = 'PDF sayfası: ' . $page . ' (sayfa başına ' . $limit . ' kayıt)';

        return implode(' · ', $parts);
    }

    public static function suggestFilename(string $yap, int $page): string {
        $kind = $yap === '0' ? 'yapilmayan' : 'yapilan';

        return 'Izlem_Listesi_' . $kind . '_sayfa' . max(1, $page) . '_' . date('Y-m-d') . '.pdf';
    }

    /**
     * @return list<string>
     */
    public static function exportVisitRow(object $row): array {
        $name = trim((string) ($row->isim ?? '') . ' ' . (string) ($row->soyisim ?? ''));
        if (!empty($row->gecici)) {
            $name .= ' [G]';
        }

        $mahalle = trim((string) ($row->mahalle ?? ''));
        $ilce = trim((string) ($row->ilce ?? ''));
        $konum = $mahalle !== '' && $ilce !== ''
            ? $mahalle . "\n" . $ilce
            : ($mahalle !== '' ? $mahalle : $ilce);

        $izlemTarihi = !empty($row->izlemtarihi)
            ? date('d-m-Y', strtotime((string) $row->izlemtarihi))
            : '—';

        $zaman = ZamanDilimiHelper::badgeFor($row->zaman ?? null);
        $zamanText = (string) ($zaman['text'] ?? '—');

        $arac = trim((string) ($row->aracplaka ?? ''));
        if ($arac === '') {
            $arac = '—';
        }

        return [
            $name,
            ValidationHelper::formatTc((string) ($row->hastatckimlik ?? '')),
            $konum,
            $izlemTarihi,
            $zamanText,
            $arac,
            VisitIslemHelper::yapilanlarKonsultasyonCellPlain(
                (string) ($row->yapilanlar ?? ''),
                (string) ($row->yapilan ?? ''),
                (string) ($row->kons_brans_istek ?? ''),
                (string) ($row->brans ?? ''),
                (string) ($row->kons_istekler ?? '')
            ),
            trim((string) ($row->yapanlar ?? '')) !== '' ? (string) $row->yapanlar : '—',
        ];
    }

    public static function islemFilterLabel(int $secim, array $islemler): string {
        if ($secim < 1) {
            return 'Tüm işlemler';
        }
        foreach ($islemler as $is) {
            if ((int) ($is->id ?? 0) === $secim) {
                return (string) ($is->islemadi ?? 'İşlem #' . $secim);
            }
        }

        return 'İşlem #' . $secim;
    }
}
