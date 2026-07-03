<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$mysqlDsn = getenv('ESH_MYSQL_DSN') ?: 'mysql:host=localhost;dbname=esh4;charset=utf8mb4';
$mysqlUser = getenv('ESH_MYSQL_USER') ?: 'root';
$mysqlPass = getenv('ESH_MYSQL_PASS') ?: '';
$sqlsrvDsn = getenv('ESH_MSSQL_DSN') ?: 'sqlsrv:Server=localhost\\SQLEXPRESS;Database=esh4;TrustServerCertificate=1';
$sqlsrvUser = getenv('ESH_MSSQL_USER') ?: 'esh_app';
$sqlsrvPass = getenv('ESH_MSSQL_PASS') ?: '';

$tables = [
    'esh_kurumlar',
    'esh_users',
    'esh_hastalar',
    'esh_izlemler',
];

try {
    $src = new PDO($mysqlDsn, $mysqlUser, $mysqlPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $dst = new PDO($sqlsrvDsn, $sqlsrvUser, $sqlsrvPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) {
    fwrite(STDERR, "Connection error: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

foreach ($tables as $table) {
    echo "Migrating {$table}..." . PHP_EOL;
    $rows = $src->query('SELECT * FROM ' . $table)->fetchAll(PDO::FETCH_ASSOC);
    if ($rows === []) {
        echo "  - skipped (empty)" . PHP_EOL;
        continue;
    }
    $cols = array_keys($rows[0]);
    $colList = implode(',', array_map(static fn (string $c): string => '[' . $c . ']', $cols));
    $paramList = implode(',', array_fill(0, count($cols), '?'));
    $insertSql = 'INSERT INTO ' . $table . ' (' . $colList . ') VALUES (' . $paramList . ')';
    $stmt = $dst->prepare($insertSql);

    $dst->beginTransaction();
    try {
        $dst->exec('SET IDENTITY_INSERT ' . $table . ' ON');
    } catch (Throwable) {
        // Table may not have identity column.
    }

    try {
        foreach ($rows as $row) {
            $stmt->execute(array_values($row));
        }
        try {
            $dst->exec('SET IDENTITY_INSERT ' . $table . ' OFF');
        } catch (Throwable) {
        }
        $dst->commit();
        echo "  - copied " . count($rows) . " rows" . PHP_EOL;
    } catch (Throwable $e) {
        $dst->rollBack();
        fwrite(STDERR, "  - failed: " . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}

echo "Migration done." . PHP_EOL;
