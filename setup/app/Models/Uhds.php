<?php
namespace App\Models;

/**
 * Uhds randevu takvimi (`#__goruntulu_randevu`).
 * Kurallar branş randevu ile aynı; veri ayrı tabloda tutulur.
 */
class Uhds extends KonsRandevu
{
    public $video_room_id = null;
    public $video_started_at = null;
    public $video_ended_at = null;
    /** @var int|null */
    public $visit_id = null;
    public $telehealth_summary = null;

    public function __construct()
    {
        BaseModel::__construct('#__goruntulu_randevu', 'id');
    }

    /**
     * Yapıldı mı? (DB: hasta_geldi — NULL=belirtilmedi, 1=yapıldı, 0=yapılmadı).
     *
     * @param int|string|null $v
     */
    public static function yapildiMiLabel($v): string
    {
        if ($v === null || $v === '') {
            return 'Belirtilmedi';
        }
        $i = (int) $v;

        return match ($i) {
            1 => 'Yapıldı',
            0 => 'Yapılmadı',
            default => 'Belirtilmedi',
        };
    }

    public function markVideoStarted(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        return $this->db->executePrepared(
            'UPDATE ' . $this->_tbl . ' SET video_started_at = COALESCE(video_started_at, NOW()) WHERE id = ?'
                . \App\Helpers\TenantSqlHelper::andBare('kurum_id'),
            [$id]
        );
    }

    public function completeTelehealthSession(int $id, ?string $summary, ?int $hastaGeldi, ?int $visitId = null): bool
    {
        if ($id <= 0) {
            return false;
        }
        $fields = ['video_ended_at = NOW()'];
        $params = [];
        if ($summary !== null) {
            $fields[] = 'telehealth_summary = ?';
            $params[] = $summary !== '' ? $summary : null;
        }
        if ($hastaGeldi !== null) {
            $fields[] = 'hasta_geldi = ?';
            $params[] = $hastaGeldi;
        }
        if ($visitId !== null && $visitId > 0) {
            $fields[] = 'visit_id = ?';
            $params[] = $visitId;
        }
        $params[] = $id;
        $sql = 'UPDATE ' . $this->_tbl . ' SET ' . implode(', ', $fields)
            . ' WHERE id = ?' . \App\Helpers\TenantSqlHelper::andBare('kurum_id');

        return $this->db->executePrepared($sql, $params);
    }
}
