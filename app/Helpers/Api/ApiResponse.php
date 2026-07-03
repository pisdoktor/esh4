<?php
declare(strict_types=1);

namespace App\Helpers\Api;

/**
 * JSON API yanıtları.
 */
final class ApiResponse
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function json(array $payload, int $status = 200): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($status);
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @param array<string, mixed>|list<mixed> $data
     * @param array<string, mixed> $meta
     */
    public static function ok($data, array $meta = [], int $status = 200): void
    {
        self::json(array_merge(['ok' => true, 'data' => $data], $meta !== [] ? ['meta' => $meta] : []), $status);
    }

    public static function error(string $message, int $status = 400, array $extra = []): void
    {
        self::json(array_merge(['ok' => false, 'error' => $message], $extra), $status);
    }
}
