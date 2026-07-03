<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Federasyon bölge filtresi — süper yönetici oturumu.
 */
final class FederationContext
{
    public const SESSION_BOLGE_FILTER = 'bolge_filter';

    public static function sessionBolgeFilter(): ?int
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !is_array($_SESSION ?? null)) {
            return null;
        }
        if (!AuthHelper::sessionIsSuperAdmin()) {
            return null;
        }
        if (!isset($_SESSION[self::SESSION_BOLGE_FILTER])) {
            return null;
        }
        $v = (int) $_SESSION[self::SESSION_BOLGE_FILTER];

        return $v > 0 ? $v : null;
    }

    public static function setSessionBolgeFilter(?int $bolgeId): void
    {
        if (!AuthHelper::sessionIsSuperAdmin()) {
            return;
        }
        if (TenantContext::sessionIsBolgeLockedSuperAdmin()) {
            return;
        }
        if (!AuthHelper::sessionIsPlatformOwner()) {
            return;
        }
        if ($bolgeId === null || $bolgeId <= 0) {
            unset($_SESSION[self::SESSION_BOLGE_FILTER]);

            return;
        }
        $_SESSION[self::SESSION_BOLGE_FILTER] = (int) $bolgeId;
        TenantContext::setSessionKurumFilter(null);
    }

    public static function clearBolgeFilter(): void
    {
        unset($_SESSION[self::SESSION_BOLGE_FILTER]);
    }
}
