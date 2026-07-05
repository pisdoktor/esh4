<?php
declare(strict_types=1);

/**
 * İlaç rehberi scrape arka plan işçisi (CLI).
 *
 *   php database/ilac_rehber_scrape_worker.php --job={24_hex_id}
 *
 * Web arayüzü: IlacRehber/migration (bölge yöneticisi).
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require_once ROOT_PATH . '/database/ilac_rehber_scrape_jobs.php';

function irWorkerCliArg(array $argv, string $name, ?string $default = null): ?string
{
    $prefix = '--' . $name . '=';
    foreach ($argv as $arg) {
        if (str_starts_with((string) $arg, $prefix)) {
            return substr((string) $arg, strlen($prefix));
        }
    }

    return $default;
}

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

$jobId = irWorkerCliArg($argv ?? [], 'job', null);
if ($jobId === null || $jobId === '') {
    fwrite(STDERR, "Kullanım: php database/ilac_rehber_scrape_worker.php --job={id}\n");
    exit(1);
}

try {
    exit(\App\Helpers\IlacRehberScrapeSync::runWorker($jobId));
} catch (Throwable $e) {
    if ($e->getMessage() === ESH_ILAC_REHBER_CANCELLED_MSG) {
        exit(0);
    }
    fwrite(STDERR, 'HATA: ' . $e->getMessage() . PHP_EOL);
    irScrapeUpdateJob($jobId, [
        'status' => 'error',
        'error' => $e->getMessage(),
        'step' => 'Hata',
        'finished_at' => date('c'),
        'worker_pid' => null,
    ], ROOT_PATH);
    exit(1);
}
