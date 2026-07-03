<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$cfg = require __DIR__ . '/bridge.config.example.php';
$localCfgPath = __DIR__ . '/bridge.config.php';
if (is_file($localCfgPath)) {
    $loaded = require $localCfgPath;
    if (is_array($loaded)) {
        $cfg = array_merge($cfg, $loaded);
    }
}

$allowedOrigin = (string) ($cfg['allowed_origin'] ?? 'http://localhost');
if ($allowedOrigin !== '') {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
}
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function jsonOut(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function runCmd(array $parts): array
{
    $cmd = [];
    foreach ($parts as $p) {
        $cmd[] = escapeshellarg((string) $p);
    }
    $line = implode(' ', $cmd);
    $descriptors = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $proc = proc_open($line, $descriptors, $pipes, null, null);
    if (!is_resource($proc)) {
        return ['ok' => false, 'code' => -1, 'stdout' => '', 'stderr' => 'Komut başlatılamadı'];
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);

    return [
        'ok' => $code === 0,
        'code' => $code,
        'stdout' => (string) $stdout,
        'stderr' => (string) $stderr,
    ];
}

function tokenBaseCommand(array $cfg): array
{
    $bin = trim((string) ($cfg['pkcs11_tool_bin'] ?? 'pkcs11-tool'));
    $module = trim((string) ($cfg['pkcs11_module'] ?? ''));
    if ($bin === '' || $module === '') {
        return [];
    }
    $cmd = [$bin, '--module', $module];
    $slot = $cfg['slot_index'] ?? null;
    if ($slot !== null && $slot !== '') {
        $cmd[] = '--slot-index';
        $cmd[] = (string) ((int) $slot);
    }

    return $cmd;
}

function certDerToPem(string $derBin): string
{
    $b64 = chunk_split(base64_encode($derBin), 64, "\n");

    return "-----BEGIN CERTIFICATE-----\n" . trim($b64) . "\n-----END CERTIFICATE-----\n";
}

function extractTcFromCertPem(string $pem): string
{
    $parsed = @openssl_x509_parse($pem);
    if (!is_array($parsed)) {
        return '';
    }
    $subject = isset($parsed['subject']) && is_array($parsed['subject']) ? $parsed['subject'] : [];
    foreach (['serialNumber', 'SN', 'UID'] as $key) {
        if (!isset($subject[$key])) {
            continue;
        }
        $digits = preg_replace('/\D+/', '', (string) $subject[$key]);
        if (strlen($digits) === 11) {
            return $digits;
        }
    }

    return '';
}

$path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

$baseCmd = tokenBaseCommand($cfg);
if ($baseCmd === []) {
    jsonOut(['ok' => false, 'error' => 'pkcs11_tool_bin veya pkcs11_module ayarı eksik.'], 500);
}
$certId = trim((string) ($cfg['cert_id_hex'] ?? ''));
if ($certId === '') {
    jsonOut(['ok' => false, 'error' => 'cert_id_hex ayarı eksik.'], 500);
}

if ($method === 'GET' && $path === '/health') {
    $listRes = runCmd(array_merge($baseCmd, ['-L']));
    if (!$listRes['ok']) {
        jsonOut([
            'ok' => false,
            'token_present' => false,
            'error' => 'Token veya PKCS#11 modülü okunamadı',
            'detail' => trim((string) $listRes['stderr']),
        ], 503);
    }

    $tmpDer = tempnam(sys_get_temp_dir(), 'eimza-cert-');
    $readRes = runCmd(array_merge($baseCmd, ['--read-object', '--type', 'cert', '--id', $certId, '--output-file', $tmpDer]));
    if (!$readRes['ok'] || !is_file($tmpDer)) {
        @unlink($tmpDer);
        jsonOut([
            'ok' => false,
            'token_present' => true,
            'error' => 'Sertifika okunamadı (cert_id_hex kontrol edin).',
            'detail' => trim((string) $readRes['stderr']),
        ], 503);
    }
    $der = (string) file_get_contents($tmpDer);
    @unlink($tmpDer);
    $pem = certDerToPem($der);
    $tc = extractTcFromCertPem($pem);

    jsonOut([
        'ok' => true,
        'token_present' => true,
        'tc_kimlikno' => $tc,
    ]);
}

if ($method === 'POST' && $path === '/sign') {
    $raw = file_get_contents('php://input');
    $body = json_decode((string) $raw, true);
    if (!is_array($body)) {
        jsonOut(['ok' => false, 'error' => 'JSON body geçersiz.'], 400);
    }
    $challenge = (string) ($body['challenge'] ?? '');
    $pin = (string) ($body['pin'] ?? '');
    if ($challenge === '' || $pin === '') {
        jsonOut(['ok' => false, 'error' => 'challenge ve pin zorunlu.'], 400);
    }

    $inFile = tempnam(sys_get_temp_dir(), 'eimza-in-');
    $outFile = tempnam(sys_get_temp_dir(), 'eimza-out-');
    file_put_contents($inFile, $challenge);

    $signRes = runCmd(array_merge(
        $baseCmd,
        ['--login', '--pin', $pin, '--sign', '--mechanism', 'SHA256-RSA-PKCS', '--id', $certId, '--input-file', $inFile, '--output-file', $outFile]
    ));
    @unlink($inFile);
    if (!$signRes['ok']) {
        @unlink($outFile);
        jsonOut([
            'ok' => false,
            'error' => 'İmzalama başarısız (PIN veya token durumu).',
            'detail' => trim((string) $signRes['stderr']),
        ], 403);
    }

    $signatureRaw = (string) file_get_contents($outFile);
    @unlink($outFile);
    if ($signatureRaw === '') {
        jsonOut(['ok' => false, 'error' => 'İmza çıktısı boş.'], 500);
    }

    $tmpDer = tempnam(sys_get_temp_dir(), 'eimza-cert-');
    $readRes = runCmd(array_merge($baseCmd, ['--read-object', '--type', 'cert', '--id', $certId, '--output-file', $tmpDer]));
    if (!$readRes['ok'] || !is_file($tmpDer)) {
        @unlink($tmpDer);
        jsonOut([
            'ok' => false,
            'error' => 'Sertifika okunamadı.',
            'detail' => trim((string) $readRes['stderr']),
        ], 500);
    }
    $der = (string) file_get_contents($tmpDer);
    @unlink($tmpDer);
    $pem = certDerToPem($der);
    $tc = extractTcFromCertPem($pem);

    jsonOut([
        'ok' => true,
        'signature_b64' => base64_encode($signatureRaw),
        'certificate_pem' => $pem,
        'tc_kimlikno' => $tc,
    ]);
}

jsonOut(['ok' => false, 'error' => 'Endpoint bulunamadı.'], 404);
