<?php
/**
 * Evde sağlık ICD-10 top 300 → seed + migration SQL üretir.
 * Kullanım: php tools/generate_evde_saglik_icd10_top300_sql.php
 */
declare(strict_types=1);

require_once __DIR__ . '/inc_evde_saglik_icd10_candidates.php';

require_once dirname(__DIR__) . '/app/Helpers/Icd10CatMapper.php';

const SEED_KURUM_ID = 0;
const EXPECTED_COUNT = 300;

/**
 * ICD-10 kodunu esh_hastalikcat.id (1–21) ile eşler.
 */
function icd10ToHastalikCat(string $icd): int
{
    return \App\Helpers\Icd10CatMapper::toHastalikCat($icd);
}

function sqlEscape(string $value): string
{
    return str_replace(["\\", "'"], ["\\\\", "''"], $value);
}

/**
 * @param list<array{icd:string,hastalikadi:string,cat?:int}> $candidates
 * @return list<array{id:int,kurum_id:int,cat:int,hastalikadi:string,icd:string}>
 */
function normalizeCandidates(array $candidates): array
{
    $seen = [];
    $rows = [];
    foreach ($candidates as $row) {
        $icd = strtoupper(trim((string) ($row['icd'] ?? '')));
        $name = trim((string) ($row['hastalikadi'] ?? ''));
        if ($icd === '' || $name === '') {
            throw new RuntimeException('Boş icd veya hastalikadi: ' . json_encode($row, JSON_UNESCAPED_UNICODE));
        }
        if (isset($seen[$icd])) {
            throw new RuntimeException("Yinelenen ICD: {$icd}");
        }
        $seen[$icd] = true;
        $cat = isset($row['cat']) ? (int) $row['cat'] : icd10ToHastalikCat($icd);
        $rows[] = [
            'kurum_id' => SEED_KURUM_ID,
            'cat' => $cat,
            'hastalikadi' => $name,
            'icd' => $icd,
        ];
    }

    return $rows;
}

/**
 * @param list<array{id:int,kurum_id:int,cat:int,hastalikadi:string,icd:string}> $rows
 */
function writeSeedSql(string $path, array $rows): void
{
    $fh = fopen($path, 'wb');
    if ($fh === false) {
        throw new RuntimeException('Yazılamadı: ' . $path);
    }

    fwrite($fh, "-- =============================================================================\n");
    fwrite($fh, "-- ESH kurulum seed — Hastalık kütüphanesi (ICD tanı listesi)\n");
    fwrite($fh, "-- Tablo: `#__hastaliklar` (kaynak: `esh_hastaliklar`) | Satır: " . count($rows) . "\n");
    fwrite($fh, "-- Evde sağlık odaklı ICD-10-TR top 300 — tools/generate_evde_saglik_icd10_top300_sql.php\n");
    fwrite($fh, "-- Kurulum sırasında Installer otomatik çalıştırır.\n");
    fwrite($fh, "-- Elle: mysql -u KULLANICI -p VERITABANI < database/seed/seed_esh_hastaliklar.sql\n");
    fwrite($fh, "-- =============================================================================\n\n");
    fwrite($fh, "SET NAMES utf8mb4;\n");
    fwrite($fh, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

    $batchSize = 100;
    $total = count($rows);
    for ($offset = 0; $offset < $total; $offset += $batchSize) {
        $batch = array_slice($rows, $offset, $batchSize);
        fwrite($fh, "INSERT INTO `#__hastaliklar` (`id`, `kurum_id`, `cat`, `hastalikadi`, `icd`) VALUES\n");
        $lines = [];
        foreach ($batch as $i => $row) {
            $id = $offset + $i + 1;
            $lines[] = sprintf(
                "(%d, %d, %d, '%s', '%s')",
                $id,
                (int) $row['kurum_id'],
                (int) $row['cat'],
                sqlEscape($row['hastalikadi']),
                sqlEscape($row['icd'])
            );
        }
        fwrite($fh, implode(",\n", $lines));
        fwrite($fh, ";\n\n");
    }

    fwrite($fh, "SET FOREIGN_KEY_CHECKS = 1;\n");
    fclose($fh);
}

/**
 * @param list<array{kurum_id:int,cat:int,hastalikadi:string,icd:string}> $rows
 */
function writeSkrsTestCsv(string $path, array $rows): void
{
    $fh = fopen($path, 'wb');
    if ($fh === false) {
        throw new RuntimeException('Yazılamadı: ' . $path);
    }
    fputcsv($fh, ['KOD', 'ADI']);
    foreach ($rows as $row) {
        fputcsv($fh, [$row['icd'], $row['hastalikadi']]);
    }
    fclose($fh);
}

/**
 * @param list<array{kurum_id:int,cat:int,hastalikadi:string,icd:string}> $rows
 */
function writeMigrationSql(string $path, array $rows): void
{
    $fh = fopen($path, 'wb');
    if ($fh === false) {
        throw new RuntimeException('Yazılamadı: ' . $path);
    }

    fwrite($fh, "-- =============================================================================\n");
    fwrite($fh, "-- İsteğe bağlı migrasyon — evde sağlık ICD-10 top 300 (eksik tanıları ekler)\n");
    fwrite($fh, "-- Aynı `icd` zaten varsa atlanır (idempotent).\n");
    fwrite($fh, "-- Elle: mysql -u KULLANICI -p VERITABANI < database/migrate_seed_esh_hastaliklar_icd10_top300.sql\n");
    fwrite($fh, "-- veya: php tools/run_sql_migration.php database/migrate_seed_esh_hastaliklar_icd10_top300.sql\n");
    fwrite($fh, "-- =============================================================================\n\n");
    fwrite($fh, "SET NAMES utf8mb4;\n\n");

    foreach ($rows as $row) {
        $icd = sqlEscape($row['icd']);
        $name = sqlEscape($row['hastalikadi']);
        $cat = (int) $row['cat'];
        $kurum = (int) $row['kurum_id'];
        fwrite($fh, "INSERT INTO `esh_hastaliklar` (`kurum_id`, `cat`, `hastalikadi`, `icd`)\n");
        fwrite($fh, "SELECT {$kurum}, {$cat}, '{$name}', '{$icd}'\n");
        fwrite($fh, "WHERE NOT EXISTS (\n");
        fwrite($fh, "  SELECT 1 FROM `esh_hastaliklar` h2 WHERE h2.`icd` = '{$icd}'\n");
        fwrite($fh, ");\n\n");
    }

    fclose($fh);
}

$rows = normalizeCandidates(evde_saglik_icd10_candidates());
$count = count($rows);
if ($count !== EXPECTED_COUNT) {
    fwrite(STDERR, "HATA: {$count} aday bulundu, beklenen " . EXPECTED_COUNT . ".\n");
    exit(1);
}

$root = dirname(__DIR__);
$migratePath = $root . '/database/migrate_seed_esh_hastaliklar_icd10_top300.sql';
$csvPath = $root . '/database/import/icd10-tr.csv';

writeMigrationSql($migratePath, $rows);
writeSkrsTestCsv($csvPath, $rows);

echo "OK: {$count} tanı → migration → {$migratePath}\n";
echo "OK: SKRS test CSV → {$csvPath}\n";
echo "NOT: database/seed/seed_esh_hastaliklar.sql bilinçli boş (tam katalog SKRS import).\n";

// Örnek eşleme doğrulaması
$samples = ['I10' => 9, 'G30.9' => 6, 'J44.9' => 10, 'Z51.5' => 21];
foreach ($samples as $icd => $expectedCat) {
    $actual = icd10ToHastalikCat($icd);
    if ($actual !== $expectedCat) {
        fwrite(STDERR, "Eşleme hatası {$icd}: beklenen {$expectedCat}, bulunan {$actual}\n");
        exit(1);
    }
}
