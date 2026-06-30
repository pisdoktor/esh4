<?php
namespace App\Helpers;

/**
 * Planlı izlem tekrarı: aralık (gün/hafta/ay) ve tekrar sayısı ile birden çok plan tarihi üretimi.
 */
class IzlemPlanTekrarHelper {
    public const ARALIK_YOK = 'yok';
    public const ARALIK_GUNLUK = 'gunluk';
    public const ARALIK_HAFTALIK = 'haftalik';
    public const ARALIK_AYLIK = 'aylik';

    public const SAYI_MIN = 1;
    public const SAYI_MAX = 100;

    /**
     * @return array<string, string> değer => etiket (form select)
     */
    public static function aralikSecenekleri(): array {
        return [
            self::ARALIK_YOK => 'Tek sefer (tekrar yok)',
            self::ARALIK_GUNLUK => 'Günlük',
            self::ARALIK_HAFTALIK => 'Haftalık',
            self::ARALIK_AYLIK => 'Aylık',
        ];
    }

    public static function normalizeAralik(?string $raw): string {
        $v = strtolower(trim((string) $raw));
        $allowed = array_keys(self::aralikSecenekleri());
        return in_array($v, $allowed, true) ? $v : self::ARALIK_YOK;
    }

    public static function normalizeSayi($raw): int {
        $n = is_numeric($raw) ? (int) $raw : self::SAYI_MIN;
        if ($n < self::SAYI_MIN) {
            $n = self::SAYI_MIN;
        }
        if ($n > self::SAYI_MAX) {
            $n = self::SAYI_MAX;
        }
        return $n;
    }

    /**
     * Başlangıç tarihinden itibaren N adet plan tarihi (Y-m-d), dahil.
     *
     * @return list<string>
     */
    public static function expandPlanDates(string $startYmd, string $aralik, int $sayi): array {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startYmd)) {
            return [date('Y-m-d')];
        }
        $sayi = self::normalizeSayi($sayi);
        $aralik = self::normalizeAralik($aralik);
        if ($aralik === self::ARALIK_YOK || $sayi < 2) {
            return [$startYmd];
        }

        $dates = [];
        $cur = new \DateTimeImmutable($startYmd);
        for ($i = 0; $i < $sayi; $i++) {
            $dates[] = $cur->format('Y-m-d');
            if ($i < $sayi - 1) {
                if ($aralik === self::ARALIK_GUNLUK) {
                    $cur = $cur->modify('+1 day');
                } elseif ($aralik === self::ARALIK_HAFTALIK) {
                    $cur = $cur->modify('+1 week');
                } elseif ($aralik === self::ARALIK_AYLIK) {
                    $cur = $cur->modify('+1 month');
                }
            }
        }
        return $dates;
    }
}
