<?php
/**
 * Eksik ilçe zincirli (orphan) adres kayıtlarını toplu temizler (CLI).
 *
 * Kullanım:
 *   php tools/purge_orphan_addresses.php --dry-run --tip=all
 *   php tools/purge_orphan_addresses.php --tip=kapino --batch=500
 *   php tools/purge_orphan_addresses.php --tip=sokak
 *   php tools/purge_orphan_addresses.php --tip=mahalle
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Bu araç yalnızca CLI'dan çalıştırılabilir.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/config.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Models\Address;

/** @return array<string, mixed> */
function parseCliArgs(array $argv): array {
    $opts = [
        'dry-run' => false,
        'tip' => 'all',
        'batch' => 500,
        'max-rounds' => 1000,
    ];
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--dry-run') {
            $opts['dry-run'] = true;
            continue;
        }
        if (str_starts_with($arg, '--tip=')) {
            $opts['tip'] = strtolower(trim(substr($arg, 6)));
            continue;
        }
        if (str_starts_with($arg, '--batch=')) {
            $opts['batch'] = max(1, (int) substr($arg, 8));
            continue;
        }
        if (str_starts_with($arg, '--max-rounds=')) {
            $opts['max-rounds'] = max(1, (int) substr($arg, 13));
            continue;
        }
        if ($arg === '--help' || $arg === '-h') {
            $opts['help'] = true;
        }
    }
    return $opts;
}

function printUsage(): void {
    fwrite(STDOUT, "Kullanım: php tools/purge_orphan_addresses.php [seçenekler]\n\n");
    fwrite(STDOUT, "  --dry-run           Silme yapmadan orphan sayısını göster\n");
    fwrite(STDOUT, "  --tip=TIP           kapino|sokak|mahalle|all (varsayılan: all)\n");
    fwrite(STDOUT, "  --batch=N           Tur başına kayıt (varsayılan: 500)\n");
    fwrite(STDOUT, "  --max-rounds=N      Maksimum tur (varsayılan: 1000)\n");
    fwrite(STDOUT, "\nSilme sırası (all): Kapı → Sokak → Mahalle\n");
}

/** @param list<string> $tips */
function resolveTips(string $tip): array {
    if ($tip === 'all') {
        return ['kapino', 'sokak', 'mahalle'];
    }
    if (!in_array($tip, ['kapino', 'sokak', 'mahalle'], true)) {
        fwrite(STDERR, "Geçersiz --tip: {$tip}\n");
        exit(1);
    }
    return [$tip];
}

$opts = parseCliArgs($argv);
if (!empty($opts['help'])) {
    printUsage();
    exit(0);
}

$dryRun = (bool) $opts['dry-run'];
$batch = (int) $opts['batch'];
$maxRounds = (int) $opts['max-rounds'];
$tips = resolveTips((string) $opts['tip']);

$model = new Address();
$grandDeleted = 0;
$grandFailed = 0;
$grandSkippedChildren = 0;
$grandSkippedPatient = 0;

fwrite(STDOUT, 'Veritabanı: ' . DB_NAME . PHP_EOL);
fwrite(STDOUT, ($dryRun ? '[DRY-RUN] ' : '') . 'Orphan temizlik başlıyor…' . PHP_EOL);

foreach ($tips as $tip) {
    fwrite(STDOUT, PHP_EOL . '=== ' . strtoupper($tip) . ' ===' . PHP_EOL);

    if ($dryRun) {
        $count = $model->countOrphansForTip($tip);
        fwrite(STDOUT, "Orphan sayısı: {$count}" . PHP_EOL);
        continue;
    }

    $round = 0;
    $tipDeleted = 0;
    $tipFailed = 0;
    $tipSkippedChildren = 0;
    $tipSkippedPatient = 0;

    while ($round < $maxRounds) {
        $round++;
        $result = $model->purgeOrphansBatch($tip, $batch);
        $fetched = (int) ($result['batch_fetched'] ?? 0);
        $deleted = count($result['deleted'] ?? []);
        $failed = count($result['failed'] ?? []);
        $tipDeleted += $deleted;
        $tipFailed += $failed;
        $tipSkippedChildren += (int) ($result['skipped_has_children'] ?? 0);
        $tipSkippedPatient += (int) ($result['skipped_patient_ref'] ?? 0);

        $remaining = (int) ($result['remaining_estimate'] ?? 0);
        fwrite(STDOUT, sprintf(
            "Tur %d: alınan=%d, silinen=%d, başarısız=%d, kalan~=%d\n",
            $round,
            $fetched,
            $deleted,
            $failed,
            $remaining
        ));

        if ($fetched === 0) {
            break;
        }
        if ($deleted === 0 && $failed > 0) {
            fwrite(STDOUT, "Silinemeyen kayıtlar nedeniyle durduruldu (alt kayıt veya hasta referansı).\n");
            break;
        }
    }

    if ($round >= $maxRounds) {
        fwrite(STDOUT, "Uyarı: --max-rounds ({$maxRounds}) sınırına ulaşıldı.\n");
    }

    fwrite(STDOUT, sprintf(
        "%s özeti: silinen=%d, başarısız=%d (alt kayıt=%d, hasta ref=%d)\n",
        strtoupper($tip),
        $tipDeleted,
        $tipFailed,
        $tipSkippedChildren,
        $tipSkippedPatient
    ));

    $grandDeleted += $tipDeleted;
    $grandFailed += $tipFailed;
    $grandSkippedChildren += $tipSkippedChildren;
    $grandSkippedPatient += $tipSkippedPatient;
}

if (!$dryRun) {
    fwrite(STDOUT, PHP_EOL . sprintf(
        'TOPLAM: silinen=%d, başarısız=%d (alt kayıt=%d, hasta ref=%d)' . PHP_EOL,
        $grandDeleted,
        $grandFailed,
        $grandSkippedChildren,
        $grandSkippedPatient
    ));
}

fwrite(STDOUT, 'Bitti.' . PHP_EOL);
