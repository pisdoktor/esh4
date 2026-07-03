<?php
declare(strict_types=1);

namespace App\Helpers\Api;

use App\Helpers\AuthHelper;
use App\Helpers\RateLimitHelper;
use App\Helpers\TenantContext;
use App\Services\Api\ApiTokenService;
use App\Services\PermissionService;

/**
 * Bearer token kimlik doğrulama ve oturum bağlamı.
 */
final class ApiAuth
{
    /** @var object|null */
    private static $tokenRow = null;

    public static function bearerFromRequest(): string
    {
        $auth = (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if ($auth !== '' && preg_match('/^\s*Bearer\s+(.+)$/i', $auth, $m)) {
            return trim($m[1]);
        }
        $apiKey = trim((string) ($_SERVER['HTTP_X_API_KEY'] ?? ''));
        if ($apiKey !== '') {
            return $apiKey;
        }

        return '';
    }

    /**
     * @return object Token satırı
     */
    public static function requireAuth(): object
    {
        if (!\App\Helpers\AppSettings::isModuleEnabled('rest_api')) {
            ApiResponse::error('REST API modülü kapalı.', 503);
        }
        if (!ApiTokenService::tableReady()) {
            ApiResponse::error('API token altyapısı kurulu değil.', 503);
        }

        $bearer = self::bearerFromRequest();
        if ($bearer === '') {
            ApiResponse::error('Authorization: Bearer token gerekli.', 401);
        }

        $ip = RateLimitHelper::clientIp();
        if (RateLimitHelper::tooManyAttempts('api_token_ip', $ip, 120, 60)) {
            ApiResponse::error('Çok fazla istek. Lütfen kısa süre sonra tekrar deneyin.', 429);
        }
        RateLimitHelper::hit('api_token_ip', $ip, 60);

        $auth = ApiTokenService::authenticate($bearer);
        if (empty($auth['ok'])) {
            ApiResponse::error((string) ($auth['message'] ?? 'Yetkisiz.'), 401);
        }

        /** @var object $tokenRow */
        $tokenRow = $auth['token_row'];
        /** @var object $user */
        $user = $auth['user'];
        self::$tokenRow = $tokenRow;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['user_id'] = (int) $user->id;
        $_SESSION['name'] = (string) ($user->name ?? '');
        $_SESSION['username'] = (string) ($user->username ?? '');
        $adminLevel = AuthHelper::clampLevel((int) ($user->isadmin ?? 0));
        AuthHelper::syncSessionFromLevel($adminLevel);
        PermissionService::syncSessionPermissions((int) $user->id, $adminLevel);
        TenantContext::syncSessionFromUser(
            isset($user->kurum_id) ? (int) $user->kurum_id : null,
            $adminLevel,
            isset($user->bolge_id) ? (int) $user->bolge_id : null
        );

        return $tokenRow;
    }

    public static function requireScope(object $tokenRow, string $resource): void
    {
        if (!ApiTokenService::allowsScope($tokenRow, $resource)) {
            ApiResponse::error('Bu kaynak için token kapsamı yetersiz.', 403);
        }
    }

    public static function requireWriteScope(object $tokenRow, string $resource): void
    {
        if (!ApiTokenService::allowsWrite($tokenRow, $resource)) {
            ApiResponse::error('Yazma işlemi için token kapsamında write veya kaynak yetkisi gerekli.', 403);
        }
    }

    public static function pagination(): array
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($_GET['per_page'] ?? 25)));

        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
    }
}
