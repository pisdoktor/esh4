<?php

declare(strict_types=1);

namespace Tests\Smoke;

use App\Helpers\IdHelper;
use PHPUnit\Framework\TestCase;

final class IdHelperTest extends TestCase
{
    public function test_generate_uuid_v4_format(): void
    {
        $uuid = IdHelper::generateUuidV4();
        self::assertTrue(IdHelper::isValidUuid($uuid));
        self::assertSame(36, strlen($uuid));
    }

    public function test_is_valid_uuid_rejects_int_legacy(): void
    {
        self::assertFalse(IdHelper::isValidUuid('123'));
        self::assertFalse(IdHelper::isValidUuid(123));
    }

    public function test_is_valid_entity_id_accepts_uuid_and_legacy_int(): void
    {
        $uuid = IdHelper::generateUuidV4();
        self::assertTrue(IdHelper::isValidEntityId($uuid));
        self::assertTrue(IdHelper::isValidEntityId('42'));
        self::assertTrue(IdHelper::isValidEntityId(42));
        self::assertFalse(IdHelper::isValidEntityId('0'));
        self::assertFalse(IdHelper::isValidEntityId(''));
    }

    public function test_normalize_request_id(): void
    {
        $uuid = IdHelper::generateUuidV4();
        self::assertSame(strtolower($uuid), IdHelper::normalizeRequestId($uuid));
        self::assertSame('99', IdHelper::normalizeRequestId('99'));
        self::assertNull(IdHelper::normalizeRequestId(''));
        self::assertNull(IdHelper::normalizeRequestId('not-an-id'));
    }
}
