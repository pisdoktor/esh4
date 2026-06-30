<?php
namespace App\Models;

/**
 * Uhds randevu takvimi (`#__goruntulu_randevu`).
 * Kurallar branş randevu ile aynı; veri ayrı tabloda tutulur.
 */
class Uhds extends KonsRandevu
{
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
}
