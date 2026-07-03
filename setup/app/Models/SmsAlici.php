<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class SmsAlici extends BaseModel
{
    public function __construct()
    {
        parent::__construct('#__sms_alici', 'id');
    }

    public static function tableReady(): bool
    {
        try {
            $db = Database::getInstance();

            return $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$db->replacePrefix('#__sms_alici')]
            ) !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    public function insertRow(array $data): int
    {
        if (!self::tableReady()) {
            return 0;
        }
        $id = $this->db->insertPrepared('#__sms_alici', $data);

        return $id !== false ? (int) $id : 0;
    }

    public function updateRow(int $id, array $data): bool
    {
        if ($id <= 0) {
            return false;
        }

        return (bool) $this->db->updatePrepared('#__sms_alici', $data, 'id = ?', [$id]);
    }

    /**
     * @return list<object>
     */
    public function listByGonderim(int $gonderimId): array
    {
        if ($gonderimId <= 0 || !self::tableReady()) {
            return [];
        }
        $list = $this->db->fetchObjectListPrepared(
            'SELECT a.*, h.isim, h.soyisim
             FROM #__sms_alici a
             LEFT JOIN #__hastalar h ON h.id = a.hasta_id
             WHERE a.gonderim_id = ?
             ORDER BY a.id ASC',
            [$gonderimId]
        );

        return is_array($list) ? $list : [];
    }

    /**
     * @return list<object>
     */
    public function listPending(int $limit = 100): array
    {
        if (!self::tableReady()) {
            return [];
        }
        $limit = max(1, min(500, $limit));
        $list = $this->db->fetchObjectListPrepared(
            'SELECT * FROM #__sms_alici WHERE durum = ? ORDER BY id ASC LIMIT ' . $limit,
            ['beklemede']
        );

        return is_array($list) ? $list : [];
    }
}
