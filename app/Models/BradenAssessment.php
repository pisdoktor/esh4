<?php
namespace App\Models;

/**
 * Hasta Braden ölçeği değerlendirme kayıtları.
 */
class BradenAssessment extends BaseModel
{
    public $id = null;
    public $kurum_id = null;
    public $hasta_id = null;
    public $degerlendirme_tarihi = null;
    public $duyusal = null;
    public $nem = null;
    public $aktivite = null;
    public $hareket = null;
    public $beslenme = null;
    public $surtunme = null;
    public $toplam_skor = null;
    public $risk_duzeyi = null;
    public $notlar = null;
    public $kaydeden_id = null;
    public $created_at = null;

    public function __construct()
    {
        parent::__construct('#__hasta_braden', 'id');
    }

    public function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS #__hasta_braden (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            kurum_id INT UNSIGNED NOT NULL DEFAULT 1,
            hasta_id INT UNSIGNED NOT NULL,
            degerlendirme_tarihi DATE NOT NULL,
            duyusal TINYINT UNSIGNED NOT NULL,
            nem TINYINT UNSIGNED NOT NULL,
            aktivite TINYINT UNSIGNED NOT NULL,
            hareket TINYINT UNSIGNED NOT NULL,
            beslenme TINYINT UNSIGNED NOT NULL,
            surtunme TINYINT UNSIGNED NOT NULL,
            toplam_skor TINYINT UNSIGNED NOT NULL,
            risk_duzeyi VARCHAR(32) NOT NULL DEFAULT '',
            notlar TEXT DEFAULT NULL,
            kaydeden_id INT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_braden_hasta (hasta_id),
            KEY idx_braden_tarih (degerlendirme_tarihi),
            KEY idx_braden_kurum (kurum_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci";
        $this->db->execLogged($sql);
    }

    /**
     * @return array<int, object>
     */
    public function getByHastaId(int $hastaId): array
    {
        return $this->db->fetchObjectListPrepared(
            'SELECT b.*, u.name AS kaydeden_adi
             FROM #__hasta_braden b
             LEFT JOIN #__users u ON u.id = b.kaydeden_id
             WHERE b.hasta_id = :hasta
             ORDER BY b.degerlendirme_tarihi DESC, b.id DESC',
            [':hasta' => $hastaId]
        );
    }

    public function getLatestByHastaId(int $hastaId): ?object
    {
        return $this->db->fetchObjectPrepared(
            'SELECT b.*, u.name AS kaydeden_adi
             FROM #__hasta_braden b
             LEFT JOIN #__users u ON u.id = b.kaydeden_id
             WHERE b.hasta_id = :hasta
             ORDER BY b.degerlendirme_tarihi DESC, b.id DESC
             LIMIT 1',
            [':hasta' => $hastaId]
        );
    }

    public function countByHastaId(int $hastaId): int
    {
        return (int) $this->db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__hasta_braden WHERE hasta_id = :hasta',
            [':hasta' => $hastaId]
        );
    }

    public function getById(int $id): ?object
    {
        return $this->db->fetchObjectPrepared(
            'SELECT * FROM #__hasta_braden WHERE id = :id',
            [':id' => $id]
        );
    }
}
