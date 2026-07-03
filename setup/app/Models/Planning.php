<?php
namespace App\Models;

use App\Helpers\KurumAdresScope;
use App\Helpers\TenantSqlHelper;

class Planning extends BaseModel {

    public $id;
    public $bolge;
    public $gun;

    private MahallePlan $mahallePlan;

    public function __construct() {
        parent::__construct('#__adrestablosu', 'id');
        $this->mahallePlan = new MahallePlan();
    }

    /**
     * @param string $bolge_filter Boş = tümü; '0' = atanmamış; '1'…max = belirli bölge
     */
    public function getPlanningList($ilce_id = '', $limit = 20, $offset = 0, $bolge_filter = '', string $orderFragment = '') {
        $where = $this->buildMahalleWhere($ilce_id, $bolge_filter, 'm', 'mp');
        $whereSql = ' WHERE ' . implode(' AND ', $where);
        $bolge = $this->mahallePlan->bolgeExpr('mp');
        $gun = $this->mahallePlan->gunExpr('mp');
        $join = $this->mahallePlan->planJoinSql('m', 'mp');
        if ($orderFragment === '') {
            $orderFragment = '(CAST(' . $bolge . ' AS UNSIGNED) = 0) ASC, CAST(' . $bolge . ' AS UNSIGNED) ASC, i.adi ASC, m.adi ASC';
        }

        $query = "SELECT m.id, m.adi, m.ust_id, m.tip, {$bolge} AS bolge, {$gun} AS gun, i.adi AS ilce_adi
                  FROM #__adrestablosu AS m
                  {$join}
                  LEFT JOIN #__adrestablosu AS i ON i.id = m.ust_id
                  {$whereSql}
                  ORDER BY {$orderFragment}
                  LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset;

        return $this->db->fetchObjectListPrepared($query);
    }

    public function bolgeOrderExpr(string $planAlias = 'mp'): string {
        return $this->mahallePlan->bolgeExpr($planAlias);
    }

    public function getPlanningCount($ilce_id = '', $bolge_filter = '') {
        $where = $this->buildMahalleWhere($ilce_id, $bolge_filter, 'm', 'mp');
        $whereSql = ' WHERE ' . implode(' AND ', $where);
        $join = $this->mahallePlan->planJoinSql('m', 'mp');

        return $this->db->loadResultPrepared(
            "SELECT COUNT(m.id) FROM #__adrestablosu AS m {$join} {$whereSql}"
        );
    }

    /**
     * @return list<string>
     */
    private function buildMahalleWhere(string $ilce_id, string $bolge_filter, string $alias, string $planAlias): array {
        $p = $alias !== '' ? $alias . '.' : '';
        $where = ["{$p}tip = 'mahalle'"];
        $bolge = $this->mahallePlan->bolgeExpr($planAlias);

        if ($ilce_id !== '' && $ilce_id !== '0') {
            $where[] = $p . 'ust_id = ' . $this->db->quote($ilce_id);
        }

        if ($bolge_filter !== '') {
            if ($bolge_filter === '0') {
                $where[] = 'CAST(' . $bolge . ' AS UNSIGNED) = 0';
            } else {
                $b = (int) $bolge_filter;
                if ($b > 0) {
                    $where[] = 'CAST(' . $bolge . ' AS UNSIGNED) = ' . $b;
                }
            }
        }

        $kid = KurumAdresScope::effectiveKurumId();
        if ($kid !== null) {
            $scopeSql = KurumAdresScope::sqlMahalleScope($alias !== '' ? $alias : 'm', $kid, $ilce_id);
            if ($scopeSql !== '') {
                $where[] = ltrim($scopeSql, ' AND');
            }
        }

        return $where;
    }

    /** Coğrafi ilçe listesi — kurum kapsamına göre filtrelenir. */
    public function getDistricts() {
        return (new Address())->getDistricts();
    }

    /**
     * Haftalık planlama matrisi — kurum bazlı bölge/gün, mahalle kaydı ortak.
     */
    public function getMasterPlanData() {
        $bolge = $this->mahallePlan->bolgeExpr('mp');
        $gun = $this->mahallePlan->gunExpr('mp');
        $join = $this->mahallePlan->planJoinSql('m', 'mp');
        $hKurum = TenantSqlHelper::andEquals('h', 'kurum_id');
        $where = [
            "m.tip = 'mahalle'",
            'CAST(' . $bolge . ' AS UNSIGNED) > 0',
            $gun . ' IS NOT NULL',
            "TRIM({$gun}) != ''",
        ];
        $kid = KurumAdresScope::effectiveKurumId();
        if ($kid !== null) {
            $scopeSql = KurumAdresScope::sqlMahalleScope('m', $kid);
            if ($scopeSql !== '') {
                $where[] = ltrim($scopeSql, ' AND');
            }
        }
        $query = "SELECT m.id, m.adi, m.ust_id, m.tip, {$bolge} AS bolge, {$gun} AS gun, i.adi AS ilce_adi,
             (SELECT COUNT(h.id) FROM #__hastalar AS h WHERE h.mahalle = m.id AND h.pasif = '0'{$hKurum}) AS hastasayisi
             FROM #__adrestablosu AS m
             {$join}
             LEFT JOIN #__adrestablosu AS i ON i.id = m.ust_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY CAST({$bolge} AS UNSIGNED) ASC, i.adi ASC, m.adi ASC";

        return $this->db->fetchObjectListPrepared($query);
    }

    public function saveMahallePlan(string $mahalleId, int $bolge, string $gun): bool
    {
        return $this->mahallePlan->upsert(
            MahallePlan::effectiveKurumIdForPlan(),
            $mahalleId,
            $bolge,
            $gun
        );
    }
}
