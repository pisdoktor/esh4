<?php
declare(strict_types=1);

namespace Tests\Smoke;

use App\Helpers\PatientAccessHelper;
use Tests\Support\SessionTestCase;

final class PatientAccessHelperTest extends SessionTestCase
{
    public function test_denies_without_session_user(): void
    {
        $patient = $this->patientRow('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 1);
        self::assertFalse(PatientAccessHelper::canAccessPatient('eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', $patient));
    }

    public function test_staff_same_kurum_active_patient(): void
    {
        $this->actAsStaff('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 1);
        $patient = $this->patientRow('55555555-5555-4555-8555-555555555555', 1, '0');
        self::assertTrue(PatientAccessHelper::canAccessPatient('55555555-5555-4555-8555-555555555555', $patient));
    }

    public function test_staff_other_kurum_denied(): void
    {
        $this->actAsStaff('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 1);
        $patient = $this->patientRow('55555555-5555-4555-8555-555555555555', 2, '0');
        self::assertFalse(PatientAccessHelper::canAccessPatient('55555555-5555-4555-8555-555555555555', $patient));
    }

    public function test_staff_waiting_patient_allowed(): void
    {
        $this->actAsStaff('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 1);
        $patient = $this->patientRow('55555555-5555-4555-8555-555555555555', 1, '-3');
        self::assertTrue(PatientAccessHelper::canAccessPatient('55555555-5555-4555-8555-555555555555', $patient));
    }

    public function test_staff_deceased_patient_denied(): void
    {
        $this->actAsStaff('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 1);
        $patient = $this->patientRow('55555555-5555-4555-8555-555555555555', 1, '-1');
        self::assertFalse(PatientAccessHelper::canAccessPatient('55555555-5555-4555-8555-555555555555', $patient));
    }

    public function test_admin_deceased_patient_allowed(): void
    {
        $this->actAsAdmin('bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 1);
        $patient = $this->patientRow('55555555-5555-4555-8555-555555555555', 1, '-1');
        self::assertTrue(PatientAccessHelper::canAccessPatient('55555555-5555-4555-8555-555555555555', $patient));
    }

    public function test_superadmin_any_kurum(): void
    {
        $this->actAsSuperAdmin();
        $patient = $this->patientRow('55555555-5555-4555-8555-555555555555', 99, '0');
        self::assertTrue(PatientAccessHelper::canAccessPatient('55555555-5555-4555-8555-555555555555', $patient));
    }

    public function test_superadmin_kurum_filter_restricts(): void
    {
        $this->actAsSuperAdmin('cccccccc-cccc-4ccc-8ccc-cccccccccccc', 2);
        self::assertTrue(PatientAccessHelper::canAccessPatient('55555555-5555-4555-8555-555555555555', $this->patientRow('55555555-5555-4555-8555-555555555555', 2)));
        self::assertFalse(PatientAccessHelper::canAccessPatient('66666666-6666-4666-8666-666666666666', $this->patientRow('66666666-6666-4666-8666-666666666666', 1)));
    }

    public function test_wrong_patient_id_denied(): void
    {
        $this->actAsAdmin();
        $patient = $this->patientRow('55555555-5555-4555-8555-555555555555', 1);
        self::assertFalse(PatientAccessHelper::canAccessPatient('99999999-9999-4999-8999-999999999999', $patient));
    }
}
