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
    public const SESSION_ASSIGNED_BOLGE_ID = 'assigned_bolge_id';

    public static function syncSessionFromUser(?int $kurumId, int $isadminLevel, ?int $bolgeId = null): void
    {
        if ($isadminLevel >= AuthHelper::ROLE_SUPERADMIN) {
            $_SESSION[self::SESSION_KURUM_ID] = null;
        } else {
            $_SESSION[self::SESSION_KURUM_ID] = ($kurumId !== null && $kurumId > 0) ? (int) $kurumId : 1;
        }

        if ($isadminLevel === AuthHelper::ROLE_SUPERADMIN && $bolgeId !== null && $bolgeId > 0) {
            $_SESSION[self::SESSION_ASSIGNED_BOLGE_ID] = (int) $bolgeId;
            FederationContext::clearBolgeFilter();
        } else {
            unset($_SESSION[self::SESSION_ASSIGNED_BOLGE_ID]);
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

    /** Süper yönetici hesabına sistem sahibi tarafından atanan zorunlu bölge (yalnız seviye 2). */
    public static function sessionAssignedBolgeId(): ?int
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !is_array($_SESSION ?? null)) {
            return null;
        }
        if (!AuthHelper::sessionIsSuperAdminOnly()) {
            return null;
        }
        if (!isset($_SESSION[self::SESSION_ASSIGNED_BOLGE_ID])) {
            return null;
        }
        $v = (int) $_SESSION[self::SESSION_ASSIGNED_BOLGE_ID];

        return $v > 0 ? $v : null;
    }

    /** Bölge atanmış süper yönetici — veri kapsamı sabit bölge ile sınırlı. */
    public static function sessionIsBolgeLockedSuperAdmin(): bool
    {
        return self::sessionAssignedBolgeId() !== null;
    }

    /**
     * Liste / SQL kapsamı için efektif bölge.
     * Sistem sahibi: isteğe bağlı oturum filtresi; bölge kilitli süper yönetici: atanan bölge.
     */
    public static function effectiveBolgeFilterId(): ?int
    {
        if (!AuthHelper::sessionIsSuperAdmin()) {
            return null;
        }
        if (AuthHelper::sessionIsPlatformOwner()) {
            return FederationContext::sessionBolgeFilter();
        }
        $assigned = self::sessionAssignedBolgeId();
        if ($assigned !== null) {
            return $assigned;
        }

        return FederationContext::sessionBolgeFilter();
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
        if (!self::isKurumInScope((int) $kurumId, false)) {
            return;
        }
        $_SESSION[self::SESSION_KURUM_FILTER] = (int) $kurumId;
    }

    /**
     * SQL kapsamı için efektif kurum kimlikleri.
     * null = tüm kurumlar (süper yönetici, filtre yok).
     * [] = eşleşen kurum yok (bölge filtresi boş).
     *
     * @return list<int>|null
     */
    public static function filterKurumIds(): ?array
    {
        if (!AuthHelper::sessionIsSuperAdmin()) {
            $kid = self::sessionKurumId();

            return $kid !== null && $kid > 0 ? [$kid] : null;
        }

        $kurumFilter = self::sessionKurumFilter();
        if ($kurumFilter !== null && $kurumFilter > 0) {
            if (!self::isKurumInScope($kurumFilter, false)) {
                return [];
            }

            return [$kurumFilter];
        }

        $bolgeScoped = self::kurumIdsForBolgeScope();
        if ($bolgeScoped !== null) {
            return $bolgeScoped;
        }

        return null;
    }

    /**
     * @return list<int>|null null = bölge kısıtı yok
     */
    private static function kurumIdsForBolgeScope(): ?array
    {
        $bolgeId = self::effectiveBolgeFilterId();
        if ($bolgeId === null || $bolgeId <= 0) {
            return null;
        }
        if (!class_exists(FederationHelper::class) || !FederationHelper::columnsReady()) {
            return null;
        }

        return FederationHelper::activeKurumIdsForBolge($bolgeId);
    }

    public static function isKurumInScope(int $kurumId, bool $respectKurumFilter = true): bool
    {
        if ($kurumId <= 0) {
            return false;
        }
        if (!AuthHelper::sessionIsSuperAdmin()) {
            $sessionKid = self::sessionKurumId();

            return $sessionKid !== null && (int) $sessionKid === $kurumId;
        }

        if ($respectKurumFilter) {
            $kurumFilter = self::sessionKurumFilter();
            if ($kurumFilter !== null && $kurumFilter > 0) {
                return (int) $kurumFilter === $kurumId;
            }
        }

        $bolgeScoped = self::kurumIdsForBolgeScope();
        if ($bolgeScoped !== null) {
            if ($bolgeScoped === []) {
                return false;
            }

            return in_array($kurumId, $bolgeScoped, true);
        }

        return true;
    }

    /**
     * Form / navbar kurum listeleri için bölge filtresi (kurum filtresi yokken).
     */
    public static function kurumListBolgeFilterId(): ?int
    {
        if (!AuthHelper::sessionIsSuperAdmin()) {
            return null;
        }

        return self::effectiveBolgeFilterId();
    }

    /**
     * Oturum kapsamına göre kurum listesi (navbar, formlar, admin listeler).
     *
     * @return list<object>
     */
    public static function kurumListForScope(bool $onlyActive = true, string $orderFragment = 'ad ASC'): array
    {
        if (!AuthHelper::sessionIsSuperAdmin()) {
            $kid = self::sessionKurumId();
            if ($kid === null || $kid <= 0) {
                return [];
            }
            $model = new \App\Models\Kurum();
            if (!$model->load($kid)) {
                return [];
            }
            if ($onlyActive && empty($model->aktif)) {
                return [];
            }

            return [$model];
        }

        $bolgeId = self::kurumListBolgeFilterId();
        if ($bolgeId !== null && $bolgeId > 0) {
            return (new \App\Models\Kurum())->getList($onlyActive, $orderFragment, $bolgeId);
        }

        $kurumFilter = self::sessionKurumFilter();
        if ($kurumFilter !== null && $kurumFilter > 0) {
            $model = new \App\Models\Kurum();
            if (!$model->load($kurumFilter)) {
                return [];
            }
            if ($onlyActive && empty($model->aktif)) {
                return [];
            }

            return [$model];
        }

        return (new \App\Models\Kurum())->getList($onlyActive, $orderFragment, null);
    }

    /**
     * Önbellek anahtarı için oturum kapsam etiketi.
     */
    public static function scopeCacheKey(): string
    {
        $ids = self::filterKurumIds();
        if ($ids === null) {
            return 'all';
        }
        if ($ids === []) {
            return 'none';
        }
        $ids = array_map('intval', $ids);
        sort($ids);

        return 'ids_' . implode('_', $ids);
    }

    /**
     * Süper yönetici — bölge/kurum filtresine göre varsayılan kurum (yeni kayıt, uyarılar).
     */
    public static function defaultKurumIdForSuperAdmin(): int
    {
        $filter = self::sessionKurumFilter();
        if ($filter !== null && $filter > 0) {
            return $filter;
        }
        $ids = self::filterKurumIds();
        if (is_array($ids) && $ids !== []) {
            return (int) $ids[0];
        }

        return 1;
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
        $ids = self::filterKurumIds();
        if (is_array($ids) && count($ids) === 1) {
            return (int) $ids[0];
        }
        if (AuthHelper::sessionIsSuperAdmin()) {
            throw new \RuntimeException('Kurum kapsamı gerekli; süper yönetici için kurum filtresi seçin.');
        }
        throw new \RuntimeException('Oturum kurum bilgisi eksik.');
    }

    public static function canAccessKurum(?int $kurumId): bool
    {
        if ($kurumId === null || $kurumId <= 0) {
            return AuthHelper::sessionIsSuperAdmin() && self::filterKurumIds() === null;
        }
        if (!AuthHelper::sessionIsSuperAdmin()) {
            $sessionKid = self::sessionKurumId();
            if ($sessionKid === null) {
                return false;
            }

            return (int) $kurumId === $sessionKid;
        }

        return self::isKurumInScope((int) $kurumId);
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
            $ids = self::filterKurumIds();
            if ($ids !== null) {
                if ($ids === [] || !in_array((int) $recordKurumId, $ids, true)) {
                    self::deny($message);
                }

                return;
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
                if (!self::isKurumInScope((int) $requestedKurumId)) {
                    return self::defaultKurumIdForSuperAdmin();
                }

                return (int) $requestedKurumId;
            }
            $filter = self::sessionKurumFilter();
            if ($filter !== null && self::isKurumInScope($filter)) {
                return $filter;
            }

            return self::defaultKurumIdForSuperAdmin();
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
