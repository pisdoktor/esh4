<?php
namespace App\Core;
use PDO;
use Exception;

/**
 * Yeni veya refaktör edilen kod: executePrepared, fetch*Prepared, insertPrepared,
 * updatePrepared, whereInPlaceholders; çok adımlı iş için transaction().
 * DDL/bakım: execLogged() tercih edin; ham getPdo() son çare.
 */
class Database {
    private static $instance = null;
    private $pdo;
    private $stmt = null;
    private $_sql = '';

    // Eski sistemle tam uyum için hata ve log değişkenleri
    private $_errorNum = 0;
    private $_errorMsg = '';
    /** @var list<array{sql: string, duration_ms: float, memory_mb: float, slow: bool}> */
    private $_log = [];
    private $_debug = 0;
    private $_slowQueryThresholdMs = 500;
    private string $driver = 'mysql';

    /** @var array{host:string,user:string,pass:string,name:string,prefix:string,port:string,driver:string}|null */
    private ?array $installConfig = null;

    private function __construct(bool $autoConnect = true)
    {
        if ($autoConnect) {
            $this->connectFromAppConfig();
        }
    }

    private function connectFromAppConfig(): void
    {
        $this->driver = DbSqlHelper::driver();
        $pdoDriver = $this->pdoDriverName($this->driver);
        if (!in_array($pdoDriver, PDO::getAvailableDrivers(), true)) {
            if ($this->driver !== 'mysql') {
                $this->handleError(new Exception('PDO sürücüsü yüklü değil: ' . $pdoDriver), 'Bağlantı Hatası');
                die('Kritik Veritabanı Hatası! Lütfen yöneticiye bildiriniz.');
            }
            $this->driver = 'mysql';
            $pdoDriver = 'mysql';
        }
        $this->_debug = (defined('DB_DEBUG') && DB_DEBUG) ? 1 : 0;
        $this->_slowQueryThresholdMs = defined('DB_SLOW_QUERY_MS') ? max(0, (int) \DB_SLOW_QUERY_MS) : 500;
        try {
            $dsn = DbSqlHelper::buildPdoDsn($this->driver);
            if ($this->driver === 'sqlite') {
                $this->pdo = new PDO($dsn);
            } else {
                $this->pdo = new PDO($dsn, \DB_USER, \DB_PASS);
            }
            $this->configurePdo();
            if ($this->driver === 'mysql') {
                $this->runMysqlInitStatements();
            }
            if ($this->driver === 'sqlite') {
                $this->pdo->exec('PRAGMA foreign_keys = ON');
            }
        } catch (Exception $e) {
            $this->handleError($e, 'Bağlantı Hatası');
            die('Kritik Veritabanı Hatası! Lütfen yöneticiye bildiriniz.');
        }
    }

    private function pdoDriverName(string $driver): string
    {
        return match (DbSqlHelper::normalizeDbDriver($driver)) {
            'sqlsrv' => 'sqlsrv',
            'pgsql' => 'pgsql',
            'sqlite' => 'sqlite',
            'oci' => 'oci',
            default => 'mysql',
        };
    }

    private function configurePdo(): void
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    private function runMysqlInitStatements(): void
    {
        foreach (DbSqlHelper::mysqlInitStatements() as $initSql) {
            $this->pdo->exec($initSql);
        }
    }

    /**
     * Kurulum sihirbazı — config.local.php olmadan geçici PDO bağlantısı.
     * getInstance() singleton'ını etkilemez.
     *
     * @throws \RuntimeException
     */
    public static function createInstallConnection(
        string $host,
        string $user,
        string $pass,
        string $dbName = '',
        string $prefix = 'esh_',
        string $port = '',
        string $driver = 'mysql'
    ): self {
        $db = new self(false);
        $db->installConfig = [
            'host' => $host,
            'user' => $user,
            'pass' => $pass,
            'name' => $dbName,
            'prefix' => $prefix,
            'port' => $port,
            'driver' => DbSqlHelper::normalizeDbDriver($driver),
        ];
        try {
            $db->connectInstall();
        } catch (Exception $e) {
            throw new \RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
        }

        return $db;
    }

    private function connectInstall(): void
    {
        $c = $this->installConfig;
        if ($c === null) {
            throw new \LogicException('Kurulum bağlantı yapılandırması eksik.');
        }
        $this->driver = $c['driver'];
        $this->_debug = 0;
        $this->_slowQueryThresholdMs = 500;
        $dsn = DbSqlHelper::buildInstallPdoDsn($c['driver'], $c['host'], $c['name'], $c['port']);
        if ($c['driver'] === 'sqlite') {
            $this->pdo = new PDO($dsn);
        } else {
            $this->pdo = new PDO($dsn, $c['user'], $c['pass']);
        }
        $this->configurePdo();
        if ($c['driver'] === 'mysql') {
            $this->runMysqlInitStatements();
        }
        if ($c['driver'] === 'sqlite') {
            $this->pdo->exec('PRAGMA foreign_keys = ON');
        }
    }

    public static function getInstance() {
        if (self::$instance === null) { self::$instance = new self(); }
        return self::$instance;
    }

    private function resolveTableName(string $table): ?string
    {
        $table = $this->replacePrefix(trim($table));
        if (DbSqlHelper::isValidTableIdentifier($table)) {
            return $table;
        }
        $this->_errorNum = 0;
        $this->_errorMsg = 'Geçersiz tablo adı: ' . $table;

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setColumnValidationError(array $data): void
    {
        foreach ($data as $k => $v) {
            if ($k === '' || $k[0] === '_') {
                continue;
            }
            if (!DbSqlHelper::isValidColumnIdentifier((string) $k)) {
                $this->_errorNum = 0;
                $this->_errorMsg = 'Geçersiz sütun adı: ' . $k;

                return;
            }
        }
    }

    /**
     * @param array<int|string, mixed> $params
     */
    private function runPreparedStatement(string $sql, array $params = [], string $errorContext = 'Sorgu Hatası'): bool
    {
        $this->clearErrors();
        $this->_sql = $sql;
        $logSql = $this->formatPreparedLogEntry($sql, $params);
        $started = microtime(true);
        try {
            $stmt = $this->pdo->prepare($sql);
            $ok = $stmt->execute($params);
            $this->stmt = $stmt;
            $this->recordQueryTiming($logSql, (microtime(true) - $started) * 1000);

            return (bool) $ok;
        } catch (Exception $e) {
            $this->recordQueryTiming($logSql, (microtime(true) - $started) * 1000);
            $this->handleError($e, $errorContext);

            return false;
        }
    }

    /**
     * Hata Yönetimi: Hem dosyaya yazar hem sınıf değişkenlerini doldurur.
     */
    private function handleError($e, $context = "Sorgu Hatası") {
        $this->_errorNum = $e->getCode();
        $this->_errorMsg = $e->getMessage();

        $logPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
        $logFile = $logPath . 'db_errors.log';

        $time = date('Y-m-d H:i:s');
        $requestLine = $this->formatLogRequestLine();
        $originLine = '  İstisna-konumu: ' . $e->getFile() . ':' . $e->getLine();
        $callerLine = $this->formatLogFirstCallerOutsideDb();
        $sqlLog = $this->truncateForLog((string) $this->_sql, 8000);

        $traceStr = $this->truncateForLog(str_replace(["\r\n", "\r"], "\n", $e->getTraceAsString()), 12000);

        $logMessage = '[' . $time . '] ' . $context . $requestLine . PHP_EOL
            . $originLine . PHP_EOL
            . '  Mesaj: ' . $this->_errorMsg . PHP_EOL
            . '  Kod: ' . $this->_errorNum . PHP_EOL
            . '  SQL: ' . $sqlLog . PHP_EOL
            . ($callerLine !== '' ? $callerLine . PHP_EOL : '')
            . '  Trace:' . PHP_EOL
            . '  ' . str_replace("\n", "\n  ", $traceStr) . PHP_EOL
            . str_repeat('-', 80) . PHP_EOL;

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    /**
     * HTTP isteği veya CLI betiği için tek satır özet (günlük).
     */
    private function formatLogRequestLine(): string {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            $argv = $_SERVER['argv'] ?? [];
            $cmd = is_array($argv) ? implode(' ', array_map('strval', $argv)) : '';
            $cmd = $cmd !== '' ? $this->truncateForLog($cmd, 500) : '(argv yok)';

            return ' | Ortam: CLI | ' . $cmd;
        }
        $method = isset($_SERVER['REQUEST_METHOD']) ? (string) $_SERVER['REQUEST_METHOD'] : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
        if ($uri === '' && $method === '') {
            return ' | Ortam: WEB (URI bilinmiyor)';
        }
        $https = !empty($_SERVER['HTTPS']) && (string) $_SERVER['HTTPS'] !== 'off';
        $https = $https || (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
        $scheme = $https ? 'https' : 'http';
        $addr = $host !== '' ? ($scheme . '://' . $host . $uri) : $uri;

        return ' | Ortam: WEB | ' . $method . ' ' . $this->truncateForLog($addr, 2000);
    }

    /**
     * Database sınıfı dışındaki ilk yığın çerçevesi (dosya:satır + çağrı).
     */
    private function formatLogFirstCallerOutsideDb(): string {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
        foreach ($trace as $frame) {
            $class = isset($frame['class']) ? (string) $frame['class'] : '';
            if ($class === self::class) {
                continue;
            }
            $file = isset($frame['file']) ? (string) $frame['file'] : '';
            $line = isset($frame['line']) ? (string) $frame['line'] : '?';
            $type = isset($frame['type']) ? (string) $frame['type'] : '';
            $func = isset($frame['function']) ? (string) $frame['function'] : '';
            $label = ($class !== '' ? $class . $type . $func : $func);
            if ($label === '' && $file === '') {
                continue;
            }

            return '  İlk-uygulama-çerçevesi: ' . $label . ' @ ' . ($file !== '' ? $file . ':' . $line : '?');
        }

        return '';
    }

    private function truncateForLog(string $s, int $max): string {
        if ($max <= 0 || strlen($s) <= $max) {
            return $s;
        }

        return substr($s, 0, $max) . ' ... [kesildi, uzunluk=' . strlen($s) . ']';
    }

    /**
     * DB_DEBUG günlüğü için SQL + maskelenmiş parametre özeti.
     *
     * @param array<int|string, mixed> $params
     */
    private function formatPreparedLogEntry(string $sql, array $params): string
    {
        if ($params === []) {
            return $sql;
        }
        $masked = [];
        foreach ($params as $k => $v) {
            $key = is_int($k) ? (string) $k : (string) $k;
            if (preg_match('/pass|secret|token|pwd/i', $key)) {
                $masked[$k] = '[masked]';
            } elseif (is_string($v) && strlen($v) > 200) {
                $masked[$k] = substr($v, 0, 200) . '...';
            } else {
                $masked[$k] = $v;
            }
        }
        $encoded = json_encode($masked, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            $encoded = '[params encode failed]';
        }

        return $sql . ' | params: ' . $encoded;
    }

    /**
     * Sorgu süresini kaydeder; DB_DEBUG açıkken bellek ile _log'a ekler.
     * Eşik aşıldığında yavaş sorgu dosyasına yazar (DB_DEBUG gerekmez).
     */
    private function recordQueryTiming(string $sql, float $durationMs): void
    {
        $memoryMb = round(memory_get_usage(true) / 1024 / 1024, 2);
        $durationMs = round($durationMs, 2);
        $slow = $this->_slowQueryThresholdMs > 0 && $durationMs >= $this->_slowQueryThresholdMs;
        if ($this->_debug) {
            $this->_log[] = [
                'sql' => $sql,
                'duration_ms' => $durationMs,
                'memory_mb' => $memoryMb,
                'slow' => $slow,
            ];
        }
        if ($slow) {
            $this->writeSlowQueryLog($sql, $durationMs, $memoryMb);
        }
    }

    /**
     * Yavaş sorgular — logs/db_slow_queries.log (eşik aşıldığında; DB_DEBUG gerekmez).
     */
    private function writeSlowQueryLog(string $sql, float $durationMs, float $memoryMb): void
    {
        $logPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
        $logFile = $logPath . 'db_slow_queries.log';
        $time = date('Y-m-d H:i:s');
        $requestLine = $this->formatLogRequestLine();
        $callerLine = $this->formatLogFirstCallerOutsideDb();
        $sqlLog = $this->truncateForLog($sql, 8000);

        $logMessage = '[' . $time . '] Yavaş sorgu (' . $durationMs . ' ms, eşik '
            . $this->_slowQueryThresholdMs . ' ms)' . $requestLine . PHP_EOL
            . '  Bellek: ' . $memoryMb . ' MB' . PHP_EOL
            . '  SQL: ' . $sqlLog . PHP_EOL
            . ($callerLine !== '' ? $callerLine . PHP_EOL : '')
            . str_repeat('-', 80) . PHP_EOL;

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    private function clearErrors() {
        $this->_errorNum = 0;
        $this->_errorMsg = '';
    }

    // --- SORGUBALAMA VE ÇALIŞTIRMA ---

    /**
     * Parametreli sorgu çalıştırır (#__ önek değişimi, hata günlüğü ve stmt ataması ile).
     *
     * @param array<int|string, mixed> $params PDO bind değerleri (sıralı veya :adlandırılmış)
     */
    public function executePrepared(string $sql, array $params = []): bool
    {
        return $this->runPreparedStatement($this->replacePrefix($sql), $params);
    }

    /**
     * Parametreli sorgudan tek satır (assoc) döndürür.
     *
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetchOnePrepared(string $sql, array $params = []): ?array
    {
        if (!$this->executePrepared($sql, $params) || !$this->stmt) {
            return null;
        }
        $row = $this->stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Parametreli sorgudan tüm satırları (assoc) döndürür.
     *
     * @param array<int|string, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function fetchAllPrepared(string $sql, array $params = []): array
    {
        if (!$this->executePrepared($sql, $params) || !$this->stmt) {
            return [];
        }
        $rows = $this->stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows === false ? [] : $rows;
    }

    /**
     * Parametreli sorgudan tek satır nesne (stdClass) döndürür.
     *
     * @param array<int|string, mixed> $params
     */
    public function fetchObjectPrepared(string $sql, array $params = []): ?object
    {
        if (!$this->executePrepared($sql, $params) || !$this->stmt) {
            return null;
        }
        $row = $this->stmt->fetch(PDO::FETCH_OBJ);

        return $row === false ? null : $row;
    }

    /**
     * Parametreli sorgudan tüm satırları nesne listesi olarak döndürür.
     *
     * @param array<int|string, mixed> $params
     * @return list<object>
     */
    public function fetchObjectListPrepared(string $sql, array $params = []): array
    {
        if (!$this->executePrepared($sql, $params) || !$this->stmt) {
            return [];
        }
        $rows = $this->stmt->fetchAll(PDO::FETCH_OBJ);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Parametreli sorgunun ilk sütun değerini döndürür (COUNT, MAX vb.).
     *
     * @param array<int|string, mixed> $params
     */
    public function loadResultPrepared(string $sql, array $params = []): mixed
    {
        if (!$this->executePrepared($sql, $params) || !$this->stmt) {
            return null;
        }

        return $this->stmt->fetchColumn();
    }

    /**
     * Parametreli sorgunun tek sütununu liste olarak döndürür.
     *
     * @param array<int|string, mixed> $params
     * @return list<mixed>
     */
    public function fetchColumnListPrepared(string $sql, array $params = [], int $colIndex = 0): array
    {
        if (!$this->executePrepared($sql, $params) || !$this->stmt) {
            return [];
        }
        $rows = $this->stmt->fetchAll(PDO::FETCH_COLUMN, $colIndex);

        return $rows === false ? [] : $rows;
    }

    /**
     * Güvenli INSERT; başarıda lastInsertId döner.
     *
     * @param array<string, mixed> $data
     */
    public function insertPrepared(string $table, array $data): int|false
    {
        $tableName = $this->resolveTableName($table);
        if ($tableName === null) {
            return false;
        }
        $built = DbSqlHelper::buildInsertSql($tableName, $data);
        if ($built === null) {
            $this->setColumnValidationError($data);

            return false;
        }
        if (!$this->runPreparedStatement($built['sql'], $built['params'], 'insertPrepared Hatası')) {
            return false;
        }

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Güvenli UPDATE; $whereSql örn. "id = ?" veya "status = ? AND id = ?".
     *
     * @param array<string, mixed> $data
     * @param array<int|string, mixed> $whereParams
     */
    public function updatePrepared(string $table, array $data, string $whereSql, array $whereParams = []): bool
    {
        $tableName = $this->resolveTableName($table);
        if ($tableName === null) {
            return false;
        }
        $whereSql = trim($whereSql);
        if ($whereSql === '') {
            $this->_errorNum = 0;
            $this->_errorMsg = 'updatePrepared: WHERE koşulu boş olamaz';

            return false;
        }
        $built = DbSqlHelper::buildUpdateSql($tableName, $data, $whereSql);
        if ($built === null) {
            $this->setColumnValidationError($data);

            return false;
        }

        return $this->runPreparedStatement($built['sql'], array_merge($built['params'], $whereParams), 'updatePrepared Hatası');
    }

    /**
     * IN (?,?,?) için placeholder dizisi. Boş dizi için whereInClause() kullanın.
     *
     * @param list<mixed> $values
     */
    public function whereInPlaceholders(array $values): string
    {
        return DbSqlHelper::whereInPlaceholders($values);
    }

    /**
     * Boş dizi için eşleşmeyen koşul (0=1); aksi halde placeholder + parametreler.
     *
     * @param list<mixed> $values
     * @return array{0: string, 1: list<mixed>}
     */
    public function whereInClause(array $values): array
    {
        return DbSqlHelper::whereInClause($values);
    }

    // --- YARDIMCI ARAÇLAR ---

    public function insertid() { return $this->pdo->lastInsertId(); }
    /** SELECT rowCount güvenilir değil (MySQL PDO); sayım için loadResultPrepared kullanın. */
    public function affectedRows() { return $this->stmt ? $this->stmt->rowCount() : 0; }
    public function getAffectedRows() { return $this->affectedRows(); } // Alias
    
    public function quote($text) { return $this->pdo->quote($text ?? ''); }

    public function replacePrefix($sql, $prefix = '#__') {
        if ($this->installConfig !== null) {
            return DbSqlHelper::replacePrefix((string) $sql, (string) $prefix, $this->installConfig['prefix']);
        }

        return DbSqlHelper::replacePrefix((string) $sql, (string) $prefix);
    }

    /**
     * Kurulum: schema/seed SQL dosyalarında #__ ve esh_ öneklerini hedef öneke çevirir.
     */
    private function prepareInstallSql(string $sql, bool $fromMysqlSeed = false): string
    {
        if ($this->installConfig === null) {
            return $this->replacePrefix($sql);
        }

        $prefix = $this->installConfig['prefix'];
        $driver = $this->installConfig['driver'];
        if ($fromMysqlSeed && $driver !== 'mysql') {
            return DbSqlHelper::adaptMysqlSeedSql($sql, $prefix, $driver);
        }

        return DbSqlHelper::remapSqlFilePrefix($sql, $prefix);
    }

    /**
     * SQL Server seed INSERT — açık [id] sütunu varsa IDENTITY_INSERT ile çalıştırır.
     */
    private function execSqlServerInstallStatement(string $statement): void
    {
        if (preg_match('/^\s*INSERT\s+INTO\s+(?:\[dbo\]\.)?\[([^\]]+)\]\s*\([^)]*\[id\]/is', $statement, $matches)) {
            $qualified = '[dbo].[' . $matches[1] . ']';
            $this->pdo->exec('SET IDENTITY_INSERT ' . $qualified . ' ON');
            try {
                $this->pdo->exec($statement);
            } finally {
                $this->pdo->exec('SET IDENTITY_INSERT ' . $qualified . ' OFF');
            }

            return;
        }

        $this->pdo->exec($statement);
    }

    public function getErrorMsg() { return $this->_errorMsg; }

    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool {
        return $this->pdo->commit();
    }

    public function rollBack(): bool {
        return $this->pdo->rollBack();
    }

    /**
     * İşlemi transaction içinde çalıştırır; başarıda commit, istisnada rollback.
     * Callable false dönerse commit edilmez (rollback).
     *
     * @param callable(self): mixed $callback
     * @return mixed Callable dönüş değeri; false ise rollback sonrası false
     * @throws \Throwable Callable istisna fırlatırsa rollback sonrası yeniden fırlatılır
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            if ($result === false) {
                if ($this->pdo->inTransaction()) {
                    $this->rollBack();
                }

                return false;
            }
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->rollBack();
            }

            throw $e;
        }
    }
    
    /**
     * Debug modu aktifse tüm sorgu günlüğünü döndürür.
     *
     * @return list<array{sql: string, duration_ms: float, memory_mb: float, slow: bool}>
     */
    public function getQueryLog() {
        return $this->_log;
    }

    /** DB_SLOW_QUERY_MS yavaş sorgu eşiği (ms); DB_DEBUG gerekmez. */
    public function getSlowQueryThresholdMs(): int
    {
        return $this->_slowQueryThresholdMs;
    }

    /**
     * Debug modunun durumunu kontrol eder
     */
    public function isDebug() {
        return $this->_debug == 1;
    }

    /**
     * Önek değişimi, hata günlüğü ve yavaş sorgu izleme ile DDL/exec.
     * getPdo()->exec() yerine tercih edin.
     */
    public function execLogged(string $sql): bool
    {
        $this->clearErrors();
        $sql = $this->prepareInstallSql($sql);
        $this->_sql = $sql;
        $started = microtime(true);
        try {
            $this->pdo->exec($sql);
            $this->stmt = null;
            $this->recordQueryTiming($sql, (microtime(true) - $started) * 1000);

            return true;
        } catch (Exception $e) {
            $this->recordQueryTiming($sql, (microtime(true) - $started) * 1000);
            $this->handleError($e, 'exec Hatası');

            return false;
        }
    }

    /**
     * Çok ifadeli SQL dosyasını çalıştırır (schema.sql, seed dosyaları).
     *
     * @throws \RuntimeException
     */
    public function execSqlFile(string $path, string $label, bool $fromMysqlSeed = false): void
    {
        $sql = file_get_contents($path);
        if ($sql === false || trim($sql) === '') {
            throw new \RuntimeException($label . ' okunamadı: ' . $path);
        }
        $driver = $this->installConfig['driver'] ?? 'mysql';
        $sql = $this->prepareInstallSql($sql, $fromMysqlSeed);
        $statements = DbSqlHelper::splitSqlStatements($sql, $driver);
        if ($statements === []) {
            throw new \RuntimeException($label . ' boş veya yalnızca yorum içeriyor: ' . $path);
        }
        $this->clearErrors();
        $this->_sql = '[sql file: ' . basename($path) . ', ' . count($statements) . ' ifade]';
        $started = microtime(true);
        try {
            foreach ($statements as $index => $statement) {
                $this->_sql = $statement;
                if ($driver === 'sqlsrv' && $fromMysqlSeed) {
                    $this->execSqlServerInstallStatement($statement);
                } else {
                    $this->pdo->exec($statement);
                }
            }
            $this->stmt = null;
            $this->recordQueryTiming('[sql file: ' . basename($path) . ']', (microtime(true) - $started) * 1000);
        } catch (Exception $e) {
            $this->recordQueryTiming($this->_sql, (microtime(true) - $started) * 1000);
            $this->handleError($e, $label . ' hatası');
            $stmtNo = isset($index) ? ((int) $index + 1) : 0;
            throw new \RuntimeException(
                $label . ' hatası (ifade ' . $stmtNo . '/' . count($statements) . '): ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    /** Bakım / yedek servisi için ham PDO (execLogged mümkünse onu kullanın). */
    public function getPdo(): PDO {
        return $this->pdo;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }
}