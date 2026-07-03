<?php
declare(strict_types=1);

/**
 * ESYS API kuyruk işleyici (CLI).
 *
 *   php tools/process_esys_queue.php
 *   php tools/process_esys_queue.php --retry-only
 *   php tools/process_esys_queue.php --push-only
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

$retryOnly = in_array('--retry-only', $argv ?? [], true);
$pushOnly = in_array('--push-only', $argv ?? [], true);

if (!\App\Helpers\EsysComplianceHelper::enabled()) {
    fwrite(STDERR, "FAIL: ESYS referans sütunları kurulu değil.\n");
    exit(1);
}

if (!\App\Helpers\EsysBridgeHelper::isReady()) {
    fwrite(STDERR, "FAIL: ESYS köprüsü kapalı.\n");
    exit(1);
}

$mode = \App\Helpers\OperationalSettings::esysBridgeApiMode();
if ($mode === 'file') {
    fwrite(STDERR, "SKIP: ESYS API modu 'file' — push atlandı.\n");
    exit(0);
}

$service = new \App\Services\Esys\EsysBridgeService();
$retried = 0;
$pushOk = false;

if (!$pushOnly) {
    $retried = $service->retryFailedSyncs(10);
    echo "Retry failed syncs: {$retried}\n";
}

if (!$retryOnly) {
    $push = $service->pushCurrentBundle();
    $pushOk = !empty($push['ok']);
    if ($pushOk) {
        $service->logSync('esh_to_esys', 'success', 'cli-push.json', (array) (($push['response'] ?? []) ?: []));
        \App\Helpers\AuditLogHelper::log('esys.queue.push', 'esys', null, null, ['cli' => true]);
        echo "Push: OK\n";
    } else {
        $err = (string) ($push['error'] ?? 'bilinmeyen hata');
        $service->logSync('esh_to_esys', 'failed', 'cli-push.json', [], $err);
        fwrite(STDERR, "Push: FAIL — {$err}\n");
    }
}

exit(($retryOnly || $pushOk || $retried > 0) ? 0 : 1);
