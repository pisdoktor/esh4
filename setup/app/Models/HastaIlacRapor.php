<?php
namespace App\Models;

/**
 * Hasta tanı bazlı ilaç / sağlık raporu takibi (`#__hastailacrapor`).
 */
class HastaIlacRapor extends BaseModel {

    public $id = null;
    public $hastatckimlik = null;
    public $hastalikid = null;
    public $rapor = 0;
    /** @var string|null YYYY-MM-DD */
    public $bitistarihi = null;
    public $brans = null;
    public $raporyeri = 0;

    public function __construct() {
        parent::__construct('#__hastailacrapor', 'id');
    }

    /**
     * @param list<int> $hastalikIds
     * @return list<object> hastalik_id, hastalikadi, rapor_id, hastatckimlik, rapor, bitistarihi, brans, raporyeri
     */
    public function getReportRowsForPatient(string $tc, array $hastalikIds): array {
        $tc = preg_replace('/\D+/', '', $tc);
        if ($tc === '' || strlen($tc) !== 11) {
            return [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $hastalikIds), static fn ($v) => $v > 0)));
        if ($ids === []) {
            return [];
        }
        [$inSql, $inParams] = $this->db->whereInClause($ids);
        $sql = "SELECT h.id AS hastalik_id, h.hastalikadi AS hastalikadi,
                r.id AS rapor_id, r.hastatckimlik AS hastatckimlik,
                CAST(COALESCE(r.rapor, 0) AS UNSIGNED) AS rapor,
                r.bitistarihi AS bitistarihi,
                r.brans AS brans,
                CAST(COALESCE(r.raporyeri, 0) AS UNSIGNED) AS raporyeri
            FROM #__hastaliklar h
            LEFT JOIN #__hastailacrapor r ON r.hastalikid = h.id AND r.hastatckimlik = ?
            WHERE h.id IN ({$inSql})
            ORDER BY h.hastalikadi ASC";

        return $this->db->fetchObjectListPrepared($sql, array_merge([$tc], $inParams));
    }

    public function findByIdForTc(int $raporId, string $tc): bool {
        $tc = preg_replace('/\D+/', '', $tc);
        if ($raporId < 1 || strlen($tc) !== 11) {
            return false;
        }
        $row = $this->db->fetchObjectPrepared(
            'SELECT * FROM #__hastailacrapor WHERE id = ? AND hastatckimlik = ? LIMIT 1',
            [$raporId, $tc]
        );
        if (!$row) {
            return false;
        }
        $this->_dirty = [];
        $this->bind((array) $row, false);

        return true;
    }

    /**
     * brans[] POST dizisinden virgüllü branş id listesi (sıralı, tekil).
     *
     * @param mixed $raw
     */
    public static function normalizeBransCsv($raw): string {
        if (!is_array($raw)) {
            return '';
        }
        $ids = [];
        foreach ($raw as $v) {
            $n = (int) $v;
            if ($n > 0) {
                $ids[$n] = $n;
            }
        }
        sort($ids);

        return implode(',', $ids);
    }
}
