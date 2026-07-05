<?php
declare(strict_types=1);

use App\Helpers\UsbsComplianceHelper;
use PHPUnit\Framework\TestCase;

final class UsbsComplianceKpisTest extends TestCase
{
    public function testComplianceKpisStructure(): void
    {
        $kpis = UsbsComplianceHelper::complianceKpis(null);
        foreach ([
            'patients_missing',
            'bildirim_pending',
            'bildirim_sent',
            'bildirim_failed',
            'bildirim_skipped',
            'visits_missing_ref',
        ] as $key) {
            self::assertArrayHasKey($key, $kpis);
            self::assertIsInt($kpis[$key]);
            self::assertGreaterThanOrEqual(0, $kpis[$key]);
        }
    }

    public function testBildirimDurumLabel(): void
    {
        self::assertSame('Bekliyor', UsbsComplianceHelper::bildirimDurumLabel('pending'));
        self::assertSame('—', UsbsComplianceHelper::bildirimDurumLabel(''));
    }
}
