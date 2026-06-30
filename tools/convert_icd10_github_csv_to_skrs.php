<?php
/**
 * GitHub ICD-10 CSV (k4m1113/ICD-10-CSV) → SKRS import formatı (KOD, ADI).
 * Kullanım: php tools/convert_icd10_github_csv_to_skrs.php [girdi.csv] [cikti.csv]
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI gerekli.\n");
    exit(1);
}

$root = dirname(__DIR__);
$input = isset($argv[1]) ? trim((string) $argv[1]) : $root . '/database/import/icd10-github-raw.csv';
$output = isset($argv[2]) ? trim((string) $argv[2]) : $root . '/database/import/icd10-tr.csv';

if (!is_readable($input)) {
    fwrite(STDERR, "Girdi okunamadı: {$input}\n");
    fwrite(STDERR, "Önce indirin: https://raw.githubusercontent.com/k4m1113/ICD-10-CSV/master/codes.csv\n");
    exit(1);
}

function compactToIcd10(string $compact): string
{
    $compact = strtoupper(trim($compact));
    if ($compact === '') {
        return '';
    }
    if (strlen($compact) <= 3) {
        return $compact;
    }

    return substr($compact, 0, 3) . '.' . substr($compact, 3);
}

$in = fopen($input, 'rb');
if ($in === false) {
    fwrite(STDERR, "Girdi açılamadı.\n");
    exit(1);
}
$out = fopen($output, 'wb');
if ($out === false) {
    fclose($in);
    fwrite(STDERR, "Çıktı yazılamadı: {$output}\n");
    exit(1);
}

fputcsv($out, ['KOD', 'ADI']);
$seen = [];
$written = 0;
$skipped = 0;

while (($line = fgetcsv($in)) !== false) {
    if ($line === [null] || count($line) < 4) {
        continue;
    }
    $icd = compactToIcd10((string) ($line[2] ?? ''));
    $name = trim((string) ($line[3] ?? ''));
    if ($icd === '' || $name === '') {
        $skipped++;
        continue;
    }
    if (isset($seen[$icd])) {
        $skipped++;
        continue;
    }
    $seen[$icd] = true;
    fputcsv($out, [$icd, $name]);
    $written++;
}

fclose($in);
fclose($out);

echo "OK: {$written} tanı → {$output}\n";
if ($skipped > 0) {
    echo "Atlanan satır: {$skipped}\n";
}
