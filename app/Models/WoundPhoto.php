<?php
namespace App\Models;

/**
 * Hasta yara fotoğrafları.
 */
class WoundPhoto extends BaseModel
{
    /** @var bool */
    protected $uuidPrimaryKey = true;

    public $id = null;
    public $hasta_id = null;
    public $dosya_adi = null;
    public $orijinal_ad = null;
    public $mime = null;
    public $boyut = null;
    public $aciklama = null;
    public $yara_bolgesi = null;
    public $yara_evresi = null;
    public $cekim_tarihi = null;
    public $yukleyen_id = null;
    public $created_at = null;

    public function __construct()
    {
        parent::__construct('#__hasta_yara_fotolar', 'id');
    }

    public function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS #__hasta_yara_fotolar (
            id CHAR(36) NOT NULL,
            hasta_id CHAR(36) NOT NULL,
            dosya_adi VARCHAR(255) NOT NULL,
            orijinal_ad VARCHAR(255) DEFAULT NULL,
            mime VARCHAR(100) DEFAULT NULL,
            boyut INT UNSIGNED DEFAULT NULL,
            aciklama VARCHAR(255) DEFAULT NULL,
            yara_bolgesi VARCHAR(100) DEFAULT NULL,
            yara_evresi VARCHAR(50) DEFAULT NULL,
            cekim_tarihi DATETIME DEFAULT NULL,
            yukleyen_id CHAR(36) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_hasta (hasta_id),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci";
        $this->db->execLogged($sql);

        if (!$this->columnExists('yara_bolgesi')) {
            $this->db->execLogged('ALTER TABLE #__hasta_yara_fotolar ADD COLUMN yara_bolgesi VARCHAR(100) DEFAULT NULL AFTER aciklama');
        }
        if (!$this->columnExists('yara_evresi')) {
            $this->db->execLogged('ALTER TABLE #__hasta_yara_fotolar ADD COLUMN yara_evresi VARCHAR(50) DEFAULT NULL AFTER yara_bolgesi');
        }
        if (!$this->columnExists('cekim_tarihi')) {
            $this->db->execLogged('ALTER TABLE #__hasta_yara_fotolar ADD COLUMN cekim_tarihi DATETIME DEFAULT NULL AFTER yara_evresi');
        }
    }

    /**
     * @return array<int, object>
     */
    public function getByHastaId(string $hastaId): array
    {
        return $this->db->fetchObjectListPrepared(
            'SELECT f.*, u.name AS yukleyen_adi
             FROM #__hasta_yara_fotolar f
             LEFT JOIN #__users u ON u.id = f.yukleyen_id
             WHERE f.hasta_id = :hasta
             ORDER BY COALESCE(f.cekim_tarihi, f.created_at) DESC, f.id DESC',
            [':hasta' => $hastaId]
        );
    }

    public function getById(string $id): ?object
    {
        return $this->db->fetchObjectPrepared(
            'SELECT * FROM #__hasta_yara_fotolar WHERE id = :id',
            [':id' => $id]
        );
    }

    private function columnExists(string $column): bool
    {
        $n = (int) $this->db->loadResultPrepared(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$this->db->replacePrefix('#__hasta_yara_fotolar'), $column]
        );

        return $n > 0;
    }
}
