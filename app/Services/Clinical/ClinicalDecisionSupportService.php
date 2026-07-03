<?php

declare(strict_types=1);

namespace App\Services\Clinical;

use App\Core\Database;
use App\Helpers\ClinicalDecisionSupportHelper;
use App\Helpers\TenantSqlHelper;

/**
 * Klinik karar desteği — yüksek riskli ve izlenmeyen hasta sorguları.
 */
final class ClinicalDecisionSupportService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function countOverdueHighRisk(): int
    {
        if (!ClinicalDecisionSupportHelper::enabled()) {
            return 0;
        }
        $sql = 'SELECT COUNT(h.id) FROM #__hastalar AS h WHERE ' . $this->overdueHighRiskWhereSql();

        return (int) $this->db->loadResultPrepared($sql, $this->thresholdParams());
    }

    /**
     * @return list<object>
     */
    public function listOverdueHighRisk(int $limit = 50, int $offset = 0): array
    {
        if (!ClinicalDecisionSupportHelper::enabled()) {
            return [];
        }
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $izScope = $this->izScopeSql('iz');
        $izScope2 = $this->izScopeSql('iz2');
        $sql = "SELECT h.id, h.isim, h.soyisim, h.tckimlik, h.basiyarasi, h.dogumtarihi,
            (SELECT MAX(iz.izlemtarihi) FROM #__izlemler AS iz
                WHERE iz.hastatckimlik = h.tckimlik AND iz.yapildimi = 1{$izScope}) AS son_izlem,
            DATEDIFF(CURDATE(), (
                SELECT MAX(iz2.izlemtarihi) FROM #__izlemler AS iz2
                WHERE iz2.hastatckimlik = h.tckimlik AND iz2.yapildimi = 1{$izScope2}
            )) AS gun_since_visit,
            (SELECT b.toplam_skor FROM #__hasta_braden AS b
                WHERE b.hasta_id = h.id ORDER BY b.degerlendirme_tarihi DESC, b.id DESC LIMIT 1) AS braden_skor,
            (SELECT i.toplam_skor FROM #__hasta_itaki AS i
                WHERE i.hasta_id = h.id ORDER BY i.degerlendirme_tarihi DESC, i.id DESC LIMIT 1) AS itaki_skor,
            (SELECT hz.toplam_skor FROM #__hasta_harizmi AS hz
                WHERE hz.hasta_id = h.id ORDER BY hz.degerlendirme_tarihi DESC, hz.id DESC LIMIT 1) AS harizmi_skor,
            (SELECT m.toplam_skor FROM #__hasta_mna AS m
                WHERE m.hasta_id = h.id ORDER BY m.degerlendirme_tarihi DESC, m.id DESC LIMIT 1) AS mna_skor,
            (SELECT bt.toplam_skor FROM #__hasta_barthel AS bt
                WHERE bt.hasta_id = h.id ORDER BY bt.degerlendirme_tarihi DESC, bt.id DESC LIMIT 1) AS barthel_skor
            FROM #__hastalar AS h
            WHERE " . $this->overdueHighRiskWhereSql()
            . ' ORDER BY son_izlem IS NULL DESC, son_izlem ASC, h.soyisim, h.isim'
            . ' LIMIT ' . (int) $offset . ', ' . (int) $limit;
        $rows = $this->db->fetchObjectListPrepared($sql, $this->thresholdParams());

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<string>
     */
    public function riskLabelsForRow(object $row): array
    {
        $labels = [];
        $bradenThr = ClinicalDecisionSupportHelper::bradenHighThreshold();
        $fallThr = ClinicalDecisionSupportHelper::fallRiskThreshold();
        $mnaThr = ClinicalDecisionSupportHelper::mnaRiskThreshold();
        $barthelDepThr = ClinicalDecisionSupportHelper::barthelDependencyThreshold();

        if (isset($row->braden_skor) && $row->braden_skor !== null && (int) $row->braden_skor <= $bradenThr) {
            $labels[] = 'Braden ' . (int) $row->braden_skor;
        }
        if (isset($row->itaki_skor) && $row->itaki_skor !== null && (int) $row->itaki_skor >= $fallThr) {
            $labels[] = 'İTAKİ ' . (int) $row->itaki_skor;
        }
        if (isset($row->harizmi_skor) && $row->harizmi_skor !== null && (int) $row->harizmi_skor >= $fallThr) {
            $labels[] = 'Harizmi ' . (int) $row->harizmi_skor;
        }
        if (isset($row->mna_skor) && $row->mna_skor !== null && (int) $row->mna_skor < $mnaThr) {
            $labels[] = 'MNA ' . (int) $row->mna_skor;
        }
        if (isset($row->barthel_skor) && $row->barthel_skor !== null && (int) $row->barthel_skor <= $barthelDepThr) {
            $labels[] = 'Barthel ' . (int) $row->barthel_skor;
        }

        return $labels;
    }

    /** @return array<string, int> */
    private function thresholdParams(): array
    {
        return [
            ':braden_thr' => ClinicalDecisionSupportHelper::bradenHighThreshold(),
            ':fall_thr_itaki' => ClinicalDecisionSupportHelper::fallRiskThreshold(),
            ':fall_thr_harizmi' => ClinicalDecisionSupportHelper::fallRiskThreshold(),
            ':mna_thr' => ClinicalDecisionSupportHelper::mnaRiskThreshold(),
            ':barthel_dep_thr' => ClinicalDecisionSupportHelper::barthelDependencyThreshold(),
            ':overdue_days' => ClinicalDecisionSupportHelper::overdueDays(),
        ];
    }

    private function overdueHighRiskWhereSql(): string
    {
        $kurumSql = TenantSqlHelper::andEquals('h');
        $izScope = $this->izScopeSql('iz');
        $pasif = "h.pasif = '0'";

        return "{$pasif}{$kurumSql}
            AND (
                COALESCE((
                    SELECT b.toplam_skor FROM #__hasta_braden AS b
                    WHERE b.hasta_id = h.id
                    ORDER BY b.degerlendirme_tarihi DESC, b.id DESC LIMIT 1
                ), 99) <= :braden_thr
                OR COALESCE((
                    SELECT i.toplam_skor FROM #__hasta_itaki AS i
                    WHERE i.hasta_id = h.id
                    ORDER BY i.degerlendirme_tarihi DESC, i.id DESC LIMIT 1
                ), 0) >= :fall_thr_itaki
                OR COALESCE((
                    SELECT hz.toplam_skor FROM #__hasta_harizmi AS hz
                    WHERE hz.hasta_id = h.id
                    ORDER BY hz.degerlendirme_tarihi DESC, hz.id DESC LIMIT 1
                ), 0) >= :fall_thr_harizmi
                OR COALESCE((
                    SELECT m.toplam_skor FROM #__hasta_mna AS m
                    WHERE m.hasta_id = h.id
                    ORDER BY m.degerlendirme_tarihi DESC, m.id DESC LIMIT 1
                ), 99) < :mna_thr
                OR COALESCE((
                    SELECT bt.toplam_skor FROM #__hasta_barthel AS bt
                    WHERE bt.hasta_id = h.id
                    ORDER BY bt.degerlendirme_tarihi DESC, bt.id DESC LIMIT 1
                ), 100) <= :barthel_dep_thr
            )
            AND (
                (
                    SELECT MAX(iz.izlemtarihi) FROM #__izlemler AS iz
                    WHERE iz.hastatckimlik = h.tckimlik AND iz.yapildimi = 1{$izScope}
                ) IS NULL
                OR (
                    SELECT MAX(iz.izlemtarihi) FROM #__izlemler AS iz
                    WHERE iz.hastatckimlik = h.tckimlik AND iz.yapildimi = 1{$izScope}
                ) < DATE_SUB(CURDATE(), INTERVAL :overdue_days DAY)
            )";
    }

    private function izScopeSql(string $alias): string
    {
        return TenantSqlHelper::izMatchesPatientKurumSql($alias);
    }
}
