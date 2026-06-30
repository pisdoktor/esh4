<?php
/**
 * Overpass API'ye ham sorgu POST eder (stdout veya -o dosya).
 * Kullanım: php tools/fetch_overpass.php tools/overpass_bbox_admin8.txt storage/bbox_admin8.json
 */
declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "Kullanım: php fetch_overpass.php <sorgu.txt> <çıktı.json>\n");
    exit(1);
}

$queryPath = $argv[1];
$outPath = $argv[2];
if (!is_readable($queryPath)) {
    fwrite(STDERR, "Okunamıyor: $queryPath\n");
    exit(1);
}

$ql = file_get_contents($queryPath);
if ($ql === false || trim($ql) === '') {
    fwrite(STDERR, "Boş sorgu.\n");
    exit(1);
}

$url = getenv('OVERPASS_URL') ?: 'https://overpass.openstreetmap.fr/api/interpreter';
$ctx = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: ESH-geo-fetch/1.0 (local dev)\r\n",
        'content' => 'data=' . rawurlencode($ql),
        'timeout' => 600,
        'ignore_errors' => true,
    ],
]);

$raw = @file_get_contents($url, false, $ctx);
if ($raw === false) {
    fwrite(STDERR, "HTTP isteği başarısız.\n");
    exit(1);
}

if (str_starts_with(ltrim($raw), '<!DOCTYPE')) {
    fwrite(STDERR, "Sunucu HTML döndü (muhtemelen hata). İlk 200 byte:\n" . substr($raw, 0, 200) . "\n");
    exit(1);
}

if (!str_starts_with(ltrim($raw), '{')) {
    $dbg = $outPath . '.http-debug.txt';
    file_put_contents($dbg, $raw);
    fwrite(STDERR, "JSON bekleniyordu; ham yanıt: $dbg\nİlk 200 byte:\n" . substr($raw, 0, 200) . "\n");
    if (!empty($http_response_header)) {
        fwrite(STDERR, implode("\n", $http_response_header) . "\n");
    }
    exit(1);
}

if (file_put_contents($outPath, $raw) === false) {
    fwrite(STDERR, "Yazılamadı: $outPath\n");
    exit(1);
}

$j = json_decode($raw, true);
$cnt = is_array($j['elements'] ?? null) ? count($j['elements']) : 0;
echo "OK: $outPath (elements: $cnt)\n";
