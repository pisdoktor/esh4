<?php
namespace App\Models;

/**
 * Hasta MNA-SF beslenme değerlendirme kayıtları.
 */
class MnaAssessment extends BaseModel
{
    /** @var bool */
    protected $uuidPrimaryKey = true;

    public $id = null;
    public $kurum_id = null;
    public $hasta_id = null;
    public $degerlendirme_tarihi = null;
    public $besin_alimi = null;
    public $kilo_kaybi = null;
    public $mobilite = null;
    public $stres_hastalik = null;
    public $noropsikolojik = null;
    public $bmi_olcum_tipi = null;
    public $bmi_skor = null;
    public $toplam_skor = null;
    public $durum_duzeyi = null;
    public $notlar = null;
    public $kaydeden_id = null;
    public $created_at = null;

    public function __construct()
    {
        parent::__construct('#__hasta_mna', 'id');
    }

    public function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS #__hasta_mna (
            id CHAR(36) NOT NULL,
            kurum_id INT UNSIGNED NOT NULL DEFAULT 1,
            hasta_id CHAR(36) NOT NULL,
            degerlendirme_tarihi DATE NOT NULL,
            besin_alimi TINYINT UNSIGNED NOT NULL,
            kilo_kaybi TINYINT UNSIGNED NOT NULL,
            mobilite TINYINT UNSIGNED NOT NULL,
            stres_hastalik TINYINT UNSIGNED NOT NULL,
            noropsikolojik TINYINT UNSIGNED NOT NULL,
            bmi_olcum_tipi VARCHAR(20) NOT NULL DEFAULT 'bmi',
            bmi_skor TINYINT UNSIGNED NOT NULL,
            toplam_skor TINYINT UNSIGNED NOT NULL,
            durum_duzeyi VARCHAR(32) NOT NULL DEFAULT '',
            notlar TEXT DEFAULT NULL,
            kaydeden_id CHAR(36) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_mna_hasta (hasta_id),
            KEY idx_mna_tarih (degerlendirme_tarihi),
            KEY idx_mna_kurum (kurum_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci";
        $this->db->execLogged($sql);
    }

    /**
     * @return array<int, object>
     */
    public function getByHastaId(string $hastaId): array
    {
        return $this->db->fetchObjectListPrepared(
            'SELECT b.*, u.name AS kaydeden_adi
             FROM #__hasta_mna b
             LEFT JOIN #__users u ON u.id = b.kaydeden_id
             WHERE b.hasta_id = :hasta
             ORDER BY b.degerlendirme_tarihi DESC, b.id DESC',
            [':hasta' => $hastaId]
        );
    }

    public function getLatestByHastaId(string $hastaId): ?object
    {
        return $this->db->fetchObjectPrepared(
            'SELECT b.*, u.name AS kaydeden_adi
             FROM #__hasta_mna b
             LEFT JOIN #__users u ON u.id = b.kaydeden_id
             WHERE b.hasta_id = :hasta
             ORDER BY b.degerlendirme_tarihi DESC, b.id DESC
             LIMIT 1',
            [':hasta' => $hastaId]
        );
    }

    public function countByHastaId(string $hastaId): int
    {
        return (int) $this->db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__hasta_mna WHERE hasta_id = :hasta',
            [':hasta' => $hastaId]
        );
    }

    public function getById(int|string $id): ?object
    {
        return $this->db->fetchObjectPrepared(
            'SELECT * FROM #__hasta_mna WHERE id = :id',
            [':id' => $id]
        );
    }
}
