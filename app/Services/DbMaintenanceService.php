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

    /** Bakım işlemlerinden hariç tutulan tablolar */
    private const MAINTENANCE_EXCLUDE_TABLES = [
        'esh_rota_cache',
    ];

    /** Tam yedekten hariç tutulabilecek önbellek tabloları */
    private const BACKUP_EXCLUDE_TABLES = [
        'esh_rota_cache',
    ];

    /** @var array<string, array{label:string}> */
    private const BACKUP_GROUPS = [
        'hasta' => ['label' => 'Hasta ve klinik verileri'],
        'izlem' => ['label' => 'İzlem ve e-rapor'],
        'randevu' => ['label' => 'Randevu ve portal talepleri'],
        'referans' => ['label' => 'Kurum ve katalog tabloları'],
        'stok' => ['label' => 'Stok ve malzeme'],
        'mesaj' => ['label' => 'Dahili mesajlaşma'],
        'sms' => ['label' => 'SMS kayıtları'],
        'entegrasyon' => ['label' => 'ESYS, USBS, SKRS ve köprü logları'],
    ];

    /** @var array<string, string> */
    private const TABLE_GROUP_LABELS = [
        'hasta' => 'Hasta',
        'izlem' => 'İzlem',
        'randevu' => 'Randevu',
        'referans' => 'Referans',
        'stok' => 'Stok',
        'mesaj' => 'Mesajlaşma',
        'sms' => 'SMS',
        'rehber' => 'İlaç rehberi',
        'entegrasyon' => 'Entegrasyon',
        'cache' => 'Önbellek',
        'sistem' => 'Sistem',
        'diger' => 'Diğer',
    ];

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
            'group' => $meta['group'],
        ];
    }

    /**
     * @return array{scope:'full'|'table'|'group',table:?string,group:?string}
     */
    public function parseBackupMeta(string $path): array {
        $name = basename($path);
        $head = $this->readBackupHead($path);
        if (is_string($head) && preg_match('/^--\s*ESH grup yedeği:\s*(.+)$/m', $head, $m)) {
            $group = strtolower(trim($m[1]));
            if ($group !== '' && isset(self::BACKUP_GROUPS[$group])) {
                return ['scope' => 'group', 'table' => null, 'group' => $group];
            }
        }
        if (is_string($head) && preg_match('/^--\s*ESH tablo yedeği:\s*(.+)$/m', $head, $m)) {
            $table = trim($m[1]);
            if ($table !== '') {
                return ['scope' => 'table', 'table' => $table, 'group' => null];
            }
        }
        $dbSeg = $this->sanitizeDbSegment(defined('DB_NAME') ? DB_NAME : 'db');
        $groupPattern = '/^' . preg_quote($dbSeg, '/') . '_group_([a-z]+)_\d{4}-\d{2}-\d{2}_\d{6}\.sql(\.gz)?$/';
        if (preg_match($groupPattern, $name, $m)) {
            $group = $m[1];
            if (isset(self::BACKUP_GROUPS[$group])) {
                return ['scope' => 'group', 'table' => null, 'group' => $group];
            }
        }
        $pattern = '/^' . preg_quote($dbSeg, '/') . '_([a-zA-Z0-9_-]+)_\d{4}-\d{2}-\d{2}_\d{6}\.sql(\.gz)?$/';
        if (preg_match($pattern, $name, $m)) {
            return ['scope' => 'table', 'table' => $m[1], 'group' => null];
        }
        return ['scope' => 'full', 'table' => null, 'group' => null];
    }

    private function readBackupHead(string $path, int $len = 4096): ?string {
        if (str_ends_with(strtolower($path), '.gz')) {
            $h = @gzopen($path, 'rb');
            if (!$h) {
                return null;
            }
            $head = gzread($h, $len);
            gzclose($h);
            return is_string($head) ? $head : null;
        }
        $head = @file_get_contents($path, false, null, 0, $len);
        return is_string($head) ? $head : null;
    }

    /** @return array<string, array{label:string}> */
    public function getBackupGroups(): array {
        return self::BACKUP_GROUPS;
    }

    public function getTableGroupKey(string $table): string {
        $table = strtolower(trim($table));
        if ($table === 'esh_rota_cache' || str_contains($table, '_cache')) {
            return 'cache';
        }
        if (str_starts_with($table, 'esh_hasta') || in_array($table, ['esh_hastalar', 'esh_hastailacrapor', 'esh_hasta_ilaclar', 'esh_hasta_nakil'], true)) {
            return 'hasta';
        }
        if (str_starts_with($table, 'esh_izlem') || str_starts_with($table, 'esh_pizlem') || $table === 'esh_erapor') {
            return 'izlem';
        }
        if (in_array($table, ['esh_kons_randevu', 'esh_goruntulu_randevu', 'esh_portal_appointment_requests'], true)) {
            return 'randevu';
        }
        if (str_starts_with($table, 'esh_stok_')) {
            return 'stok';
        }
        if (str_starts_with($table, 'esh_mesaj')) {
            return 'mesaj';
        }
        if (str_starts_with($table, 'esh_sms_')) {
            return 'sms';
        }
        if (str_starts_with($table, 'esh_rehber_')) {
            return 'rehber';
        }
        if ($this->isEntegrasyonTable($table)) {
            return 'entegrasyon';
        }
        $referansPrefixes = ['esh_kurum', 'esh_adres', 'esh_mahalle', 'esh_brans', 'esh_hastalik', 'esh_guvence', 'esh_islem', 'esh_istek', 'esh_arac', 'esh_unvan', 'esh_ekip', 'esh_personel', 'esh_resmi'];
        foreach ($referansPrefixes as $prefix) {
            if (str_starts_with($table, $prefix)) {
                return 'referans';
            }
        }
        if (in_array($table, ['esh_kurumlar', 'esh_adrestablosu', 'esh_branslar', 'esh_guvence', 'esh_islemler', 'esh_istekler', 'esh_hastaliklar', 'esh_hastalikcat', 'esh_araclar', 'esh_unvanlar'], true)) {
            return 'referans';
        }
        $sistemTables = ['esh_users', 'esh_roles', 'esh_permissions', 'esh_role_permissions', 'esh_user_roles', 'esh_api_tokens', 'esh_audit_log', 'esh_eimza_challenges', 'esh_eimza_login_logs'];
        if (in_array($table, $sistemTables, true) || str_starts_with($table, 'esh_eimza_')) {
            return 'sistem';
        }
        return 'diger';
    }

    private function isEntegrasyonTable(string $table): bool {
        if (str_starts_with($table, 'esh_skrs_') || str_starts_with($table, 'esh_federation_')) {
            return true;
        }
        return in_array($table, ['esh_esys_sync_log', 'esh_usbs_sync_log', 'esh_cds_ack'], true);
    }

    public function getTableGroupLabel(string $groupKey): string {
        return self::TABLE_GROUP_LABELS[$groupKey] ?? self::TABLE_GROUP_LABELS['diger'];
    }

    /**
     * @param list<array{t:string,mb:string|float,est_rows:int,group:string,group_label:string}> $tables
     * @return list<array{key:string,label:string,count:int,mb:float,rows:int}>
     */
    public function getTableGroupSummary(array $tables): array {
        $buckets = [];
        foreach (self::TABLE_GROUP_LABELS as $key => $label) {
            $buckets[$key] = ['key' => $key, 'label' => $label, 'count' => 0, 'mb' => 0.0, 'rows' => 0];
        }
        foreach ($tables as $row) {
            $key = (string) ($row['group'] ?? 'diger');
            if (!isset($buckets[$key])) {
                $key = 'diger';
            }
            $buckets[$key]['count']++;
            $buckets[$key]['mb'] += (float) ($row['mb'] ?? 0);
            $buckets[$key]['rows'] += (int) ($row['est_rows'] ?? 0);
        }
        $out = [];
        foreach ($buckets as $bucket) {
            if ($bucket['count'] === 0) {
                continue;
            }
            $bucket['mb'] = round($bucket['mb'], 2);
            $out[] = $bucket;
        }
        return $out;
    }

    /**
     * @param list<string> $presentLabels
     * @return list<string>
     */
    public function orderedGroupLabels(array $presentLabels): array {
        $present = array_flip($presentLabels);
        $out = [];
        foreach (self::TABLE_GROUP_LABELS as $label) {
            if (isset($present[$label])) {
                $out[] = $label;
            }
        }
        foreach ($presentLabels as $label) {
            if (!in_array($label, $out, true)) {
                $out[] = $label;
            }
        }
        return $out;
    }

    /**
     * @return list<string>
     */
    public function resolveGroupTables(string $group): array {
        $group = strtolower(trim($group));
        if (!isset(self::BACKUP_GROUPS[$group])) {
            return [];
        }
        $out = [];
        foreach ($this->getTableOverview() as $row) {
            $t = (string) ($row['t'] ?? '');
            if ($t === '' || $this->getTableGroupKey($t) !== $group) {
                continue;
            }
            $obj = $this->resolveTableObject($t);
            if ($obj && $obj['type'] === 'BASE TABLE') {
                $out[] = $obj['name'];
            }
        }
        sort($out);
        return $out;
    }

    public function formatByteSize(int $bytes): string {
        if ($bytes < 1024) {
            return number_format($bytes, 0, ',', '.') . ' B';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }
        if ($bytes < 1073741824) {
            return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
        }
        return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
    }

    /**
     * @return array{tables_count:int,total_mb:float,total_est_rows:int,backups_count:int,last_backup_mtime:?int,mysqldump_available:bool,mysql_available:bool}
     */
    public function getDatabaseSummary(): array {
        $tables = $this->getTableOverview();
        $totalMb = 0.0;
        $totalRows = 0;
        foreach ($tables as $t) {
            $totalMb += (float) ($t['mb'] ?? 0);
            $totalRows += (int) ($t['est_rows'] ?? 0);
        }
        $backups = $this->listBackups();
        $lastMtime = 0;
        foreach ($backups as $b) {
            $mt = (int) ($b['mtime'] ?? 0);
            if ($mt > $lastMtime) {
                $lastMtime = $mt;
            }
        }
        $tools = $this->getMysqlToolPaths();
        return [
            'tables_count' => count($tables),
            'total_mb' => round($totalMb, 2),
            'total_est_rows' => $totalRows,
            'backups_count' => count($backups),
            'last_backup_mtime' => $lastMtime > 0 ? $lastMtime : null,
            'mysqldump_available' => !empty($tools['mysqldump']) && is_file($tools['mysqldump']),
            'mysql_available' => !empty($tools['mysql']) && is_file($tools['mysql']),
        ];
    }

    /**
     * @return list<array{t:string,eng:string,est_rows:int,mb:string|float,group:string,group_label:string}>
     */
    public function getEnrichedTableOverview(): array {
        $rows = $this->getTableOverview();
        foreach ($rows as &$row) {
            $group = $this->getTableGroupKey((string) ($row['t'] ?? ''));
            $row['group'] = $group;
            $row['group_label'] = $this->getTableGroupLabel($group);
        }
        unset($row);
        usort($rows, function ($a, $b) {
            $mbA = (float) ($a['mb'] ?? 0);
            $mbB = (float) ($b['mb'] ?? 0);
            if ($mbB !== $mbA) {
                return $mbB <=> $mbA;
            }
            return strcmp((string) ($a['t'] ?? ''), (string) ($b['t'] ?? ''));
        });
        return $rows;
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

    public function groupBackupFileName(string $group): string {
        $dbSeg = $this->sanitizeDbSegment(defined('DB_NAME') ? DB_NAME : 'db');
        $groupSeg = preg_replace('/[^a-z]/', '', strtolower($group)) ?: 'group';
        return $dbSeg . '_group_' . $groupSeg . '_' . date('Y-m-d_His') . '.sql';
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
     * @param bool $useMysqldump true ise mysqldump denenir; başarısızsa PHP’ye düşülür.
     * @return array{ok:bool,message:string,file?:string,engine?:string}
     */
    public function createSqlBackup(bool $useMysqldump = false, bool $compress = false, bool $excludeCache = true): array {
        if (DbSqlHelper::isSqlSrv()) {
            return ['ok' => false, 'message' => 'SQL Server için bu panelden SQL dump yerine BACKUP DATABASE (.bak) kullanın.'];
        }
        @set_time_limit(0);
        $dir = $this->backupDir();
        $fname = $this->sanitizeDbSegment(DB_NAME) . '_' . date('Y-m-d_His') . '.sql';
        $outPath = $dir . DIRECTORY_SEPARATOR . $fname;
        $tools = $this->getMysqlToolPaths();
        $ignoreTables = $excludeCache ? self::BACKUP_EXCLUDE_TABLES : [];

        if ($useMysqldump && !empty($tools['mysqldump']) && is_file($tools['mysqldump'])) {
            $ok = $this->runMysqldump($tools['mysqldump'], $outPath, $ignoreTables);
            if ($ok) {
                return $this->finishBackupResult($outPath, $fname, 'Yedek mysqldump ile oluşturuldu.', 'mysqldump', $compress);
            }
            @unlink($outPath);
        }

        try {
            if ($useMysqldump && (empty($tools['mysqldump']) || !is_file($tools['mysqldump']))) {
                $this->dumpDatabasePhp($outPath, $ignoreTables);
                return $this->finishBackupResult($outPath, $fname, 'Yedek PHP ile oluşturuldu (mysqldump seçildi ancak çalıştırılabilir dosya bulunamadı).', 'php', $compress);
            }
            if ($useMysqldump) {
                $this->dumpDatabasePhp($outPath, $ignoreTables);
                return $this->finishBackupResult($outPath, $fname, 'Yedek PHP ile oluşturuldu (mysqldump hata verdi veya dosya boş kaldı).', 'php', $compress);
            }
            $this->dumpDatabasePhp($outPath, $ignoreTables);
            return $this->finishBackupResult($outPath, $fname, 'Yedek PHP ile oluşturuldu.', 'php', $compress);
        } catch (\Throwable $e) {
            @unlink($outPath);
            return ['ok' => false, 'message' => 'Yedek oluşturulamadı: ' . $e->getMessage()];
        }
    }

    /**
     * @return array{ok:bool,message:string,file?:string,engine?:string}
     */
    public function createGroupSqlBackup(string $group, bool $useMysqldump = false, bool $compress = false): array {
        if (DbSqlHelper::isSqlSrv()) {
            return ['ok' => false, 'message' => 'SQL Server için grup yedeği bu panelden desteklenmiyor.'];
        }
        $group = strtolower(trim($group));
        if (!isset(self::BACKUP_GROUPS[$group])) {
            return ['ok' => false, 'message' => 'Geçersiz yedek grubu.'];
        }
        $tables = $this->resolveGroupTables($group);
        if ($tables === []) {
            return ['ok' => false, 'message' => 'Bu grupta yedeklenecek tablo bulunamadı.'];
        }
        @set_time_limit(0);
        $dir = $this->backupDir();
        $fname = $this->groupBackupFileName($group);
        $outPath = $dir . DIRECTORY_SEPARATOR . $fname;
        $tools = $this->getMysqlToolPaths();

        if ($useMysqldump && !empty($tools['mysqldump']) && is_file($tools['mysqldump'])) {
            $ok = $this->runMysqldumpTables($tools['mysqldump'], $outPath, $tables, $group);
            if ($ok) {
                return $this->finishBackupResult($outPath, $fname, 'Grup yedeği mysqldump ile oluşturuldu.', 'mysqldump', $compress);
            }
            @unlink($outPath);
        }

        try {
            $this->dumpGroupPhp($outPath, $tables, $group);
            $label = self::BACKUP_GROUPS[$group]['label'];
            return $this->finishBackupResult($outPath, $fname, 'Grup yedeği PHP ile oluşturuldu (' . $label . ').', 'php', $compress);
        } catch (\Throwable $e) {
            @unlink($outPath);
            return ['ok' => false, 'message' => 'Grup yedeği oluşturulamadı: ' . $e->getMessage()];
        }
    }

    /**
     * @return array{ok:bool,message:string,file?:string,engine?:string}
     */
    private function finishBackupResult(string $outPath, string $fname, string $message, string $engine, bool $compress): array {
        if ($compress) {
            $gzPath = $this->compressSqlFile($outPath);
            if ($gzPath === null) {
                @unlink($outPath);
                return ['ok' => false, 'message' => 'Yedek sıkıştırılamadı.'];
            }
            return ['ok' => true, 'message' => $message . ' (gzip)', 'file' => basename($gzPath), 'engine' => $engine];
        }
        return ['ok' => true, 'message' => $message, 'file' => $fname, 'engine' => $engine];
    }

    private function compressSqlFile(string $sqlPath): ?string {
        if (!is_file($sqlPath)) {
            return null;
        }
        $gzPath = $sqlPath . '.gz';
        $src = @fopen($sqlPath, 'rb');
        if (!$src) {
            return null;
        }
        $dst = @gzopen($gzPath, 'wb9');
        if (!$dst) {
            fclose($src);
            return null;
        }
        while (!feof($src)) {
            $chunk = fread($src, 65536);
            if ($chunk === false) {
                break;
            }
            gzwrite($dst, $chunk);
        }
        fclose($src);
        gzclose($dst);
        @unlink($sqlPath);
        return is_file($gzPath) ? $gzPath : null;
    }

    private function sanitizeDbSegment(string $name): string {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name) ?: 'db';
    }

    private function runMysqldump(string $exe, string $outPath, array $ignoreTables = []): bool {
        $args = [
            $exe,
            '--user=' . DB_USER,
            '--password=' . DB_PASS,
            '--host=' . DB_HOST,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--set-charset',
            '--default-character-set=utf8mb4',
            '--result-file=' . $outPath,
        ];
        foreach ($ignoreTables as $t) {
            $args[] = '--ignore-table=' . DB_NAME . '.' . $t;
        }
        $args[] = DB_NAME;
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

    /**
     * @param list<string> $tables
     */
    private function runMysqldumpTables(string $exe, string $outPath, array $tables, ?string $groupLabel = null): bool {
        $args = [
            $exe,
            '--user=' . DB_USER,
            '--password=' . DB_PASS,
            '--host=' . DB_HOST,
            '--single-transaction',
            '--quick',
            '--set-charset',
            '--default-character-set=utf8mb4',
            '--result-file=' . $outPath,
            DB_NAME,
        ];
        foreach ($tables as $table) {
            $args[] = $table;
        }
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
        $header = $groupLabel !== null
            ? "-- ESH grup yedeği: {$groupLabel}\n-- " . date('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n"
            : "-- ESH çoklu tablo yedeği\n-- " . date('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";
        @file_put_contents($outPath, $header . $body . "\nSET FOREIGN_KEY_CHECKS=1;\n", LOCK_EX);
        return true;
    }

    private function runMysqldumpTable(string $exe, string $outPath, string $table): bool {
        $args = [
            $exe,
            '--user=' . DB_USER,
            '--password=' . DB_PASS,
            '--host=' . DB_HOST,
            '--single-transaction',
            '--quick',
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
    public function createTableSqlBackup(string $table, bool $useMysqldump = false, bool $compress = false): array {
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
                return $this->finishBackupResult($outPath, $fname, 'Tablo yedeği mysqldump ile oluşturuldu.', 'mysqldump', $compress);
            }
            @unlink($outPath);
        }

        try {
            if ($useMysqldump && (empty($tools['mysqldump']) || !is_file($tools['mysqldump']))) {
                $this->dumpTableObjectPhp($outPath, $obj);
                return $this->finishBackupResult($outPath, $fname, 'Tablo yedeği PHP ile oluşturuldu (mysqldump seçildi ancak çalıştırılabilir dosya bulunamadı).', 'php', $compress);
            }
            if ($useMysqldump) {
                $this->dumpTableObjectPhp($outPath, $obj);
                return $this->finishBackupResult($outPath, $fname, 'Tablo yedeği PHP ile oluşturuldu (mysqldump hata verdi veya dosya boş kaldı).', 'php', $compress);
            }
            $this->dumpTableObjectPhp($outPath, $obj);
            return $this->finishBackupResult($outPath, $fname, 'Tablo yedeği PHP ile oluşturuldu.', 'php', $compress);
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
     * @param list<string> $tables
     * @throws \RuntimeException
     */
    private function dumpGroupPhp(string $outPath, array $tables, string $group): void {
        $db = Database::getInstance();
        $fh = fopen($outPath, 'wb');
        if (!$fh) {
            throw new \RuntimeException('Yedek dosyası yazılamadı.');
        }
        fwrite(
            $fh,
            "-- ESH grup yedeği: {$group}\n-- ESH yedek (PHP)\n-- " . date('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n"
        );
        foreach ($tables as $tableName) {
            $obj = $this->resolveTableObject($tableName);
            if (!$obj || $obj['type'] !== 'BASE TABLE') {
                continue;
            }
            $this->dumpTablePhp($db, $fh, $obj['name']);
        }
        fwrite($fh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fh);
    }

    /**
     * @throws \RuntimeException
     */
    private function dumpDatabasePhp(string $outPath, array $skipTables = []): void {
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
        $skip = array_flip($skipTables);
        foreach ($rows as $row) {
            $t = $row['TABLE_NAME'];
            if (isset($skip[$t])) {
                continue;
            }
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
    public function runTableMaintenance(string $operation, ?array $tables = null, ?array $exclude = null): array {
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

        $excludeList = self::MAINTENANCE_EXCLUDE_TABLES;
        if ($exclude !== null) {
            $excludeList = array_values(array_unique(array_merge($excludeList, $exclude)));
        }
        $excludeFlip = array_flip($excludeList);

        if ($tables !== null && $tables !== []) {
            $targetTables = [];
            foreach ($tables as $t) {
                $t = trim((string) $t);
                if ($t === '' || isset($excludeFlip[$t])) {
                    continue;
                }
                $obj = $this->resolveTableObject($t);
                if ($obj && $obj['type'] === 'BASE TABLE') {
                    $targetTables[] = $obj['name'];
                }
            }
        } else {
            $all = $db->fetchColumnListPrepared(
                'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ? ORDER BY TABLE_NAME',
                [$dbName, 'BASE TABLE']
            );
            $targetTables = [];
            foreach ($all as $t) {
                $t = (string) $t;
                if (!isset($excludeFlip[$t])) {
                    $targetTables[] = $t;
                }
            }
        }

        $out = [];
        foreach ($targetTables as $t) {
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
