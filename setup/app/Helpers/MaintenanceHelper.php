<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Site geneli bakım modu — yalnızca sistem yöneticisi (platform owner) erişebilir.
 */
final class MaintenanceHelper
{
    public static function isActive(): bool
    {
        return OperationalSettings::isMaintenanceModeEnabled();
    }

    public static function message(): string
    {
        return OperationalSettings::maintenanceMessage();
    }

    public static function userMayBypass(): bool
    {
        return AuthHelper::sessionIsPlatformOwner();
    }

    public static function shouldBlock(string $controller, string $action): bool
    {
        if (!self::isActive()) {
            return false;
        }
        if (self::userMayBypass()) {
            return false;
        }
        $controller = trim($controller);
        $action = trim($action);
        if ($controller === 'Auth' && in_array($action, ['login', 'logout', 'doLogin', 'eimzaLogin', 'eimzaChallenge'], true)) {
            return false;
        }

        return true;
    }

    public static function respondBlocked(): void
    {
        if (!headers_sent()) {
            http_response_code(503);
            header('Retry-After: 300');
        }

        if (CsrfHelper::isJsonClientRequest()) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'ok' => false,
                'maintenance' => true,
                'error' => self::message(),
                'mesaj' => self::message(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $eshMaintenanceMessage = self::message();
        $eshMaintenanceHasSession = isset($_SESSION['user_id']);
        include ROOT_PATH . '/views/guest/maintenance.php';
        exit;
    }

    /**
     * Giriş sırasında bakım modu: yalnızca sistem yöneticisi oturum açabilir.
     */
    public static function rejectLoginIfBlocked(int $adminLevel): ?string
    {
        if (!self::isActive()) {
            return null;
        }
        if ($adminLevel >= AuthHelper::ROLE_PLATFORM_OWNER) {
            return null;
        }

        return self::message();
    }
}
