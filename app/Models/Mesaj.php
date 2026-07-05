<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\IdHelper;
use App\Core\Database;

/**
 * Mesaj satırları (#__mesajlar).
 */
class Mesaj extends BaseModel
{
    /** @var bool */
    protected $uuidPrimaryKey = true;

    public $id = null;
    public $konusma_id = null;
    public $gonderen_id = null;
    public $gonderen_tip = 'user';
    public $govde = '';
    public $created_at = null;

    public const MAX_BODY_LEN = 4000;

    public function __construct()
    {
        parent::__construct('#__mesajlar', 'id');
    }

    public function insertMessage(string $konusmaId, ?string $senderId, string $senderType, string $body): ?string
    {
        $body = trim($body);
        if (IdHelper::isEmptyEntityId($konusmaId) || $body === '') {
            return null;
        }
        if (mb_strlen($body) > self::MAX_BODY_LEN) {
            $body = mb_substr($body, 0, self::MAX_BODY_LEN);
        }
        $senderType = $senderType === 'system' ? 'system' : 'user';

        $id = $this->db->insertPrepared('#__mesajlar', [
            'id' => IdHelper::generateUuidV4(),
            'konusma_id' => $konusmaId,
            'gonderen_id' => $senderId,
            'gonderen_tip' => $senderType,
            'govde' => $body,
        ]);

        return is_string($id) && $id !== '' ? $id : null;
    }

    /**
     * @return list<object>
     */
    public function getMessages(string $konusmaId, ?string $sinceMessageId = null, int $limit = 100): array
    {
        if (IdHelper::isEmptyEntityId($konusmaId)) {
            return [];
        }
        $limit = max(1, min(200, $limit));

        $sql = 'SELECT m.*, u.name AS gonderen_adi
            FROM #__mesajlar m
            LEFT JOIN #__users u ON u.id = m.gonderen_id
            WHERE m.konusma_id = ?';
        $params = [$konusmaId];

        if ($sinceMessageId !== null && $sinceMessageId !== '') {
            $sql .= ' AND m.created_at > COALESCE(
                (SELECT created_at FROM #__mesajlar WHERE id = ? AND konusma_id = ? LIMIT 1),
                \'1970-01-01 00:00:00\'
            )';
            $params[] = $sinceMessageId;
            $params[] = $konusmaId;
            $sql .= ' ORDER BY m.created_at ASC, m.id ASC';

            $list = $this->db->fetchObjectListPrepared($sql, $params);

            return is_array($list) ? $list : [];
        }

        $sql .= ' ORDER BY m.id DESC LIMIT ' . $limit;
        $list = $this->db->fetchObjectListPrepared($sql, $params);
        if (!is_array($list)) {
            return [];
        }

        return array_reverse($list);
    }

    public function getLastMessageId(string $konusmaId): ?string
    {
        if (IdHelper::isEmptyEntityId($konusmaId)) {
            return null;
        }
        $val = $this->db->loadResultPrepared(
            'SELECT id FROM #__mesajlar WHERE konusma_id = ? ORDER BY created_at DESC, id DESC LIMIT 1',
            [$konusmaId]
        );

        return is_string($val) && $val !== '' ? $val : null;
    }

    public static function countUnreadForUser(string $userId): int
    {
        if (IdHelper::isEmptyEntityId($userId) || !MesajKonusma::tableReady()) {
            return 0;
        }
        $db = Database::getInstance();
        $trashSql = MesajKonusma::supportsTrash() ? ' AND u.silindi_at IS NULL' : '';
        $val = $db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__mesajlar m
             INNER JOIN #__mesaj_konusma_uyeler u ON u.konusma_id = m.konusma_id AND u.user_id = ?
             WHERE m.created_at > COALESCE(
                (SELECT m2.created_at FROM #__mesajlar m2 WHERE m2.id = u.son_okunan_mesaj_id LIMIT 1),
                \'1970-01-01 00:00:00\'
             )' . $trashSql,
            [$userId]
        );

        return $val !== null ? (int) $val : 0;
    }
}
