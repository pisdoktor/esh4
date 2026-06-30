<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\DenizliAdresSync;
use App\Helpers\PaginationHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\Address;

/**
 * Süper yönetici: Denizli belediye adres ağacı toplu senkronu (batch AJAX).
 */
class AdresFetchController {

    /** @var list<string> */
    private static array $validTreeTips = ['ilce', 'mahalle', 'sokak', 'kapino'];

    /** @var list<string> */
    private static array $ajaxActions = [
        'ajaxDeleteOrphans',
        'ajaxOrphanReport',
        'ajaxPurgeOrphans',
        'ajaxTreeChildren',
        'cancel',
        'processNext',
        'resume',
        'start',
        'status',
    ];

    private static bool $jsonResponseSent = false;

    public function __construct() {
        $action = (string) ($GLOBALS['actionName'] ?? '');
        if (in_array($action, self::$ajaxActions, true)) {
            if (!AuthHelper::sessionIsSuperAdmin()) {
                self::jsonResponse(['ok' => false, 'mesaj' => 'Bu alana erişim yetkiniz bulunmamaktadır.'], 403);
            }
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            return;
        }
        AuthHelper::requireSuperAdmin();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function jsonResponse(array $payload, int $code = 200): void {
        self::$jsonResponseSent = true;
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private static function registerJsonTimeoutGuard(): void {
        register_shutdown_function(static function (): void {
            if (self::$jsonResponseSent) {
                return;
            }
            $err = error_get_last();
            if ($err === null || !str_contains((string) ($err['message'] ?? ''), 'Maximum execution time')) {
                return;
            }
            if (!headers_sent()) {
                http_response_code(504);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'ok' => false,
                'mesaj' => 'Sorgu zaman aşımına uğradı.',
                'timeout' => true,
            ], JSON_UNESCAPED_UNICODE);
        });
    }

    public function index(): void {
        $counts = DenizliAdresSync::getTableCounts();
        $activeJob = null;
        $resumableJob = null;
        $activeId = DenizliAdresSync::getActiveJobId();
        if ($activeId !== null) {
            $activeJob = DenizliAdresSync::readJob($activeId);
        }
        if ($activeJob === null) {
            $activeJob = DenizliAdresSync::findLatestJob();
        }
        if ($activeJob !== null && ($activeJob['status'] ?? '') === 'running') {
            $resumableJob = null;
        } else {
            $candidate = DenizliAdresSync::findResumableJob();
            if ($candidate !== null) {
                $resumableJob = $candidate;
            }
        }
        $pageTitle = 'Denizli adres senkronu';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'adres-fetch/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function tree(): void {
        $counts = DenizliAdresSync::getTableCounts();
        $pageTitle = 'Adres ağacı';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'adres-fetch/tree');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function tarama(): void {
        $counts = DenizliAdresSync::getTableCounts();
        $pageTitle = 'Eksik ilçe taraması';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'adres-fetch/tarama');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function start(): void {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'mesaj' => 'Geçersiz istek.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $truncate = isset($_POST['truncate']) && (string) $_POST['truncate'] === '1';
        try {
            $job = DenizliAdresSync::createJob($truncate);
            echo json_encode([
                'ok' => true,
                'job_id' => $job['id'],
                'mesaj' => $truncate
                    ? 'Senkron başlatıldı; mevcut adres tablosu temizlenecek.'
                    : 'Senkron başlatıldı (mevcut kayıtlar korunur; var olanlar atlanır).',
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'mesaj' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    public function processNext(): void {
        header('Content-Type: application/json; charset=utf-8');
        $jobId = trim((string) ($_GET['job_id'] ?? $_POST['job_id'] ?? ''));
        if ($jobId === '') {
            $jobId = (string) (DenizliAdresSync::getActiveJobId() ?? '');
        }
        if ($jobId === '') {
            echo json_encode(['ok' => false, 'done' => true, 'mesaj' => 'Aktif iş yok.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $batch = (int) ($_GET['batch'] ?? $_POST['batch'] ?? 0);
        if ($batch <= 0) {
            $job = DenizliAdresSync::readJob($jobId);
            $batch = $job !== null ? DenizliAdresSync::suggestedBatchSize($job) : 1;
        }
        $result = DenizliAdresSync::processNextStep($jobId, $batch);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function resume(): void {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'mesaj' => 'Geçersiz istek.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $jobId = trim((string) ($_POST['job_id'] ?? ''));
        if ($jobId === '') {
            $resumable = DenizliAdresSync::findResumableJob();
            $jobId = $resumable !== null ? (string) ($resumable['id'] ?? '') : '';
        }
        if ($jobId === '') {
            echo json_encode(['ok' => false, 'mesaj' => 'Devam ettirilecek iş bulunamadı.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        try {
            $job = DenizliAdresSync::resumeJob($jobId);
            echo json_encode([
                'ok' => true,
                'job_id' => $job['id'],
                'phase_label' => $job['phase_label'] ?? '',
                'progress' => DenizliAdresSync::weightedProgressPercent($job),
                'mesaj' => 'Senkron kaldığı yerden devam ediyor.',
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => false, 'mesaj' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    public function status(): void {
        header('Content-Type: application/json; charset=utf-8');
        $jobId = trim((string) ($_GET['job_id'] ?? ''));
        if ($jobId === '') {
            $jobId = (string) (DenizliAdresSync::getActiveJobId() ?? '');
        }
        $job = $jobId !== '' ? DenizliAdresSync::readJob($jobId) : DenizliAdresSync::findLatestJob();
        if ($job === null) {
            echo json_encode([
                'ok' => true,
                'has_job' => false,
                'counts' => DenizliAdresSync::getTableCounts(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode([
            'ok' => true,
            'has_job' => true,
            'job_id' => $job['id'] ?? '',
            'status' => $job['status'] ?? '',
            'phase' => $job['phase'] ?? '',
            'phase_label' => $job['phase_label'] ?? '',
            'progress' => DenizliAdresSync::weightedProgressPercent($job),
            'counts' => $job['counts'] ?? DenizliAdresSync::getTableCounts(),
            'totals' => $job['totals'] ?? [],
            'log' => $job['log'] ?? [],
            'done' => ($job['status'] ?? '') !== 'running',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function cancel(): void {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'mesaj' => 'Geçersiz istek.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $jobId = trim((string) ($_POST['job_id'] ?? ''));
        if ($jobId === '') {
            $jobId = (string) (DenizliAdresSync::getActiveJobId() ?? '');
        }
        if ($jobId === '') {
            echo json_encode(['ok' => false, 'mesaj' => 'İptal edilecek iş yok.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        DenizliAdresSync::cancelJob($jobId);
        echo json_encode(['ok' => true, 'mesaj' => 'Durdurma isteği gönderildi.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function ajaxTreeChildren(): void {
        header('Content-Type: application/json; charset=utf-8');
        $tip = isset($_GET['tip']) ? trim((string) $_GET['tip']) : 'ilce';
        if (!in_array($tip, self::$validTreeTips, true)) {
            echo json_encode(['ok' => false, 'mesaj' => 'Geçersiz tip.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $ustId = isset($_GET['ust_id']) ? trim((string) $_GET['ust_id']) : '0';
        if ($tip !== 'ilce' && $ustId === '') {
            echo json_encode(['ok' => false, 'mesaj' => 'ust_id gerekli.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $model = new Address();
        $items = $model->adminTreeChildren($tip, $tip === 'ilce' ? '0' : $ustId);
        echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function ajaxOrphanReport(): void {
        @set_time_limit(120);
        self::registerJsonTimeoutGuard();
        $mode = isset($_GET['mode']) ? trim((string) $_GET['mode']) : 'counts';
        $model = new Address();
        if ($mode === 'list') {
            $tip = isset($_GET['tip']) ? trim((string) $_GET['tip']) : '';
            if (!in_array($tip, ['mahalle', 'sokak', 'kapino'], true)) {
                self::jsonResponse(['ok' => false, 'mesaj' => 'Geçersiz tip.'], 400);
            }
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $perPage = max(1, min(200, (int) ($_GET['per_page'] ?? 50)));
            $offset = ($page - 1) * $perPage;
            $knownTotal = null;
            if (isset($_GET['known_total']) && $_GET['known_total'] !== '') {
                $knownTotal = max(0, (int) $_GET['known_total']);
            }
            $result = $model->listOrphansWithoutIlce($tip, $offset, $perPage, $knownTotal);
            $total = (int) ($result['total'] ?? 0);
            $paginationOpts = [
                'ajax' => true,
                'link_class' => 'page-link orphan-page-link',
                'select_class' => 'form-select form-select-sm orphan-limit-select',
                'jump_id' => 'orphan_jump_page',
                'jump_btn_class' => 'btn btn-outline-primary orphan-jump-page-btn',
                'limits' => [25, 50, 100, 200],
            ];
            self::jsonResponse([
                'ok' => true,
                'mode' => 'list',
                'tip' => $tip,
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'items' => $result['items'],
                'pagination' => [
                    'info_html' => PaginationHelper::infoText($total, $page, $perPage),
                    'limit_html' => PaginationHelper::limitSelector($perPage, '', $paginationOpts),
                    'nav_html' => PaginationHelper::render($total, $page, $perPage, '', $paginationOpts),
                ],
            ]);
        }
        $singleTip = isset($_GET['tip']) ? trim((string) $_GET['tip']) : '';
        if ($singleTip !== '' && in_array($singleTip, ['mahalle', 'sokak', 'kapino'], true)) {
            $count = $model->countOrphansForTip($singleTip);
            self::jsonResponse([
                'ok' => true,
                'mode' => 'counts',
                'tip' => $singleTip,
                'count' => $count,
                'counts' => [$singleTip => $count],
            ]);
        }
        $counts = $model->countOrphansWithoutIlce();
        self::jsonResponse([
            'ok' => true,
            'mode' => 'counts',
            'counts' => $counts,
            'total' => (int) ($counts['mahalle'] ?? 0) + (int) ($counts['sokak'] ?? 0) + (int) ($counts['kapino'] ?? 0),
        ]);
    }

    public function ajaxDeleteOrphans(): void {
        @set_time_limit(300);
        self::registerJsonTimeoutGuard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::jsonResponse(['ok' => false, 'mesaj' => 'Geçersiz istek.'], 405);
        }
        $items = $this->parseOrphanDeleteItems();
        if ($items === []) {
            self::jsonResponse(['ok' => false, 'mesaj' => 'Silinecek kayıt seçilmedi.'], 400);
        }
        if (count($items) > 200) {
            self::jsonResponse(['ok' => false, 'mesaj' => 'En fazla 200 kayıt silinebilir.'], 400);
        }
        $model = new Address();
        $deleted = [];
        $failed = [];
        foreach ($items as $item) {
            $id = (string) ($item['id'] ?? '');
            $ustId = isset($item['ust_id']) ? (string) $item['ust_id'] : null;
            if ($ustId !== null && trim($ustId) === '') {
                $ustId = null;
            }
            $err = $model->attemptDeleteOrphan($id, $ustId);
            if ($err === null) {
                $deleted[] = $id;
            } else {
                $failed[] = ['id' => $id, 'mesaj' => $err];
            }
        }
        self::jsonResponse([
            'ok' => true,
            'deleted' => $deleted,
            'failed' => $failed,
        ]);
    }

    public function ajaxPurgeOrphans(): void {
        @set_time_limit(600);
        self::registerJsonTimeoutGuard();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::jsonResponse(['ok' => false, 'mesaj' => 'Geçersiz istek.'], 405);
        }
        $params = $this->parsePurgeOrphansParams();
        $tip = (string) ($params['tip'] ?? '');
        if (!in_array($tip, ['mahalle', 'sokak', 'kapino'], true)) {
            self::jsonResponse(['ok' => false, 'mesaj' => 'Geçersiz tip.'], 400);
        }
        $batchSize = max(1, min(200, (int) ($params['batch_size'] ?? 100)));
        $maxBatches = max(1, min(100, (int) ($params['max_batches'] ?? 20)));

        $model = new Address();
        $allDeleted = [];
        $allFailed = [];
        $skippedHasChildren = 0;
        $skippedPatientRef = 0;
        $batchesRun = 0;
        $remainingEstimate = 0;
        $stuck = false;
        $done = false;

        while ($batchesRun < $maxBatches) {
            $result = $model->purgeOrphansBatch($tip, $batchSize);
            $batchesRun++;
            $batchDeleted = $result['deleted'] ?? [];
            $batchFailed = $result['failed'] ?? [];
            $allDeleted = array_merge($allDeleted, $batchDeleted);
            $allFailed = array_merge($allFailed, $batchFailed);
            $skippedHasChildren += (int) ($result['skipped_has_children'] ?? 0);
            $skippedPatientRef += (int) ($result['skipped_patient_ref'] ?? 0);
            $remainingEstimate = (int) ($result['remaining_estimate'] ?? 0);
            $fetched = (int) ($result['batch_fetched'] ?? 0);

            if ($fetched === 0) {
                $done = true;
                break;
            }
            if (count($batchDeleted) === 0 && count($batchFailed) > 0 && $fetched === count($batchFailed)) {
                $stuck = true;
                $done = true;
                break;
            }
        }

        if (!$done && $batchesRun >= $maxBatches) {
            $done = false;
        } elseif (!$done) {
            $done = $remainingEstimate <= 0;
        }

        self::jsonResponse([
            'ok' => true,
            'tip' => $tip,
            'deleted' => $allDeleted,
            'deleted_count' => count($allDeleted),
            'failed' => $allFailed,
            'failed_count' => count($allFailed),
            'skipped_has_children' => $skippedHasChildren,
            'skipped_patient_ref' => $skippedPatientRef,
            'remaining_estimate' => $remainingEstimate,
            'batches_run' => $batchesRun,
            'done' => $done,
            'stuck' => $stuck,
        ]);
    }

    /**
     * @return array{tip?: string, batch_size?: int, max_batches?: int}
     */
    private function parsePurgeOrphansParams(): array {
        if (isset($_POST['tip'])) {
            return [
                'tip' => trim((string) $_POST['tip']),
                'batch_size' => isset($_POST['batch_size']) ? (int) $_POST['batch_size'] : 100,
                'max_batches' => isset($_POST['max_batches']) ? (int) $_POST['max_batches'] : 20,
            ];
        }
        $body = file_get_contents('php://input');
        if (is_string($body) && $body !== '') {
            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                return [
                    'tip' => trim((string) ($decoded['tip'] ?? '')),
                    'batch_size' => isset($decoded['batch_size']) ? (int) $decoded['batch_size'] : 100,
                    'max_batches' => isset($decoded['max_batches']) ? (int) $decoded['max_batches'] : 20,
                ];
            }
        }
        return [];
    }

    /**
     * @return list<array{id: string, ust_id: string|null}>
     */
    private function parseOrphanDeleteItems(): array {
        $rawItems = [];
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            $rawItems = $_POST['items'];
        } elseif (isset($_POST['ids']) && is_array($_POST['ids'])) {
            foreach ($_POST['ids'] as $v) {
                $rawItems[] = is_array($v) ? $v : ['id' => $v];
            }
        } else {
            $body = file_get_contents('php://input');
            if (is_string($body) && $body !== '') {
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    if (isset($decoded['items']) && is_array($decoded['items'])) {
                        $rawItems = $decoded['items'];
                    } elseif (isset($decoded['ids']) && is_array($decoded['ids'])) {
                        foreach ($decoded['ids'] as $v) {
                            $rawItems[] = is_array($v) ? $v : ['id' => $v];
                        }
                    }
                }
            }
        }
        $items = [];
        $seen = [];
        foreach ($rawItems as $v) {
            if (is_string($v) || is_numeric($v)) {
                $id = trim((string) $v);
                $ustId = null;
            } elseif (is_array($v)) {
                $id = trim((string) ($v['id'] ?? ''));
                $ustTrim = trim((string) ($v['ust_id'] ?? ''));
                $ustId = $ustTrim !== '' ? $ustTrim : null;
            } else {
                continue;
            }
            if ($id === '') {
                continue;
            }
            $key = $id . "\0" . ($ustId ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $items[] = ['id' => $id, 'ust_id' => $ustId];
        }
        return $items;
    }
}
