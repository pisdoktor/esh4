<?php
declare(strict_types=1);

use App\Services\Api\ApiTokenService;
use PHPUnit\Framework\TestCase;

final class ApiTokenWriteScopeTest extends TestCase
{
    public function testNormalizeWriteScopes(): void
    {
        self::assertSame('write', ApiTokenService::normalizeScopes('write'));
        self::assertSame('visits:write', ApiTokenService::normalizeScopes('visits:write'));
        self::assertStringContainsString('visits', ApiTokenService::normalizeScopes('patients,visits,plans'));
    }

    public function testAllowsWriteWithVisitsWriteScope(): void
    {
        $token = (object) ['scopes' => 'visits:write'];
        self::assertTrue(ApiTokenService::allowsWrite($token, 'visits'));
        self::assertFalse(ApiTokenService::allowsWrite($token, 'patients'));
    }
}
