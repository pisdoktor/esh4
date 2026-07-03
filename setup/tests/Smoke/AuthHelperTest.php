<?php
declare(strict_types=1);

namespace Tests\Smoke;

use App\Helpers\AuthHelper;
use Tests\Support\SessionTestCase;

final class AuthHelperTest extends SessionTestCase
{
    public function test_clamp_level_bounds(): void
    {
        self::assertSame(0, AuthHelper::clampLevel(-1));
        self::assertSame(3, AuthHelper::clampLevel(99));
        self::assertSame(2, AuthHelper::clampLevel(2));
    }

    public function test_platform_owner_session_helpers(): void
    {
        $this->actAsPlatformOwner();
        self::assertTrue(AuthHelper::sessionIsPlatformOwner());
        self::assertTrue(AuthHelper::sessionIsSuperAdmin());
        self::assertFalse(AuthHelper::sessionIsSuperAdminOnly());
    }

    public function test_superadmin_only_session_helper(): void
    {
        $this->actAsSuperAdmin();
        self::assertFalse(AuthHelper::sessionIsPlatformOwner());
        self::assertTrue(AuthHelper::sessionIsSuperAdmin());
        self::assertTrue(AuthHelper::sessionIsSuperAdminOnly());
    }

    public function test_assignable_levels_platform_owner(): void
    {
        $this->actAsPlatformOwner();
        self::assertSame(
            [0, 1, 2, 3],
            AuthHelper::assignableAdminLevels()
        );
    }

    public function test_assignable_levels_superadmin_capped(): void
    {
        $this->actAsSuperAdmin();
        self::assertSame(
            [AuthHelper::ROLE_STAFF, AuthHelper::ROLE_ADMIN],
            AuthHelper::assignableAdminLevels()
        );
    }

    public function test_can_assign_superadmin_only_platform_owner(): void
    {
        $this->actAsSuperAdmin();
        self::assertFalse(AuthHelper::canAssignSuperAdminRole());
        self::assertFalse(AuthHelper::canAssignPlatformOwnerRole());

        $this->actAsPlatformOwner();
        self::assertTrue(AuthHelper::canAssignSuperAdminRole());
        self::assertTrue(AuthHelper::canAssignPlatformOwnerRole());
    }

    public function test_admin_level_labels(): void
    {
        self::assertSame('Sistem Sahibi', AuthHelper::adminLevelLabel(3));
        self::assertSame('Süper Yönetici', AuthHelper::adminLevelLabel(2));
    }

    public function test_is_platform_level(): void
    {
        self::assertTrue(AuthHelper::isPlatformLevel(2));
        self::assertTrue(AuthHelper::isPlatformLevel(3));
        self::assertFalse(AuthHelper::isPlatformLevel(1));
    }
}
