<?php
declare(strict_types=1);

use App\Helpers\FederationBridgeHelper;
use App\Helpers\FederationHelper;
use PHPUnit\Framework\TestCase;

final class FederationBridgeHelperTest extends TestCase
{
    public function testValidateImportBundleAcceptsHubDirection(): void
    {
        $r = FederationBridgeHelper::validateImportBundle([
            'bundle_version' => 1,
            'direction' => 'hub_to_node',
        ]);
        self::assertTrue($r['ok']);
        self::assertSame('hub_to_node', $r['direction']);
    }

    public function testValidateImportBundleRejectsSnapshotDirection(): void
    {
        $r = FederationBridgeHelper::validateImportBundle([
            'bundle_version' => 1,
            'direction' => 'node_snapshot',
        ]);
        self::assertFalse($r['ok']);
    }

    public function testExtractRegionItemNormalizesKod(): void
    {
        $item = FederationBridgeHelper::extractRegionItem([
            'kod' => ' Denizli Merkez ',
            'ad' => 'Denizli',
            'aktif' => 1,
        ]);
        self::assertSame('denizli-merkez', $item['kod']);
        self::assertSame('Denizli', $item['ad']);
    }

    public function testParseUploadedJson(): void
    {
        $parsed = FederationBridgeHelper::parseUploadedJson('{"bundle_version":1,"direction":"hub_to_node"}');
        self::assertIsArray($parsed);
        self::assertSame(1, $parsed['bundle_version']);
    }

    public function testNormalizeRef(): void
    {
        self::assertSame('NODE-1', FederationHelper::normalizeRef(' NODE-1 '));
        self::assertNull(FederationHelper::normalizeRef(''));
    }
}
