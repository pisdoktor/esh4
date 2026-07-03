<?php
declare(strict_types=1);

namespace Tests\Smoke;

use App\Services\Api\ApiTokenService;
use PHPUnit\Framework\TestCase;

final class ApiTokenServiceTest extends TestCase
{
    public function test_normalize_scopes_read_default(): void
    {
        self::assertSame('read', ApiTokenService::normalizeScopes(''));
        self::assertSame('read', ApiTokenService::normalizeScopes('*'));
    }

    public function test_normalize_scopes_specific(): void
    {
        self::assertSame('patients,visits', ApiTokenService::normalizeScopes('patients, visits, invalid'));
    }

    public function test_hash_token_deterministic(): void
    {
        $a = ApiTokenService::hashToken('esh_live_testtoken123456789012345678901234');
        $b = ApiTokenService::hashToken('esh_live_testtoken123456789012345678901234');
        self::assertSame($a, $b);
        self::assertSame(64, strlen($a));
    }

    public function test_allows_scope_read(): void
    {
        $row = (object) ['scopes' => 'read'];
        self::assertTrue(ApiTokenService::allowsScope($row, 'patients'));
        self::assertTrue(ApiTokenService::allowsScope($row, 'visits'));
    }

    public function test_allows_scope_limited(): void
    {
        $row = (object) ['scopes' => 'patients'];
        self::assertTrue(ApiTokenService::allowsScope($row, 'patients'));
        self::assertFalse(ApiTokenService::allowsScope($row, 'visits'));
    }

    public function test_allows_write_scope(): void
    {
        $readOnly = (object) ['scopes' => 'visits'];
        self::assertFalse(ApiTokenService::allowsWrite($readOnly, 'visits'));
        $writer = (object) ['scopes' => 'visits,write'];
        self::assertTrue(ApiTokenService::allowsWrite($writer, 'visits'));
    }
}
