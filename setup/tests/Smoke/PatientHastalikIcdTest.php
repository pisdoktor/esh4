<?php
declare(strict_types=1);

namespace Tests\Smoke;

use App\Models\Patient;
use PHPUnit\Framework\TestCase;

final class PatientHastalikIcdTest extends TestCase
{
    public function test_normalize_trims_icd(): void
    {
        self::assertSame('E11.9', Patient::normalizeHastalikIcd('  E11.9  '));
        self::assertSame('', Patient::normalizeHastalikIcd('   '));
    }

    public function test_parse_csv_to_icds_from_string(): void
    {
        $icds = Patient::parseHastalikCsvToIcds('E11.9, I10 ,J44.9');
        self::assertSame(['E11.9', 'I10', 'J44.9'], $icds);
    }

    public function test_parse_csv_to_icds_empty(): void
    {
        self::assertSame([], Patient::parseHastalikCsvToIcds(''));
        self::assertSame([], Patient::parseHastalikCsvToIcds(null));
        self::assertSame([], Patient::parseHastalikCsvToIcds([]));
    }

    public function test_hastaliklar_to_storage_csv_dedupes(): void
    {
        $csv = Patient::hastaliklarToStorageCsv(['I10', 'E11.9', 'I10', '']);
        self::assertSame('I10,E11.9', $csv);
    }

    public function test_parse_csv_from_array(): void
    {
        $icds = Patient::parseHastalikCsvToIcds(['E11.9', 'I10']);
        self::assertSame(['E11.9', 'I10'], $icds);
    }

    public function test_merged_hastalik_icds_for_ilac_rapor(): void
    {
        $patient = (object) ['hastaliklar' => 'G30.9,E11,I10'];
        self::assertSame(['E11', 'G30.9', 'I10'], Patient::mergedHastalikIcdsForIlacRapor($patient));
    }
}
