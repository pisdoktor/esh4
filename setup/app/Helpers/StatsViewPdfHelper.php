<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * İstatistik rapor view — PDF/Excel dışa aktarma düğmeleri ve kart başlığı kısayolları.
 *
 * {@see renderPdfButton()} stats_block_pdf_btn partial üzerinden PDF + Excel düğmelerini render eder.
 */
final class StatsViewPdfHelper {
    private static function partial(string $name): string {
        return dirname(__DIR__, 2) . '/views/admin/stats/partials/' . $name . '.php';
    }

    public static function renderPdfButton(string $block, ?string $title = null): void {
        if (!AuthHelper::sessionIsAdmin()) {
            return;
        }
        $eshStatsPdfBlock = trim($block);
        if ($eshStatsPdfBlock === '') {
            return;
        }
        $eshStatsPdfTitle = $title;
        require self::partial('stats_block_pdf_btn');
    }

    public static function renderCardHeader(
        string $titleHtml,
        string $block = 'main',
        string $headingTag = 'h6',
        ?string $headerClass = null,
        ?string $pdfTitle = null
    ): void {
        $eshStatsCardTitle = $titleHtml;
        $eshStatsPdfBlock = $block;
        $eshStatsCardHeadingTag = $headingTag;
        $eshStatsCardHeaderClass = $headerClass ?? 'card-header bg-white py-3';
        $eshStatsPdfTitle = $pdfTitle;
        require self::partial('stats_card_header');
    }
}
