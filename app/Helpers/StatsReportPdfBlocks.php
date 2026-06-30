<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\Stats;

/**
 * İstatistik rapor sayfası — kart / panel bazlı PDF blokları.
 */
final class StatsReportPdfBlocks {
    /** @var array<string, list<string>> */
    private const EXTRA_BLOCKS = [
        'overview' => ['summary_toplam', 'summary_aktif', 'summary_erkek', 'summary_kadin', 'mahalle', 'kayit_yili'],
        'operationsPulse' => [
            'kpi_gun7', 'kpi_gun30', 'kpi_bu_ay', 'kpi_pending', 'kpi_visits_year', 'kpi_waiting',
            'visit_months', 'erapor_pool', 'ilce_ranking', 'bagimlilik', 'brans_erapor',
        ],
        'barthel' => ['ortalama', 'dagilim'],
    ];

    /**
     * @param array<string, mixed> $query
     */
    public static function supports(string $action, string $block, array $query = []): bool {
        $block = trim($block);
        if ($block === '') {
            return false;
        }
        if ($block === 'main') {
            if (str_starts_with($action, 'xTab_')) {
                return false;
            }

            return StatsReportPdfExports::tryExport($action, $query) !== null;
        }
        if (str_starts_with($action, 'xTab_')) {
            return $block === 'matrix';
        }
        if (StatsReportPdfBlockExports::supports($action, $block)) {
            return true;
        }

        return in_array($block, self::blocksForAction($action), true);
    }

    /**
     * @return list<string>
     */
    public static function blocksForAction(string $action): array {
        $action = trim($action);
        if (str_starts_with($action, 'xTab_')) {
            return ['matrix'];
        }
        $blocks = ['main'];
        if (isset(self::EXTRA_BLOCKS[$action])) {
            foreach (self::EXTRA_BLOCKS[$action] as $b) {
                if (!in_array($b, $blocks, true)) {
                    $blocks[] = $b;
                }
            }
        }
        foreach (StatsReportPdfBlockExports::blocksForAction($action) as $b) {
            if (!in_array($b, $blocks, true)) {
                $blocks[] = $b;
            }
        }

        return $blocks;
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>|null
     */
    public static function export(string $action, string $block, array $query): ?array {
        $action = trim($action);
        $block = trim($block);
        if ($action === '' || $block === '') {
            return null;
        }

        if ($block === 'main') {
            if (str_starts_with($action, 'xTab_')) {
                return null;
            }

            return StatsReportPdfExports::tryExport($action, $query);
        }

        if (str_starts_with($action, 'xTab_')) {
            if ($block !== 'matrix') {
                return null;
            }
            $tabId = StatsCrossTabRegistry::idFromAction($action);
            if ($tabId === null) {
                return null;
            }
            $months = StatsCrossTabBuilder::normalizePeriodMonths((int) ($query['months'] ?? 12));
            $report = StatsCrossTabBuilder::build(new Stats(), $tabId, ['months' => $months]);

            return StatsCrossTabPdfHelper::buildPayload($report, $months);
        }

        $m = new Stats();

        $special = match ($action) {
            'overview' => self::exportOverviewBlock($m, $block),
            'operationsPulse' => self::exportOperationsPulseBlock($m, $block),
            'barthel' => self::exportBarthelBlock($m, $block),
            default => null,
        };
        if ($special !== null) {
            return $special;
        }

        return StatsReportPdfBlockExports::tryExport($action, $block, $query);
    }

    private static function kpiPayload(string $pageTitle, string $label, string $value, string $action, string $block): array {
        $slug = $action . '_' . $block;

        return StatsReportPdfFormatHelper::payload(
            $pageTitle . ' — ' . $label,
            ['Gösterge', 'Değer'],
            [[$label, $value]],
            $label,
            StatsReportPdfFormatHelper::filename($slug)
        );
    }

    private static function exportOverviewBlock(Stats $m, string $block): ?array {
        $summary = $m->getGeneralSummary() ?: (object) ['toplam' => 0, 'aktif' => 0, 'pasif' => 0, 'erkek' => 0, 'kadin' => 0];
        $title = 'Genel özet';

        return match ($block) {
            'summary_toplam' => self::kpiPayload($title, 'Toplam kayıt', (string) (int) ($summary->toplam ?? 0), 'overview', $block),
            'summary_aktif' => self::kpiPayload($title, 'Aktif hasta', (string) (int) ($summary->aktif ?? 0), 'overview', $block),
            'summary_erkek' => self::kpiPayload($title, 'Erkek hasta', (string) (int) ($summary->erkek ?? 0), 'overview', $block),
            'summary_kadin' => self::kpiPayload($title, 'Kadın hasta', (string) (int) ($summary->kadin ?? 0), 'overview', $block),
            'mahalle' => StatsReportPdfFormatHelper::payload(
                $title . ' — Mahalle dağılımı',
                ['İlçe / Mahalle', 'Erkek', 'Kadın', 'Toplam'],
                self::mahalleRows($m),
                'Mahalle dağılımı',
                StatsReportPdfFormatHelper::filename('overview_mahalle'),
                ['*', 40, 40, 44]
            ),
            'kayit_yili' => StatsReportPdfFormatHelper::payload(
                $title . ' — Yıllara göre kayıt',
                ['Yıl', 'Erkek', 'Kadın', 'Toplam'],
                self::kayitYiliRows($m),
                'Yıllara göre kayıt',
                StatsReportPdfFormatHelper::filename('overview_kayit_yili'),
                [44, 40, 40, 44]
            ),
            default => null,
        };
    }

    /** @return list<list<string>> */
    private static function mahalleRows(Stats $m): array {
        $rows = [];
        foreach ($m->getMahalleStats() ?: [] as $r) {
            $rows[] = [
                trim((string) ($r->ilce_adi ?? $r->ilce ?? '') . ' / ' . (string) ($r->mahalle_adi ?? $r->mahalle ?? '')),
                (string) (int) ($r->erkek_sayisi ?? $r->erkek ?? 0),
                (string) (int) ($r->kadin_sayisi ?? $r->kadin ?? 0),
                (string) (int) ($r->toplam_hasta ?? $r->toplam ?? 0),
            ];
        }

        return $rows;
    }

    /** @return list<list<string>> */
    private static function kayitYiliRows(Stats $m): array {
        $rows = [];
        foreach ($m->getKayitYiliStats() ?: [] as $r) {
            $rows[] = [
                (string) ($r->kayityili ?? $r->yil ?? ''),
                (string) (int) ($r->erkek_sayisi ?? $r->erkek ?? 0),
                (string) (int) ($r->kadin_sayisi ?? $r->kadin ?? 0),
                (string) (int) ($r->toplam_sayi ?? $r->adet ?? $r->toplam ?? 0),
            ];
        }

        return $rows;
    }

    private static function exportOperationsPulseBlock(Stats $m, string $block): ?array {
        $title = 'Operasyonel nabız';
        $trend = $m->getVisitTrendStats();
        $erapor = $m->getEraporPoolStats();
        $bagimlilikEtiket = ['1' => 'Bağımsız', '2' => 'Yarı bağımlı', '3' => 'Tam bağımlı'];

        return match ($block) {
            'kpi_gun7' => self::kpiPayload($title, '7 gün izlem', (string) (int) ($trend->gun7 ?? 0), 'operationsPulse', $block),
            'kpi_gun30' => self::kpiPayload($title, '30 gün izlem', (string) (int) ($trend->gun30 ?? 0), 'operationsPulse', $block),
            'kpi_bu_ay' => self::kpiPayload($title, 'Bu ay izlem', (string) (int) ($trend->bu_ay ?? 0), 'operationsPulse', $block),
            'kpi_pending' => self::kpiPayload($title, 'Bu ay bekleyen', (string) $m->getVisitPendingThisMonth(), 'operationsPulse', $block),
            'kpi_visits_year' => self::kpiPayload($title, (int) date('Y') . ' yılı izlem', (string) $m->getCompletedVisitsThisYear(), 'operationsPulse', $block),
            'kpi_waiting' => self::kpiPayload($title, 'Bekleyen hasta (-3)', (string) $m->getWaitingPatientCount(), 'operationsPulse', $block),
            'visit_months' => StatsReportPdfFormatHelper::payload(
                $title . ' — Son 12 ay izlem',
                ['Dönem', 'Adet'],
                self::visitMonthRows($m),
                'Tamamlanmış izlem (aylık)',
                StatsReportPdfFormatHelper::filename('operationsPulse_visit_months')
            ),
            'erapor_pool' => StatsReportPdfFormatHelper::payload(
                $title . ' — e-Rapor havuzu',
                ['Gösterge', 'Adet'],
                [
                    ['Toplam', (string) (int) ($erapor->toplam ?? 0)],
                    ['Sistemde eşleşen', (string) (int) ($erapor->sistemde ?? 0)],
                    ['Eşleşmeyen', (string) (int) ($erapor->disaridan ?? 0)],
                    ['Pansumanlı aktif hasta', (string) $m->getPansumanActiveCount()],
                    ['Aktif hasta planlı izlem', (string) $m->getPlannedOpenCount()],
                ],
                'e-Rapor havuzu özeti',
                StatsReportPdfFormatHelper::filename('operationsPulse_erapor_pool')
            ),
            'ilce_ranking' => StatsReportPdfFormatHelper::payload(
                $title . ' — İlçe sıralaması',
                ['İlçe', 'Adet'],
                StatsReportPdfFormatHelper::rowsFromItems($m->getIlceActiveRanking(25), ['ilce_adi', 'adet']),
                'İlçe bazlı aktif hasta',
                StatsReportPdfFormatHelper::filename('operationsPulse_ilce')
            ),
            'bagimlilik' => StatsReportPdfFormatHelper::payload(
                $title . ' — Bağımlılık',
                ['Durum', 'Adet'],
                self::bagimlilikRows($m->getBagimlilikActiveBreakdown(), $bagimlilikEtiket),
                'Bağımlılık (aktif)',
                StatsReportPdfFormatHelper::filename('operationsPulse_bagimlilik')
            ),
            'brans_erapor' => StatsReportPdfFormatHelper::payload(
                $title . ' — e-Rapor branş',
                ['Branş', 'Adet'],
                self::bransRows($m->getEraporBransDistribution()),
                'e-Rapor branş dağılımı',
                StatsReportPdfFormatHelper::filename('operationsPulse_brans')
            ),
            default => null,
        };
    }

    /** @return list<list<string>> */
    private static function visitMonthRows(Stats $m): array {
        $rows = [];
        foreach ($m->getCompletedVisitsByMonth(12) as $vm) {
            $rows[] = [(string) ($vm->ym ?? ''), (string) (int) ($vm->n ?? 0)];
        }

        return $rows;
    }

    /**
     * @param list<object> $items
     * @param array<string, string> $labels
     * @return list<list<string>>
     */
    private static function bagimlilikRows(array $items, array $labels): array {
        $rows = [];
        foreach ($items as $b) {
            $kod = trim((string) ($b->kod ?? ''));
            $label = $labels[$kod] ?? ($kod === '' || $kod === '—' ? 'Belirtilmemiş' : $kod);
            $rows[] = [$label, (string) (int) ($b->adet ?? 0)];
        }

        return $rows;
    }

    /** @param list<object> $items @return list<list<string>> */
    private static function bransRows(array $items): array {
        $rows = [];
        foreach ($items as $b) {
            $rows[] = [
                (string) ($b->brans ?? ''),
                (string) (int) ($b->count ?? $b->adet ?? 0),
            ];
        }

        return $rows;
    }

    private static function exportBarthelBlock(Stats $m, string $block): ?array {
        $b = $m->getBarthelDistribution();
        $title = 'Barthel dağılımı';

        return match ($block) {
            'ortalama' => StatsReportPdfFormatHelper::payload(
                $title . ' — Özet',
                ['Gösterge', 'Değer'],
                [
                    ['Ortalama skor', (string) ($b->ortalama_skor ?? 0)],
                    ['Aktif hasta', (string) (int) ($b->toplam_hasta ?? 0)],
                ],
                'Ortalama skor kartı',
                StatsReportPdfFormatHelper::filename('barthel_ortalama')
            ),
            'dagilim' => StatsReportPdfFormatHelper::payload(
                $title . ' — Skor grupları',
                ['Grup', 'Adet'],
                [
                    ['Tam bağımlı (0–20)', (string) (int) ($b->g_0_20 ?? 0)],
                    ['Ağır (21–61)', (string) (int) ($b->g_21_61 ?? 0)],
                    ['Orta (62–90)', (string) (int) ($b->g_62_90 ?? 0)],
                    ['Hafif (91–99)', (string) (int) ($b->g_91_99 ?? 0)],
                    ['Tam bağımsız (100)', (string) (int) ($b->g_100 ?? 0)],
                ],
                'Skor grupları',
                StatsReportPdfFormatHelper::filename('barthel_dagilim')
            ),
            default => null,
        };
    }
}
