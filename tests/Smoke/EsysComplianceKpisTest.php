<?php
declare(strict_types=1);

use App\Helpers\EsysComplianceHelper;
use PHPUnit\Framework\TestCase;

final class EsysComplianceKpisTest extends TestCase
{
    public function testValidateImportBundleEmpty(): void
    {
        $result = EsysComplianceHelper::validateImportBundle([]);
        self::assertTrue($result['ok']);
        self::assertSame(0, $result['counts']['invalid']);
    }

    public function testValidateImportBundleInvalidItem(): void
    {
        $result = EsysComplianceHelper::validateImportBundle(['patients' => ['not-array']]);
        self::assertFalse($result['ok']);
    }

    public function testComplianceKpisStructure(): void
    {
        $kpis = EsysComplianceHelper::complianceKpis(null);
        self::assertArrayHasKey('patients_missing', $kpis);
        self::assertArrayHasKey('visits_missing', $kpis);
        self::assertArrayHasKey('plans_missing', $kpis);
        foreach (['patients_missing', 'visits_missing', 'plans_missing'] as $key) {
            self::assertIsInt($kpis[$key]);
            self::assertGreaterThanOrEqual(0, $kpis[$key]);
        }
    }

    public function testComplianceKpisPlansQueryMatchesDirectSql(): void
    {
        if (!EsysComplianceHelper::columnsReady()) {
            self::markTestSkipped('ESYS referans sütunları kurulu değil.');
        }
        $db = \App\Core\Database::getInstance();
        $expected = (int) $db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__pizlemler WHERE COALESCE(durum, 0) = 0'
                . ' AND (TRIM(COALESCE(esys_plan_ref, \'\')) = \'\')',
            []
        );
        $kpis = EsysComplianceHelper::complianceKpis(null);
        self::assertSame($expected, $kpis['plans_missing'], 'plans_missing sessiz hata ile sıfırlanmış olabilir');
    }
}
