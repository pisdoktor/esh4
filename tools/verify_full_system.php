<?php
declare(strict_types=1);

/**
 * Tam sistem QA master runner (CLI).
 *
 *   php tools/verify_full_system.php
 *   php tools/verify_full_system.php --skip-smoke
 *   php tools/verify_full_system.php --save-baseline
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
$php = PHP_BINARY ?: 'php';
$skipSmoke = in_array('--skip-smoke', $argv ?? [], true);
$saveBaseline = in_array('--save-baseline', $argv ?? [], true);

/** @var list<array{name:string,ok:bool,detail:string}> */
$steps = [];

function runStep(string $name, string $cmd, string $root): bool
{
    global $steps;
    echo "\n=== {$name} ===\n";
    passthru($cmd, $code);
    $ok = ($code === 0);
    $steps[] = ['name' => $name, 'ok' => $ok, 'detail' => $ok ? 'OK' : "exit {$code}"];
    return $ok;
}

function lintPhpTree(string $root): bool
{
    global $steps;
    echo "\n=== PHP lint (app/) ===\n";
    $dirs = ['Controllers', 'Models', 'Helpers', 'Services'];
    $fail = 0;
    $count = 0;
    foreach ($dirs as $dir) {
        $path = $root . '/app/' . $dir;
        if (!is_dir($path)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($it as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $count++;
            $out = [];
            exec(escapeshellarg(PHP_BINARY ?: 'php') . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $out, $code);
            if ($code !== 0) {
                echo "FAIL: {$file->getPathname()}\n  " . implode("\n  ", $out) . "\n";
                $fail++;
            }
        }
    }
    $ok = ($fail === 0);
    echo $ok ? "OK: {$count} dosya\n" : "FAIL: {$fail} / {$count} dosya\n";
    $steps[] = ['name' => 'PHP lint', 'ok' => $ok, 'detail' => "{$count} dosya, {$fail} hata"];
    return $ok;
}

function printManualCrudChecklist(): void
{
    echo "\n";
    echo str_repeat('=', 70) . "\n";
    echo "MANUEL TARAYICI CRUD CHECKLIST (admin / Admin123)\n";
    echo str_repeat('=', 70) . "\n";
    echo "Her madde: HTTP 200, flash hata yok, logs/db_errors.log yeni satır yok.\n\n";

    $core = [
        ['Hasta', '/public/Patient/unified', 'Read', 'ilkkayit→kaydet', 'edit→kaydet', 'pasife al'],
        ['İzlem', '/public/Visit/index', 'Read', 'yeni izlem', 'düzenle', 'sil (yetki)'],
        ['Planlı izlem', '/public/PlannedVisit/index', 'Read', 'yeni plan', 'düzenle', 'iptal/tamam'],
        ['Dashboard', '/public/Dashboard/index', 'Read', 'planla (dry-run)', '—', '—'],
        ['Kullanıcı', '/public/User/list', 'Read', 'yeni', 'düzenle', 'pasif'],
        ['Stok', '/public/Stok/index', 'Read', 'malzeme+hareket', 'düzenle', '—'],
        ['UHDS', '/public/Uhds/index', 'Read', 'randevu ekle', 'hasta_geldi', '—'],
        ['Portal', '/public/PatientPortal/login', 'Read', 'OTP akışı', 'SMS onay', '—'],
        ['Portal kuyruk', '/public/PortalAppointment/index', 'Read', '—', 'onayla/reddet', '—'],
    ];

    echo "Çekirdek modüller (zorunlu):\n";
    printf("%-16s %-36s %-8s %-18s %-12s %s\n", 'Modül', 'URL', 'Read', 'Create', 'Update', 'Delete');
    echo str_repeat('-', 110) . "\n";
    foreach ($core as $row) {
        printf("%-16s %-36s %-8s %-18s %-12s %s\n", $row[0], $row[1], $row[2], $row[3], $row[4], $row[5]);
    }

    echo "\nEntegrasyon / Faz modülleri:\n";
    $integration = [
        'ESYS Compliance — KPI kartları, Network/console temiz',
        'ESYS Bridge — export JSON indir',
        'USBS Bridge — export JSON indir',
        'REST API — ApiToken oluştur; GET /public/api/v1/patients',
        'Audit log — liste + CSV export',
        'CDS — dashboard widget sayısı görünür',
        'Federasyon — FederationBridge/index açılır',
        'Modern FE — dashboard pilot widget (rollout %)',
    ];
    foreach ($integration as $i => $item) {
        echo '  ' . ($i + 1) . '. [ ] ' . $item . "\n";
    }
    echo str_repeat('=', 70) . "\n";
}

$allOk = true;

if ($saveBaseline) {
    runStep('db_errors baseline', escapeshellarg($php) . ' ' . escapeshellarg($root . '/tools/verify_db_errors_gate.php') . ' --save-baseline', $root);
}

$allOk = lintPhpTree($root) && $allOk;
$allOk = runStep('Modül CRUD wiring', escapeshellarg($php) . ' ' . escapeshellarg($root . '/tools/verify_module_crud.php'), $root) && $allOk;
$allOk = runStep('Modül SQL probu', escapeshellarg($php) . ' ' . escapeshellarg($root . '/tools/verify_module_queries.php'), $root) && $allOk;
$allOk = runStep('Faz migration', escapeshellarg($php) . ' ' . escapeshellarg($root . '/tools/verify_phase_migrations.php'), $root) && $allOk;
$allOk = runStep('Faz rotalar', escapeshellarg($php) . ' ' . escapeshellarg($root . '/tools/verify_phase_routes.php'), $root) && $allOk;

if (!$skipSmoke) {
    $allOk = runStep('Smoke testler', escapeshellarg($php) . ' ' . escapeshellarg($root . '/tools/run_smoke_tests.php'), $root) && $allOk;
}

$allOk = runStep('db_errors gate', escapeshellarg($php) . ' ' . escapeshellarg($root . '/tools/verify_db_errors_gate.php'), $root) && $allOk;

echo "\n" . str_repeat('=', 60) . "\n";
echo "ÖZET\n";
echo str_repeat('-', 60) . "\n";
foreach ($steps as $s) {
    echo sprintf("[%s] %s — %s\n", $s['ok'] ? 'OK' : 'FAIL', $s['name'], $s['detail']);
}
echo str_repeat('-', 60) . "\n";
echo $allOk ? "Sonuç: TÜM OTOMATİK KAPILAR GEÇTİ\n" : "Sonuç: OTOMATİK KAPILARDA HATA VAR\n";

printManualCrudChecklist();

exit($allOk ? 0 : 1);
