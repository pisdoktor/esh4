<?php
declare(strict_types=1);

/**
 * Manuel checklist — derin akış otomasyonu (portal OTP, REST API, export, SMS, CDS).
 *
 *   php tools/run_manual_checklist_extended.php
 *   php tools/run_manual_checklist_extended.php --base=http://localhost
 *
 * @return array{ok:bool,results:list<array{ok:bool,label:string,detail:string}>,log_delta:int}
 */
function run_manual_checklist_extended(string $root, string $base, bool $verbose = true): array
{
    require_once $root . '/tools/manual_check_http.php';
    manual_check_register_autoload($root);
    \App\Helpers\AppSettings::boot();

    /** @var list<array{ok:bool,label:string,detail:string}> */
    $results = [];
    $logBaseline = manual_check_db_error_delta($root);

    $record = static function (string $label, bool $ok, string $detail) use (&$results, $verbose): void {
        $results[] = ['ok' => $ok, 'label' => $label, 'detail' => $detail];
        if ($verbose) {
            echo ($ok ? '[OK]' : '[FAIL]') . " [extended] {$label} — {$detail}\n";
        }
    };

    if ($verbose) {
        echo "\n=== Genişletilmiş manuel akışlar ===\n";
    }

    $portalPatient = manual_check_portal_patient();
    $demoPatient = manual_check_demo_patient();
    $patientId = $demoPatient['id'] ?? 'a0000001-0001-4001-8001-000000000001';

    // --- Portal giriş (captcha + oturum) ---
    if ($portalPatient === null) {
        $record('Portal hasta verisi', false, 'Aktif hasta + ceptel1 bulunamadı');
    } elseif (!\App\Helpers\PatientPortalHelper::isEnabled()) {
        $record('Portal modülü', false, 'Patient portal kapalı');
    } else {
        $portal = new ManualCheckHttp($base, '_portal');
        $loginPage = $portal->get('/public/PatientPortal/login');
        $captcha = manual_check_solve_captcha($loginPage['body']);
        if ($captcha === null) {
            $record('Portal captcha çözümü', false, 'Güvenlik sorusu okunamadı');
        } else {
            $portal->extractCsrf($loginPage['body']);
            $otpEnabled = \App\Helpers\OperationalSettings::patientPortalOtpSmsEnabled();
            $mockLog = $root . '/storage/logs/sms_mock.log';
            $mockOffset = is_file($mockLog) ? (int) filesize($mockLog) : 0;

            $portal->post('/public/PatientPortal/doLogin', [
                'csrf_token' => $portal->csrfToken() ?? '',
                'tckimlik' => $portalPatient['tckimlik'],
                'telefon' => $portalPatient['phone'],
                'captcha_answer' => (string) $captcha,
            ]);

            if ($otpEnabled) {
                $otpPage = $portal->get('/public/PatientPortal/otp');
                $otpCode = manual_check_read_mock_sms_otp($root, $mockOffset);
                if ($otpPage['code'] !== 200 || !str_contains($otpPage['body'], 'SMS doğrulama')) {
                    $record('Portal OTP ekranı', false, 'HTTP ' . $otpPage['code']);
                } elseif ($otpCode === null) {
                    $record('Portal OTP mock SMS', false, 'sms_mock.log içinde kod yok (test modu / mock sağlayıcı gerekir)');
                } else {
                    $portal->post('/public/PatientPortal/verifyOtp', [
                        'otp_code' => $otpCode,
                    ]);
                    $index = $portal->get('/public/PatientPortal/index');
                    $ok = $index['code'] === 200
                        && str_contains($index['body'], 'Hasta portalı')
                        && !str_contains($index['url'], 'login');
                    $record('Portal OTP giriş + dashboard', $ok, $ok ? 'HTTP 200' : 'Oturum açılamadı');
                }
            } else {
                $index = $portal->get('/public/PatientPortal/index');
                $ok = $index['code'] === 200
                    && (str_contains($index['body'], 'Hasta portalı') || str_contains($index['body'], 'Planlı'))
                    && !str_contains($index['url'], 'login');
                $record('Portal doğrudan giriş (OTP kapalı)', $ok, $ok ? 'HTTP 200' : 'HTTP ' . $index['code']);
            }

            if (($results[array_key_last($results)]['ok'] ?? false) === true) {
                $indexBody = $portal->get('/public/PatientPortal/index')['body'];
                if (preg_match('/href="([^"]*(?:patientVideo|Uhds\/patientVideo)[^"]*)"/i', $indexBody, $joinMatch)) {
                    $joinPath = html_entity_decode($joinMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    if (str_starts_with($joinPath, '/')) {
                        $join = $portal->get($joinPath);
                        $joinOk = $join['code'] === 200 && !str_contains($join['body'], 'SQLSTATE[');
                        $record('Portal UHDS join URL', $joinOk, 'HTTP ' . $join['code']);
                    } else {
                        $record('Portal UHDS join URL', true, 'Join linki yok (telehealth kapalı veya randevu yok)');
                    }
                } else {
                    $record('Portal UHDS join URL', true, 'Join linki yok (telehealth kapalı veya randevu yok)');
                }
            }
        }
    }

    // --- Admin oturumu: CDS, export, SMS ---
    $admin = new ManualCheckHttp($base, '_admin_ext');
    if (!$admin->login('admin', 'Admin123')) {
        $record('Admin giriş (extended)', false, 'Giriş başarısız');
    } else {
        $dash = $admin->get('/public/Dashboard/index');
        $cdsVisible = str_contains($dash['body'], 'yüksek risk')
            || str_contains($dash['body'], 'Braden')
            || str_contains($dash['body'], 'klinik');
        try {
            $cdsCount = (new \App\Services\Clinical\ClinicalDecisionSupportService())->countOverdueHighRisk();
            $cdsOk = is_int($cdsCount) && $cdsCount >= 0;
            $detail = $cdsVisible
                ? "widget görünür, sayı={$cdsCount}"
                : "widget gizli (sayı=0), sorgu OK count={$cdsCount}";
            $record('CDS dashboard / sorgu', $cdsOk, $detail);
        } catch (Throwable $e) {
            $record('CDS dashboard / sorgu', false, $e->getMessage());
        }

        foreach (['/public/EsysBridge/export', '/public/UsbsBridge/export'] as $exportPath) {
            $label = str_contains($exportPath, 'Esys') ? 'ESYS export JSON' : 'USBS export JSON';
            $exp = $admin->get($exportPath);
            $jsonOk = $exp['code'] === 200
                && (str_starts_with(trim($exp['body']), '{') || str_starts_with(trim($exp['body']), '['));
            if (!$jsonOk && str_contains($exp['body'], 'köprüsü kapalı')) {
                $record($label, true, 'Köprü kapalı — atlandı (beklenen)');
            } else {
                $record($label, $jsonOk, 'HTTP ' . $exp['code'] . ', len=' . strlen($exp['body']));
            }
        }

        if (!\App\Helpers\AppSettings::isModuleEnabled('sms_bildirim')) {
            $record('SMS preview/send', true, 'Modül kapalı — atlandı');
        } else {
            $admin->refreshCsrfFromPage('/public/Sms/compose');
            $preview = $admin->postJson('/public/Sms/previewRecipients', [
                'segment' => 'tek_hasta',
                'params' => ['hasta_id' => $patientId],
                'govde' => '[MANUAL-CHECK] test mesajı',
                'roles' => ['hasta'],
            ]);
            $previewData = json_decode($preview['body'], true);
            $previewOk = $preview['code'] === 200 && is_array($previewData) && ($previewData['ok'] ?? false);
            $recipientCount = is_array($previewData) ? (int) ($previewData['stats']['gonderecek'] ?? 0) : 0;
            $record('SMS previewRecipients', $previewOk, $previewOk ? "alıcı={$recipientCount}" : 'HTTP ' . $preview['code']);

            if ($previewOk) {
                $send = $admin->post('/public/Sms/send', [
                    'csrf_token' => $admin->csrfToken() ?? '',
                    'segment' => 'tek_hasta',
                    'hasta_id' => $patientId,
                    'govde' => '[MANUAL-CHECK] otomatik test ' . date('His'),
                    'roles' => ['hasta'],
                ]);
                $sendOk = $send['code'] >= 200 && $send['code'] < 400
                    && !str_contains($send['body'], 'Gönderim başarısız')
                    && !str_contains($send['body'], 'erişim yetkiniz');
                $record('SMS send (mock/test)', $sendOk, 'HTTP ' . $send['code']);
            }
        }
    }

    // --- REST API Bearer ---
    if (!\App\Helpers\AppSettings::isModuleEnabled('rest_api')) {
        $record('REST API GET /patients', true, 'Modül kapalı — atlandı');
    } else {
        try {
            $pdo = manual_check_pdo();
            $adminId = $pdo->query(
                "SELECT id FROM " . DB_PREFIX . "users WHERE username='admin' LIMIT 1"
            )->fetchColumn();
            if (!is_string($adminId) || $adminId === '') {
                $record('REST API token', false, 'admin kullanıcısı yok');
            } else {
                $tokenResult = \App\Services\Api\ApiTokenService::create(
                    $adminId,
                    'manual-check-' . date('YmdHis'),
                    'read'
                );
                if (empty($tokenResult['ok']) || empty($tokenResult['token'])) {
                    $record('REST API token', false, (string) ($tokenResult['message'] ?? 'token üretilemedi'));
                } else {
                    $api = new ManualCheckHttp($base, '_api');
                    $resp = $api->getBearer('/public/api/v1/patients?limit=1', (string) $tokenResult['token']);
                    $data = json_decode($resp['body'], true);
                    $apiOk = $resp['code'] === 200
                        && is_array($data)
                        && (isset($data['data']) || isset($data['items']) || array_is_list($data));
                    $record('REST API GET /patients', $apiOk, 'HTTP ' . $resp['code']);
                }
            }
        } catch (Throwable $e) {
            $record('REST API GET /patients', false, $e->getMessage());
        }
    }

    $logDelta = manual_check_db_error_delta($root) - $logBaseline;
    $fail = 0;
    foreach ($results as $row) {
        if (!$row['ok']) {
            $fail++;
        }
    }

    if ($verbose) {
        echo str_repeat('-', 70) . "\n";
        echo 'Extended geçen: ' . (count($results) - $fail) . ' / ' . count($results)
            . " | db_errors yeni Sorgu Hatası: {$logDelta}\n";
    }

    return [
        'ok' => $fail === 0 && $logDelta === 0,
        'results' => $results,
        'log_delta' => $logDelta,
    ];
}

if (PHP_SAPI === 'cli' && realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === realpath(__FILE__)) {
    $root = dirname(__DIR__);
    require $root . '/config/config.php';
    $base = rtrim(defined('SITEURL') ? (string) SITEURL : 'http://localhost', '/');
    foreach ($argv ?? [] as $arg) {
        if (str_starts_with($arg, '--base=')) {
            $base = rtrim(substr($arg, 7), '/');
        }
    }
    $out = run_manual_checklist_extended($root, $base, true);
    exit($out['ok'] ? 0 : 1);
}
