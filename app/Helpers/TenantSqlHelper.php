<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Kurum kapsamı SQL parçaları — modellerde tekrar kullanım.
 */
final class TenantSqlHelper
{
    /** Süper yönetici tüm kurumlardaki izlem/plan kayıtlarını görür. */
    public static function izlemScopedToPatientKurum(): bool
    {
        return !AuthHelper::sessionIsSuperAdmin();
    }

    /**
     * İzlem/pizlem alt sorgusu — hasta ile aynı kurum (personel/admin).
     * Süper yönetici için boş döner.
     */
    public static function izMatchesPatientKurumSql(string $alias = ''): string
    {
        if (!self::izlemScopedToPatientKurum()) {
            return '';
        }
        $a = $alias !== '' ? preg_replace('/[^a-zA-Z0-9_]/', '', $alias) . '.' : '';

        return ' AND ' . $a . 'kurum_id = h.kurum_id';
    }

    /**
     * @return string Boş veya " AND alias.column = N"
     */
    public static function andEquals(string $tableAlias = 'h', string $column = 'kurum_id'): string
    {
        $kid = TenantContext::filterKurumId();
        if ($kid === null) {
            return '';
        }

        return ' AND ' . self::qualify($tableAlias, $column) . ' = ' . (int) $kid;
    }

    /**
     * WHERE parçasına kurum filtresi ekler (WHERE zaten var varsayılır).
     */
    public static function appendToWhere(string $where, string $tableAlias = 'h', string $column = 'kurum_id'): string
    {
        return $where . self::andEquals($tableAlias, $column);
    }

    /**
     * Düz tablo sorguları (JOIN yok).
     */
    public static function whereOnly(string $column = 'kurum_id'): string
    {
        $kid = TenantContext::filterKurumId();
        if ($kid === null) {
            return '';
        }

        return ' WHERE `' . preg_replace('/[^a-z0-9_]/', '', $column) . '` = ' . (int) $kid;
    }

    /**
     * Mevcut WHERE parçalarına AND ekler.
     *
     * @param list<string> $parts
     */
    public static function mergeParts(array &$parts, string $tableAlias = '', string $column = 'kurum_id'): void
    {
        $kid = TenantContext::filterKurumId();
        if ($kid === null) {
            return;
        }
        if ($tableAlias !== '') {
            $parts[] = self::qualify($tableAlias, $column) . ' = ' . (int) $kid;
        } else {
            $parts[] = '`' . preg_replace('/[^a-z0-9_]/', '', $column) . '` = ' . (int) $kid;
        }
    }

    public static function sqlWhereKurum(string $column = 'kurum_id'): string
    {
        $kid = TenantContext::filterKurumId();
        if ($kid === null) {
            return '';
        }
        $c = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

        return ' WHERE `' . $c . '` = ' . (int) $kid;
    }

    /**
     * Alias olmadan (tek tablo): " AND kurum_id = N"
     */
    public static function andBare(string $column = 'kurum_id'): string
    {
        $kid = TenantContext::filterKurumId();
        if ($kid === null) {
            return '';
        }
        $c = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

        return ' AND `' . $c . '` = ' . (int) $kid;
    }

    private static function qualify(string $alias, string $column): string
    {
        $a = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);
        $c = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

        return $a . '.' . $c;
    }
}
