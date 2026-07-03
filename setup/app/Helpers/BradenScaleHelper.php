<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Braden bası yarası risk ölçeği — alan tanımları ve skor hesabı.
 */
final class BradenScaleHelper
{
    /** @var list<string> */
    public const SCORE_KEYS = ['duyusal', 'nem', 'aktivite', 'hareket', 'beslenme', 'surtunme'];

    /**
     * @return array<string, array{label: string, min: int, max: int, intro: string, scores: array<int, string>}>
     */
    public static function getFieldDefinitions(): array
    {
        return [
            'duyusal' => [
                'label' => 'Duyusal Algı',
                'min' => 1,
                'max' => 4,
                'intro' => 'Ağrı ve rahatsızlığa yanıt verme yeteneği',
                'scores' => [
                    1 => 'Tamamen kısıtlı — bilinç kaybı veya sedasyon',
                    2 => 'Çok kısıtlı — ağrıya yanıt sınırlı; yarısından fazlası uyarılamıyor',
                    3 => 'Hafif kısıtlı — tek kelime veya jestle yanıt; ihtiyaçları karşılanıyor',
                    4 => 'Hiçbir kısıtlama yok — uyanık, ağrı ve rahatsızlığa yanıt veriyor',
                ],
            ],
            'nem' => [
                'label' => 'Nem',
                'min' => 1,
                'max' => 4,
                'intro' => 'Derinin nem derecesi',
                'scores' => [
                    1 => 'Sürekli ıslak — ter, idrar veya dışkı ile sürekli nemli',
                    2 => 'Çok ıslak — sık sık ıslanma; her değişimde nemli',
                    3 => 'Ara sıra ıslak — günde bir kez ekstra nemlendirme gerekir',
                    4 => 'Nadiren ıslak — deri genelde kuru; rutin değişim yeterli',
                ],
            ],
            'aktivite' => [
                'label' => 'Aktivite',
                'min' => 1,
                'max' => 4,
                'intro' => 'Fiziksel aktivite düzeyi',
                'scores' => [
                    1 => 'Yatakta — yatakta veya tekerlekli sandalyede sürekli',
                    2 => 'Sandalyede — yürüme yeteneği çok sınırlı veya yok',
                    3 => 'Ara sıra yürür — günde en az iki kez dışarı çıkar veya odada yürür',
                    4 => 'Sık yürür — günde en az iki kez dışarı ve odada sık hareket',
                ],
            ],
            'hareket' => [
                'label' => 'Hareket',
                'min' => 1,
                'max' => 4,
                'intro' => 'Vücut pozisyonunu değiştirme yeteneği',
                'scores' => [
                    1 => 'Tamamen immobil — yardım olmadan pozisyon değiştiremez',
                    2 => 'Çok kısıtlı — ara sıra küçük pozisyon değişiklikleri',
                    3 => 'Hafif kısıtlı — bağımsız küçük değişiklikler; tam dönüşte yardım',
                    4 => 'Tamamen hareketli — yardımsız pozisyon değiştirebilir',
                ],
            ],
            'beslenme' => [
                'label' => 'Beslenme',
                'min' => 1,
                'max' => 4,
                'intro' => 'Besin alımı düzeni',
                'scores' => [
                    1 => 'Çok zayıf — nadiren öğünün yarısını yer; protein takviyesi gerekir',
                    2 => 'Muhtemelen yetersiz — öğünlerin yarısını yer; sıvı alımı sınırlı',
                    3 => 'Yeterli — öğünlerin yarısından fazlasını yer; ara sıra takviye',
                    4 => 'Mükemmel — öğünlerin çoğunu yer; takviye gerekmez',
                ],
            ],
            'surtunme' => [
                'label' => 'Sürtünme ve Kesme',
                'min' => 1,
                'max' => 3,
                'intro' => 'Sürtünme ve kesme kuvvetine maruz kalma',
                'scores' => [
                    1 => 'Problem — sık sürtünme/kesme; yatakta kayma, transferde sürüklenme',
                    2 => 'Potansiyel problem — sınırlı hareket; hafif sürtünme riski',
                    3 => 'Görünür problem yok — bağımsız hareket; yeterli kaldırma',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, min: int, max: int, tooltip_html: string}>
     */
    public static function getFormFieldDefinitions(): array
    {
        $out = [];
        foreach (self::getFieldDefinitions() as $key => $row) {
            $out[$key] = [
                'label' => (string) $row['label'],
                'min' => (int) $row['min'],
                'max' => (int) $row['max'],
                'tooltip_html' => self::buildTooltipHtml((string) $row['intro'], $row['scores']),
            ];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $scores
     */
    public static function calculateTotal(array $scores): int
    {
        $total = 0;
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
            $min = (int) ($defs[$key]['min'] ?? 1);
            $max = (int) ($defs[$key]['max'] ?? 4);
            $val = isset($input[$key]) ? (int) $input[$key] : $min;
            $out[$key] = max($min, min($max, $val));
        }

        return $out;
    }

    /**
     * @return array{label: string, badgeClass: string}
     */
    public static function resolveRisk(int $total): array
    {
        if ($total <= 9) {
            return ['label' => 'Çok yüksek risk', 'badgeClass' => 'bg-danger'];
        }
        if ($total <= 12) {
            return ['label' => 'Yüksek risk', 'badgeClass' => 'bg-danger-subtle text-danger border'];
        }
        if ($total <= 14) {
            return ['label' => 'Orta risk', 'badgeClass' => 'bg-warning text-dark'];
        }
        if ($total <= 18) {
            return ['label' => 'Hafif risk', 'badgeClass' => 'bg-info-subtle text-info border'];
        }

        return ['label' => 'Risk yok', 'badgeClass' => 'bg-success-subtle text-success border'];
    }

    /**
     * @param array<int, string> $scoreLines
     */
    public static function buildTooltipHtml(string $intro, array $scoreLines): string
    {
        ksort($scoreLines, SORT_NUMERIC);
        $html = '';
        if ($intro !== '') {
            $html .= '<div class="esh-barthel-tt-intro fw-semibold border-bottom border-secondary border-opacity-50 pb-1 mb-2">'
                . htmlspecialchars($intro, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        $html .= '<div class="esh-barthel-tt-scores">';
        foreach ($scoreLines as $pts => $text) {
            $html .= '<div class="esh-barthel-tt-row d-flex align-items-start gap-2 mb-1">'
                . '<span class="esh-barthel-tt-pts badge rounded-pill bg-light text-dark fw-bold flex-shrink-0">'
                . (int) $pts . ' puan</span>'
                . '<span class="esh-barthel-tt-text">' . htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8') . '</span>'
                . '</div>';
        }
        $html .= '</div>';

        return $html;
    }
}
