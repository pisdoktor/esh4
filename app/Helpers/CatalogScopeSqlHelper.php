<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Brans;
use App\Models\Islem;
use App\Models\Istek;
use App\Models\KurumBrans;
use App\Models\KurumHastalik;
use App\Models\KurumIslem;
use App\Models\KurumIstek;

/**
 * Platform kataloğu (kurum_id=0) + esh_kurum_* junction — istatistik ve rapor SQL parçaları.
 */
final class CatalogScopeSqlHelper
{
    public static function filterKurumId(): ?int
    {
        return TenantContext::filterKurumId();
    }

    public static function sqlPlatformCatalogWhere(string $alias, string $column = 'kurum_id'): string
    {
        return ' AND ' . self::sqlPlatformCatalogOnEquals($alias, $column);
    }

    public static function sqlPlatformCatalogOnEquals(string $alias, string $column = 'kurum_id'): string
    {
        $a = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);
        $c = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

        return $a . '.' . $c . ' = ' . CatalogStoreHelper::PLATFORM_KURUM_ID;
    }

    public static function sqlBransAssignedJoin(string $b = 'b', string $kb = 'kb'): string
    {
        $kid = self::filterKurumId();
        if ($kid === null || $kid <= 0 || !KurumBrans::tableExists()) {
            return '';
        }
        $b = preg_replace('/[^a-zA-Z0-9_]/', '', $b);
        $kb = preg_replace('/[^a-zA-Z0-9_]/', '', $kb);

        return ' INNER JOIN #__kurum_brans AS ' . $kb
            . ' ON ' . $kb . '.brans_id = ' . $b . '.id AND ' . $kb . '.kurum_id = ' . (int) $kid;
    }

    public static function sqlIstekAssignedJoin(string $i = 'i', string $ki = 'ki'): string
    {
        $kid = self::filterKurumId();
        if ($kid === null || $kid <= 0 || !KurumIstek::tableExists()) {
            return '';
        }
        $i = preg_replace('/[^a-zA-Z0-9_]/', '', $i);
        $ki = preg_replace('/[^a-zA-Z0-9_]/', '', $ki);

        return ' INNER JOIN #__kurum_istek AS ' . $ki
            . ' ON ' . $ki . '.istek_id = ' . $i . '.id AND ' . $ki . '.kurum_id = ' . (int) $kid;
    }

    public static function sqlIslemAssignedJoin(string $i = 'i', string $km = 'km'): string
    {
        $kid = self::filterKurumId();
        if ($kid === null || $kid <= 0 || !KurumIslem::tableExists()) {
            return '';
        }
        $i = preg_replace('/[^a-zA-Z0-9_]/', '', $i);
        $km = preg_replace('/[^a-zA-Z0-9_]/', '', $km);

        return ' INNER JOIN #__kurum_islem AS ' . $km
            . ' ON ' . $km . '.islem_id = ' . $i . '.id AND ' . $km . '.kurum_id = ' . (int) $kid;
    }

    public static function sqlHastalikAssignedJoin(string $h = 'h', string $kh = 'kh'): string
    {
        $kid = self::filterKurumId();
        if ($kid === null || $kid <= 0 || !KurumHastalik::tableExists()) {
            return '';
        }
        $h = preg_replace('/[^a-zA-Z0-9_]/', '', $h);
        $kh = preg_replace('/[^a-zA-Z0-9_]/', '', $kh);

        return ' INNER JOIN #__kurum_hastalik AS ' . $kh
            . ' ON ' . $kh . '.hastalik_id = ' . $h . '.id AND ' . $kh . '.kurum_id = ' . (int) $kid;
    }

    /**
     * Kurum kapsamında: junction atanmış veya aktif hastada fiilen kullanılmış tanılar.
     */
    public static function sqlHastalikOperationalScopeWhere(string $alias = 'h', string $idColumn = 'id'): string
    {
        $kid = self::filterKurumId();
        if ($kid === null || $kid <= 0 || !KurumHastalik::tableExists()) {
            return '';
        }
        $a = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);
        $c = preg_replace('/[^a-zA-Z0-9_]/', '', $idColumn);
        $kid = (int) $kid;

        return ' AND (' . $a . '.' . $c . ' IN (SELECT hastalik_id FROM #__kurum_hastalik WHERE kurum_id = ' . $kid . ')'
            . ' OR EXISTS (SELECT 1 FROM #__hastalar hp WHERE hp.pasif = \'0\' AND hp.kurum_id = ' . $kid
            . ' AND FIND_IN_SET(' . $a . '.icd, REPLACE(hp.hastaliklar, \' \', \'\')) > 0))';
    }

    /** @return array<int, string> */
    public static function loadHastalikIdLabelMap(): array
    {
        $model = new \App\Models\Hastalik();
        $kid = self::filterKurumId();
        $rows = ($kid !== null && $kid > 0) ? $model->getList((int) $kid) : $model->getCatalogList();
        $map = [];
        foreach ($rows as $r) {
            $id = (int) ($r->id ?? 0);
            if ($id <= 0) {
                continue;
            }
            $icd = trim((string) ($r->icd ?? ''));
            $name = trim((string) ($r->hastalikadi ?? ''));
            $label = $name;
            if ($icd !== '' && $name !== '') {
                $label = $icd . '-' . $name;
            } elseif ($icd !== '') {
                $label = $icd;
            }
            $map[$id] = $label !== '' ? $label : ('Tanı #' . $id);
        }

        return $map;
    }

    /** @return array<int, string> */
    public static function loadBransIdNameMap(): array
    {
        $model = new Brans();
        $kid = self::filterKurumId();
        $rows = ($kid !== null && $kid > 0) ? $model->getList() : $model->getCatalogList();

        return self::rowsToIdNameMap($rows, 'bransadi', 'Branş');
    }

    /** @return array<int, string> */
    public static function loadIstekIdNameMap(): array
    {
        $model = new Istek();
        $kid = self::filterKurumId();
        $rows = ($kid !== null && $kid > 0) ? $model->getList() : $model->getCatalogList();

        return self::rowsToIdNameMap($rows, 'istek_adi', 'İstek');
    }

    /** @return array<int, string> */
    public static function loadIslemIdNameMap(): array
    {
        $model = new Islem();
        $kid = self::filterKurumId();
        $rows = ($kid !== null && $kid > 0) ? $model->getList() : $model->getCatalogList();

        return self::rowsToIdNameMap($rows, 'islemadi', 'İşlem');
    }

    /**
     * @param list<object>|array<int, object> $rows
     * @return array<int, string>
     */
    private static function rowsToIdNameMap(array $rows, string $nameField, string $fallbackPrefix): array
    {
        $map = [];
        foreach ($rows as $r) {
            $id = (int) ($r->id ?? 0);
            if ($id <= 0) {
                continue;
            }
            $label = trim((string) ($r->{$nameField} ?? ''));
            $map[$id] = $label !== '' ? $label : ($fallbackPrefix . ' #' . $id);
        }

        return $map;
    }
}
