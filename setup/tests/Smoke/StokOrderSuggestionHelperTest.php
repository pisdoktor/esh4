<?php
declare(strict_types=1);

use App\Helpers\StokOrderSuggestionHelper;
use PHPUnit\Framework\TestCase;

final class StokOrderSuggestionHelperTest extends TestCase
{
    public function testSummarizeWhenModuleNotReady(): void
    {
        $summary = StokOrderSuggestionHelper::summarize(null);
        self::assertArrayHasKey('ready', $summary);
        self::assertArrayHasKey('count', $summary);
        if (!$summary['ready']) {
            self::assertSame(0, $summary['count']);
        } else {
            self::assertGreaterThanOrEqual(0, $summary['count']);
            self::assertIsArray($summary['items']);
        }
    }

    public function testExportRowsStructure(): void
    {
        $rows = StokOrderSuggestionHelper::exportRows(null, 10);
        self::assertIsArray($rows);
        foreach ($rows as $row) {
            self::assertArrayHasKey('kod', $row);
            self::assertArrayHasKey('ad', $row);
            self::assertArrayHasKey('oneri', $row);
        }
    }
}
