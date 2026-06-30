<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\Stats;

/**
 * İstatistik hub kartı → pdfMake JSON yükü.
 */
final class StatsReportPdfService {
    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    public static function build(string $action, array $query): array {
        $action = trim($action);
        if ($action === '') {
            throw new \InvalidArgumentException('Rapor seçilmedi.');
        }

        $card = StatsNavHelper::hubCardByAction($action);
        if ($card === null) {
            throw new \InvalidArgumentException('Geçersiz istatistik raporu.');
        }

        $block = isset($query['block']) ? trim((string) $query['block']) : '';
        if ($block !== '') {
            if (!StatsReportPdfBlocks::supports($action, $block, $query)) {
                throw new \InvalidArgumentException('Bu kart için PDF tanımlı değil.');
            }
            $payload = StatsReportPdfBlocks::export($action, $block, $query);
            if ($payload === null) {
                throw new \InvalidArgumentException('PDF verisi oluşturulamadı.');
            }

            return $payload;
        }

        if (str_starts_with($action, 'xTab_')) {
            $tabId = substr($action, 5);
            if (!StatsCrossTabRegistry::has($tabId)) {
                throw new \InvalidArgumentException('Geçersiz çapraz tablo.');
            }
            $months = StatsCrossTabBuilder::normalizePeriodMonths((int) ($query['months'] ?? 12));
            $report = StatsCrossTabBuilder::build(new Stats(), $tabId, ['months' => $months]);

            return StatsCrossTabPdfHelper::buildPayload($report, $months);
        }

        $payload = StatsReportPdfExports::tryExport($action, $query);
        if ($payload === null) {
            $payload = StatsReportPdfFormatHelper::stubFromCard(
                $card,
                'Bu rapor için otomatik tablo PDF henüz tanımlı değil; rapor sayfasını açıp ekrandaki veriyi kullanın.'
            );
        }

        return $payload;
    }
}
