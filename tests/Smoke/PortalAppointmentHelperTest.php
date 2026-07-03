<?php
declare(strict_types=1);

use App\Helpers\PatientPortalHelper;
use PHPUnit\Framework\TestCase;

final class PortalAppointmentHelperTest extends TestCase
{
    public function testAppointmentRequestsTableReadyCheck(): void
    {
        $ready = PatientPortalHelper::appointmentRequestsTableReady();
        self::assertIsBool($ready);
    }

    public function testListAdminAppointmentRequestsNoError(): void
    {
        if (!PatientPortalHelper::appointmentRequestsTableReady()) {
            self::markTestSkipped('Portal randevu tablosu kurulu değil.');
        }
        $rows = PatientPortalHelper::listAdminAppointmentRequests(null, 'queued', 5);
        self::assertIsArray($rows);
        self::assertLessThanOrEqual(5, count($rows));
    }

    public function testCountQueuedAppointmentRequests(): void
    {
        if (!PatientPortalHelper::appointmentRequestsTableReady()) {
            self::markTestSkipped('Portal randevu tablosu kurulu değil.');
        }
        $count = PatientPortalHelper::countQueuedAppointmentRequests(null);
        self::assertGreaterThanOrEqual(0, $count);
    }
}
