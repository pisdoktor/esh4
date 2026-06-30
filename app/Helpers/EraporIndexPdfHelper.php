<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * e-Rapor havuzu (Erapor::index) — pdfMake satır verisi.
 */
final class EraporIndexPdfHelper {
    /**
     * @return list<string>
     */
    public static function tableHeaders(): array {
        return [
            'TC',
            'Hasta ad soyad',
            'Rapor tarihi',
            'Branş',
            'Durum',
            'Yenilendi',
            'Neden',
        ];
    }

    /**
     * @param array<string, mixed> $st indexRequestState
     * @param list<object> $branslar
     */
    public static function buildFilterSummary(array $st, int $total, array $branslar): string {
        $parts = [];
        $parts[] = 'Branş: ' . self::bransFilterLabel((int) ($st['bransFilter'] ?? 0), $branslar);
        if (($st['durumFilter'] ?? null) !== null) {
            $parts[] = (int) $st['durumFilter'] === 1 ? 'Sistemde kayıtlı' : 'Yeni veri';
        } else {
            $parts[] = 'Durum: Tümü';
        }
        if (($st['yenilendiFilter'] ?? null) !== null) {
            $parts[] = (int) $st['yenilendiFilter'] === 1 ? 'Yenilendi: Evet' : 'Yenilendi: Hayır';
        }
        if (($st['dateFromTr'] ?? '') !== '' || ($st['dateToTr'] ?? '') !== '') {
            $parts[] = 'Tarih: ' . trim(($st['dateFromTr'] ?? '') . ' – ' . ($st['dateToTr'] ?? ''), ' –');
        }
        $parts[] = 'Toplam: ' . $total;
        if (($st['search'] ?? '') !== '') {
            $parts[] = 'Arama: ' . $st['search'];
        }
        $page = (int) ($st['page'] ?? 1);
        $limit = (int) ($st['limit'] ?? 20);
        $parts[] = 'PDF sayfası: ' . $page . ' (sayfa başına ' . $limit . ' kayıt)';

        return implode(' · ', $parts);
    }

    /**
     * @param list<object> $branslar
     */
    public static function bransFilterLabel(int $bransId, array $branslar): string {
        if ($bransId < 1) {
            return 'Tüm branşlar';
        }
        foreach ($branslar as $b) {
            if ((int) ($b->id ?? 0) === $bransId) {
                return (string) ($b->bransadi ?? 'Branş #' . $bransId);
            }
        }

        return 'Branş #' . $bransId;
    }

    public static function suggestFilename(int $page): string {
        return 'Erapor_Havuzu_sayfa' . max(1, $page) . '_' . date('Y-m-d') . '.pdf';
    }

    /**
     * @return list<string>
     */
    public static function exportReportRow(object $row): array {
        $name = trim((string) ($row->isim ?? '') . ' ' . (string) ($row->soyisim ?? ''));
        $tcHavuzAdet = (int) ($row->tc_havuz_adet ?? 0);
        if ($tcHavuzAdet > 1) {
            $name .= ' (' . $tcHavuzAdet . '× havuz)';
        }

        $bransEtiket = '';
        if (!empty($row->bransadi)) {
            $bransEtiket = (string) $row->bransadi;
        } elseif ($row->brans !== null && $row->brans !== '') {
            $bransEtiket = (string) $row->brans;
        } else {
            $bransEtiket = '—';
        }

        $raporTarihi = !empty($row->basvurutarihi)
            ? date('d-m-Y', strtotime((string) $row->basvurutarihi))
            : '—';

        $durum = !empty($row->kayitlimi) ? 'Sistemde kayıtlı' : 'Yeni veri';
        $yenilendi = (int) ($row->yenilendimi ?? 0) === 1 ? 'Evet' : 'Hayır';

        $neden = trim(preg_replace('/\s+/u', ' ', str_replace(["\r\n", "\r", "\n"], ' ', (string) ($row->neden ?? ''))));

        return [
            ValidationHelper::formatTc((string) ($row->hastatckimlik ?? '')),
            $name !== '' ? $name : '—',
            $raporTarihi,
            $bransEtiket,
            $durum,
            $yenilendi,
            $neden !== '' ? $neden : '—',
        ];
    }
}
