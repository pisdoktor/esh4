<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLogHelper;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;
use App\Helpers\OperationalSettings;
use App\Helpers\PaginationHelper;
use App\Helpers\ThemeViewHelper;
use App\Helpers\ValidationHelper;
use App\Models\AuditLog;
use App\Models\Kurum;

/**
 * KVKK / iç denetim — işlem günlüğü (yönetici).
 */
class AuditLogController
{
    public function __construct()
    {
        AuthHelper::requireAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    private function parseFilters(): array
    {
        $kurumId = 0;
        if (AuthHelper::sessionIsSuperAdmin()) {
            $kurumRaw = trim((string) ($_GET['kurum_id'] ?? ''));
            if ($kurumRaw !== '' && ctype_digit($kurumRaw) && (int) $kurumRaw > 0) {
                $kurumId = (int) $kurumRaw;
            }
        }

        $userId = 0;
        $userRaw = trim((string) ($_GET['user_id'] ?? ''));
        if ($userRaw !== '' && ctype_digit($userRaw) && (int) $userRaw > 0) {
            $userId = (int) $userRaw;
        }

        $entityRef = ValidationHelper::tcDigitsOnly((string) ($_GET['entity_ref'] ?? ''));

        $dateFrom = trim((string) ($_GET['date_from'] ?? ''));
        $dateTo = trim((string) ($_GET['date_to'] ?? ''));
        if ($dateFrom === '' && $dateTo === '' && !isset($_GET['q']) && !isset($_GET['action']) && !isset($_GET['entity_type']) && !isset($_GET['entity_ref'])) {
            $dateFrom = date('Y-m-d', strtotime('-30 days'));
            $dateTo = date('Y-m-d');
        }

        return [
            'kurum_id' => $kurumId,
            'action' => trim((string) ($_GET['action'] ?? '')),
            'entity_type' => trim((string) ($_GET['entity_type'] ?? '')),
            'entity_ref' => $entityRef,
            'user_id' => $userId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'q' => trim((string) ($_GET['q'] ?? '')),
        ];
    }

    private function buildPagelink(array $filters, int $limit): string
    {
        $params = [
            'controller' => 'AuditLog',
            'action' => 'index',
            'limit' => $limit,
        ];

        foreach ($filters as $key => $value) {
            if ($value === '' || $value === 0 || $value === null) {
                continue;
            }
            $params[$key] = $value;
        }

        return \App\Helpers\UrlHelper::fromRequestParams($params);
    }

    public function index(): void
    {
        $filters = $this->parseFilters();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = max(10, min(100, (int) ($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $tableReady = AuditLog::tableReady();
        $model = new AuditLog();
        $total = $tableReady ? $model->countFiltered($filters) : 0;
        $items = $tableReady ? $model->listFiltered($filters, $limit, $offset) : [];

        $pagelink = $this->buildPagelink($filters, $limit);
        $paginationInfo = PaginationHelper::infoText($total, $page, $limit);
        $paginationNav = PaginationHelper::render($total, $page, $limit, $pagelink);
        $paginationLimit = PaginationHelper::limitSelector($limit, $pagelink, ['limits' => [25, 50, 100]]);

        $kurumOptions = [];
        if (AuthHelper::sessionIsSuperAdmin() && Kurum::tableExists()) {
            $kurumOptions[] = \App\Helpers\FormHelper::makeOption('', 'Tüm kurumlar');
            foreach (\App\Helpers\TenantContext::kurumListForScope(true) as $kurumRow) {
                $kid = (int) ($kurumRow->id ?? 0);
                if ($kid <= 0) {
                    continue;
                }
                $label = (string) ($kurumRow->ad ?? '') . ' (' . (string) ($kurumRow->kod ?? '') . ')';
                $kurumOptions[] = \App\Helpers\FormHelper::makeOption((string) $kid, $label);
            }
        }

        $actionOptions = [\App\Helpers\FormHelper::makeOption('', 'Tüm işlemler')];
        foreach (AuditLogHelper::actionOptions() as $code => $label) {
            $actionOptions[] = \App\Helpers\FormHelper::makeOption($code, $label);
        }

        $entityTypeOptions = [\App\Helpers\FormHelper::makeOption('', 'Tüm varlıklar')];
        foreach (AuditLogHelper::entityTypeOptions() as $code => $label) {
            $entityTypeOptions[] = \App\Helpers\FormHelper::makeOption($code, $label);
        }

        $pageTitle = 'İşlem günlüğü (denetim)';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'audit_log/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Tablo satırları (JSON HTML parçası).
     */
    public function indexRows(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        AuthHelper::requireAdminJson();

        if (!AuditLog::tableReady()) {
            echo json_encode(['ok' => true, 'html' => ''], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $filters = $this->parseFilters();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = max(10, min(100, (int) ($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $items = (new AuditLog())->listFiltered($filters, $limit, $offset);

        ob_start();
        include ROOT_PATH . '/views/admin/audit_log/partials/index_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function exportCsv(): void
    {
        AuthHelper::requireAdmin();
        if (!AuditLog::tableReady()) {
            $_SESSION['error'] = 'İşlem günlüğü tablosu kurulu değil.';
            header('Location: ' . esh_url('AuditLog', 'index'));
            exit;
        }

        $filters = $this->parseFilters();
        $items = (new AuditLog())->listForExport($filters, 10000);

        $filename = 'audit_log_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['Tarih', 'Kullanıcı', 'Kurum', 'İşlem', 'Varlık', 'Referans', 'IP', 'URI', 'Detay'], ';');
        foreach ($items as $row) {
            fputcsv($out, [
                (string) ($row->created_at ?? ''),
                (string) ($row->user_name ?? $row->user_username ?? ''),
                (string) ($row->kurum_ad ?? ''),
                AuditLogHelper::actionLabel((string) ($row->action ?? '')),
                AuditLogHelper::entityTypeLabel((string) ($row->entity_type ?? '')),
                (string) ($row->entity_ref ?? ''),
                (string) ($row->ip_address ?? ''),
                (string) ($row->request_uri ?? ''),
                (string) ($row->context_json ?? ''),
            ], ';');
        }
        fclose($out);
        exit;
    }

    public function purgeRetention(): void
    {
        AuthHelper::requirePlatformOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CsrfHelper::validate()) {
            $_SESSION['error'] = 'Geçersiz istek.';
            header('Location: ' . esh_url('AuditLog', 'index'));
            exit;
        }
        if (!AuditLog::tableReady()) {
            $_SESSION['error'] = 'İşlem günlüğü tablosu kurulu değil.';
            header('Location: ' . esh_url('AuditLog', 'index'));
            exit;
        }

        $days = OperationalSettings::auditLogRetentionDays();
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
        $deleted = (new AuditLog())->deleteOlderThan($cutoff);
        AuditLogHelper::auditPurge(['deleted' => $deleted, 'retention_days' => $days, 'cutoff' => $cutoff]);
        $_SESSION['success'] = $deleted . ' eski denetim kaydı silindi (>' . $days . ' gün).';
        header('Location: ' . esh_url('AuditLog', 'index'));
        exit;
    }
}
