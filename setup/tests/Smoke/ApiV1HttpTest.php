<?php
declare(strict_types=1);

namespace Tests\Smoke;

use App\Helpers\Api\ApiRouter;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ApiV1HttpTest extends TestCase
{
    /**
     * @return array{resource: string, id: ?string, sub: ?string}|null
     */
    private static function parseRouteViaReflection(): ?array
    {
        $_GET = [];
        $_SERVER['REQUEST_URI'] = '/public/api/v1/patients/a0000001-0001-4001-8001-000000000001';
        $m = new ReflectionMethod(ApiRouter::class, 'parseRoute');
        $m->setAccessible(true);

        return $m->invoke(null);
    }

    public function testParseRoutePatientsWithId(): void
    {
        $parsed = self::parseRouteViaReflection();
        self::assertNotNull($parsed);
        self::assertSame('patients', $parsed['resource']);
        self::assertSame('a0000001-0001-4001-8001-000000000001', $parsed['id']);
    }

    public function testParseRouteVisitsCheckin(): void
    {
        $_GET = [];
        $_SERVER['REQUEST_URI'] = '/public/api/v1/visits/checkin';
        $m = new ReflectionMethod(ApiRouter::class, 'parseRoute');
        $m->setAccessible(true);
        $parsed = $m->invoke(null);
        self::assertNotNull($parsed);
        self::assertSame('visits', $parsed['resource']);
        self::assertSame('checkin', $parsed['sub'] ?? null);
    }

    public function testApiIndexFileExists(): void
    {
        self::assertFileExists(ROOT_PATH . '/public/api/index.php');
    }
}
