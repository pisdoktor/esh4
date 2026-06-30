<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * İlaç rehberi import günlüğü — storage/ilac_rehber_import_jobs/*.json
 */
final class IlacRehberImportStore
{
    public const JOBS_REL = 'storage/ilac_rehber_import_jobs';

    public const CURRENT_REL = 'storage/ilac_rehber_import_current.json';

    private static function root(): string
    {
        return rtrim((string) ROOT_PATH, '/\\');
    }

    private static function ensureHelpers(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;
        require_once self::root() . '/database/ilac_rehber_import_log.php';
    }

    public static function getLatestImportLog(): ?object
    {
        self::ensureHelpers();

        return ilacRehberImportGetLatest(self::root());
    }

    public static function getLatestCompletedImportLog(): ?object
    {
        self::ensureHelpers();

        return ilacRehberImportGetLatestCompleted(self::root());
    }

    public static function getOpenImportLog(): ?object
    {
        self::ensureHelpers();

        return ilacRehberImportGetOpen(self::root());
    }

    public static function finalizeOpenImportLog(string $errorSummary): int
    {
        self::ensureHelpers();
        $etkenModel = new \App\Models\RehberEtken();
        $ilacModel = new \App\Models\RehberIlac();

        return ilacRehberImportFinalizeOpen(
            self::root(),
            $errorSummary,
            static fn (): int => $etkenModel->countAll(),
            static fn (): int => $ilacModel->countAll()
        );
    }

    public static function syncIfWorkerStopped(): void
    {
        self::ensureHelpers();
        ilacRehberImportSyncIfWorkerStopped(self::root());
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function createJob(string $site, array $options): string
    {
        self::ensureHelpers();

        return ilacRehberImportCreateJob(self::root(), $site, $options);
    }

    public static function finishJob(
        string $jobId,
        int $etkenCount,
        int $ilacCount,
        ?string $errorSummary
    ): void {
        self::ensureHelpers();
        ilacRehberImportFinishJob(self::root(), $jobId, $etkenCount, $ilacCount, $errorSummary);
    }

    public static function clearAll(): void
    {
        self::ensureHelpers();
        ilacRehberImportClearAll(self::root());
    }

    public static function isImportInProgress(bool $workerRunning): bool
    {
        if (!$workerRunning) {
            return false;
        }
        $open = self::getOpenImportLog();

        return $open !== null;
    }
}
