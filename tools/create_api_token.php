<?php
declare(strict_types=1);

/**
 * CLI: REST API bearer token oluştur.
 *
 *   php tools/create_api_token.php --user=1 --label="Entegrasyon"
 *   php tools/create_api_token.php --user=1 --label="BI" --scopes=patients,visits
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/config.php';

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

$userId = 0;
$label = '';
$scopes = 'read';
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--user=')) {
        $userId = (int) substr($arg, 7);
    } elseif (str_starts_with($arg, '--label=')) {
        $label = substr($arg, 8);
    } elseif (str_starts_with($arg, '--scopes=')) {
        $scopes = substr($arg, 9);
    }
}

if ($userId <= 0) {
    fwrite(STDERR, "Usage: php tools/create_api_token.php --user=ID [--label=...] [--scopes=read|patients,visits,plans]\n");
    exit(1);
}

$result = \App\Services\Api\ApiTokenService::create($userId, $label, $scopes);
if (empty($result['ok'])) {
    fwrite(STDERR, 'FAIL: ' . ($result['message'] ?? '') . PHP_EOL);
    exit(1);
}

echo "OK token_id=" . (int) ($result['id'] ?? 0) . PHP_EOL;
echo (string) ($result['token'] ?? '') . PHP_EOL;
