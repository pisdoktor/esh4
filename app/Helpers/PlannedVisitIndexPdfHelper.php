<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Planlı izlem listesi (PlannedVisit::index) — pdfMake satır verisi.
 */
final class PlannedVisitIndexPdfHelper {
    /** @var array<string, array{text:string, class:string}> */
    private const ONCELIK = [
        '1' => ['text' => 'Normal', 'class' => 'success'],
        '2' => ['text' => 'Orta', 'class' => 'warning'],
        '3' => ['text' => 'Yüksek', 'class' => 'danger'],
        '0' => ['text' => '—', 'class' => 'secondary'],
    ];

    /**
     * @return list<string>
     */
    public static function tableHeaders(): array {
        return [
            'Hasta adı',
            'TC',
            'Mahalle / ilçe',
            'Plan tarihi',
            'Zaman',
            'Öncelik',
            'Yapılacak işlem',
            'Planlayan(lar)',
            'Durum',
        ];
    }

    /**
     * @param array<string, mixed> $st
     */
    public static function buildFilterSummary(array $st, int $total, string $islemLabel, string $durumLabel): string {
        $parts = [];
        $parts[] = 'Plan tarihi: ' . DateHelper::toTrOrEmpty($st['dateFrom'] ?? '')
            . ' – ' . DateHelper::toTrOrEmpty($st['dateTo'] ?? '');
        $parts[] = 'Durum: ' . $durumLabel;
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

    public static function durumFilterLabel(string $durum): string {
        return match ($durum) {
            '0' => 'Bekleyen',
            '1' => 'Yapıldı',
            default => 'Tümü',
        };
    }

    public static function suggestFilename(string $durum, int $page): string {
        $slug = match ($durum) {
            '0' => 'bekleyen',
            '1' => 'yapildi',
            default => 'tumu',
        };

        return 'Planli_Izlem_' . $slug . '_sayfa' . max(1, $page) . '_' . date('Y-m-d') . '.pdf';
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

    /**
     * @return list<string>
     */
    public static function exportPlanRow(object $p): array {
        $name = trim((string) ($p->isim ?? '') . ' ' . (string) ($p->soyisim ?? ''));
        if (!empty($p->gecici)) {
            $name .= ' [G]';
        }
        $tel = trim((string) ($p->ceptel1 ?? '')) !== ''
            ? trim((string) $p->ceptel1)
            : trim((string) ($p->ceptel2 ?? ''));
        if ($tel !== '') {
            $name .= "\n" . $tel;
        }

        $mahalle = trim((string) ($p->mahalle ?? ''));
        $ilce = trim((string) ($p->ilce ?? ''));
        $konum = $mahalle !== '' && $ilce !== ''
            ? $mahalle . "\n" . $ilce
            : ($mahalle !== '' ? $mahalle : $ilce);

        $planTarih = !empty($p->planlanantarih)
            ? date('d-m-Y', strtotime((string) $p->planlanantarih))
            : '—';

        $zaman = ZamanDilimiHelper::badgeFor($p->zaman ?? null);
        $zamanText = (string) ($zaman['text'] ?? '—');

        $ok = (string) (int) ($p->oncelik ?? 1);
        $onc = self::ONCELIK[$ok] ?? self::ONCELIK['0'];
        $oncelikText = $onc['text'];

        $durumText = (int) ($p->durum ?? 0) === 1 ? 'Yapıldı' : 'Bekleyen';
        $bugun = date('Y-m-d');
        if ((int) ($p->durum ?? 0) === 0 && !empty($p->planlanantarih) && $p->planlanantarih < $bugun) {
            $durumText .= ' (gecikmiş)';
        }

        return [
            $name,
            ValidationHelper::formatTc((string) ($p->hastatckimlik ?? '')),
            $konum,
            $planTarih,
            $zamanText,
            $oncelikText,
            trim((string) ($p->yapilacaklar ?? '')) !== '' ? (string) $p->yapilacaklar : '—',
            trim((string) ($p->planlayanlar ?? '')) !== '' ? (string) $p->planlayanlar : '—',
            $durumText,
        ];
    }
}
