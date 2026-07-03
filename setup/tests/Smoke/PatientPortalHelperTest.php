<?php
declare(strict_types=1);

use App\Helpers\PatientPortalHelper;
use PHPUnit\Framework\TestCase;

final class PatientPortalHelperTest extends TestCase
{
    public function testResolveLoginRoleHastaPhone(): void
    {
        $patient = (object) [
            'ceptel1' => '0532 111 22 33',
            'ceptel2' => '',
            'bakimveren_tel' => '0544 999 88 77',
        ];
        self::assertSame('hasta', PatientPortalHelper::resolveLoginRole($patient, '05321112233'));
        self::assertSame('bakimveren', PatientPortalHelper::resolveLoginRole($patient, '05449998877'));
        self::assertNull(PatientPortalHelper::resolveLoginRole($patient, '05551234567'));
    }

    public function testNormalizePhone(): void
    {
        self::assertSame('05321234567', PatientPortalHelper::normalizePhone('0532 123 45 67'));
    }

    public function testVisitStatusLabel(): void
    {
        self::assertSame('Yapıldı', PatientPortalHelper::visitStatusLabel(1));
        self::assertSame('Yapılmadı', PatientPortalHelper::visitStatusLabel(0));
    }

    public function testSessionLifecycle(): void
    {
        PatientPortalHelper::clearSession();
        self::assertFalse(PatientPortalHelper::hasValidSession());

        $patient = (object) [
            'id' => 99,
            'tckimlik' => '12345678901',
            'kurum_id' => 1,
        ];
        PatientPortalHelper::startSession($patient, 'hasta');
        self::assertTrue(PatientPortalHelper::hasValidSession());
        $claims = PatientPortalHelper::sessionClaims();
        self::assertIsArray($claims);
        self::assertSame(99, $claims['patient_id']);
        self::assertSame('hasta', $claims['role']);

        PatientPortalHelper::clearSession();
        self::assertFalse(PatientPortalHelper::hasValidSession());
    }

    public function testSafeStatusMessageForPortal(): void
    {
        self::assertNotSame('', PatientPortalHelper::safeStatusMessageForPortal((object) ['pasif' => '1']));
        self::assertSame('', PatientPortalHelper::safeStatusMessageForPortal((object) ['pasif' => '0']));
    }
}
