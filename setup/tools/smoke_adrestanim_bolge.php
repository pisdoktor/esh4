<?php
declare(strict_types=1);

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

use App\Models\Address;

$addr = new Address();
$bolgeler = $addr->adminListByTipUst('bolge', '0');
if ($bolgeler === []) {
    fwrite(STDERR, "FAIL: no bolge root\n");
    exit(1);
}
$bolgeId = (string) ($bolgeler[0]['id'] ?? '');
$ilceler = $addr->adminListByTipUst('ilce', $bolgeId);
if ($ilceler === []) {
    fwrite(STDERR, "FAIL: no ilce under bolge\n");
    exit(1);
}
$ilceId = (string) ($ilceler[0]['id'] ?? '');
$mahalle = $addr->adminListByTipUst('mahalle', $ilceId);
if (!$addr->adminValidateParentForChild('ilce', $bolgeId)) {
    fwrite(STDERR, "FAIL: ilce parent validation\n");
    exit(1);
}
if (!$addr->adminValidateParentForChild('mahalle', $ilceId)) {
    fwrite(STDERR, "FAIL: mahalle parent validation\n");
    exit(1);
}
echo 'OK bolge=' . count($bolgeler) . ' ilce=' . count($ilceler) . ' mahalle_sample=' . count($mahalle) . "\n";
