<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Helpers\TenantSqlHelper;

/**
 * Stok malzeme kartı — kurum kapsamlı.
 */
class StokMalzeme extends BaseModel
{
    public $id = null;
    public $kurum_id = 1;
    public $kod = null;
    public $ad = '';
    public $kategori = 'sarf';
    public $birim = 'adet';
    public $min_stok = 0;
    public $aktif = 1;
    public $aciklama = null;
    public $tedarikci_adi = null;
    public $tedarikci_tel = null;
    public $birim_fiyat = null;
    public $created_at = null;
    public $updated_at = null;

    public function __construct()
    {
        parent::__construct('#__stok_malzeme', 'id');
    }

    public static function tableReady(): bool
    {
        try {
            $db = Database::getInstance();

            return $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$db->replacePrefix('#__stok_malzeme')]
            ) !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<object>
     */
    public function listWithStock(array $filters = []): array
    {
        if (!self::tableReady()) {
            return [];
        }

        $where = ['1=1'];
        $params = [];
        if (empty($filters['include_pasif'])) {
            $where[] = 'm.aktif = 1';
        }
        $kurumFilter = isset($filters['kurum_id']) ? (int) $filters['kurum_id'] : 0;
        if ($kurumFilter > 0) {
            $where[] = 'm.kurum_id = ?';
            $params[] = $kurumFilter;
        } else {
            TenantSqlHelper::mergeParts($where, 'm', 'kurum_id');
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $esc = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $like = '%' . $esc . '%';
            $where[] = '(m.ad LIKE ? OR m.kod LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        $kategori = trim((string) ($filters['kategori'] ?? ''));
        if ($kategori !== '' && array_key_exists($kategori, \App\Helpers\StokHelper::kategoriOptions())) {
            $where[] = 'm.kategori = ?';
            $params[] = $kategori;
        }

        if (!empty($filters['kritik_only'])) {
            $where[] = 'COALESCE(s.miktar, 0) < m.min_stok';
        }

        $orderby = trim((string) ($filters['orderby'] ?? 'm.ad'));
        $orderdir = strtoupper(trim((string) ($filters['orderdir'] ?? 'ASC'))) === 'DESC' ? 'DESC' : 'ASC';
        $allowedOrder = [
            'm.ad' => true,
            'm.kod' => true,
            'm.kategori' => true,
            's.miktar' => true,
            'm.min_stok' => true,
        ];
        if (!isset($allowedOrder[$orderby])) {
            $orderby = 'm.ad';
        }

        $sql = 'SELECT m.*, COALESCE(s.miktar, 0) AS mevcut_miktar
                FROM #__stok_malzeme AS m
                LEFT JOIN #__stok_mevcut AS s ON s.malzeme_id = m.id AND s.kurum_id = m.kurum_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY ' . $orderby . ' ' . $orderdir;

        $list = $this->db->fetchObjectListPrepared($sql, $params);

        return is_array($list) ? $list : [];
    }

    /**
     * @return list<object>
     */
    public function listCatalog(array $filters = []): array
    {
        $filters['include_pasif'] = !empty($filters['include_pasif']);

        return $this->listWithStock($filters);
    }

    public function loadForKurum(int $id, ?int $kurumId = null): bool
    {
        if ($id < 1 || !self::tableReady()) {
            return false;
        }
        $sql = 'SELECT * FROM #__stok_malzeme WHERE id = ?';
        $params = [$id];
        $kid = $kurumId ?? \App\Helpers\TenantContext::filterKurumId();
        if ($kid !== null && $kid > 0) {
            $sql .= ' AND kurum_id = ?';
            $params[] = $kid;
        }
        $row = $this->db->fetchObjectPrepared($sql, $params);
        if (!$row) {
            return false;
        }
        $this->bind($row, false);

        return true;
    }

    /**
     * Aktif malzemeler — select listesi (çıkış/giriş formları).
     *
     * @return list<object>
     */
    public function listActiveForSelect(?int $kurumId = null): array
    {
        if (!self::tableReady()) {
            return [];
        }
        $where = ['m.aktif = 1'];
        $params = [];
        if ($kurumId !== null && $kurumId > 0) {
            $where[] = 'm.kurum_id = ?';
            $params[] = $kurumId;
        } else {
            TenantSqlHelper::mergeParts($where, 'm', 'kurum_id');
        }
        $sql = 'SELECT m.id, m.ad, m.kod, m.birim, m.kategori, COALESCE(s.miktar, 0) AS mevcut_miktar
                FROM #__stok_malzeme AS m
                LEFT JOIN #__stok_mevcut AS s ON s.malzeme_id = m.id AND s.kurum_id = m.kurum_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY m.ad ASC';
        $list = $this->db->fetchObjectListPrepared($sql, $params);

        return is_array($list) ? $list : [];
    }

    public function countCritical(?int $kurumId = null): int
    {
        if (!self::tableReady()) {
            return 0;
        }
        $where = ['m.aktif = 1', 'COALESCE(s.miktar, 0) < m.min_stok'];
        $params = [];
        if ($kurumId !== null && $kurumId > 0) {
            $where[] = 'm.kurum_id = ?';
            $params[] = $kurumId;
        } else {
            TenantSqlHelper::mergeParts($where, 'm', 'kurum_id');
        }
        $sql = 'SELECT COUNT(*) FROM #__stok_malzeme AS m
                LEFT JOIN #__stok_mevcut AS s ON s.malzeme_id = m.id AND s.kurum_id = m.kurum_id
                WHERE ' . implode(' AND ', $where);
        $n = $this->db->loadResultPrepared($sql, $params);

        return (int) ($n ?? 0);
    }

    public function ensureMevcutRow(int $kurumId, int $malzemeId): void
    {
        if ($kurumId < 1 || $malzemeId < 1) {
            return;
        }
        $exists = $this->db->loadResultPrepared(
            'SELECT 1 FROM #__stok_mevcut WHERE kurum_id = ? AND malzeme_id = ? LIMIT 1',
            [$kurumId, $malzemeId]
        );
        if ($exists !== null && $exists !== false) {
            return;
        }
        $this->db->insertPrepared('#__stok_mevcut', [
            'kurum_id' => $kurumId,
            'malzeme_id' => $malzemeId,
            'miktar' => 0,
        ]);
    }

    /**
     * @return array{kritik_sayisi: int, cikis_30_gun: float, kategori: array<string, int>}
     */
    public function getOzetStats(?int $kurumId = null): array
    {
        if (!self::tableReady()) {
            return ['kritik_sayisi' => 0, 'cikis_30_gun' => 0.0, 'kategori' => []];
        }

        $kritik = $this->countCritical($kurumId);
        $cikis = (new StokHareket())->sumCikisSince($kurumId, 30);

        $where = ['m.aktif = 1'];
        $params = [];
        if ($kurumId !== null && $kurumId > 0) {
            $where[] = 'm.kurum_id = ?';
            $params[] = $kurumId;
        } else {
            TenantSqlHelper::mergeParts($where, 'm', 'kurum_id');
        }
        $sql = 'SELECT m.kategori, COUNT(*) AS cnt
                FROM #__stok_malzeme AS m
                WHERE ' . implode(' AND ', $where) . '
                GROUP BY m.kategori';
        $rows = $this->db->fetchObjectListPrepared($sql, $params);
        $kategori = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $kat = (string) ($row->kategori ?? 'diger');
                $kategori[$kat] = (int) ($row->cnt ?? 0);
            }
        }

        return [
            'kritik_sayisi' => $kritik,
            'cikis_30_gun' => $cikis,
            'kategori' => $kategori,
        ];
    }

    /**
     * min_stok - mevcut > 0 olan malzemeler — sipariş önerisi.
     *
     * @return list<object>
     */
    public function listSiparisOneri(?int $kurumId = null): array
    {
        if (!self::tableReady()) {
            return [];
        }
        $where = ['m.aktif = 1', 'm.min_stok > 0', 'COALESCE(s.miktar, 0) < m.min_stok'];
        $params = [];
        if ($kurumId !== null && $kurumId > 0) {
            $where[] = 'm.kurum_id = ?';
            $params[] = $kurumId;
        } else {
            TenantSqlHelper::mergeParts($where, 'm', 'kurum_id');
        }
        $sql = 'SELECT m.*, COALESCE(s.miktar, 0) AS mevcut_miktar,
                       GREATEST(m.min_stok - COALESCE(s.miktar, 0), 0) AS oneri_miktar
                FROM #__stok_malzeme AS m
                LEFT JOIN #__stok_mevcut AS s ON s.malzeme_id = m.id AND s.kurum_id = m.kurum_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY oneri_miktar DESC, m.ad ASC';
        $list = $this->db->fetchObjectListPrepared($sql, $params);

        return is_array($list) ? $list : [];
    }

    /**
     * Kritik malzeme listesi (SMS uyarısı için).
     *
     * @return list<object>
     */
    public function listCriticalItems(?int $kurumId = null, int $limit = 50): array
    {
        if (!self::tableReady()) {
            return [];
        }
        $filters = ['kritik_only' => true];
        if ($kurumId !== null && $kurumId > 0) {
            $filters['kurum_id'] = $kurumId;
        }
        $all = $this->listWithStock($filters);

        return array_slice($all, 0, max(1, min(200, $limit)));
    }
}
