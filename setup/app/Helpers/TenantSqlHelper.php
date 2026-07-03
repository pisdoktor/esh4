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

     * @return string Boş veya " AND alias.column = N" / IN (...)

     */

    public static function andEquals(string $tableAlias = 'h', string $column = 'kurum_id'): string

    {

        return self::scopeSql($tableAlias, $column, false);

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

        return self::scopeSql('', $column, true);

    }



    /**

     * Mevcut WHERE parçalarına AND ekler.

     *

     * @param list<string> $parts

     */

    public static function mergeParts(array &$parts, string $tableAlias = '', string $column = 'kurum_id'): void

    {

        $sql = self::scopeSql($tableAlias, $column, false);

        if ($sql === '') {

            return;

        }

        $parts[] = ltrim(substr($sql, 5));

    }



    public static function sqlWhereKurum(string $column = 'kurum_id'): string

    {

        return self::scopeSql('', $column, true);

    }



    /**

     * Alias olmadan (tek tablo): " AND kurum_id = N"

     */

    public static function andBare(string $column = 'kurum_id'): string

    {

        return self::scopeSql('', $column, false);

    }



    private static function scopeSql(string $tableAlias, string $column, bool $wherePrefix): string

    {

        $ids = TenantContext::filterKurumIds();

        if ($ids === null) {

            return '';

        }

        $col = $tableAlias !== ''

            ? self::qualify($tableAlias, $column)

            : '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $column) . '`';

        $prefix = $wherePrefix ? ' WHERE ' : ' AND ';

        if ($ids === []) {

            return $prefix . '1=0';

        }

        if (count($ids) === 1) {

            return $prefix . $col . ' = ' . (int) $ids[0];

        }



        return $prefix . $col . ' IN (' . implode(',', array_map('intval', $ids)) . ')';

    }



    private static function qualify(string $alias, string $column): string

    {

        $a = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);

        $c = preg_replace('/[^a-zA-Z0-9_]/', '', $column);



        return $a . '.' . $c;

    }

}


