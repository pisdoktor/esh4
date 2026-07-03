<?php
declare(strict_types=1);
/**
 * Geçici API smoke — CLI: php tools/test_api_v1.php [token]
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
\App\Helpers\AppSettings::boot();

$token = $argv[1] ?? '';
if ($token === '') {
    fwrite(STDERR, "Usage: php tools/test_api_v1.php TOKEN\n");
    exit(1);
}

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/public/api/v1/patients?per_page=2';
$_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;

ob_start();
\App\Helpers\Api\ApiRouter::dispatch();
$out = ob_get_clean();
echo $out . PHP_EOL;
$decoded = json_decode($out, true);
exit(is_array($decoded) && !empty($decoded['ok']) ? 0 : 1);
