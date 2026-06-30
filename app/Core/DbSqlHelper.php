<?php

declare(strict_types=1);



namespace App\Core;



final class DbSqlHelper

{

    /** @var list<string> */

    public const SUPPORTED_DRIVERS = ['mysql', 'sqlsrv', 'pgsql', 'sqlite', 'oci'];



    public static function driver(): string

    {

        if (!defined('DB_DRIVER')) {

            return 'mysql';

        }

        $driver = strtolower(trim((string) DB_DRIVER));

        return self::isValidDbDriver($driver) ? self::normalizeDbDriver($driver) : 'mysql';

    }



    public static function isSqlSrv(): bool

    {

        return self::driver() === 'sqlsrv';

    }



    public static function isMysql(): bool

    {

        return self::driver() === 'mysql';

    }



    public static function isPgsql(): bool

    {

        return self::driver() === 'pgsql';

    }



    public static function isSqlite(): bool

    {

        return self::driver() === 'sqlite';

    }



    public static function isOci(): bool

    {

        return self::driver() === 'oci';

    }



    public static function identifier(string $name): string

    {

        $driver = self::driver();

        if ($driver === 'sqlsrv') {

            return '[' . $name . ']';

        }

        if (in_array($driver, ['pgsql', 'sqlite', 'oci'], true)) {

            return '"' . str_replace('"', '""', $name) . '"';

        }



        return '`' . $name . '`';

    }



    public static function nowExpr(): string

    {

        return match (self::driver()) {

            'sqlsrv' => 'GETDATE()',

            'oci' => 'SYSTIMESTAMP',

            default => 'NOW()',

        };

    }



    public static function applyLimit(string $sql, int $offset, int $limit): string

    {

        if ($limit <= 0) {

            return $sql;

        }

        $driver = self::driver();

        if ($driver === 'mysql') {

            return $sql . ' LIMIT ' . $offset . ', ' . $limit;

        }

        if ($driver === 'sqlite' || $driver === 'pgsql') {

            return $sql . ' LIMIT ' . $limit . ' OFFSET ' . $offset;

        }

        if (!preg_match('/\border\s+by\b/i', $sql)) {

            $sql .= ' ORDER BY (SELECT 1)';

        }



        return $sql . ' OFFSET ' . $offset . ' ROWS FETCH NEXT ' . $limit . ' ROWS ONLY';

    }



    public static function isValidColumnIdentifier(string $name): bool

    {

        return $name !== '' && (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name);

    }



    public static function isValidTableIdentifier(string $name): bool

    {

        return $name !== '' && (bool) preg_match('/^[a-zA-Z0-9_]+$/', $name);

    }



    public static function tablePrefix(): string

    {

        return defined('DB_PREFIX') ? (string) DB_PREFIX : 'esh_';

    }



    public static function replacePrefix(string $sql, string $placeholder = '#__', ?string $physicalPrefix = null): string

    {

        $prefix = $physicalPrefix ?? self::tablePrefix();



        return str_replace($placeholder, $prefix, trim($sql));

    }



    /** schema.sql ve seed dosyalarındaki sabit tablo öneki (depo içeriği). */

    public const SQL_FILE_DEFAULT_PREFIX = 'esh_';



    public static function isValidTablePrefix(string $prefix): bool

    {

        return $prefix !== '' && (bool) preg_match('/^[a-zA-Z0-9_]{1,32}$/', $prefix);

    }



    public static function normalizeDbDriver(string $driver): string

    {

        $d = strtolower(trim($driver));

        return match ($d) {

            'sqlsrv', 'mssql' => 'sqlsrv',

            'pgsql', 'postgres', 'postgresql' => 'pgsql',

            'sqlite', 'sqlite3' => 'sqlite',

            'oci', 'oracle' => 'oci',

            default => 'mysql',

        };

    }



    public static function isValidDbDriver(string $driver): bool

    {

        return in_array(self::normalizeDbDriver($driver), self::SUPPORTED_DRIVERS, true);

    }



    public static function pdoExtensionForDriver(string $driver): string

    {

        return match (self::normalizeDbDriver($driver)) {

            'sqlsrv' => 'pdo_sqlsrv',

            'pgsql' => 'pdo_pgsql',

            'sqlite' => 'pdo_sqlite',

            'oci' => 'pdo_oci',

            default => 'pdo_mysql',

        };

    }



    public static function driverLabel(string $driver): string

    {

        return match (self::normalizeDbDriver($driver)) {

            'sqlsrv' => 'SQL Server',

            'pgsql' => 'PostgreSQL',

            'sqlite' => 'SQLite',

            'oci' => 'Oracle',

            default => 'MySQL',

        };

    }



    public const SCHEMA_DIR_RELATIVE = 'database/schemas';

    public static function schemaFileNameForDriver(string $driver): string

    {

        return match (self::normalizeDbDriver($driver)) {

            'sqlsrv' => 'schema.mssql.sql',

            'pgsql' => 'schema.pgsql.sql',

            'sqlite' => 'schema.sqlite.sql',

            'oci' => 'schema.oci.sql',

            default => 'schema.sql',

        };

    }

    public static function schemaRelativePathForDriver(string $driver): string
    {
        return self::SCHEMA_DIR_RELATIVE . '/' . self::schemaFileNameForDriver($driver);
    }

    public static function schemaPathForDriver(string $driver, ?string $projectRoot = null): string
    {
        $root = $projectRoot ?? dirname(__DIR__, 2);

        return $root . '/' . self::schemaRelativePathForDriver($driver);
    }



    private static function sqliteProjectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    private static function isAbsoluteFilesystemPath(string $path): bool
    {
        if ($path === '') {
            return false;
        }
        if ($path[0] === '/' || $path[0] === '\\') {
            return true;
        }

        return (bool) preg_match('/^[A-Za-z]:[\\/]/', $path);
    }

    private static function normalizeSqliteFilesystemPath(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        if ($path === '') {
            return $path;
        }
        if (!self::isAbsoluteFilesystemPath($path)) {
            $path = self::sqliteProjectRoot() . '/' . ltrim($path, '/');
        }

        return $path;
    }

    public static function resolveSqlitePath(string $host, string $dbName): string

    {

        if ($dbName !== '' && (str_contains($dbName, '/') || str_contains($dbName, '\\'))) {

            return self::normalizeSqliteFilesystemPath($dbName);

        }

        $dir = $host !== '' ? rtrim(str_replace('\\', '/', $host), '/') : self::sqliteProjectRoot() . '/storage/data';

        if (!self::isAbsoluteFilesystemPath($dir)) {

            $dir = self::sqliteProjectRoot() . '/' . ltrim($dir, '/');

        }

        $file = $dbName !== '' ? $dbName : 'esh';

        $fileLower = strtolower($file);

        if (!str_ends_with($fileLower, '.sqlite') && !str_ends_with($fileLower, '.db')) {

            $file .= '.sqlite';

        }



        return self::normalizeSqliteFilesystemPath($dir . '/' . $file);

    }

    public static function isSqliteFileHealthy(string $path): bool
    {
        if (!is_file($path)) {
            return true;
        }

        $size = filesize($path);
        if ($size === false || $size < 100) {
            return false;
        }

        if (!extension_loaded('pdo_sqlite')) {
            return true;
        }

        try {
            $pdo = new \PDO('sqlite:' . $path);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $check = $pdo->query('PRAGMA integrity_check')->fetchColumn();

            return $check === 'ok';
        } catch (\Throwable) {
            return false;
        }
    }

    public static function deleteSqliteFileArtifacts(string $path): void
    {
        foreach ([$path, $path . '-wal', $path . '-shm'] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public static function prepareSqliteInstallFile(string $path, bool $createDatabase): array
    {
        if (!is_file($path)) {
            return ['ok' => true];
        }

        if (self::isSqliteFileHealthy($path)) {
            return ['ok' => true];
        }

        if (!$createDatabase) {
            return [
                'ok' => false,
                'message' => 'SQLite dosyası bozuk veya geçersiz: ' . $path
                    . '. Önceki yarım kurulumdan kalmış olabilir; dosyayı silin veya "Veritabanı yoksa oluştur" seçeneğini işaretleyip tekrar deneyin.',
            ];
        }

        self::deleteSqliteFileArtifacts($path);

        return ['ok' => true];
    }



    public static function buildInstallPdoDsn(string $driver, string $host, string $dbName = '', string $port = ''): string

    {

        $driver = self::normalizeDbDriver($driver);

        if ($driver === 'sqlsrv') {

            $server = $host;

            if ($port !== '' && strpos($server, ',') === false && strpos($server, '\\') === false) {

                $server .= ',' . $port;

            }

            $dsn = 'sqlsrv:Server=' . $server . ';TrustServerCertificate=1';

            if ($dbName !== '') {

                $dsn .= ';Database=' . $dbName;

            }



            return $dsn;

        }

        if ($driver === 'pgsql') {

            $portPart = $port !== '' ? ';port=' . (int) $port : '';

            $dsn = 'pgsql:host=' . $host . $portPart;

            $dbname = $dbName !== '' ? $dbName : 'postgres';

            return $dsn . ';dbname=' . $dbname;

        }

        if ($driver === 'sqlite') {

            $path = self::resolveSqlitePath($host, $dbName);



            return 'sqlite:' . $path;

        }

        if ($driver === 'oci') {

            $portNum = $port !== '' ? (int) $port : 1521;

            $service = $dbName !== '' ? $dbName : 'ORCL';



            return 'oci:dbname=//' . $host . ':' . $portNum . '/' . $service;

        }

        $portPart = $port !== '' ? ';port=' . (int) $port : '';

        $dsn = 'mysql:host=' . $host . $portPart . ';charset=utf8mb4';

        if ($dbName !== '') {

            $dsn .= ';dbname=' . $dbName;

        }



        return $dsn;

    }



    /**

     * MySQL seed dosyasını SQL Server sözdizimine uyarlar (#__ / esh_ önek dönüşümü dahil).

     */

    public static function adaptMysqlSeedSqlForSqlSrv(

        string $sql,

        string $targetPrefix,

        string $filePrefix = self::SQL_FILE_DEFAULT_PREFIX

    ): string {

        $sql = self::remapSqlFilePrefix($sql, $targetPrefix, $filePrefix);

        $lines = preg_split('/\r\n|\r|\n/', $sql) ?: [];

        $out = [];

        foreach ($lines as $line) {

            if (preg_match('/^\s*SET (NAMES|FOREIGN_KEY_CHECKS)/i', $line)) {

                continue;

            }

            $converted = preg_replace('/`([a-zA-Z0-9_]+)`/', '[$1]', $line);

            $out[] = $converted ?? $line;

        }



        return trim(implode("\n", $out));

    }



    public static function adaptMysqlSeedSql(

        string $sql,

        string $targetPrefix,

        string $driver,

        string $filePrefix = self::SQL_FILE_DEFAULT_PREFIX

    ): string {

        return match (self::normalizeDbDriver($driver)) {

            'sqlsrv' => self::adaptMysqlSeedSqlForSqlSrv($sql, $targetPrefix, $filePrefix),

            'pgsql' => self::adaptMysqlSeedSqlForPgsql($sql, $targetPrefix, $filePrefix),

            'sqlite' => self::adaptMysqlSeedSqlForSqlite($sql, $targetPrefix, $filePrefix),

            'oci' => self::adaptMysqlSeedSqlForOci($sql, $targetPrefix, $filePrefix),

            default => self::remapSqlFilePrefix($sql, $targetPrefix, $filePrefix),

        };

    }



    public static function adaptMysqlSeedSqlForPgsql(

        string $sql,

        string $targetPrefix,

        string $filePrefix = self::SQL_FILE_DEFAULT_PREFIX

    ): string {

        $sql = self::remapSqlFilePrefix($sql, $targetPrefix, $filePrefix);

        $lines = preg_split('/\r\n|\r|\n/', $sql) ?: [];

        $out = [];

        foreach ($lines as $line) {

            if (preg_match('/^\s*SET (NAMES|FOREIGN_KEY_CHECKS)/i', $line)) {

                continue;

            }

            $converted = preg_replace('/`([a-zA-Z0-9_]+)`/', '"$1"', $line);

            $converted = $converted ?? $line;

            if (preg_match('/^\s*INSERT INTO/i', $converted) && preg_match('/\(\s*"?id"?\b/i', $converted)) {

                $converted = preg_replace('/\)\s*VALUES/i', ') OVERRIDING SYSTEM VALUE VALUES', $converted) ?? $converted;

            }

            $out[] = $converted;

        }



        return trim(implode("\n", $out));

    }



    public static function adaptMysqlSeedSqlForSqlite(

        string $sql,

        string $targetPrefix,

        string $filePrefix = self::SQL_FILE_DEFAULT_PREFIX

    ): string {

        $sql = self::remapSqlFilePrefix($sql, $targetPrefix, $filePrefix);

        $lines = preg_split('/\r\n|\r|\n/', $sql) ?: [];

        $out = [];

        foreach ($lines as $line) {

            if (preg_match('/^\s*SET (NAMES|FOREIGN_KEY_CHECKS)/i', $line)) {

                continue;

            }

            $converted = preg_replace('/`([a-zA-Z0-9_]+)`/', '"$1"', $line);

            $out[] = $converted ?? $line;

        }



        return trim(implode("\n", $out));

    }



    public static function adaptMysqlSeedSqlForOci(

        string $sql,

        string $targetPrefix,

        string $filePrefix = self::SQL_FILE_DEFAULT_PREFIX

    ): string {

        return self::adaptMysqlSeedSqlForSqlite($sql, $targetPrefix, $filePrefix);

    }



    /**

     * Kurulum SQL dosyalarında #__ ve depodaki sabit önek (varsayılan esh_) hedef öneke çevrilir.

     * Yalnızca tablo/kısıt tanımlayıcılarında değişim yapılır; INSERT veri alanlarına dokunulmaz.

     */

    public static function remapSqlFilePrefix(

        string $sql,

        string $targetPrefix,

        string $filePrefix = self::SQL_FILE_DEFAULT_PREFIX

    ): string {

        if (!self::isValidTablePrefix($targetPrefix)) {

            throw new \InvalidArgumentException('Geçersiz tablo öneki: ' . $targetPrefix);

        }

        $sql = self::replacePrefix($sql, '#__', $targetPrefix);

        if ($filePrefix === $targetPrefix || !self::isValidTablePrefix($filePrefix)) {

            return $sql;

        }

        $sql = str_replace('`' . $filePrefix, '`' . $targetPrefix, $sql);

        $sql = str_replace('"' . $filePrefix, '"' . $targetPrefix, $sql);

        $sql = str_replace("N'[dbo].[" . $filePrefix, "N'[dbo].[" . $targetPrefix, $sql);

        $sql = str_replace('[dbo].[' . $filePrefix, '[dbo].[' . $targetPrefix, $sql);

        $sql = str_replace('[' . $filePrefix, '[' . $targetPrefix, $sql);

        $replaced = preg_replace(

            '/\b([A-Z]{2,12}_)' . preg_quote($filePrefix, '/') . '/',

            '$1' . $targetPrefix,

            $sql

        );

        return $replaced ?? $sql;

    }



    /**

     * SQL dosyasını tek tek çalıştırılabilir ifadelere böler (yorumlar ve string içi ; korunur).

     *

     * @return list<string>

     */

    public static function splitSqlStatements(string $sql, string $driver = 'mysql'): array

    {

        if (self::normalizeDbDriver($driver) === 'sqlsrv') {

            return self::splitSqlServerBatches($sql);

        }

        if (self::normalizeDbDriver($driver) === 'oci') {

            return self::splitOciStatements($sql);

        }



        $statements = [];

        $buffer = '';

        $len = strlen($sql);

        $inString = false;

        $stringChar = '';

        $inLineComment = false;

        $inBlockComment = false;



        for ($i = 0; $i < $len; $i++) {

            $ch = $sql[$i];

            $next = ($i + 1 < $len) ? $sql[$i + 1] : '';



            if ($inLineComment) {

                if ($ch === "\n") {

                    $inLineComment = false;

                    $buffer .= $ch;

                }

                continue;

            }



            if ($inBlockComment) {

                if ($ch === '*' && $next === '/') {

                    $inBlockComment = false;

                    $i++;

                }

                continue;

            }



            if (!$inString && $ch === '-' && $next === '-') {

                $inLineComment = true;

                $i++;

                continue;

            }



            if (!$inString && $ch === '#') {

                $inLineComment = true;

                continue;

            }



            if (!$inString && $ch === '/' && $next === '*') {

                $inBlockComment = true;

                $i++;

                continue;

            }



            if (!$inString && ($ch === "'" || $ch === '"')) {

                $inString = true;

                $stringChar = $ch;

                $buffer .= $ch;

                continue;

            }



            if ($inString) {

                $buffer .= $ch;

                if ($ch === '\\' && $next !== '') {

                    $buffer .= $next;

                    $i++;

                    continue;

                }

                if ($ch === $stringChar) {

                    if ($stringChar === "'" && $next === "'") {

                        $buffer .= $next;

                        $i++;

                        continue;

                    }

                    $inString = false;

                    $stringChar = '';

                }

                continue;

            }



            if ($ch === ';') {

                $stmt = trim($buffer);

                if ($stmt !== '') {

                    $statements[] = $stmt;

                }

                $buffer = '';

                continue;

            }



            $buffer .= $ch;

        }



        $stmt = trim($buffer);

        if ($stmt !== '') {

            $statements[] = $stmt;

        }



        return $statements;

    }



    /**

     * SQL Server GO batch ayırıcı.

     *

     * @return list<string>

     */

    public static function splitSqlServerBatches(string $sql): array

    {

        $parts = preg_split('/^\s*GO\s*$/mi', $sql) ?: [];

        $batches = [];

        foreach ($parts as $part) {

            $part = trim($part);

            if ($part !== '') {

                $batches[] = $part;

            }

        }



        return $batches;

    }



    /**

     * Oracle kurulum SQL — ifadeler arasında boş satır ile ayrılır.

     *

     * @return list<string>

     */

    public static function splitOciStatements(string $sql): array

    {

        $parts = preg_split('/;\s*\n\s*\n/', trim($sql)) ?: [];

        $statements = [];

        foreach ($parts as $part) {

            $part = trim($part);

            if ($part === '' || str_starts_with($part, '--')) {

                continue;

            }

            $statements[] = rtrim($part, ';') . ';';

        }



        return $statements;

    }



    /**

     * IN (?,?,?) için placeholder dizisi. Boş dizi için whereInClause() kullanın.

     *

     * @param list<mixed> $values

     */

    public static function whereInPlaceholders(array $values): string

    {

        if ($values === []) {

            return '';

        }



        return implode(',', array_fill(0, count($values), '?'));

    }



    /**

     * Boş dizi için eşleşmeyen koşul (0=1); aksi halde placeholder + parametreler.

     *

     * @param list<mixed> $values

     * @return array{0: string, 1: list<mixed>}

     */

    public static function whereInClause(array $values): array

    {

        if ($values === []) {

            return ['0=1', []];

        }



        return [self::whereInPlaceholders($values), array_values($values)];

    }



    /**

     * @param array<string, mixed> $data

     * @return array{sql: string, params: list<mixed>}|null

     */

    public static function buildInsertSql(string $tableName, array $data): ?array

    {

        $fields = [];

        $values = [];

        foreach ($data as $k => $v) {

            if ($k === '' || $k[0] === '_') {

                continue;

            }

            if (!self::isValidColumnIdentifier((string) $k)) {

                return null;

            }

            $fields[] = (string) $k;

            $values[] = $v;

        }

        if ($fields === []) {

            return null;

        }

        $quotedFields = array_map([self::class, 'identifier'], $fields);

        $sql = 'INSERT INTO ' . self::identifier($tableName) . ' (' . implode(',', $quotedFields) . ') VALUES ('

            . implode(',', array_fill(0, count($fields), '?')) . ')';



        return ['sql' => $sql, 'params' => $values];

    }



    /**

     * @param array<string, mixed> $data

     * @return array{sql: string, params: list<mixed>}|null

     */

    public static function buildUpdateSql(string $tableName, array $data, string $whereSql): ?array

    {

        $sets = [];

        $values = [];

        foreach ($data as $k => $v) {

            if ($k === '' || $k[0] === '_') {

                continue;

            }

            if (!self::isValidColumnIdentifier((string) $k)) {

                return null;

            }

            $sets[] = self::identifier((string) $k) . ' = ?';

            $values[] = $v;

        }

        if ($sets === []) {

            return null;

        }

        $whereSql = trim($whereSql);

        if ($whereSql === '') {

            return null;

        }

        $sql = 'UPDATE ' . self::identifier($tableName) . ' SET ' . implode(',', $sets) . ' WHERE ' . $whereSql;



        return ['sql' => $sql, 'params' => $values];

    }



    public static function buildPdoDsn(string $driver): string

    {

        $driver = self::normalizeDbDriver($driver);

        if ($driver === 'sqlsrv') {

            $server = (string) DB_HOST;

            if (defined('DB_PORT') && (string) DB_PORT !== '' && strpos($server, ',') === false && strpos($server, '\\') === false) {

                $server .= ',' . (string) DB_PORT;

            }



            return 'sqlsrv:Server=' . $server . ';Database=' . DB_NAME . ';TrustServerCertificate=1';

        }

        if ($driver === 'pgsql') {

            $port = defined('DB_PORT') && (string) DB_PORT !== '' ? ';port=' . (int) DB_PORT : '';



            return 'pgsql:host=' . DB_HOST . $port . ';dbname=' . DB_NAME;

        }

        if ($driver === 'sqlite') {

            return 'sqlite:' . self::resolveSqlitePath((string) DB_HOST, (string) DB_NAME);

        }

        if ($driver === 'oci') {

            $port = defined('DB_PORT') && (string) DB_PORT !== '' ? (int) DB_PORT : 1521;



            return 'oci:dbname=//' . DB_HOST . ':' . $port . '/' . DB_NAME;

        }

        $port = defined('DB_PORT') && (string) DB_PORT !== '' ? ';port=' . (int) DB_PORT : '';



        return 'mysql:host=' . DB_HOST . $port . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    }



    /**

     * @return list<string>

     */

    public static function mysqlInitStatements(): array

    {

        return [

            "SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))",

            'SET NAMES utf8mb4 COLLATE utf8mb4_turkish_ci',

        ];

    }

}

