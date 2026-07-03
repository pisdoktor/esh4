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

use App\Helpers\FederationAdresBolgeSync;
use App\Models\Address;

$n = FederationAdresBolgeSync::syncMissingLinks();
$bolgeler = (new Address())->adminListByTipUst('bolge', '0');
echo 'synced=' . $n . ' bolge_count=' . count($bolgeler) . "\n";
foreach ($bolgeler as $b) {
    echo ' - ' . ($b['adi'] ?? '') . ' id=' . ($b['id'] ?? '') . "\n";
}
