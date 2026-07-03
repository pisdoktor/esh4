<?php
declare(strict_types=1);

/**
 * Faz 1+2 migration sağlık kontrolü (CLI).
 *
 *   php tools/verify_phase_migrations.php
 *   php tools/verify_phase_migrations.php --json
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

$jsonOut = in_array('--json', $argv ?? [], true);
$db = \App\Core\Database::getInstance();
$prefix = defined('DB_PREFIX') ? (string) DB_PREFIX : 'esh_';

/** @var list<array{kind:string,label:string,migration:string,optional?:bool}> */
$checks = [
    ['kind' => 'table', 'label' => $prefix . 'audit_log', 'migration' => 'database/migrate_esh_audit_log.sql'],
    ['kind' => 'table', 'label' => $prefix . 'api_tokens', 'migration' => 'database/migrate_esh_api_tokens.sql'],
    ['kind' => 'table', 'label' => $prefix . 'esys_sync_log', 'migration' => 'database/migrate_esh_esys_sync_log.sql'],
    ['kind' => 'table', 'label' => $prefix . 'usbs_sync_log', 'migration' => 'database/migrate_esh_usbs_sync_log.sql'],
    ['kind' => 'table', 'label' => $prefix . 'federation_regions', 'migration' => 'database/migrate_esh_federation_regions.sql'],
    ['kind' => 'table', 'label' => $prefix . 'federation_sync_log', 'migration' => 'database/migrate_esh_federation_sync_log.sql'],
    ['kind' => 'table', 'label' => $prefix . 'portal_appointment_requests', 'migration' => 'database/migrate_esh_portal_appointment_requests.sql'],
    ['kind' => 'table', 'label' => $prefix . 'cds_ack', 'migration' => 'database/migrate_esh_cds_ack.sql'],
    ['kind' => 'column', 'label' => $prefix . 'izlemler.checkin_accuracy', 'migration' => 'database/migrate_esh_izlemler_checkin_accuracy.sql'],
    ['kind' => 'column', 'label' => $prefix . 'hastalar.esys_hasta_ref', 'migration' => 'database/migrate_esh_esys_refs.sql'],
    ['kind' => 'table', 'label' => $prefix . 'hasta_barthel', 'migration' => 'database/migrate_esh_hasta_barthel.sql', 'optional' => true],
    ['kind' => 'table', 'label' => $prefix . 'stok_malzeme', 'migration' => 'database/migrate_esh_stok_tables.sql', 'optional' => true],
];

function tableExists(\App\Core\Database $db, string $table): bool
{
    $row = $db->loadResultPrepared(
        'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
        [$table]
    );

    return $row !== null && $row !== false && $row !== '';
}

function columnExists(\App\Core\Database $db, string $table, string $column): bool
{
    $row = $db->loadResultPrepared(
        'SELECT 1 FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1',
        [$table, $column]
    );

    return $row !== null && $row !== false && $row !== '';
}

$results = [];
$ok = true;
$warn = 0;

foreach ($checks as $check) {
    $label = (string) $check['label'];
    $migration = (string) $check['migration'];
    $optional = !empty($check['optional']);
    $passed = false;
    $detail = '';

    if ($check['kind'] === 'table') {
        $passed = tableExists($db, $label);
        $detail = $passed ? 'tablo mevcut' : 'tablo eksik';
    } elseif ($check['kind'] === 'column') {
        $parts = explode('.', $label, 2);
        $passed = count($parts) === 2 && columnExists($db, $parts[0], $parts[1]);
        $detail = $passed ? 'kolon mevcut' : 'kolon eksik';
    }

    $status = $passed ? 'OK' : ($optional ? 'SKIP' : 'FAIL');
    if (!$passed && !$optional) {
        $ok = false;
    }
    if (!$passed && $optional) {
        $warn++;
    }

    $cmd = 'php tools/run_sql_migration.php ' . $migration;
    $results[] = [
        'status' => $status,
        'label' => $label,
        'detail' => $detail,
        'migration' => $migration,
        'command' => $passed ? null : $cmd,
    ];
}

if ($jsonOut) {
    echo json_encode(['ok' => $ok, 'warnings' => $warn, 'checks' => $results], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit($ok ? 0 : 1);
}

echo "Faz 1+2 migration sağlık kontrolü (" . DB_NAME . ")\n";
echo str_repeat('-', 60) . "\n";
foreach ($results as $r) {
    $line = sprintf('[%s] %s — %s', $r['status'], $r['label'], $r['detail']);
    echo $line . "\n";
    if ($r['command'] !== null && $r['status'] === 'FAIL') {
        echo '      → ' . $r['command'] . "\n";
    }
}
echo str_repeat('-', 60) . "\n";
if ($ok) {
    echo "Sonuç: OK";
    if ($warn > 0) {
        echo " ({$warn} isteğe bağlı eksik)";
    }
    echo "\n";
    exit(0);
}

echo "Sonuç: FAIL — eksik migration dosyalarını yukarıdaki komutlarla uygulayın.\n";
exit(1);
