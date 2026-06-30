<?php
declare(strict_types=1);

/**
 * İlaç rehberi scrape ilerleme checkpoint (storage/ilac_rehber_scrape.checkpoint.json).
 */

function ilacRehberCheckpointPath(string $projectRoot): string
{
    $dir = rtrim($projectRoot, '\\/') . DIRECTORY_SEPARATOR . 'storage';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    return $dir . DIRECTORY_SEPARATOR . 'ilac_rehber_scrape.checkpoint.json';
}

/**
 * @return array<string, mixed>|null
 */
function ilacRehberCheckpointRead(string $projectRoot): ?array
{
    $path = ilacRehberCheckpointPath($projectRoot);
    if (!is_file($path)) {
        return null;
    }
    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return null;
    }
    $data = json_decode($raw, true);

    return is_array($data) ? $data : null;
}

/**
 * @param array<string, mixed> $data
 */
function ilacRehberCheckpointWrite(string $projectRoot, array $data): void
{
    $data['updated_at'] = date('c');
    $path = ilacRehberCheckpointPath($projectRoot);
    @file_put_contents(
        $path,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        LOCK_EX
    );
}

function ilacRehberCheckpointClear(string $projectRoot): void
{
    $path = ilacRehberCheckpointPath($projectRoot);
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * @param array<string, mixed> $config site, discovery, id_max
 * @return array{seed_index: int, id_cursor: int, warnings: list<string>, used: bool}
 */
function ilacRehberCheckpointResolveResume(
    ?array $checkpoint,
    array $config,
    bool $wantResume
): array {
    $seedIndex = 0;
    $idCursor = 1;
    $warnings = [];
    $used = false;

    if (!$wantResume || $checkpoint === null) {
        return ['seed_index' => $seedIndex, 'id_cursor' => $idCursor, 'warnings' => $warnings, 'used' => $used];
    }

    $site = (string) ($config['site'] ?? '');
    $discovery = (string) ($config['discovery'] ?? 'both');
    $idMax = max(1, (int) ($config['id_max'] ?? 15000));

    $cpSite = (string) ($checkpoint['site'] ?? '');
    if ($cpSite !== $site) {
        $warnings[] = "Checkpoint site ({$cpSite}) ≠ {$site}; baştan başlanıyor.";

        return ['seed_index' => 0, 'id_cursor' => 1, 'warnings' => $warnings, 'used' => false];
    }

    $cpDiscovery = (string) ($checkpoint['discovery'] ?? '');
    $cpIdMax = max(1, (int) ($checkpoint['id_max'] ?? 15000));
    $cpSeedIndex = max(0, (int) ($checkpoint['seed_index'] ?? 0));
    $cpIdCursor = max(1, (int) ($checkpoint['id_cursor'] ?? 1));

    $idMaxChanged = $cpIdMax !== $idMax;
    if ($idMaxChanged) {
        $warnings[] = "id-max değişti ({$cpIdMax} → {$idMax}); Id taraması sıfırlandı.";
        $idCursor = 1;
    } else {
        $idCursor = $cpIdCursor;
    }

    if ($cpDiscovery !== $discovery) {
        $warnings[] = "discovery değişti ({$cpDiscovery} → {$discovery}); uyumsuz aşama sıfırlandı.";
        if ($discovery === 'seeds') {
            $seedIndex = $cpSeedIndex;
            $idCursor = 1;
        } elseif ($discovery === 'ids') {
            $seedIndex = 0;
            if (!$idMaxChanged) {
                $idCursor = $cpIdCursor;
            }
        } else {
            if ($cpDiscovery === 'seeds') {
                $seedIndex = $cpSeedIndex;
                $idCursor = 1;
            } elseif ($cpDiscovery === 'ids') {
                $seedIndex = 0;
                if (!$idMaxChanged) {
                    $idCursor = $cpIdCursor;
                }
            } else {
                $seedIndex = $cpSeedIndex;
                if (!$idMaxChanged) {
                    $idCursor = $cpIdCursor;
                }
            }
        }
    } else {
        $seedIndex = $cpSeedIndex;
        if (!$idMaxChanged) {
            $idCursor = $cpIdCursor;
        }
    }

    if ($discovery === 'seeds') {
        $idCursor = 1;
    } elseif ($discovery === 'ids') {
        $seedIndex = 0;
    }

    $used = $seedIndex > 0 || $idCursor > 1;

    return ['seed_index' => $seedIndex, 'id_cursor' => $idCursor, 'warnings' => $warnings, 'used' => $used];
}

/**
 * @param array<string, mixed> $base
 * @return array<string, mixed>
 */
function ilacRehberCheckpointNewState(array $base): array
{
    return array_merge([
        'version' => 1,
        'site' => 'ilacabak',
        'discovery' => 'both',
        'id_max' => 15000,
        'seed_index' => 0,
        'seed_total' => 0,
        'id_cursor' => 1,
        'etken_processed' => 0,
        'started_at' => date('c'),
        'updated_at' => date('c'),
        'completed' => false,
    ], $base);
}

/**
 * @return array<string, mixed>|null Özet (UI / status)
 */
function ilacRehberCheckpointSummary(string $projectRoot): ?array
{
    $cp = ilacRehberCheckpointRead($projectRoot);
    if ($cp === null || !empty($cp['completed'])) {
        return null;
    }

    return [
        'site' => (string) ($cp['site'] ?? ''),
        'discovery' => (string) ($cp['discovery'] ?? ''),
        'id_max' => (int) ($cp['id_max'] ?? 0),
        'seed_index' => (int) ($cp['seed_index'] ?? 0),
        'seed_total' => (int) ($cp['seed_total'] ?? 0),
        'id_cursor' => (int) ($cp['id_cursor'] ?? 1),
        'etken_processed' => (int) ($cp['etken_processed'] ?? 0),
        'updated_at' => $cp['updated_at'] ?? null,
    ];
}

/**
 * Keşif aşaması yüzdesi (0–100).
 */
function ilacRehberCheckpointPhasePercent(int $index, int $total): int
{
    if ($total <= 0) {
        return 0;
    }

    return min(100, (int) floor(($index * 100) / $total));
}

/**
 * Id taraması: cursor bir sonraki Id; tamamlanan = cursor - 1.
 */
function ilacRehberCheckpointIdPercent(int $idCursor, int $idMax): int
{
    if ($idMax <= 0) {
        return 0;
    }
    $done = max(0, min($idMax, $idCursor - 1));

    return min(100, (int) floor(($done * 100) / $idMax));
}

/**
 * @param array<string, mixed>|null $jobOptions Çalışan iş seçenekleri (checkpoint henüz yokken)
 * @return array{
 *   phase: string,
 *   progress_seed_index: int,
 *   progress_seed_total: int,
 *   progress_seed_percent: int,
 *   progress_id_cursor: int,
 *   progress_id_max: int,
 *   progress_id_percent: int,
 *   progress_overall_percent: int,
 *   etken_processed: int,
 *   checkpoint_resume: string|null
 * }
 */
function ilacRehberCheckpointProgress(string $projectRoot, bool $running, ?array $jobOptions = null): array
{
    $cp = ilacRehberCheckpointRead($projectRoot);
    $discovery = strtolower((string) ($cp['discovery'] ?? $jobOptions['discovery'] ?? 'both'));
    if (!in_array($discovery, ['seeds', 'ids', 'both'], true)) {
        $discovery = 'both';
    }

    $seedIndex = max(0, (int) ($cp['seed_index'] ?? 0));
    $seedTotal = max(0, (int) ($cp['seed_total'] ?? 0));
    $idCursor = max(1, (int) ($cp['id_cursor'] ?? 1));
    $idMax = max(1, (int) ($cp['id_max'] ?? $jobOptions['id_max'] ?? 15000));
    $etkenProcessed = max(0, (int) ($cp['etken_processed'] ?? 0));

    $seedPercent = ilacRehberCheckpointPhasePercent($seedIndex, $seedTotal);
    $idPercent = ilacRehberCheckpointIdPercent($idCursor, $idMax);

    $seedsComplete = $seedTotal > 0 && $seedIndex >= $seedTotal;
    $idsComplete = $idCursor > $idMax;
    $includesSeeds = $discovery === 'seeds' || $discovery === 'both';
    $includesIds = $discovery === 'ids' || $discovery === 'both';

    $phase = 'idle';
    $derivePhase = static function () use (
        $includesSeeds,
        $includesIds,
        $seedsComplete,
        $idsComplete
    ): string {
        if ($includesSeeds && !$seedsComplete) {
            return 'seeds';
        }
        if ($includesIds && !$idsComplete) {
            return 'ids';
        }

        return 'done';
    };

    if ($running) {
        $phase = $derivePhase();
    } elseif ($cp !== null && empty($cp['completed'])) {
        $phase = $derivePhase();
        if ($phase === 'done') {
            $phase = 'idle';
        }
    }

    $overallPercent = 0;
    if ($discovery === 'seeds') {
        $overallPercent = $seedPercent;
    } elseif ($discovery === 'ids') {
        $overallPercent = $idPercent;
    } elseif ($seedTotal > 0 && $idMax > 0) {
        $weightSeed = $seedTotal;
        $weightId = $idMax;
        $doneSeed = min($seedIndex, $seedTotal);
        $doneId = max(0, min($idMax, $idCursor - 1));
        $overallPercent = min(
            100,
            (int) floor((($doneSeed + $doneId) * 100) / ($weightSeed + $weightId))
        );
    } elseif ($includesSeeds) {
        $overallPercent = $seedPercent;
    } else {
        $overallPercent = $idPercent;
    }

    $resumeParts = [];
    if ($cp !== null && !$running) {
        if ($includesSeeds && $seedTotal > 0 && !$seedsComplete) {
            $resumeParts[] = 'Seed ' . $seedIndex . '/' . $seedTotal;
        }
        if ($includesIds && !$idsComplete) {
            $resumeParts[] = 'Id ' . min($idCursor, $idMax) . '/' . $idMax;
        }
        if ($etkenProcessed > 0) {
            $resumeParts[] = 'etken işlenen ' . $etkenProcessed;
        }
    } elseif ($running && $cp !== null) {
        if ($phase === 'seeds' && $seedTotal > 0) {
            $resumeParts[] = 'canliArama ' . $seedIndex . '/' . $seedTotal;
        } elseif ($phase === 'ids') {
            $resumeParts[] = 'Id ' . min($idCursor, $idMax) . '/' . $idMax;
        }
    }

    $checkpointResume = $resumeParts === []
        ? null
        : ($running ? 'Devam ediyor: ' : 'Kaldığı yerden devam: ') . implode(' · ', $resumeParts);

    return [
        'stopped' => !$running && $cp !== null && empty($cp['completed']),
        'phase' => $phase,
        'progress_seed_index' => $seedIndex,
        'progress_seed_total' => $seedTotal,
        'progress_seed_percent' => $seedPercent,
        'progress_id_cursor' => $idCursor,
        'progress_id_max' => $idMax,
        'progress_id_percent' => $idPercent,
        'progress_overall_percent' => $overallPercent,
        'etken_processed' => $etkenProcessed,
        'checkpoint_resume' => $checkpointResume,
    ];
}

function ilacRehberWorkerIsRunning(string $projectRoot): bool
{
    require_once __DIR__ . '/ilac_rehber_scrape_jobs.php';

    return ilacRehberScrapeWorkerIsRunning($projectRoot);
}

function ilacRehberSyncImportLogIfWorkerStopped(string $projectRoot): void
{
    require_once __DIR__ . '/ilac_rehber_import_log.php';
    ilacRehberImportSyncIfWorkerStopped($projectRoot);
}
