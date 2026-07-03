<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\DbSqlHelper;
use App\Helpers\CatalogStoreHelper;

/**
 * Kurum ↔ platform tanı ataması — #__kurum_hastalik.
 */
class KurumHastalik extends BaseModel
{
    public function __construct()
    {
        parent::__construct('#__kurum_hastalik', 'kurum_id');
    }

    public static function tableExists(): bool
    {
        try {
            $db = Database::getInstance();
            $tbl = $db->replacePrefix('#__kurum_hastalik');

            if (DbSqlHelper::isSqlSrv()) {
                return (int) $db->loadResultPrepared(
                    'SELECT COUNT(*) FROM sys.tables WHERE name = ?',
                    [$tbl]
                ) > 0;
            }

            return (int) $db->loadResultPrepared(
                'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$tbl]
            ) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public function assign(int $kurumId, int $hastalikId): bool
    {
        if ($kurumId <= 0 || $hastalikId <= 0 || !self::tableExists()) {
            return false;
        }
        if (!$this->isPlatformHastalik($hastalikId)) {
            return false;
        }
        if ($this->isAssigned($kurumId, $hastalikId)) {
            return true;
        }

        return $this->db->insertPrepared('#__kurum_hastalik', [
            'kurum_id' => $kurumId,
            'hastalik_id' => $hastalikId,
        ]) !== false;
    }

    public function unassign(int $kurumId, int $hastalikId): bool
    {
        if ($kurumId <= 0 || $hastalikId <= 0 || !self::tableExists()) {
            return false;
        }

        return $this->db->executePrepared(
            'DELETE FROM #__kurum_hastalik WHERE kurum_id = ? AND hastalik_id = ?',
            [$kurumId, $hastalikId]
        );
    }

    public function isAssigned(int $kurumId, int $hastalikId): bool
    {
        if ($kurumId <= 0 || $hastalikId <= 0 || !self::tableExists()) {
            return false;
        }

        return (int) $this->db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__kurum_hastalik WHERE kurum_id = ? AND hastalik_id = ?',
            [$kurumId, $hastalikId]
        ) > 0;
    }

    /** @return list<int> */
    public function getAssignedIds(int $kurumId): array
    {
        if ($kurumId <= 0 || !self::tableExists()) {
            return [];
        }

        $rows = $this->db->fetchColumnListPrepared(
            'SELECT hastalik_id FROM #__kurum_hastalik WHERE kurum_id = ? ORDER BY hastalik_id ASC',
            [$kurumId]
        );

        if (!is_array($rows)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $rows)));
    }

    /** @param list<int> $hastalikIds */
    public function syncSelection(int $kurumId, array $hastalikIds): int
    {
        if ($kurumId <= 0 || !self::tableExists()) {
            return 0;
        }

        $hastalikIds = array_values(array_unique(array_filter(array_map('intval', $hastalikIds))));
        $current = $this->getAssignedIds($kurumId);

        foreach (array_diff($current, $hastalikIds) as $removeId) {
            $this->unassign($kurumId, (int) $removeId);
        }

        $assigned = 0;
        foreach ($hastalikIds as $hid) {
            if ($hid > 0 && $this->assign($kurumId, $hid)) {
                $assigned++;
            }
        }

        return $assigned;
    }

    public function countAssigned(int $kurumId): int
    {
        if ($kurumId <= 0 || !self::tableExists()) {
            return 0;
        }

        return (int) $this->db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__kurum_hastalik WHERE kurum_id = ?',
            [$kurumId]
        );
    }

    private function isPlatformHastalik(int $hastalikId): bool
    {
        return (int) $this->db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__hastaliklar WHERE id = ? AND kurum_id = ?',
            [$hastalikId, CatalogStoreHelper::PLATFORM_KURUM_ID]
        ) > 0;
    }
}
