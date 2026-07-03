<?php
declare(strict_types=1);

namespace Tests\Smoke;

use App\Helpers\PatientAccessHelper;
use Tests\Support\SessionTestCase;

final class PatientAccessHelperTest extends SessionTestCase
{
    public function test_denies_without_session_user(): void
    {
        $patient = $this->patientRow(1, 1);
        self::assertFalse(PatientAccessHelper::canAccessPatient(1, $patient));
    }

    public function test_staff_same_kurum_active_patient(): void
    {
        $this->actAsStaff(10, 1);
        $patient = $this->patientRow(5, 1, '0');
        self::assertTrue(PatientAccessHelper::canAccessPatient(5, $patient));
    }

    public function test_staff_other_kurum_denied(): void
    {
        $this->actAsStaff(10, 1);
        $patient = $this->patientRow(5, 2, '0');
        self::assertFalse(PatientAccessHelper::canAccessPatient(5, $patient));
    }

    public function test_staff_waiting_patient_allowed(): void
    {
        $this->actAsStaff(10, 1);
        $patient = $this->patientRow(5, 1, '-3');
        self::assertTrue(PatientAccessHelper::canAccessPatient(5, $patient));
    }

    public function test_staff_deceased_patient_denied(): void
    {
        $this->actAsStaff(10, 1);
        $patient = $this->patientRow(5, 1, '-1');
        self::assertFalse(PatientAccessHelper::canAccessPatient(5, $patient));
    }

    public function test_admin_deceased_patient_allowed(): void
    {
        $this->actAsAdmin(7, 1);
        $patient = $this->patientRow(5, 1, '-1');
        self::assertTrue(PatientAccessHelper::canAccessPatient(5, $patient));
    }

    public function test_superadmin_any_kurum(): void
    {
        $this->actAsSuperAdmin();
        $patient = $this->patientRow(5, 99, '0');
        self::assertTrue(PatientAccessHelper::canAccessPatient(5, $patient));
    }

    public function test_superadmin_kurum_filter_restricts(): void
    {
        $this->actAsSuperAdmin(1, 2);
        self::assertTrue(PatientAccessHelper::canAccessPatient(5, $this->patientRow(5, 2)));
        self::assertFalse(PatientAccessHelper::canAccessPatient(6, $this->patientRow(6, 1)));
    }

    public function test_wrong_patient_id_denied(): void
    {
        $this->actAsAdmin();
        $patient = $this->patientRow(5, 1);
        self::assertFalse(PatientAccessHelper::canAccessPatient(99, $patient));
    }
}
