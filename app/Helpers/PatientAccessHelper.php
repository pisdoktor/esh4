<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Patient;

/**
 * Hasta kaydı erişimi: kurum_id + rol (personel / admin / süper yönetici) + pasif durumu.
 */
final class PatientAccessHelper
{
    /** Personel (user) birleşik listede görebildiği pasif kodları. */
    private const STAFF_VISIBLE_PASIF = ['0', '1', '-3'];

    public static function canAccessPatient(int $patientId, ?object $patient = null): bool
    {
        if ((int) ($_SESSION['user_id'] ?? 0) <= 0) {
            return false;
        }

        if ($patient === null) {
            $patient = (new Patient())->getById($patientId);
        }
        if (!$patient || (int) ($patient->id ?? 0) !== $patientId) {
            return false;
        }

        $patientKurum = isset($patient->kurum_id) ? (int) $patient->kurum_id : null;
        if (!self::canAccessPatientKurum($patientKurum)) {
            return false;
        }

        return self::canAccessByPasifStatus($patient->pasif ?? '0');
    }

    public static function requirePatientAccess(int $patientId, ?object $patient = null, string $redirectUrl = ''): object
    {
        if ($patient === null) {
            $patient = (new Patient())->getById($patientId);
        }
        if (!$patient || (int) ($patient->id ?? 0) !== $patientId) {
            $_SESSION['error'] = 'Hasta bulunamadı.';
            header('Location: ' . ($redirectUrl !== '' ? $redirectUrl : esh_url('Patient', 'unified', ['status' => 'active'])));
            exit;
        }

        if (!self::canAccessPatient($patientId, $patient)) {
            $_SESSION['error'] = 'Bu hasta kaydına erişim yetkiniz bulunmamaktadır.';
            header('Location: ' . ($redirectUrl !== '' ? $redirectUrl : esh_url('Patient', 'unified', ['status' => 'active'])));
            exit;
        }

        return $patient;
    }

    /**
     * Hasta kurum_id — oturum kurumu / süper yönetici kurum filtresi ile uyumlu mu?
     */
    private static function canAccessPatientKurum(?int $patientKurumId): bool
    {
        if ($patientKurumId === null || $patientKurumId <= 0) {
            return AuthHelper::sessionIsSuperAdmin();
        }

        if (AuthHelper::sessionIsSuperAdmin()) {
            $filter = TenantContext::sessionKurumFilter();
            if ($filter !== null) {
                return (int) $patientKurumId === $filter;
            }

            return true;
        }

        $sessionKid = TenantContext::sessionKurumId();
        if ($sessionKid === null) {
            return false;
        }

        return (int) $patientKurumId === $sessionKid;
    }

    /**
     * Admin ve süper yönetici tüm pasif durumları görür; personel yalnızca aktif / pasif dosya / bekleyen.
     */
    private static function canAccessByPasifStatus(mixed $pasif): bool
    {
        if (AuthHelper::sessionIsAdmin()) {
            return true;
        }

        return in_array((string) ($pasif ?? '0'), self::STAFF_VISIBLE_PASIF, true);
    }
}
