<?php
/**
 * Development router for the PHP built-in server.
 *
 * The app is normally served by Apache with the docroot at the project root and
 * reached under /public/ (assets resolve as SITEURL/public/assets). The PHP
 * built-in server has no mod_rewrite, so this router reproduces the rewrites in
 * public/.htaccess: it serves existing static files directly and forwards every
 * other /public/* request to the front controller (public/index.php).
 *
 * Usage (from the project root):
 *   ESH_SITEURL=http://localhost:8000 \
 *     php -S 0.0.0.0:8000 -t . tools/dev_server_router.php
 *
 * Then open http://localhost:8000/public/ ( / redirects to /public/ ).
 */

$root = dirname(__DIR__);
$uri = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

// Root -> public/
if ($uri === '' || $uri === '/') {
    header('Location: /public/');
    exit;
}

// Serve real existing static files (assets, uploads, etc.) directly.
$resolved = realpath($root . $uri);
if ($resolved !== false && is_file($resolved) && strpos($resolved, $root) === 0) {
    return false; // let the built-in server serve it as-is
}

// Anything under /public/api/ -> API front controller.
if ($uri === '/public/api' || strpos($uri, '/public/api/') === 0) {
    chdir($root . '/public/api');
    $_SERVER['SCRIPT_NAME'] = '/public/api/index.php';
    $_SERVER['SCRIPT_FILENAME'] = $root . '/public/api/index.php';
    require $root . '/public/api/index.php';
    return true;
}

// Anything under /public/ -> front controller.
if ($uri === '/public' || strpos($uri, '/public/') === 0) {
    chdir($root . '/public');
    $_SERVER['SCRIPT_NAME'] = '/public/index.php';
    $_SERVER['SCRIPT_FILENAME'] = $root . '/public/index.php';
    require $root . '/public/index.php';
    return true;
}

http_response_code(404);
echo 'Not Found';
return true;
