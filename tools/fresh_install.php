<?php
declare(strict_types=1);

/**
 * Sıfırdan UUID şeması ile veritabanı kurulumu (config.local.php kullanır).
 * Kullanım: php tools/fresh_install.php [--keep-lock]
 */
$root = dirname(__DIR__);

require $root . '/config/config.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = ROOT_PATH . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$keepLock = in_array('--keep-lock', $argv ?? [], true);

if (!$keepLock) {
    $lock = $root . '/config/install.lock';
    if (is_file($lock)) {
        unlink($lock);
        echo "install.lock kaldirildi.\n";
    }
}

$result = \App\Install\Installer::runInstall(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME,
    true,
    rtrim(SITEURL, '/'),
    'Platform Yöneticisi',
    DB_PREFIX,
    DB_DRIVER,
    DB_PORT
);

if (!$result['ok']) {
    fwrite(STDERR, 'Kurulum basarisiz: ' . ($result['message'] ?? '') . "\n");
    exit(1);
}

echo "Kurulum tamam.\n";
echo $result['message'] . "\n";
echo "Giris: admin / Admin123 (platform owner UUID ile olusturuldu)\n";
