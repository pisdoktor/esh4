<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AppSettings;
use App\Helpers\AuditLogHelper;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;
use App\Helpers\PatientPortalHelper;
use App\Helpers\TenantContext;
use App\Helpers\ThemeViewHelper;

/**
 * Hasta portalı randevu talebi kuyruğu (yönetici).
 */
class PortalAppointmentController
{
    public function __construct()
    {
        AuthHelper::requireAdmin();
        if (!AppSettings::isModuleEnabled('patient_portal')) {
            $_SESSION['error'] = 'Hasta portalı modülü kapalı.';
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }
    }

    public function index(): void
    {
        $kurumId = TenantContext::filterKurumId();
        $durum = trim((string) ($_GET['durum'] ?? 'queued'));
        if ($durum === '') {
            $durum = 'queued';
        }
        $tableReady = PatientPortalHelper::appointmentRequestsTableReady();
        $rows = $tableReady
            ? PatientPortalHelper::listAdminAppointmentRequests($kurumId, $durum, 100)
            : [];
        $queuedCount = $tableReady ? PatientPortalHelper::countQueuedAppointmentRequests($kurumId) : 0;
        $pageTitle = 'Portal randevu talepleri';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'portal_appointment/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function updateStatus(): void
    {
        CsrfHelper::requirePostMethod(esh_url('PortalAppointment', 'index'));
        if (!PatientPortalHelper::appointmentRequestsTableReady()) {
            $_SESSION['error'] = 'Portal randevu tablosu kurulu değil.';
            header('Location: ' . esh_url('PortalAppointment', 'index'));
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $durum = trim((string) ($_POST['durum'] ?? ''));
        $notu = trim((string) ($_POST['durum_notu'] ?? ''));

        if ($id <= 0 || $durum === '') {
            $_SESSION['error'] = 'Geçersiz talep.';
            header('Location: ' . esh_url('PortalAppointment', 'index'));
            exit;
        }

        if (PatientPortalHelper::updateAppointmentRequestStatus($id, $durum, $notu !== '' ? $notu : null)) {
            AuditLogHelper::log('patient_portal.appointment_status', 'patient_portal', $id, null, [
                'durum' => $durum,
            ]);
            $_SESSION['success'] = 'Talep durumu güncellendi.';
        } else {
            $_SESSION['error'] = 'Talep güncellenemedi.';
        }

        header('Location: ' . esh_url('PortalAppointment', 'index'));
        exit;
    }
}
