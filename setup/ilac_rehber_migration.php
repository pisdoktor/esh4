<?php
declare(strict_types=1);

/**
 * Eski giriş noktası — IlacRehber/migration sayfasına yönlendirir.
 * CLI işçi: database/ilac_rehber_scrape_worker.php
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once ROOT_PATH . '/config/url_helpers.php';

if (PHP_SAPI === 'cli') {
    $jobId = null;
    $isWorker = false;
    foreach ($argv ?? [] as $arg) {
        $arg = (string) $arg;
        if ($arg === '--worker=1') {
            $isWorker = true;
        }
        if (str_starts_with($arg, '--job=')) {
            $jobId = substr($arg, 6);
        }
    }
    if (($isWorker || $jobId !== null) && $jobId !== null && $jobId !== '') {
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
        try {
            exit(\App\Helpers\IlacRehberScrapeSync::runWorker($jobId));
        } catch (Throwable $e) {
            if ($e->getMessage() === ESH_ILAC_REHBER_CANCELLED_MSG) {
                exit(0);
            }
            fwrite(STDERR, 'HATA: ' . $e->getMessage() . PHP_EOL);
            exit(1);
        }
    }
    fwrite(STDOUT, "Web: IlacRehber/migration (süper yönetici)\n");
    fwrite(STDOUT, "CLI işçi: php database/ilac_rehber_scrape_worker.php --job={id}\n");
    fwrite(STDOUT, "Scrape: php database/scrape_ilac_rehber.php\n");
    exit(0);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Location: ' . esh_url('IlacRehber', 'migration'), true, 302);
exit;
