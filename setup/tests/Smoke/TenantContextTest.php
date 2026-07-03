<?php
declare(strict_types=1);

namespace Tests\Smoke;

use App\Helpers\AuthHelper;
use App\Helpers\FederationContext;
use App\Helpers\TenantContext;
use App\Helpers\TenantSqlHelper;
use Tests\Support\SessionTestCase;

final class TenantContextTest extends SessionTestCase
{
    public function test_staff_session_kurum_id(): void
    {
        $this->actAsStaff(3, 4);
        self::assertSame(4, TenantContext::sessionKurumId());
        self::assertSame(4, TenantContext::filterKurumId());
    }

    public function test_superadmin_session_kurum_null(): void
    {
        $this->actAsSuperAdmin();
        self::assertNull(TenantContext::sessionKurumId());
        self::assertNull(TenantContext::filterKurumId());
    }

    public function test_superadmin_kurum_filter(): void
    {
        $this->actAsSuperAdmin(1, 3);
        self::assertSame(3, TenantContext::sessionKurumFilter());
        self::assertSame(3, TenantContext::filterKurumId());
        self::assertSame([3], TenantContext::filterKurumIds());
    }

    public function test_filter_kurum_ids_staff(): void
    {
        $this->actAsStaff(1, 4);
        self::assertSame([4], TenantContext::filterKurumIds());
    }

    public function test_can_access_kurum_staff(): void
    {
        $this->actAsStaff(1, 2);
        self::assertTrue(TenantContext::canAccessKurum(2));
        self::assertFalse(TenantContext::canAccessKurum(1));
    }

    public function test_can_access_kurum_superadmin(): void
    {
        $this->actAsSuperAdmin();
        self::assertTrue(TenantContext::canAccessKurum(99));
    }

    public function test_assign_kurum_for_store_staff(): void
    {
        $this->actAsStaff(1, 5);
        self::assertSame(5, TenantContext::assignKurumIdForStore());
    }

    public function test_assign_kurum_for_store_superadmin_requested(): void
    {
        $this->actAsSuperAdmin();
        self::assertSame(7, TenantContext::assignKurumIdForStore(7));
    }

    public function test_assign_kurum_for_store_superadmin_filter_fallback(): void
    {
        $this->actAsSuperAdmin(1, 8);
        self::assertSame(8, TenantContext::assignKurumIdForStore());
    }

    public function test_sync_session_superadmin_clears_kurum(): void
    {
        TenantContext::syncSessionFromUser(5, AuthHelper::ROLE_SUPERADMIN, null);
        self::assertNull(TenantContext::sessionKurumId());
    }

    public function test_superadmin_assigned_bolge_scope(): void
    {
        $this->actAsSuperAdmin(1, null, 7);
        self::assertSame(7, TenantContext::sessionAssignedBolgeId());
        self::assertTrue(TenantContext::sessionIsBolgeLockedSuperAdmin());
        self::assertSame(7, TenantContext::effectiveBolgeFilterId());
    }

    public function test_bolge_locked_superadmin_cannot_use_voluntary_bolge_filter(): void
    {
        $this->actAsSuperAdmin(1, null, 3);
        FederationContext::setSessionBolgeFilter(9);
        self::assertSame(3, TenantContext::effectiveBolgeFilterId());
    }

    public function test_platform_owner_uses_session_bolge_filter(): void
    {
        $this->actAsPlatformOwner();
        FederationContext::setSessionBolgeFilter(4);
        self::assertSame(4, TenantContext::effectiveBolgeFilterId());
        self::assertNull(TenantContext::sessionAssignedBolgeId());
    }

    public function test_sync_session_platform_owner_clears_kurum(): void
    {
        TenantContext::syncSessionFromUser(5, AuthHelper::ROLE_PLATFORM_OWNER);
        self::assertNull(TenantContext::sessionKurumId());
    }

    public function test_scope_cache_key_superadmin_no_filter(): void
    {
        $this->actAsSuperAdmin();
        self::assertSame('all', TenantContext::scopeCacheKey());
    }

    public function test_scope_cache_key_superadmin_kurum_filter(): void
    {
        $this->actAsSuperAdmin(1, 3);
        self::assertSame('ids_3', TenantContext::scopeCacheKey());
    }

    public function test_kurum_list_for_scope_staff(): void
    {
        $this->actAsStaff(1, 4);
        $list = TenantContext::kurumListForScope(true);
        self::assertCount(1, $list);
        self::assertSame(4, (int) ($list[0]->id ?? 0));
    }

    public function test_kurum_filter_does_not_clear_bolge_filter(): void
    {
        $this->actAsSuperAdmin(1, 5);
        self::assertSame(5, TenantContext::sessionKurumFilter());
        self::assertSame([5], TenantContext::filterKurumIds());
    }

    public function test_platform_owner_bolge_filter_preserved_when_kurum_out_of_scope(): void
    {
        $this->actAsPlatformOwner();
        FederationContext::setSessionBolgeFilter(2);
        TenantContext::setSessionKurumFilter(999999);
        self::assertSame(2, FederationContext::sessionBolgeFilter());
        self::assertNull(TenantContext::sessionKurumFilter());
    }

    public function test_default_kurum_for_superadmin_with_kurum_filter(): void
    {
        $this->actAsSuperAdmin(1, 8);
        self::assertSame(8, TenantContext::defaultKurumIdForSuperAdmin());
        self::assertSame(8, TenantContext::assignKurumIdForStore());
    }
}
