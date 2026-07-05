<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Federasyon dosya senkron günlüğü (`#__federation_sync_log`).
 */
class FederationSyncLog extends BaseModel
{
    public $id = null;
    public $user_id = null;
    public $direction = null;
    public $status = null;
    public $file_name = null;
    public $stats_json = null;
    public $error_message = null;
    public $created_at = null;

    public function __construct()
    {
        parent::__construct('#__federation_sync_log', 'id');
    }

    /**
     * @param array<string, mixed> $stats
     */
    public function record(
        string $direction,
        string $status,
        ?string $userId,
        ?string $fileName,
        array $stats = [],
        ?string $errorMessage = null
    ): bool {
        $this->bind([
            'direction' => $direction,
            'status' => $status,
            'user_id' => $userId,
            'file_name' => $fileName,
            'stats_json' => $stats !== [] ? json_encode($stats, JSON_UNESCAPED_UNICODE) : null,
            'error_message' => $errorMessage !== null && $errorMessage !== ''
                ? substr($errorMessage, 0, 512)
                : null,
        ], true);

        return $this->store();
    }

    /** @return list<object> */
    public function recent(int $limit = 25): array
    {
        $limit = max(1, min(100, $limit));
        $sql = 'SELECT * FROM ' . $this->_tbl . ' ORDER BY id DESC LIMIT ' . $limit;
        $rows = $this->db->fetchObjectListPrepared($sql, []);

        return is_array($rows) ? $rows : [];
    }
}
