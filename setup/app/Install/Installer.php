<?php
declare(strict_types=1);

namespace App\Install;

use App\Core\Database;
use App\Core\DbSqlHelper;
use App\Helpers\IdHelper;

require_once __DIR__ . '/../Core/DbSqlHelper.php';

/**
 * Web kurulum sihirbazı (`public/install.php`) için yardımcılar.
 */
final class Installer
{
    public const SUPERADMIN_USER = 'admin';
    public const SUPERADMIN_PASS = 'Admin123';
    public const SUPERADMIN_NAME = 'Bölge Yöneticisi';
    public const SUPERADMIN_UNVAN = 'doktor';
    public const SUPERADMIN_EMAIL = 'esh@esh.local';
    public const PLATFORM_OWNER_ISADMIN = 3;
    /** @deprecated Kurulum hesabı artık PLATFORM_OWNER_ISADMIN (3) kullanır */
    public const SUPERADMIN_ISADMIN = 3;
    public const SUPERADMIN_TC = '10000000146';
    public const SUPERADMIN_REGISTER_DATE = '2024-03-14 00:00:00';
    public const DEFAULT_DB_PREFIX = 'esh_';

    public static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function lockFilePath(): string
    {
        return self::projectRoot() . '/config/install.lock';
    }

    public static function localConfigPath(): string
    {
        return self::projectRoot() . '/config/config.local.php';
    }

    public static function schemaPath(): string
    {
        $driver = strtolower((string) getenv('ESH_INSTALL_DB_DRIVER'));

        return self::schemaPathForDriver($driver !== '' ? $driver : 'mysql');
    }

    public static function schemaPathForDriver(string $driver): string
    {
        return DbSqlHelper::schemaPathForDriver($driver, self::projectRoot());
    }

    /**
     * schema.sql sonrası sırayla çalıştırılacak seed SQL dosyaları.
     *
     * @return list<string> mutlak dosya yolları
     */
    public static function seedSqlPaths(): array
    {
        $manifest = self::projectRoot() . '/database/seed/install_seeds.php';
        if (!is_file($manifest)) {
            return [];
        }
        $list = require $manifest;
        if (!is_array($list)) {
            return [];
        }
        $paths = [];
        $seedDir = self::projectRoot() . '/database/seed';
        foreach ($list as $file) {
            if (!is_string($file) || $file === '') {
                continue;
            }
            $path = $seedDir . '/' . $file;
            if (is_file($path)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * Kurulum sırasında App\Core\Database katmanını yükler (composer/autoload yok).
     */
    private static function bootstrapDatabaseLayer(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        require_once __DIR__ . '/../Core/Database.php';
        $loaded = true;
    }

    public static function isLocked(): bool
    {
        return is_file(self::lockFilePath());
    }

    /**
     * PHP sürümü ve config/ yazılabilirliği (sürücüden bağımsız).
     *
     * @return list<string>
     */
    public static function hardPrerequisiteErrors(): array
    {
        $errors = [];
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            $errors[] = 'PHP 7.4 veya üzeri gerekli (şu an: ' . PHP_VERSION . ').';
        }

        $configDir = self::projectRoot() . '/config';
        if (!is_dir($configDir) || !is_writable($configDir)) {
            $errors[] = 'config/ dizini yazılabilir olmalı (config.local.php ve install.lock için).';
        }

        return $errors;
    }

    private static function installDriverSelectLabel(string $driver): string
    {
        return match (DbSqlHelper::normalizeDbDriver($driver)) {
            'mysql' => 'MySQL / MariaDB',
            'sqlsrv' => 'Microsoft SQL Server',
            'pgsql' => 'PostgreSQL',
            'sqlite' => 'SQLite',
            'oci' => 'Oracle',
            default => DbSqlHelper::driverLabel($driver),
        };
    }

    private static function isPdoDriverExtensionReady(string $driver): bool
    {
        $ext = DbSqlHelper::pdoExtensionForDriver($driver);
        $pdoName = substr($ext, 4);
        if (!extension_loaded($ext)) {
            return false;
        }
        if (!class_exists(\PDO::class, false)) {
            return false;
        }

        return in_array($pdoName, \PDO::getAvailableDrivers(), true);
    }

    /**
     * Kurulum sihirbazı için sunucu taraması (PDO eklentisi + şema dosyası).
     *
     * @return list<array{
     *   driver: string,
     *   label: string,
     *   select_label: string,
     *   extension: string,
     *   extension_ok: bool,
     *   schema_file: string,
     *   schema_ok: bool,
     *   available: bool,
     *   status_message: string
     * }>
     */
    public static function installDriverCapabilities(): array
    {
        $list = [];
        foreach (DbSqlHelper::SUPPORTED_DRIVERS as $driver) {
            $extension = DbSqlHelper::pdoExtensionForDriver($driver);
            $schemaFile = DbSqlHelper::schemaFileNameForDriver($driver);
            $extensionOk = self::isPdoDriverExtensionReady($driver);
            $schemaOk = is_readable(self::schemaPathForDriver($driver));
            $available = $extensionOk && $schemaOk;
            if ($available) {
                $statusMessage = 'Kuruluma hazır';
            } elseif (!$extensionOk && !$schemaOk) {
                $statusMessage = $extension . ' yüklü değil; ' . DbSqlHelper::schemaRelativePathForDriver($driver) . ' okunamıyor';
            } elseif (!$extensionOk) {
                $statusMessage = $extension . ' yüklü değil';
            } else {
                $statusMessage = DbSqlHelper::schemaRelativePathForDriver($driver) . ' okunamıyor';
            }

            $list[] = [
                'driver' => $driver,
                'label' => DbSqlHelper::driverLabel($driver),
                'select_label' => self::installDriverSelectLabel($driver),
                'extension' => $extension,
                'extension_ok' => $extensionOk,
                'schema_file' => $schemaFile,
                'schema_ok' => $schemaOk,
                'available' => $available,
                'status_message' => $statusMessage,
            ];
        }

        return $list;
    }

    /**
     * @return list<string>
     */
    public static function installAvailableDrivers(): array
    {
        $drivers = [];
        foreach (self::installDriverCapabilities() as $cap) {
            if ($cap['available']) {
                $drivers[] = $cap['driver'];
            }
        }

        return $drivers;
    }

    public static function isInstallDriverAvailable(string $driver): bool
    {
        $driver = DbSqlHelper::normalizeDbDriver($driver);
        foreach (self::installDriverCapabilities() as $cap) {
            if ($cap['driver'] === $driver) {
                return $cap['available'];
            }
        }

        return false;
    }

    /**
     * Veritabanı sürücüsü önkoşulları.
     *
     * @return list<string>
     */
    public static function driverPrerequisiteErrors(?string $dbDriver = null): array
    {
        $errors = [];
        $available = self::installAvailableDrivers();

        if ($dbDriver !== null && $dbDriver !== '') {
            $driver = DbSqlHelper::normalizeDbDriver($dbDriver);
            if (!self::isInstallDriverAvailable($driver)) {
                foreach (self::installDriverCapabilities() as $cap) {
                    if ($cap['driver'] === $driver) {
                        $errors[] = $cap['select_label'] . ' ile kurulum yapılamaz: ' . $cap['status_message'];
                        break;
                    }
                }
            }

            return $errors;
        }

        if ($available === []) {
            $errors[] = 'Sunucuda kurulum için uygun veritabanı sürücüsü bulunamadı.';
            foreach (self::installDriverCapabilities() as $cap) {
                if (!$cap['available']) {
                    $errors[] = $cap['select_label'] . ': ' . $cap['status_message'];
                }
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    public static function prerequisiteErrors(?string $dbDriver = null): array
    {
        return array_merge(
            self::hardPrerequisiteErrors(),
            self::driverPrerequisiteErrors($dbDriver)
        );
    }

    public static function validateDbName(string $name, string $driver = 'mysql'): bool
    {
        $driver = DbSqlHelper::normalizeDbDriver($driver);
        if ($driver === 'sqlite') {
            if ($name === '') {
                return false;
            }
            $normalized = str_replace('\\', '/', $name);

            return (bool) preg_match('#^[a-zA-Z0-9_./-]{1,255}$#', $normalized);
        }
        if ($driver === 'oci') {
            return $name !== '' && (bool) preg_match('/^[a-zA-Z0-9_$#]{1,64}$/', $name);
        }

        return (bool) preg_match('/^[a-zA-Z0-9_]{1,64}$/', $name);
    }

    public static function validateDbPrefix(string $prefix): bool
    {
        self::bootstrapDatabaseLayer();

        return \App\Core\DbSqlHelper::isValidTablePrefix($prefix);
    }

    public static function validateUsername(string $u): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9._-]{2,64}$/', $u);
    }

    /**
     * Kurulum API / sihirbaz girdisini doğrular.
     *
     * @param array<string, mixed> $post
     * @return array{ok:true, data:array<string, mixed>}|array{ok:false, message:string}
     */
    public static function parseInstallInput(array $post): array
    {
        $dbHost = trim((string) ($post['db_host'] ?? ''));
        $dbUser = trim((string) ($post['db_user'] ?? ''));
        $dbPass = (string) ($post['db_pass'] ?? '');
        $dbName = trim((string) ($post['db_name'] ?? ''));
        $dbPrefix = trim((string) ($post['db_prefix'] ?? self::DEFAULT_DB_PREFIX));
        $dbDriver = DbSqlHelper::normalizeDbDriver((string) ($post['db_driver'] ?? 'mysql'));
        $dbPort = trim((string) ($post['db_port'] ?? ''));
        $createDatabase = !empty($post['create_database']);
        $siteUrl = rtrim(trim((string) ($post['site_url'] ?? '')), '/');
        $adminDisplayName = trim((string) ($post['admin_name'] ?? self::SUPERADMIN_NAME));
        $adminUnvan = trim((string) ($post['admin_unvan'] ?? self::SUPERADMIN_UNVAN));
        $adminEmail = trim((string) ($post['admin_email'] ?? self::SUPERADMIN_EMAIL));

        if ($dbHost === '' && $dbDriver !== 'sqlite') {
            return ['ok' => false, 'message' => 'Veritabanı sunucusu boş olamaz.'];
        }
        if (!DbSqlHelper::isValidDbDriver($dbDriver)) {
            return ['ok' => false, 'message' => 'Geçersiz veritabanı sürücüsü.'];
        }
        if ($dbPort !== '' && !ctype_digit($dbPort)) {
            return ['ok' => false, 'message' => 'Port yalnızca rakam içermelidir.'];
        }
        if ($dbName === '') {
            return ['ok' => false, 'message' => 'Veritabanı adı boş olamaz.'];
        }
        if (!self::validateDbName($dbName, $dbDriver)) {
            return ['ok' => false, 'message' => 'Veritabanı adı / dosya yolu geçersiz.'];
        }
        $dbPrefix = $dbPrefix !== '' ? $dbPrefix : self::DEFAULT_DB_PREFIX;
        if (!self::validateDbPrefix($dbPrefix)) {
            return ['ok' => false, 'message' => 'Tablo öneki yalnızca harf, rakam ve alt çizgi içerebilir (en fazla 32 karakter).'];
        }
        if ($siteUrl === '') {
            return ['ok' => false, 'message' => 'Site URL boş olamaz.'];
        }
        if ($adminDisplayName === '') {
            $adminDisplayName = self::SUPERADMIN_NAME;
        }
        if ($adminUnvan === '') {
            $adminUnvan = self::SUPERADMIN_UNVAN;
        }
        if (!self::validateAdminUnvan($adminUnvan)) {
            return ['ok' => false, 'message' => 'Geçersiz ünvan seçimi.'];
        }
        if ($adminEmail === '') {
            $adminEmail = self::SUPERADMIN_EMAIL;
        }
        if (!self::validateAdminEmail($adminEmail)) {
            return ['ok' => false, 'message' => 'Geçersiz e-posta adresi.'];
        }

        return [
            'ok' => true,
            'data' => [
                'db_host' => $dbHost,
                'db_user' => $dbUser,
                'db_pass' => $dbPass,
                'db_name' => $dbName,
                'db_prefix' => $dbPrefix,
                'db_driver' => $dbDriver,
                'db_port' => $dbPort,
                'create_database' => $createDatabase,
                'site_url' => $siteUrl,
                'admin_display_name' => $adminDisplayName,
                'admin_unvan' => $adminUnvan,
                'admin_email' => $adminEmail,
            ],
        ];
    }

    public static function validateAdminUnvan(string $unvan): bool
    {
        return array_key_exists($unvan, self::adminUnvanChoices());
    }

    public static function validateAdminEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * @return array<string, string>
     */
    public static function adminUnvanChoices(): array
    {
        return [
            'uzman_doktor' => 'Uzman Doktor',
            'doktor' => 'Doktor',
            'dis_hekimi' => 'Diş Hekimi',
            'hemsire' => 'Hemşire',
            'ebe' => 'Ebe',
            'saglik_memuru' => 'Sağlık Memuru',
            'toplum_sagligi_teknisyeni' => 'Toplum Sağlığı Teknisyeni',
            'tekniker' => 'Tekniker',
            'evde_hasta_bakim_teknikeri' => 'Evde Hasta Bakım Teknikeri',
            'yasli_bakim_teknikeri' => 'Yaşlı Bakım Teknikeri',
            'agiz_dis_sagligi_teknikeri' => 'Ağız ve Diş Sağlığı Teknikeri',
            'dis_protez_teknikeri' => 'Diş Protez Teknikeri',
            'yardimci_saglik_personeli' => 'Yardımcı Sağlık Personeli',
            'eczaci' => 'Eczacı',
            'gerontolog' => 'Gerontolog',
            'psikolog' => 'Psikolog',
            'sosyal_calismaci' => 'Sosyal Çalışmacı',
            'fizyoterapist' => 'Fizyoterapist',
            'diyetisyen' => 'Diyetisyen',
            'tibbi_sekreter' => 'Tıbbi Sekreter',
            'saglik_yoneticisi' => 'Sağlık Yöneticisi',
            'sofor' => 'Şoför',
            'diger' => 'Diğer',
        ];
    }

    /**
     * Seed dosyaları kataloğu (UI ilerleme listesi).
     *
     * @return list<array{index:int, file:string, label:string}>
     */
    public static function seedCatalog(): array
    {
        $labels = [
            'seed_esh_rbac.sql' => 'Rol ve izin tanımları (RBAC)',
            'seed_esh_unvan_roles.sql' => 'Personel ünvan rolleri',
            'seed_esh_guvence.sql' => 'Güvence / ödeme türleri',
            'seed_esh_hastalikcat.sql' => 'Hastalık kategorileri',
            'seed_esh_hastaliklar.sql' => 'Hastalık kütüphanesi',
            'seed_esh_branslar.sql' => 'Tıp branşları',
            'seed_esh_islemler.sql' => 'Evde sağlık işlemleri',
            'seed_esh_istekler.sql' => 'Konsültasyon istek türleri',
            'seed_esh_adrestablosu.sql' => 'Adres ağacı',
            'seed_esh_adrestablosu_bolge_tier.sql' => 'Adres ağacı — bölge katmanı',
        ];
        $catalog = [];
        foreach (self::seedSqlPaths() as $index => $path) {
            $file = basename($path);
            $catalog[] = [
                'index' => $index,
                'file' => $file,
                'label' => $labels[$file] ?? $file,
            ];
        }

        return $catalog;
    }

    /**
     * Kurulum sihirbazı AJAX adımı.
     *
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    public static function runInstallStep(string $step, array $post): array
    {
        $parsed = self::parseInstallInput($post);
        if (!$parsed['ok']) {
            return ['ok' => false, 'message' => $parsed['message'], 'step' => $step];
        }
        $data = $parsed['data'];
        $seedTotal = count(self::seedCatalog());

        try {
            switch ($step) {
                case 'bootstrap':
                    return self::installStepBootstrap($data, $seedTotal);
                case 'schema':
                    return self::installStepSchema($data, $seedTotal);
                case 'seed':
                    return self::installStepSeed($data, $seedTotal, (int) ($post['seed_index'] ?? -1));
                case 'finalize':
                    return self::installStepFinalize($data, $seedTotal);
                default:
                    return ['ok' => false, 'message' => 'Geçersiz kurulum adımı.', 'step' => $step];
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'step' => $step];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function openInstallDatabase(array $data, string $dbName = ''): Database
    {
        return Database::createInstallConnection(
            (string) $data['db_host'],
            (string) $data['db_user'],
            (string) $data['db_pass'],
            $dbName,
            (string) $data['db_prefix'],
            (string) ($data['db_port'] ?? ''),
            (string) ($data['db_driver'] ?? 'mysql')
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function installStepBootstrap(array $data, int $seedTotal): array
    {
        self::bootstrapDatabaseLayer();

        $driver = DbSqlHelper::normalizeDbDriver((string) ($data['db_driver'] ?? 'mysql'));
        $driverLabel = DbSqlHelper::driverLabel($driver);

        try {
            if ($driver === 'sqlite') {
                $path = DbSqlHelper::resolveSqlitePath((string) ($data['db_host'] ?? ''), (string) $data['db_name']);
                $dir = dirname(str_replace('\\', '/', $path));
                if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
                    return ['ok' => false, 'message' => 'SQLite dizini oluşturulamadı: ' . $dir, 'step' => 'bootstrap'];
                }
                $sqlitePrep = DbSqlHelper::prepareSqliteInstallFile($path, true);
                if (!$sqlitePrep['ok']) {
                    return ['ok' => false, 'message' => (string) ($sqlitePrep['message'] ?? 'SQLite dosyası hazırlanamadı.'), 'step' => 'bootstrap'];
                }
                self::openInstallDatabase($data, (string) $data['db_name']);
            } else {
                $bootstrapDb = $driver === 'pgsql' ? 'postgres' : '';
                $serverDb = self::openInstallDatabase($data, $bootstrapDb);

                if (!empty($data['create_database'])) {
                    $dbName = (string) $data['db_name'];
                    if ($driver === 'sqlsrv') {
                        $escapedName = str_replace("'", "''", $dbName);
                        $safeBracket = str_replace(']', ']]', $dbName);
                        $createSql = "IF DB_ID(N'" . $escapedName . "') IS NULL CREATE DATABASE [" . $safeBracket . "]";
                    } elseif ($driver === 'pgsql') {
                        $safeDb = str_replace('"', '""', $dbName);
                        $createSql = 'CREATE DATABASE "' . $safeDb . '"';
                    } else {
                        $safeDb = str_replace('`', '``', $dbName);
                        $createSql = 'CREATE DATABASE IF NOT EXISTS `' . $safeDb . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci';
                    }
                    if (!$serverDb->execLogged($createSql)) {
                        return ['ok' => false, 'message' => 'Veritabanı oluşturulamadı: ' . $serverDb->getErrorMsg(), 'step' => 'bootstrap'];
                    }
                }

                self::openInstallDatabase($data, (string) $data['db_name']);
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $driverLabel . ' bağlantısı kurulamadı: ' . $e->getMessage(), 'step' => 'bootstrap'];
        }

        return [
            'ok' => true,
            'step' => 'bootstrap',
            'message' => 'Veritabanı bağlantısı hazır.',
            'percent' => self::installProgressPercent('bootstrap', 0, $seedTotal),
            'seed_total' => $seedTotal,
            'seeds' => self::seedCatalog(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function installStepSchema(array $data, int $seedTotal): array
    {
        self::bootstrapDatabaseLayer();

        if (DbSqlHelper::normalizeDbDriver((string) ($data['db_driver'] ?? 'mysql')) === 'sqlite') {
            $sqlitePath = DbSqlHelper::resolveSqlitePath((string) ($data['db_host'] ?? ''), (string) $data['db_name']);
            $sqlitePrep = DbSqlHelper::prepareSqliteInstallFile($sqlitePath, true);
            if (!$sqlitePrep['ok']) {
                return ['ok' => false, 'message' => (string) ($sqlitePrep['message'] ?? 'SQLite dosyası hazırlanamadı.'), 'step' => 'schema'];
            }
        }

        try {
            $db = self::openInstallDatabase($data, (string) $data['db_name']);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Veritabanı bağlantısı kurulamadı: ' . $e->getMessage(), 'step' => 'schema'];
        }

        $schemaPath = self::schemaPathForDriver((string) ($data['db_driver'] ?? 'mysql'));

        try {
            $db->execSqlFile($schemaPath, 'Şema');
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Şema çalıştırılırken hata: ' . $e->getMessage(), 'step' => 'schema'];
        }

        return [
            'ok' => true,
            'step' => 'schema',
            'message' => 'Veritabanı şeması oluşturuldu.',
            'percent' => self::installProgressPercent('schema', 0, $seedTotal),
            'seed_total' => $seedTotal,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function installStepSeed(array $data, int $seedTotal, int $seedIndex): array
    {
        $paths = self::seedSqlPaths();
        if ($seedIndex < 0 || $seedIndex >= count($paths)) {
            return ['ok' => false, 'message' => 'Geçersiz seed adımı.', 'step' => 'seed', 'seed_index' => $seedIndex];
        }

        @set_time_limit(0);

        self::bootstrapDatabaseLayer();

        try {
            $db = self::openInstallDatabase($data, (string) $data['db_name']);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Veritabanı bağlantısı kurulamadı: ' . $e->getMessage(), 'step' => 'seed', 'seed_index' => $seedIndex];
        }

        $seedPath = $paths[$seedIndex];
        $file = basename($seedPath);
        $catalog = self::seedCatalog();
        $label = $catalog[$seedIndex]['label'] ?? $file;

        try {
            $db->execSqlFile($seedPath, 'Seed (' . $file . ')', true);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Seed hatası: ' . $e->getMessage(),
                'step' => 'seed',
                'seed_index' => $seedIndex,
                'seed_file' => $file,
                'seed_label' => $label,
            ];
        }

        return [
            'ok' => true,
            'step' => 'seed',
            'message' => $label . ' yüklendi.',
            'percent' => self::installProgressPercent('seed', $seedIndex, $seedTotal),
            'seed_index' => $seedIndex,
            'seed_file' => $file,
            'seed_label' => $label,
            'seed_total' => $seedTotal,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function installStepFinalize(array $data, int $seedTotal): array
    {
        self::bootstrapDatabaseLayer();

        $driver = DbSqlHelper::normalizeDbDriver((string) ($data['db_driver'] ?? 'mysql'));

        try {
            $db = self::openInstallDatabase($data, (string) $data['db_name']);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Veritabanı bağlantısı kurulamadı: ' . $e->getMessage(), 'step' => 'finalize'];
        }

        $adminUser = self::SUPERADMIN_USER;
        $adminPassPlain = self::SUPERADMIN_PASS;
        $adminDisplayName = (string) $data['admin_display_name'];
        $adminUnvan = (string) ($data['admin_unvan'] ?? self::SUPERADMIN_UNVAN);
        $adminEmail = (string) ($data['admin_email'] ?? self::SUPERADMIN_EMAIL);
        $hash = password_hash($adminPassPlain, PASSWORD_DEFAULT);
        if ($hash === false) {
            return ['ok' => false, 'message' => 'Şifre hash üretilemedi.', 'step' => 'finalize'];
        }

        $adminTc = self::SUPERADMIN_TC;
        $isadmin = self::PLATFORM_OWNER_ISADMIN;
        $hasAdminRow = $db->fetchOnePrepared('SELECT 1 AS ok FROM #__users WHERE username = ?', [$adminUser]) !== null;

        $userData = [
            'username' => $adminUser,
            'password' => $hash,
            'name' => $adminDisplayName,
            'email' => $adminEmail,
            'unvan' => $adminUnvan,
            'registerDate' => self::SUPERADMIN_REGISTER_DATE,
            'activated' => 1,
            'isadmin' => $isadmin,
            'kurum_id' => null,
        ];

        if ($hasAdminRow) {
            $written = $db->updatePrepared('#__users', $userData, 'username = ?', [$adminUser]);
        } else {
            $userData['id'] = IdHelper::generateUuidV4();
            $userData['tckimlikno'] = $adminTc;
            $written = $db->insertPrepared('#__users', $userData) !== false;
        }

        if (!$written) {
            return ['ok' => false, 'message' => 'Yönetici kaydı yazılamadı: ' . $db->getErrorMsg(), 'step' => 'finalize'];
        }

        $local = [
            'db_driver' => $driver,
            'db_host' => $data['db_host'],
            'db_port' => $data['db_port'] ?? '',
            'db_user' => $data['db_user'],
            'db_pass' => $data['db_pass'],
            'db_name' => $data['db_name'],
            'db_prefix' => $data['db_prefix'],
            'siteurl' => $data['site_url'],
        ];

        $write = self::writeLocalConfig($local);
        if ($write !== true) {
            return ['ok' => false, 'message' => (string) $write, 'step' => 'finalize'];
        }

        $dirErr = self::ensureWritablePaths();
        if ($dirErr !== '') {
            return ['ok' => false, 'message' => $dirErr, 'step' => 'finalize'];
        }

        self::writeDefaultProfileAvatar();

        $lock = self::writeLockFile();
        if ($lock !== true) {
            return ['ok' => false, 'message' => (string) $lock, 'step' => 'finalize'];
        }

        return [
            'ok' => true,
            'step' => 'finalize',
            'message' => 'Kurulum tamamlandı. Giriş: ' . self::SUPERADMIN_USER . ' / ' . self::SUPERADMIN_PASS,
            'percent' => 100,
        ];
    }

    private static function installProgressPercent(string $phase, int $seedIndex, int $seedTotal): int
    {
        $total = max(1, 3 + $seedTotal);
        switch ($phase) {
            case 'bootstrap':
                $completed = 1;
                break;
            case 'schema':
                $completed = 2;
                break;
            case 'seed':
                $completed = 3 + $seedIndex;
                break;
            case 'finalize':
                $completed = 3 + $seedTotal;
                break;
            default:
                $completed = 0;
        }

        return (int) min(100, round(($completed / $total) * 100));
    }

    /**
     * @return array{ok:bool, message:string}
     */
    public static function runInstall(
        string $dbHost,
        string $dbUser,
        string $dbPass,
        string $dbName,
        bool $createDatabase,
        string $siteUrlTrimmed,
        string $adminDisplayName = self::SUPERADMIN_NAME,
        string $dbPrefix = self::DEFAULT_DB_PREFIX,
        string $dbDriver = 'mysql',
        string $dbPort = ''
    ): array {
        $post = [
            'db_host' => $dbHost,
            'db_user' => $dbUser,
            'db_pass' => $dbPass,
            'db_name' => $dbName,
            'db_prefix' => $dbPrefix,
            'db_driver' => $dbDriver,
            'db_port' => $dbPort,
            'create_database' => $createDatabase ? '1' : '',
            'site_url' => $siteUrlTrimmed,
            'admin_name' => $adminDisplayName,
        ];

        $bootstrap = self::runInstallStep('bootstrap', $post);
        if (!$bootstrap['ok']) {
            return ['ok' => false, 'message' => (string) $bootstrap['message']];
        }

        $schema = self::runInstallStep('schema', $post);
        if (!$schema['ok']) {
            return ['ok' => false, 'message' => (string) $schema['message']];
        }

        $seedTotal = (int) ($bootstrap['seed_total'] ?? 0);
        for ($i = 0; $i < $seedTotal; $i++) {
            $post['seed_index'] = (string) $i;
            $seed = self::runInstallStep('seed', $post);
            if (!$seed['ok']) {
                return ['ok' => false, 'message' => (string) $seed['message']];
            }
        }

        $finalize = self::runInstallStep('finalize', $post);
        if (!$finalize['ok']) {
            return ['ok' => false, 'message' => (string) $finalize['message']];
        }

        return ['ok' => true, 'message' => (string) $finalize['message']];
    }

    /**
     * @param array<string, mixed> $data
     * @return true|string hata metni
     */
    public static function writeLocalConfig(array $data)
    {
        $path = self::localConfigPath();
        $export = "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($data, true) . ";\n";
        if (file_put_contents($path, $export) === false) {
            return 'config/config.local.php yazılamadı.';
        }
        return true;
    }

    /**
     * @return true|string
     */
    public static function writeLockFile()
    {
        $path = self::lockFilePath();
        $payload = json_encode(
            [
                'installed_at' => date('c'),
                'php' => PHP_VERSION,
            ],
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
        if ($payload === false || file_put_contents($path, $payload . "\n") === false) {
            return 'config/install.lock oluşturulamadı.';
        }
        return true;
    }

    public static function ensureWritablePaths(): string
    {
        $root = self::projectRoot();
        $paths = [
            $root . '/storage/backups',
            $root . '/storage/data',
            $root . '/storage/exports',
            $root . '/logs',
            $root . '/public/uploads',
            $root . '/public/uploads/profile',
        ];
        foreach ($paths as $p) {
            if (!is_dir($p)) {
                if (!@mkdir($p, 0775, true)) {
                    return 'Dizin oluşturulamadı: ' . $p;
                }
            }
            if (!is_writable($p)) {
                return 'Dizin yazılabilir değil: ' . $p;
            }
        }
        if (!\App\Helpers\StorageHardeningHelper::ensureExportsDirectory($root)) {
            return 'storage/exports dizini oluşturulamadı veya .htaccess yazılamadı.';
        }
        return '';
    }

    public static function writeDefaultProfileAvatar(): void
    {
        $path = self::projectRoot() . '/public/uploads/profile/default.jpg';
        if (is_file($path) && filesize($path) > 0) {
            return;
        }
        if (extension_loaded('gd')) {
            $im = imagecreatetruecolor(64, 64);
            if ($im !== false) {
                $bg = imagecolorallocate($im, 220, 230, 240);
                imagefill($im, 0, 0, $bg);
                imagejpeg($im, $path, 85);
                imagedestroy($im);
            }
        }
        if (!is_file($path) || filesize($path) === 0) {
            $jpeg = base64_decode(
                '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQcJDwwRFA8RGBQaGhgQGhMcHR0cHB4kHBggLDAkMDBINDg0NHiwcHCQrKSksMDAwMS0xMjc3Nzc3Nzc3Nzc3N//wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAD//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//9k=',
                true
            );
            if ($jpeg !== false) {
                @file_put_contents($path, $jpeg);
            }
        }
    }

    public static function detectSiteUrlFromRequest(): string
    {
        if (empty($_SERVER['HTTP_HOST'])) {
            return '';
        }
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $https = $https || (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script = $_SERVER['SCRIPT_NAME'] ?? '/public/install.php';
        $publicDir = dirname(str_replace('\\', '/', $script));
        $basePath = dirname($publicDir);
        if ($basePath === '/' || $basePath === '.' || $basePath === '') {
            $basePath = '';
        } else {
            $basePath = rtrim($basePath, '/');
        }
        return rtrim($scheme . '://' . $host . $basePath, '/');
    }
}
