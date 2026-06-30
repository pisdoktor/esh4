<?php
declare(strict_types=1);

/**
 * Sürüm migrate dosyalarını tek patch_{SÜRÜM}.sql altında birleştirir.
 *
 *   php tools/build_patch_sql.php
 *   php tools/build_patch_sql.php 3.2.1
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Bu betik yalnızca CLI ile çalıştırılmalıdır.\n");
    exit(1);
}

require_once __DIR__ . '/build_dist_mirror.inc.php';

$projectRoot = realpath(dirname(__DIR__));
if ($projectRoot === false) {
    fwrite(STDERR, "Proje kökü bulunamadı.\n");
    exit(1);
}

$ver = $argv[1] ?? null;
$releaseDate = gmdate('Y-m-d');
$vfile = $projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'version.php';
if (is_readable($vfile)) {
    $raw = (string) file_get_contents($vfile);
    if ($ver === null && preg_match("/define\s*\(\s*'ESH_APP_VERSION'\s*,\s*'([^']+)'/", $raw, $m)) {
        $ver = $m[1];
    }
    if (preg_match("/define\s*\(\s*'ESH_APP_RELEASE_DATE'\s*,\s*'([^']+)'/", $raw, $m)) {
        $releaseDate = $m[1];
    }
}

if ($ver === null || $ver === '') {
    fwrite(STDERR, "Sürüm belirtilmedi (argüman veya config/version.php).\n");
    exit(1);
}

$consolidated = buildConsolidatedPatchSql($ver, $releaseDate, $projectRoot);
if ($consolidated === null) {
    fwrite(STDERR, "Sürüm {$ver} için birleşik patch tanımlı değil.\n");
    exit(1);
}

$out = $projectRoot . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'patch_' . $ver . '.sql';
file_put_contents($out, $consolidated);
$bytes = strlen($consolidated);
echo "OK: {$out} ({$bytes} bayt)\n";
