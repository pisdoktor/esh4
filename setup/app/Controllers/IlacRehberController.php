<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;
use App\Helpers\IlacRehberImportStore;
use App\Helpers\IlacRehberScrapeSync;
use App\Helpers\PageShellHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\RehberEtken;
use App\Models\RehberIlac;

/**
 * Üçüncü taraf ilaç rehberi snapshot (esh_rehber_*). TİTCK / hasta ilaçlarından bağımsız.
 */
class IlacRehberController
{
    /** Oturum açmış personel / yönetici / süper yönetici (arama ve etken detay). */
    private const STAFF_ACTIONS = ['search', 'etkenAjax', 'ilacAjax', 'etken'];

    /** Yönetici ve süper yönetici (veri kaynağı sayfası ve canlı özet ajax). */
    private const ADMIN_ACTIONS = ['about', 'statsAjax'];

    /** Süper yönetici — scrape yönetimi (AdresFetch deseni). */
    private const SCRAPE_ACTIONS = ['migration', 'scrapeStart', 'scrapeStatus', 'scrapeCancel'];

    public function __construct()
    {
        $action = (string) ($GLOBALS['actionName'] ?? '');
        if (in_array($action, self::STAFF_ACTIONS, true)) {
            return;
        }
        if (in_array($action, self::ADMIN_ACTIONS, true)) {
            AuthHelper::requireAdmin();

            return;
        }
        if (in_array($action, self::SCRAPE_ACTIONS, true)) {
            AuthHelper::requireSuperAdmin();

            return;
        }
        AuthHelper::requireSuperAdmin();
    }

    /**
     * Süper yönetici: üçüncü taraf siteden snapshot scrape yönetimi.
     */
    public function migration(): void
    {
        $defaults = IlacRehberScrapeSync::defaultOptions();
        $initialActivePayload = null;
        $foundActive = IlacRehberScrapeSync::findLatestActiveJob();
        if ($foundActive !== null) {
            $jid0 = $foundActive['id'];
            $job0 = IlacRehberScrapeSync::guardAndEnrichJob($jid0, $foundActive['job']);
            if (IlacRehberScrapeSync::jobIsActive($job0)) {
                $initialActivePayload = ['job_id' => $jid0, 'job' => $job0];
            }
        }

        $initialJobId = is_array($initialActivePayload) ? ($initialActivePayload['job_id'] ?? null) : null;
        $initialStatus = IlacRehberScrapeSync::buildStatusPayload(
            is_string($initialJobId) && $initialJobId !== '' ? $initialJobId : null
        );
        $stats = is_array($initialStatus['stats'] ?? null) ? $initialStatus['stats'] : [];
        $pageTitle = 'İlaç rehberi — veri aktarımı';
        $csrfToken = CsrfHelper::token();

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'ilac-rehber/migration');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function scrapeStart(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'POST gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $parsed = IlacRehberScrapeSync::parseOptionsFromRequest($_POST);
        if (!$parsed['ok']) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => (string) ($parsed['error'] ?? 'Geçersiz seçenek')], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $options = $parsed['options'];

        $result = IlacRehberScrapeSync::startJob($options);
        if (!$result['ok']) {
            $status = isset($result['active_job_id']) ? 409 : 500;
            http_response_code($status);
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function scrapeStatus(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $jobId = trim((string) ($_GET['job_id'] ?? $_GET['job'] ?? ''));
        if ($jobId !== '') {
            $resolved = IlacRehberScrapeSync::resolveJobForStatus($jobId);
            if ($resolved === null) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'job bulunamadı'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        echo json_encode(
            IlacRehberScrapeSync::buildStatusPayload($jobId !== '' ? $jobId : null),
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    public function scrapeCancel(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'POST gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $jobId = trim((string) ($_POST['job_id'] ?? ''));
        if ($jobId === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'job_id gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $force = ((string) ($_POST['force'] ?? '1')) === '1';
        $result = IlacRehberScrapeSync::cancelJob($jobId, $force);
        if (!$result['ok']) {
            $code = str_contains((string) ($result['error'] ?? ''), 'bulunamadı') ? 404 : 409;
            http_response_code($code);
        }
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function search(): void
    {
        $model = new RehberEtken();
        $model->ensureTables();

        $etkenAjaxUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'IlacRehber',
            'action' => 'etkenAjax',
        ]);
        $ilacAjaxUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'IlacRehber',
            'action' => 'ilacAjax',
        ]);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'ilac_rehber/search');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function index(): void
    {
        $model = new RehberEtken();
        $model->ensureTables();
        self::syncRehberImportLogState();

        $etkenCount = $model->countAll();
        $ilacModel = new RehberIlac();
        $ilacCount = $ilacModel->countAll();
        $etkenWithoutIlacCount = $model->countWithoutIlac();
        $importLog = $model->getLatestImportLog();
        $scrapeWorkerRunning = self::isRehberScrapeWorkerRunning();
        $etkenAjaxUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'IlacRehber',
            'action' => 'etkenAjax',
        ]);
        $statsAjaxUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'IlacRehber',
            'action' => 'statsAjax',
        ]);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'ilac_rehber/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function etkenAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $q = trim((string) ($_GET['q'] ?? ''));
        if (mb_strlen($q, 'UTF-8') < 1) {
            echo json_encode(['ok' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $model = new RehberEtken();
        $model->ensureTables();
        $rows = $model->searchByQuery($q, 25);
        $items = [];
        foreach ($rows as $r) {
            $id = (int) $r->id;
            $items[] = [
                'id' => $id,
                'ad' => (string) $r->ad,
                'url' => esh_url('IlacRehber', 'etken', ['id' => $id]),
            ];
        }

        echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function ilacAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $q = trim((string) ($_GET['q'] ?? ''));
        if (mb_strlen($q, 'UTF-8') < 1) {
            echo json_encode(['ok' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $etkenModel = new RehberEtken();
        $etkenModel->ensureTables();
        $ilacModel = new RehberIlac();
        $rows = $ilacModel->searchByQuery($q, 25);
        $items = [];
        foreach ($rows as $r) {
            $etkenId = (int) $r->etken_id;
            $id = (int) $r->id;
            $firma = trim((string) ($r->firma ?? ''));
            $etkenAd = trim((string) ($r->etken_ad ?? ''));
            $subtitle = $firma !== '' ? $firma : ($etkenAd !== '' ? $etkenAd : '');
            $items[] = [
                'id' => $id,
                'ad' => (string) $r->ad,
                'subtitle' => $subtitle,
                'etken_ad' => $etkenAd,
                'url' => esh_url('IlacRehber', 'etken', ['id' => $etkenId]),
                'source_url' => trim((string) ($r->source_url ?? '')) !== '' ? (string) $r->source_url : null,
            ];
        }

        echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function statsAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $etkenModel = new RehberEtken();
        $etkenModel->ensureTables();
        self::syncRehberImportLogState();
        $ilacModel = new RehberIlac();
        $workerRunning = self::isRehberScrapeWorkerRunning();

        $openImportLog = IlacRehberImportStore::getOpenImportLog();
        $importInProgress = IlacRehberImportStore::isImportInProgress($workerRunning);
        $importStartedAt = null;
        if ($openImportLog !== null && !empty($openImportLog->started_at)) {
            $importStartedAt = (string) $openImportLog->started_at;
        }

        echo json_encode([
            'ok' => true,
            'etken' => $etkenModel->countAll(),
            'ilac' => $ilacModel->countAll(),
            'etken_without_ilac' => $etkenModel->countWithoutIlac(),
            'import_in_progress' => $importInProgress,
            'import_started_at' => $importStartedAt,
            'scrape_worker_running' => $workerRunning,
            'last_log_line' => self::tailScrapeLogLine(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private static function tailScrapeLogLine(int $tailBytes = 200): ?string
    {
        $path = ROOT_PATH . '/logs/ilac_rehber_scrape.log';
        if (!is_readable($path)) {
            return null;
        }
        $size = filesize($path);
        if ($size === false || $size < 1) {
            return null;
        }
        $fh = @fopen($path, 'rb');
        if ($fh === false) {
            return null;
        }
        $read = (int) min($tailBytes, $size);
        if (fseek($fh, -$read, SEEK_END) !== 0) {
            fclose($fh);

            return null;
        }
        $chunk = fread($fh, $read);
        fclose($fh);
        if ($chunk === false || $chunk === '') {
            return null;
        }
        $lines = preg_split("/\r\n|\n|\r/", trim($chunk)) ?: [];
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^\[\d{4}-\d{2}-\d{2}[^\]]*\]\s*(.+)$/u', $line, $m)) {
                return $m[1];
            }

            return $line;
        }

        return null;
    }

    public function etken(): void
    {
        $etkenId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $etkenModel = new RehberEtken();
        $etkenModel->ensureTables();
        $etken = $etkenModel->findById($etkenId);
        if ($etken === null) {
            header('Location: ' . esh_url('IlacRehber', 'search'));
            exit;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = max(10, min(100, (int) ($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $ilacModel = new RehberIlac();
        $total = $ilacModel->countByEtkenId($etkenId);
        $ilaclar = $ilacModel->listByEtkenId($etkenId, $limit, $offset);

        $pagelink = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'IlacRehber',
            'action' => 'etken',
            'id' => $etkenId,
            'limit' => $limit,
        ]);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'ilac_rehber/etken');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function about(): void
    {
        $etkenModel = new RehberEtken();
        $etkenModel->ensureTables();
        self::syncRehberImportLogState();
        $ilacModel = new RehberIlac();

        $etkenCount = $etkenModel->countAll();
        $ilacCount = $ilacModel->countAll();
        $etkenWithoutIlacCount = $etkenModel->countWithoutIlac();
        $scrapedSummary = $etkenModel->getScrapedSummary();
        $scrapeWorkerRunning = self::isRehberScrapeWorkerRunning();

        $completedImportLog = $etkenModel->getLatestCompletedImportLog();
        $inProgressImportLog = null;
        if ($scrapeWorkerRunning) {
            $inProgressImportLog = IlacRehberImportStore::getOpenImportLog();
        }

        $statsAjaxUrl = \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => 'IlacRehber',
            'action' => 'statsAjax',
        ]);

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'ilac_rehber/about');
        include ThemeViewHelper::resolvePartial('footer');
    }

    private static function rehberCheckpointHelpersLoaded(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;
        require_once ROOT_PATH . '/database/ilac_rehber_checkpoint.php';
    }

    private static function isRehberScrapeWorkerRunning(): bool
    {
        self::rehberCheckpointHelpersLoaded();

        return ilacRehberWorkerIsRunning(ROOT_PATH);
    }

    private static function syncRehberImportLogState(): void
    {
        self::rehberCheckpointHelpersLoaded();
        ilacRehberSyncImportLogIfWorkerStopped(ROOT_PATH);
    }
}
