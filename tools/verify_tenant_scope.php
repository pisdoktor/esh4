<?php
declare(strict_types=1);

/**
 * Tenant kapsamı doğrulama (CLI).
 *
 *   php tools/verify_tenant_scope.php
 *
 * Oturum simülasyonu olmadan SQL yardımcılarının yüklenebilirliğini ve
 * Kurum tablosunun varlığını kontrol eder.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

spl_autoload_register(function ($class) {
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

$ok = true;

if (!\App\Models\Kurum::tableExists()) {
    echo "FAIL: esh_kurumlar tablosu yok — database/patch_4.0.0.sql uygulayın.\n";
    $ok = false;
} else {
    echo "OK: esh_kurumlar mevcut.\n";
}

$classes = [
    \App\Helpers\TenantContext::class,
    \App\Helpers\TenantSqlHelper::class,
    \App\Helpers\TenantStoreHelper::class,
    \App\Helpers\KurumCorporateSettings::class,
];

foreach ($classes as $cls) {
    if (!class_exists($cls)) {
        echo "FAIL: sınıf yok: {$cls}\n";
        $ok = false;
    }
}

if ($ok) {
    echo "Tenant altyapısı dosya düzeyinde hazır.\n";
    exit(0);
}

exit(1);
