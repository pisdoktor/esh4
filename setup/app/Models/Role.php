<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Platform rolü (#__roles).
 */
class Role extends BaseModel
{
    public $id = null;
    public $slug = null;
    /** @var string|null esh_users.unvan ile otomatik eşleme */
    public $unvan_code = null;
    public $name = null;
    public $description = null;
    public $is_system = 0;
    public $sort_order = 0;

    public function __construct()
    {
        parent::__construct('#__roles', 'id');
    }

    public function loadBySlug(string $slug): bool
    {
        $slug = trim($slug);
        if ($slug === '') {
            return false;
        }
        $row = $this->db->fetchObjectPrepared(
            'SELECT * FROM #__roles WHERE slug = ? LIMIT 1',
            [$slug]
        );
        if ($row === null) {
            return false;
        }
        $this->bind($row, false);

        return true;
    }

    /**
     * @return list<object>
     */
    public function listAll(): array
    {
        $rows = $this->db->fetchObjectListPrepared(
            'SELECT * FROM #__roles ORDER BY sort_order, name'
        );

        return is_array($rows) ? $rows : [];
    }

    public static function isTablesReady(): bool
    {
        try {
            $db = Database::getInstance();
            $tbl = $db->replacePrefix('#__roles');
            $row = $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
                [$tbl]
            );

            return $row !== null && $row !== false && $row !== '';
        } catch (\Throwable $e) {
            return false;
        }
    }
}
