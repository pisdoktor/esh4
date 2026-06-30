<?php
namespace App\Services;

use App\Core\Database;
use App\Core\DbSqlHelper;
use PDO;
use PDOException;

/**
 * Yönetici: SQL yedek, geri yükleme ve basit tablo bakımı (OPTIMIZE / CHECK / ANALYZE).
 */
class DbMaintenanceService {

    private function backupDir(): string {
        $dir = defined('BACKUP_STORAGE_PATH') ? BACKUP_STORAGE_PATH : (ROOT_PATH . '/storage/backups');
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        return $dir;
    }

    /** Güvenli yedek dosya yolu veya null */
    public function resolveBackupFile(string $name): ?string {
        $name = basename($name);
        if (!preg_match('/^[a-zA-Z0-9._-]+\.sql(\.gz)?$/', $name)) {
            return null;
        }
        $path = $this->backupDir() . DIRECTORY_SEPARATOR . $name;
        if (!is_file($path)) {
            return null;
        }
        $real = realpath($path);
        $base = realpath($this->backupDir());
        if ($real === false || $base === false || strpos($real, $base) !== 0) {
            return null;
        }
        return $real;
    }

    public function listBackups(): array {
        $dir = $this->backupDir();
        $out = [];
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.sql') ?: [] as $p) {
            $out[] = $this->fileMeta($p);
        }
        foreach (glob($dir . DIRECTORY_SEPARATOR . '*.sql.gz') ?: [] as $p) {
            $out[] = $this->fileMeta($p);
        }
        usort($out, fn ($a, $b) => ($b['mtime'] ?? 0) <=> ($a['mtime'] ?? 0));
        return $out;
    }

    private function fileMeta(string $path): array {
        $meta = $this->parseBackupMeta($path);
        return [
            'name' => basename($path),
            'size' => @filesize($path) ?: 0,
            'mtime' => @filemtime($path) ?: 0,
            'scope' => $meta['scope'],
            'table' => $meta['table'],
        ];
    }

    /**
     * @return array{scope:'full'|'table',table:?string}
     */
    public function parseBackupMeta(string $path): array {
        $name = basename($path);
        $head = @file_get_contents($path, false, null, 0, 4096);
        if (is_string($head) && preg_match('/^--\s*ESH tablo yedeği:\s*(.+)$/m', $head, $m)) {
            $table = trim($m[1]);
            if ($table !== '') {
                return ['scope' => 'table', 'table' => $table];
            }
        }
        $dbSeg = $this->sanitizeDbSegment(defined('DB_NAME') ? DB_NAME : 'db');
        $pattern = '/^' . preg_quote($dbSeg, '/') . '_([a-zA-Z0-9_-]+)_\d{4}-\d{2}-\d{2}_\d{6}\.sql(\.gz)?$/';
        if (preg_match($pattern, $name, $m)) {
            return ['scope' => 'table', 'table' => $m[1]];
        }
        return ['scope' => 'full', 'table' => null];
    }

    /**
     * @return array{name:string,type:string}|null
     */
    public function resolveTableObject(string $name): ?array {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        $db = Database::getInstance();
        if (DbSqlHelper::isSqlSrv()) {
            $row = $db->fetchOnePrepared(
                'SELECT t.name AS name, t.type_desc AS type_desc
                 FROM sys.tables t WHERE t.name = ?',
                [$name]
            );
            if ($row) {
                return ['name' => (string) $row['name'], 'type' => 'BASE TABLE'];
            }
            $row = $db->fetchOnePrepared(
                'SELECT v.name AS name FROM sys.views v WHERE v.name = ?',
                [$name]
            );
            if ($row) {
                return ['name' => (string) $row['name'], 'type' => 'VIEW'];
            }
            return null;
        }
        $dbName = $db->loadResultPrepared('SELECT DATABASE()');
        if (!$dbName) {
            return null;
        }
        $row = $db->fetchOnePrepared(
            'SELECT TABLE_NAME AS name, TABLE_TYPE AS type
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$dbName, $name]
        );
        if (!$row) {
            return null;
        }
        $type = (string) ($row['type'] ?? 'BASE TABLE');
        if ($type !== 'VIEW') {
            $type = 'BASE TABLE';
        }
        return ['name' => (string) $row['name'], 'type' => $type];
    }

    public function tableBackupFileName(string $table): string {
        $dbSeg = $this->sanitizeDbSegment(defined('DB_NAME') ? DB_NAME : 'db');
        $tableSeg = $this->sanitizeDbSegment($table);
        return $dbSeg . '_' . $tableSeg . '_' . date('Y-m-d_His') . '.sql';
    }

    public function guessMysqlBinDir(): ?string {
        if (defined('MYSQL_BIN_DIR') && MYSQL_BIN_DIR !== '') {
            $d = MYSQL_BIN_DIR;
            if ($this->hasMysqlTools($d)) {
                return $d;
            }
        }
        if (PHP_OS_FAMILY === 'Windows') {
            $candidates = [
                'C:\\xampp\\mysql\\bin',
                'C:\\wamp64\\bin\\mysql\\mysql8.4.0\\bin',
                'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin',
                'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin',
            ];
            foreach ($candidates as $d) {
                if ($this->hasMysqlTools($d)) {
                    return $d;
                }
            }
            $which = @shell_exec('where mysqldump 2>nul');
            if (is_string($which) && $which !== '') {
                $line = trim(explode("\n", $which)[0]);
                if ($line !== '' && is_file($line)) {
                    return dirname($line);
                }
            }
        } else {
            $which = @shell_exec('command -v mysqldump 2>/dev/null');
            if (is_string($which)) {
                $line = trim($which);
                if ($line !== '' && is_file($line)) {
                    return dirname($line);
                }
            }
        }
        return null;
    }

    private function hasMysqlTools(string $dir): bool {
        $dump = $dir . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === 'Windows' ? 'mysqldump.exe' : 'mysqldump');
        return is_file($dump);
    }

    public function getMysqlToolPaths(): array {
        $dir = $this->guessMysqlBinDir();
        if (!$dir) {
            return ['dir' => null, 'mysqldump' => null, 'mysql' => null];
        }
        $ext = PHP_OS_FAMILY === 'Windows' ? '.exe' : '';
        return [
            'dir' => $dir,
            'mysqldump' => $dir . DIRECTORY_SEPARATOR . 'mysqldump' . $ext,
            'mysql' => $dir . DIRECTORY_SEPARATOR . 'mysql' . $ext,
        ];
    }

    /**
     * @param bool $useMysqldump true ise mysqldump denenir; başarısızsa PHP’ye düşülür. false ise doğrudan PHP yedeği (varsayılan).
     * @return array{ok:bool,message:string,file?:string,engine?:string}
     */
    public function createSqlBackup(bool $useMysqldump = false): array {
        if (DbSqlHelper::isSqlSrv()) {
            return ['ok' => false, 'message' => 'SQL Server için bu panelden SQL dump yerine BACKUP DATABASE (.bak) kullanın.'];
        }
        @set_time_limit(0);
        $dir = $this->backupDir();
        $fname = $this->sanitizeDbSegment(DB_NAME) . '_' . date('Y-m-d_His') . '.sql';
        $outPath = $dir . DIRECTORY_SEPARATOR . $fname;
        $tools = $this->getMysqlToolPaths();

        if ($useMysqldump && !empty($tools['mysqldump']) && is_file($tools['mysqldump'])) {
            $ok = $this->runMysqldump($tools['mysqldump'], $outPath);
            if ($ok) {
                return ['ok' => true, 'message' => 'Yedek mysqldump ile oluşturuldu.', 'file' => $fname, 'engine' => 'mysqldump'];
            }
            @unlink($outPath);
        }

        try {
            if ($useMysqldump && (empty($tools['mysqldump']) || !is_file($tools['mysqldump']))) {
                $this->dumpDatabasePhp($outPath);
                return ['ok' => true, 'message' => 'Yedek PHP ile oluşturuldu (mysqldump seçildi ancak çalıştırılabilir dosya bulunamadı).', 'file' => $fname, 'engine' => 'php'];
            }
            if ($useMysqldump) {
                $this->dumpDatabasePhp($outPath);
                return ['ok' => true, 'message' => 'Yedek PHP ile oluşturuldu (mysqldump hata verdi veya dosya boş kaldı).', 'file' => $fname, 'engine' => 'php'];
            }
            $this->dumpDatabasePhp($outPath);
            return ['ok' => true, 'message' => 'Yedek PHP ile oluşturuldu.', 'file' => $fname, 'engine' => 'php'];
        } catch (\Throwable $e) {
            @unlink($outPath);
            return ['ok' => false, 'message' => 'Yedek oluşturulamadı: ' . $e->getMessage()];
        }
    }

    private function sanitizeDbSegment(string $name): string {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name) ?: 'db';
    }

    private function runMysqldump(string $exe, string $outPath): bool {
        $args = [
            $exe,
            '--user=' . DB_USER,
            '--password=' . DB_PASS,
            '--host=' . DB_HOST,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--set-charset',
            '--default-character-set=utf8mb4',
            '--result-file=' . $outPath,
            DB_NAME,
        ];
        $proc = @proc_open(
            $args,
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            null,
            ['bypass_shell' => true]
        );
        if (!is_resource($proc)) {
            return false;
        }
        if (isset($pipes[0])) {
            fclose($pipes[0]);
        }
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);
        if ($code !== 0) {
            return false;
        }
        if (!is_file($outPath) || filesize($outPath) < 32) {
            return false;
        }
        return true;
    }

    private function runMysqldumpTable(string $exe, string $outPath, string $table): bool {
        $args = [
            $exe,
            '--user=' . DB_USER,
            '--password=' . DB_PASS,
            '--host=' . DB_HOST,
            '--single-transaction',
            '--set-charset',
            '--default-character-set=utf8mb4',
            '--result-file=' . $outPath,
            '--tables',
            DB_NAME,
            $table,
        ];
        $proc = @proc_open(
            $args,
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            null,
            ['bypass_shell' => true]
        );
        if (!is_resource($proc)) {
            return false;
        }
        if (isset($pipes[0])) {
            fclose($pipes[0]);
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);
        if ($code !== 0) {
            return false;
        }
        if (!is_file($outPath) || filesize($outPath) < 16) {
            return false;
        }
        $body = @file_get_contents($outPath);
        if ($body === false) {
            return false;
        }
        $header = "-- ESH tablo yedeği: {$table}\n-- " . date('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";
        @file_put_contents($outPath, $header . $body . "\nSET FOREIGN_KEY_CHECKS=1;\n", LOCK_EX);
        return true;
    }

    /**
     * @return array{ok:bool,message:string,file?:string,engine?:string}
     */
    public function createTableSqlBackup(string $table, bool $useMysqldump = false): array {
        if (DbSqlHelper::isSqlSrv()) {
            return ['ok' => false, 'message' => 'SQL Server için tablo yedeği bu panelden desteklenmiyor.'];
        }
        $obj = $this->resolveTableObject($table);
        if (!$obj) {
            return ['ok' => false, 'message' => 'Geçersiz tablo veya view adı.'];
        }
        @set_time_limit(0);
        $tableName = $obj['name'];
        $dir = $this->backupDir();
        $fname = $this->tableBackupFileName($tableName);
        $outPath = $dir . DIRECTORY_SEPARATOR . $fname;
        $tools = $this->getMysqlToolPaths();

        if ($useMysqldump && !empty($tools['mysqldump']) && is_file($tools['mysqldump'])) {
            $ok = $this->runMysqldumpTable($tools['mysqldump'], $outPath, $tableName);
            if ($ok) {
                return ['ok' => true, 'message' => 'Tablo yedeği mysqldump ile oluşturuldu.', 'file' => $fname, 'engine' => 'mysqldump'];
            }
            @unlink($outPath);
        }

        try {
            if ($useMysqldump && (empty($tools['mysqldump']) || !is_file($tools['mysqldump']))) {
                $this->dumpTableObjectPhp($outPath, $obj);
                return ['ok' => true, 'message' => 'Tablo yedeği PHP ile oluşturuldu (mysqldump seçildi ancak çalıştırılabilir dosya bulunamadı).', 'file' => $fname, 'engine' => 'php'];
            }
            if ($useMysqldump) {
                $this->dumpTableObjectPhp($outPath, $obj);
                return ['ok' => true, 'message' => 'Tablo yedeği PHP ile oluşturuldu (mysqldump hata verdi veya dosya boş kaldı).', 'file' => $fname, 'engine' => 'php'];
            }
            $this->dumpTableObjectPhp($outPath, $obj);
            return ['ok' => true, 'message' => 'Tablo yedeği PHP ile oluşturuldu.', 'file' => $fname, 'engine' => 'php'];
        } catch (\Throwable $e) {
            @unlink($outPath);
            return ['ok' => false, 'message' => 'Tablo yedeği oluşturulamadı: ' . $e->getMessage()];
        }
    }

    /**
     * @param array{name:string,type:string} $obj
     * @throws \RuntimeException
     */
    private function dumpTableObjectPhp(string $outPath, array $obj): void {
        $db = Database::getInstance();
        $fh = fopen($outPath, 'wb');
        if (!$fh) {
            throw new \RuntimeException('Yedek dosyası yazılamadı.');
        }
        $tableName = $obj['name'];
        fwrite(
            $fh,
            "-- ESH tablo yedeği: {$tableName}\n-- ESH yedek (PHP)\n-- " . date('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n"
        );
        if ($obj['type'] === 'VIEW') {
            $this->dumpViewPhp($db, $fh, $tableName);
        } else {
            $this->dumpTablePhp($db, $fh, $tableName);
        }
        fwrite($fh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public function restoreTableFromBackup(string $name, string $table): array {
        if (DbSqlHelper::isSqlSrv()) {
            return ['ok' => false, 'message' => 'SQL Server için tablo geri yükleme bu panelden desteklenmiyor.'];
        }
        $obj = $this->resolveTableObject($table);
        if (!$obj) {
            return ['ok' => false, 'message' => 'Geçersiz tablo veya view adı.'];
        }
        $path = $this->resolveBackupFile($name);
        if (!$path) {
            return ['ok' => false, 'message' => 'Geçersiz yedek dosyası.'];
        }
        $meta = $this->parseBackupMeta($path);
        if ($meta['scope'] !== 'table') {
            return ['ok' => false, 'message' => 'Seçilen dosya tam veritabanı yedeği; tablo geri yükleme için tablo yedeği seçin.'];
        }
        if (($meta['table'] ?? '') !== $obj['name']) {
            return ['ok' => false, 'message' => 'Yedek dosyası seçili tabloyla eşleşmiyor (yedek: ' . ($meta['table'] ?? '—') . ').'];
        }
        $res = $this->restoreFromBackup($name);
        if (!empty($res['ok'])) {
            $res['message'] = 'Tablo geri yükleme tamamlandı: ' . $obj['name'] . '.';
        }
        return $res;
    }

    /**
     * @throws \RuntimeException
     */
    private function dumpDatabasePhp(string $outPath): void {
        $db = Database::getInstance();
        $fh = fopen($outPath, 'wb');
        if (!$fh) {
            throw new \RuntimeException('Yedek dosyası yazılamadı.');
        }
        fwrite($fh, "-- ESH yedek (PHP)\n-- " . date('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

        $dbName = $db->loadResultPrepared('SELECT DATABASE()');
        if (!$dbName) {
            fclose($fh);
            throw new \RuntimeException('Veritabanı seçili değil.');
        }
        $rows = $db->fetchAllPrepared(
            'SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME',
            [$dbName]
        );
        $views = [];
        foreach ($rows as $row) {
            $t = $row['TABLE_NAME'];
            $type = $row['TABLE_TYPE'] ?? 'BASE TABLE';
            if ($type === 'VIEW') {
                $views[] = $t;
            } elseif ($type === 'BASE TABLE') {
                $this->dumpTablePhp($db, $fh, $t);
            }
        }
        foreach ($views as $t) {
            $this->dumpViewPhp($db, $fh, $t);
        }

        fwrite($fh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
    }

    private function dumpViewPhp(Database $db, $fh, string $table): void {
        $row = $db->fetchOnePrepared('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`');
        if (!$row) {
            return;
        }
        $key = isset($row['Create View']) ? 'Create View' : 'Create Table';
        if (!isset($row[$key])) {
            return;
        }
        fwrite($fh, "\nDROP VIEW IF EXISTS `" . str_replace('`', '``', $table) . "`;\n");
        fwrite($fh, $row[$key] . ";\n");
    }

    private function dumpTablePhp(Database $db, $fh, string $table): void {
        $t = str_replace('`', '``', $table);
        $row = $db->fetchOnePrepared('SHOW CREATE TABLE `' . $t . '`');
        if (!$row || !isset($row['Create Table'])) {
            return;
        }
        fwrite($fh, "\nDROP TABLE IF EXISTS `" . $t . "`;\n");
        fwrite($fh, $row['Create Table'] . ";\n");

        $colRows = $db->fetchAllPrepared(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$table]
        );
        $cols = [];
        foreach ($colRows as $colRow) {
            $name = (string) ($colRow['COLUMN_NAME'] ?? '');
            if ($name !== '') {
                $cols[] = $name;
            }
        }
        if ($cols === []) {
            return;
        }
        $colList = '`' . implode('`,`', array_map(fn ($c) => str_replace('`', '``', $c), $cols)) . '`';
        $batch = [];
        $offset = 0;
        $pageSize = 500;
        while (true) {
            $rows = $db->fetchAllPrepared(
                'SELECT * FROM `' . $t . '` LIMIT ' . (int) $pageSize . ' OFFSET ' . (int) $offset
            );
            if ($rows === []) {
                break;
            }
            foreach ($rows as $r) {
                $vals = [];
                foreach ($cols as $c) {
                    $vals[] = $this->sqlLiteral($db, $r[$c] ?? null);
                }
                $batch[] = '(' . implode(',', $vals) . ')';
                if (count($batch) >= 150) {
                    fwrite($fh, "INSERT INTO `" . $t . "` ($colList) VALUES\n" . implode(",\n", $batch) . ";\n");
                    $batch = [];
                }
            }
            if (count($rows) < $pageSize) {
                break;
            }
            $offset += $pageSize;
        }
        if ($batch !== []) {
            fwrite($fh, "INSERT INTO `" . $t . "` ($colList) VALUES\n" . implode(",\n", $batch) . ";\n");
        }
    }

    private function sqlLiteral(Database $db, $v): string {
        if ($v === null) {
            return 'NULL';
        }
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }
        if (is_int($v)) {
            return (string) $v;
        }
        if (is_float($v)) {
            if (is_nan($v) || is_infinite($v)) {
                return 'NULL';
            }
            return (string) $v;
        }
        return $db->quote((string) $v);
    }

    public function deleteBackup(string $name): array {
        $path = $this->resolveBackupFile($name);
        if (!$path) {
            return ['ok' => false, 'message' => 'Geçersiz dosya.'];
        }
        if (@unlink($path)) {
            return ['ok' => true, 'message' => 'Yedek silindi.'];
        }
        return ['ok' => false, 'message' => 'Dosya silinemedi.'];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public function restoreFromBackup(string $name): array {
        if (DbSqlHelper::isSqlSrv()) {
            return ['ok' => false, 'message' => 'SQL Server için bu panelde .sql geri yükleme kapalıdır. SSMS/RESTORE DATABASE kullanın.'];
        }
        @set_time_limit(0);
        $path = $this->resolveBackupFile($name);
        if (!$path) {
            return ['ok' => false, 'message' => 'Geçersiz yedek dosyası.'];
        }

        $workPath = $path;
        $tmp = null;
        if (str_ends_with(strtolower($path), '.gz')) {
            $tmp = tempnam(sys_get_temp_dir(), 'eshsql_');
            if ($tmp === false) {
                return ['ok' => false, 'message' => 'Geçici dosya oluşturulamadı.'];
            }
            $tmp .= '.sql';
            @unlink($tmp);
            $src = @gzopen($path, 'rb');
            if (!$src) {
                return ['ok' => false, 'message' => 'Sıkıştırılmış yedek okunamadı.'];
            }
            $dst = @fopen($tmp, 'wb');
            if (!$dst) {
                gzclose($src);
                return ['ok' => false, 'message' => 'Geçici dosya açılamadı.'];
            }
            stream_copy_to_stream($src, $dst);
            fclose($dst);
            gzclose($src);
            $workPath = $tmp;
        }

        $tools = $this->getMysqlToolPaths();
        if (!empty($tools['mysql']) && is_file($tools['mysql'])) {
            $res = $this->runMysqlImport($tools['mysql'], $workPath);
            if ($tmp && is_file($tmp)) {
                @unlink($tmp);
            }
            return $res;
        }

        $size = @filesize($workPath) ?: 0;
        if ($size > 52428800) {
            if ($tmp && is_file($tmp)) {
                @unlink($tmp);
            }
            return ['ok' => false, 'message' => 'mysql istemcisi bulunamadı; 50 MB üzeri yedekler için sunucuya mysqldump/mysql kurun veya ESH_MYSQL_BIN ayarlayın.'];
        }

        $sql = @file_get_contents($workPath);
        if ($tmp && is_file($tmp)) {
            @unlink($tmp);
        }
        if ($sql === false || $sql === '') {
            return ['ok' => false, 'message' => 'Yedek dosyası okunamadı.'];
        }

        return $this->restoreViaMysqliMulti($sql);
    }

    private function runMysqlImport(string $mysqlExe, string $sqlFile): array {
        $args = [
            $mysqlExe,
            '--user=' . DB_USER,
            '--password=' . DB_PASS,
            '--host=' . DB_HOST,
            '--default-character-set=utf8mb4',
            DB_NAME,
        ];
        $proc = @proc_open(
            $args,
            [
                0 => ['file', $sqlFile, 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            null,
            null,
            ['bypass_shell' => true]
        );
        if (!is_resource($proc)) {
            return ['ok' => false, 'message' => 'mysql işlemi başlatılamadı.'];
        }
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);
        if ($code !== 0) {
            $msg = trim($err !== '' ? $err : $out);
            return ['ok' => false, 'message' => 'Geri yükleme hatası' . ($msg !== '' ? ': ' . $msg : ' (kod ' . $code . ')')];
        }
        return ['ok' => true, 'message' => 'Geri yükleme tamamlandı (mysql istemcisi).'];
    }

    private function restoreViaMysqliMulti(string $sql): array {
        if (!function_exists('mysqli_connect')) {
            return ['ok' => false, 'message' => 'mysqli uzantısı kapalı; mysql istemcisi gerekir.'];
        }
        $mysqli = @new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($mysqli->connect_error) {
            return ['ok' => false, 'message' => 'Bağlantı: ' . $mysqli->connect_error];
        }
        $mysqli->set_charset('utf8mb4');
        if (!$mysqli->multi_query($sql)) {
            $err = $mysqli->error;
            $mysqli->close();
            return ['ok' => false, 'message' => 'SQL: ' . $err];
        }
        do {
            if ($r = $mysqli->store_result()) {
                $r->free();
            }
        } while ($mysqli->more_results() && $mysqli->next_result());
        if ($mysqli->error) {
            $err = $mysqli->error;
            $mysqli->close();
            return ['ok' => false, 'message' => 'SQL (devam): ' . $err];
        }
        $mysqli->close();
        return ['ok' => true, 'message' => 'Geri yükleme tamamlandı (mysqli).'];
    }

    /** @return array<int,array{table:string,op:string,msg_type:string,msg_text:string}> */
    public function runTableMaintenance(string $operation): array {
        $db = Database::getInstance();
        if (DbSqlHelper::isSqlSrv()) {
            if (!$db->execLogged('DBCC CHECKDB WITH NO_INFOMSGS')) {
                return [[
                    'table' => 'database',
                    'op' => 'CHECKDB',
                    'msg_type' => 'error',
                    'msg_text' => $db->getErrorMsg() ?: 'DBCC CHECKDB başarısız.',
                ]];
            }

            return [[
                'table' => 'database',
                'op' => 'CHECKDB',
                'msg_type' => 'status',
                'msg_text' => 'DBCC CHECKDB çalıştırıldı.',
            ]];
        }
        $op = strtoupper($operation);
        if (!in_array($op, ['CHECK', 'OPTIMIZE', 'ANALYZE'], true)) {
            return [];
        }
        $dbName = $db->loadResultPrepared('SELECT DATABASE()');
        if (!$dbName) {
            return [];
        }
        $tables = $db->fetchColumnListPrepared(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ? ORDER BY TABLE_NAME',
            [$dbName, 'BASE TABLE']
        );
        $out = [];
        foreach ($tables as $t) {
            $t = (string) $t;
            $safe = str_replace('`', '``', $t);
            try {
                $row = $db->fetchOnePrepared($op . ' TABLE `' . $safe . '`');
                if ($row) {
                    $out[] = [
                        'table' => $t,
                        'op' => $op,
                        'msg_type' => (string) ($row['Msg_type'] ?? ''),
                        'msg_text' => (string) ($row['Msg_text'] ?? ''),
                    ];
                }
            } catch (PDOException $e) {
                $out[] = [
                    'table' => $t,
                    'op' => $op,
                    'msg_type' => 'error',
                    'msg_text' => $e->getMessage(),
                ];
            }
        }
        return $out;
    }

    /** Özet: tablo adı, satır sayısı, motor, boyut */
    public function getTableOverview(): array {
        $db = Database::getInstance();
        if (DbSqlHelper::isSqlSrv()) {
            $sql = "SELECT t.name AS t, p.rows AS est_rows, 'ROWSTORE' AS eng,
                           CAST(ROUND((SUM(a.total_pages) * 8.0) / 1024.0, 2) AS DECIMAL(18,2)) AS mb
                    FROM sys.tables t
                    JOIN sys.indexes i ON t.object_id = i.object_id
                    JOIN sys.partitions p ON i.object_id = p.object_id AND i.index_id = p.index_id
                    JOIN sys.allocation_units a ON p.partition_id = a.container_id
                    WHERE i.index_id <= 1
                    GROUP BY t.name, p.rows
                    ORDER BY t.name";
            return $db->fetchAllPrepared($sql);
        }
        $dbName = $db->loadResultPrepared('SELECT DATABASE()');
        if (!$dbName) {
            return [];
        }
        return $db->fetchAllPrepared(
            'SELECT TABLE_NAME AS t, TABLE_ROWS AS est_rows, ENGINE AS eng,
             ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS mb
             FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_NAME',
            [$dbName]
        );
    }
}
