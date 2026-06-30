<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Birleşik hasta listesi — pdfMake tablo satırları (JSON → istemci).
 */
final class PatientUnifiedPdfHelper {
    /**
     * @return array<string, string>
     */
    public static function statusLabelsForSession(bool $isAdmin): array {
        if ($isAdmin) {
            return [
                'all' => 'Tüm durumlar',
                'active' => 'Aktif',
                'passive' => 'Pasif (dosya kapalı)',
                'waiting' => 'Bekleyen (ilk kayıt)',
                'deleted' => 'Silinen (manuel)',
                'araf' => 'Araf',
                'probable' => 'Muhtemel ölen',
            ];
        }

        return [
            'active' => 'Aktif',
            'passive' => 'Pasif (dosya kapalı)',
            'waiting' => 'Bekleyen (ilk kayıt)',
        ];
    }

    public static function pasifTarihiColumnTitle(string $status): string {
        return match ($status) {
            'deleted' => 'Silinme tarihi',
            'passive' => 'Pasif tarihi',
            default => 'Ölüm tarihi',
        };
    }

    public static function usesPasifTarihiColumn(string $status): bool {
        return in_array($status, ['passive', 'probable', 'araf', 'deleted'], true);
    }

    /**
     * @return list<string>
     */
    public static function tableHeaders(bool $usesPasifTarihi, string $altColTitle): array {
        return [
            'Durum',
            'Hasta adı',
            'TC',
            'Mahalle / İlçe',
            'Anne / Baba',
            'D. tarihi',
            'İletişim',
            'Kayıt',
            $altColTitle,
            'Son izlem',
            'İzlem (Y/Yp/P)',
        ];
    }

    /**
     * @param array<string, mixed> $st unifiedListRequestState parçası
     */
    public static function buildFilterSummary(array $st, int $total, string $statusLabel): string {
        $parts = ['Durum: ' . $statusLabel, 'Toplam: ' . $total];
        if (($st['search'] ?? '') !== '') {
            $parts[] = 'Arama: ' . $st['search'];
        }
        if (($st['reason'] ?? '') !== '') {
            $parts[] = 'Pasif nedeni filtresi';
        }
        if (($st['startDate'] ?? '') !== '' || ($st['endDate'] ?? '') !== '') {
            $parts[] = 'Pasif tarihi: ' . trim(($st['startDate'] ?? '') . ' – ' . ($st['endDate'] ?? ''), ' –');
        }
        if (($st['feature'] ?? '') !== '') {
            $parts[] = 'Özellik: ' . BadgeHelper::patientFeatureFilterLabel((string) $st['feature']);
        }
        $page = (int) ($st['page'] ?? 1);
        $limit = (int) ($st['limit'] ?? 20);
        $parts[] = 'PDF sayfası: ' . $page . ' (sayfa başına ' . $limit . ' kayıt)';

        return implode(' · ', $parts);
    }

    public static function suggestFilename(string $status, int $page): string {
        $slug = preg_replace('/[^a-z0-9]+/i', '_', $status) ?: 'liste';
        $slug = trim((string) $slug, '_');

        return 'Hasta_Listesi_' . $slug . '_sayfa' . max(1, $page) . '_' . date('Y-m-d') . '.pdf';
    }

    /**
     * @return list<string>
     */
    public static function exportPatientRow(object $p, bool $usesPasifTarihi): array {
        $name = trim((string) ($p->isim ?? '') . ' ' . (string) ($p->soyisim ?? ''));
        $flags = self::patientFeatureLetters($p);
        if ($flags !== '') {
            $name .= ' [' . $flags . ']';
        }

        $mahalle = trim((string) ($p->mahalle_adi ?? ''));
        $ilce = trim((string) ($p->ilce_adi ?? ''));
        $konum = $mahalle !== '' && $ilce !== ''
            ? $mahalle . ' / ' . $ilce
            : ($mahalle !== '' ? $mahalle : $ilce);

        $dogum = DateHelper::toTrOrEmpty($p->dogumtarihi ?? '');
        $yas = DateHelper::calculateAge($p->dogumtarihi ?? '');
        if ($dogum !== '' && $yas !== '' && $yas !== '—') {
            $dogum .= "\n" . $yas . ' yaş';
        }

        $anneBaba = 'A: ' . trim((string) ($p->anneAdi ?? '')) . "\nB: " . trim((string) ($p->babaAdi ?? ''));

        $altRaw = $usesPasifTarihi ? ($p->pasiftarihi ?? '') : ($p->randevutarihi ?? '');
        $altDate = DateHelper::toTrOrEmpty((string) $altRaw);
        if ($altDate === '') {
            $altDate = '—';
        }

        $sonIzlem = DateHelper::toTrOrEmpty($p->sonizlemtarihi ?? '');
        if ($sonIzlem === '') {
            $sonIzlem = 'İzlem yok';
        }

        $izlem = (int) ($p->izlemsayisi ?? 0) . '/'
            . (int) ($p->yizlemsayisi ?? 0) . '/'
            . (int) ($p->totalplanli ?? 0);

        return [
            BadgeHelper::patientUnifiedMenuStatusLine($p),
            $name,
            ValidationHelper::formatTc((string) ($p->tckimlik ?? '')),
            $konum,
            $anneBaba,
            $dogum,
            trim((string) ($p->ceptel1 ?? '')),
            DateHelper::toTrOrEmpty($p->kayittarihi ?? ''),
            $altDate,
            $sonIzlem,
            $izlem,
        ];
    }

    private static function patientFeatureLetters(object $patient): string {
        $parts = [];
        if (BadgeHelper::patientHasGeciciFlag($patient)) {
            $parts[] = 'G';
        }
        if (BadgeHelper::patientHasNotesFlag($patient)) {
            $parts[] = 'N';
        }
        if (BadgeHelper::patientHasEraporFlag($patient)) {
            $parts[] = 'E';
        }

        return implode(',', $parts);
    }
}
