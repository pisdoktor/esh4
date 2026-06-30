<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Oturum bazlı kurum (tenant) bağlamı — tek DB, kurum_id ile izolasyon.
 */
final class TenantContext
{
    public const SESSION_KURUM_ID = 'kurum_id';
    public const SESSION_KURUM_FILTER = 'kurum_filter';

    public static function syncSessionFromUser(?int $kurumId, int $isadminLevel): void
    {
        if ($isadminLevel >= AuthHelper::ROLE_SUPERADMIN) {
            $_SESSION[self::SESSION_KURUM_ID] = null;
        } else {
            $_SESSION[self::SESSION_KURUM_ID] = ($kurumId !== null && $kurumId > 0) ? (int) $kurumId : 1;
        }
    }

    /** Oturumdaki kullanıcının bağlı olduğu kurum (superadmin → null). */
    public static function sessionKurumId(): ?int
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !is_array($_SESSION ?? null)) {
            return null;
        }
        if (!array_key_exists(self::SESSION_KURUM_ID, $_SESSION)) {
            return null;
        }
        $v = $_SESSION[self::SESSION_KURUM_ID];
        if ($v === null || $v === '' || (int) $v <= 0) {
            return null;
        }

        return (int) $v;
    }

    /** Süper yönetici için isteğe bağlı liste filtresi. */
    public static function sessionKurumFilter(): ?int
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !is_array($_SESSION ?? null)) {
            return null;
        }
        if (!AuthHelper::sessionIsSuperAdmin()) {
            return self::sessionKurumId();
        }
        if (!isset($_SESSION[self::SESSION_KURUM_FILTER])) {
            return null;
        }
        $v = (int) $_SESSION[self::SESSION_KURUM_FILTER];

        return $v > 0 ? $v : null;
    }

    public static function setSessionKurumFilter(?int $kurumId): void
    {
        if (!AuthHelper::sessionIsSuperAdmin()) {
            return;
        }
        if ($kurumId === null || $kurumId <= 0) {
            unset($_SESSION[self::SESSION_KURUM_FILTER]);
            return;
        }
        $_SESSION[self::SESSION_KURUM_FILTER] = (int) $kurumId;
    }

    /**
     * SQL sorgularında kullanılacak efektif kurum (null = tüm kurumlar, yalnızca superadmin).
     */
    public static function filterKurumId(): ?int
    {
        if (AuthHelper::sessionIsSuperAdmin()) {
            return self::sessionKurumFilter();
        }

        return self::sessionKurumId();
    }

    /** Personel/admin için zorunlu kurum; superadmin tüm kurum modunda exception. */
    public static function requireKurumScope(): int
    {
        $kid = self::filterKurumId();
        if ($kid !== null && $kid > 0) {
            return $kid;
        }
        if (AuthHelper::sessionIsSuperAdmin()) {
            throw new \RuntimeException('Kurum kapsamı gerekli; süper yönetici için kurum filtresi seçin.');
        }
        throw new \RuntimeException('Oturum kurum bilgisi eksik.');
    }

    public static function canAccessKurum(?int $kurumId): bool
    {
        if (AuthHelper::sessionIsSuperAdmin()) {
            return true;
        }
        $sessionKid = self::sessionKurumId();
        if ($sessionKid === null || $kurumId === null) {
            return false;
        }

        return (int) $kurumId === $sessionKid;
    }

    public static function assertRecordKurum(?int $recordKurumId, string $message = 'Bu kayda erişim yetkiniz bulunmamaktadır.'): void
    {
        if ($recordKurumId === null || $recordKurumId <= 0) {
            if (AuthHelper::sessionIsSuperAdmin()) {
                return;
            }
            self::deny($message);
        }
        if (AuthHelper::sessionIsSuperAdmin()) {
            $filter = self::sessionKurumFilter();
            if ($filter !== null && (int) $recordKurumId !== $filter) {
                self::deny($message);
            }

            return;
        }
        $sessionKid = self::sessionKurumId();
        if ($sessionKid === null || (int) $recordKurumId !== $sessionKid) {
            self::deny($message);
        }
    }

    /** Yeni kayıt oluştururken atanacak kurum_id. */
    public static function assignKurumIdForStore(?int $requestedKurumId = null): int
    {
        if (AuthHelper::sessionIsSuperAdmin()) {
            if ($requestedKurumId !== null && $requestedKurumId > 0) {
                return (int) $requestedKurumId;
            }
            $filter = self::sessionKurumFilter();
            if ($filter !== null) {
                return $filter;
            }

            return 1;
        }

        return self::requireKurumScope();
    }

    private static function deny(string $message): void
    {
        $_SESSION['error'] = $message;
        if (!headers_sent()) {
            header('Location: ' . esh_url('Dashboard', 'index'));
        }
        exit;
    }
}
