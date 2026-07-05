<?php
declare(strict_types=1);

namespace Tests\Smoke;

use App\Helpers\FederationContext;
use App\Helpers\SettingsWriteScope;
use Tests\Support\SessionTestCase;

final class SettingsWriteScopeTest extends SessionTestCase
{
    public function test_platform_owner_without_filter_targets_platform(): void
    {
        $this->actAsPlatformOwner();
        $target = SettingsWriteScope::resolveSaveTarget();
        self::assertIsArray($target);
        self::assertSame(SettingsWriteScope::TARGET_PLATFORM, $target['target']);
        self::assertTrue(SettingsWriteScope::canWritePlatformDefaults());
        self::assertTrue(SettingsWriteScope::canSaveTab('modules'));
    }

    public function test_superadmin_without_scope_cannot_save_kurum_tab(): void
    {
        $this->actAsSuperAdmin();
        self::assertIsString(SettingsWriteScope::resolveSaveTarget());
        self::assertFalse(SettingsWriteScope::canWritePlatformDefaults());
        self::assertFalse(SettingsWriteScope::canSaveTab('modules'));
        self::assertFalse(SettingsWriteScope::canSaveTab('bakim'));
    }

    public function test_superadmin_with_kurum_filter_targets_kurum(): void
    {
        $this->actAsSuperAdmin(1, 3);
        $target = SettingsWriteScope::resolveSaveTarget();
        self::assertIsArray($target);
        self::assertSame(SettingsWriteScope::TARGET_KURUM, $target['target']);
        self::assertSame(3, $target['kurum_id']);
        self::assertTrue(SettingsWriteScope::canSaveTab('harita'));
    }

    public function test_bolge_locked_superadmin_without_kurum_targets_bolge(): void
    {
        $this->actAsSuperAdmin(1, null, 7);
        $target = SettingsWriteScope::resolveSaveTarget();
        self::assertIsArray($target);
        self::assertSame(SettingsWriteScope::TARGET_BOLGE, $target['target']);
        self::assertSame(7, $target['bolge_id']);
        self::assertTrue(SettingsWriteScope::canSaveTab('nobet'));
    }

    public function test_platform_owner_bolge_filter_targets_bolge_without_kurum(): void
    {
        $this->actAsPlatformOwner();
        FederationContext::setSessionBolgeFilter(4);
        $target = SettingsWriteScope::resolveSaveTarget();
        self::assertIsArray($target);
        self::assertSame(SettingsWriteScope::TARGET_BOLGE, $target['target']);
        self::assertSame(4, $target['bolge_id']);
    }

    public function test_platform_owner_can_save_bakim_tab(): void
    {
        $this->actAsPlatformOwner();
        self::assertTrue(SettingsWriteScope::canSaveTab('bakim'));
        self::assertFalse(SettingsWriteScope::canSaveTab('overview'));
        self::assertTrue(SettingsWriteScope::canSaveTab('eimza'));
    }
}
