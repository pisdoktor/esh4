<?php
declare(strict_types=1);

/**
 * ESYS / USBS export CLI ortak bootstrap.
 */

require_once dirname(__DIR__) . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $base = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $file = $base . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$argv = $GLOBALS['argv'] ?? [];

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--kurum-id=')) {
        $kid = (int) substr($arg, strlen('--kurum-id='));
        if ($kid > 0) {
            $_SESSION[\App\Helpers\TenantContext::SESSION_KURUM_ID] = $kid;
            $_SESSION['isadmin_level'] = \App\Helpers\AuthHelper::ROLE_ADMIN;
            $_SESSION['isadmin'] = true;
        }
    }
}

function bridge_export_cli_write(string $json, string $prefix): string
{
    $argv = $GLOBALS['argv'] ?? [];
    $output = '';
    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--output=')) {
            $output = substr($arg, strlen('--output='));
            break;
        }
    }
    if ($output === '') {
        $dir = dirname(__DIR__) . '/storage/exports';
        if (!\App\Helpers\StorageHardeningHelper::ensureExportsDirectory(dirname(__DIR__))) {
            fwrite(STDERR, "storage/exports oluşturulamadı.\n");
            return '';
        }
        $output = $dir . '/' . $prefix . '-export-' . date('Y-m-d-His') . '.json';
    }
    $output = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $output);
    $parent = dirname($output);
    if (!is_dir($parent)) {
        @mkdir($parent, 0750, true);
    }
    if (@file_put_contents($output, $json) === false) {
        return '';
    }

    return $output;
}
