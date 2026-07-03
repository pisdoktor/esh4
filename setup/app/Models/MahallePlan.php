<?php
namespace App\Models;

use App\Helpers\TenantContext;

/**
 * Kurum bazlı mahalle planlama (bölge / gün) — coğrafi mahalle kaydı platform genelidir.
 */
class MahallePlan extends BaseModel
{
    public function __construct()
    {
        parent::__construct('#__mahalle_plan', 'mahalle_id');
    }

    public static function effectiveKurumIdForPlan(): int
    {
        $kid = TenantContext::filterKurumId();
        if ($kid !== null && $kid > 0) {
            return (int) $kid;
        }

        return TenantContext::assignKurumIdForStore();
    }

    public function upsert(int $kurumId, string $mahalleId, int $bolge, string $gun): bool
    {
        $mahalleId = trim($mahalleId);
        if ($mahalleId === '' || $kurumId <= 0) {
            return false;
        }

        if ($this->db->updatePrepared(
            '#__mahalle_plan',
            ['bolge' => $bolge, 'gun' => $gun],
            'kurum_id = ? AND mahalle_id = ?',
            [$kurumId, $mahalleId]
        ) && $this->db->affectedRows() > 0) {
            return true;
        }

        return $this->db->insertPrepared('#__mahalle_plan', [
            'kurum_id' => $kurumId,
            'mahalle_id' => $mahalleId,
            'bolge' => $bolge,
            'gun' => $gun,
        ]) !== false;
    }

    public function planJoinSql(string $mahalleAlias = 'm', string $planAlias = 'mp'): string
    {
        $kid = self::effectiveKurumIdForPlan();

        return ' LEFT JOIN #__mahalle_plan AS ' . $planAlias
            . ' ON ' . $planAlias . '.mahalle_id = ' . $mahalleAlias . '.id'
            . ' AND ' . $planAlias . '.kurum_id = ' . (int) $kid;
    }

    /** Hasta kurum_id ile mahalle planı (rota / günlük plan). */
    public static function joinSqlForHasta(string $mahalleAlias = 'm', string $planAlias = 'mp', string $hastaAlias = 'h'): string
    {
        return ' LEFT JOIN #__mahalle_plan AS ' . $planAlias
            . ' ON ' . $planAlias . '.mahalle_id = ' . $mahalleAlias . '.id'
            . ' AND ' . $planAlias . '.kurum_id = ' . $hastaAlias . '.kurum_id';
    }

    public static function bolgeSelectSql(string $planAlias = 'mp', string $resultAlias = 'bolge'): string
    {
        return 'COALESCE(' . $planAlias . '.bolge, 0) AS ' . $resultAlias;
    }

    public function bolgeExpr(string $planAlias = 'mp'): string
    {
        return 'COALESCE(' . $planAlias . '.bolge, 0)';
    }

    public function gunExpr(string $planAlias = 'mp'): string
    {
        return $planAlias . '.gun';
    }

    /** Hasta kartı vb. — kurum + mahalle için plan satırı (bölge / gün). */
    public function getForHastaKurum(int $kurumId, string $mahalleId): ?object
    {
        $mahalleId = trim($mahalleId);
        if ($kurumId < 1 || $mahalleId === '') {
            return null;
        }

        return $this->db->fetchObjectPrepared(
            'SELECT bolge, gun FROM #__mahalle_plan WHERE kurum_id = ? AND mahalle_id = ?',
            [$kurumId, $mahalleId]
        );
    }
}
