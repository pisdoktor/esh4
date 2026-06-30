<?php
declare(strict_types=1);

/**
 * İlaç rehberi scrape import günlüğü — JSON dosya deposu (MySQL esh_rehber_import_log kullanılmaz).
 * migration.php / storage/migration_jobs desenine benzer.
 */

function ilacRehberImportJobsDir(string $projectRoot): string
{
    $dir = rtrim($projectRoot, '\\/') . DIRECTORY_SEPARATOR . 'storage'
        . DIRECTORY_SEPARATOR . 'ilac_rehber_import_jobs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    return $dir;
}

function ilacRehberImportCurrentPath(string $projectRoot): string
{
    return rtrim($projectRoot, '\\/') . DIRECTORY_SEPARATOR . 'storage'
        . DIRECTORY_SEPARATOR . 'ilac_rehber_import_current.json';
}

function ilacRehberImportJobFile(string $projectRoot, string $jobId): string
{
    $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', $jobId) ?? '';

    return ilacRehberImportJobsDir($projectRoot) . DIRECTORY_SEPARATOR . $safe . '.json';
}

/**
 * @return array<string, mixed>|null
 */
function ilacRehberImportReadJob(string $projectRoot, string $jobId): ?array
{
    $file = ilacRehberImportJobFile($projectRoot, $jobId);
    if (!is_file($file)) {
        return null;
    }
    $raw = @file_get_contents($file);
    if ($raw === false || $raw === '') {
        return null;
    }
    $data = json_decode($raw, true);

    return is_array($data) ? $data : null;
}

/**
 * @param array<string, mixed> $data
 */
function ilacRehberImportWriteJob(string $projectRoot, string $jobId, array $data): void
{
    $data['id'] = $jobId;
    $data['updated_at'] = date('c');
    file_put_contents(
        ilacRehberImportJobFile($projectRoot, $jobId),
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

function ilacRehberImportSetCurrent(string $projectRoot, string $jobId): void
{
    file_put_contents(
        ilacRehberImportCurrentPath($projectRoot),
        json_encode(['job_id' => $jobId, 'updated_at' => date('c')], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

function ilacRehberImportClearCurrent(string $projectRoot, ?string $jobId = null): void
{
    $path = ilacRehberImportCurrentPath($projectRoot);
    if (!is_file($path)) {
        return;
    }
    if ($jobId !== null) {
        $raw = @file_get_contents($path);
        if ($raw !== false && $raw !== '') {
            $cur = json_decode($raw, true);
            if (is_array($cur) && (string) ($cur['job_id'] ?? '') !== $jobId) {
                return;
            }
        }
    }
    @unlink($path);
}

/**
 * @return list<array<string, mixed>>
 */
function ilacRehberImportLoadAllJobs(string $projectRoot): array
{
    $files = glob(ilacRehberImportJobsDir($projectRoot) . DIRECTORY_SEPARATOR . '*.json') ?: [];
    $jobs = [];
    foreach ($files as $file) {
        $raw = @file_get_contents((string) $file);
        if ($raw === false || $raw === '') {
            continue;
        }
        $data = json_decode($raw, true);
        if (is_array($data) && isset($data['id'])) {
            $jobs[] = $data;
        }
    }

    return $jobs;
}

function ilacRehberImportJobStartedTs(array $job): string
{
    return (string) ($job['started_at'] ?? $job['updated_at'] ?? '');
}

function ilacRehberImportJobFinishedTs(array $job): string
{
    return (string) ($job['finished_at'] ?? '');
}

function ilacRehberImportIsOpenJob(array $job): bool
{
    $status = (string) ($job['status'] ?? '');
    if ($status === 'running') {
        return true;
    }

    return ($job['finished_at'] ?? null) === null || $job['finished_at'] === '';
}

/**
 * UI / model uyumluluğu için stdClass (source_site alanı dahil).
 *
 * @param array<string, mixed> $job
 */
function ilacRehberImportToObject(array $job): object
{
    return (object) [
        'id' => (string) ($job['id'] ?? ''),
        'source_site' => (string) ($job['site'] ?? ''),
        'site' => (string) ($job['site'] ?? ''),
        'discovery' => (string) ($job['discovery'] ?? ''),
        'started_at' => $job['started_at'] ?? null,
        'finished_at' => $job['finished_at'] ?? null,
        'etken_count' => (int) ($job['etken_count'] ?? 0),
        'ilac_count' => (int) ($job['ilac_count'] ?? 0),
        'error_summary' => $job['error_summary'] ?? null,
        'options_json' => $job['options_json'] ?? null,
        'status' => (string) ($job['status'] ?? ''),
    ];
}

function ilacRehberImportGetLatest(string $projectRoot): ?object
{
    $jobs = ilacRehberImportLoadAllJobs($projectRoot);
    if ($jobs === []) {
        return null;
    }
    usort($jobs, static function (array $a, array $b): int {
        return strcmp(ilacRehberImportJobStartedTs($b), ilacRehberImportJobStartedTs($a));
    });

    return ilacRehberImportToObject($jobs[0]);
}

function ilacRehberImportGetLatestCompleted(string $projectRoot): ?object
{
    $jobs = ilacRehberImportLoadAllJobs($projectRoot);
    $completed = array_values(array_filter(
        $jobs,
        static fn (array $j): bool => !ilacRehberImportIsOpenJob($j) && ilacRehberImportJobFinishedTs($j) !== ''
    ));
    if ($completed === []) {
        return null;
    }
    usort($completed, static function (array $a, array $b): int {
        $cmp = strcmp(ilacRehberImportJobFinishedTs($b), ilacRehberImportJobFinishedTs($a));
        if ($cmp !== 0) {
            return $cmp;
        }

        return strcmp(ilacRehberImportJobStartedTs($b), ilacRehberImportJobStartedTs($a));
    });

    return ilacRehberImportToObject($completed[0]);
}

function ilacRehberImportGetOpen(string $projectRoot): ?object
{
    $jobs = ilacRehberImportLoadAllJobs($projectRoot);
    $open = array_values(array_filter($jobs, 'ilacRehberImportIsOpenJob'));
    if ($open === []) {
        return null;
    }
    usort($open, static function (array $a, array $b): int {
        return strcmp(ilacRehberImportJobStartedTs($b), ilacRehberImportJobStartedTs($a));
    });

    return ilacRehberImportToObject($open[0]);
}

/**
 * @param array<string, mixed> $options
 */
function ilacRehberImportCreateJob(string $projectRoot, string $site, array $options): string
{
    $id = bin2hex(random_bytes(12));
    $discovery = (string) ($options['discovery'] ?? '');
    $job = [
        'id' => $id,
        'started_at' => date('c'),
        'finished_at' => null,
        'site' => $site,
        'discovery' => $discovery,
        'options_json' => json_encode($options, JSON_UNESCAPED_UNICODE),
        'etken_count' => 0,
        'ilac_count' => 0,
        'error_summary' => null,
        'status' => 'running',
    ];
    ilacRehberImportWriteJob($projectRoot, $id, $job);
    ilacRehberImportSetCurrent($projectRoot, $id);

    return $id;
}

function ilacRehberImportFinishJob(
    string $projectRoot,
    string $jobId,
    int $etkenCount,
    int $ilacCount,
    ?string $errorSummary
): void {
    $job = ilacRehberImportReadJob($projectRoot, $jobId);
    if ($job === null) {
        return;
    }
    $job['finished_at'] = date('c');
    $job['etken_count'] = $etkenCount;
    $job['ilac_count'] = $ilacCount;
    $job['error_summary'] = $errorSummary;
    $job['status'] = $errorSummary !== null && $errorSummary !== '' ? 'completed_with_errors' : 'completed';
    ilacRehberImportWriteJob($projectRoot, $jobId, $job);
    ilacRehberImportClearCurrent($projectRoot, $jobId);
}

/**
 * Açık işi kapatır; sayılar verilmezse $countEtken / $countIlac çağrılır.
 */
function ilacRehberImportFinalizeOpen(
    string $projectRoot,
    string $errorSummary,
    ?callable $countEtken = null,
    ?callable $countIlac = null
): int {
    $open = ilacRehberImportGetOpen($projectRoot);
    if ($open === null) {
        return 0;
    }
    $jobId = (string) $open->id;
    $job = ilacRehberImportReadJob($projectRoot, $jobId);
    if ($job === null || !ilacRehberImportIsOpenJob($job)) {
        return 0;
    }
    $etken = $countEtken !== null ? (int) $countEtken() : (int) ($job['etken_count'] ?? 0);
    $ilac = $countIlac !== null ? (int) $countIlac() : (int) ($job['ilac_count'] ?? 0);
    $job['finished_at'] = date('c');
    $job['etken_count'] = $etken;
    $job['ilac_count'] = $ilac;
    $job['error_summary'] = $errorSummary;
    $job['status'] = 'closed';
    ilacRehberImportWriteJob($projectRoot, $jobId, $job);
    ilacRehberImportClearCurrent($projectRoot, $jobId);

    return 1;
}

function ilacRehberImportClearAll(string $projectRoot): void
{
    foreach (glob(ilacRehberImportJobsDir($projectRoot) . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
        @unlink((string) $file);
    }
    @unlink(ilacRehberImportCurrentPath($projectRoot));
}

function ilacRehberImportSyncIfWorkerStopped(string $projectRoot): void
{
    require_once __DIR__ . '/ilac_rehber_checkpoint.php';
    if (ilacRehberWorkerIsRunning($projectRoot)) {
        return;
    }
    $open = ilacRehberImportGetOpen($projectRoot);
    if ($open === null) {
        return;
    }
    $countEtken = null;
    $countIlac = null;
    if (defined('ROOT_PATH') || is_file(rtrim($projectRoot, '\\/') . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php')) {
        if (!defined('ROOT_PATH')) {
            require_once rtrim($projectRoot, '\\/') . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
        }
        if (!class_exists(\App\Models\RehberEtken::class, false)) {
            spl_autoload_register(static function (string $class) use ($projectRoot): void {
                $prefix = 'App\\';
                $baseDir = rtrim($projectRoot, '\\/') . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;
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
        }
        try {
            $etkenModel = new \App\Models\RehberEtken();
            $ilacModel = new \App\Models\RehberIlac();
            $countEtken = static fn (): int => $etkenModel->countAll();
            $countIlac = static fn (): int => $ilacModel->countAll();
        } catch (Throwable $e) {
            $countEtken = null;
            $countIlac = null;
        }
    }
    ilacRehberImportFinalizeOpen(
        $projectRoot,
        'Scrape süreci çalışmıyor (otomatik kapatıldı)',
        $countEtken,
        $countIlac
    );
}
