<?php
declare(strict_types=1);

/**
 * USBS dosya köprüsü — JSON paket dışa aktarma (CLI, cron).
 *
 *   php tools/export_usbs_bundle.php
 *   php tools/export_usbs_bundle.php --output=storage/exports/usbs.json
 *   php tools/export_usbs_bundle.php --kurum-id=1
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once __DIR__ . '/bridge_export_cli.inc.php';

if (!\App\Helpers\UsbsComplianceHelper::enabled()) {
    fwrite(STDERR, "FAIL: USBS referans sütunları kurulu değil.\n");
    exit(1);
}

if (!\App\Helpers\UsbsBridgeHelper::isReady()) {
    fwrite(STDERR, "FAIL: USBS köprüsü kapalı.\n");
    exit(1);
}

$service = new \App\Services\Usbs\UsbsBridgeService();
$bundle = $service->exportBundle();
$json = json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if (!is_string($json)) {
    fwrite(STDERR, "FAIL: JSON oluşturulamadı.\n");
    exit(1);
}

$written = bridge_export_cli_write($json, 'usbs');
if ($written === '') {
    fwrite(STDERR, "FAIL: Dosya yazılamadı.\n");
    exit(1);
}

$meta = is_array($bundle['meta'] ?? null) ? $bundle['meta'] : [];
$service->logSync('esh_to_usbs', 'success', basename($written), $meta);
\App\Helpers\AuditLogHelper::log('usbs.export.cli', 'usbs', null, null, array_merge($meta, ['file' => $written]));

echo "OK: {$written}\n";
echo 'patients=' . (int) ($meta['patient_count'] ?? 0)
    . ' visits=' . (int) ($meta['visit_count'] ?? 0) . "\n";

exit(0);
