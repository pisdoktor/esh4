<?php
/**
 * SKRS ICD-10-TR Excel/CSV → database/import/icd10_hastaliklar.json
 * Kullanım: php tools/build_icd10_hastaliklar_from_skrs.php [dosya.xlsx|csv]
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI gerekli.\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/config/config.php';

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'App\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $file = $root . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Helpers\Icd10SkrsParser;

$importDir = $root . '/database/import';
if (!is_dir($importDir) && !mkdir($importDir, 0775, true)) {
    fwrite(STDERR, "Dizin oluşturulamadı: {$importDir}\n");
    exit(1);
}

$defaultXlsx = $importDir . '/icd10-tr.xlsx';
$defaultCsv = $importDir . '/icd10-tr.csv';
$storageXlsx = $root . '/storage/ICD10Listesi.xlsx';
$input = isset($argv[1]) ? trim((string) $argv[1]) : '';
if ($input === '') {
    if (is_readable($storageXlsx)) {
        $input = $storageXlsx;
    } elseif (is_readable($defaultXlsx)) {
        $input = $defaultXlsx;
    } elseif (is_readable($defaultCsv)) {
        $input = $defaultCsv;
    } else {
        fwrite(STDERR, "Girdi dosyası bulunamadı. SKRS export'unu şuraya koyun:\n");
        fwrite(STDERR, "  {$defaultXlsx}\n  veya {$defaultCsv}\n");
        exit(1);
    }
} elseif (!str_contains($input, ':') && !str_starts_with($input, '/') && !preg_match('/^[A-Za-z]:\\\\/', $input)) {
    $input = $root . '/' . ltrim(str_replace('\\', '/', $input), '/');
}

try {
    $rows = Icd10SkrsParser::parseFile($input);
    $rows = Icd10SkrsParser::withCategories($rows);
} catch (Throwable $e) {
    fwrite(STDERR, 'HATA: ' . $e->getMessage() . "\n");
    exit(1);
}

$jsonPath = $importDir . '/icd10_hastaliklar.json';
$encoded = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if (!is_string($encoded) || file_put_contents($jsonPath, $encoded) === false) {
    fwrite(STDERR, "JSON yazılamadı: {$jsonPath}\n");
    exit(1);
}

echo "OK: " . count($rows) . " tanı → {$jsonPath}\n";
echo "Sonraki adım: php tools/migrate_import_icd10_hastaliklar.php\n";
