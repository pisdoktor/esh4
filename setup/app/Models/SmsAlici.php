<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Helpers\IdHelper;

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

    public function insertRow(array $data): string|false
    {
        if (!self::tableReady()) {
            return false;
        }
        if (!isset($data['id']) || IdHelper::isEmptyEntityId($data['id'] ?? null)) {
            $data['id'] = IdHelper::generateUuidV4();
        }
        $id = $this->db->insertPrepared('#__sms_alici', $data);

        return $id !== false ? (string) $id : false;
    }

    public function updateRow(int|string $id, array $data): bool
    {
        $rid = IdHelper::normalizeRequestId($id);
        if ($rid === null) {
            return false;
        }

        return (bool) $this->db->updatePrepared('#__sms_alici', $data, 'id = ?', [$rid]);
    }

    /**
     * @return list<object>
     */
    public function listByGonderim(int|string $gonderimId): array
    {
        $gid = IdHelper::normalizeRequestId($gonderimId);
        if ($gid === null || !self::tableReady()) {
            return [];
        }
        $list = $this->db->fetchObjectListPrepared(
            'SELECT a.*, h.isim, h.soyisim
             FROM #__sms_alici a
             LEFT JOIN #__hastalar h ON h.id = a.hasta_id
             WHERE a.gonderim_id = ?
             ORDER BY a.id ASC',
            [$gid]
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
