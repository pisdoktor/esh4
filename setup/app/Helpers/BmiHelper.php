<?php
namespace App\Helpers;

/**
 * VKİ (BMI) hesaplama ve sınıflandırma — aktif hasta antropometri raporları.
 */
class BmiHelper {
    public const CATEGORY_ZAYIF = 'zayif';
    public const CATEGORY_NORMAL = 'normal';
    public const CATEGORY_OBEZ = 'obez';
    public const CATEGORY_MORBID = 'morbid_obez';

    /** @return array<string, array{label: string, short: string, color: string}> */
    public static function categories(): array {
        return [
            self::CATEGORY_ZAYIF => [
                'label' => 'Zayıf',
                'short' => 'VKİ < 18,5',
                'color' => '#0dcaf0',
            ],
            self::CATEGORY_NORMAL => [
                'label' => 'Normal',
                'short' => '18,5 – 24,9',
                'color' => '#198754',
            ],
            self::CATEGORY_OBEZ => [
                'label' => 'Obez',
                'short' => '25,0 – 39,9',
                'color' => '#fd7e14',
            ],
            self::CATEGORY_MORBID => [
                'label' => 'Morbid obez',
                'short' => 'VKİ ≥ 40',
                'color' => '#dc3545',
            ],
        ];
    }

    /** @return list<string> */
    public static function categoryKeys(): array {
        return array_keys(self::categories());
    }

    public static function normalizeBoyCm($boy): ?float {
        $b = (float) $boy;
        if ($b <= 0) {
            return null;
        }
        if ($b > 0 && $b <= 3) {
            $b *= 100;
        }
        if ($b < 50 || $b > 250) {
            return null;
        }

        return $b;
    }

    public static function normalizeKiloKg($kilo): ?float {
        $k = (float) $kilo;
        if ($k <= 0 || $k > 500) {
            return null;
        }

        return $k;
    }

    public static function calculateBmi(float $kg, float $cm): float {
        $m = $cm / 100.0;

        return round($kg / ($m * $m), 1);
    }

    public static function classifyBmi(float $bmi): string {
        if ($bmi < 18.5) {
            return self::CATEGORY_ZAYIF;
        }
        if ($bmi < 25.0) {
            return self::CATEGORY_NORMAL;
        }
        if ($bmi < 40.0) {
            return self::CATEGORY_OBEZ;
        }

        return self::CATEGORY_MORBID;
    }

    public static function genderKey($cinsiyet): string {
        $n = CinsiyetHelper::normalize($cinsiyet);
        if ($n === CinsiyetHelper::ERKEK) {
            return CinsiyetHelper::ERKEK;
        }
        if ($n === CinsiyetHelper::KADIN) {
            return CinsiyetHelper::KADIN;
        }

        return '?';
    }

    public static function genderLabel(string $key): string {
        return CinsiyetHelper::label($key, 'Belirtilmemiş');
    }

    /** @return list<string> */
    public static function ageBandKeys(): array {
        return AgeBandHelper::keys();
    }

    public static function ageBand(?string $dogumYmd): ?string {
        return AgeBandHelper::bandFromBirthDate($dogumYmd);
    }

    /** @return array<string, int> */
    public static function emptyCategoryCounts(): array {
        $out = [];
        foreach (self::categoryKeys() as $key) {
            $out[$key] = 0;
        }

        return $out;
    }
}
