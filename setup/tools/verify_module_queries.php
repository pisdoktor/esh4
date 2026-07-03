<?php
declare(strict_types=1);

/**
 * Modül başına read-only SQL / model probu (CLI).
 *
 *   php tools/verify_module_queries.php
 *   php tools/verify_module_queries.php --json
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

/** @var list<array{label:string,run:callable():void,skip?:callable():bool}> */
$probes = [
    [
        'label' => 'patient:active_count',
        'run' => static function () use ($db): void {
            $n = (int) $db->loadResultPrepared('SELECT COUNT(*) FROM #__hastalar WHERE pasif = ?', ['0']);
            if ($n < 0) {
                throw new RuntimeException('Negatif sayım');
            }
        },
    ],
    [
        'label' => 'visit:recent_30d',
        'run' => static function () use ($db): void {
            $since = date('Y-m-d', strtotime('-30 days'));
            $db->loadResultPrepared(
                'SELECT COUNT(*) FROM #__izlemler WHERE izlemtarihi >= ?',
                [$since]
            );
        },
    ],
    [
        'label' => 'planned_visit:pending',
        'run' => static function () use ($db): void {
            $db->loadResultPrepared(
                'SELECT COUNT(*) FROM #__pizlemler WHERE COALESCE(durum, 0) = 0',
                []
            );
        },
    ],
    [
        'label' => 'stok:critical',
        'skip' => static fn (): bool => !\App\Services\Stok\StokService::moduleReady(),
        'run' => static function (): void {
            (new \App\Models\StokMalzeme())->countCritical(null);
        },
    ],
    [
        'label' => 'mesajlasma:ready',
        'skip' => static fn (): bool => !\App\Services\MesajService::moduleReady(),
        'run' => static function (): void {
            \App\Models\MesajKonusma::tableReady();
        },
    ],
    [
        'label' => 'audit_log:recent_7d',
        'skip' => static fn (): bool => !\App\Models\AuditLog::tableReady(),
        'run' => static function () use ($db): void {
            $since = date('Y-m-d', strtotime('-7 days'));
            $db->loadResultPrepared(
                'SELECT COUNT(*) FROM #__audit_log WHERE created_at >= ?',
                [$since . ' 00:00:00']
            );
        },
    ],
    [
        'label' => 'esys:compliance_kpis',
        'skip' => static fn (): bool => !\App\Helpers\EsysComplianceHelper::columnsReady(),
        'run' => static function (): void {
            \App\Helpers\EsysComplianceHelper::complianceKpis(null);
        },
    ],
    [
        'label' => 'usbs:columns_ready',
        'skip' => static fn (): bool => !\App\Helpers\UsbsComplianceHelper::columnsReady(),
        'run' => static function () use ($db): void {
            $db->loadResultPrepared('SELECT COUNT(*) FROM #__hastalar WHERE pasif = 0 LIMIT 1', []);
        },
    ],
    [
        'label' => 'cds:count_overdue',
        'skip' => static fn (): bool => !\App\Helpers\ClinicalDecisionSupportHelper::enabled(),
        'run' => static function (): void {
            (new \App\Services\Clinical\ClinicalDecisionSupportService())->countOverdueHighRisk();
        },
    ],
    [
        'label' => 'cds:list_overdue',
        'skip' => static fn (): bool => !\App\Helpers\ClinicalDecisionSupportHelper::enabled(),
        'run' => static function (): void {
            (new \App\Services\Clinical\ClinicalDecisionSupportService())->listOverdueHighRisk(1, 0);
        },
    ],
    [
        'label' => 'portal:queued_count',
        'skip' => static fn (): bool => !\App\Helpers\PatientPortalHelper::appointmentRequestsTableReady(),
        'run' => static function (): void {
            \App\Helpers\PatientPortalHelper::countQueuedAppointmentRequests(null);
        },
    ],
    [
        'label' => 'uhds:today',
        'run' => static function (): void {
            (new \App\Models\Uhds())->getByDate(date('Y-m-d'));
        },
    ],
    [
        'label' => 'user:active_count',
        'run' => static function () use ($db): void {
            $db->loadResultPrepared('SELECT COUNT(*) FROM #__users WHERE activated = 1', []);
        },
    ],
    [
        'label' => 'kurum:count',
        'skip' => static fn (): bool => !\App\Models\Kurum::tableExists(),
        'run' => static function () use ($db): void {
            $db->loadResultPrepared('SELECT COUNT(*) FROM #__kurumlar', []);
        },
    ],
];

$results = [];
$ok = true;
$skipped = 0;

foreach ($probes as $probe) {
    $label = (string) $probe['label'];
    if (isset($probe['skip']) && is_callable($probe['skip']) && ($probe['skip'])()) {
        $results[] = ['status' => 'SKIP', 'label' => $label, 'detail' => 'modül/tablo hazır değil'];
        $skipped++;
        continue;
    }
    try {
        ($probe['run'])();
        $err = (string) $db->getErrorMsg();
        if ($err !== '') {
            throw new RuntimeException($err);
        }
        $results[] = ['status' => 'OK', 'label' => $label, 'detail' => ''];
    } catch (\Throwable $e) {
        $results[] = ['status' => 'FAIL', 'label' => $label, 'detail' => $e->getMessage()];
        $ok = false;
    }
}

if ($jsonOut) {
    echo json_encode(['ok' => $ok, 'skipped' => $skipped, 'probes' => $results], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit($ok ? 0 : 1);
}

echo "Modül read-only SQL probu (" . DB_NAME . ")\n";
echo str_repeat('-', 55) . "\n";
foreach ($results as $r) {
    $line = sprintf('[%s] %s', $r['status'], $r['label']);
    if ($r['detail'] !== '') {
        $line .= ' — ' . $r['detail'];
    }
    echo $line . "\n";
}
echo str_repeat('-', 55) . "\n";
echo $ok ? "Sonuç: OK ({$skipped} skip)\n" : "Sonuç: FAIL\n";
exit($ok ? 0 : 1);
