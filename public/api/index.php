<?php
declare(strict_types=1);

/**
 * REST API v1 giriş noktası — oturum çerezi gerekmez; Bearer token kullanılır.
 *
 * Örnek:
 *   GET /public/api/v1/patients
 *   Authorization: Bearer esh_live_...
 */

$root = dirname(__DIR__, 2);

$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';

require_once $root . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'App\\';
    $base = $root . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $file = $base . str_replace('\\', '/', substr($class, $len)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

\App\Helpers\AppSettings::boot();

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

\App\Helpers\Api\ApiRouter::dispatch();
