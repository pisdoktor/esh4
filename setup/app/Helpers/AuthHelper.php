<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\User;
use App\Services\PermissionService;

/**
 * Oturum yetki seviyesi: #__users.isadmin → 0 personel, 1 kurum yöneticisi, 2 bölge yöneticisi, 3 sistem yöneticisi.
 */
final class AuthHelper
{
    public const ROLE_STAFF = 0;
    public const ROLE_ADMIN = 1;
    public const ROLE_SUPERADMIN = 2;
    public const ROLE_PLATFORM_OWNER = 3;
    public const MAX_ADMIN_LEVEL = 3;

    public static function clampLevel(int $level): int
    {
        return max(0, min(self::MAX_ADMIN_LEVEL, $level));
    }

    public static function sessionAdminLevel(): int
    {
        $level = null;
        if (isset($_SESSION['isadmin_level'])) {
            $level = self::clampLevel((int) $_SESSION['isadmin_level']);
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
        if ((int) $v === self::ROLE_PLATFORM_OWNER || $v === '3') {
            return self::ROLE_PLATFORM_OWNER;
        }
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

    /** Bölge yöneticisi (2) — sistem yöneticisi (3) hariç. */
    public static function sessionIsSuperAdminOnly(): bool
    {
        $level = self::sessionAdminLevel();

        return $level >= self::ROLE_SUPERADMIN && $level < self::ROLE_PLATFORM_OWNER;
    }

    public static function sessionIsPlatformOwner(): bool
    {
        return self::sessionAdminLevel() >= self::ROLE_PLATFORM_OWNER;
    }

    /** Oturumdaki kullanıcı PK (UUID string); yoksa null. */
    public static function sessionUserId(): ?string
    {
        return IdHelper::normalizeRequestId($_SESSION['user_id'] ?? null);
    }

    public static function sessionHasUser(): bool
    {
        return self::sessionUserId() !== null;
    }

    /** Platform geneli (bölge yöneticisi veya sistem yöneticisi). */
    public static function isPlatformLevel(int $level): bool
    {
        return self::clampLevel($level) >= self::ROLE_SUPERADMIN;
    }

    public static function syncSessionFromLevel(int $level): void
    {
        $level = self::clampLevel($level);
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

    public static function requirePlatformOwner(): void
    {
        if (!self::sessionIsPlatformOwner()) {
            $_SESSION['error'] = 'Bu alana erişim yetkiniz bulunmamaktadır!';
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }
    }

    public static function requirePlatformOwnerJson(): void
    {
        if (self::sessionIsPlatformOwner()) {
            return;
        }
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Bu alana erişim yetkiniz bulunmamaktadır!'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function canManageUser(int|string $targetUserId): bool
    {
        if (!IdHelper::isValidEntityId($targetUserId)) {
            return false;
        }
        if (self::sessionIsPlatformOwner()) {
            return true;
        }
        $user = new User();
        if (!$user->load((string) $targetUserId)) {
            return false;
        }
        $targetLevel = self::clampLevel((int) ($user->isadmin ?? 0));
        if (self::sessionIsSuperAdmin()) {
            if ($targetLevel > self::ROLE_ADMIN) {
                return false;
            }
            $targetKurumId = isset($user->kurum_id) ? (int) $user->kurum_id : null;

            return TenantContext::canAccessKurum($targetKurumId > 0 ? $targetKurumId : null);
        }
        if (!self::sessionIsAdmin()) {
            return false;
        }
        if (!TenantContext::canAccessKurum(isset($user->kurum_id) ? (int) $user->kurum_id : null)) {
            return false;
        }

        return $targetLevel === self::ROLE_STAFF;
    }

    public static function canManageUserLevel(int $targetLevel): bool
    {
        $targetLevel = self::clampLevel($targetLevel);
        if (self::sessionIsPlatformOwner()) {
            return true;
        }
        if (self::sessionIsSuperAdmin()) {
            return $targetLevel <= self::ROLE_ADMIN;
        }
        if (!self::sessionIsAdmin()) {
            return false;
        }

        return $targetLevel <= self::ROLE_ADMIN;
    }

    public static function canManageKurumId(?int $kurumId): bool
    {
        return TenantContext::canAccessKurum($kurumId);
    }

    /** @return int[] Oturum sahibinin atayabileceği yetki seviyeleri */
    public static function assignableAdminLevels(): array
    {
        if (self::sessionIsPlatformOwner()) {
            return [
                self::ROLE_STAFF,
                self::ROLE_ADMIN,
                self::ROLE_SUPERADMIN,
                self::ROLE_PLATFORM_OWNER,
            ];
        }
        if (self::sessionIsSuperAdmin()) {
            return [self::ROLE_STAFF, self::ROLE_ADMIN];
        }
        if (self::sessionIsAdmin()) {
            return [self::ROLE_STAFF, self::ROLE_ADMIN];
        }

        return [self::ROLE_STAFF];
    }

    public static function normalizeIsadminForStore(int $requestedLevel, ?User $target): int
    {
        $requestedLevel = self::clampLevel($requestedLevel);
        if (self::sessionIsPlatformOwner()) {
            return $requestedLevel;
        }
        if (self::sessionIsSuperAdmin()) {
            $requestedLevel = min($requestedLevel, self::ROLE_ADMIN);
            if ($target === null || empty($target->id)) {
                return $requestedLevel;
            }
            $existing = self::clampLevel((int) ($target->isadmin ?? 0));
            if ($existing >= self::ROLE_SUPERADMIN) {
                return $existing;
            }
            if ($existing >= self::ROLE_ADMIN) {
                return $existing;
            }

            return $requestedLevel;
        }
        if (!self::sessionIsAdmin()) {
            if ($target === null || empty($target->id)) {
                return self::ROLE_STAFF;
            }

            return self::clampLevel((int) ($target->isadmin ?? 0));
        }
        // Yönetici: personel veya yönetici atayabilir; üst seviye POST'u düşürülür
        $requestedLevel = min($requestedLevel, self::ROLE_ADMIN);
        if ($target === null || empty($target->id)) {
            return $requestedLevel;
        }
        $existing = self::clampLevel((int) ($target->isadmin ?? 0));
        if ($existing >= self::ROLE_SUPERADMIN) {
            return $existing;
        }
        if ($existing >= self::ROLE_ADMIN) {
            return $existing;
        }

        return $requestedLevel;
    }

    /** Bölge yöneticisi ataması yalnızca sistem yöneticisi oturumunda mümkün mü? */
    public static function canAssignSuperAdminRole(): bool
    {
        return self::sessionIsPlatformOwner();
    }

    public static function canAssignPlatformOwnerRole(): bool
    {
        return self::sessionIsPlatformOwner();
    }

    public static function countSuperadmins(): int
    {
        $db = (new User())->db;

        return (int) $db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__users WHERE isadmin = ?',
            [self::ROLE_SUPERADMIN]
        );
    }

    public static function countPlatformOwners(): int
    {
        $db = (new User())->db;

        return (int) $db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__users WHERE isadmin = ?',
            [self::ROLE_PLATFORM_OWNER]
        );
    }

    public static function adminLevelLabel(int $level): string
    {
        return match (self::clampLevel($level)) {
            self::ROLE_PLATFORM_OWNER => 'Sistem Yöneticisi',
            self::ROLE_SUPERADMIN => 'Bölge Yöneticisi',
            self::ROLE_ADMIN => 'Kurum Yöneticisi',
            default => 'Personel',
        };
    }

    public static function adminLevelBadgeClass(int $level): string
    {
        return match (self::clampLevel($level)) {
            self::ROLE_PLATFORM_OWNER => 'bg-black text-white border border-dark',
            self::ROLE_SUPERADMIN => 'bg-dark text-white border border-dark-subtle',
            self::ROLE_ADMIN => 'bg-danger-soft text-danger border border-danger-subtle',
            default => 'bg-info-soft text-info border border-info-subtle',
        };
    }

    /** Granüler izin kontrolü (RBAC). Platform seviyesi: her zaman; yönetici: admin_bypass modüllerde. */
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
