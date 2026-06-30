<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Mesaj satırları (#__mesajlar).
 */
class Mesaj extends BaseModel
{
    public $id = null;
    public $konusma_id = 0;
    public $gonderen_id = null;
    public $gonderen_tip = 'user';
    public $govde = '';
    public $created_at = null;

    public const MAX_BODY_LEN = 4000;

    public function __construct()
    {
        parent::__construct('#__mesajlar', 'id');
    }

    public function insertMessage(int $konusmaId, ?int $senderId, string $senderType, string $body): int
    {
        $body = trim($body);
        if ($konusmaId <= 0 || $body === '') {
            return 0;
        }
        if (mb_strlen($body) > self::MAX_BODY_LEN) {
            $body = mb_substr($body, 0, self::MAX_BODY_LEN);
        }
        $senderType = $senderType === 'system' ? 'system' : 'user';

        $id = $this->db->insertPrepared('#__mesajlar', [
            'konusma_id' => $konusmaId,
            'gonderen_id' => $senderId,
            'gonderen_tip' => $senderType,
            'govde' => $body,
        ]);

        return $id !== false ? (int) $id : 0;
    }

    /**
     * @return list<object>
     */
    public function getMessages(int $konusmaId, int $sinceId = 0, int $limit = 100): array
    {
        if ($konusmaId <= 0) {
            return [];
        }
        $sinceId = max(0, $sinceId);
        $limit = max(1, min(200, $limit));

        $sql = 'SELECT m.*, u.name AS gonderen_adi
            FROM #__mesajlar m
            LEFT JOIN #__users u ON u.id = m.gonderen_id
            WHERE m.konusma_id = ?';
        $params = [$konusmaId];
        if ($sinceId > 0) {
            $sql .= ' AND m.id > ?';
            $params[] = $sinceId;
        } else {
            $sql .= ' ORDER BY m.id DESC LIMIT ' . $limit;
            $list = $this->db->fetchObjectListPrepared($sql, $params);
            if (!is_array($list)) {
                return [];
            }

            return array_reverse($list);
        }
        $sql .= ' ORDER BY m.id ASC';

        $list = $this->db->fetchObjectListPrepared($sql, $params);

        return is_array($list) ? $list : [];
    }

    public function getLastMessageId(int $konusmaId): int
    {
        if ($konusmaId <= 0) {
            return 0;
        }
        $val = $this->db->loadResultPrepared(
            'SELECT id FROM #__mesajlar WHERE konusma_id = ? ORDER BY id DESC LIMIT 1',
            [$konusmaId]
        );

        return $val !== null ? (int) $val : 0;
    }

    public static function countUnreadForUser(int $userId): int
    {
        if ($userId <= 0 || !MesajKonusma::tableReady()) {
            return 0;
        }
        $db = Database::getInstance();
        $trashSql = MesajKonusma::supportsTrash() ? ' AND u.silindi_at IS NULL' : '';
        $val = $db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__mesajlar m
             INNER JOIN #__mesaj_konusma_uyeler u ON u.konusma_id = m.konusma_id AND u.user_id = ?
             WHERE m.id > u.son_okunan_mesaj_id' . $trashSql,
            [$userId]
        );

        return $val !== null ? (int) $val : 0;
    }
}
