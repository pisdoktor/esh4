<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Federasyon bölgesi — `#__federation_regions`.
 */
class FederationRegion extends BaseModel
{
    public $id = null;
    public $kod = null;
    public $ad = null;
    public $il_adi = null;
    public $hub_node_ref = null;
    public $aktif = 1;
    public $aciklama = null;
    public $ayarlar_json = null;
    public $olusturma_tarihi = null;

    public function __construct()
    {
        parent::__construct('#__federation_regions', 'id');
    }

    /** @return list<object> */
    public function getList(bool $onlyActive = false, string $orderFragment = 'ad ASC'): array
    {
        $orderFragment = trim($orderFragment) !== '' ? $orderFragment : 'ad ASC';
        $sql = 'SELECT * FROM #__federation_regions';
        if ($onlyActive) {
            $sql .= ' WHERE aktif = 1';
        }
        $sql .= ' ORDER BY ' . $orderFragment;
        $list = $this->db->fetchObjectListPrepared($sql, []);

        return is_array($list) ? $list : [];
    }

    public static function normalizeKod(string $raw): string
    {
        $s = strtolower(trim($raw));
        $s = preg_replace('/[^a-z0-9\-_]+/', '-', $s) ?? '';
        $s = trim($s, '-');

        return $s !== '' ? $s : 'bolge';
    }

    public function kodUnique(string $kod, ?int $excludeId = null): bool
    {
        $kod = self::normalizeKod($kod);
        $params = [$kod];
        $sql = 'SELECT COUNT(*) FROM #__federation_regions WHERE kod = ?';
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }

        return (int) $this->db->loadResultPrepared($sql, $params) === 0;
    }

    public static function tableExists(): bool
    {
        try {
            $db = Database::getInstance();

            return (int) $db->loadResultPrepared(
                'SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$db->replacePrefix('#__federation_regions')]
            ) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<int, int> bolge_id => kurum_count
     */
    public function kurumCountsByBolge(): array
    {
        if (!Kurum::tableExists() || !self::columnsReadyOnKurum()) {
            return [];
        }
        $rows = $this->db->fetchObjectListPrepared(
            'SELECT bolge_id, COUNT(*) AS cnt FROM #__kurumlar WHERE bolge_id IS NOT NULL GROUP BY bolge_id',
            []
        );
        if (!is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $bid = (int) ($row->bolge_id ?? 0);
            if ($bid > 0) {
                $out[$bid] = (int) ($row->cnt ?? 0);
            }
        }

        return $out;
    }

    public static function ayarlarColumnReady(): bool
    {
        try {
            $db = Database::getInstance();
            $row = $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
                [$db->replacePrefix('#__federation_regions'), 'ayarlar_json']
            );

            return $row !== null && $row !== false && $row !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array<string, mixed> */
    public function ayarlarArray(): array
    {
        $raw = trim((string) ($this->ayarlar_json ?? ''));
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function setAyarlarArray(array $data): void
    {
        $this->set('ayarlar_json', json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    public static function columnsReadyOnKurum(): bool
    {
        try {
            $db = Database::getInstance();
            $row = $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
                [$db->replacePrefix('#__kurumlar'), 'bolge_id']
            );

            return $row !== null && $row !== false && $row !== '';
        } catch (\Throwable) {
            return false;
        }
    }
}
