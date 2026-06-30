<?php
declare(strict_types=1);

namespace App\Helpers;

use App\Models\RehberEtken;
use App\Models\RehberIlac;
use RuntimeException;
use Throwable;

/**
 * İlaç rehberi snapshot scrape — web yönetimi ve arka plan işçisi (AdresFetch deseni).
 *
 * CLI işçi: php database/ilac_rehber_scrape_worker.php --job={id}
 */
final class IlacRehberScrapeSync
{
    public const WORKER_SCRIPT_REL = 'database/ilac_rehber_scrape_worker.php';

    private static function root(): string
    {
        return rtrim((string) ROOT_PATH, '/\\');
    }

    private static function ensureScrapeJobs(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;
        require_once self::root() . '/database/ilac_rehber_scrape_jobs.php';
    }

    private static function ensureCheckpoint(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;
        require_once self::root() . '/database/ilac_rehber_checkpoint.php';
    }

    private static function ensureImportLog(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;
        require_once self::root() . '/database/ilac_rehber_import_log.php';
    }

    private static function logFile(): string
    {
        return self::root() . '/logs/ilac_rehber_scrape.log';
    }

    private static function lastErrorFile(): string
    {
        $dir = self::root() . '/storage';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir . '/ilac_rehber_scrape.last_error.txt';
    }

    public static function writeLastError(string $message): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . trim($message) . "\n";
        @file_put_contents(self::lastErrorFile(), $line, LOCK_EX);
    }

    public static function readLastError(): ?string
    {
        if (!is_file(self::lastErrorFile())) {
            return null;
        }
        $raw = trim((string) @file_get_contents(self::lastErrorFile()));

        return $raw === '' ? null : $raw;
    }

    public static function clearLastError(): void
    {
        @unlink(self::lastErrorFile());
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultOptions(): array
    {
        return [
            'site' => 'ilacabak',
            'sleep_ms' => 50,
            'discovery' => 'both',
            'id_max' => 15000,
            'id_empty_stop' => 50,
            'limit_etken' => 0,
            'limit_ilac' => 0,
            'truncate' => false,
            'fresh' => false,
            'resume' => true,
            'insecure_ssl' => false,
            'progress_every' => 25,
        ];
    }

    /**
     * @param array<string, mixed> $raw
     * @return array{ok: bool, options?: array<string, mixed>, error?: string}
     */
    public static function parseOptionsFromRequest(array $raw): array
    {
        $defaults = self::defaultOptions();
        $site = strtolower(trim((string) ($raw['site'] ?? $defaults['site'])));
        if ($site === '' || !preg_match('/^[a-z0-9_-]{2,32}$/', $site)) {
            return ['ok' => false, 'error' => 'Geçersiz site parametresi.'];
        }
        if ($site !== 'ilacabak') {
            return ['ok' => false, 'error' => 'Şu an yalnızca ilacabak kaynağı destekleniyor.'];
        }

        $discovery = strtolower(trim((string) ($raw['discovery'] ?? $defaults['discovery'])));
        if (!in_array($discovery, ['seeds', 'ids', 'both'], true)) {
            return ['ok' => false, 'error' => 'Keşif modu seeds, ids veya both olmalıdır.'];
        }

        $sleepMs = max(50, min(10000, (int) ($raw['sleep_ms'] ?? $defaults['sleep_ms'])));
        $idMax = max(1, min(500000, (int) ($raw['id_max'] ?? $defaults['id_max'])));
        $idEmptyStop = max(1, min(5000, (int) ($raw['id_empty_stop'] ?? $defaults['id_empty_stop'])));
        $limitEtken = max(0, min(500000, (int) ($raw['limit_etken'] ?? $defaults['limit_etken'])));
        $limitIlac = max(0, min(500000, (int) ($raw['limit_ilac'] ?? $defaults['limit_ilac'])));
        $progressEvery = max(0, min(5000, (int) ($raw['progress_every'] ?? $defaults['progress_every'])));
        $truncate = ((string) ($raw['truncate'] ?? '0')) === '1';
        $fresh = ((string) ($raw['fresh'] ?? '0')) === '1';
        $resume = ((string) ($raw['resume'] ?? '1')) !== '0';
        $insecureSsl = ((string) ($raw['insecure_ssl'] ?? '0')) === '1';

        return [
            'ok' => true,
            'options' => [
                'site' => $site,
                'sleep_ms' => $sleepMs,
                'discovery' => $discovery,
                'id_max' => $idMax,
                'id_empty_stop' => $idEmptyStop,
                'limit_etken' => $limitEtken,
                'limit_ilac' => $limitIlac,
                'truncate' => $truncate,
                'fresh' => $fresh || $truncate,
                'resume' => $resume && !$fresh && !$truncate,
                'insecure_ssl' => $insecureSsl,
                'progress_every' => $progressEvery,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @return list<string>
     */
    public static function buildScrapeArgv(array $options): array
    {
        $argv = [
            '--site=' . (string) $options['site'],
            '--sleep=' . (int) $options['sleep_ms'],
            '--discovery=' . (string) $options['discovery'],
            '--id-max=' . (int) $options['id_max'],
            '--id-empty-stop=' . (int) $options['id_empty_stop'],
        ];
        if (!empty($options['truncate'])) {
            $argv[] = '--truncate';
        }
        if (!empty($options['fresh'])) {
            $argv[] = '--fresh';
        }
        if (empty($options['resume'])) {
            $argv[] = '--no-resume';
        }
        if (!empty($options['insecure_ssl'])) {
            $argv[] = '--insecure-ssl';
        }
        $limitEtken = (int) ($options['limit_etken'] ?? 0);
        if ($limitEtken > 0) {
            $argv[] = '--limit-etken=' . $limitEtken;
        }
        $limitIlac = (int) ($options['limit_ilac'] ?? 0);
        if ($limitIlac > 0) {
            $argv[] = '--limit-ilac=' . $limitIlac;
        }
        $progressEvery = (int) ($options['progress_every'] ?? 0);
        if ($progressEvery > 0) {
            $argv[] = '--progress-every=' . $progressEvery;
        }

        return $argv;
    }

    public static function isScrapeRunning(): bool
    {
        self::ensureScrapeJobs();

        return ilacRehberScrapeWorkerIsRunning(self::root());
    }

    /**
     * @return array{id: string, job: array}|null
     */
    public static function findLatestActiveJob(): ?array
    {
        self::ensureScrapeJobs();

        return irScrapeFindLatestActiveJob(self::root());
    }

    /**
     * @return array{id: string, job: array}|null
     */
    public static function resolveJobForStatus(?string $jobId): ?array
    {
        self::ensureScrapeJobs();
        $root = self::root();
        if ($jobId !== null && $jobId !== '') {
            $job = irScrapeReadJob($jobId, $root);
            if ($job === null) {
                return null;
            }

            return ['id' => $jobId, 'job' => $job];
        }

        return irScrapeFindLatestActiveJob($root);
    }

    /**
     * @param array<string, mixed> $job
     * @return array<string, mixed>
     */
    public static function guardAndEnrichJob(string $jobId, array $job): array
    {
        self::ensureScrapeJobs();
        $root = self::root();
        $job = irScrapeApplyStaleQueuedGuard($jobId, $job, $root);
        $job = irScrapeApplyStaleJobGuard($jobId, $job, $root);
        $job = irScrapeApplyLiveCheckpointProgress($jobId, $job, $root);

        return $job;
    }

    public static function jobIsActive(array $job): bool
    {
        self::ensureScrapeJobs();

        return irScrapeJobIsActive($job);
    }

    /**
     * @return list<string>
     */
    public static function tailLogLines(int $maxLines = 20): array
    {
        $path = self::logFile();
        if (!is_readable($path)) {
            return [];
        }
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            return [];
        }
        $lines = preg_split("/\r\n|\n|\r/", trim($content)) ?: [];
        if ($lines === []) {
            return [];
        }

        return array_map(
            static fn (string $line): string => self::sanitizeUtf8String($line),
            array_slice($lines, -$maxLines)
        );
    }

    private static function sanitizeUtf8String(string $value): string
    {
        if ($value === '') {
            return $value;
        }
        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            if (is_string($converted)) {
                return $converted;
            }
        }
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if (is_string($converted)) {
                return $converted;
            }
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public static function fetchDbStats(?bool $workerRunning = null): array
    {
        if ($workerRunning === null) {
            $workerRunning = self::isScrapeRunning();
        }
        if (!$workerRunning) {
            self::closeOpenImportLog('Scrape süreci çalışmıyor (otomatik kapatıldı)');
        }

        self::ensureImportLog();
        $etkenModel = new RehberEtken();
        $etkenModel->ensureTables();
        $ilacModel = new RehberIlac();

        $latestImportLog = ilacRehberImportGetLatest(self::root());
        $openImportLog = ilacRehberImportGetOpen(self::root());
        $importInProgress = $workerRunning && $openImportLog !== null;
        $importStartedAt = null;
        $importSite = null;
        $importFinishedAt = null;
        if ($latestImportLog !== null) {
            if (!empty($latestImportLog->started_at)) {
                $importStartedAt = (string) $latestImportLog->started_at;
            }
            if (!empty($latestImportLog->source_site)) {
                $importSite = (string) $latestImportLog->source_site;
            }
            if (!empty($latestImportLog->finished_at)) {
                $importFinishedAt = (string) $latestImportLog->finished_at;
            }
        }

        return [
            'ok' => true,
            'etken' => $etkenModel->countAll(),
            'ilac' => $ilacModel->countAll(),
            'etken_without_ilac' => $etkenModel->countWithoutIlac(),
            'import_in_progress' => $importInProgress,
            'import_started_at' => $importStartedAt,
            'import_finished_at' => $importFinishedAt,
            'import_site' => $importSite,
            'last_log_line' => self::tailLogLines(1)[0] ?? null,
        ];
    }

    public static function closeOpenImportLog(string $reason): void
    {
        self::ensureImportLog();
        $root = self::root();
        $countEtken = null;
        $countIlac = null;
        try {
            $etkenModel = new RehberEtken();
            $etkenModel->ensureTables();
            $ilacModel = new RehberIlac();
            $countEtken = static fn (): int => $etkenModel->countAll();
            $countIlac = static fn (): int => $ilacModel->countAll();
        } catch (Throwable $e) {
            // sayılar alınamazsa iş yine kapatılır
        }
        ilacRehberImportFinalizeOpen($root, $reason, $countEtken, $countIlac);
    }

    /**
     * @param array<string, mixed> $progress
     * @param array<string, mixed> $stats
     * @return array{worker_state: string, status_message: string}
     */
    private static function deriveWorkerState(bool $running, ?array $checkpoint, array $progress, array $stats): array
    {
        if ($running) {
            return ['worker_state' => 'running', 'status_message' => ''];
        }

        $resumeHint = trim((string) ($progress['checkpoint_resume'] ?? ''));
        if ($checkpoint !== null) {
            $msg = $resumeHint !== ''
                ? 'Durduruldu — ' . preg_replace('/^Kaldığı yerden devam:\s*/u', '', $resumeHint)
                : 'Durduruldu — checkpoint ile devam edebilirsiniz';

            return ['worker_state' => 'stopped', 'status_message' => $msg];
        }

        if (($progress['phase'] ?? '') === 'done') {
            return ['worker_state' => 'completed', 'status_message' => 'Tamamlandı'];
        }

        if (!empty($stats['import_in_progress'])) {
            return [
                'worker_state' => 'import_stale',
                'status_message' => 'Import kaydı açık (süreç yok) — Scrape başlat ile devam edin',
            ];
        }

        return ['worker_state' => 'idle', 'status_message' => 'Hazır — scrape başlatılabilir'];
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildStatusPayload(?string $jobId = null): array
    {
        $resolved = self::resolveJobForStatus($jobId);
        $stats = self::fetchDbStats(null);
        self::ensureCheckpoint();
        $projectRoot = self::root();

        if ($resolved === null) {
            $checkpoint = ilacRehberCheckpointSummary($projectRoot);
            $progress = ilacRehberCheckpointProgress($projectRoot, false, null);
            $uiState = self::deriveWorkerState(false, $checkpoint, $progress, is_array($stats) ? $stats : []);

            return array_merge([
                'ok' => true,
                'job' => null,
                'running' => false,
                'checkpoint' => $checkpoint,
                'log_tail' => self::tailLogLines(20),
                'last_error' => self::readLastError(),
                'stats' => $stats,
                'worker_state' => $uiState['worker_state'],
                'status_message' => $uiState['status_message'],
            ], $progress);
        }

        $jid = $resolved['id'];
        $job = self::guardAndEnrichJob($jid, $resolved['job']);
        $running = self::jobIsActive($job);
        $jobConfig = is_array($job['config'] ?? null) ? $job['config'] : null;
        $checkpoint = ilacRehberCheckpointSummary($projectRoot);
        $progress = ilacRehberCheckpointProgress($projectRoot, $running, $jobConfig);
        $uiState = self::deriveWorkerState($running, $checkpoint, $progress, is_array($stats) ? $stats : []);

        return array_merge([
            'ok' => true,
            'job' => $job,
            'running' => $running,
            'checkpoint' => $checkpoint,
            'log_tail' => self::tailLogLines(20),
            'last_error' => self::readLastError(),
            'stats' => $stats,
            'worker_state' => $uiState['worker_state'],
            'status_message' => $uiState['status_message'],
            'job_options' => $jobConfig,
            'started_at' => $job['created_at'] ?? null,
        ], $progress);
    }

    /**
     * @param array<string, mixed> $options
     * @return array{ok: bool, job_id?: string, error?: string, active_job_id?: string, last_error?: string|null}
     */
    public static function startJob(array $options): array
    {
        self::ensureScrapeJobs();
        $root = self::root();

        $existing = irScrapeFindLatestActiveJob($root);
        if ($existing !== null) {
            $ej = self::guardAndEnrichJob($existing['id'], $existing['job']);
            if (self::jobIsActive($ej)) {
                return [
                    'ok' => false,
                    'error' => 'Halihazırda çalışan veya sırada bekleyen bir scrape var. Önce durdurun veya bitmesini bekleyin.',
                    'active_job_id' => $existing['id'],
                ];
            }
        }

        self::closeOpenImportLog('Yeni scrape başlatılıyor (önceki import kaydı kapatıldı)');
        self::clearLastError();

        $jobId = bin2hex(random_bytes(12));
        irScrapeWriteJob($jobId, [
            'id' => $jobId,
            'status' => 'queued',
            'step' => 'Sırada',
            'progress' => 0,
            'sub_progress' => 0,
            'sub_step' => 'İş kuyruğa alındı',
            'logs' => ['[' . date('H:i:s') . '] Job oluşturuldu.'],
            'config' => $options,
            'created_at' => date('c'),
        ], true, $root);

        $spawn = self::spawnScrapeWorker($jobId);
        if (!$spawn['ok']) {
            $failMsg = (string) ($spawn['error'] ?? 'bilinmeyen');
            self::writeLastError($failMsg);
            irScrapeUpdateJob($jobId, [
                'status' => 'error',
                'step' => 'Hata',
                'sub_step' => 'Worker başlatılamadı',
                'error' => $failMsg,
                'finished_at' => date('c'),
                'worker_pid' => null,
            ], $root);
            self::logScrapeLine('START FAILED job=' . $jobId . ': ' . $failMsg);

            return [
                'ok' => false,
                'error' => $failMsg,
                'job_id' => $jobId,
                'last_error' => self::readLastError(),
            ];
        }

        self::logScrapeLine('START job=' . $jobId);

        return ['ok' => true, 'job_id' => $jobId];
    }

    /**
     * @return array{ok: bool, mode?: string, killed?: bool, had_pid?: bool, error?: string}
     */
    public static function cancelJob(string $jobId, bool $force = true): array
    {
        self::ensureScrapeJobs();
        $root = self::root();
        $cj = irScrapeReadJob($jobId, $root);
        if ($cj === null) {
            return ['ok' => false, 'error' => 'job bulunamadı'];
        }
        $cst = (string) ($cj['status'] ?? '');
        if ($cst !== 'queued' && $cst !== 'running') {
            return ['ok' => false, 'error' => 'Bu iş zaten tamamlanmış veya durmuş.'];
        }

        if (!$force) {
            irScrapeUpdateJob($jobId, ['cancel_requested' => 1], $root);
            irScrapeAppendJobLog($jobId, 'Tarayıcıdan iptal isteği alındı.', $root);

            return ['ok' => true, 'mode' => 'graceful'];
        }

        irScrapeUpdateJob($jobId, ['cancel_requested' => 1], $root);
        irScrapeAppendJobLog($jobId, 'Tarayıcıdan zorla durdurma istendi.', $root);

        $pid = isset($cj['worker_pid']) ? (int) $cj['worker_pid'] : 0;
        $killed = false;
        if ($pid > 0) {
            $killed = self::killWorkerProcess($pid);
            irScrapeAppendJobLog(
                $jobId,
                $killed
                    ? ('İşçi süreci sonlandırıldı (PID ' . $pid . ').')
                    : ('PID ' . $pid . ' için kill başarısız veya süreç yok.'),
                $root
            );
        } else {
            irScrapeAppendJobLog($jobId, 'worker_pid yok; iptal bayrağı ayarlandı.', $root);
        }

        irScrapeUpdateJob($jobId, [
            'status' => 'error',
            'step' => 'Zorla durduruldu',
            'sub_step' => $killed ? 'İşçi süreci sonlandırıldı' : ($pid > 0 ? 'Sonlandırma başarısız' : 'PID bilinmiyor'),
            'error' => $killed
                ? 'Scrape işçisi sonlandırıldı. Checkpoint korunur.'
                : 'Sonlandırma başarısız olabilir; iptal bayrağı ayarlı.',
            'finished_at' => date('c'),
            'worker_pid' => null,
        ], $root);
        self::closeOpenImportLog('Scrape kullanıcı tarafından durduruldu');
        self::logScrapeLine('STOP job=' . $jobId . ' killed=' . ($killed ? '1' : '0'));

        return ['ok' => true, 'mode' => 'force', 'killed' => $killed, 'had_pid' => $pid > 0];
    }

    /**
     * @return array{ok: bool, job_id?: string, error?: string}
     */
    private static function spawnScrapeWorker(string $jobId): array
    {
        if (!function_exists('popen')) {
            return ['ok' => false, 'error' => 'popen() devre dışı — php.ini disable_functions kontrol edin.'];
        }

        $phpCliBin = self::resolvePhpCliBinary();
        $workerScript = self::root() . '/' . self::WORKER_SCRIPT_REL;
        if (!is_file($workerScript)) {
            return ['ok' => false, 'error' => 'İşçi betiği bulunamadı: ' . $workerScript];
        }

        $cmd = 'cmd /c start "" /B ' . escapeshellarg($phpCliBin) . ' ' . escapeshellarg($workerScript)
            . ' --job=' . escapeshellarg($jobId);
        $pipe = @popen($cmd, 'r');
        if ($pipe === false) {
            self::ensureScrapeJobs();
            irScrapeUpdateJob($jobId, [
                'status' => 'error',
                'step' => 'Hata',
                'sub_step' => 'Worker başlatılamadı',
                'error' => 'Arka plan PHP işçisi açılamadı (popen). Komut: ' . $cmd,
            ], self::root());

            return ['ok' => false, 'error' => 'Scrape işçisi başlatılamadı (sunucu izni veya PHP CLI yolu kontrol edin).'];
        }
        pclose($pipe);
        self::clearLastError();

        return ['ok' => true, 'job_id' => $jobId];
    }

    private static function resolvePhpCliBinary(): string
    {
        $candidates = [];
        if (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '') {
            $candidates[] = PHP_BINARY;
        }
        if (defined('PHP_BINDIR') && is_string(PHP_BINDIR) && PHP_BINDIR !== '') {
            $candidates[] = rtrim(PHP_BINDIR, '\\/') . DIRECTORY_SEPARATOR . 'php.exe';
            $candidates[] = rtrim(PHP_BINDIR, '\\/') . DIRECTORY_SEPARATOR . 'php';
        }
        $candidates[] = 'C:\\xampp\\php\\php.exe';
        $candidates[] = 'C:\\php\\php.exe';
        $envPhp = getenv('PHP_CLI_BIN');
        if (is_string($envPhp) && $envPhp !== '') {
            $candidates[] = $envPhp;
        }
        foreach ($candidates as $bin) {
            $bin = trim((string) $bin);
            if ($bin === '') {
                continue;
            }
            $name = strtolower((string) pathinfo($bin, PATHINFO_BASENAME));
            if ($name !== 'php.exe' && $name !== 'php') {
                continue;
            }
            if (is_file($bin)) {
                return $bin;
            }
        }

        return 'php';
    }

    private static function execAvailable(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }
        $df = ini_get('disable_functions');
        if (!is_string($df) || trim($df) === '') {
            return true;
        }
        foreach (array_map('trim', explode(',', $df)) as $fn) {
            if (strtolower($fn) === 'exec') {
                return false;
            }
        }

        return true;
    }

    private static function killWorkerProcess(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }
        if (PHP_OS_FAMILY === 'Windows') {
            if (!self::execAvailable()) {
                return false;
            }
            @exec('taskkill /PID ' . $pid . ' /F /T 2>NUL', $unusedOut, $code);

            return $code === 0;
        }
        if (function_exists('posix_kill')) {
            if (@posix_kill($pid, 9)) {
                return true;
            }

            return @posix_kill($pid, 15);
        }
        @exec('kill -9 ' . $pid . ' 2>/dev/null', $unusedOut2, $code2);

        return $code2 === 0;
    }

    private static function logScrapeLine(string $line): void
    {
        $path = self::logFile();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $ts = date('Y-m-d H:i:s');
        @file_put_contents($path, "[{$ts}] {$line}\n", FILE_APPEND | LOCK_EX);
    }

    public static function runWorker(string $jobId): int
    {
        $root = self::root();
        if ($root !== '' && is_dir($root)) {
            @chdir($root);
        }

        self::ensureScrapeJobs();
        $job = irScrapeReadJob($jobId, $root);
        if ($job === null || !is_array($job['config'] ?? null)) {
            $msg = 'Job bulunamadı veya config eksik: ' . $jobId;
            self::writeLastError($msg);
            fwrite(STDERR, $msg . PHP_EOL);

            return 1;
        }

        irScrapeUpdateJob($jobId, [
            'status' => 'running',
            'step' => 'Scrape başlatılıyor',
            'sub_step' => 'İşçi ayağa kalktı',
            'worker_pid' => getmypid(),
            'worker_started_at' => date('c'),
        ], $root);
        irScrapeAppendJobLog($jobId, 'Worker başladı (PID ' . getmypid() . ').', $root);

        $options = $job['config'];
        $phpBin = self::resolvePhpCliBinary();
        $scrapeScript = $root . '/database/scrape_ilac_rehber.php';
        if (!is_file($scrapeScript)) {
            $msg = "Scrape betiği bulunamadı: {$scrapeScript}";
            self::writeLastError($msg);
            irScrapeUpdateJob($jobId, [
                'status' => 'error',
                'step' => 'Hata',
                'error' => $msg,
                'finished_at' => date('c'),
                'worker_pid' => null,
            ], $root);
            fwrite(STDERR, $msg . PHP_EOL);

            return 1;
        }

        $stderrLog = $root . '/logs/ilac_rehber_scrape_stderr.log';
        $parts = [escapeshellarg($phpBin), escapeshellarg($scrapeScript)];
        foreach (self::buildScrapeArgv($options) as $arg) {
            $parts[] = escapeshellarg($arg);
        }
        $cmd = implode(' ', $parts) . ' 2>> ' . escapeshellarg($stderrLog);

        $process = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
        if (!is_resource($process)) {
            $msg = 'Scrape alt süreci açılamadı (proc_open).';
            self::writeLastError($msg);
            irScrapeUpdateJob($jobId, [
                'status' => 'error',
                'step' => 'Hata',
                'error' => $msg,
                'finished_at' => date('c'),
                'worker_pid' => null,
            ], $root);

            return 1;
        }
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $lastCheckpointBump = 0;
        while (true) {
            try {
                irScrapeThrowIfCancelled($jobId, $root);
            } catch (RuntimeException $e) {
                if ($e->getMessage() === ESH_ILAC_REHBER_CANCELLED_MSG) {
                    if (function_exists('proc_terminate')) {
                        @proc_terminate($process);
                    }
                    proc_close($process);
                    self::closeOpenImportLog('Scrape kullanıcı tarafından durduruldu');

                    return 0;
                }
                throw $e;
            }

            $now = time();
            if ($now - $lastCheckpointBump >= 3) {
                $live = irScrapeReadJob($jobId, $root);
                if (is_array($live)) {
                    irScrapeApplyLiveCheckpointProgress($jobId, $live, $root);
                }
                $lastCheckpointBump = $now;
            }

            $stdout = stream_get_contents($pipes[1]);
            if ($stdout !== false && trim($stdout) !== '') {
                foreach (preg_split('/\R/', trim($stdout)) as $line) {
                    if ($line !== '') {
                        irScrapeAppendJobLog($jobId, $line, $root);
                        self::logScrapeLine($line);
                    }
                }
            }
            $stderr = stream_get_contents($pipes[2]);
            if ($stderr !== false && trim($stderr) !== '') {
                foreach (preg_split('/\R/', trim($stderr)) as $line) {
                    if ($line !== '') {
                        irScrapeAppendJobLog($jobId, 'HATA: ' . $line, $root);
                    }
                }
            }

            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            usleep(200000);
        }

        foreach ([1, 2] as $fd) {
            if (isset($pipes[$fd]) && is_resource($pipes[$fd])) {
                fclose($pipes[$fd]);
            }
        }
        $exitCode = proc_close($process);

        $finalJob = irScrapeReadJob($jobId, $root) ?? [];
        if (!empty($finalJob['cancel_requested'])) {
            self::closeOpenImportLog('Scrape kullanıcı tarafından durduruldu');

            return 0;
        }

        if ($exitCode !== 0) {
            self::writeLastError('Scrape betiği çıkış kodu: ' . $exitCode);
            irScrapeUpdateJob($jobId, [
                'status' => 'error',
                'step' => 'Hata',
                'sub_step' => 'Scrape betiği hata ile bitti',
                'error' => 'Scrape çıkış kodu: ' . $exitCode,
                'finished_at' => date('c'),
                'worker_pid' => null,
            ], $root);
            self::closeOpenImportLog('Scrape hata ile bitti (kod ' . $exitCode . ')');

            return (int) $exitCode;
        }

        irScrapeUpdateJob($jobId, [
            'status' => 'success',
            'step' => 'Tamamlandı',
            'sub_step' => 'Scrape başarıyla bitti',
            'progress' => 100,
            'sub_progress' => 100,
            'finished_at' => date('c'),
            'worker_pid' => null,
        ], $root);
        irScrapeAppendJobLog($jobId, 'Scrape tamamlandı.', $root);

        return 0;
    }
}
