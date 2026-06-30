<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Oturum tabanlı CSRF koruması.
 */
final class CsrfHelper
{
    public const SESSION_KEY = 'esh_csrf_token';
    public const POST_FIELD = 'csrf_token';
    public const HEADER_NAME = 'HTTP_X_CSRF_TOKEN';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[self::SESSION_KEY];
    }

    public static function regenerate(): string
    {
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));

        return (string) $_SESSION[self::SESSION_KEY];
    }

    public static function hiddenField(): string
    {
        $t = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');

        return '<input type="hidden" name="' . self::POST_FIELD . '" value="' . $t . '">';
    }

    public static function metaTag(): string
    {
        $t = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');

        return '<meta name="csrf-token" content="' . $t . '">';
    }

    public static function submittedToken(): string
    {
        if (isset($_POST[self::POST_FIELD]) && is_string($_POST[self::POST_FIELD])) {
            return $_POST[self::POST_FIELD];
        }
        if (isset($_SERVER[self::HEADER_NAME]) && is_string($_SERVER[self::HEADER_NAME])) {
            return $_SERVER[self::HEADER_NAME];
        }

        return '';
    }

    public static function validate(?string $token = null): bool
    {
        $token = $token ?? self::submittedToken();
        if ($token === '' || !isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        return hash_equals((string) $_SESSION[self::SESSION_KEY], $token);
    }

    /**
     * @param array<string, list<string>> $exempt controller => [actions]
     */
    public static function isExemptRoute(string $controller, string $action, array $exempt): bool
    {
        if (!isset($exempt[$controller])) {
            return false;
        }

        return in_array($action, $exempt[$controller], true);
    }

    /** Silme / mutasyon uçları — GET ile tetiklenmeyi engeller. */
    public static function requirePostMethod(string $redirectUrl): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            return;
        }
        header('Location: ' . $redirectUrl);
        exit;
    }

    public static function isJsonClientRequest(): bool
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

        return str_contains($accept, 'application/json')
            || str_contains((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''), 'XMLHttpRequest');
    }

    public static function enforcePost(string $controller, string $action): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return;
        }

        $exempt = [
            'Auth' => ['eimzaChallenge', 'eimzaLogin'],
        ];
        if (self::isExemptRoute($controller, $action, $exempt)) {
            return;
        }

        if (self::validate()) {
            return;
        }

        $isJson = self::isJsonClientRequest();

        if ($isJson) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            http_response_code(403);
            $msg = 'Geçersiz veya eksik güvenlik anahtarı (CSRF). Sayfayı yenileyip tekrar deneyin.';
            echo json_encode(['ok' => false, 'error' => $msg, 'mesaj' => $msg], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $_SESSION['error'] = 'Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyip tekrar deneyin.';
        $loginUrl = esh_url('Auth', 'login');
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $loginUrl);
            exit;
        }
        header('Location: ' . esh_url('Dashboard', 'index'));
        exit;
    }
}
