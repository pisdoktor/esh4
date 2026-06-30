<?php
declare(strict_types=1);

/**
 * schema.sql → hedef veritabanı şeması dönüştürücü.
 */
final class SchemaDialectConverter
{
    /** @var list<string> */
    private array $out = [];

    /** @var list<string> */
    private array $pendingIndexes = [];

    private string $dialect;

    public function __construct(string $dialect)
    {
        $d = strtolower($dialect);
        if (!in_array($d, ['pgsql', 'sqlite', 'oci'], true)) {
            throw new InvalidArgumentException('Desteklenen dialect: pgsql, sqlite, oci');
        }
        $this->dialect = $d;
    }

    public function convert(string $mysql): string
    {
        $this->out = [];
        $this->pendingIndexes = [];

        $header = match ($this->dialect) {
            'pgsql' => [
                '-- ESH Panel — PostgreSQL şeması (schema.sql\'den üretildi)',
                '-- Üretim: php tools/build_schema_dialect.php pgsql',
                '-- Kurulum: db_driver=pgsql',
            ],
            'sqlite' => [
                '-- ESH Panel — SQLite şeması (schema.sql\'den üretildi)',
                '-- Üretim: php tools/build_schema_dialect.php sqlite',
                '-- Kurulum: db_driver=sqlite',
            ],
            'oci' => [
                '-- ESH Panel — Oracle şeması (schema.sql\'den üretildi)',
                '-- Üretim: php tools/build_schema_dialect.php oci',
                '-- Kurulum: db_driver=oci',
            ],
        };
        foreach ($header as $line) {
            $this->out[] = $line;
        }
        $this->out[] = '-- =============================================================================';
        $this->out[] = '';

        if ($this->dialect === 'sqlite') {
            $this->out[] = 'PRAGMA foreign_keys = OFF;';
            $this->out[] = '';
        }

        $lines = preg_split('/\r\n|\r|\n/', $mysql) ?: [];
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
            if (preg_match('/^DROP TABLE IF EXISTS `(\w+)`/i', $trim, $dm)) {
                $this->emitDropTable($dm[1]);
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
                    $this->convertCreateTable($buffer);
                    $buffer = '';
                    $inCreate = false;
                }
                continue;
            }
            if (preg_match('/^INSERT INTO/i', $trim)) {
                $this->out[] = $this->convertInsert($trim);
                $this->out[] = '';
            }
        }

        if ($this->dialect === 'sqlite') {
            $this->out[] = 'PRAGMA foreign_keys = ON;';
            $this->out[] = '';
        }

        return implode("\n", $this->out) . "\n";
    }

    private function emitDropTable(string $table): void
    {
        $q = $this->quoteId($table);
        if ($this->dialect === 'oci') {
            $this->out[] = 'BEGIN EXECUTE IMMEDIATE \'DROP TABLE ' . $q . ' CASCADE CONSTRAINTS\'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;';
        } else {
            $this->out[] = 'DROP TABLE IF EXISTS ' . $q . ($this->dialect === 'pgsql' ? ' CASCADE' : '') . ';';
        }
        $this->out[] = '';
    }

    private function convertCreateTable(string $block): void
    {
        if (!preg_match('/CREATE TABLE IF NOT EXISTS `(\w+)`\s*\(/i', $block, $m)) {
            return;
        }
        $table = $m[1];
        $qTable = $this->quoteId($table);
        $lines = preg_split('/\r\n|\r|\n/', $block) ?: [];
        $body = [];
        $started = false;
        $sqliteIdAuto = false;

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
                if ($this->dialect === 'sqlite' && $sqliteIdAuto && preg_match('/^PRIMARY KEY\s*\(\s*`id`\s*\)/i', $work)) {
                    continue;
                }
                $body[] = '  ' . $this->convertKeyList($work) . ($hasComma ? ',' : '');
                continue;
            }
            if (preg_match('/^UNIQUE KEY\s+`([^`]+)`\s+\((.+)\)$/i', $work, $km)) {
                $cols = $this->convertKeyList($km[2]);
                if ($this->dialect === 'oci') {
                    $body[] = '  CONSTRAINT ' . $this->quoteId($km[1]) . ' UNIQUE (' . $cols . ')' . ($hasComma ? ',' : '');
                } else {
                    $body[] = '  CONSTRAINT ' . $this->quoteId($km[1]) . ' UNIQUE (' . $cols . ')' . ($hasComma ? ',' : '');
                }
                continue;
            }
            if (preg_match('/^KEY\s+`([^`]+)`\s+\((.+)\)$/i', $work, $km)) {
                $cols = $this->convertKeyList($km[2]);
                $this->pendingIndexes[] = 'CREATE INDEX IF NOT EXISTS '
                    . $this->quoteId($km[1]) . ' ON ' . $qTable . ' (' . $cols . ');';
                continue;
            }
            if (preg_match('/^`([^`]+)`\s+(.+)$/', $work, $cm)) {
                $col = $cm[1];
                $type = $this->convertColumnType($cm[2], $col);
                if ($this->dialect === 'sqlite' && $col === 'id' && stripos($cm[2], 'AUTO_INCREMENT') !== false) {
                    $sqliteIdAuto = true;
                }
                $body[] = '  ' . $this->quoteId($col) . ' ' . $type . ($hasComma ? ',' : '');
            }
        }

        if ($body !== []) {
            $last = count($body) - 1;
            $body[$last] = rtrim($body[$last], ',');
        }

        if ($this->dialect === 'oci') {
            $this->out[] = 'CREATE TABLE ' . $qTable . ' (';
            $this->out[] = implode("\n", $body);
            $this->out[] = ');';
        } else {
            $this->out[] = 'CREATE TABLE IF NOT EXISTS ' . $qTable . ' (';
            $this->out[] = implode("\n", $body);
            $this->out[] = ');';
        }
        $this->out[] = '';

        foreach ($this->pendingIndexes as $idxSql) {
            if ($this->dialect === 'oci') {
                $this->out[] = preg_replace('/IF NOT EXISTS\s+/i', '', $idxSql) ?? $idxSql;
            } else {
                $this->out[] = $idxSql;
            }
        }
        $this->pendingIndexes = [];
        $this->out[] = '';
    }

    private function convertKeyList(string $expr): string
    {
        if (preg_match('/^PRIMARY KEY\s*\(/i', $expr)) {
            $inner = preg_replace('/^PRIMARY KEY\s*\((.+)\)$/i', '$1', $expr) ?? $expr;
            return 'PRIMARY KEY (' . $this->quoteColumnList($inner) . ')';
        }

        return $this->quoteColumnList($expr);
    }

    private function quoteColumnList(string $list): string
    {
        return preg_replace_callback('/`([^`]+)`(\(\d+\))?/', function (array $m): string {
            return $this->quoteId($m[1]);
        }, $list) ?? $list;
    }

    private function replaceEnum(string $def, string $replacement): string
    {
        return preg_replace('/\bENUM\s*\([^)]*\)/i', $replacement, $def) ?? $def;
    }

    private function convertColumnType(string $def, string $column = ''): string
    {
        $def = preg_replace('/\s+COMMENT\s+\'[^\']*\'/i', '', $def) ?? $def;
        $def = preg_replace('/\bON UPDATE CURRENT_TIMESTAMP\b/i', '', $def) ?? $def;

        $hasAutoInc = (bool) preg_match('/\bAUTO_INCREMENT\b/i', $def);
        $def = preg_replace('/\bAUTO_INCREMENT\b/i', '', $def) ?? $def;

        $def = match ($this->dialect) {
            'pgsql' => $this->convertTypePgsql($def, $hasAutoInc),
            'sqlite' => $this->convertTypeSqlite($def, $hasAutoInc, $column),
            'oci' => $this->convertTypeOci($def, $hasAutoInc),
        };

        return trim(preg_replace('/\s+/', ' ', $def) ?? $def);
    }

    private function convertTypePgsql(string $def, bool $hasAutoInc): string
    {
        $def = preg_replace('/\bBIGINT UNSIGNED\b/i', 'BIGINT', $def) ?? $def;
        $def = preg_replace('/\bINT UNSIGNED\b/i', 'INTEGER', $def) ?? $def;
        $def = preg_replace('/\bSMALLINT UNSIGNED\b/i', 'SMALLINT', $def) ?? $def;
        $def = preg_replace('/\bTINYINT\s+UNSIGNED\b/i', 'SMALLINT', $def) ?? $def;
        $def = preg_replace('/\bTINYINT\(\d+\)\b/i', 'SMALLINT', $def) ?? $def;
        $def = preg_replace('/\bSMALLINT\(\d+\)\b/i', 'SMALLINT', $def) ?? $def;
        $def = preg_replace('/\bTINYINT\b/i', 'SMALLINT', $def) ?? $def;
        $def = preg_replace('/\bLONGTEXT\b/i', 'TEXT', $def) ?? $def;
        $def = preg_replace('/\bMEDIUMTEXT\b/i', 'TEXT', $def) ?? $def;
        $def = preg_replace('/\bTEXT\b/i', 'TEXT', $def) ?? $def;
        $def = preg_replace('/\bDATETIME\b/i', 'TIMESTAMP', $def) ?? $def;
        $def = preg_replace('/\bTIMESTAMP\b/i', 'TIMESTAMP', $def) ?? $def;
        $def = $this->replaceEnum($def, 'VARCHAR(32)');
        $def = preg_replace('/\bDEFAULT CURRENT_TIMESTAMP\b/i', 'DEFAULT CURRENT_TIMESTAMP', $def) ?? $def;
        if ($hasAutoInc) {
            $def = preg_replace('/\b(INTEGER|BIGINT)(?=\s)/i', '$1 GENERATED BY DEFAULT AS IDENTITY', $def, 1) ?? $def;
        }

        return $def;
    }

    private function convertTypeSqlite(string $def, bool $hasAutoInc, string $column): string
    {
        $def = preg_replace('/\b(BIGINT|INT|SMALLINT|TINYINT)\s+UNSIGNED\b/i', 'INTEGER', $def) ?? $def;
        $def = preg_replace('/\bTINYINT\(\d+\)\b/i', 'INTEGER', $def) ?? $def;
        $def = preg_replace('/\bINTEGER\(\d+\)\b/i', 'INTEGER', $def) ?? $def;
        $def = preg_replace('/\bTINYINT\b/i', 'INTEGER', $def) ?? $def;
        $def = preg_replace('/\bBIGINT\b/i', 'INTEGER', $def) ?? $def;
        $def = preg_replace('/\bINT UNSIGNED\b/i', 'INTEGER', $def) ?? $def;
        $def = preg_replace('/\bINT\b/i', 'INTEGER', $def) ?? $def;
        $def = preg_replace('/\bSMALLINT\b/i', 'INTEGER', $def) ?? $def;
        $def = preg_replace('/\bLONGTEXT\b/i', 'TEXT', $def) ?? $def;
        $def = preg_replace('/\bMEDIUMTEXT\b/i', 'TEXT', $def) ?? $def;
        $def = preg_replace('/\bDATETIME\b/i', 'TEXT', $def) ?? $def;
        $def = preg_replace('/\bTIMESTAMP\b/i', 'TEXT', $def) ?? $def;
        $def = $this->replaceEnum($def, 'TEXT');
        $def = preg_replace('/\bDEFAULT CURRENT_TIMESTAMP\b/i', 'DEFAULT CURRENT_TIMESTAMP', $def) ?? $def;
        if ($hasAutoInc && $column === 'id') {
            return 'INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL';
        }

        return $def;
    }

    private function convertTypeOci(string $def, bool $hasAutoInc): string
    {
        $def = preg_replace('/\bBIGINT UNSIGNED\b/i', 'NUMBER(19)', $def) ?? $def;
        $def = preg_replace('/\bINT UNSIGNED\b/i', 'NUMBER(10)', $def) ?? $def;
        $def = preg_replace('/\bSMALLINT UNSIGNED\b/i', 'NUMBER(5)', $def) ?? $def;
        $def = preg_replace('/\bTINYINT\(\d+\)\b/i', 'NUMBER(3)', $def) ?? $def;
        $def = preg_replace('/\bTINYINT\s+UNSIGNED\b/i', 'NUMBER(3)', $def) ?? $def;
        $def = preg_replace('/\bTINYINT\b/i', 'NUMBER(3)', $def) ?? $def;
        $def = preg_replace('/\bBIGINT\b/i', 'NUMBER(19)', $def) ?? $def;
        $def = preg_replace('/\bINT\b/i', 'NUMBER(10)', $def) ?? $def;
        $def = preg_replace('/\bSMALLINT\b/i', 'NUMBER(5)', $def) ?? $def;
        $def = preg_replace('/\bLONGTEXT\b/i', 'CLOB', $def) ?? $def;
        $def = preg_replace('/\bMEDIUMTEXT\b/i', 'CLOB', $def) ?? $def;
        $def = preg_replace('/\bTEXT\b/i', 'CLOB', $def) ?? $def;
        $def = preg_replace('/\bVARCHAR\s*\(\s*(\d+)\s*\)/i', 'VARCHAR2($1)', $def) ?? $def;
        $def = preg_replace('/\bVARCHAR\b/i', 'VARCHAR2(255)', $def) ?? $def;
        $def = preg_replace('/\bCHAR\((\d+)\)\b/i', 'CHAR($1)', $def) ?? $def;
        $def = preg_replace('/\bDATETIME\b/i', 'TIMESTAMP', $def) ?? $def;
        $def = preg_replace('/\bTIMESTAMP\b/i', 'TIMESTAMP', $def) ?? $def;
        $def = $this->replaceEnum($def, 'VARCHAR2(32)');
        $def = preg_replace('/\bDECIMAL\s*\(/i', 'NUMBER(', $def) ?? $def;
        $def = preg_replace('/\bDEFAULT CURRENT_TIMESTAMP\b/i', 'DEFAULT SYSTIMESTAMP', $def) ?? $def;
        $def = preg_replace('/NUMBER\((\d+)\)\(\d+\)/i', 'NUMBER($1)', $def) ?? $def;
        if ($hasAutoInc) {
            $def = preg_replace(
                '/\b(NUMBER\(10\)|NUMBER\(19\))(?=\s)/i',
                '$1 GENERATED BY DEFAULT ON NULL AS IDENTITY',
                $def,
                1
            ) ?? $def;
        }

        return $def;
    }

    private function convertInsert(string $line): string
    {
        $sql = preg_replace_callback('/`(\w+)`/', fn (array $m): string => $this->quoteId($m[1]), $line) ?? $line;
        if ($this->dialect === 'pgsql' && preg_match('/^INSERT INTO\s+("[^"]+"|\w+)\s*\([^)]*\bid\b/i', $sql)) {
            $sql = preg_replace('/\)\s*VALUES/i', ') OVERRIDING SYSTEM VALUE VALUES', $sql) ?? $sql;
        }

        return rtrim($sql, ';') . ';';
    }

    private function quoteId(string $name): string
    {
        return match ($this->dialect) {
            'pgsql', 'oci' => '"' . str_replace('"', '""', $name) . '"',
            'sqlite' => '"' . str_replace('"', '""', $name) . '"',
            default => '`' . $name . '`',
        };
    }
}
