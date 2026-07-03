<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($path === '/health') {
    echo json_encode(['ok' => true, 'service' => 'esys-stub', 'time' => date('c')], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($path === '/push' && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
    $raw = file_get_contents('php://input');
    $payload = is_string($raw) ? json_decode($raw, true) : null;
    echo json_encode([
        'ok' => true,
        'service' => 'esys-stub',
        'received' => is_array($payload),
        'meta' => is_array($payload) ? ($payload['meta'] ?? null) : null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
http_response_code(404);
echo json_encode(['ok' => false, 'error' => 'not_found'], JSON_UNESCAPED_UNICODE);
