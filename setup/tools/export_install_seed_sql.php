<?php
/**
 * Kurulum seed SQL dosyalarını canlı DB'den üretir.
 * Kullanım: php tools/export_install_seed_sql.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

$outDir = dirname(__DIR__) . '/database/seed';
if (!is_dir($outDir) && !mkdir($outDir, 0775, true)) {
    fwrite(STDERR, "Dizin oluşturulamadı: $outDir\n");
    exit(1);
}

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function sqlQuote(PDO $pdo, mixed $value): string
{
    if ($value === null) {
        return 'NULL';
    }

    return $pdo->quote((string) $value);
}

/** Fiziksel tablo adını kurulum placeholder'ına çevirir (esh_guvence → #__guvence). */
function tableNameForSeedSql(string $physicalTable, string $dbPrefix): string
{
    if ($dbPrefix !== '' && str_starts_with($physicalTable, $dbPrefix)) {
        return '#__' . substr($physicalTable, strlen($dbPrefix));
    }

    return $physicalTable;
}

function writeSeedFile(PDO $pdo, string $path, string $title, string $table, array $columns, string $orderBy = '', string $dbPrefix = 'esh_'): int
{
    $colList = implode(', ', array_map(static fn(string $c): string => '`' . $c . '`', $columns));
    $sqlTable = tableNameForSeedSql($table, $dbPrefix);
    $sql = 'SELECT ' . $colList . ' FROM `' . $table . '`';
    if ($orderBy !== '') {
        $sql .= ' ORDER BY ' . $orderBy;
    }

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $fh = fopen($path, 'wb');
    if ($fh === false) {
        throw new RuntimeException('Yazılamadı: ' . $path);
    }

    fwrite($fh, "-- =============================================================================\n");
    fwrite($fh, "-- ESH kurulum seed — $title\n");
    fwrite($fh, "-- Tablo: `$sqlTable` (kaynak: `$table`) | Satır: " . count($rows) . "\n");
    fwrite($fh, "-- Kurulum sırasında Installer otomatik çalıştırır.\n");
    fwrite($fh, "-- Elle: mysql -u KULLANICI -p VERITABANI < database/seed/" . basename($path) . "\n");
    fwrite($fh, "-- =============================================================================\n\n");
    fwrite($fh, "SET NAMES utf8mb4;\n");
    fwrite($fh, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

    if ($rows === []) {
        fwrite($fh, "-- (boş tablo — seed satırı yok)\n");
    } else {
        $batchSize = 200;
        $total = count($rows);
        for ($offset = 0; $offset < $total; $offset += $batchSize) {
            $batch = array_slice($rows, $offset, $batchSize);
            fwrite($fh, "INSERT INTO `$sqlTable` ($colList) VALUES\n");
            $lines = [];
            foreach ($batch as $row) {
                $vals = [];
                foreach ($columns as $col) {
                    $vals[] = sqlQuote($pdo, $row[$col] ?? null);
                }
                $lines[] = '(' . implode(', ', $vals) . ')';
            }
            fwrite($fh, implode(",\n", $lines));
            fwrite($fh, ";\n\n");
        }
    }

    fwrite($fh, "SET FOREIGN_KEY_CHECKS = 1;\n");
    fclose($fh);

    return count($rows);
}

$exports = [
    [
        'file' => 'seed_esh_guvence.sql',
        'title' => 'Güvence / ödeme türleri',
        'table' => 'esh_guvence',
        'columns' => ['id', 'guvenceadi'],
        'order' => 'id',
    ],
    [
        'file' => 'seed_esh_hastalikcat.sql',
        'title' => 'Hastalık kategorileri (ICD üst katman)',
        'table' => 'esh_hastalikcat',
        'columns' => ['id', 'name', 'icd_range'],
        'order' => 'id',
    ],
    [
        'file' => 'seed_esh_hastaliklar.sql',
        'title' => 'Hastalık kütüphanesi (ICD tanı listesi)',
        'table' => 'esh_hastaliklar',
        'columns' => ['id', 'kurum_id', 'cat', 'hastalikadi', 'icd', 'parent_icd', 'seviye'],
        'order' => 'id',
    ],
    [
        'file' => 'seed_esh_branslar.sql',
        'title' => 'Tıp branşları (platform kataloğu)',
        'table' => 'esh_branslar',
        'columns' => ['id', 'kurum_id', 'bransadi', 'hasta_kotasi'],
        'order' => 'id',
    ],
    [
        'file' => 'seed_esh_islemler.sql',
        'title' => 'Evde sağlık işlem referans listesi',
        'table' => 'esh_islemler',
        'columns' => ['id', 'kurum_id', 'islemadi'],
        'order' => 'id',
    ],
    [
        'file' => 'seed_esh_istekler.sql',
        'title' => 'Konsültasyon istek türleri',
        'table' => 'esh_istekler',
        'columns' => ['id', 'kurum_id', 'istek_adi'],
        'order' => 'id',
    ],
    [
        'file' => 'seed_esh_adrestablosu.sql',
        'title' => 'Adres ağacı (ilçe → mahalle → sokak → kapı)',
        'table' => 'esh_adrestablosu',
        'columns' => ['id', 'adi', 'ust_id', 'tip', 'coords', 'has_coords'],
        'order' => 'tip, ust_id, id',
    ],
];

echo "Exporting install seeds to $outDir\n";
$only = isset($argv[1]) ? trim((string) $argv[1]) : '';
foreach ($exports as $spec) {
    if ($only !== '' && $spec['file'] !== $only && $spec['table'] !== $only) {
        continue;
    }
    $path = $outDir . '/' . $spec['file'];
    $count = writeSeedFile(
        $pdo,
        $path,
        $spec['title'],
        $spec['table'],
        $spec['columns'],
        $spec['order'],
        (string) DB_PREFIX
    );
    $size = filesize($path);
    echo sprintf("  %s — %d rows (%s KB)\n", $spec['file'], $count, number_format($size / 1024, 1));
}

$manifest = <<<'PHP'
<?php
declare(strict_types=1);

/**
 * Sıfırdan kurulumda schema.sql sonrası içe aktarılacak seed dosyaları (sıralı).
 */
return [
    'seed_esh_rbac.sql',
    'seed_esh_unvan_roles.sql',
    'seed_esh_guvence.sql',
    'seed_esh_hastalikcat.sql',
    'seed_esh_hastaliklar.sql',
    'seed_esh_branslar.sql',
    'seed_esh_islemler.sql',
    'seed_esh_istekler.sql',
    'seed_esh_adrestablosu.sql',
];

PHP;
file_put_contents($outDir . '/install_seeds.php', $manifest);
echo "Wrote database/seed/install_seeds.php\n";
