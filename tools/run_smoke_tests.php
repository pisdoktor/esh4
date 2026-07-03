<?php
declare(strict_types=1);

/**
 * PHPUnit smoke test çalıştırıcı (Composer gerekmez).
 *
 *   php tools/run_smoke_tests.php
 *   php tools/run_smoke_tests.php --download   # phpunit.phar yoksa indir
 *
 * PHPUnit PHAR: tools/phpunit.phar (git dışı; ilk çalıştırmada --download ile alınır)
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
$phar = $root . '/tools/phpunit.phar';
$xml = $root . '/phpunit.xml';
$download = in_array('--download', $argv, true);

$vendorPhpunit = $root . '/vendor/phpunit/phpunit/phpunit';
if (is_file($vendorPhpunit)) {
    $php = PHP_BINARY ?: 'php';
    $cmd = escapeshellarg($php) . ' ' . escapeshellarg($vendorPhpunit)
        . ' -c ' . escapeshellarg($xml)
        . ' --testsuite smoke';
    echo "Running (Composer): {$cmd}\n\n";
    passthru($cmd, $code);
    exit((int) $code);
}

if (!is_file($phar)) {
  if ($download) {
    $url = 'https://phar.phpunit.de/phpunit-10.5.phar';
    echo "Downloading PHPUnit PHAR from {$url} ...\n";
    $ctx = stream_context_create([
      'http' => ['timeout' => 120, 'follow_location' => 1],
      'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $bytes = @file_get_contents($url, false, $ctx);
    if (!is_string($bytes) || $bytes === '') {
      fwrite(STDERR, "FAIL: PHPUnit PHAR indirilemedi. Manuel: {$url} → tools/phpunit.phar\n");
      exit(1);
    }
    if (file_put_contents($phar, $bytes) === false) {
      fwrite(STDERR, "FAIL: tools/phpunit.phar yazılamadı.\n");
      exit(1);
    }
    echo "OK: tools/phpunit.phar kaydedildi.\n";
  } else {
    fwrite(STDERR, "PHPUnit PHAR bulunamadı: tools/phpunit.phar\n");
    fwrite(STDERR, "İndirmek için: php tools/run_smoke_tests.php --download\n");
    exit(1);
  }
}

if (!is_readable($xml)) {
  fwrite(STDERR, "FAIL: phpunit.xml bulunamadı.\n");
  exit(1);
}

$php = PHP_BINARY ?: 'php';
$cmd = escapeshellarg($php) . ' ' . escapeshellarg($phar)
    . ' -c ' . escapeshellarg($xml)
    . ' --testsuite smoke';

echo "Running: {$cmd}\n\n";
passthru($cmd, $code);
exit((int) $code);
