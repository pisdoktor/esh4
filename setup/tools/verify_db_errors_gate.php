<?php
declare(strict_types=1);

/**
 * db_errors.log delta kontrolü (CLI).
 *
 *   php tools/verify_db_errors_gate.php --baseline-file=logs/.qa_baseline
 *   php tools/verify_db_errors_gate.php --since-line=4500
 *   php tools/verify_db_errors_gate.php --save-baseline
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
$logFile = $root . '/logs/db_errors.log';
$defaultBaseline = $root . '/logs/.qa_baseline';

$saveBaseline = in_array('--save-baseline', $argv ?? [], true);
$sinceLine = 0;
$baselineFile = $defaultBaseline;

foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--since-line=')) {
        $sinceLine = max(0, (int) substr($arg, strlen('--since-line=')));
    }
    if (str_starts_with($arg, '--baseline-file=')) {
        $baselineFile = $root . '/' . ltrim(substr($arg, strlen('--baseline-file=')), '/');
    }
}

if (!is_file($logFile)) {
    if ($saveBaseline) {
        if (!is_dir(dirname($baselineFile))) {
            @mkdir(dirname($baselineFile), 0775, true);
        }
        file_put_contents($baselineFile, "0\n");
        echo "Baseline kaydedildi: 0 satır (log dosyası yok)\n";
        exit(0);
    }
    echo "OK: db_errors.log yok — hata yok\n";
    exit(0);
}

$lines = file($logFile, FILE_IGNORE_NEW_LINES);
if (!is_array($lines)) {
    fwrite(STDERR, "FAIL: db_errors.log okunamadı\n");
    exit(1);
}

$totalLines = count($lines);

if ($saveBaseline) {
    if (!is_dir(dirname($baselineFile))) {
        @mkdir(dirname($baselineFile), 0775, true);
    }
    file_put_contents($baselineFile, (string) $totalLines . "\n");
    echo "Baseline kaydedildi: satır {$totalLines} → {$baselineFile}\n";
    exit(0);
}

if ($sinceLine <= 0 && is_file($baselineFile)) {
    $raw = trim((string) file_get_contents($baselineFile));
    if ($raw !== '' && ctype_digit($raw)) {
        $sinceLine = (int) $raw;
    }
}

$newErrors = [];
for ($i = $sinceLine; $i < $totalLines; $i++) {
    if (str_contains((string) ($lines[$i] ?? ''), 'Sorgu Hatası')) {
        $newErrors[] = $i + 1;
    }
}

if ($newErrors === []) {
    echo "OK: db_errors.log — satır {$sinceLine} sonrası yeni Sorgu Hatası yok (toplam {$totalLines} satır)\n";
    exit(0);
}

echo "FAIL: db_errors.log — " . count($newErrors) . " yeni Sorgu Hatası (satır {$sinceLine}+)\n";
foreach (array_slice($newErrors, 0, 10) as $lineNo) {
    echo "  → satır {$lineNo}\n";
}
if (count($newErrors) > 10) {
    echo '  → ... +' . (count($newErrors) - 10) . " daha\n";
}
exit(1);
