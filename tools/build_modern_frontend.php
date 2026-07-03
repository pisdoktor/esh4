<?php
declare(strict_types=1);

/**
 * Modern frontend Vite derlemesi (npm gerekir — isteğe bağlı).
 *
 *   php tools/build_modern_frontend.php
 *   php tools/build_modern_frontend.php --check   # yalnızca npm var mı
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
$modernDir = $root . '/frontend/modern';
$checkOnly = in_array('--check', $argv, true);

if (!is_dir($modernDir)) {
    fwrite(STDERR, "FAIL: frontend/modern bulunamadı.\n");
    exit(1);
}

$npm = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'npm.cmd' : 'npm';
$npmPath = null;
foreach ([$npm, 'npm'] as $candidate) {
    $out = [];
    $code = 0;
    exec(escapeshellcmd($candidate) . ' --version 2>&1', $out, $code);
    if ($code === 0) {
        $npmPath = $candidate;
        break;
    }
}

if ($npmPath === null) {
    fwrite(STDERR, "npm bulunamadı — CDN pilot dosyaları (public/assets/modern/*.mjs) kullanılmaya devam eder.\n");
    exit($checkOnly ? 1 : 0);
}

if ($checkOnly) {
    echo "OK: npm mevcut ({$npmPath})\n";
    exit(0);
}

$cmds = [
    'cd ' . escapeshellarg($modernDir) . ' && ' . escapeshellcmd($npmPath) . ' install',
    'cd ' . escapeshellarg($modernDir) . ' && ' . escapeshellcmd($npmPath) . ' run build',
];

foreach ($cmds as $cmd) {
    echo ">> {$cmd}\n";
    passthru($cmd, $code);
    if ($code !== 0) {
        fwrite(STDERR, "FAIL: komut başarısız ({$code})\n");
        exit((int) $code);
    }
}

if (!is_file($root . '/public/assets/modern/dist/dashboard-pilot.built.mjs')) {
    fwrite(STDERR, "FAIL: dashboard-pilot.built.mjs üretilmedi.\n");
    exit(1);
}
if (!is_file($root . '/public/assets/modern/dist/planning-pilot.built.mjs')) {
    fwrite(STDERR, "FAIL: planning-pilot.built.mjs üretilmedi.\n");
    exit(1);
}

echo "OK: modern frontend derlendi → public/assets/modern/dist/\n";
exit(0);
