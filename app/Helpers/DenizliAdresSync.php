<?php
namespace App\Helpers;

use App\Core\Database;
use RuntimeException;
use SimpleXMLElement;

/**
 * Denizli Büyükşehir adres ağacı senkronu (adres.denizli.bel.tr).
 * İlçe listesi: veriHazirla.ashx?t=belediye
 * Mahalle/sokak/kapı: id={GUID}&t=mahalle|sokak|kapi
 */
class DenizliAdresSync {

    public const API_BASE = 'https://adres.denizli.bel.tr/veriHazirla.ashx';

    private const JOB_SUBDIR = 'adres_fetch_jobs';

    private const PHASE_ILCE = 'ilce';
    private const PHASE_MAHALLE = 'mahalle';
    private const PHASE_SOKAK = 'sokak';
    private const PHASE_KAPI = 'kapi';
    private const PHASE_DONE = 'done';

    public static function jobsDirectory(): string {
        $dir = ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . self::JOB_SUBDIR;
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }

    /**
     * @return array<string, int>
     */
    public static function getTableCounts(): array {
        $db = Database::getInstance();
        $rows = $db->fetchAllPrepared(
            'SELECT tip, COUNT(*) AS c FROM #__adrestablosu GROUP BY tip'
        );
        $out = ['ilce' => 0, 'mahalle' => 0, 'sokak' => 0, 'kapino' => 0];
        foreach ($rows as $row) {
            $tip = (string) ($row['tip'] ?? '');
            if (isset($out[$tip])) {
                $out[$tip] = (int) ($row['c'] ?? 0);
            }
        }
        return $out;
    }

    /**
     * @return list<array{id: string, adi: string}>
     */
    public static function fetchIlceListFromApi(): array {
        $sx = self::fetchXml('', 'belediye');
        if ($sx === null) {
            throw new RuntimeException('İlçe listesi (belediye) API yanıtı okunamadı.');
        }
        $list = [];
        foreach ($sx->Belediye as $node) {
            $id = trim((string) ($node->ID ?? ''));
            $adi = trim((string) ($node->ADI ?? ''));
            if ($id === '' || $adi === '') {
                continue;
            }
            $list[] = ['id' => $id, 'adi' => $adi];
        }
        if ($list === []) {
            throw new RuntimeException('İlçe listesi boş döndü.');
        }
        usort($list, static function (array $a, array $b): int {
            return strcasecmp($a['adi'], $b['adi']);
        });
        return $list;
    }

    /**
     * Durdurulmuş veya hatalı, tamamlanmamış son işi döndürür (kaldığı yerden devam için).
     *
     * @return array<string, mixed>|null
     */
    public static function findResumableJob(): ?array {
        $dir = self::jobsDirectory();
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.json');
        if (!is_array($files) || $files === []) {
            return null;
        }
        usort($files, static function (string $a, string $b): int {
            return filemtime($b) <=> filemtime($a);
        });
        foreach ($files as $file) {
            $raw = @file_get_contents($file);
            if ($raw === false) {
                continue;
            }
            $job = json_decode($raw, true);
            if (!is_array($job) || !self::isJobResumable($job)) {
                continue;
            }
            return $job;
        }
        return null;
    }

    /**
     * @param array<string, mixed> $job
     */
    public static function isJobResumable(array $job): bool {
        $status = (string) ($job['status'] ?? '');
        if (!in_array($status, ['cancelled', 'error'], true)) {
            return false;
        }
        $phase = (string) ($job['phase'] ?? '');
        if ($phase === self::PHASE_DONE) {
            return false;
        }
        if ($phase !== self::PHASE_ILCE) {
            return true;
        }
        $idx = (int) ($job['ilce_idx'] ?? 0);
        $mahalleIdx = (int) ($job['mahalle_idx'] ?? 0);
        $sokakIdx = (int) ($job['sokak_idx'] ?? 0);
        return $idx > 0 || $mahalleIdx > 0 || $sokakIdx > 0 || !empty($job['ilce_list']);
    }

    /**
     * İptal veya hatalı işi yeniden çalışır duruma getirir (indeksler korunur).
     *
     * @return array<string, mixed>
     */
    public static function resumeJob(string $jobId): array {
        $job = self::readJob($jobId);
        if ($job === null) {
            throw new RuntimeException('Devam edilecek iş bulunamadı.');
        }
        if (!self::isJobResumable($job)) {
            $status = (string) ($job['status'] ?? '');
            if ($status === 'running') {
                self::setActiveJobId($jobId);
                return $job;
            }
            if ($status === 'completed') {
                throw new RuntimeException('Bu iş zaten tamamlanmış.');
            }
            throw new RuntimeException('Bu iş kaldığı yerden devam ettirilemez.');
        }
        self::cancelRunningJobs();
        $job['status'] = 'running';
        $job['cancel_requested'] = false;
        $job['error'] = null;
        $phaseLabel = (string) ($job['phase_label'] ?? '');
        if ($phaseLabel === '' || str_starts_with($phaseLabel, 'Hata:')) {
            $job['phase_label'] = self::phaseResumeLabel($job);
            $phaseLabel = (string) $job['phase_label'];
        }
        $job['updated_at'] = date('c');
        self::appendLog($job, 'Senkron kaldığı yerden devam ediyor (' . $phaseLabel . ').');
        self::writeJob($jobId, $job);
        self::setActiveJobId($jobId);
        return $job;
    }

    public static function createJob(bool $truncate): array {
        self::cancelRunningJobs();
        $id = self::newJobId();
        $job = [
            'id' => $id,
            'created_at' => date('c'),
            'updated_at' => date('c'),
            'status' => 'running',
            'truncate' => $truncate,
            'truncated' => false,
            'phase' => self::PHASE_ILCE,
            'ilce_list' => [],
            'ilce_idx' => 0,
            'mahalle_idx' => 0,
            'sokak_idx' => 0,
            'mahalle_cursor' => null,
            'sokak_cursor' => null,
            'http_fail_count' => 0,
            'totals' => ['ilce' => 0, 'mahalle' => 0, 'sokak' => 0, 'kapino' => 0],
            'phase_label' => 'Hazırlanıyor',
            'error' => null,
            'cancel_requested' => false,
            'log' => [],
        ];
        self::writeJob($id, $job);
        self::setActiveJobId($id);
        return $job;
    }

    /**
     * @return array<string, mixed>
     */
    public static function processNextStep(string $jobId, int $maxSteps = 1): array {
        $maxSteps = max(1, min(50, $maxSteps));
        $job = self::readJob($jobId);
        if ($job === null) {
            return ['ok' => false, 'done' => true, 'mesaj' => 'İş bulunamadı.'];
        }
        if (($job['status'] ?? '') !== 'running') {
            return self::buildResponse($job, true, (string) ($job['error'] ?? 'İş tamamlandı veya durduruldu.'));
        }
        if (!empty($job['cancel_requested'])) {
            $job['status'] = 'cancelled';
            $job['phase_label'] = 'Durduruldu — kaldığı yerden devam edebilirsiniz';
            self::appendLog($job, 'Kullanıcı tarafından durduruldu. İlerleme kaydedildi.');
            self::writeJob($jobId, $job);
            return self::buildResponse($job, true, 'Senkron durduruldu. «Kaldığı yerden devam et» ile sürdürebilirsiniz.');
        }

        try {
            $db = Database::getInstance();
            $stepsDone = 0;
            $job['_log_new_buffer'] = [];

            for ($step = 0; $step < $maxSteps; $step++) {
                if (($job['status'] ?? '') !== 'running' || !empty($job['cancel_requested'])) {
                    break;
                }
                $phase = (string) ($job['phase'] ?? self::PHASE_ILCE);
                $phaseBefore = $phase;

                if ($phase === self::PHASE_ILCE) {
                    self::stepIlce($db, $job);
                } elseif ($phase === self::PHASE_MAHALLE) {
                    self::stepMahalle($db, $job);
                } elseif ($phase === self::PHASE_SOKAK) {
                    self::stepSokak($db, $job);
                } elseif ($phase === self::PHASE_KAPI) {
                    self::stepKapi($db, $job);
                } else {
                    $job['status'] = 'completed';
                    $job['phase'] = self::PHASE_DONE;
                    $job['phase_label'] = 'Tamamlandı';
                    break;
                }

                $stepsDone++;
                $phaseAfter = (string) ($job['phase'] ?? '');
                if ($phaseAfter === self::PHASE_DONE) {
                    break;
                }
                if ($phaseAfter !== $phaseBefore) {
                    continue;
                }
            }

            $job['updated_at'] = date('c');
            self::refreshTotalsFromDb($job);

            $done = ($job['status'] ?? '') !== 'running' || ($job['phase'] ?? '') === self::PHASE_DONE;
            if (($job['phase'] ?? '') === self::PHASE_DONE && ($job['status'] ?? '') === 'running') {
                $job['status'] = 'completed';
                self::appendLog($job, 'Adres senkronu tamamlandı.');
                $done = true;
            }

            $logNew = is_array($job['_log_new_buffer'] ?? null) ? $job['_log_new_buffer'] : [];
            unset($job['_log_new_buffer']);
            self::writeJob($jobId, $job);

            $response = self::buildResponse($job, $done, (string) ($job['phase_label'] ?? ''));
            $response['steps_done'] = $stepsDone;
            $response['log_new'] = $logNew;
            return $response;
        } catch (\Throwable $e) {
            $job['status'] = 'error';
            $job['error'] = $e->getMessage();
            $job['phase_label'] = 'Hata: ' . $e->getMessage();
            self::appendLog($job, $e->getMessage());
            $logNew = is_array($job['_log_new_buffer'] ?? null) ? $job['_log_new_buffer'] : [];
            unset($job['_log_new_buffer']);
            self::writeJob($jobId, $job);
            $response = self::buildResponse($job, true, $job['error']);
            $response['log_new'] = $logNew;
            return $response;
        }
    }

    /**
     * Faz için önerilen toplu adım sayısı (tek istekte birden fazla kayıt işlenir).
     */
    public static function suggestedBatchSize(array $job): int {
        return 1;
    }

    public static function cancelJob(string $jobId): bool {
        $job = self::readJob($jobId);
        if ($job === null) {
            return false;
        }
        if (($job['status'] ?? '') !== 'running') {
            return true;
        }
        $job['cancel_requested'] = true;
        self::writeJob($jobId, $job);
        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function readJob(string $jobId): ?array {
        $jobId = self::sanitizeJobId($jobId);
        if ($jobId === '') {
            return null;
        }
        $path = self::jobsDirectory() . DIRECTORY_SEPARATOR . $jobId . '.json';
        if (!is_file($path)) {
            return null;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findLatestJob(): ?array {
        $dir = self::jobsDirectory();
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.json');
        if (!is_array($files) || $files === []) {
            return null;
        }
        usort($files, static function (string $a, string $b): int {
            return filemtime($b) <=> filemtime($a);
        });
        foreach ($files as $file) {
            $raw = @file_get_contents($file);
            if ($raw === false) {
                continue;
            }
            $data = json_decode($raw, true);
            if (is_array($data)) {
                return $data;
            }
        }
        return null;
    }

    public static function setActiveJobId(?string $jobId): void {
        if ($jobId === null || $jobId === '') {
            unset($_SESSION['adres_fetch_job_id']);
            return;
        }
        $_SESSION['adres_fetch_job_id'] = self::sanitizeJobId($jobId);
    }

    public static function getActiveJobId(): ?string {
        $id = $_SESSION['adres_fetch_job_id'] ?? null;
        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * @param array<string, mixed> $job
     */
    public static function weightedProgressPercent(array $job): int {
        $phase = (string) ($job['phase'] ?? '');
        if ($phase === self::PHASE_DONE || ($job['status'] ?? '') === 'completed') {
            return 100;
        }
        if ($phase === self::PHASE_ILCE) {
            return 2;
        }
        if ($phase === self::PHASE_MAHALLE) {
            $n = max(1, count($job['ilce_list'] ?? []));
            $idx = (int) ($job['ilce_idx'] ?? 0);
            return min(24, 5 + (int) floor((19 * $idx) / $n));
        }
        if ($phase === self::PHASE_SOKAK) {
            $tot = max(1, (int) (($job['totals']['mahalle'] ?? 0) ?: ($job['mahalle_idx'] ?? 1)));
            $idx = (int) ($job['mahalle_idx'] ?? 0);
            return min(54, 25 + (int) floor((29 * $idx) / $tot));
        }
        if ($phase === self::PHASE_KAPI) {
            $tot = max(1, (int) (($job['totals']['sokak'] ?? 0) ?: ($job['sokak_idx'] ?? 1)));
            $idx = (int) ($job['sokak_idx'] ?? 0);
            return min(99, 55 + (int) floor((44 * $idx) / $tot));
        }
        return 0;
    }

    private static function stepIlce(Database $db, array &$job): void {
        if (!empty($job['truncate']) && empty($job['truncated'])) {
            $db->execLogged('TRUNCATE TABLE #__adrestablosu');
            $job['truncated'] = true;
            self::appendLog($job, '#__adrestablosu temizlendi (TRUNCATE).');
        }
        $ilceler = self::fetchIlceListFromApi();
        $stats = self::upsertRows($db, $ilceler, '0', 'ilce');
        $job['ilce_list'] = $ilceler;
        $job['totals']['ilce'] = count($ilceler);
        $job['ilce_idx'] = 0;
        $job['phase'] = self::PHASE_MAHALLE;
        $job['phase_label'] = 'İlçeler yazıldı (' . count($ilceler) . '). Mahalleler çekiliyor…';
        self::appendLog($job, 'İlçe kayıtları tamamlandı' . self::formatUpsertLogSuffix($stats));
    }

    private static function stepMahalle(Database $db, array &$job): void {
        $list = $job['ilce_list'] ?? [];
        if ($list === []) {
            $list = self::fetchIlceListFromApi();
            $job['ilce_list'] = $list;
        }
        $idx = (int) ($job['ilce_idx'] ?? 0);
        if ($idx >= count($list)) {
            $job['phase'] = self::PHASE_SOKAK;
            $job['mahalle_idx'] = 0;
            $job['mahalle_cursor'] = null;
            $job['totals']['mahalle'] = self::countByTip($db, 'mahalle');
            $job['phase_label'] = 'Mahalleler tamamlandı (' . $job['totals']['mahalle'] . '). Sokaklar çekiliyor…';
            self::appendLog($job, 'Mahalle fazı bitti: ' . $job['totals']['mahalle']);
            return;
        }

        $ilce = $list[$idx];
        $ilceId = (string) $ilce['id'];
        $ilceAdi = trim((string) $ilce['adi']);
        usleep(10000);
        $sx = self::fetchXml($ilceId, 'mahalle');
        $apiRows = self::parseXmlRows($sx, 'Mahalle');
        $stats = ['inserted' => 0, 'updated' => 0, 'unchanged' => 0];
        foreach ($apiRows as $mRow) {
            $mahalleAdi = trim((string) ($mRow['adi'] ?? ''));
            if ($mahalleAdi === '') {
                continue;
            }
            $result = self::upsertRow($db, (string) ($mRow['id'] ?? ''), $mahalleAdi, $ilceId, 'mahalle');
            self::accumulateUpsertStats($stats, $result);
            self::appendLog(
                $job,
                'Mahalle: ' . $mahalleAdi . ' · ' . $ilceAdi . self::formatSingleUpsertSuffix($result)
            );
        }
        $idx++;
        $job['ilce_idx'] = $idx;
        $job['phase_label'] = 'Mahalle: ' . $idx . '/' . count($list) . ' ilçe (' . $ilceAdi . self::formatUpsertLogSuffix($stats) . ')';
        self::appendLog($job, $job['phase_label']);

        if ($idx >= count($list)) {
            $job['phase'] = self::PHASE_SOKAK;
            $job['mahalle_idx'] = 0;
            $job['mahalle_cursor'] = null;
            $job['totals']['mahalle'] = self::countByTip($db, 'mahalle');
            $job['phase_label'] = 'Mahalleler tamamlandı (' . $job['totals']['mahalle'] . '). Sokaklar çekiliyor…';
            self::appendLog($job, 'Mahalle fazı bitti: ' . $job['totals']['mahalle']);
        }
    }

    private static function stepSokak(Database $db, array &$job): void {
        $idx = (int) ($job['mahalle_idx'] ?? 0);
        $mahalleRow = self::fetchNextChildRow($db, 'mahalle', $job, 'mahalle_cursor', 'mahalle_idx');
        if ($mahalleRow === null) {
            $job['phase'] = self::PHASE_KAPI;
            $job['sokak_idx'] = 0;
            $job['sokak_cursor'] = null;
            $job['totals']['sokak'] = self::countByTip($db, 'sokak');
            $tot = (int) $job['totals']['sokak'];
            $job['phase_label'] = 'Kapı: 0/' . $tot . ' sokak — kapı numaraları çekiliyor…';
            self::appendLog($job, 'Sokak fazı bitti: ' . $tot);
            return;
        }

        $mahalleId = (string) ($mahalleRow['id'] ?? '');
        $mahalleKonum = self::formatAncestorPath($db, $mahalleRow, $mahalleId);
        usleep(10000);
        $sx = self::fetchXml($mahalleId, 'sokak');
        $apiRows = self::parseXmlRows($sx, 'Sokak');
        $stats = self::upsertRows($db, $apiRows, $mahalleId, 'sokak');
        $idx++;
        $job['mahalle_idx'] = $idx;
        $tot = max((int) ($job['totals']['mahalle'] ?? 0), $idx);
        $job['phase_label'] = 'Sokak: ' . $idx . '/' . $tot . ' mahalle (' . $mahalleKonum . self::formatUpsertLogSuffix($stats) . ')';
        self::appendLog($job, $job['phase_label'] . ', toplam sokak=' . self::countFromJob($job, 'sokak'));
    }

    private static function stepKapi(Database $db, array &$job): void {
        $sokakRow = self::fetchNextChildRow($db, 'sokak', $job, 'sokak_cursor', 'sokak_idx');
        if ($sokakRow === null) {
            $job['phase'] = self::PHASE_DONE;
            $job['totals']['kapino'] = self::countByTip($db, 'kapino');
            $job['phase_label'] = 'Kapı numaraları tamamlandı (' . $job['totals']['kapino'] . ').';
            self::appendLog($job, 'Kapı fazı bitti: ' . $job['totals']['kapino']);
            return;
        }

        $sokakId = (string) ($sokakRow['id'] ?? '');
        $sokakKonum = self::formatAncestorPath($db, $sokakRow, $sokakId);
        $idx = (int) ($job['sokak_idx'] ?? 0);
        $tot = max((int) ($job['totals']['sokak'] ?? 0), $idx + 1);

        usleep(10000);
        try {
            $sx = self::fetchXml($sokakId, 'kapi');
        } catch (RuntimeException $e) {
            if (!str_contains($e->getMessage(), 'HTTP okunamadı')) {
                throw $e;
            }
            $idx++;
            $job['sokak_idx'] = $idx;
            $job['http_fail_count'] = (int) ($job['http_fail_count'] ?? 0) + 1;
            $skipped = is_array($job['skipped_sokak_ids'] ?? null) ? $job['skipped_sokak_ids'] : [];
            if (count($skipped) < 50) {
                $skipped[] = $sokakId;
            }
            $job['skipped_sokak_ids'] = $skipped;
            $job['phase_label'] = 'Kapı: ' . $idx . '/' . $tot . ' sokak (HTTP hatası — sokak atlandı)';
            self::appendLog($job, 'Kapı: ' . $idx . '/' . $tot . ' — ' . $sokakKonum . ' (HTTP hatası, atlandı)');
            return;
        }

        $apiRows = self::parseKapiRows($sx);
        $stats = self::upsertRows($db, $apiRows, $sokakId, 'kapino');
        $idx++;
        $job['sokak_idx'] = $idx;
        $job['phase_label'] = 'Kapı: ' . $idx . '/' . $tot . ' sokak (' . $sokakKonum . self::formatUpsertLogSuffix($stats) . ')';
        self::appendLog($job, $job['phase_label'] . ', toplam kapı=' . self::countFromJob($job, 'kapino'));
    }

    /**
     * Üst kayıtlar dahil konum metni: ilçe · mahalle veya ilçe · mahalle · sokak
     *
     * @param array{ust_id?: string, adi?: string, id?: string}|null $row
     */
    private static function formatAncestorPath(Database $db, ?array $row, string $fallbackId = ''): string {
        if ($row === null) {
            return self::formatAdiLabel(null, $fallbackId);
        }
        $names = [];
        $adi = trim((string) ($row['adi'] ?? ''));
        if ($adi !== '') {
            $names[] = $adi;
        }
        $ustId = trim((string) ($row['ust_id'] ?? ''));
        $depth = 0;
        while ($ustId !== '' && $ustId !== '0' && $depth < 2) {
            $parent = self::fetchRowById($db, $ustId);
            if ($parent === null) {
                break;
            }
            $parentAdi = trim((string) ($parent['adi'] ?? ''));
            if ($parentAdi !== '') {
                array_unshift($names, $parentAdi);
            }
            $ustId = trim((string) ($parent['ust_id'] ?? ''));
            $depth++;
        }
        if ($names === []) {
            return self::formatAdiLabel($row, $fallbackId);
        }
        return implode(' · ', $names);
    }

    private static function formatSingleUpsertSuffix(string $result): string {
        if ($result === 'inserted') {
            return ', +1 yeni';
        }
        if ($result === 'updated') {
            return ', güncellendi';
        }
        return ', değişiklik yok';
    }

    /**
     * @param array{inserted: int, updated: int, unchanged: int} $stats
     */
    private static function accumulateUpsertStats(array &$stats, string $result): void {
        if ($result === 'inserted') {
            $stats['inserted']++;
        } elseif ($result === 'updated') {
            $stats['updated']++;
        } else {
            $stats['unchanged']++;
        }
    }

    /**
     * @param array{ust_id?: string, adi?: string, id?: string}|null $row
     */
    private static function formatAdiLabel(?array $row, string $fallbackId): string {
        if ($row !== null) {
            $adi = trim((string) ($row['adi'] ?? ''));
            if ($adi !== '') {
                return $adi;
            }
        }
        $fallbackId = trim($fallbackId);
        return $fallbackId !== '' ? $fallbackId : '?';
    }

    /**
     * @param array{inserted: int, updated: int, unchanged: int} $stats
     */
    private static function formatUpsertLogSuffix(array $stats): string {
        $parts = [];
        if (($stats['inserted'] ?? 0) > 0) {
            $parts[] = '+' . (int) $stats['inserted'] . ' yeni';
        }
        if (($stats['updated'] ?? 0) > 0) {
            $parts[] = (int) $stats['updated'] . ' güncellendi';
        }
        if ($parts === []) {
            return ', değişiklik yok';
        }
        return ', ' . implode(', ', $parts);
    }

    private static function fetchChildIdByOffset(Database $db, string $tip, int $offset): ?string {
        $row = $db->fetchOnePrepared(
            'SELECT id, ust_id FROM #__adrestablosu WHERE tip = ? ORDER BY ust_id ASC, adi ASC, id ASC LIMIT 1 OFFSET ?',
            [$tip, max(0, $offset)]
        );
        if (!is_array($row) || empty($row['id'])) {
            return null;
        }
        return (string) $row['id'];
    }

    /**
     * Keyset pagination ile sıradaki üst kayıt satırı (OFFSET yerine; büyük indekslerde hızlı).
     *
     * @param array<string, mixed> $job
     * @return array{ust_id: string, adi: string, id: string}|null
     */
    private static function fetchNextChildRow(
        Database $db,
        string $tip,
        array &$job,
        string $cursorKey,
        string $idxKey
    ): ?array {
        self::ensureChildCursorFromIdx($db, $job, $tip, $cursorKey, $idxKey);
        $cursor = $job[$cursorKey] ?? null;
        if (!is_array($cursor) || empty($cursor['id'])) {
            $row = $db->fetchOnePrepared(
                'SELECT id, ust_id, adi FROM #__adrestablosu WHERE tip = ? ORDER BY ust_id ASC, adi ASC, id ASC LIMIT 1',
                [$tip]
            );
        } else {
            $ustId = (string) ($cursor['ust_id'] ?? '');
            $adi = (string) ($cursor['adi'] ?? '');
            $rowId = (string) ($cursor['id'] ?? '');
            $row = $db->fetchOnePrepared(
                'SELECT id, ust_id, adi FROM #__adrestablosu WHERE tip = ?
                 AND (ust_id > ? OR (ust_id = ? AND adi > ?) OR (ust_id = ? AND adi = ? AND id > ?))
                 ORDER BY ust_id ASC, adi ASC, id ASC LIMIT 1',
                [$tip, $ustId, $ustId, $adi, $ustId, $adi, $rowId]
            );
        }
        if (!is_array($row) || empty($row['id'])) {
            return null;
        }
        $job[$cursorKey] = [
            'ust_id' => (string) ($row['ust_id'] ?? ''),
            'adi' => (string) ($row['adi'] ?? ''),
            'id' => (string) ($row['id'] ?? ''),
        ];
        return $job[$cursorKey];
    }

    /**
     * @param array<string, mixed> $job
     */
    private static function fetchNextChildId(
        Database $db,
        string $tip,
        array &$job,
        string $cursorKey,
        string $idxKey
    ): ?string {
        $row = self::fetchNextChildRow($db, $tip, $job, $cursorKey, $idxKey);
        if ($row === null) {
            return null;
        }
        return (string) ($row['id'] ?? '');
    }

    /**
     * Eski job dosyalarında cursor yoksa bir kez OFFSET ile üretir.
     *
     * @param array<string, mixed> $job
     */
    private static function ensureChildCursorFromIdx(
        Database $db,
        array &$job,
        string $tip,
        string $cursorKey,
        string $idxKey
    ): void {
        if (!empty($job[$cursorKey]) && is_array($job[$cursorKey])) {
            return;
        }
        $idx = (int) ($job[$idxKey] ?? 0);
        if ($idx <= 0) {
            return;
        }
        $lastId = self::fetchChildIdByOffset($db, $tip, $idx - 1);
        if ($lastId !== null) {
            self::setChildCursorFromId($db, $job, $cursorKey, $lastId);
        }
    }

    /**
     * @param array<string, mixed> $job
     */
    private static function setChildCursorFromId(Database $db, array &$job, string $cursorKey, string $id, ?string $ustId = null): void {
        $row = self::fetchRowById($db, $id, $ustId);
        if ($row === null) {
            return;
        }
        $job[$cursorKey] = [
            'ust_id' => (string) ($row['ust_id'] ?? ''),
            'adi' => (string) ($row['adi'] ?? ''),
            'id' => (string) ($row['id'] ?? ''),
        ];
    }

    /**
     * @return array{ust_id: string, adi: string, id: string}|null
     */
    private static function fetchRowById(Database $db, string $id, ?string $ustId = null): ?array {
        if ($ustId !== null && trim($ustId) !== '') {
            $row = $db->fetchOnePrepared(
                'SELECT id, ust_id, adi FROM #__adrestablosu WHERE id = ? AND ust_id = ? LIMIT 1',
                [$id, trim($ustId)]
            );
        } else {
            $row = $db->fetchOnePrepared(
                'SELECT id, ust_id, adi FROM #__adrestablosu WHERE id = ? LIMIT 1',
                [$id]
            );
        }
        if (!is_array($row)) {
            return null;
        }
        return [
            'ust_id' => (string) ($row['ust_id'] ?? ''),
            'adi' => (string) ($row['adi'] ?? ''),
            'id' => (string) ($row['id'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $job
     */
    private static function countFromJob(array $job, string $tip): int {
        if (isset($job['counts'][$tip])) {
            return (int) $job['counts'][$tip];
        }
        return (int) ($job['totals'][$tip] ?? 0);
    }

    /**
     * @param array<string, mixed> $job
     */
    private static function phaseResumeLabel(array $job): string {
        $phase = (string) ($job['phase'] ?? '');
        if ($phase === self::PHASE_KAPI) {
            $idx = (int) ($job['sokak_idx'] ?? 0);
            $tot = max(1, (int) ($job['totals']['sokak'] ?? 0));
            return 'Kapı: ' . $idx . '/' . $tot . ' sokak';
        }
        if ($phase === self::PHASE_SOKAK) {
            $idx = (int) ($job['mahalle_idx'] ?? 0);
            $tot = max(1, (int) ($job['totals']['mahalle'] ?? 0));
            return 'Sokak: ' . $idx . '/' . $tot . ' mahalle';
        }
        if ($phase === self::PHASE_MAHALLE) {
            $idx = (int) ($job['ilce_idx'] ?? 0);
            $tot = max(1, count($job['ilce_list'] ?? []));
            return 'Mahalle: ' . $idx . '/' . $tot . ' ilçe';
        }
        return (string) ($job['phase'] ?? 'Devam ediyor');
    }

    private static function countByTip(Database $db, string $tip): int {
        return (int) $db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__adrestablosu WHERE tip = ?',
            [$tip]
        );
    }

    /**
     * @param list<array{id: string, adi: string}> $rows
     * @return array{inserted: int, updated: int, unchanged: int}
     */
    private static function upsertRows(Database $db, array $rows, string $ustId, string $tip): array {
        $inserted = 0;
        $updated = 0;
        $unchanged = 0;
        foreach ($rows as $row) {
            $result = self::upsertRow($db, (string) ($row['id'] ?? ''), (string) ($row['adi'] ?? ''), $ustId, $tip);
            if ($result === 'inserted') {
                $inserted++;
            } elseif ($result === 'updated') {
                $updated++;
            } else {
                $unchanged++;
            }
        }
        return ['inserted' => $inserted, 'updated' => $updated, 'unchanged' => $unchanged];
    }

    private static function upsertRow(Database $db, string $id, string $adi, string $ustId, string $tip): string {
        $id = trim($id);
        $adi = trim($adi);
        if ($id === '' || $adi === '') {
            return 'unchanged';
        }
        if (!$db->executePrepared(
            'INSERT INTO #__adrestablosu (id, adi, ust_id, tip) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE adi = VALUES(adi), ust_id = VALUES(ust_id), tip = VALUES(tip)',
            [$id, $adi, $ustId, $tip]
        )) {
            return 'unchanged';
        }
        $rowCount = $db->affectedRows();
        if ($rowCount === 1) {
            return 'inserted';
        }
        if ($rowCount === 2) {
            return 'updated';
        }
        return 'unchanged';
    }

    /**
     * @return list<array{id: string, adi: string}>
     */
    private static function parseXmlRows(?SimpleXMLElement $sx, string $nodeName): array {
        if ($sx === null || !isset($sx->{$nodeName})) {
            return [];
        }
        $rows = [];
        foreach ($sx->{$nodeName} as $node) {
            $id = trim((string) ($node->ID ?? ''));
            $adi = trim((string) ($node->ADI ?? ''));
            if ($id === '' || $adi === '') {
                continue;
            }
            $rows[] = ['id' => $id, 'adi' => $adi];
        }
        return $rows;
    }

    /**
     * @return list<array{id: string, adi: string}>
     */
    private static function parseKapiRows(?SimpleXMLElement $sx): array {
        if ($sx === null || !isset($sx->Kapi)) {
            return [];
        }
        $rows = [];
        foreach ($sx->Kapi as $node) {
            $id = trim((string) ($node->ID ?? ''));
            $no = trim((string) ($node->NO ?? ''));
            if ($no === '' && isset($node->ADI)) {
                $no = trim((string) $node->ADI);
            }
            if ($id === '' || $no === '') {
                continue;
            }
            $rows[] = ['id' => $id, 'adi' => $no];
        }
        return $rows;
    }

    private static function fetchXml(string $id, string $type): ?SimpleXMLElement {
        $q = 't=' . rawurlencode($type);
        if ($id !== '') {
            $q = 'id=' . rawurlencode($id) . '&' . $q;
        }
        $url = self::API_BASE . '?' . $q;
        $raw = self::httpGet($url);
        if ($raw === '') {
            return null;
        }
        $utf8 = self::xmlToUtf8($raw);
        $utf8 = preg_replace('/<\?xml\s[^?]*\?>/i', '<?xml version="1.0" encoding="UTF-8"?>', $utf8, 1);
        libxml_use_internal_errors(true);
        $sx = simplexml_load_string($utf8);
        return $sx === false ? null : $sx;
    }

    private static function httpGet(string $url): string {
        $delays = [0, 1000000, 2000000, 4000000];
        $lastError = '';
        foreach ($delays as $attempt => $delayUs) {
            if ($delayUs > 0) {
                usleep($delayUs);
            }
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 90,
                    'header' => "User-Agent: ESH-AdresSync/1.0\r\nAccept: application/xml,*/*\r\n",
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);
            $raw = @file_get_contents($url, false, $ctx);
            if ($raw !== false) {
                return $raw;
            }
            $lastError = error_get_last()['message'] ?? 'bilinmeyen hata';
            if ($attempt >= count($delays) - 1) {
                break;
            }
        }
        throw new RuntimeException('HTTP okunamadı: ' . $url . ($lastError !== '' ? ' (' . $lastError . ')' : ''));
    }

    private static function xmlToUtf8(string $raw): string {
        if ($raw === '') {
            return '';
        }
        if (substr($raw, 0, 2) === "\xFF\xFE") {
            return mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
        }
        if (substr($raw, 0, 2) === "\xFE\xFF") {
            return mb_convert_encoding($raw, 'UTF-8', 'UTF-16BE');
        }
        if (str_contains($raw, "\0") && str_contains($raw, '<?xml')) {
            return mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
        }
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            return substr($raw, 3);
        }
        return $raw;
    }

    /**
     * @param array<string, mixed> $job
     */
    private static function refreshTotalsFromDb(array &$job): void {
        $counts = self::getTableCounts();
        foreach ($counts as $k => $v) {
            if ((int) ($job['totals'][$k] ?? 0) < $v) {
                $job['totals'][$k] = $v;
            }
        }
        $job['counts'] = $counts;
    }

    /**
     * @param array<string, mixed> $job
     */
    private static function appendLog(array &$job, string $line): void {
        $formatted = date('H:i:s') . ' — ' . $line;
        $log = is_array($job['log'] ?? null) ? $job['log'] : [];
        $log[] = $formatted;
        if (count($log) > 500) {
            $log = array_slice($log, -500);
        }
        $job['log'] = $log;
        if (array_key_exists('_log_new_buffer', $job) && is_array($job['_log_new_buffer'])) {
            $job['_log_new_buffer'][] = $formatted;
        }
    }

    /**
     * @param array<string, mixed> $job
     * @return array<string, mixed>
     */
    private static function buildResponse(array $job, bool $done, string $mesaj): array {
        return [
            'ok' => ($job['status'] ?? '') !== 'error',
            'done' => $done,
            'mesaj' => $mesaj,
            'job_id' => (string) ($job['id'] ?? ''),
            'status' => (string) ($job['status'] ?? ''),
            'phase' => (string) ($job['phase'] ?? ''),
            'phase_label' => (string) ($job['phase_label'] ?? ''),
            'progress' => self::weightedProgressPercent($job),
            'counts' => $job['counts'] ?? self::getTableCounts(),
            'totals' => $job['totals'] ?? [],
            'log' => $job['log'] ?? [],
        ];
    }

    /**
     * @param array<string, mixed> $job
     */
    private static function writeJob(string $jobId, array $job): void {
        unset($job['_log_new_buffer'], $job['_kapi_inserts_this_request']);
        $jobId = self::sanitizeJobId($jobId);
        $path = self::jobsDirectory() . DIRECTORY_SEPARATOR . $jobId . '.json';
        file_put_contents($path, json_encode($job, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    }

    private static function cancelRunningJobs(): void {
        $dir = self::jobsDirectory();
        $files = glob($dir . DIRECTORY_SEPARATOR . '*.json');
        if (!is_array($files)) {
            return;
        }
        foreach ($files as $file) {
            $raw = @file_get_contents($file);
            if ($raw === false) {
                continue;
            }
            $job = json_decode($raw, true);
            if (!is_array($job) || ($job['status'] ?? '') !== 'running') {
                continue;
            }
            $job['cancel_requested'] = true;
            $job['status'] = 'cancelled';
            $job['phase_label'] = 'Yeni işlem başlatıldı — iptal edildi';
            file_put_contents($file, json_encode($job, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        }
    }

    private static function newJobId(): string {
        return date('Ymd_His') . '_' . bin2hex(random_bytes(4));
    }

    private static function sanitizeJobId(string $jobId): string {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $jobId) ?? '';
    }
}
