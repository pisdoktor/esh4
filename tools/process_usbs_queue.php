<?php
declare(strict_types=1);

/**
 * USBS bildirim kuyruk işleyici (CLI).
 *
 *   php tools/process_usbs_queue.php
 *   php tools/process_usbs_queue.php --retry-only
 *   php tools/process_usbs_queue.php --push-only
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

if (!\App\Helpers\UsbsComplianceHelper::enabled()) {
    fwrite(STDERR, "FAIL: USBS referans sütunları kurulu değil.\n");
    exit(1);
}

if (!\App\Helpers\UsbsBridgeHelper::isReady()) {
    fwrite(STDERR, "FAIL: USBS köprüsü kapalı.\n");
    exit(1);
}

$mode = \App\Helpers\OperationalSettings::usbsBridgeApiMode();
if ($mode === 'file') {
    fwrite(STDERR, "SKIP: USBS API modu 'file' — push atlandı.\n");
    exit(0);
}

$service = new \App\Services\Usbs\UsbsBridgeService();
$retried = 0;
$pushOk = false;

if (!$pushOnly) {
    $retried = $service->retryFailedNotifications(50);
    echo "Retry failed notifications: {$retried}\n";
}

if (!$retryOnly) {
    $push = $service->pushCurrentBundle();
    $pushOk = !empty($push['ok']);
    if ($pushOk) {
        $service->logSync('esh_to_usbs', 'success', 'cli-push.json', (array) (($push['response'] ?? []) ?: []));
        \App\Helpers\AuditLogHelper::log('usbs.queue.push', 'usbs', null, null, ['cli' => true]);
        echo "Push: OK\n";
    } else {
        $err = (string) ($push['error'] ?? 'bilinmeyen hata');
        $service->logSync('esh_to_usbs', 'failed', 'cli-push.json', [], $err);
        fwrite(STDERR, "Push: FAIL — {$err}\n");
    }
}

exit(($retryOnly || $pushOk || $retried > 0) ? 0 : 1);
