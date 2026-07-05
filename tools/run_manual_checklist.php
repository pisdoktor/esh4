<?php
declare(strict_types=1);

/**
 * Manuel tarayıcı CRUD checklist — HTTP oturum taraması (CLI).
 *
 *   php tools/run_manual_checklist.php
 *   php tools/run_manual_checklist.php --base=http://localhost
 *   php tools/run_manual_checklist.php --skip-extended
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
require $root . '/config/config.php';
require $root . '/tools/manual_check_http.php';
require $root . '/tools/run_manual_checklist_extended.php';

$base = rtrim(defined('SITEURL') ? (string) SITEURL : 'http://localhost', '/');
$skipExtended = false;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--base=')) {
        $base = rtrim(substr($arg, 7), '/');
    }
    if ($arg === '--skip-extended') {
        $skipExtended = true;
    }
}

/** @return list<array{group:string,label:string,path:string,as?:string,expect_fail?:bool}> */
function buildChecks(?array $patient): array
{
    $pid = $patient['id'] ?? 'a0000001-0001-4001-8001-000000000001';
    $checks = [
        ['group' => 'admin', 'label' => 'Hasta unified', 'path' => '/public/Patient/unified'],
        ['group' => 'admin', 'label' => 'Hasta view', 'path' => '/public/Patient/view?id=' . rawurlencode($pid)],
        ['group' => 'admin', 'label' => 'İzlem index', 'path' => '/public/Visit/index'],
        ['group' => 'admin', 'label' => 'Planlı izlem', 'path' => '/public/PlannedVisit/index'],
        ['group' => 'admin', 'label' => 'Dashboard', 'path' => '/public/Dashboard/index'],
        ['group' => 'admin', 'label' => 'Kullanıcı list', 'path' => '/public/User/list'],
        ['group' => 'admin', 'label' => 'Stok index', 'path' => '/public/Stok/index'],
        ['group' => 'admin', 'label' => 'UHDS index', 'path' => '/public/Uhds/index'],
        ['group' => 'admin', 'label' => 'Portal login', 'path' => '/public/PatientPortal/login'],
        ['group' => 'admin', 'label' => 'Portal kuyruk', 'path' => '/public/PortalAppointment/index'],
        ['group' => 'admin', 'label' => 'Mesaj index', 'path' => '/public/Mesaj/index'],
        ['group' => 'admin', 'label' => 'Mesaj sent', 'path' => '/public/Mesaj/sent'],
        ['group' => 'admin', 'label' => 'Mesaj trash', 'path' => '/public/Mesaj/trash'],
        ['group' => 'admin', 'label' => 'Randevu index', 'path' => '/public/Randevu/index'],
        ['group' => 'admin', 'label' => 'Hasta ilaç raporu', 'path' => '/public/HastaIlacRapor/index?id=' . rawurlencode($pid)],
        ['group' => 'admin', 'label' => 'Hasta ilaç raporu ilacRows', 'path' => '/public/HastaIlacRapor/ilacRows?id=' . rawurlencode($pid)],
        ['group' => 'admin', 'label' => 'Hasta ilaç raporu raporRows', 'path' => '/public/HastaIlacRapor/raporRows?id=' . rawurlencode($pid)],
        ['group' => 'admin', 'label' => 'SMS index', 'path' => '/public/Sms/index'],
        ['group' => 'admin', 'label' => 'SMS history', 'path' => '/public/Sms/history'],
        ['group' => 'admin', 'label' => 'Hasta nakil review', 'path' => '/public/PatientNakil/review'],
        ['group' => 'admin', 'label' => 'Hasta nakil incoming', 'path' => '/public/PatientNakil/incoming'],
        ['group' => 'admin', 'label' => 'e-Rapor index', 'path' => '/public/Erapor/index'],
        ['group' => 'admin', 'label' => 'Profil istatistik', 'path' => '/public/User/statsDetail'],
        ['group' => 'admin', 'label' => 'Stats field coverage', 'path' => '/public/Stats/fieldCoveragePatients'],
        ['group' => 'admin', 'label' => 'Stats data health', 'path' => '/public/Stats/dataHealthPatients'],
        ['group' => 'admin', 'label' => 'ESYS Compliance', 'path' => '/public/EsysCompliance/index'],
        ['group' => 'admin', 'label' => 'ESYS Bridge', 'path' => '/public/EsysBridge/index'],
        ['group' => 'admin', 'label' => 'USBS Bridge', 'path' => '/public/UsbsBridge/index'],
        ['group' => 'admin', 'label' => 'Audit log', 'path' => '/public/AuditLog/index'],
        ['group' => 'admin', 'label' => 'Federation bridge', 'path' => '/public/FederationBridge/index'],
        ['group' => 'admin', 'label' => 'ApiToken', 'path' => '/public/ApiToken/index'],
        ['group' => 'admin', 'label' => 'Mesaj thread', 'path' => '/public/Mesaj/thread?id=3e198f0b-fccc-4876-9dd0-6a857b69c8d3'],
        ['group' => 'admin', 'label' => 'UHDS video', 'path' => '/public/Uhds/video?id=7d61fbf9-835b-4719-aba7-71123abc6416'],
        ['group' => 'admin', 'label' => 'e-Rapor edit', 'path' => '/public/Erapor/edit?id=2eb80d43-eee1-43c2-97eb-c2c311fd5ae7'],
        ['group' => 'admin', 'label' => 'Modern FE pilot', 'path' => '/public/ModernFrontend/pilotData'],
        ['group' => 'hemsire', 'label' => 'Nobet mine (İzin/Mazeret)', 'path' => '/public/Nobet/mine', 'as' => 'demo.hemsire'],
        ['group' => 'hemsire', 'label' => 'Nobet mineIzinRows', 'path' => '/public/Nobet/mineIzinRows', 'as' => 'demo.hemsire'],
        ['group' => 'hemsire', 'label' => 'Nobet mineIstekRows', 'path' => '/public/Nobet/mineIstekRows', 'as' => 'demo.hemsire'],
        ['group' => 'hemsire', 'label' => 'Mesaj index (beklenen 403)', 'path' => '/public/Mesaj/index', 'as' => 'demo.hemsire', 'expect_fail' => true],
        ['group' => 'hemsire', 'label' => 'Randevu index', 'path' => '/public/Randevu/index', 'as' => 'demo.hemsire'],
        ['group' => 'hemsire', 'label' => 'Hasta ilaç raporu', 'path' => '/public/HastaIlacRapor/index?id=' . rawurlencode($pid), 'as' => 'demo.hemsire'],
        ['group' => 'hemsire', 'label' => 'UHDS index', 'path' => '/public/Uhds/index', 'as' => 'demo.hemsire'],
        ['group' => 'hemsire', 'label' => 'Stok index (beklenen 403)', 'path' => '/public/Stok/index', 'as' => 'demo.hemsire', 'expect_fail' => true],
    ];

    return $checks;
}

// --- main ---
$patient = manual_check_demo_patient();
$checks = buildChecks($patient);
$logBaseline = manual_check_db_error_delta($root);

/** @var list<array{ok:bool,group:string,label:string,path:string,user:string,detail:string}> */
$results = [];

$users = [
    'admin' => ['username' => 'admin', 'password' => 'Admin123'],
    'demo.hemsire' => ['username' => 'demo.hemsire', 'password' => 'Demo123'],
];

$currentUser = '';
$http = new ManualCheckHttp($base);

foreach ($checks as $check) {
    $userKey = $check['as'] ?? 'admin';
    if ($userKey !== $currentUser) {
        $cred = $users[$userKey] ?? $users['admin'];
        $okLogin = $http->login($cred['username'], $cred['password']);
        if (!$okLogin) {
            $results[] = [
                'ok' => false,
                'group' => $check['group'],
                'label' => 'LOGIN ' . $userKey,
                'path' => '/public/Auth/doLogin',
                'user' => $userKey,
                'detail' => 'Giriş başarısız',
            ];
            $currentUser = '';
            continue;
        }
        $currentUser = $userKey;
        echo "→ Oturum: {$userKey}\n";
    }

    $r = $http->get($check['path']);
    $issues = ManualCheckHttp::scanBody($r['body'], $r['code']);
    $expectFail = !empty($check['expect_fail']);
    $ok = $expectFail ? ($r['code'] === 403 || str_contains($r['body'], 'erişim yetkiniz')) : ($issues === []);
    $detail = $issues === [] ? 'HTTP ' . $r['code'] : implode('; ', $issues);
    if ($expectFail && $ok) {
        $detail = 'HTTP ' . $r['code'] . ' (beklenen erişim engeli)';
    }
    $results[] = [
        'ok' => $ok,
        'group' => $check['group'],
        'label' => $check['label'],
        'path' => $check['path'],
        'user' => $userKey,
        'detail' => $detail,
    ];
    $mark = $ok ? 'OK' : 'FAIL';
    echo "[{$mark}] [{$userKey}] {$check['label']} — {$detail}\n";
}

if ($http->login('demo.hemsire', 'Demo123')) {
    $http->refreshCsrfFromPage('/public/Nobet/mine');
    $bas = (new DateTime('+14 days'))->format('Y-m-d');
    $bit = (new DateTime('+16 days'))->format('Y-m-d');
    $post = $http->post('/public/Nobet/saveMineIzin', [
        'csrf_token' => $http->csrfToken() ?? '',
        'baslangic_tarihi' => $bas,
        'bitis_tarihi' => $bit,
        'sebep' => '[MANUAL-CHECK] test izin',
    ]);
    $issues = ManualCheckHttp::scanBody($post['body'], $post['code']);
    $redirectOk = $post['code'] >= 200 && $post['code'] < 400 && !str_contains($post['body'], 'erişim yetkiniz');
    $ok = $issues === [] && $redirectOk;
    $detail = $ok ? 'HTTP ' . $post['code'] . ' (redirect/kayıt)' : implode('; ', $issues);
    $results[] = [
        'ok' => $ok,
        'group' => 'hemsire',
        'label' => 'Nobet saveMineIzin (POST)',
        'path' => '/public/Nobet/saveMineIzin',
        'user' => 'demo.hemsire',
        'detail' => $detail,
    ];
    echo ($ok ? '[OK]' : '[FAIL]') . " [demo.hemsire] Nobet saveMineIzin — {$detail}\n";
}

$extendedOk = true;
$extendedFail = 0;
if (!$skipExtended) {
    $ext = run_manual_checklist_extended($root, $base, true);
    $extendedOk = $ext['ok'];
    foreach ($ext['results'] as $row) {
        if (!$row['ok']) {
            $extendedFail++;
            $results[] = [
                'ok' => false,
                'group' => 'extended',
                'label' => $row['label'],
                'path' => '',
                'user' => 'extended',
                'detail' => $row['detail'],
            ];
        }
    }
}

$logDelta = manual_check_db_error_delta($root) - $logBaseline;

echo "\n" . str_repeat('=', 70) . "\n";
echo "MANUEL CHECKLIST ÖZET — {$base}\n";
echo str_repeat('=', 70) . "\n";
$fail = 0;
foreach ($results as $row) {
    if (!$row['ok']) {
        $fail++;
        echo "[FAIL] [{$row['user']}] {$row['label']} — {$row['detail']}\n";
        if ($row['path'] !== '') {
            echo "       {$row['path']}\n";
        }
    }
}
$pass = count($results) - $fail;
echo str_repeat('-', 70) . "\n";
echo "Geçen: {$pass} / " . count($results) . " | db_errors yeni Sorgu Hatası: {$logDelta}\n";
if ($patient !== null) {
    echo "Test hasta id: {$patient['id']}\n";
}
$allOk = $fail === 0 && $logDelta === 0 && $extendedOk;
echo $allOk ? "Sonuç: TÜM MANUEL GET/POST KONTROLLER GEÇTİ\n" : "Sonuç: HATA VAR\n";
exit($allOk ? 0 : 1);
