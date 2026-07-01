<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * İTAKİ II düşme riski ölçeği — parametre tanımları ve skor hesabı (18+ yaş).
 */
final class ItakiScaleHelper
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
                'intro' => '60 yaş altı için bu gruptan puan alınmaz.',
                'options' => [
                    'yas_60_69' => ['label' => '60–69 yaş', 'score' => 1],
                    'yas_70_79' => ['label' => '70–79 yaş', 'score' => 2],
                    'yas_80_ust' => ['label' => '80 yaş ve üstü', 'score' => 3],
                ],
            ],
            'bilinc' => [
                'label' => 'Bilinç durumu',
                'type' => 'single',
                'intro' => 'Mevcut bilinç düzeyi',
                'options' => [
                    'bilinc_kapali' => ['label' => 'Bilinci kapalı', 'score' => 1],
                    'bilinc_bozuk' => ['label' => 'Bilinç bozukluğu var (konfüze, letarjik vb.)', 'score' => 2],
                ],
            ],
            'dusme_oykusu' => [
                'label' => 'Düşme hikayesi',
                'type' => 'toggle',
                'intro' => 'Son 6 ay içinde düşme öyküsü',
                'options' => [
                    'dusme_6ay' => ['label' => 'Son 6 ay içerisinde düşme öyküsü var', 'score' => 3],
                ],
            ],
            'komorbidite' => [
                'label' => 'Hastalıklar / komorbiditeler',
                'type' => 'single',
                'intro' => 'Hipotansiyon, vertigo, SVH, Parkinson, uzuv kaybı, nöbet, artrit, osteoporoz, kırıklar vb.',
                'options' => [
                    'hastalik_1_2' => ['label' => 'Hastalıklardan en fazla ikisi bulunmaktadır', 'score' => 1],
                    'hastalik_3_ust' => ['label' => 'Hastalıklardan üç veya daha fazlası bulunmaktadır', 'score' => 2],
                ],
            ],
            'hareket' => [
                'label' => 'Hareket kabiliyeti',
                'type' => 'multi',
                'intro' => 'Uygun olan tüm maddeleri işaretleyin.',
                'options' => [
                    'hareket_destek' => ['label' => 'Ayakta/yürürken fiziksel desteğe ihtiyacı var', 'score' => 5],
                    'hareket_denge' => ['label' => 'Ayakta/yürürken denge bozukluğu var', 'score' => 10],
                    'hareket_basdonmesi' => ['label' => 'Baş dönmesi var', 'score' => 2],
                ],
            ],
            'bosaltim' => [
                'label' => 'Boşaltım ihtiyacı',
                'type' => 'toggle',
                'intro' => 'Üriner veya fekal kontinans',
                'options' => [
                    'bosaltim_kontinans' => ['label' => 'Üriner/fekal kontinans bozukluğu var', 'score' => 1],
                ],
            ],
            'gorus' => [
                'label' => 'Görme durumu',
                'type' => 'single',
                'intro' => 'Görme yeteneği değerlendirmesi',
                'options' => [
                    'gorus_bozuk' => ['label' => 'Görme bozukluğu var (katarakt, gözlük kullanımı vb.)', 'score' => 2],
                    'gorus_ileri' => ['label' => 'İleri derecede görme engeli var', 'score' => 10],
                ],
            ],
            'ilac' => [
                'label' => 'İlaç kullanımı',
                'type' => 'single',
                'intro' => 'Son 1 hafta içindeki riskli ilaç ve toplam ilaç sayısı',
                'options' => [
                    'ilac_4_ust' => ['label' => "4'den fazla ilaç kullanımı var", 'score' => 2],
                    'ilac_riskli_2' => ['label' => 'Son 1 hafta içinde riskli en çok 2 ilaç kullanımı var', 'score' => 2],
                    'ilac_riskli_3_ust' => ['label' => 'Son 1 hafta içinde riskli 3 ve daha fazla ilaç kullanımı var', 'score' => 3],
                ],
            ],
            'ekipman' => [
                'label' => 'Ekipman kullanımı',
                'type' => 'single',
                'intro' => 'Hastanın hareketini kısıtlayan bakım ekipmanları',
                'options' => [
                    'ekipman_1_2' => ['label' => 'Hastaya bağlı 1–2 bakım ekipmanı var', 'score' => 1],
                    'ekipman_3_ust' => ['label' => 'Hastaya bağlı 3 ve üstü bakım ekipmanı var', 'score' => 2],
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
        if ($age >= 80) {
            return 'yas_80_ust';
        }
        if ($age >= 70) {
            return 'yas_70_79';
        }
        if ($age >= 60) {
            return 'yas_60_69';
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
