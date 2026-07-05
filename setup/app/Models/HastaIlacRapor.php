<?php
namespace App\Models;

use App\Helpers\CatalogStoreHelper;

/**
 * Hasta tanı bazlı ilaç / sağlık raporu takibi (`#__hastailacrapor`).
 */
class HastaIlacRapor extends BaseModel {

    public $id = null;
    public $hastatckimlik = null;
    public $hastalikicd = null;
    public $rapor = 0;
    /** @var string|null YYYY-MM-DD */
    public $bitistarihi = null;
    public $brans = null;
    public $raporyeri = 0;

    public function __construct() {
        parent::__construct('#__hastailacrapor', 'id');
    }

    /**
     * @param list<string> $hastalikIcds
     * @return list<object> hastalik_icd, hastalik_id, hastalikadi, rapor_id, hastatckimlik, rapor, bitistarihi, brans, raporyeri
     */
    public function getReportRowsForPatient(string $tc, array $hastalikIcds): array {
        $tc = preg_replace('/\D+/', '', $tc);
        if ($tc === '' || strlen($tc) !== 11) {
            return [];
        }
        $icds = array_values(array_unique(array_filter(array_map(
            static fn ($v) => Patient::normalizeHastalikIcd((string) $v),
            $hastalikIcds
        ), static fn ($v) => $v !== '')));
        if ($icds === []) {
            return [];
        }
        [$inSql, $inParams] = $this->db->whereInClause($icds);
        $params = array_merge([$tc, CatalogStoreHelper::PLATFORM_KURUM_ID], $inParams);
        $sql = "SELECT h.icd AS hastalik_icd, h.id AS hastalik_id, h.hastalikadi AS hastalikadi,
                r.id AS rapor_id, r.hastatckimlik AS hastatckimlik,
                CAST(COALESCE(r.rapor, 0) AS UNSIGNED) AS rapor,
                r.bitistarihi AS bitistarihi,
                r.brans AS brans,
                CAST(COALESCE(r.raporyeri, 0) AS UNSIGNED) AS raporyeri
            FROM #__hastaliklar h
            LEFT JOIN #__hastailacrapor r ON r.hastalikicd = h.icd AND r.hastatckimlik = ?
            WHERE h.kurum_id = ? AND h.icd IN ({$inSql})
            ORDER BY h.hastalikadi ASC";

        return $this->db->fetchObjectListPrepared($sql, $params);
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
