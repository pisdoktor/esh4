<?php
declare(strict_types=1);

use App\Helpers\BridgeLookupResolver;
use App\Helpers\CinsiyetHelper;
use PHPUnit\Framework\TestCase;

final class BridgeLookupResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        BridgeLookupResolver::resetCache();
        parent::tearDown();
    }

    public function testResolveCinsiyetNormalizes(): void
    {
        self::assertSame(CinsiyetHelper::ERKEK, BridgeLookupResolver::resolve('E', '', 'cinsiyet'));
        self::assertSame(CinsiyetHelper::KADIN, BridgeLookupResolver::resolve('2', '', 'cinsiyet'));
    }

    public function testResolveEmptyReturnsNull(): void
    {
        self::assertNull(BridgeLookupResolver::resolve(null, 'icd10_skrs'));
        self::assertNull(BridgeLookupResolver::resolve('', 'esh_islemler'));
    }

    public function testResolveKonsBransIstekJson(): void
    {
        $raw = '{"1":[2,3]}';
        $result = BridgeLookupResolver::resolve($raw, '', 'kons_brans_istek');
        self::assertIsArray($result);
        self::assertSame(['1' => [2, 3]], $result);
    }

    public function testResolveUnknownLookupReturnsScalar(): void
    {
        self::assertSame('foo', BridgeLookupResolver::resolve('foo', 'unknown_lookup'));
    }
}
