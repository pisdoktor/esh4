<?php
declare(strict_types=1);

use App\Helpers\ComposerBootstrapHelper;
use PHPUnit\Framework\TestCase;

final class ComposerBootstrapHelperTest extends TestCase
{
    public function testLoadIfPresentWithoutVendor(): void
    {
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', dirname(__DIR__, 2));
        }
        self::assertFalse(ComposerBootstrapHelper::loadIfPresent());
        self::assertFalse(ComposerBootstrapHelper::isLoaded());
    }
}
