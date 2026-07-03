<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * MNA-SF (Mini Nutritional Assessment Short Form) — 6 madde, max 14 puan.
 */
final class MnaScaleHelper
{
    /** @var list<string> */
    public const SCORE_KEYS = ['besin_alimi', 'kilo_kaybi', 'mobilite', 'stres_hastalik', 'noropsikolojik'];

    public const OLcum_BMI = 'bmi';
    public const OLcum_BALDIR = 'baldirc_cevresi';

    /**
     * @return array<string, array{label: string, intro: string, options: array<int, string>}>
     */
    public static function getFieldDefinitions(): array
    {
        return [
            'besin_alimi' => [
                'label' => 'Besin alımı',
                'intro' => 'Son 3 ayda iştah, sindirim, çiğneme/yutma sorunları nedeniyle besin alımında azalma',
                'options' => [
                    0 => 'Ciddi azalma',
                    1 => 'Orta düzeyde azalma',
                    2 => 'Azalma yok',
                ],
            ],
            'kilo_kaybi' => [
                'label' => 'İstemsiz kilo kaybı',
                'intro' => 'Son 3 ayda istemsiz kilo kaybı',
                'options' => [
                    0 => '3 kg üzeri kayıp',
                    1 => 'Bilmiyor',
                    2 => '1–3 kg arası kayıp',
                    3 => 'Kilo kaybı yok',
                ],
            ],
            'mobilite' => [
                'label' => 'Mobilite',
                'intro' => 'Hareket kabiliyeti',
                'options' => [
                    0 => 'Yatağa veya sandalyeye bağımlı',
                    1 => 'Yataktan/sandalyeden kalkar ama dışarı çıkmaz',
                    2 => 'Dışarı çıkar',
                ],
            ],
            'stres_hastalik' => [
                'label' => 'Stres / akut hastalık',
                'intro' => 'Son 3 ayda psikolojik stres veya akut hastalık',
                'options' => [
                    0 => 'Evet',
                    2 => 'Hayır',
                ],
            ],
            'noropsikolojik' => [
                'label' => 'Nöropsikolojik sorunlar',
                'intro' => 'Demans veya depresyon',
                'options' => [
                    0 => 'Ciddi demans veya depresyon',
                    1 => 'Hafif demans',
                    2 => 'Psikolojik sorun yok',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, options: array<int, string>}>
     */
    public static function getBmiFieldDefinition(): array
    {
        return [
            'label' => 'VKİ (BMI)',
            'intro' => 'Boy ve kilo ile hesaplanan vücut kitle indeksi',
            'options' => [
                0 => 'VKİ < 19',
                1 => 'VKİ 19 – < 21',
                2 => 'VKİ 21 – < 23',
                3 => 'VKİ ≥ 23',
            ],
        ];
    }

    /**
     * @return array{label: string, options: array<int, string>}
     */
    public static function getCalfFieldDefinition(): array
    {
        return [
            'label' => 'Baldır çevresi',
            'intro' => 'VKİ ölçülemediğinde baldır çevresi (cm)',
            'options' => [
                0 => '< 31 cm',
                3 => '≥ 31 cm',
            ],
        ];
    }

    public static function bmiScoreFromValue(?float $bmi): ?int
    {
        if ($bmi === null || $bmi <= 0) {
            return null;
        }
        if ($bmi < 19.0) {
            return 0;
        }
        if ($bmi < 21.0) {
            return 1;
        }
        if ($bmi < 23.0) {
            return 2;
        }

        return 3;
    }

    public static function calfScoreFromCm(?float $cm): ?int
    {
        if ($cm === null || $cm <= 0) {
            return null;
        }

        return $cm < 31.0 ? 0 : 3;
    }

    /**
     * @param array<string, mixed> $scores
     */
    public static function calculateTotal(array $scores, int $bmiSkor): int
    {
        $total = $bmiSkor;
        foreach (self::SCORE_KEYS as $key) {
            $total += (int) ($scores[$key] ?? 0);
        }

        return $total;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, int>
     */
    public static function sanitizeScores(array $input): array
    {
        $defs = self::getFieldDefinitions();
        $out = [];
        foreach (self::SCORE_KEYS as $key) {
            $opts = $defs[$key]['options'] ?? [];
            $allowed = array_map('intval', array_keys($opts));
            $val = isset($input[$key]) ? (int) $input[$key] : $allowed[0];
            if (!in_array($val, $allowed, true)) {
                $val = $allowed[0];
            }
            $out[$key] = $val;
        }

        return $out;
    }

    public static function sanitizeBmiScore(mixed $raw, string $olcumTipi): int
    {
        if ($olcumTipi === self::OLcum_BALDIR) {
            $val = (int) $raw;
            return in_array($val, [0, 3], true) ? $val : 0;
        }
        $val = (int) $raw;

        return max(0, min(3, $val));
    }

    public static function sanitizeOlcumTipi(mixed $raw): string
    {
        $v = trim((string) $raw);

        return $v === self::OLcum_BALDIR ? self::OLcum_BALDIR : self::OLcum_BMI;
    }

    /**
     * @return array{label: string, badgeClass: string}
     */
    public static function resolveStatus(int $total): array
    {
        if ($total >= 12) {
            return ['label' => 'Normal beslenme', 'badgeClass' => 'bg-success-subtle text-success border'];
        }
        if ($total >= 8) {
            return ['label' => 'Malnütrisyon riski', 'badgeClass' => 'bg-warning text-dark'];
        }

        return ['label' => 'Malnütrisyon', 'badgeClass' => 'bg-danger'];
    }

    /**
     * Hasta boy/kilo ile önerilen BMI skoru.
     *
     * @return array{bmi: ?float, score: ?int, boy: ?float, kilo: ?float}
     */
    public static function suggestBmiFromPatient(object $patient): array
    {
        $boy = BmiHelper::normalizeBoyCm($patient->boy ?? null);
        $kilo = BmiHelper::normalizeKiloKg($patient->kilo ?? null);
        if ($boy === null || $kilo === null) {
            return ['bmi' => null, 'score' => null, 'boy' => $boy, 'kilo' => $kilo];
        }
        $bmi = BmiHelper::calculateBmi($kilo, $boy);

        return [
            'bmi' => $bmi,
            'score' => self::bmiScoreFromValue($bmi),
            'boy' => $boy,
            'kilo' => $kilo,
        ];
    }
}
