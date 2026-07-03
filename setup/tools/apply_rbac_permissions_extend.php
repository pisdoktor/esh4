<?php
/**
 * database/patch_rbac_permissions_extend.sql içeriğini uygular.
 * Kullanım: php tools/apply_rbac_permissions_extend.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/config/config.php';

spl_autoload_register(function ($class) use ($root) {
    $prefix = 'App\\';
    $base_dir = $root . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

if (!\App\Services\PermissionService::tablesReady()) {
    fwrite(STDERR, "RBAC tabloları bulunamadı.\n");
    exit(1);
}

$rows = [
    ['patient', 'admin', 'patient.admin', 'Hasta — yönetici işlemleri'],
    ['user', 'admin', 'user.admin', 'Kullanıcı — yönetim'],
    ['ilac_rehber', 'admin', 'ilac_rehber.admin', 'İlaç rehberi — yönetim'],
    ['ilac_rehber', 'superadmin', 'ilac_rehber.superadmin', 'İlaç rehberi — süper yönetici'],
    ['mesajlasma', 'admin', 'mesajlasma.admin', 'Mesajlaşma — duyuru (broadcast)'],
    ['mesajlasma', 'update', 'mesajlasma.update', 'Mesajlaşma — güncelleme'],
    ['mesajlasma', 'delete', 'mesajlasma.delete', 'Mesajlaşma — kalıcı silme'],
];

$db = \App\Core\Database::getInstance();
$inserted = 0;
foreach ($rows as [$moduleKey, $crud, $slug, $label]) {
    $exists = $db->loadResultPrepared(
        'SELECT id FROM #__permissions WHERE slug = ? LIMIT 1',
        [$slug]
    );
    if ($exists !== null && $exists !== false && $exists !== '') {
        echo "SKIP $slug (zaten var)\n";
        continue;
    }
    $ok = $db->insertPrepared('#__permissions', [
        'module_key' => $moduleKey,
        'crud' => $crud,
        'slug' => $slug,
        'label' => $label,
    ]);
    if ($ok === false) {
        fwrite(STDERR, "HATA: $slug eklenemedi\n");
        exit(1);
    }
    echo "OK   $slug\n";
    $inserted++;
}

echo "\nTamamlandı — $inserted yeni izin eklendi.\n";
