<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Barthel İndeksi — fonksiyonel bağımsızlık ölçeği (10 madde, max 100 puan).
 */
final class BarthelScaleHelper
{
    /** @var list<string> */
    public const SCORE_KEYS = [
        'barbeslenme',
        'barbanyo',
        'barbakim',
        'bargiyinme',
        'barbarsak',
        'barmesane',
        'bartuvalet',
        'bartransfer',
        'barmobilite',
        'barmerdiven',
    ];

    /**
     * @return array<string, array{label: string, max: int, tooltip_html: string}>
     */
    public static function getFieldDefinitions(): array
    {
        $raw = [
            'barbeslenme' => [
                'label' => 'Beslenme',
                'max' => 10,
                'intro' => 'Yemek yeme ve içme',
                'scores' => [
                    0 => 'Beslenemez veya sonda/PEG ile beslenir',
                    5 => 'Yardımla: hazırlık, bıçak-çatal veya yedirme',
                    10 => 'Tek başına yiyip içer',
                ],
            ],
            'barbanyo' => [
                'label' => 'Banyo',
                'max' => 5,
                'intro' => 'Banyo veya duş',
                'scores' => [
                    0 => 'Yıkanamaz',
                    5 => 'Küvet/duşa tek başına girer-çıkar',
                ],
            ],
            'barbakim' => [
                'label' => 'Kişisel Bakım',
                'max' => 5,
                'intro' => 'Yüz, saç, diş, tıraş',
                'scores' => [
                    0 => 'Kişisel bakımda yardım gerekir',
                    5 => 'Aynada görünen bölgeleri tek başına yapar',
                ],
            ],
            'bargiyinme' => [
                'label' => 'Giyinme',
                'max' => 10,
                'intro' => 'Giyinip çıkma',
                'scores' => [
                    0 => 'Tamamen bağımlı',
                    5 => 'Yarı bağımsız; çoğu giysiyi giyer-çıkarır',
                    10 => 'Tek başına; düğme, fermuar, kayış dahil',
                ],
            ],
            'barbarsak' => [
                'label' => 'Bağırsak',
                'max' => 10,
                'intro' => 'Dışkı kontrolü',
                'scores' => [
                    0 => 'İnkontinans veya düzenli lavman gerekir',
                    5 => 'Ara sıra kaza',
                    10 => 'Tam kontrol',
                ],
            ],
            'barmesane' => [
                'label' => 'Mesane',
                'max' => 10,
                'intro' => 'İdrar kontrolü',
                'scores' => [
                    0 => 'İnkontinans veya kateter/torba',
                    5 => 'Ara sıra idrar kaçırma',
                    10 => 'Tam kontrol',
                ],
            ],
            'bartuvalet' => [
                'label' => 'Tuvalet',
                'max' => 10,
                'intro' => 'Tuvalet kullanımı',
                'scores' => [
                    0 => 'Tamamen bağımlı',
                    5 => 'Gider; temizlik ve giyinmede yardım',
                    10 => 'Tek başına gider ve temizlenir',
                ],
            ],
            'bartransfer' => [
                'label' => 'Transfer',
                'max' => 15,
                'intro' => 'Yatak ↔ sandalye geçişi',
                'scores' => [
                    0 => 'Transfer yapamaz',
                    5 => 'Çok yardım (1–2 kişi veya tam destek)',
                    10 => 'Az yardım veya sözlü yönlendirme',
                    15 => 'Bağımsız transfer',
                ],
            ],
            'barmobilite' => [
                'label' => 'Mobilite',
                'max' => 15,
                'intro' => 'Yürüme / tekerlekli sandalye',
                'scores' => [
                    0 => 'İmmobil veya sandalyede itilir',
                    5 => 'Tekerlekli sandalyede bağımsız hareket',
                    10 => 'Yardımla veya destekle yürür',
                    15 => 'En az 50 m bağımsız yürür',
                ],
            ],
            'barmerdiven' => [
                'label' => 'Merdiven',
                'max' => 10,
                'intro' => 'Merdiven veya rampa',
                'scores' => [
                    0 => 'Kullanamaz',
                    5 => 'Yardımla iner-çıkar',
                    10 => 'Tek başına güvenle kullanır',
                ],
            ],
        ];

        $out = [];
        foreach ($raw as $key => $row) {
            $out[$key] = [
                'label' => $row['label'],
                'max' => (int) $row['max'],
                'tooltip_html' => self::buildTooltipHtml((string) $row['intro'], $row['scores']),
            ];
        }

        return $out;
    }

    /**
     * @param array<int, string> $scoreLines
     */
    private static function buildTooltipHtml(string $intro, array $scoreLines): string
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

    /**
     * @param array<string, mixed> $input
     * @return array<string, int>
     */
    public static function sanitizeScores(array $input): array
    {
        $defs = self::getFieldDefinitions();
        $out = [];
        foreach (self::SCORE_KEYS as $key) {
            $max = (int) ($defs[$key]['max'] ?? 0);
            $val = isset($input[$key]) ? (int) $input[$key] : 0;
            $out[$key] = max(0, min($max, $val));
        }

        return $out;
    }

    /**
     * @param array<string, int> $scores
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
     * @return array{label: string, badgeClass: string}
     */
    public static function resolveDependencyLevel(int $total): array
    {
        if ($total <= 20) {
            return ['label' => 'Tam Bağımlı', 'badgeClass' => 'bg-danger-subtle text-danger border'];
        }
        if ($total <= 60) {
            return ['label' => 'Ağır Bağımlı', 'badgeClass' => 'bg-warning text-dark'];
        }
        if ($total <= 90) {
            return ['label' => 'Orta Bağımlı', 'badgeClass' => 'bg-info-subtle text-info border'];
        }
        if ($total <= 99) {
            return ['label' => 'Hafif Derecede Bağımlı', 'badgeClass' => 'bg-primary-subtle text-primary border'];
        }

        return ['label' => 'Bağımsız', 'badgeClass' => 'bg-success-subtle text-success border'];
    }
}
