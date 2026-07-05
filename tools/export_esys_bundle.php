<?php
declare(strict_types=1);

/**
 * ESYS dosya köprüsü — JSON paket dışa aktarma (CLI, cron).
 *
 *   php tools/export_esys_bundle.php
 *   php tools/export_esys_bundle.php --output=storage/exports/esys.json
 *   php tools/export_esys_bundle.php --kurum-id=1
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once __DIR__ . '/bridge_export_cli.inc.php';

if (!\App\Helpers\EsysComplianceHelper::enabled()) {
    fwrite(STDERR, "FAIL: ESYS referans sütunları kurulu değil.\n");
    exit(1);
}

if (!\App\Helpers\EsysBridgeHelper::isReady()) {
    fwrite(STDERR, "FAIL: ESYS köprüsü kapalı.\n");
    exit(1);
}

$service = new \App\Services\Esys\EsysBridgeService();
$bundle = $service->exportBundle();
$json = json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
if (!is_string($json)) {
    fwrite(STDERR, "FAIL: JSON oluşturulamadı.\n");
    exit(1);
}

$written = bridge_export_cli_write($json, 'esys');
if ($written === '') {
    fwrite(STDERR, "FAIL: Dosya yazılamadı.\n");
    exit(1);
}

$meta = is_array($bundle['meta'] ?? null) ? $bundle['meta'] : [];
$service->logSync('esh_to_esys', 'success', basename($written), $meta);
\App\Helpers\AuditLogHelper::log('esys.export.cli', 'esys', null, null, array_merge($meta, ['file' => $written]));

echo "OK: {$written}\n";
echo 'patients=' . (int) ($meta['patient_count'] ?? 0)
    . ' visits=' . (int) ($meta['visit_count'] ?? 0)
    . ' plans=' . (int) ($meta['plan_count'] ?? 0)
    . ' erapor=' . (int) ($meta['erapor_count'] ?? 0) . "\n";

exit(0);
