<?php
declare(strict_types=1);

namespace App\Models;

/**
 * REST API bearer token (#__api_tokens).
 */
class ApiToken extends BaseModel
{
    public $id = null;
    public $user_id = null;
    public $kurum_id = null;
    public $label = '';
    public $token_prefix = '';
    public $token_hash = '';
    public $scopes = 'read';
    public $expires_at = null;
    public $last_used_at = null;
    public $created_at = null;
    public $revoked_at = null;

    /** @var bool|null */
    private static $tableReady = null;

    public function __construct()
    {
        parent::__construct('#__api_tokens', 'id');
    }

    public static function tableReady(): bool
    {
        if (self::$tableReady !== null) {
            return self::$tableReady;
        }
        try {
            $db = \App\Core\Database::getInstance();
            $tbl = $db->replacePrefix('#__api_tokens');
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
     * @return list<object>
     */
    public function listActive(): array
    {
        if (!self::tableReady()) {
            return [];
        }
        $list = $this->db->fetchObjectListPrepared(
            'SELECT t.*, u.name AS user_name, u.username AS user_username
             FROM #__api_tokens AS t
             LEFT JOIN #__users AS u ON u.id = t.user_id
             WHERE t.revoked_at IS NULL
             ORDER BY t.created_at DESC, t.id DESC'
        );

        return is_array($list) ? $list : [];
    }

    public function findByPrefixAndHash(string $prefix, string $hash): ?object
    {
        if (!self::tableReady() || $prefix === '' || $hash === '') {
            return null;
        }
        $row = $this->db->fetchObjectPrepared(
            'SELECT * FROM #__api_tokens
             WHERE token_prefix = ? AND token_hash = ? AND revoked_at IS NULL
             LIMIT 1',
            [$prefix, $hash]
        );

        return $row ?: null;
    }

    public function touchLastUsed(int $id): void
    {
        if (!self::tableReady() || $id <= 0) {
            return;
        }
        $this->db->updatePrepared(
            '#__api_tokens',
            ['last_used_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$id]
        );
    }

    public function revoke(int $id): bool
    {
        if (!self::tableReady() || $id <= 0) {
            return false;
        }

        return (bool) $this->db->updatePrepared(
            '#__api_tokens',
            ['revoked_at' => date('Y-m-d H:i:s')],
            'id = ? AND revoked_at IS NULL',
            [$id]
        );
    }
}
