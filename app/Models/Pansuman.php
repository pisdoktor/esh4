<?php
namespace App\Models;

use App\Helpers\TenantSqlHelper;
use App\Helpers\TrSearchFoldHelper;

class Pansuman extends BaseModel {
    
    public function __construct() {
        parent::__construct('#__hastalar', 'id');
    }

    /**
     * @return array{0: list<string>, 1: list<mixed>}
     */
    private function buildPansumanWhere(string $search, string $filterDay, ?int $kurumIdFilter = null): array
    {
        $where = ['h.pasif = 0', 'h.pansuman = 1'];
        $params = [];

        if ($search !== '') {
            $like = TrSearchFoldHelper::likePattern($search);
            $isimFold = TrSearchFoldHelper::sqlFoldExpr('h.isim');
            $soyisimFold = TrSearchFoldHelper::sqlFoldExpr('h.soyisim');
            $tcDigits = preg_replace('/\D+/', '', $search);
            $tcLike = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $tcDigits) . '%';
            $where[] = "({$isimFold} LIKE ? OR {$soyisimFold} LIKE ? OR h.tckimlik LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $tcLike;
        }

        if ($filterDay !== '') {
            $day = (int) $filterDay;
            if ($day >= 1 && $day <= 7) {
                $where[] = "FIND_IN_SET(?, h.pgunleri)";
                $params[] = (string) $day;
            }
        }

        if ($kurumIdFilter !== null && $kurumIdFilter > 0) {
            $where[] = 'h.kurum_id = ' . (int) $kurumIdFilter;
        } else {
            TenantSqlHelper::mergeParts($where, 'h', 'kurum_id');
        }

        return [$where, $params];
    }

    /**
     * Pansuman hastalarını filtreli, sayfalı ve gün bazlı getirir
     */
    public function getPansumanList($search = '', $filter_day = '', $limit = 20, $offset = 0, ?int $kurumIdFilter = null, string $orderFragment = 'h.isim ASC') {
        $search = is_string($search) ? trim($search) : '';
        $filter_day = is_string($filter_day) ? trim($filter_day) : (string) $filter_day;
        [$where, $params] = $this->buildPansumanWhere($search, $filter_day, $kurumIdFilter);
        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);

        $sql = "SELECT h.*, m.adi AS mahalle, il.adi AS ilce 
                FROM {$this->_tbl} AS h
                LEFT JOIN #__adrestablosu AS il ON il.id = h.ilce
                LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle
                WHERE " . implode(' AND ', $where) . "
                ORDER BY {$orderFragment}
                LIMIT {$limit} OFFSET {$offset}";

        return $this->db->fetchObjectListPrepared($sql, $params);
    }

    /**
     * Toplam sayı (Pagination için)
     */
    public function getPansumanCount($search = '', $filter_day = '', ?int $kurumIdFilter = null) {
        $search = is_string($search) ? trim($search) : '';
        $filter_day = is_string($filter_day) ? trim($filter_day) : (string) $filter_day;
        [$where, $params] = $this->buildPansumanWhere($search, $filter_day, $kurumIdFilter);

        $sql = "SELECT COUNT(h.id) AS cnt FROM {$this->_tbl} AS h WHERE " . implode(' AND ', $where);
        $row = $this->db->fetchOnePrepared($sql, $params);

        return (int) ($row['cnt'] ?? 0);
    }
}
