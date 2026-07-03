<?php
declare(strict_types=1);

use App\Helpers\ComposerBootstrapHelper;
use App\Helpers\ModernFrontendHelper;
use PHPUnit\Framework\TestCase;

final class ModernFrontendHelperTest extends TestCase
{
    public function testAssetUrl(): void
    {
        if (!defined('ASSETS_URL')) {
            define('ASSETS_URL', '/public/assets');
        }
        self::assertStringContainsString('/modern/dashboard-pilot.mjs', ModernFrontendHelper::assetUrl('dashboard-pilot.mjs'));
    }

    public function testShouldLoadForDashboardRoute(): void
    {
        self::assertIsBool(ModernFrontendHelper::shouldLoadForRoute('dashboard', 'index'));
        self::assertFalse(ModernFrontendHelper::shouldLoadForRoute('patient', 'index'));
    }

    public function testDefaultVueCdn(): void
    {
        self::assertStringContainsString('vue', ModernFrontendHelper::DEFAULT_VUE_CDN);
    }

    public function testUseBuiltBundleReturnsBoolForScopes(): void
    {
        self::assertIsBool(ModernFrontendHelper::useBuiltBundle('dashboard'));
        self::assertIsBool(ModernFrontendHelper::useBuiltBundle('planning'));
    }
}
