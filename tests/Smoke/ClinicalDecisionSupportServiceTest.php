<?php
declare(strict_types=1);

use App\Core\Database;
use App\Helpers\ClinicalDecisionSupportHelper;
use App\Services\Clinical\ClinicalDecisionSupportService;
use PHPUnit\Framework\TestCase;

final class ClinicalDecisionSupportServiceTest extends TestCase
{
    public function testCountOverdueHighRiskNoSqlError(): void
    {
        if (!ClinicalDecisionSupportHelper::enabled()) {
            self::markTestSkipped('Klinik karar desteği kapalı veya tablolar eksik.');
        }
        $db = Database::getInstance();
        $service = new ClinicalDecisionSupportService();
        $count = $service->countOverdueHighRisk();
        self::assertGreaterThanOrEqual(0, $count);
        self::assertSame('', (string) $db->getErrorMsg(), 'countOverdueHighRisk SQL hatası: ' . $db->getErrorMsg());
    }

    public function testListOverdueHighRiskNoSqlError(): void
    {
        if (!ClinicalDecisionSupportHelper::enabled()) {
            self::markTestSkipped('Klinik karar desteği kapalı veya tablolar eksik.');
        }
        $db = Database::getInstance();
        $service = new ClinicalDecisionSupportService();
        $rows = $service->listOverdueHighRisk(5, 0);
        self::assertIsArray($rows);
        self::assertLessThanOrEqual(5, count($rows));
        self::assertSame('', (string) $db->getErrorMsg(), 'listOverdueHighRisk SQL hatası: ' . $db->getErrorMsg());
    }
}
