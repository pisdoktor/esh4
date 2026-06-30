<?php
namespace App\Models;

use App\Helpers\UserProfileStatsHelper;

/**
 * Kullanıcı profili iş özeti — detay listeleri ve sayfalama.
 */
class UserProfileStats extends BaseModel
{
    public function __construct()
    {
        parent::__construct('#__users', 'id');
    }

    public static function userInIzlemCondition(int $userId, string $alias = 'i'): string
    {
        $uid = (int) $userId;

        return 'FIND_IN_SET(' . $uid . ', REPLACE(CAST(' . $alias . '.izlemiyapan AS CHAR), \' \', \'\'))';
    }

    public static function userInPlanCondition(int $userId, string $alias = 'p'): string
    {
        $uid = (int) $userId;

        return 'FIND_IN_SET(' . $uid . ', REPLACE(CAST(' . $alias . '.planiyapan AS CHAR), \' \', \'\'))';
    }

    /**
     * Metrik için ek WHERE parçaları (visit/plan tabanlı).
     *
     * @return list<string>
     */
    private function metricExtraWhere(string $metricKey): array
    {
        switch ($metricKey) {
            case 'visits_done':
                return ['COALESCE(i.yapildimi, 0) = 1'];
            case 'visits_missed':
                return ['COALESCE(i.yapildimi, 0) = 0'];
            case 'visits_with_vehicle':
                return ['COALESCE(i.arac, 0) > 0'];
            case 'visits_with_kons':
                return ['TRIM(COALESCE(i.brans, \'\')) <> \'\''];
            case 'visits_this_month':
                return ['DATE_FORMAT(i.izlemtarihi, \'%Y-%m\') = DATE_FORMAT(CURDATE(), \'%Y-%m\')'];
            case 'visits_this_year':
                return ['YEAR(i.izlemtarihi) = YEAR(CURDATE())'];
            case 'visits_last_30_days':
                return ['i.izlemtarihi >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)'];
            case 'visits_last_7_days':
                return ['i.izlemtarihi >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)'];
            case 'plans_open':
                return ['COALESCE(p.durum, 0) = 0'];
            case 'plans_done':
                return ['COALESCE(p.durum, 0) = 1'];
            case 'plans_open_overdue':
                return ['COALESCE(p.durum, 0) = 0', 'p.planlanantarih < CURDATE()'];
            case 'plans_due_next_7_days':
                return [
                    'COALESCE(p.durum, 0) = 0',
                    'p.planlanantarih >= CURDATE()',
                    'p.planlanantarih <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)',
                ];
            case 'plans_this_month':
                return ['DATE_FORMAT(p.planlanantarih, \'%Y-%m\') = DATE_FORMAT(CURDATE(), \'%Y-%m\')'];
            case 'plans_this_year':
                return ['YEAR(p.planlanantarih) = YEAR(CURDATE())'];
            case 'nobet_total':
                return ['n.durum = 1'];
            case 'nobet_this_month':
                return [
                    'n.durum = 1',
                    'DATE_FORMAT(n.nobet_tarihi, \'%Y-%m\') = DATE_FORMAT(CURDATE(), \'%Y-%m\')',
                ];
            default:
                return [];
        }
    }

    public function countDetail(int $userId, string $metricKey): int
    {
        $metric = UserProfileStatsHelper::metric($metricKey);
        if ($metric === null || $userId <= 0) {
            return 0;
        }

        $listType = $metric['list_type'];

        try {
            if ($listType === 'visit') {
                $in = self::userInIzlemCondition($userId, 'i');
                $where = array_merge([$in], $this->metricExtraWhere($metricKey));
                $sql = 'SELECT COUNT(*) FROM #__izlemler i WHERE ' . implode(' AND ', $where);

                return (int) $this->db->loadResultPrepared($sql);
            }
            if ($listType === 'plan') {
                $in = self::userInPlanCondition($userId, 'p');
                $where = array_merge([$in], $this->metricExtraWhere($metricKey));
                $sql = 'SELECT COUNT(*) FROM #__pizlemler p WHERE ' . implode(' AND ', $where);

                return (int) $this->db->loadResultPrepared($sql);
            }
            if ($listType === 'patient_visit') {
                $in = self::userInIzlemCondition($userId, 'i');
                $sql = "SELECT COUNT(DISTINCT i.hastatckimlik) FROM #__izlemler i WHERE {$in}";

                return (int) $this->db->loadResultPrepared($sql);
            }
            if ($listType === 'patient_plan') {
                $in = self::userInPlanCondition($userId, 'p');
                $sql = "SELECT COUNT(DISTINCT p.hastatckimlik) FROM #__pizlemler p WHERE {$in}";

                return (int) $this->db->loadResultPrepared($sql);
            }
            if ($listType === 'nobet') {
                $uid = (int) $userId;
                $where = ['n.personel_id = ?'];
                $where = array_merge($where, $this->metricExtraWhere($metricKey));
                $sql = 'SELECT COUNT(*) FROM #__personel_nobet n WHERE ' . implode(' AND ', $where);

                return (int) $this->db->loadResultPrepared($sql, [$uid]);
            }
            if ($listType === 'izin') {
                return (int) $this->db->loadResultPrepared(
                    'SELECT COUNT(*) FROM #__personel_izin iz WHERE iz.personel_id = ?',
                    [(int) $userId]
                );
            }
            if ($listType === 'istek') {
                return (int) $this->db->loadResultPrepared(
                    'SELECT COUNT(*) FROM #__personel_istek ist WHERE ist.personel_id = ?',
                    [(int) $userId]
                );
            }
            if ($listType === 'ekip') {
                $uid = (int) $userId;
                $sql = 'SELECT COUNT(*) FROM #__ekipler e
                    WHERE FIND_IN_SET(' . $uid . ', REPLACE(COALESCE(e.user_ids, \'\'), \' \', \'\'))';

                return (int) $this->db->loadResultPrepared($sql);
            }
            if ($listType === 'wound_photo') {
                return (int) $this->db->loadResultPrepared(
                    'SELECT COUNT(*) FROM #__hasta_yara_fotolar wf WHERE wf.yukleyen_id = ?',
                    [(int) $userId]
                );
            }
        } catch (\Throwable $e) {
            return 0;
        }

        return 0;
    }

    /**
     * @return list<object>
     */
    public function getDetailRows(int $userId, string $metricKey, int $limit, int $offset): array
    {
        $metric = UserProfileStatsHelper::metric($metricKey);
        if ($metric === null || $userId <= 0) {
            return [];
        }

        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $listType = $metric['list_type'];

        try {
            if ($listType === 'visit') {
                return $this->fetchVisitRows($userId, $metricKey, $limit, $offset);
            }
            if ($listType === 'plan') {
                return $this->fetchPlanRows($userId, $metricKey, $limit, $offset);
            }
            if ($listType === 'patient_visit') {
                return $this->fetchPatientVisitRows($userId, $limit, $offset);
            }
            if ($listType === 'patient_plan') {
                return $this->fetchPatientPlanRows($userId, $limit, $offset);
            }
            if ($listType === 'nobet') {
                return $this->fetchNobetRows($userId, $metricKey, $limit, $offset);
            }
            if ($listType === 'izin') {
                return $this->fetchIzinRows($userId, $limit, $offset);
            }
            if ($listType === 'istek') {
                return $this->fetchIstekRows($userId, $limit, $offset);
            }
            if ($listType === 'ekip') {
                return $this->fetchEkipRows($userId, $limit, $offset);
            }
            if ($listType === 'wound_photo') {
                return $this->fetchWoundPhotoRows($userId, $limit, $offset);
            }
        } catch (\Throwable $e) {
            return [];
        }

        return [];
    }

    /**
     * @return list<object>
     */
    private function fetchVisitRows(int $userId, string $metricKey, int $limit, int $offset): array
    {
        $in = self::userInIzlemCondition($userId, 'i');
        $where = array_merge([$in], $this->metricExtraWhere($metricKey));
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT i.id, i.izlemtarihi, i.yapildimi, i.hastatckimlik, i.brans,
                       h.id AS hid, h.isim, h.soyisim,
                       (SELECT GROUP_CONCAT(isl2.islemadi ORDER BY isl2.id SEPARATOR ', ')
                          FROM #__islemler isl2
                          WHERE FIND_IN_SET(isl2.id, REPLACE(i.yapilan, ' ', ''))) AS yapilanlar
                FROM #__izlemler i
                LEFT JOIN #__hastalar h ON h.tckimlik = i.hastatckimlik
                {$whereSql}
                ORDER BY i.izlemtarihi DESC, i.id DESC
                LIMIT {$limit} OFFSET {$offset}";
        $rows = $this->db->fetchObjectListPrepared($sql);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<object>
     */
    private function fetchPlanRows(int $userId, string $metricKey, int $limit, int $offset): array
    {
        $in = self::userInPlanCondition($userId, 'p');
        $where = array_merge([$in], $this->metricExtraWhere($metricKey));
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT p.id, p.planlanantarih, p.durum, p.hastatckimlik, p.oncelik,
                       h.id AS hid, h.isim, h.soyisim,
                       (SELECT GROUP_CONCAT(isl2.islemadi ORDER BY isl2.id SEPARATOR ', ')
                          FROM #__islemler isl2
                          WHERE FIND_IN_SET(isl2.id, REPLACE(p.yapilacak, ' ', ''))) AS yapilacaklar
                FROM #__pizlemler p
                LEFT JOIN #__hastalar h ON h.tckimlik = p.hastatckimlik
                {$whereSql}
                ORDER BY p.planlanantarih DESC, p.id DESC
                LIMIT {$limit} OFFSET {$offset}";
        $rows = $this->db->fetchObjectListPrepared($sql);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<object>
     */
    private function fetchPatientVisitRows(int $userId, int $limit, int $offset): array
    {
        $in = self::userInIzlemCondition($userId, 'i');
        $sql = "SELECT h.id AS hid, h.isim, h.soyisim, h.tckimlik,
                       COUNT(i.id) AS kayit_sayisi,
                       MAX(i.izlemtarihi) AS son_izlem_tarihi
                FROM #__izlemler i
                INNER JOIN #__hastalar h ON h.tckimlik = i.hastatckimlik
                WHERE {$in}
                GROUP BY h.id, h.isim, h.soyisim, h.tckimlik
                ORDER BY h.isim ASC, h.soyisim ASC
                LIMIT {$limit} OFFSET {$offset}";
        $rows = $this->db->fetchObjectListPrepared($sql);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<object>
     */
    private function fetchPatientPlanRows(int $userId, int $limit, int $offset): array
    {
        $in = self::userInPlanCondition($userId, 'p');
        $sql = "SELECT h.id AS hid, h.isim, h.soyisim, h.tckimlik,
                       COUNT(p.id) AS kayit_sayisi,
                       MAX(p.planlanantarih) AS son_plan_tarihi
                FROM #__pizlemler p
                INNER JOIN #__hastalar h ON h.tckimlik = p.hastatckimlik
                WHERE {$in}
                GROUP BY h.id, h.isim, h.soyisim, h.tckimlik
                ORDER BY h.isim ASC, h.soyisim ASC
                LIMIT {$limit} OFFSET {$offset}";
        $rows = $this->db->fetchObjectListPrepared($sql);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<object>
     */
    private function fetchNobetRows(int $userId, string $metricKey, int $limit, int $offset): array
    {
        $where = ['n.personel_id = ?'];
        $where = array_merge($where, $this->metricExtraWhere($metricKey));
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT n.id, n.nobet_tarihi, n.nobet_tipi, n.durum, n.created_at
                FROM #__personel_nobet n
                {$whereSql}
                ORDER BY n.nobet_tarihi DESC, n.id DESC
                LIMIT {$limit} OFFSET {$offset}";
        $rows = $this->db->fetchObjectListPrepared($sql, [(int) $userId]);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<object>
     */
    private function fetchIzinRows(int $userId, int $limit, int $offset): array
    {
        $sql = 'SELECT iz.id, iz.baslangic_tarihi, iz.bitis_tarihi, iz.sebep
                FROM #__personel_izin iz
                WHERE iz.personel_id = ?
                ORDER BY iz.baslangic_tarihi DESC, iz.id DESC
                LIMIT ' . $limit . ' OFFSET ' . $offset;
        $rows = $this->db->fetchObjectListPrepared($sql, [(int) $userId]);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<object>
     */
    private function fetchIstekRows(int $userId, int $limit, int $offset): array
    {
        $sql = 'SELECT ist.id, ist.baslangic_tarihi, ist.bitis_tarihi, ist.aciklama
                FROM #__personel_istek ist
                WHERE ist.personel_id = ?
                ORDER BY ist.baslangic_tarihi DESC, ist.id DESC
                LIMIT ' . $limit . ' OFFSET ' . $offset;
        $rows = $this->db->fetchObjectListPrepared($sql, [(int) $userId]);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<object>
     */
    private function fetchEkipRows(int $userId, int $limit, int $offset): array
    {
        $uid = (int) $userId;
        $sql = "SELECT e.id, e.tarih, e.vardiya, e.ekip_no, e.baslangic_saati, e.user_ids
                FROM #__ekipler e
                WHERE FIND_IN_SET({$uid}, REPLACE(COALESCE(e.user_ids, ''), ' ', ''))
                ORDER BY e.tarih DESC, e.id DESC
                LIMIT {$limit} OFFSET {$offset}";
        $rows = $this->db->fetchObjectListPrepared($sql);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return list<object>
     */
    private function fetchWoundPhotoRows(int $userId, int $limit, int $offset): array
    {
        $sql = 'SELECT wf.id, wf.hasta_id, wf.cekim_tarihi, wf.created_at, wf.aciklama, wf.yara_bolgesi,
                       h.id AS hid, h.isim, h.soyisim
                FROM #__hasta_yara_fotolar wf
                LEFT JOIN #__hastalar h ON h.id = wf.hasta_id
                WHERE wf.yukleyen_id = ?
                ORDER BY COALESCE(wf.cekim_tarihi, wf.created_at) DESC, wf.id DESC
                LIMIT ' . $limit . ' OFFSET ' . $offset;
        $rows = $this->db->fetchObjectListPrepared($sql, [(int) $userId]);

        return is_array($rows) ? $rows : [];
    }
}
