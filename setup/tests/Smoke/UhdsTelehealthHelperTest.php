<?php
declare(strict_types=1);

use App\Helpers\UhdsTelehealthHelper;
use PHPUnit\Framework\TestCase;

final class UhdsTelehealthHelperTest extends TestCase
{
    public function testIsValidRoomName(): void
    {
        self::assertTrue(UhdsTelehealthHelper::isValidRoomName('esh-uhds-k1-r9-abc12345'));
        self::assertFalse(UhdsTelehealthHelper::isValidRoomName(''));
        self::assertFalse(UhdsTelehealthHelper::isValidRoomName('bad room!'));
    }

    public function testPatientJoinTokenRoundTrip(): void
    {
        $appointmentId = 'bbbbbbbb-bbbb-4bbb-8bbb-000000000042';
        $token = UhdsTelehealthHelper::createPatientJoinToken($appointmentId, '2026-07-02');
        self::assertNotSame('', $token);
        $claims = UhdsTelehealthHelper::verifyPatientJoinToken($token);
        self::assertIsArray($claims);
        self::assertSame($appointmentId, $claims['id']);
        self::assertSame('patient', $claims['role']);
        self::assertSame('2026-07-02', $claims['d']);
    }

    public function testPatientJoinTokenRejectsTampered(): void
    {
        $token = UhdsTelehealthHelper::createPatientJoinToken(1, '2026-01-01');
        $tampered = substr($token, 0, -2) . 'xx';
        self::assertNull(UhdsTelehealthHelper::verifyPatientJoinToken($tampered));
    }

    public function testJitsiClientConfigShape(): void
    {
        $cfg = UhdsTelehealthHelper::jitsiClientConfig('esh-uhds-k1-r1-test', 'Dr Test', true);
        self::assertArrayHasKey('domain', $cfg);
        self::assertArrayHasKey('roomName', $cfg);
        self::assertSame('esh-uhds-k1-r1-test', $cfg['roomName']);
        self::assertSame('Dr Test', $cfg['displayName']);
    }
}
