#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Bekleyen SMS alıcılarını işler (50+ toplu gönderim kuyruğu).
 * Kullanım: php tools/sms_worker.php [limit]
 */
define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/config/bootstrap.php';

$limit = isset($argv[1]) ? max(1, (int) $argv[1]) : 100;

if (!\App\Services\Sms\SmsService::moduleReady()) {
    fwrite(STDERR, "SMS tabloları yok.\n");
    exit(1);
}

$svc = new \App\Services\Sms\SmsService();
$done = $svc->processQueue($limit);
echo "İşlenen: {$done}\n";
