<?php
declare(strict_types=1);

use App\Helpers\SmartPlanningHelper;
use PHPUnit\Framework\TestCase;

final class SmartPlanningMultiDayTest extends TestCase
{
    public function testMultiDayRouteAnalysisEmpty(): void
    {
        $alerts = SmartPlanningHelper::multiDayRouteAnalysis([]);
        self::assertSame([], $alerts);
    }

    public function testMultiDayRouteAnalysisDetectsHighKm(): void
    {
        $data = [
            '2026-07-01' => [
                1 => ['hastalar' => [1, 2, 3], 'toplam_km' => 75.5],
            ],
            '2026-07-02' => [
                1 => ['hastalar' => [1], 'toplam_km' => 20.0],
            ],
        ];
        $alerts = SmartPlanningHelper::multiDayRouteAnalysis($data, 60.0);
        self::assertNotEmpty($alerts);
        $warnings = array_filter($alerts, static fn ($a) => ($a['tip'] ?? '') === 'warning');
        self::assertNotEmpty($warnings);
    }

    public function testMultiDayRouteAnalysisSummaryInfo(): void
    {
        $data = [
            '2026-07-01' => [1 => ['hastalar' => [1, 2], 'toplam_km' => 10.0]],
            '2026-07-02' => [1 => ['hastalar' => [1], 'toplam_km' => 5.0]],
        ];
        $alerts = SmartPlanningHelper::multiDayRouteAnalysis($data);
        $infos = array_filter($alerts, static fn ($a) => ($a['tip'] ?? '') === 'info');
        self::assertNotEmpty($infos);
    }
}
