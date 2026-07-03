<?php
declare(strict_types=1);

namespace Tests\Smoke;

use App\Helpers\CsrfHelper;
use App\Helpers\PostAllowlistHelper;
use PHPUnit\Framework\TestCase;

final class SecurityHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function test_post_allowlist_strips_unknown_keys(): void
    {
        $source = ['isim' => 'Ali', 'isadmin' => 2, 'id' => 99];
        $picked = PostAllowlistHelper::pick($source, ['isim']);
        self::assertSame(['isim' => 'Ali'], $picked);
        self::assertArrayNotHasKey('isadmin', $picked);
    }

    public function test_csrf_validate_matching_token(): void
    {
        $token = CsrfHelper::regenerate();
        self::assertTrue(CsrfHelper::validate($token));
    }

    public function test_csrf_validate_rejects_wrong_token(): void
    {
        CsrfHelper::regenerate();
        self::assertFalse(CsrfHelper::validate('invalid-token'));
    }

    public function test_csrf_exempt_route(): void
    {
        $exempt = ['Auth' => ['login', 'doLogin']];
        self::assertTrue(CsrfHelper::isExemptRoute('Auth', 'login', $exempt));
        self::assertFalse(CsrfHelper::isExemptRoute('Patient', 'store', $exempt));
    }
}
