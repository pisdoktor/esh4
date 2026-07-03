<?php
declare(strict_types=1);

use App\Helpers\EsysBridgeHelper;
use PHPUnit\Framework\TestCase;

final class EsysBridgeHelperTest extends TestCase
{
    public function testValidateImportBundleAcceptsEsysDirection(): void
    {
        $r = EsysBridgeHelper::validateImportBundle([
            'bundle_version' => 1,
            'direction' => 'esys_to_esh',
            'patients' => [],
        ]);
        self::assertTrue($r['ok']);
        self::assertSame('esys_to_esh', $r['direction']);
    }

    public function testValidateImportBundleAcceptsAhbsDirection(): void
    {
        $r = EsysBridgeHelper::validateImportBundle([
            'bundle_version' => 1,
            'direction' => 'ahbs_to_esh',
        ]);
        self::assertTrue($r['ok']);
    }

    public function testValidateImportBundleRejectsBadVersion(): void
    {
        $r = EsysBridgeHelper::validateImportBundle(['bundle_version' => 9, 'direction' => 'esys_to_esh']);
        self::assertFalse($r['ok']);
    }

    public function testExtractPatientRefs(): void
    {
        $refs = EsysBridgeHelper::extractPatientRefs([
            'esh_id' => 1,
            'esys_hasta_ref' => ' H-1 ',
            'esys_basvuru_ref' => 'B-2',
        ]);
        self::assertSame('H-1', $refs['esys_hasta_ref']);
        self::assertSame('B-2', $refs['esys_basvuru_ref']);
    }

    public function testParseUploadedJson(): void
    {
        $parsed = EsysBridgeHelper::parseUploadedJson('{"bundle_version":1,"direction":"esys_to_esh"}');
        self::assertIsArray($parsed);
        self::assertSame(1, $parsed['bundle_version']);
    }

    public function testApiConfiguredReturnsBool(): void
    {
        self::assertIsBool(EsysBridgeHelper::apiConfigured());
    }
}
