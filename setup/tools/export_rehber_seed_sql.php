<?php
declare(strict_types=1);

/**
 * esh_rehber_* tablolarını INSERT SQL olarak dışa aktarır.
 *
 *   php tools/export_rehber_seed_sql.php
 *   php tools/export_rehber_seed_sql.php --out=database/migrate_esh_rehber_ilac_seed.sql
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$projectRoot = realpath(dirname(__DIR__));
if ($projectRoot === false) {
    fwrite(STDERR, "Project root not found.\n");
    exit(1);
}

require_once $projectRoot . '/config/config.php';

$out = $projectRoot . '/database/migrate_esh_rehber_ilac_seed.sql';
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--out=')) {
        $out = $projectRoot . '/' . ltrim(substr($arg, 6), '/\\');
    }
}

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
if (defined('DB_PORT') && (string) DB_PORT !== '') {
    $dsn .= ';port=' . DB_PORT;
}

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, 'DB bağlantısı: ' . $e->getMessage() . "\n");
    exit(1);
}

$tables = [
    'esh_rehber_etken' => ['id', 'ad', 'ad_normalized', 'source_site', 'source_key', 'scraped_at'],
    'esh_rehber_ilac' => ['id', 'etken_id', 'ad', 'firma', 'recete_turu', 'source_site', 'source_url', 'source_key', 'scraped_at'],
    'esh_rehber_import_log' => ['id', 'source_site', 'started_at', 'finished_at', 'etken_count', 'ilac_count', 'error_summary', 'options_json'],
];

function sqlQuote(?string $v): string
{
    if ($v === null) {
        return 'NULL';
    }
    return "'" . str_replace(["\\", "'", "\0", "\n", "\r"], ["\\\\", "''", '', '\\n', '\\r'], $v) . "'";
}

$lines = [
    '-- =============================================================================',
    '-- ESH 3.2.1 — ilaç rehberi seed verisi (esh_rehber_*)',
    '-- Üretim: php tools/export_rehber_seed_sql.php',
    '-- Önce: database/migrate_esh_rehber_ilac_create.sql',
    '-- Sonra: mysql -u KULLANICI -p VERITABANI < database/migrate_esh_rehber_ilac_seed.sql',
    '-- ON DUPLICATE KEY UPDATE — mevcut satırlar güncellenir (idempotent).',
    '-- =============================================================================',
    '',
    'SET NAMES utf8mb4;',
    'SET FOREIGN_KEY_CHECKS = 0;',
    '',
];

$totalRows = 0;

foreach ($tables as $table => $cols) {
    $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    fwrite(STDERR, "{$table}: {$count} satır\n");
    $totalRows += $count;

    if ($count === 0) {
        $lines[] = "-- {$table}: (boş — export anında kayıt yok)";
        $lines[] = '';
        continue;
    }

    $colList = implode(', ', array_map(static fn (string $c) => '`' . $c . '`', $cols));
    $updateParts = [];
    foreach ($cols as $c) {
        if ($c === 'id') {
            continue;
        }
        $updateParts[] = '`' . $c . '` = VALUES(`' . $c . '`)';
    }
    $onDup = $updateParts !== [] ? ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updateParts) : '';

    $lines[] = "-- {$table} ({$count} satır)";
    $stmt = $pdo->query("SELECT * FROM `{$table}` ORDER BY `id`");
    $batch = [];
    $batchSize = 100;

    while ($row = $stmt->fetch()) {
        $vals = [];
        foreach ($cols as $c) {
            $v = $row[$c] ?? null;
            if ($v === null) {
                $vals[] = 'NULL';
            } elseif (in_array($c, ['id', 'etken_id', 'etken_count', 'ilac_count'], true)) {
                $vals[] = (string) (int) $v;
            } else {
                $vals[] = sqlQuote((string) $v);
            }
        }
        $batch[] = '(' . implode(', ', $vals) . ')';

        if (count($batch) >= $batchSize) {
            $lines[] = "INSERT INTO `{$table}` ({$colList}) VALUES";
            $lines[] = implode(",\n", $batch) . $onDup . ';';
            $lines[] = '';
            $batch = [];
        }
    }

    if ($batch !== []) {
        $lines[] = "INSERT INTO `{$table}` ({$colList}) VALUES";
        $lines[] = implode(",\n", $batch) . $onDup . ';';
        $lines[] = '';
    }
}

$lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
$lines[] = '';

if ($totalRows === 0) {
    fwrite(STDERR, "Uyarı: Tüm tablolar boş; seed dosyası yalnızca başlık içerir.\n");
}

$content = implode("\n", $lines) . "\n";
if (!is_dir(dirname($out))) {
    mkdir(dirname($out), 0755, true);
}
file_put_contents($out, $content);
fwrite(STDERR, "Yazıldı: {$out} (" . strlen($content) . " bayt, {$totalRows} satır)\n");
