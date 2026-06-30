<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\User;
use App\Services\PermissionService;

/**
 * Oturum yetki seviyesi: #__users.isadmin → 0 personel, 1 admin, 2 süper yönetici.
 */
final class AuthHelper
{
    public const ROLE_STAFF = 0;
    public const ROLE_ADMIN = 1;
    public const ROLE_SUPERADMIN = 2;

    public static function sessionAdminLevel(): int
    {
        $level = null;
        if (isset($_SESSION['isadmin_level'])) {
            $level = max(0, min(2, (int) $_SESSION['isadmin_level']));
        }
        $legacy = self::legacyAdminLevelFromSession();
        if ($level !== null) {
            // Eski oturumlarda isadmin=true iken isadmin_level=0 kalabiliyordu
            return $legacy !== null ? max($level, $legacy) : $level;
        }
        if ($legacy !== null) {
            return $legacy;
        }

        return self::ROLE_STAFF;
    }

    private static function legacyAdminLevelFromSession(): ?int
    {
        if (!isset($_SESSION['isadmin'])) {
            return null;
        }
        $v = $_SESSION['isadmin'];
        if ((int) $v === self::ROLE_SUPERADMIN || $v === '2') {
            return self::ROLE_SUPERADMIN;
        }
        if ($v === true || $v === 1 || $v === '1') {
            return self::ROLE_ADMIN;
        }

        return self::ROLE_STAFF;
    }

    public static function sessionIsAdmin(): bool
    {
        return self::sessionAdminLevel() >= self::ROLE_ADMIN;
    }

    public static function sessionIsSuperAdmin(): bool
    {
        return self::sessionAdminLevel() >= self::ROLE_SUPERADMIN;
    }

    public static function syncSessionFromLevel(int $level): void
    {
        $level = max(0, min(2, $level));
        $_SESSION['isadmin_level'] = $level;
        $_SESSION['isadmin'] = $level >= self::ROLE_ADMIN;
    }

    public static function requireAdmin(): void
    {
        if (!self::sessionIsAdmin()) {
            $_SESSION['error'] = 'Bu alana erişim yetkiniz bulunmamaktadır!';
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }
    }

    /** JSON dışa aktarma uçları — redirect yerine 403 + JSON. */
    public static function requireAdminJson(): void
    {
        if (self::sessionIsAdmin()) {
            return;
        }
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Bu alana erişim yetkiniz bulunmamaktadır!'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function requireSuperAdmin(): void
    {
        if (!self::sessionIsSuperAdmin()) {
            $_SESSION['error'] = 'Bu alana erişim yetkiniz bulunmamaktadır!';
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }
    }

    public static function canManageUser(int $targetUserId): bool
    {
        if (self::sessionIsSuperAdmin()) {
            return true;
        }
        if (!self::sessionIsAdmin()) {
            return false;
        }
        $user = new User();
        if (!$user->load($targetUserId)) {
            return false;
        }
        if (!TenantContext::canAccessKurum(isset($user->kurum_id) ? (int) $user->kurum_id : null)) {
            return false;
        }

        return (int) $user->isadmin === self::ROLE_STAFF;
    }

    public static function canManageKurumId(?int $kurumId): bool
    {
        if (self::sessionIsSuperAdmin()) {
            return true;
        }

        return TenantContext::canAccessKurum($kurumId);
    }

    /** @return int[] Oturum sahibinin atayabileceği yetki seviyeleri */
    public static function assignableAdminLevels(): array
    {
        if (self::sessionIsSuperAdmin()) {
            return [self::ROLE_STAFF, self::ROLE_ADMIN, self::ROLE_SUPERADMIN];
        }
        if (self::sessionIsAdmin()) {
            return [self::ROLE_STAFF, self::ROLE_ADMIN];
        }

        return [self::ROLE_STAFF];
    }

    public static function normalizeIsadminForStore(int $requestedLevel, ?User $target): int
    {
        $requestedLevel = max(0, min(2, $requestedLevel));
        if (self::sessionIsSuperAdmin()) {
            return $requestedLevel;
        }
        if (!self::sessionIsAdmin()) {
            if ($target === null || empty($target->id)) {
                return self::ROLE_STAFF;
            }

            return max(0, min(2, (int) ($target->isadmin ?? 0)));
        }
        // Yönetici: personel veya yönetici atayabilir; süper yönetici POST'u düşürülür
        $requestedLevel = min($requestedLevel, self::ROLE_ADMIN);
        if ($target === null || empty($target->id)) {
            return $requestedLevel;
        }
        $existing = max(0, min(2, (int) ($target->isadmin ?? 0)));
        if ($existing === self::ROLE_SUPERADMIN) {
            return self::ROLE_SUPERADMIN;
        }
        if ($existing >= self::ROLE_ADMIN) {
            return $existing;
        }

        return $requestedLevel;
    }

    /** Süper yönetici ataması yalnızca süper yönetici oturumunda mümkün mü? */
    public static function canAssignSuperAdminRole(): bool
    {
        return self::sessionIsSuperAdmin();
    }

    public static function countSuperadmins(): int
    {
        $db = (new User())->db;

        return (int) $db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__users WHERE isadmin = ?',
            [self::ROLE_SUPERADMIN]
        );
    }

    public static function adminLevelLabel(int $level): string
    {
        return match (max(0, min(2, $level))) {
            self::ROLE_SUPERADMIN => 'Süper Yönetici',
            self::ROLE_ADMIN => 'Yönetici',
            default => 'Personel',
        };
    }

    public static function adminLevelBadgeClass(int $level): string
    {
        return match (max(0, min(2, $level))) {
            self::ROLE_SUPERADMIN => 'bg-dark text-white border border-dark-subtle',
            self::ROLE_ADMIN => 'bg-danger-soft text-danger border border-danger-subtle',
            default => 'bg-info-soft text-info border border-info-subtle',
        };
    }

    /** Granüler izin kontrolü (RBAC). Süper yönetici: her zaman; yönetici: admin_bypass modüllerde. */
    public static function can(string $permissionSlug): bool
    {
        if (!PermissionService::enabled()) {
            return true;
        }
        if (self::sessionIsSuperAdmin()) {
            return true;
        }
        // Kurum yöneticisi: personel RBAC dışında (hibrit model)
        if (self::sessionIsAdmin()) {
            return true;
        }
        $permissions = $_SESSION['permissions'] ?? [];
        if (!is_array($permissions)) {
            return false;
        }

        return in_array($permissionSlug, $permissions, true);
    }

    public static function requirePermission(string $permissionSlug): void
    {
        if (self::can($permissionSlug)) {
            return;
        }
        $_SESSION['error'] = 'Bu alana erişim yetkiniz bulunmamaktadır!';
        header('Location: ' . esh_url('Dashboard', 'index'));
        exit;
    }

    public static function requirePermissionJson(string $permissionSlug): void
    {
        if (self::can($permissionSlug)) {
            return;
        }
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Bu alana erişim yetkiniz bulunmamaktadır!'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Oturumdaki platform rol slug (personel rolleri; admin/superadmin null). */
    public static function sessionRoleSlug(): ?string
    {
        $slug = $_SESSION['role_slug'] ?? null;

        return is_string($slug) && $slug !== '' ? $slug : null;
    }
}
