<?php
declare(strict_types=1);

use App\Helpers\SkrsMapHelper;
use PHPUnit\Framework\TestCase;

final class SkrsMapHelperTest extends TestCase
{
    protected function tearDown(): void
    {
        SkrsMapHelper::resetCache();
        parent::tearDown();
    }

    public function testEnrichGuvencePassthroughWhenDisabled(): void
    {
        $in = ['id' => 3, 'label' => 'SSK'];
        self::assertSame($in, SkrsMapHelper::enrichGuvence($in));
    }

    public function testEnrichAdresPassthroughWhenDisabled(): void
    {
        $in = ['id' => 'abc', 'label' => 'Pamukkale', 'tip' => 'ilce'];
        self::assertSame($in, SkrsMapHelper::enrichAdres($in));
    }

    public function testEnrichUserPassthroughWhenDisabled(): void
    {
        $in = ['id' => 1, 'label' => 'Test', 'unvan' => 'hemsire'];
        self::assertSame($in, SkrsMapHelper::enrichUser($in));
    }

    public function testExportContextForKurumNullWhenDisabled(): void
    {
        self::assertNull(SkrsMapHelper::exportContextForKurum(1));
    }
}
