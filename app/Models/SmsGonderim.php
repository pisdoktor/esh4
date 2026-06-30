<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Helpers\TenantSqlHelper;

class SmsGonderim extends BaseModel
{
    public $id = null;
    public $kurum_id = 0;
    public $olusturan_id = 0;
    public $segment_tipi = 'tek_hasta';
    public $segment_param_json = null;
    public $sablon_id = null;
    public $govde_ozet = '';
    public $mesaj_turu = 'bilgilendirme';
    public $durum = 'beklemede';
    public $toplam = 0;
    public $basarili = 0;
    public $basarisiz = 0;
    public $created_at = null;

    public function __construct()
    {
        parent::__construct('#__sms_gonderim', 'id');
    }

    public static function tableReady(): bool
    {
        try {
            $db = Database::getInstance();

            return $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$db->replacePrefix('#__sms_gonderim')]
            ) !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    public function createBatch(array $data): int
    {
        if (!self::tableReady()) {
            return 0;
        }
        $id = $this->db->insertPrepared('#__sms_gonderim', $data);

        return $id !== false ? (int) $id : 0;
    }

    public function updateStats(int $id, string $durum, int $toplam, int $basarili, int $basarisiz): bool
    {
        if ($id <= 0) {
            return false;
        }

        return (bool) $this->db->updatePrepared('#__sms_gonderim', [
            'durum' => $durum,
            'toplam' => $toplam,
            'basarili' => $basarili,
            'basarisiz' => $basarisiz,
        ], 'id = ?', [$id]);
    }

    /**
     * @return list<object>
     */
    public function listRecent(int $limit = 50, ?int $kurumIdFilter = null): array
    {
        if (!self::tableReady()) {
            return [];
        }
        $where = ['1=1'];
        $params = [];
        if ($kurumIdFilter !== null && $kurumIdFilter > 0) {
            $where[] = 'g.kurum_id = ?';
            $params[] = $kurumIdFilter;
        } else {
            TenantSqlHelper::mergeParts($where, 'g', 'kurum_id');
        }
        $limit = max(1, min(200, $limit));
        $sql = 'SELECT g.*, u.name AS olusturan_adi
            FROM #__sms_gonderim g
            LEFT JOIN #__users u ON u.id = g.olusturan_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY g.created_at DESC
            LIMIT ' . $limit;

        $list = $this->db->fetchObjectListPrepared($sql, $params);

        return is_array($list) ? $list : [];
    }

    public function findById(int $id): ?object
    {
        if ($id <= 0 || !self::tableReady()) {
            return null;
        }
        $row = $this->db->fetchOnePrepared(
            'SELECT g.*, u.name AS olusturan_adi FROM #__sms_gonderim g
             LEFT JOIN #__users u ON u.id = g.olusturan_id
             WHERE g.id = ? LIMIT 1',
            [$id]
        );

        return is_object($row) ? $row : null;
    }

    public function countSentToday(int $kurumId): int
    {
        if ($kurumId <= 0 || !self::tableReady()) {
            return 0;
        }
        $val = $this->db->loadResultPrepared(
            'SELECT COALESCE(SUM(basarili), 0) FROM #__sms_gonderim
             WHERE kurum_id = ? AND DATE(created_at) = CURDATE()',
            [$kurumId]
        );

        return $val !== null ? (int) $val : 0;
    }
}
