<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Akıllı planlama 2.0 — rota atama skoru (izolasyon, yetkinlik, kapasite).
 */
final class SmartPlanningHelper
{
    public const GOREV_PANSUMAN = 'pansuman';
    public const GOREV_MUAYENE = 'muayene';
    public const GOREV_IZLEM = 'izlem';

    /** @return array<string, list<string>> */
    public static function preferredUnvansByGorev(): array
    {
        return [
            self::GOREV_PANSUMAN => ['hemsire', 'ebe', 'saglik_memuru'],
            self::GOREV_MUAYENE => ['doktor', 'uzman_doktor'],
            self::GOREV_IZLEM => ['hemsire', 'doktor', 'ebe', 'saglik_memuru'],
        ];
    }

    public static function isIsolationPatient(object $hasta): bool
    {
        return (int) ($hasta->izolasyon ?? 0) === 1;
    }

    /**
     * @param list<object> $hastalar
     */
    public static function teamIsolationCount(array $hastalar): int
    {
        $n = 0;
        foreach ($hastalar as $h) {
            if (self::isIsolationPatient($h)) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * @param array<string, mixed> $ekipData
     * @param array<string, int|float> $cfg
     */
    public static function effectiveCapacity(array $ekipData, array $cfg): int
    {
        $defaultCap = max(1, (int) ($cfg['varsayilan_arac_kapasitesi'] ?? 4));
        $aracCap = (int) ($ekipData['arac_kapasite'] ?? $defaultCap);
        $personel = max(1, (int) ($ekipData['personel'] ?? 1));
        $perPerson = max(1, (int) ($cfg['personel_dosya_sayisi'] ?? 10));

        return max(1, min($aracCap, $personel * $perPerson));
    }

    /**
     * @param array<string, mixed> $ekipData
     * @param array<string, int|float> $cfg
     */
    public static function canAssign(array $ekipData, object $hasta, array $cfg): bool
    {
        unset($hasta);
        $hastalar = $ekipData['hastalar'] ?? [];
        if (!is_array($hastalar)) {
            $hastalar = [];
        }
        $n = count($hastalar);
        $cap = self::effectiveCapacity($ekipData, $cfg);
        if ($n >= $cap) {
            return false;
        }
        $personel = max(1, (int) ($ekipData['personel'] ?? 1));
        $perPersonMax = $personel * max(1, (int) ($cfg['personel_dosya_sayisi'] ?? 10));

        return $n < $perPersonMax;
    }

    /**
     * @param list<string> $unvans
     */
    public static function teamMatchesGorev(array $unvans, string $gorev): bool
    {
        $preferred = self::preferredUnvansByGorev()[$gorev] ?? [];
        foreach ($unvans as $u) {
            $code = trim((string) $u);
            if ($code !== '' && in_array($code, $preferred, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Düşük skor daha iyi atamadır.
     *
     * @param array<string, mixed> $ekipData
     * @param array<string, int|float> $cfg
     */
    public static function scoreAssignment(
        array $ekipData,
        object $hasta,
        float $travelMinutes,
        array $cfg
    ): float {
        $maliyet = $travelMinutes * (float) ($cfg['travel_time_weight'] ?? 1);

        $personel = max(1, (int) ($ekipData['personel'] ?? 1));
        $hastalar = $ekipData['hastalar'] ?? [];
        if (!is_array($hastalar)) {
            $hastalar = [];
        }
        $n = count($hastalar);
        $isYukuOrani = $n / $personel;
        $maliyet += $isYukuOrani * (float) ($cfg['is_yuku_cezasi'] ?? 10) * 2;

        $mahalleId = (int) ($hasta->mahalle_id ?? -1);
        $bolgeId = (int) ($hasta->bolge_id ?? -1);
        if ($mahalleId === (int) ($ekipData['son_mahalle'] ?? -2)) {
            $maliyet -= (float) ($cfg['mahalle_bonusu'] ?? 40);
        } elseif ($bolgeId === (int) ($ekipData['son_bolge'] ?? -2)) {
            $maliyet -= (float) ($cfg['bolge_bonusu'] ?? 50);
        }

        if ((int) ($hasta->oncelik ?? 0) === 2) {
            $maliyet -= (float) ($cfg['oncelik_yuksek_bonusu'] ?? 75);
        }

        if (self::isIsolationPatient($hasta)) {
            $maliyet -= (float) ($cfg['izolasyon_oncelik_bonusu'] ?? 60);
        }

        $isoCount = self::teamIsolationCount($hastalar);
        $karisimCezasi = (float) ($cfg['izolasyon_karisim_cezasi'] ?? 120);
        if ($isoCount > 0 && !self::isIsolationPatient($hasta)) {
            $maliyet += $karisimCezasi;
        }
        if (self::isIsolationPatient($hasta) && $n > $isoCount) {
            $maliyet += $karisimCezasi * 0.5;
        }

        $gorev = trim((string) ($hasta->gorev_tipi ?? self::GOREV_IZLEM));
        if ($gorev === '') {
            $gorev = self::GOREV_IZLEM;
        }
        $unvans = $ekipData['unvans'] ?? [];
        if (!is_array($unvans)) {
            $unvans = [];
        }
        if (self::teamMatchesGorev($unvans, $gorev)) {
            $maliyet -= (float) ($cfg['yetkinlik_eslesme_bonusu'] ?? 30);
        }

        return $maliyet;
    }

    /**
     * Çok günlü rota özeti — günlük plan yoğunluğu ve mesafe uyarıları.
     *
     * @param array<string, array<int, array<string, mixed>>> $sonucByDate tarih => vardiya => ekip verisi
     * @return list<array{date:string,tip:string,mesaj:string,ekip?:string}>
     */
    public static function multiDayRouteAnalysis(array $sonucByDate, float $kmWarningThreshold = 60.0): array
    {
        $alerts = [];
        $dates = array_keys($sonucByDate);
        sort($dates);
        if ($dates === []) {
            return $alerts;
        }

        $totalPatients = 0;
        $totalKm = 0.0;
        foreach ($sonucByDate as $date => $vardiyalar) {
            if (!is_array($vardiyalar)) {
                continue;
            }
            $dayPatients = 0;
            $dayKm = 0.0;
            foreach ($vardiyalar as $ekipNo => $ekipData) {
                if (!is_array($ekipData)) {
                    continue;
                }
                $hastaCount = count($ekipData['hastalar'] ?? []);
                $km = (float) ($ekipData['toplam_km'] ?? 0.0);
                $dayPatients += $hastaCount;
                $dayKm += $km;
                if ($km > $kmWarningThreshold) {
                    $alerts[] = [
                        'date' => (string) $date,
                        'tip' => 'warning',
                        'ekip' => 'Ekip ' . $ekipNo,
                        'mesaj' => number_format($km, 1, ',', '.') . ' km — günlük rota mesafesi yüksek.',
                    ];
                }
            }
            $totalPatients += $dayPatients;
            $totalKm += $dayKm;
            if ($dayPatients >= 25) {
                $alerts[] = [
                    'date' => (string) $date,
                    'tip' => 'danger',
                    'mesaj' => $dayPatients . ' hasta — günlük kapasite eşiği aşıldı.',
                ];
            }
        }

        $dayCount = count($dates);
        if ($dayCount > 1) {
            $avgPatients = $totalPatients / $dayCount;
            $alerts[] = [
                'date' => $dates[0] . '…' . $dates[$dayCount - 1],
                'tip' => 'info',
                'mesaj' => sprintf(
                    '%d gün, ortalama %.1f hasta/gün, toplam %.1f km.',
                    $dayCount,
                    $avgPatients,
                    $totalKm
                ),
            ];
        }

        return $alerts;
    }
}
