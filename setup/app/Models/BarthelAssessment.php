<?php
namespace App\Models;

/**
 * Hasta Barthel indeksi değerlendirme kayıtları.
 */
class BarthelAssessment extends BaseModel
{
    /** @var bool */
    protected $uuidPrimaryKey = true;

    public $id = null;
    public $kurum_id = null;
    public $hasta_id = null;
    public $degerlendirme_tarihi = null;
    public $barbeslenme = null;
    public $barbanyo = null;
    public $barbakim = null;
    public $bargiyinme = null;
    public $barbarsak = null;
    public $barmesane = null;
    public $bartuvalet = null;
    public $bartransfer = null;
    public $barmobilite = null;
    public $barmerdiven = null;
    public $toplam_skor = null;
    public $bagimlilik_duzeyi = null;
    public $notlar = null;
    public $kaydeden_id = null;
    public $created_at = null;

    public function __construct()
    {
        parent::__construct('#__hasta_barthel', 'id');
    }

    public function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS #__hasta_barthel (
            id CHAR(36) NOT NULL,
            kurum_id INT UNSIGNED NOT NULL DEFAULT 1,
            hasta_id CHAR(36) NOT NULL,
            degerlendirme_tarihi DATE NOT NULL,
            barbeslenme TINYINT UNSIGNED NOT NULL DEFAULT 0,
            barbanyo TINYINT UNSIGNED NOT NULL DEFAULT 0,
            barbakim TINYINT UNSIGNED NOT NULL DEFAULT 0,
            bargiyinme TINYINT UNSIGNED NOT NULL DEFAULT 0,
            barbarsak TINYINT UNSIGNED NOT NULL DEFAULT 0,
            barmesane TINYINT UNSIGNED NOT NULL DEFAULT 0,
            bartuvalet TINYINT UNSIGNED NOT NULL DEFAULT 0,
            bartransfer TINYINT UNSIGNED NOT NULL DEFAULT 0,
            barmobilite TINYINT UNSIGNED NOT NULL DEFAULT 0,
            barmerdiven TINYINT UNSIGNED NOT NULL DEFAULT 0,
            toplam_skor SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            bagimlilik_duzeyi VARCHAR(32) NOT NULL DEFAULT '',
            notlar TEXT DEFAULT NULL,
            kaydeden_id CHAR(36) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_barthel_hasta (hasta_id),
            KEY idx_barthel_tarih (degerlendirme_tarihi),
            KEY idx_barthel_kurum (kurum_id)
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
             FROM #__hasta_barthel b
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
             FROM #__hasta_barthel b
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
            'SELECT COUNT(*) FROM #__hasta_barthel WHERE hasta_id = :hasta',
            [':hasta' => $hastaId]
        );
    }

    public function getById(int|string $id): ?object
    {
        return $this->db->fetchObjectPrepared(
            'SELECT * FROM #__hasta_barthel WHERE id = :id',
            [':id' => $id]
        );
    }
}
