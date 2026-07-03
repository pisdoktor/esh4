<?php
/**
 * schema.sql → PostgreSQL / SQLite / Oracle şeması.
 * Kullanım: php tools/build_schema_dialect.php [pgsql|sqlite|oci|all]
 */
declare(strict_types=1);

require_once __DIR__ . '/SchemaDialectConverter.php';

$root = dirname(__DIR__);
$in = $root . '/database/schemas/schema.sql';
$target = $argv[1] ?? 'all';

$map = [
    'pgsql' => $root . '/database/schemas/schema.pgsql.sql',
    'sqlite' => $root . '/database/schemas/schema.sqlite.sql',
    'oci' => $root . '/database/schemas/schema.oci.sql',
];

if (!is_readable($in)) {
    fwrite(STDERR, "Okunamadı: $in\n");
    exit(1);
}

$mysql = file_get_contents($in);
if ($mysql === false) {
    fwrite(STDERR, "schema.sql okunamadı.\n");
    exit(1);
}

$dialects = $target === 'all' ? array_keys($map) : [$target];
foreach ($dialects as $dialect) {
    if (!isset($map[$dialect])) {
        fwrite(STDERR, "Geçersiz dialect: $dialect (pgsql|sqlite|oci|all)\n");
        exit(1);
    }
    $converter = new SchemaDialectConverter($dialect);
    $content = $converter->convert($mysql);
    $out = $map[$dialect];
    if (file_put_contents($out, $content) === false) {
        fwrite(STDERR, "Yazılamadı: $out\n");
        exit(1);
    }
    $tables = preg_match_all('/CREATE TABLE/i', $content);
    echo "Wrote $out (CREATE TABLE: $tables)\n";
}
