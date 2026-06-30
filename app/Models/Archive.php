<?php
namespace App\Models;

use App\Helpers\KurumAdresScope;
use App\Helpers\TenantSqlHelper;

class Archive extends BaseModel {
    
    public function __construct() {
        parent::__construct('#__hastalar', 'id');
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{isim:string, soyisim:string, mahalle:list<string>}
     */
    private function normalizeFilters(array $filters): array
    {
        $mahalle = $filters['mahalle'] ?? [];
        if (!is_array($mahalle)) {
            $mahalle = ($mahalle !== '' && $mahalle !== null) ? [(string) $mahalle] : [];
        }
        $mahalle = array_values(array_filter(array_map('strval', $mahalle)));

        $kid = KurumAdresScope::effectiveKurumId();
        if ($kid !== null && KurumAdresScope::shouldFilter($kid)) {
            $mahalle = array_values(array_filter(
                $mahalle,
                static fn(string $id): bool => KurumAdresScope::isAllowed($kid, $id)
            ));
        }

        return [
            'isim' => trim((string) ($filters['isim'] ?? '')),
            'soyisim' => trim((string) ($filters['soyisim'] ?? '')),
            'mahalle' => $mahalle,
        ];
    }

    /** @param list<string> $where */
    private function appendPatientMahalleScope(array &$where, string $mahalleAlias = 'm'): void
    {
        $kid = KurumAdresScope::effectiveKurumId();
        if ($kid === null) {
            return;
        }
        $scopeSql = KurumAdresScope::sqlMahalleScope($mahalleAlias, $kid);
        if ($scopeSql !== '') {
            $where[] = ltrim($scopeSql, ' AND');
        }
    }

    public function getArchivedPatients($filters = [], $limit = 20, $offset = 0, string $orderFragment = 'h.isim ASC, h.soyisim ASC') {
        $filters = $this->normalizeFilters(is_array($filters) ? $filters : []);
        $where = ["h.pasif = 0"];
        $params = [];

        if ($filters['isim'] !== '') {
            $where[] = "h.isim LIKE ?";
            $params[] = $filters['isim'] . '%';
        }

        if ($filters['soyisim'] !== '') {
            $where[] = "h.soyisim LIKE ?";
            $params[] = $filters['soyisim'] . '%';
        }

        if (!empty($filters['mahalle'])) {
            [$inSql, $inParams] = $this->db->whereInClause($filters['mahalle']);
            $where[] = "h.mahalle IN ({$inSql})";
            $params = array_merge($params, $inParams);
        }

        TenantSqlHelper::mergeParts($where, 'h', 'kurum_id');
        $this->appendPatientMahalleScope($where, 'm');

        $whereSql = " WHERE " . implode(' AND ', $where);

        $query = "SELECT h.*, m.adi AS mahalleadi, ilc.adi AS ilceadi,
                 (SELECT COUNT(id) FROM #__izlemler WHERE hastatckimlik = h.tckimlik) AS izlemsayisi,
                 (SELECT MAX(izlemtarihi) FROM #__izlemler WHERE hastatckimlik = h.tckimlik AND yapildimi=1) AS sonizlemtarihi
                 FROM #__hastalar AS h
                 LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle
                 LEFT JOIN #__adrestablosu AS ilc ON ilc.id = h.ilce
                 $whereSql
                 ORDER BY {$orderFragment}
                 LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        return $this->db->fetchObjectListPrepared($query, $params);
    }

    public function getCount($filters = []) {
        $filters = $this->normalizeFilters(is_array($filters) ? $filters : []);
        $where = ["h.pasif = 0"];
        $params = [];

        if ($filters['isim'] !== '') {
            $where[] = "h.isim LIKE ?";
            $params[] = $filters['isim'] . '%';
        }
        if ($filters['soyisim'] !== '') {
            $where[] = "h.soyisim LIKE ?";
            $params[] = $filters['soyisim'] . '%';
        }
        if (!empty($filters['mahalle'])) {
            [$inSql, $inParams] = $this->db->whereInClause($filters['mahalle']);
            $where[] = "h.mahalle IN ({$inSql})";
            $params = array_merge($params, $inParams);
        }

        TenantSqlHelper::mergeParts($where, 'h', 'kurum_id');
        $this->appendPatientMahalleScope($where, 'm');

        $whereSql = " WHERE " . implode(' AND ', $where);
        $query = "SELECT COUNT(h.id) FROM #__hastalar AS h
                  LEFT JOIN #__adrestablosu AS m ON m.id = h.mahalle
                  $whereSql";

        return $this->db->loadResultPrepared($query, $params);
    }
    
    /**
     * Filtre paneli için mahalle hiyerarşisi (UUID Uyumlu)
     */
    public function getLocationHierarchy() {
        $where = ["m.tip='mahalle'"];
        $kid = KurumAdresScope::effectiveKurumId();
        if ($kid !== null) {
            $scopeSql = KurumAdresScope::sqlMahalleScope('m', $kid);
            if ($scopeSql !== '') {
                $where[] = ltrim($scopeSql, ' AND');
            }
        }

        $query = "SELECT m.id, m.adi AS mahalle, i.adi AS ilce, i.id AS ilce_id 
                  FROM #__adrestablosu AS m 
                  LEFT JOIN #__adrestablosu AS i ON i.id = m.ust_id 
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY i.adi ASC, m.adi ASC";
        
        $res = $this->db->fetchObjectListPrepared($query);
        
        $hierarchy = [];
        if ($res) {
            foreach ($res as $row) {
                // İlçe ID'si UUID (string) olsa bile PHP dizi anahtarı olarak kabul eder
                $hierarchy[$row->ilce_id]['name'] = $row->ilce;
                $hierarchy[$row->ilce_id]['mahalleler'][] = [
                    'id'  => $row->id, // Mahalle UUID
                    'adi' => $row->mahalle
                ];
            }
        }
        return $hierarchy;
    }
}