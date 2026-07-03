<?php
declare(strict_types=1);

/**
 * PHPUnit smoke test bootstrap — config, oturum, autoload.
 */

$eshTestRoot = dirname(__DIR__);

$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';

require_once $eshTestRoot . '/config/config.php';

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', $eshTestRoot);
}
unset($eshTestRoot);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(static function (string $class): void {
    $map = [
        'App\\' => ROOT_PATH . '/app/',
        'Tests\\' => ROOT_PATH . '/tests/',
    ];
    foreach ($map as $prefix => $base) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $file = $base . str_replace('\\', '/', substr($class, $len)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
