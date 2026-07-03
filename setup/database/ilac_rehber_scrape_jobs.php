<?php
declare(strict_types=1);

/**
 * İlaç rehberi scrape işçisi — storage/ilac_rehber_jobs/{id}.json (migration_jobs deseni).
 */

const ESH_ILAC_REHBER_CANCELLED_MSG = 'ILAC_REHBER_SCRAPE_CANCELLED';

function irScrapeJobsProjectRoot(?string $override = null): string
{
    if ($override !== null && $override !== '') {
        return rtrim($override, '\\/');
    }
    $resolved = realpath(dirname(__DIR__));

    return ($resolved !== false) ? $resolved : dirname(__DIR__);
}

function irScrapeJobsStateDir(?string $projectRoot = null): string
{
    $dir = irScrapeJobsProjectRoot($projectRoot)
        . DIRECTORY_SEPARATOR . 'storage'
        . DIRECTORY_SEPARATOR . 'ilac_rehber_jobs';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    return $dir;
}

function irScrapeJobFile(string $jobId, ?string $projectRoot = null): string
{
    return irScrapeJobsStateDir($projectRoot) . DIRECTORY_SEPARATOR . $jobId . '.json';
}

function irScrapeReadJob(string $jobId, ?string $projectRoot = null): ?array
{
    $file = irScrapeJobFile($jobId, $projectRoot);
    if (!is_file($file)) {
        return null;
    }
    $raw = (string) file_get_contents($file);
    $data = json_decode($raw, true);

    return is_array($data) ? $data : null;
}

function irScrapeWriteJob(string $jobId, array $data, bool $bumpUpdatedAt = true, ?string $projectRoot = null): void
{
    if ($bumpUpdatedAt) {
        $data['updated_at'] = date('c');
    } else {
        $onDisk = irScrapeReadJob($jobId, $projectRoot);
        if (is_array($onDisk) && isset($onDisk['updated_at'])) {
            $data['updated_at'] = $onDisk['updated_at'];
        } else {
            $data['updated_at'] = date('c');
        }
    }
    file_put_contents(
        irScrapeJobFile($jobId, $projectRoot),
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

function irScrapeAppendJobLog(string $jobId, string $line, ?string $projectRoot = null): void
{
    $job = irScrapeReadJob($jobId, $projectRoot) ?? [];
    $logs = $job['logs'] ?? [];
    $logs[] = '[' . date('H:i:s') . '] ' . $line;
    $job['logs'] = array_slice($logs, -120);
    irScrapeWriteJob($jobId, $job, true, $projectRoot);
}

function irScrapeUpdateJob(string $jobId, array $patch, ?string $projectRoot = null): void
{
    $job = irScrapeReadJob($jobId, $projectRoot) ?? [];
    foreach ($patch as $k => $v) {
        $job[$k] = $v;
    }
    irScrapeWriteJob($jobId, $job, true, $projectRoot);
}

function irScrapeThrowIfCancelled(string $jobId, ?string $projectRoot = null): void
{
    $job = irScrapeReadJob($jobId, $projectRoot);
    if ($job === null || empty($job['cancel_requested'])) {
        return;
    }
    irScrapeAppendJobLog($jobId, 'İptal istendi; scrape sonlandırılıyor.', $projectRoot);
    irScrapeUpdateJob($jobId, [
        'status' => 'error',
        'step' => 'İptal edildi',
        'sub_step' => 'Kullanıcı tarafından durduruldu',
        'error' => 'İş kullanıcı tarafından iptal edildi.',
        'cancel_requested' => 1,
        'finished_at' => date('c'),
        'worker_pid' => null,
    ], $projectRoot);
    throw new RuntimeException(ESH_ILAC_REHBER_CANCELLED_MSG);
}

/**
 * @return array{id: string, job: array}|null
 */
function irScrapeFindLatestActiveJob(?string $projectRoot = null): ?array
{
    $dir = irScrapeJobsStateDir($projectRoot);
    $files = glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [];
    $bestTs = '';
    $bestId = null;
    $bestJob = null;
    foreach ($files as $file) {
        $base = basename((string) $file, '.json');
        if ($base === '' || !preg_match('/^[a-f0-9]{24}$/', $base)) {
            continue;
        }
        $raw = @file_get_contents($file);
        if ($raw === false || $raw === '') {
            continue;
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            continue;
        }
        $status = (string) ($data['status'] ?? '');
        if ($status !== 'queued' && $status !== 'running') {
            continue;
        }
        $ts = (string) ($data['updated_at'] ?? $data['created_at'] ?? '');
        if ($ts >= $bestTs) {
            $bestTs = $ts;
            $bestId = $base;
            $bestJob = $data;
        }
    }
    if ($bestId === null || !is_array($bestJob)) {
        return null;
    }

    return ['id' => $bestId, 'job' => $bestJob];
}

function irScrapeJobIsActive(array $job): bool
{
    $st = (string) ($job['status'] ?? '');

    return $st === 'queued' || $st === 'running';
}

/**
 * Worker hiç ayağa kalkmadıysa (queued takılı) kısa süre sonra işi hata olarak kapatır.
 */
function irScrapeApplyStaleQueuedGuard(string $jobId, array $job, ?string $projectRoot = null): array
{
    if ((string) ($job['status'] ?? '') !== 'queued') {
        return $job;
    }
    $anchorRaw = (string) ($job['updated_at'] ?? $job['created_at'] ?? '');
    if ($anchorRaw === '') {
        return $job;
    }
    $anchorTs = strtotime($anchorRaw);
    if ($anchorTs === false || (time() - $anchorTs) < 90) {
        return $job;
    }

    $logs = is_array($job['logs'] ?? null) ? $job['logs'] : [];
    $logs[] = '[' . date('H:i:s') . '] HATA: İş sırada kaldı; worker başlamadı (' . (time() - $anchorTs) . ' sn).';
    $job['logs'] = array_slice($logs, -120);
    $job['status'] = 'error';
    $job['step'] = 'Hata';
    $job['sub_step'] = 'Worker başlamadı';
    $job['error'] = 'İş kuyrukta takıldı (worker başlamadı). Tekrar «Scrape başlat» deneyin.';
    $job['finished_at'] = date('c');
    $job['worker_pid'] = null;
    irScrapeWriteJob($jobId, $job, true, $projectRoot);

    return $job;
}

function irScrapeApplyStaleJobGuard(string $jobId, array $job, ?string $projectRoot = null): array
{
    if (($job['status'] ?? '') !== 'running') {
        return $job;
    }
    $updatedAtRaw = (string) ($job['updated_at'] ?? '');
    if ($updatedAtRaw === '') {
        return $job;
    }
    $updatedTs = strtotime($updatedAtRaw);
    if ($updatedTs === false) {
        return $job;
    }
    $ageSec = time() - $updatedTs;
    if ($ageSec < 600) {
        return $job;
    }

    $logs = is_array($job['logs'] ?? null) ? $job['logs'] : [];
    $logs[] = '[' . date('H:i:s') . '] HATA: Job takıldı veya worker durdu (son güncelleme ' . $ageSec . ' sn önce).';
    $job['logs'] = array_slice($logs, -120);
    $job['status'] = 'error';
    $job['step'] = 'Hata';
    $job['sub_step'] = 'Worker durdu veya zaman aşımı';
    $job['error'] = 'Job takıldı: worker süreci bulunamadı veya 10 dk güncelleme gelmedi.';
    $job['finished_at'] = date('c');
    $job['worker_pid'] = null;
    irScrapeWriteJob($jobId, $job, true, $projectRoot);

    return $job;
}

/**
 * Checkpoint dosyasından job ilerlemesini günceller (status poll ve worker döngüsü).
 */
function irScrapeApplyLiveCheckpointProgress(string $jobId, array $job, ?string $projectRoot = null): array
{
    if (($job['status'] ?? '') !== 'running') {
        return $job;
    }
    require_once __DIR__ . '/ilac_rehber_checkpoint.php';
    $root = irScrapeJobsProjectRoot($projectRoot);
    $config = is_array($job['config'] ?? null) ? $job['config'] : [];
    $progress = ilacRehberCheckpointProgress($root, true, $config);
    $phase = (string) ($progress['phase'] ?? 'idle');
    $overall = (int) ($progress['progress_overall_percent'] ?? 0);
    $seedPct = (int) ($progress['progress_seed_percent'] ?? 0);
    $idPct = (int) ($progress['progress_id_percent'] ?? 0);

    $step = 'Scrape çalışıyor';
    $subStep = (string) ($progress['checkpoint_resume'] ?? 'İlerleme izleniyor');
    $subProgress = 0;
    if ($phase === 'seeds') {
        $step = 'Seed taraması';
        $subProgress = $seedPct;
    } elseif ($phase === 'ids') {
        $step = 'Id taraması';
        $subProgress = $idPct;
    } elseif ($phase === 'done') {
        $step = 'Tamamlandı';
        $subProgress = 100;
        $overall = 100;
    }

    $job['progress'] = max((int) ($job['progress'] ?? 0), $overall);
    $job['sub_progress'] = max((int) ($job['sub_progress'] ?? 0), $subProgress);
    $job['step'] = $step;
    $job['sub_step'] = $subStep;
    $job['scrape_phase'] = $phase;
    $job['etken_processed'] = (int) ($progress['etken_processed'] ?? 0);
    irScrapeWriteJob($jobId, $job, true, $projectRoot);

    return $job;
}

function ilacRehberScrapeWorkerIsRunning(string $projectRoot): bool
{
    $found = irScrapeFindLatestActiveJob($projectRoot);
    if ($found === null) {
        return false;
    }
    $job = irScrapeApplyStaleJobGuard($found['id'], $found['job'], $projectRoot);
    $job = irScrapeApplyLiveCheckpointProgress($found['id'], $job, $projectRoot);

    return irScrapeJobIsActive($job);
}
