<?php
declare(strict_types=1);

namespace App\Models;

use App\Helpers\AuthHelper;
use App\Helpers\TenantContext;
use App\Helpers\ValidationHelper;

/**
 * KVKK / iç denetim işlem günlüğü (#__audit_log).
 */
class AuditLog extends BaseModel
{
    public $id = null;
    public $kurum_id = null;
    public $user_id = null;
    public $action = '';
    public $entity_type = '';
    public $entity_id = null;
    public $entity_ref = null;
    public $ip_address = null;
    public $user_agent = null;
    public $request_uri = null;
    public $context_json = null;
    public $created_at = null;

    /** @var bool|null */
    private static $tableReady = null;

    public function __construct()
    {
        parent::__construct('#__audit_log', 'id');
    }

    public static function tableReady(): bool
    {
        if (self::$tableReady !== null) {
            return self::$tableReady;
        }
        try {
            $db = \App\Core\Database::getInstance();
            $tbl = $db->replacePrefix('#__audit_log');
            $row = $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
                [$tbl]
            );
            self::$tableReady = $row !== null && $row !== false && $row !== '';
        } catch (\Throwable $e) {
            self::$tableReady = false;
        }

        return self::$tableReady;
    }

    /**
     * @param array<string, mixed> $row
     */
    public function insertRow(array $row): bool
    {
        if (!self::tableReady()) {
            return false;
        }
        $id = $this->db->insertPrepared('#__audit_log', $row);

        return $id !== false;
    }

    /**
     * @return array{0: list<string>, 1: list<mixed>}
     */
    private function buildFilterParts(array $filters): array
    {
        $where = ['1=1'];
        $params = [];

        if (!AuthHelper::sessionIsSuperAdmin()) {
            $kid = TenantContext::sessionKurumId();
            if ($kid !== null && $kid > 0) {
                $where[] = 'a.kurum_id = ?';
                $params[] = $kid;
            }
        } else {
            $filterKid = (int) ($filters['kurum_id'] ?? 0);
            if ($filterKid > 0) {
                $where[] = 'a.kurum_id = ?';
                $params[] = $filterKid;
            }
        }

        $action = trim((string) ($filters['action'] ?? ''));
        if ($action !== '') {
            $where[] = 'a.action = ?';
            $params[] = $action;
        }

        $entityType = trim((string) ($filters['entity_type'] ?? ''));
        if ($entityType !== '') {
            $where[] = 'a.entity_type = ?';
            $params[] = $entityType;
        }

        $entityRef = ValidationHelper::tcDigitsOnly((string) ($filters['entity_ref'] ?? ''));
        if ($entityRef !== '' && ValidationHelper::isTcLength11($entityRef)) {
            $where[] = 'a.entity_ref = ?';
            $params[] = $entityRef;
        }

        $userId = (int) ($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $where[] = 'a.user_id = ?';
            $params[] = $userId;
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $where[] = 'a.created_at >= ?';
            $params[] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $where[] = 'a.created_at <= ?';
            $params[] = $dateTo . ' 23:59:59';
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(a.action LIKE ? OR a.entity_type LIKE ? OR a.entity_ref LIKE ? OR a.request_uri LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        return [$where, $params];
    }

    /**
     * @return list<object>
     */
    public function listFiltered(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        if (!self::tableReady()) {
            return [];
        }

        [$where, $params] = $this->buildFilterParts($filters);
        $lim = max(1, min(200, $limit));
        $off = max(0, $offset);

        $sql = 'SELECT a.*, u.name AS user_name, u.username AS user_username, k.ad AS kurum_ad
                FROM #__audit_log AS a
                LEFT JOIN #__users AS u ON u.id = a.user_id
                LEFT JOIN #__kurumlar AS k ON k.id = a.kurum_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY a.created_at DESC, a.id DESC
                LIMIT ' . $lim . ' OFFSET ' . $off;

        $list = $this->db->fetchObjectListPrepared($sql, $params);

        return is_array($list) ? $list : [];
    }

    public function countFiltered(array $filters = []): int
    {
        if (!self::tableReady()) {
            return 0;
        }

        [$where, $params] = $this->buildFilterParts($filters);

        $sql = 'SELECT COUNT(*) FROM #__audit_log AS a WHERE ' . implode(' AND ', $where);
        $n = $this->db->loadResultPrepared($sql, $params);

        return (int) ($n ?? 0);
    }

    /**
     * @return list<object>
     */
    public function listForExport(array $filters = [], int $limit = 10000): array
    {
        if (!self::tableReady()) {
            return [];
        }

        [$where, $params] = $this->buildFilterParts($filters);
        $lim = max(1, min(50000, $limit));

        $sql = 'SELECT a.*, u.name AS user_name, u.username AS user_username, k.ad AS kurum_ad
                FROM #__audit_log AS a
                LEFT JOIN #__users AS u ON u.id = a.user_id
                LEFT JOIN #__kurumlar AS k ON k.id = a.kurum_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY a.created_at DESC, a.id DESC
                LIMIT ' . $lim;

        $list = $this->db->fetchObjectListPrepared($sql, $params);

        return is_array($list) ? $list : [];
    }

    public function deleteOlderThan(string $cutoffDatetime): int
    {
        if (!self::tableReady() || $cutoffDatetime === '') {
            return 0;
        }

        $ok = $this->db->executePrepared(
            'DELETE FROM #__audit_log WHERE created_at < ?',
            [$cutoffDatetime]
        );

        return $ok ? (int) $this->db->affectedRows() : 0;
    }
}
