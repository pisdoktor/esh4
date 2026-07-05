<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require_once dirname(__DIR__) . '/config/config.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $base = dirname(__DIR__) . '/app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$db = \App\Core\Database::getInstance();
$sqlFile = dirname(__DIR__) . '/database/migrate_esh_user_ref_char36.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "Missing migration file.\n");
    exit(1);
}

$raw = file_get_contents($sqlFile);
if (!is_string($raw)) {
    exit(1);
}

foreach (preg_split('/;\s*\R/', $raw) ?: [] as $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '' || str_starts_with($stmt, '--') || str_starts_with(strtoupper($stmt), 'SET ')) {
        continue;
    }
    echo substr($stmt, 0, 60) . "...\n";
    $db->execLogged($stmt);
}

echo "Done.\n";
