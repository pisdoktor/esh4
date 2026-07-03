<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class SmsSablon extends BaseModel
{
    public $id = null;
    public $kurum_id = null;
    public $kod = '';
    public $baslik = '';
    public $govde = '';
    public $degiskenler_json = null;
    public $aktif = 1;
    public $created_at = null;

    public function __construct()
    {
        parent::__construct('#__sms_sablonlari', 'id');
    }

    public static function tableReady(): bool
    {
        try {
            $db = Database::getInstance();

            return $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$db->replacePrefix('#__sms_sablonlari')]
            ) !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<object>
     */
    public function listForKurum(?int $kurumId, bool $activeOnly = true): array
    {
        if (!self::tableReady()) {
            return [];
        }
        $sql = 'SELECT * FROM #__sms_sablonlari WHERE (kurum_id IS NULL';
        $params = [];
        if ($kurumId !== null && $kurumId > 0) {
            $sql .= ' OR kurum_id = ?';
            $params[] = $kurumId;
        }
        $sql .= ')';
        if ($activeOnly) {
            $sql .= ' AND aktif = 1';
        }
        $sql .= ' ORDER BY kurum_id IS NULL DESC, baslik ASC';
        $list = $this->db->fetchObjectListPrepared($sql, $params);

        return is_array($list) ? $list : [];
    }

    public function findById(int $id): ?object
    {
        if ($id <= 0 || !self::tableReady()) {
            return null;
        }
        $row = $this->db->fetchOnePrepared('SELECT * FROM #__sms_sablonlari WHERE id = ? LIMIT 1', [$id]);

        return is_object($row) ? $row : null;
    }
}
