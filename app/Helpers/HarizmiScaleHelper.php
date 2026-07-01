<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Harizmi II düşme riski ölçeği — parametre tanımları ve skor hesabı (0–17 yaş).
 */
final class HarizmiScaleHelper
{
    /** @var array<int, string> */
    public const EVALUATION_REASONS = [
        1 => 'İlk değerlendirme',
        2 => 'Post-op dönem',
        3 => 'Hasta düşmesi',
        4 => 'Bölüm değişikliği',
        5 => 'Durum değişikliği',
    ];

    /**
     * @return array<string, array{label: string, type: string, intro: string, options: array<string, array{label: string, score: int}>}>
     */
    public static function getParameterDefinitions(): array
    {
        return [
            'yas' => [
                'label' => 'Yaş',
                'type' => 'single',
                'intro' => 'Hastanın yaş grubu',
                'options' => [
                    'yas_0_3' => ['label' => '0–3 yaş', 'score' => 4],
                    'yas_4_7' => ['label' => '4–7 yaş', 'score' => 3],
                    'yas_8_11' => ['label' => '8–11 yaş', 'score' => 2],
                    'yas_12_18' => ['label' => '12–18 yaş', 'score' => 1],
                ],
            ],
            'dusme_oykusu' => [
                'label' => 'Düşme öyküsü',
                'type' => 'toggle',
                'intro' => 'Son 6 ay içinde düşme öyküsü',
                'options' => [
                    'dusme_6ay' => ['label' => 'Son 6 ay içerisinde düşme öyküsü var', 'score' => 2],
                ],
            ],
            'hastaliklar' => [
                'label' => 'Hastalıklar',
                'type' => 'single',
                'intro' => 'Epilepsi, mental retardasyon, konvülsiyon, denge bozukluğu, kooperasyon bozukluğu, solunum hastalıkları, senkop/baş dönmesi, ajitasyon vb.',
                'options' => [
                    'hastalik_1_2' => ['label' => 'Hastalıklardan 1 veya 2 tanesi bulunmaktadır', 'score' => 1],
                    'hastalik_3_ust' => ['label' => 'Hastalıklardan 3 veya daha fazlası bulunmaktadır', 'score' => 2],
                ],
            ],
            'gorus' => [
                'label' => 'Görme durumu',
                'type' => 'single',
                'intro' => 'Görme yeteneği değerlendirmesi',
                'options' => [
                    'gorus_zayif' => ['label' => 'Görme durumu zayıf (gözlük kullanıyor vb.)', 'score' => 2],
                    'gorus_ileri' => ['label' => 'İleri derecede görme engeli var', 'score' => 10],
                ],
            ],
            'ilac' => [
                'label' => 'İlaç',
                'type' => 'toggle',
                'intro' => 'Hipnotik, barbitürat, nöroleptik, antidepresan, sedatif, antihipertansif vb.',
                'options' => [
                    'ilac_riskli' => ['label' => 'Son 1 hafta içinde 1 veya daha fazla riskli ilaç kullanımı var', 'score' => 2],
                ],
            ],
            'ekipman' => [
                'label' => 'Ekipman varlığı',
                'type' => 'toggle',
                'intro' => 'IV infüzyon, solunum cihazı, kalıcı kateter, dren, perfüzatör, pacemaker vb.',
                'options' => [
                    'ekipman_2_ust' => ['label' => 'Hastaya bağlı 2 veya daha fazla bakım ekipmanı var', 'score' => 2],
                ],
            ],
            'yurume_denge' => [
                'label' => 'Yürüme ve denge',
                'type' => 'toggle',
                'intro' => 'Yürüteç, koltuk değneği, kişi desteği vb.',
                'options' => [
                    'yurume_destek' => ['label' => 'Ayakta/yürürken fiziksel desteğe ihtiyacı var', 'score' => 10],
                ],
            ],
            'sedasyon' => [
                'label' => 'Sedasyon / anestezi',
                'type' => 'single',
                'intro' => 'Post-operatif veya sedasyon dönemi',
                'options' => [
                    'sedasyon_24' => ['label' => 'Hasta post-op/sedasyon/anestezi ilk 24 saatlik dönemde', 'score' => 3],
                    'sedasyon_48' => ['label' => 'Hasta post-op/sedasyon/anestezi ilk 48 saatlik dönemde', 'score' => 1],
                ],
            ],
            'mental' => [
                'label' => 'Mental durum',
                'type' => 'toggle',
                'intro' => 'Oryantasyon değerlendirmesi',
                'options' => [
                    'mental_bozuk' => ['label' => 'Oryantasyon bozuk (konfüze, disoryante, deliryum vb.)', 'score' => 3],
                ],
            ],
            'yasam' => [
                'label' => 'Yaşam bulguları',
                'type' => 'toggle',
                'intro' => 'Hemodinamik stabilite',
                'options' => [
                    'yasam_unstabil' => ['label' => 'Unstabil', 'score' => 3],
                ],
            ],
            'diger' => [
                'label' => 'Diğer',
                'type' => 'multi',
                'intro' => 'Ek risk faktörleri',
                'options' => [
                    'diger_yatak' => ['label' => 'Hasta uygun yatakta yatırılmıyor', 'score' => 2],
                    'diger_aile_egitim' => ['label' => 'Ailenin düşme riski konusunda eğitim/bilgilendirme ihtiyacı var', 'score' => 2],
                ],
            ],
        ];
    }

    /** @return array<string, int> */
    public static function getOptionScoreMap(): array
    {
        $map = [];
        foreach (self::getParameterDefinitions() as $group) {
            foreach ($group['options'] as $id => $opt) {
                $map[$id] = (int) $opt['score'];
            }
        }

        return $map;
    }

    /** @return array<string, string> */
    public static function getOptionLabelMap(): array
    {
        $map = [];
        foreach (self::getParameterDefinitions() as $group) {
            foreach ($group['options'] as $id => $opt) {
                $map[$id] = (string) $opt['label'];
            }
        }

        return $map;
    }

    public static function suggestAgeOptionId(int $age): ?string
    {
        if ($age <= 3) {
            return 'yas_0_3';
        }
        if ($age <= 7) {
            return 'yas_4_7';
        }
        if ($age <= 11) {
            return 'yas_8_11';
        }
        if ($age <= 18) {
            return 'yas_12_18';
        }

        return null;
    }

    /**
     * @param array<int, string>|array<string, string> $raw
     * @return list<string>
     */
    public static function sanitizeSelections(array $raw): array
    {
        $defs = self::getParameterDefinitions();
        $scoreMap = self::getOptionScoreMap();
        $selected = [];
        foreach ($raw as $id) {
            $id = trim((string) $id);
            if ($id !== '' && isset($scoreMap[$id])) {
                $selected[] = $id;
            }
        }
        $selected = array_values(array_unique($selected));

        $byGroup = [];
        foreach ($selected as $id) {
            foreach ($defs as $groupKey => $group) {
                if (!isset($group['options'][$id])) {
                    continue;
                }
                $byGroup[$groupKey][] = $id;
            }
        }

        $out = [];
        foreach ($defs as $groupKey => $group) {
            $ids = $byGroup[$groupKey] ?? [];
            if ($ids === []) {
                continue;
            }
            if ($group['type'] === 'multi') {
                foreach ($ids as $id) {
                    $out[] = $id;
                }
                continue;
            }
            $out[] = $ids[0];
        }

        return $out;
    }

    /**
     * @param list<string> $selectionIds
     */
    public static function calculateTotal(array $selectionIds): int
    {
        $map = self::getOptionScoreMap();
        $total = 0;
        foreach ($selectionIds as $id) {
            $total += (int) ($map[$id] ?? 0);
        }

        return $total;
    }

    /**
     * @return array{label: string, badgeClass: string}
     */
    public static function resolveRisk(int $total): array
    {
        if ($total >= 10) {
            return ['label' => 'Yüksek risk', 'badgeClass' => 'bg-danger'];
        }

        return ['label' => 'Düşük risk', 'badgeClass' => 'bg-success-subtle text-success border'];
    }

    /**
     * @param list<string> $selectionIds
     * @return list<string>
     */
    public static function selectionLabels(array $selectionIds): array
    {
        $labels = self::getOptionLabelMap();
        $out = [];
        foreach ($selectionIds as $id) {
            if (isset($labels[$id])) {
                $out[] = $labels[$id];
            }
        }

        return $out;
    }

    /**
     * @param list<string> $selectionIds
     */
    public static function encodeSelections(array $selectionIds): string
    {
        return json_encode(array_values($selectionIds), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return list<string>
     */
    public static function decodeSelections(?string $json): array
    {
        if ($json === null || trim($json) === '') {
            return [];
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return [];
        }

        return self::sanitizeSelections($data);
    }

    public static function evaluationReasonLabel(int $code): string
    {
        return self::EVALUATION_REASONS[$code] ?? '—';
    }

    public static function sanitizeEvaluationReason(mixed $raw): int
    {
        $code = (int) $raw;
        if (!isset(self::EVALUATION_REASONS[$code])) {
            return 1;
        }

        return $code;
    }
}
