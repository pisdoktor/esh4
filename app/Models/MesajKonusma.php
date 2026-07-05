<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\IdHelper;
use App\Core\Database;

/**
 * Mesaj konuşmaları (#__mesaj_konusmalar).
 */
class MesajKonusma extends BaseModel
{
    public $id = null;
    public $tip = 'dm';
    public $kurum_id = 0;
    public $hasta_id = null;
    public $dm_kucuk_id = null;
    public $dm_buyuk_id = null;
    public $baslik = '';
    public $olusturan_id = null;
    public $son_mesaj_at = null;
    public $created_at = null;

    public function __construct()
    {
        parent::__construct('#__mesaj_konusmalar', 'id');
    }

    public static function tableReady(): bool
    {
        try {
            $db = Database::getInstance();

            return $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$db->replacePrefix('#__mesaj_konusmalar')]
            ) !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function supportsTrash(): bool
    {
        if (!self::tableReady()) {
            return false;
        }
        try {
            $db = Database::getInstance();

            return $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1',
                [$db->replacePrefix('#__mesaj_konusma_uyeler'), 'silindi_at']
            ) !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<object>
     */
    public function getMailboxForUser(string $userId, string $mailbox = 'inbox', int $limit = 50): array
    {
        $this->repairMembersForUser($userId);

        return match ($mailbox) {
            'sent' => $this->getSentForUser($userId, $limit),
            'trash' => $this->getTrashForUser($userId, $limit),
            default => $this->getInboxForUser($userId, $limit),
        };
    }

    /**
     * @return list<object>
     */
    public function getInboxForUser(string $userId, int $limit = 50): array
    {
        if ($userId === null) {
            return [];
        }
        $limit = max(1, min(100, $limit));
        $trashSql = self::supportsTrash() ? ' AND u.silindi_at IS NULL' : '';

        $list = $this->db->fetchObjectListPrepared(
            'SELECT k.*, u.son_okunan_mesaj_id,
                (SELECT COUNT(*) FROM #__mesajlar m
                 WHERE m.konusma_id = k.id AND m.id > u.son_okunan_mesaj_id) AS unread_count,
                (SELECT m2.govde FROM #__mesajlar m2
                 WHERE m2.konusma_id = k.id ORDER BY m2.id DESC LIMIT 1) AS son_mesaj_ozet,
                (SELECT m3.created_at FROM #__mesajlar m3
                 WHERE m3.konusma_id = k.id ORDER BY m3.id DESC LIMIT 1) AS son_mesaj_created,
                u.silindi_at
             FROM #__mesaj_konusmalar k
             INNER JOIN #__mesaj_konusma_uyeler u ON u.konusma_id = k.id AND u.user_id = ?' . $trashSql . '
             ORDER BY COALESCE(k.son_mesaj_at, k.created_at) DESC
             LIMIT ' . $limit,
            [$userId]
        );

        return is_array($list) ? $list : [];
    }

    /**
     * @return list<object>
     */
    public function getSentForUser(string $userId, int $limit = 50): array
    {
        if ($userId === null) {
            return [];
        }
        $limit = max(1, min(100, $limit));
        $trashSql = self::supportsTrash() ? ' AND u.silindi_at IS NULL' : '';

        $list = $this->db->fetchObjectListPrepared(
            'SELECT k.*, u.son_okunan_mesaj_id, 0 AS unread_count,
                (SELECT m2.govde FROM #__mesajlar m2
                 WHERE m2.konusma_id = k.id ORDER BY m2.id DESC LIMIT 1) AS son_mesaj_ozet,
                (SELECT m3.created_at FROM #__mesajlar m3
                 WHERE m3.konusma_id = k.id AND m3.gonderen_id = ?
                 ORDER BY m3.id DESC LIMIT 1) AS son_mesaj_created,
                u.silindi_at
             FROM #__mesaj_konusmalar k
             INNER JOIN #__mesaj_konusma_uyeler u ON u.konusma_id = k.id AND u.user_id = ?' . $trashSql . '
             WHERE EXISTS (
                SELECT 1 FROM #__mesajlar mx
                WHERE mx.konusma_id = k.id AND mx.gonderen_id = ?
             )
             ORDER BY (
                SELECT MAX(m4.created_at) FROM #__mesajlar m4
                WHERE m4.konusma_id = k.id AND m4.gonderen_id = ?
             ) DESC
             LIMIT ' . $limit,
            [$userId, $userId, $userId, $userId]
        );

        return is_array($list) ? $list : [];
    }

    /**
     * @return list<object>
     */
    public function getTrashForUser(string $userId, int $limit = 50): array
    {
        if (IdHelper::isEmptyEntityId($userId) || !self::supportsTrash()) {
            return [];
        }
        $limit = max(1, min(100, $limit));

        $list = $this->db->fetchObjectListPrepared(
            'SELECT k.*, u.son_okunan_mesaj_id, 0 AS unread_count,
                (SELECT m2.govde FROM #__mesajlar m2
                 WHERE m2.konusma_id = k.id ORDER BY m2.id DESC LIMIT 1) AS son_mesaj_ozet,
                (SELECT m3.created_at FROM #__mesajlar m3
                 WHERE m3.konusma_id = k.id ORDER BY m3.id DESC LIMIT 1) AS son_mesaj_created,
                u.silindi_at
             FROM #__mesaj_konusmalar k
             INNER JOIN #__mesaj_konusma_uyeler u ON u.konusma_id = k.id AND u.user_id = ?
             WHERE u.silindi_at IS NOT NULL
             ORDER BY u.silindi_at DESC
             LIMIT ' . $limit,
            [$userId]
        );

        return is_array($list) ? $list : [];
    }

    public function moveToTrash(string $konusmaId, string $userId): bool
    {
        if (IdHelper::isEmptyEntityId($konusmaId) || IdHelper::isEmptyEntityId($userId) || !self::supportsTrash()) {
            return false;
        }

        return $this->db->executePrepared(
            'UPDATE #__mesaj_konusma_uyeler SET silindi_at = ? WHERE konusma_id = ? AND user_id = ? AND silindi_at IS NULL',
            [date('Y-m-d H:i:s'), $konusmaId, $userId]
        );
    }

    public function restoreFromTrash(string $konusmaId, string $userId): bool
    {
        if (IdHelper::isEmptyEntityId($konusmaId) || IdHelper::isEmptyEntityId($userId) || !self::supportsTrash()) {
            return false;
        }

        return $this->db->executePrepared(
            'UPDATE #__mesaj_konusma_uyeler SET silindi_at = NULL WHERE konusma_id = ? AND user_id = ? AND silindi_at IS NOT NULL',
            [$konusmaId, $userId]
        );
    }

    public function purgeFromTrash(string $konusmaId, string $userId): bool
    {
        if (IdHelper::isEmptyEntityId($konusmaId) || IdHelper::isEmptyEntityId($userId) || !self::supportsTrash()) {
            return false;
        }

        return $this->db->executePrepared(
            'DELETE FROM #__mesaj_konusma_uyeler WHERE konusma_id = ? AND user_id = ? AND silindi_at IS NOT NULL',
            [$konusmaId, $userId]
        );
    }

    public function restoreIfTrashed(string $konusmaId, string $userId): void
    {
        if (IdHelper::isEmptyEntityId($konusmaId) || IdHelper::isEmptyEntityId($userId) || !self::supportsTrash()) {
            return;
        }
        $this->db->executePrepared(
            'UPDATE #__mesaj_konusma_uyeler SET silindi_at = NULL WHERE konusma_id = ? AND user_id = ? AND silindi_at IS NOT NULL',
            [$konusmaId, $userId]
        );
    }

    public function isTrashedForUser(string $konusmaId, string $userId): bool
    {
        if (IdHelper::isEmptyEntityId($konusmaId) || IdHelper::isEmptyEntityId($userId) || !self::supportsTrash()) {
            return false;
        }
        $row = $this->getMemberRow($konusmaId, $userId);
        if (!$row) {
            return false;
        }

        return !empty($row->silindi_at);
    }

    public function findById(string $id): ?object
    {
        return $this->db->fetchObjectPrepared(
            'SELECT * FROM #__mesaj_konusmalar WHERE id = ? LIMIT 1',
            [$id]
        ) ?: null;
    }

    public function findDmPair(string $userA, string $userB): ?object
    {
        $userA = IdHelper::normalizeRequestId($userA);
        $userB = IdHelper::normalizeRequestId($userB);
        if ($userA === null || $userB === null) {
            return null;
        }
        [$low, $high] = strcmp($userA, $userB) <= 0 ? [$userA, $userB] : [$userB, $userA];

        return $this->db->fetchObjectPrepared(
            'SELECT * FROM #__mesaj_konusmalar WHERE tip = ? AND dm_kucuk_id = ? AND dm_buyuk_id = ? LIMIT 1',
            ['dm', $low, $high]
        ) ?: null;
    }

    public function findByHastaId(string $hastaId): ?object
    {
        if (IdHelper::isEmptyEntityId($hastaId)) {
            return null;
        }

        return $this->db->fetchObjectPrepared(
            'SELECT * FROM #__mesaj_konusmalar WHERE tip = ? AND hasta_id = ? LIMIT 1',
            ['patient', $hastaId]
        ) ?: null;
    }

    public function touchLastMessage(string $konusmaId, string $at = ''): void
    {
        if (IdHelper::isEmptyEntityId($konusmaId)) {
            return;
        }
        $ts = $at !== '' ? $at : date('Y-m-d H:i:s');
        $this->db->executePrepared(
            'UPDATE #__mesaj_konusmalar SET son_mesaj_at = ? WHERE id = ?',
            [$ts, $konusmaId]
        );
    }

    public function isMember(string $konusmaId, string $userId): bool
    {
        if (IdHelper::isEmptyEntityId($konusmaId) || IdHelper::isEmptyEntityId($userId)) {
            return false;
        }

        return $this->db->loadResultPrepared(
            'SELECT 1 FROM #__mesaj_konusma_uyeler WHERE konusma_id = ? AND user_id = ? LIMIT 1',
            [$konusmaId, $userId]
        ) !== null;
    }

    public function addMember(string $konusmaId, string $userId): bool
    {
        if (IdHelper::isEmptyEntityId($konusmaId) || IdHelper::isEmptyEntityId($userId) || !$this->isActivatedUser($userId)) {
            return false;
        }
        if ($this->isMember($konusmaId, $userId)) {
            return true;
        }
        $id = $this->db->insertPrepared('#__mesaj_konusma_uyeler', [
            'id' => IdHelper::generateUuidV4(),
            'konusma_id' => $konusmaId,
            'user_id' => $userId,
            'son_okunan_mesaj_id' => null,
        ]);

        return $id !== false;
    }

    /**
     * Konuşma tipine göre kullanıcının üye olması gerekiyorsa ekler (eksik üye satırları için).
     */
    public function ensureImplicitMember(object $konusma, string $userId): bool
    {
        if (IdHelper::isEmptyEntityId($userId) || !$this->isActivatedUser($userId)) {
            return false;
        }
        $konusmaId = (string) ($konusma->id ?? '');
        if (IdHelper::isEmptyEntityId($konusmaId)) {
            return false;
        }
        if ($this->isMember($konusmaId, $userId)) {
            return true;
        }

        $tip = (string) ($konusma->tip ?? '');
        if ($tip === 'dm') {
            $low = (string) ($konusma->dm_kucuk_id ?? '');
            $high = (string) ($konusma->dm_buyuk_id ?? '');
            if ($userId !== $low && $userId !== $high) {
                return false;
            }
            $this->addMember($konusmaId, $low);
            $this->addMember($konusmaId, $high);

            return $this->isMember($konusmaId, $userId);
        }

        if ($tip === 'patient' || $tip === 'system') {
            if (IdHelper::idsMatch($konusma->olusturan_id ?? null, $userId)) {
                return $this->addMember($konusmaId, $userId);
            }
            $sent = $this->db->loadResultPrepared(
                'SELECT 1 FROM #__mesajlar WHERE konusma_id = ? AND gonderen_id = ? LIMIT 1',
                [$konusmaId, $userId]
            );

            return $sent !== null && $this->addMember($konusmaId, $userId);
        }

        return false;
    }

    /**
     * Gelen/giden kutusu için eksik üye kayıtlarını mevcut konuşma ve mesaj verisinden tamamlar.
     */
    public function repairMembersForUser(string $userId): void
    {
        if (IdHelper::isEmptyEntityId($userId) || !self::tableReady() || !$this->isActivatedUser($userId)) {
            return;
        }

        $dmRows = $this->db->fetchAllPrepared(
            'SELECT id, dm_kucuk_id, dm_buyuk_id FROM #__mesaj_konusmalar
             WHERE tip = ? AND (dm_kucuk_id = ? OR dm_buyuk_id = ?)',
            ['dm', $userId, $userId]
        );
        foreach ($dmRows as $row) {
            $kid = (string) ($row['id'] ?? '');
            $low = (string) ($row['dm_kucuk_id'] ?? '');
            $high = (string) ($row['dm_buyuk_id'] ?? '');
            if ($kid === '') {
                continue;
            }
            if ($low !== '') {
                $this->addMember($kid, $low);
            }
            if ($high !== '') {
                $this->addMember($kid, $high);
            }
        }

        $sentRows = $this->db->fetchAllPrepared(
            'SELECT DISTINCT konusma_id FROM #__mesajlar WHERE gonderen_id = ?',
            [$userId]
        );
        foreach ($sentRows as $row) {
            $kid = (string) ($row['konusma_id'] ?? '');
            if ($kid !== '') {
                $this->addMember($kid, $userId);
            }
        }

        $createdRows = $this->db->fetchAllPrepared(
            'SELECT id FROM #__mesaj_konusmalar WHERE olusturan_id = ?',
            [$userId]
        );
        foreach ($createdRows as $row) {
            $kid = (string) ($row['id'] ?? '');
            if ($kid !== '') {
                $this->addMember($kid, $userId);
            }
        }
    }

    public function getMemberRow(string $konusmaId, string $userId): ?object
    {
        return $this->db->fetchObjectPrepared(
            'SELECT * FROM #__mesaj_konusma_uyeler WHERE konusma_id = ? AND user_id = ? LIMIT 1',
            [$konusmaId, $userId]
        ) ?: null;
    }

    private function isActivatedUser(string $userId): bool
    {
        if (IdHelper::isEmptyEntityId($userId)) {
            return false;
        }
        $u = new User();

        return $u->load($userId) && (int) ($u->activated ?? 0) === 1;
    }
}
