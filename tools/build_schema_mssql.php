<?php
/**
 * schema.sql → schema.mssql.sql dönüştürücü (kurulum / SQL Server).
 * Kullanım: php tools/build_schema_mssql.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$in = $root . '/database/schemas/schema.sql';
$out = $root . '/database/schemas/schema.mssql.sql';

if (!is_readable($in)) {
    fwrite(STDERR, "Okunamadı: $in\n");
    exit(1);
}

$mysql = file_get_contents($in);
if ($mysql === false) {
    fwrite(STDERR, "schema.sql okunamadı.\n");
    exit(1);
}

function mssqlBracket(string $name): string
{
    return '[' . $name . ']';
}

function mssqlConvertType(string $def): string
{
    $def = preg_replace('/\s+COMMENT\s+\'[^\']*\'/i', '', $def) ?? $def;
    $def = preg_replace('/\bINT UNSIGNED\b/i', 'INT', $def) ?? $def;
    $def = preg_replace('/\bBIGINT UNSIGNED\b/i', 'BIGINT', $def) ?? $def;
    $def = preg_replace('/\bSMALLINT UNSIGNED\b/i', 'SMALLINT', $def) ?? $def;
    $def = preg_replace('/\bTINYINT\s+UNSIGNED\b/i', 'TINYINT', $def) ?? $def;
    $def = preg_replace('/\bTINYINT\(\d+\)\b/i', 'TINYINT', $def) ?? $def;
    $def = preg_replace('/\bLONGTEXT\b/i', 'NVARCHAR(MAX)', $def) ?? $def;
    $def = preg_replace('/\bTEXT\b/i', 'NVARCHAR(MAX)', $def) ?? $def;
    $def = preg_replace('/\bVARCHAR\s*\(\s*(\d+)\s*\)/i', 'NVARCHAR($1)', $def) ?? $def;
    $def = preg_replace('/\bVARCHAR\b/i', 'NVARCHAR(255)', $def) ?? $def;
    $def = preg_replace('/\bCHAR\((\d+)\)\b/i', 'NCHAR($1)', $def) ?? $def;
    $def = preg_replace('/\bDATETIME\b/i', 'DATETIME2', $def) ?? $def;
    $def = preg_replace('/\bTIMESTAMP\b/i', 'DATETIME2', $def) ?? $def;
    $def = preg_replace('/\bENUM\s*\([^)]*\)/i', 'NVARCHAR(32)', $def) ?? $def;
    $def = preg_replace('/\bAUTO_INCREMENT\b/i', 'IDENTITY(1,1)', $def) ?? $def;
    $def = preg_replace('/\bDEFAULT CURRENT_TIMESTAMP\b/i', 'DEFAULT SYSDATETIME()', $def) ?? $def;
    $def = preg_replace('/\s+ON UPDATE CURRENT_TIMESTAMP\b/i', '', $def) ?? $def;
    $def = preg_replace('/`([^`]+)`(\(\d+\))?/', '[$1]', $def) ?? $def;

    return trim($def);
}

function mssqlConvertCreateTable(string $block): string
{
    if (!preg_match('/CREATE TABLE IF NOT EXISTS `(\w+)`\s*\(/i', $block, $m)) {
        return '';
    }
    $table = $m[1];
    $lines = preg_split('/\r\n|\r|\n/', $block);
    $body = [];
    $started = false;
    foreach ($lines as $line) {
        $trim = trim($line);
        if (!$started) {
            if (preg_match('/CREATE TABLE IF NOT EXISTS/i', $trim)) {
                $started = true;
            }
            continue;
        }
        if ($trim === '' || str_starts_with($trim, '--')) {
            continue;
        }
        if (preg_match('/^\)\s*ENGINE=/i', $trim)) {
            break;
        }
        $hasComma = str_ends_with(rtrim($trim), ',');
        $work = rtrim($trim, ',');
        if (preg_match('/^PRIMARY KEY/i', $work)) {
            $work = preg_replace('/`([^`]+)`/', '[$1]', $work);
            $body[] = '  ' . $work . ($hasComma ? ',' : '');
            continue;
        }
        if (preg_match('/^UNIQUE KEY\s+`([^`]+)`\s+\((.+)\)$/i', $work, $km)) {
            $cols = preg_replace('/`([^`]+)`(\(\d+\))?/', '[$1]', $km[2]);
            $body[] = '  CONSTRAINT [' . $km[1] . '] UNIQUE (' . $cols . ')' . ($hasComma ? ',' : '');
            continue;
        }
        if (preg_match('/^KEY\s+`([^`]+)`\s+\((.+)\)$/i', $work, $km)) {
            $cols = preg_replace('/`([^`]+)`(\(\d+\))?/', '[$1]', $km[2]);
            $body[] = '  INDEX [' . $km[1] . '] (' . $cols . ')' . ($hasComma ? ',' : '');
            continue;
        }
        if (preg_match('/^`([^`]+)`\s+(.+)$/', $work, $cm)) {
            $body[] = '  ' . mssqlBracket($cm[1]) . ' ' . mssqlConvertType($cm[2]) . ($hasComma ? ',' : '');
        }
    }
    if ($body !== []) {
        $last = count($body) - 1;
        $body[$last] = rtrim($body[$last], ',');
    }

    $ddl = "IF OBJECT_ID(N'[dbo]." . mssqlBracket($table) . "', N'U') IS NULL\nBEGIN\n";
    $ddl .= '    CREATE TABLE [dbo].' . mssqlBracket($table) . " (\n";
    $ddl .= implode("\n", $body) . "\n";
    $ddl .= "    );\nEND\nGO\n";

    return $ddl;
}

function mssqlConvertInsert(string $line): string
{
    if (!preg_match('/^INSERT INTO/i', trim($line))) {
        return '';
    }
    $sql = preg_replace('/`(\w+)`/', '[$1]', trim($line));
    if (!preg_match('/^INSERT INTO \[(\w+)\][^(]*\(\s*\[?id\]?/i', $sql, $m)) {
        return $sql . "\nGO\n";
    }
    $table = $m[1];

    return "SET IDENTITY_INSERT [dbo].[$table] ON;\n"
        . $sql . "\n"
        . "SET IDENTITY_INSERT [dbo].[$table] OFF;\nGO\n";
}

$outParts = [];
$outParts[] = "-- =============================================================================";
$outParts[] = "-- ESH Panel — SQL Server şeması (schema.sql'den üretildi)";
$outParts[] = "-- Üretim: php tools/build_schema_mssql.php";
$outParts[] = "-- SQL Server 2019+ · Turkish_100_CI_AS";
$outParts[] = "-- Kurulum sihirbazı bu dosyayı db_driver=sqlsrv ile çalıştırır.";
$outParts[] = "-- =============================================================================";
$outParts[] = '';
$outParts[] = 'SET NOCOUNT ON;';
$outParts[] = 'GO';
$outParts[] = '';

$lines = preg_split('/\r\n|\r|\n/', $mysql);
$buffer = '';
$inCreate = false;

foreach ($lines as $line) {
    $trim = trim($line);
    if ($trim === '' || str_starts_with($trim, '--')) {
        continue;
    }
    if (preg_match('/^SET (NAMES|FOREIGN_KEY_CHECKS)/i', $trim)) {
        continue;
    }
    if (preg_match('/^DROP TABLE IF EXISTS/i', $trim)) {
        if (preg_match('/`(\w+)`/', $trim, $dm)) {
            $t = $dm[1];
            $outParts[] = "IF OBJECT_ID(N'[dbo]." . mssqlBracket($t) . "', N'U') IS NOT NULL DROP TABLE [dbo]." . mssqlBracket($t) . ';';
            $outParts[] = 'GO';
            $outParts[] = '';
        }
        continue;
    }
    if (preg_match('/^CREATE TABLE IF NOT EXISTS/i', $trim)) {
        $inCreate = true;
        $buffer = $line . "\n";
        continue;
    }
    if ($inCreate) {
        $buffer .= $line . "\n";
        if (preg_match('/^\)\s*ENGINE=/i', $trim)) {
            $converted = mssqlConvertCreateTable($buffer);
            if ($converted !== '') {
                $outParts[] = $converted;
                $outParts[] = '';
            }
            $buffer = '';
            $inCreate = false;
        }
        continue;
    }
    if (preg_match('/^INSERT INTO/i', $trim)) {
        $outParts[] = mssqlConvertInsert($trim);
        $outParts[] = '';
    }
}

$content = implode("\n", $outParts) . "\n";
if (file_put_contents($out, $content) === false) {
    fwrite(STDERR, "Yazılamadı: $out\n");
    exit(1);
}

$goCount = substr_count(strtoupper($content), "\nGO\n");
$tableCount = preg_match_all('/CREATE TABLE/i', $content);
echo "Wrote $out\n";
echo "  CREATE TABLE blocks: $tableCount\n";
echo "  GO batches: $goCount\n";
