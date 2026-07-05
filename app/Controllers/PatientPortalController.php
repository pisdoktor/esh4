<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuditLogHelper;
use App\Helpers\IdHelper;
use App\Helpers\OperationalSettings;
use App\Helpers\PatientPortalHelper;
use App\Helpers\RateLimitHelper;
use App\Helpers\SimpleCaptchaHelper;
use App\Helpers\ValidationHelper;
use App\Helpers\ZamanDilimiHelper;
use App\Helpers\UhdsTelehealthHelper;
use App\Services\Sms\SmsProviderFactory;
use App\Services\Sms\SmsPhoneNormalizer;
use App\Services\Sms\SmsService;

/**
 * Hasta / bakım veren self-servis portalı (TC + kayıtlı telefon).
 */
class PatientPortalController
{
    private const OTP_SESSION_KEY = 'esh_patient_portal_otp';

    public function __construct()
    {
        if (!PatientPortalHelper::isEnabled()) {
            $this->renderDisabled();
        }
    }

    public function login(): void
    {
        if (PatientPortalHelper::hasValidSession()) {
            header('Location: ' . esh_url('PatientPortal', 'index', [], true));
            exit;
        }

        $eshPortalPageTitle = 'Hasta / bakım veren girişi';
        $eshPortalScript = 'login';
        $eshPortalBilgilendirme = OperationalSettings::patientPortalBilgilendirmeMetni();
        $eshPortalCaptcha = SimpleCaptchaHelper::issue('patient_portal');
        $eshPortalCaptchaError = SimpleCaptchaHelper::consumeFlashError('patient_portal');
        $eshPortalInnerFile = ROOT_PATH . '/views/guest/portal_login.php';
        include ROOT_PATH . '/views/guest/portal_shell.php';
    }

    public function doLogin(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            header('Location: ' . esh_url('PatientPortal', 'login', [], true), true, 303);
            exit;
        }

        if (!SimpleCaptchaHelper::validate($_POST[SimpleCaptchaHelper::INPUT_FIELD] ?? null, 'patient_portal')) {
            SimpleCaptchaHelper::setFlashError(
                'patient_portal',
                'Güvenlik sorusu yanlış veya eksik. Lütfen tekrar deneyin.'
            );
            header('Location: ' . esh_url('PatientPortal', 'login', [], true), true, 303);
            exit;
        }

        $ip = RateLimitHelper::clientIp();
        if (RateLimitHelper::tooManyAttempts(
            'patient_portal',
            $ip,
            ESH_PATIENT_PORTAL_RATE_MAX_ATTEMPTS,
            ESH_PATIENT_PORTAL_RATE_WINDOW_SECONDS
        )) {
            http_response_code(429);
            $this->renderPlainError('Çok fazla deneme', 'Lütfen bir süre bekleyip tekrar deneyin.');
            return;
        }

        RateLimitHelper::hit('patient_portal', $ip, ESH_PATIENT_PORTAL_RATE_WINDOW_SECONDS);

        $tc = ValidationHelper::tcDigitsOnly($_POST['tckimlik'] ?? '');
        $phone = trim((string) ($_POST['telefon'] ?? ''));

        $patient = PatientPortalHelper::findActivePatientForLogin($tc);
        $role = ($patient !== null) ? PatientPortalHelper::resolveLoginRole($patient, $phone) : null;

        if ($patient === null || $role === null) {
            sleep(1);
            SimpleCaptchaHelper::setFlashError(
                'patient_portal',
                PatientPortalHelper::safeStatusMessageForPortal($patient)
            );
            header('Location: ' . esh_url('PatientPortal', 'login', [], true), true, 303);
            exit;
        }
        if (OperationalSettings::patientPortalOtpSmsEnabled()) {
            if (!$this->issueOtpChallenge($patient, $role, $phone)) {
                SimpleCaptchaHelper::setFlashError(
                    'patient_portal',
                    'SMS doğrulama kodu gönderilemedi. Lütfen daha sonra tekrar deneyin.'
                );
                header('Location: ' . esh_url('PatientPortal', 'login', [], true), true, 303);
                exit;
            }
            header('Location: ' . esh_url('PatientPortal', 'otp', [], true), true, 303);
            exit;
        }

        $this->finalizeLogin($patient, $role, $tc);
    }

    public function otp(): void
    {
        $otp = $_SESSION[self::OTP_SESSION_KEY] ?? null;
        if (!is_array($otp) || (int) ($otp['expires'] ?? 0) < time()) {
            unset($_SESSION[self::OTP_SESSION_KEY]);
            header('Location: ' . esh_url('PatientPortal', 'login', [], true), true, 303);
            exit;
        }
        $maskedPhone = (string) ($otp['phone_masked'] ?? '');
        $eshPortalPageTitle = 'SMS doğrulama';
        $eshPortalInnerFile = ROOT_PATH . '/views/guest/portal_otp.php';
        include ROOT_PATH . '/views/guest/portal_shell.php';
    }

    public function verifyOtp(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            header('Location: ' . esh_url('PatientPortal', 'otp', [], true), true, 303);
            exit;
        }
        $otp = $_SESSION[self::OTP_SESSION_KEY] ?? null;
        if (!is_array($otp) || (int) ($otp['expires'] ?? 0) < time()) {
            unset($_SESSION[self::OTP_SESSION_KEY]);
            $_SESSION['portal_error'] = 'SMS doğrulama süresi doldu.';
            header('Location: ' . esh_url('PatientPortal', 'login', [], true), true, 303);
            exit;
        }
        $attempts = (int) ($otp['attempts'] ?? 0);
        if ($attempts >= 5) {
            unset($_SESSION[self::OTP_SESSION_KEY]);
            $_SESSION['portal_error'] = 'Çok fazla yanlış deneme. Lütfen tekrar giriş yapın.';
            header('Location: ' . esh_url('PatientPortal', 'login', [], true), true, 303);
            exit;
        }
        $code = trim((string) ($_POST['otp_code'] ?? ''));
        $hash = (string) ($otp['code_hash'] ?? '');
        if ($code === '' || $hash === '' || !password_verify($code, $hash)) {
            $otp['attempts'] = $attempts + 1;
            $_SESSION[self::OTP_SESSION_KEY] = $otp;
            $_SESSION['portal_error'] = 'Doğrulama kodu hatalı.';
            header('Location: ' . esh_url('PatientPortal', 'otp', [], true), true, 303);
            exit;
        }
        $patient = PatientPortalHelper::findActivePatientForLogin((string) ($otp['tc'] ?? ''));
        $role = (string) ($otp['role'] ?? 'hasta');
        unset($_SESSION[self::OTP_SESSION_KEY]);
        if ($patient === null) {
            $_SESSION['portal_error'] = 'Hasta kaydı doğrulanamadı.';
            header('Location: ' . esh_url('PatientPortal', 'login', [], true), true, 303);
            exit;
        }
        $this->finalizeLogin($patient, $role, (string) ($otp['tc'] ?? ''));
    }

    public function requestAppointmentChange(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            header('Location: ' . esh_url('PatientPortal', 'index', [], true), true, 303);
            exit;
        }
        $patient = PatientPortalHelper::loadSessionPatient();
        if ($patient === null) {
            header('Location: ' . esh_url('PatientPortal', 'login', [], true), true, 303);
            exit;
        }
        if (!PatientPortalHelper::isAppointmentRequestAllowed($patient)) {
            $_SESSION['portal_error'] = 'Bu dosya için randevu değişiklik talebi alınamıyor.';
            header('Location: ' . esh_url('PatientPortal', 'index', [], true), true, 303);
            exit;
        }
        $uhdsId = IdHelper::normalizeRequestId($_POST['uhds_id'] ?? null);
        $talepTarih = trim((string) ($_POST['talep_tarih'] ?? ''));
        $talepZamanRaw = trim((string) ($_POST['talep_zaman'] ?? ''));
        $neden = trim((string) ($_POST['neden'] ?? ''));
        $uhds = $uhdsId !== null
            ? $this->loadOwnedUhdsAppointment((string) $patient->tckimlik, (int) $patient->kurum_id, $uhdsId)
            : null;
        if ($uhds === null) {
            $_SESSION['portal_error'] = 'Randevu kaydı bulunamadı.';
            header('Location: ' . esh_url('PatientPortal', 'index', [], true), 303);
            exit;
        }
        $talepZaman = null;
        if ($talepZamanRaw !== '' && in_array($talepZamanRaw, ['0', '1', '2'], true)) {
            $talepZaman = (int) $talepZamanRaw;
        }
        $ok = PatientPortalHelper::createAppointmentRequest(
            (string) ($patient->id ?? ''),
            (int) $patient->kurum_id,
            (string) ($uhds->id ?? ''),
            (string) ($uhds->randevu_tarihi ?? ''),
            $talepTarih,
            $talepZaman,
            $neden
        );
        if ($ok) {
            $_SESSION['portal_success'] = 'Randevu değişiklik talebiniz alındı.';
        } else {
            $_SESSION['portal_error'] = 'Talep kaydedilemedi. Tarih ve açıklama alanlarını kontrol edin.';
        }
        header('Location: ' . esh_url('PatientPortal', 'index', [], true), true, 303);
        exit;
    }

    private function issueOtpChallenge(object $patient, string $role, string $inputPhone): bool
    {
        if (!SmsService::isSendConfigured()) {
            return false;
        }
        $phone = SmsPhoneNormalizer::normalize($inputPhone);
        if ($phone === null) {
            return false;
        }
        $code = (string) random_int(100000, 999999);
        $provider = SmsProviderFactory::create();
        $result = $provider->send($phone, 'SONEV portal giriş kodunuz: ' . $code . ' (5 dk)', ['mesaj_turu' => 'otp']);
        if (!$result->success) {
            return false;
        }
        $_SESSION[self::OTP_SESSION_KEY] = [
            'patient_id' => IdHelper::normalizeRequestId($patient->id ?? null),
            'tc' => (string) ($patient->tckimlik ?? ''),
            'role' => $role === 'bakimveren' ? 'bakimveren' : 'hasta',
            'expires' => time() + 300,
            'attempts' => 0,
            'code_hash' => password_hash($code, PASSWORD_DEFAULT),
            'phone_masked' => $this->maskPhone($phone),
        ];

        return true;
    }

    private function finalizeLogin(object $patient, string $role, string $tc): void
    {
        PatientPortalHelper::startSession($patient, $role);

        AuditLogHelper::log('patient_portal.login', 'patient', IdHelper::normalizeRequestId($patient->id ?? null), $tc, [
            'role' => $role,
        ]);

        header('Location: ' . esh_url('PatientPortal', 'index', [], true), true, 303);
        exit;
    }

    public function logout(): void
    {
        PatientPortalHelper::clearSession();
        header('Location: ' . esh_url('PatientPortal', 'login', [], true), true, 303);
        exit;
    }

    public function index(): void
    {
        $patient = PatientPortalHelper::loadSessionPatient();
        if ($patient === null) {
            header('Location: ' . esh_url('PatientPortal', 'login', [], true));
            exit;
        }

        $claims = PatientPortalHelper::sessionClaims();
        $tc = (string) ($claims['tc'] ?? '');
        $kurumId = (int) ($claims['kurum_id'] ?? 1);
        $role = (string) ($claims['role'] ?? 'hasta');

        $planned = PatientPortalHelper::upcomingPlannedVisits($tc, $kurumId);
        $visits = PatientPortalHelper::recentVisitSummary($tc, $kurumId);
        $uhds = PatientPortalHelper::upcomingUhdsAppointments($tc, $kurumId);
        $appointmentRequests = PatientPortalHelper::listAppointmentRequests((string) ($patient->id ?? ''), 12);
        $uhdsJoinUrls = [];
        if (UhdsTelehealthHelper::isEnabled() && UhdsTelehealthHelper::provider() === 'jitsi') {
            foreach ($uhds as $row) {
                $uhdsId = IdHelper::normalizeRequestId($row->id ?? null);
                $date = (string) ($row->randevu_tarihi ?? '');
                if ($uhdsId !== null && $date !== '') {
                    $uhdsJoinUrls[$uhdsId] = UhdsTelehealthHelper::patientJoinUrl($uhdsId, $date);
                }
            }
        }

        $eshPortalPageTitle = 'Hasta portalı';
        $eshPortalScript = 'index';
        $eshPortalPatient = $patient;
        $eshPortalRole = $role;
        $eshPortalRoleLabel = PatientPortalHelper::roleLabel($role);
        $eshPortalPlanned = $planned;
        $eshPortalVisits = $visits;
        $eshPortalUhds = $uhds;
        $eshPortalUhdsJoinUrls = $uhdsJoinUrls;
        $eshPortalAppointmentRequests = $appointmentRequests;
        $eshPortalSmsOnay = (int) ($patient->sms_bilgilendirme_onay ?? 1) === 1;
        $eshPortalTelehealthEnabled = UhdsTelehealthHelper::isEnabled();
        $eshPortalInnerFile = ROOT_PATH . '/views/guest/portal_dashboard.php';
        include ROOT_PATH . '/views/guest/portal_shell.php';
    }

    public function updateSmsConsent(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            header('Location: ' . esh_url('PatientPortal', 'index', [], true));
            exit;
        }

        $patient = PatientPortalHelper::loadSessionPatient();
        if ($patient === null) {
            header('Location: ' . esh_url('PatientPortal', 'login', [], true));
            exit;
        }

        $onay = isset($_POST['sms_bilgilendirme_onay']) && (string) $_POST['sms_bilgilendirme_onay'] === '1';
        $ok = PatientPortalHelper::updateSmsConsent((string) ($patient->id ?? ''), $onay);

        if ($ok) {
            AuditLogHelper::log('patient_portal.sms_consent', 'patient', IdHelper::normalizeRequestId($patient->id ?? null), (string) $patient->tckimlik, [
                'onay' => $onay ? 1 : 0,
            ]);
            $_SESSION['portal_success'] = $onay
                ? 'SMS bilgilendirme onayınız kaydedildi.'
                : 'SMS bilgilendirme onayınız kaldırıldı.';
        } else {
            $_SESSION['portal_error'] = 'Onay durumu kaydedilemedi.';
        }

        header('Location: ' . esh_url('PatientPortal', 'index', [], true), true, 303);
        exit;
    }

    private function renderDisabled(): void
    {
        http_response_code(404);
        $this->renderPlainError(
            'Hizmet kullanılamıyor',
            'Hasta / bakım veren portalı yönetici tarafından kapatılmıştır.'
        );
        exit;
    }

    private function renderPlainError(string $title, string $message): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>'
            . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
            . '</title></head><body style="font-family:sans-serif;padding:2rem;max-width:32rem;margin:auto;">';
        echo '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
        echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '<p><a href="' . htmlspecialchars(esh_url('Auth', 'login', [], true), ENT_QUOTES, 'UTF-8') . '">Personel girişi</a></p>';
        echo '</body></html>';
        exit;
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);
        if (!is_string($digits) || strlen($digits) < 4) {
            return '***';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
    }

    private function loadOwnedUhdsAppointment(string $tc, int $kurumId, int|string $uhdsId): ?object
    {
        $rid = IdHelper::normalizeRequestId($uhdsId);
        if ($rid === null || !preg_match('/^\d{11}$/', $tc)) {
            return null;
        }
        $db = \App\Core\Database::getInstance();
        $row = $db->fetchObjectPrepared(
            'SELECT id, randevu_tarihi, zaman
             FROM #__goruntulu_randevu
             WHERE id = ? AND hastatckimlik = ? AND kurum_id = ?
             LIMIT 1',
            [$rid, $tc, $kurumId]
        );

        return $row ?: null;
    }
}
