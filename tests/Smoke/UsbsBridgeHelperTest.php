<?php
declare(strict_types=1);

use App\Helpers\UsbsBridgeHelper;
use PHPUnit\Framework\TestCase;

final class UsbsBridgeHelperTest extends TestCase
{
    public function testValidateImportBundleAcceptsUsbsDirection(): void
    {
        $r = UsbsBridgeHelper::validateImportBundle([
            'bundle_version' => 1,
            'direction' => 'usbs_to_esh',
            'patients' => [],
        ]);
        self::assertTrue($r['ok']);
        self::assertSame('usbs_to_esh', $r['direction']);
    }

    public function testValidateImportBundleRejectsBadDirection(): void
    {
        $r = UsbsBridgeHelper::validateImportBundle([
            'bundle_version' => 1,
            'direction' => 'esh_to_usbs',
        ]);
        self::assertFalse($r['ok']);
    }

    public function testValidateImportBundleRejectsBadVersion(): void
    {
        $r = UsbsBridgeHelper::validateImportBundle(['bundle_version' => 9, 'direction' => 'usbs_to_esh']);
        self::assertFalse($r['ok']);
    }

    public function testExtractPatientRefs(): void
    {
        $refs = UsbsBridgeHelper::extractPatientRefs([
            'esh_id' => 1,
            'enabiz_hasta_ref' => ' EN-1 ',
            'usbs_hasta_ref' => 'U-2',
        ]);
        self::assertSame('EN-1', $refs['enabiz_hasta_ref']);
        self::assertSame('U-2', $refs['usbs_hasta_ref']);
    }

    public function testExtractVisitRefs(): void
    {
        $refs = UsbsBridgeHelper::extractVisitRefs([
            'esh_id' => 5,
            'usbs_bildirim_ref' => 'B-1',
            'usbs_bildirim_durum' => 'sent',
            'erecete_ref' => 'ER-9',
        ]);
        self::assertSame('B-1', $refs['usbs_bildirim_ref']);
        self::assertSame('sent', $refs['usbs_bildirim_durum']);
        self::assertSame('ER-9', $refs['erecete_ref']);
    }

    public function testParseUploadedJson(): void
    {
        $parsed = UsbsBridgeHelper::parseUploadedJson('{"bundle_version":1,"direction":"usbs_to_esh"}');
        self::assertIsArray($parsed);
        self::assertSame(1, $parsed['bundle_version']);
    }

    public function testApiConfiguredReturnsBool(): void
    {
        self::assertIsBool(UsbsBridgeHelper::apiConfigured());
    }
}
