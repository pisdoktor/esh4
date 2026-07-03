<?php
namespace App\Controllers;

use App\Helpers\AppSettings;
use App\Helpers\AuditLogHelper;
use App\Helpers\AuthHelper;
use App\Helpers\CsrfHelper;
use App\Helpers\MaintenanceHelper;
use App\Helpers\RateLimitHelper;
use App\Helpers\TenantContext;
use App\Helpers\ThemeViewHelper;
use App\Helpers\ValidationHelper;
use App\Models\EimzaAuth;
use App\Models\User;
use App\Services\PermissionService;

class AuthController {
    private const EIMZA_CHALLENGE_SESSION_KEY = 'eimza_login_challenge';

    /**
     * Giriş Sayfasını Gösterir
     */
    public function login() {
        // Eğer zaten giriş yapılmışsa direkt dashboard'a gönder
        if (isset($_SESSION['user_id'])) {
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }
        include '../views/login.php';
    }

    /**
     * Giriş İşlemini Doğrular (doLogin)
     */
    public function doLogin() {
        $ip = RateLimitHelper::clientIp();
        if (RateLimitHelper::tooManyAttempts('login_ip', $ip, ESH_LOGIN_RATE_MAX_ATTEMPTS, ESH_LOGIN_RATE_WINDOW_SECONDS)) {
            $wait = RateLimitHelper::retryAfterSeconds('login_ip', $ip, ESH_LOGIN_RATE_WINDOW_SECONDS);
            $_SESSION['error'] = 'Çok fazla başarısız giriş denemesi. Lütfen ' . max(1, $wait) . ' saniye sonra tekrar deneyin.';
            header('Location: ' . esh_url('Auth', 'login'));
            exit;
        }

        if (!CsrfHelper::validate()) {
            $_SESSION['error'] = 'Güvenlik doğrulaması başarısız. Lütfen sayfayı yenileyip tekrar deneyin.';
            header('Location: ' . esh_url('Auth', 'login'));
            exit;
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $userModel = new User();
        
        // 1. Kullanıcıyı bul
        if ($userModel->loadByUsername($username)) {
            // 2. Şifre kontrolü (legacy hash destekli; başarılıysa otomatik upgrade)
            if ($userModel->verifyPasswordAndUpgrade($password)) {
                // 3. Giriş başarılı, session başla
                if ($userModel->activated) {
                $adminLevel = AuthHelper::clampLevel((int) $userModel->isadmin);
                $maintenanceReject = MaintenanceHelper::rejectLoginIfBlocked($adminLevel);
                if ($maintenanceReject !== null) {
                    $_SESSION['error'] = $maintenanceReject;
                    header('Location: ' . esh_url('Auth', 'login'));
                    exit;
                }
                session_regenerate_id(true);
                CsrfHelper::regenerate();
                RateLimitHelper::clear('login_ip', $ip);
                $_SESSION['user_id'] = $userModel->id;
                $_SESSION['name'] = $userModel->name;
                $_SESSION['username'] = $userModel->username;
                AuthHelper::syncSessionFromLevel(AuthHelper::clampLevel((int) $userModel->isadmin));
                PermissionService::syncSessionPermissions((int) $userModel->id, $adminLevel);
                TenantContext::syncSessionFromUser(
                    isset($userModel->kurum_id) ? (int) $userModel->kurum_id : null,
                    $adminLevel,
                    isset($userModel->bolge_id) ? (int) $userModel->bolge_id : null
                );
                $_SESSION['avatar'] = $userModel->profileImageWebUrl();

                ThemeViewHelper::syncSessionUserThemeFromDb(isset($userModel->ui_theme) ? (string) $userModel->ui_theme : null);

                AuditLogHelper::authLogin(
                    (int) $userModel->id,
                    isset($userModel->kurum_id) ? (int) $userModel->kurum_id : null
                );

                //$_SESSION['success'] = 'Giriş yapıldı';

                $userModel->updateVisitDate($userModel->id); // Giriş tarihini güncelle
                
                
                
                header('Location: ' . esh_url('Dashboard', 'index'));
                exit;
                
                } else {
                    $_SESSION['error'] = 'Hesabınız henüz aktive edilmemiş';
                    header('Location: ' . esh_url('Auth', 'login'));
                    exit;
                }
            }
        }
        
        RateLimitHelper::hit('login_ip', $ip, ESH_LOGIN_RATE_WINDOW_SECONDS);
        $_SESSION['error'] = "Kullanıcı adı veya şifre hatalı!";
        header('Location: ' . esh_url('Auth', 'login'));
    }

    /**
     * Elektronik imza girisi icin tek kullanimlik challenge uretir.
     */
    public function eimzaChallenge() {
        header('Content-Type: application/json; charset=utf-8');
        $this->assertEimzaEnabled();
        $this->assertOpenSslReady();
        try {
            $nonce = bin2hex(random_bytes(24));
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Challenge uretilemedi.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $expiresAt = time() + ESH_EIMZA_CHALLENGE_TTL_SECONDS;
        $challenge = (new EimzaAuth())->createChallenge($nonce, $expiresAt);
        if (!is_array($challenge) || empty($challenge['id'])) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Challenge kaydi olusturulamadi.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $_SESSION[self::EIMZA_CHALLENGE_SESSION_KEY] = [
            'id' => (int) $challenge['id'],
            'nonce' => $nonce,
            'expires_at' => (int) $expiresAt,
        ];

        echo json_encode([
            'ok' => true,
            'challenge_id' => (int) $challenge['id'],
            'challenge' => $nonce,
            'expires_in' => ESH_EIMZA_CHALLENGE_TTL_SECONDS,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Elektronik imza ile giris.
     */
    public function eimzaLogin() {
        header('Content-Type: application/json; charset=utf-8');
        $this->assertEimzaEnabled();
        $this->assertOpenSslReady();
        $eimzaAuth = new EimzaAuth();

        $payload = $_SESSION[self::EIMZA_CHALLENGE_SESSION_KEY] ?? null;
        if (!is_array($payload) || empty($payload['nonce']) || (int) ($payload['expires_at'] ?? 0) < time()) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Challenge suresi doldu. Tekrar olusturun.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $tc = preg_replace('/\D+/', '', (string) ($_POST['tc_kimlikno'] ?? ''));
        $certificatePem = trim((string) ($_POST['certificate_pem'] ?? ''));
        $signatureB64 = trim((string) ($_POST['signature_b64'] ?? ''));
        $challengeId = (int) ($_POST['challenge_id'] ?? 0);
        if ($certificatePem === '' || $signatureB64 === '' || $challengeId < 1) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'TC, challenge, sertifika ve imza alanlari zorunludur.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (strpos($certificatePem, 'BEGIN CERTIFICATE') === false) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Sertifika PEM formati gecersiz.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $certInfo = @openssl_x509_parse($certificatePem);
        if (!is_array($certInfo)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Sertifika okunamadi.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!$this->isCertificateValidNow($certInfo)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sertifikanin gecerlilik suresi uygun degil.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!$this->isCertificateChainTrusted($certificatePem)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sertifika zinciri guvenilir bulunmadi.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!$this->isRevocationStatusAcceptable($certInfo)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sertifika iptal kontrolu (OCSP/CRL) basarisiz.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $subjectTc = $this->extractTcFromCertificateSubject($certInfo);
        if ($tc === '') {
            $tc = $subjectTc;
        }
        if ($subjectTc !== '' && $tc !== '' && $subjectTc !== $tc) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sertifika TC bilgisi ile girilen TC uyusmuyor.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($subjectTc === '' || !ValidationHelper::isTcLength11($tc)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Sertifika icinde TC bilgisi bulunamadi.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $this->assertRateLimitForEimza($eimzaAuth, $tc);

        $signatureRaw = base64_decode($signatureB64, true);
        if (!is_string($signatureRaw) || $signatureRaw === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Imza (base64) gecersiz.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $challengeRow = $eimzaAuth->consumeChallenge($challengeId, (string) $payload['nonce']);
        if (!is_array($challengeRow)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Challenge gecersiz veya kullanilmis.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ((int) ($payload['id'] ?? 0) !== $challengeId) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Challenge oturumu ile eslesmiyor.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $verify = @openssl_verify((string) $payload['nonce'], $signatureRaw, $certificatePem, OPENSSL_ALGO_SHA256);
        if ($verify !== 1) {
            $eimzaAuth->logAttempt(0, $tc, false, 'signature_verify_failed', $this->certificateSerial($certInfo), $this->certificateFingerprint($certificatePem));
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Elektronik imza dogrulanamadi.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $userModel = new User();
        if (!$userModel->loadByTcKimlikNo($tc)) {
            $eimzaAuth->logAttempt(0, $tc, false, 'user_not_found', $this->certificateSerial($certInfo), $this->certificateFingerprint($certificatePem));
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Bu TC ile kayitli kullanici bulunamadi.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!(bool) ($userModel->eimza_enabled ?? 0)) {
            $eimzaAuth->logAttempt((int) $userModel->id, $tc, false, 'eimza_disabled', $this->certificateSerial($certInfo), $this->certificateFingerprint($certificatePem));
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Kullanici icin e-imza girisi aktif degil.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($this->isUserCertificatePinningRequired($userModel) && !$this->isUserCertificateFingerprintMatched($userModel, $certificatePem)) {
            $eimzaAuth->logAttempt((int) $userModel->id, $tc, false, 'cert_fingerprint_mismatch', $this->certificateSerial($certInfo), $this->certificateFingerprint($certificatePem));
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Kullanici sertifika eslesmesi dogrulanamadi.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!(bool) $userModel->activated) {
            $eimzaAuth->logAttempt((int) $userModel->id, $tc, false, 'user_not_activated', $this->certificateSerial($certInfo), $this->certificateFingerprint($certificatePem));
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Hesabiniz aktive edilmemis.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $adminLevel = AuthHelper::clampLevel((int) $userModel->isadmin);
        $maintenanceReject = MaintenanceHelper::rejectLoginIfBlocked($adminLevel);
        if ($maintenanceReject !== null) {
            $eimzaAuth->logAttempt((int) $userModel->id, $tc, false, 'maintenance_mode', $this->certificateSerial($certInfo), $this->certificateFingerprint($certificatePem));
            http_response_code(503);
            echo json_encode(['ok' => false, 'error' => $maintenanceReject], JSON_UNESCAPED_UNICODE);
            exit;
        }

        unset($_SESSION[self::EIMZA_CHALLENGE_SESSION_KEY]);
        session_regenerate_id(true);
        CsrfHelper::regenerate();
        $_SESSION['user_id'] = $userModel->id;
        $_SESSION['name'] = $userModel->name;
        $_SESSION['username'] = $userModel->username;
        AuthHelper::syncSessionFromLevel(AuthHelper::clampLevel((int) $userModel->isadmin));
        $eimzaAdminLevel = AuthHelper::clampLevel((int) $userModel->isadmin);
        PermissionService::syncSessionPermissions((int) $userModel->id, $eimzaAdminLevel);
        TenantContext::syncSessionFromUser(
            isset($userModel->kurum_id) ? (int) $userModel->kurum_id : null,
            $eimzaAdminLevel,
            isset($userModel->bolge_id) ? (int) $userModel->bolge_id : null
        );
        $_SESSION['avatar'] = $userModel->profileImageWebUrl();
        ThemeViewHelper::syncSessionUserThemeFromDb(isset($userModel->ui_theme) ? (string) $userModel->ui_theme : null);
        $userModel->updateVisitDate($userModel->id);
        $userModel->set('eimza_last_login_at', date('Y-m-d H:i:s'));
        $userModel->set('eimza_cert_serial', $this->certificateSerial($certInfo));
        $userModel->set('eimza_cert_subject', $this->certificateSubjectText($certInfo));
        $userModel->set('eimza_cert_fingerprint', $this->certificateFingerprint($certificatePem));
        $userModel->store();
        $eimzaAuth->logAttempt((int) $userModel->id, $tc, true, 'ok', $this->certificateSerial($certInfo), $this->certificateFingerprint($certificatePem));

        AuditLogHelper::authLogin(
            (int) $userModel->id,
            isset($userModel->kurum_id) ? (int) $userModel->kurum_id : null
        );

        echo json_encode(['ok' => true, 'redirect' => esh_url('Dashboard', 'index')], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function assertEimzaEnabled(): void
    {
        if (!AppSettings::isModuleEnabled('eimza_login')) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'E-imza girisi sistemde kapali.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    private function assertOpenSslReady(): void
    {
        if (!\extension_loaded('openssl')) {
            http_response_code(503);
            echo json_encode(['ok' => false, 'error' => 'Sunucuda OpenSSL eklentisi aktif degil.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    private function extractTcFromCertificateSubject(array $certInfo): string
    {
        $subject = $certInfo['subject'] ?? null;
        if (!is_array($subject)) {
            return '';
        }
        $keys = ['serialNumber', 'SN', 'UID'];
        foreach ($keys as $k) {
            if (!isset($subject[$k])) {
                continue;
            }
            $digits = ValidationHelper::tcDigitsOnly($subject[$k]);
            if (ValidationHelper::isTcLength11($digits)) {
                return $digits;
            }
        }

        return '';
    }

    private function isCertificateValidNow(array $certInfo): bool
    {
        $from = isset($certInfo['validFrom_time_t']) ? (int) $certInfo['validFrom_time_t'] : 0;
        $to = isset($certInfo['validTo_time_t']) ? (int) $certInfo['validTo_time_t'] : 0;
        $now = time();
        if ($from > 0 && $now < $from) {
            return false;
        }
        if ($to > 0 && $now > $to) {
            return false;
        }

        return true;
    }

    private function isCertificateChainTrusted(string $certificatePem): bool
    {
        if (!defined('ESH_EIMZA_TRUST_STORE_PATH')) {
            return false;
        }
        $cafile = (string) ESH_EIMZA_TRUST_STORE_PATH;
        if ($cafile === '' || !is_file($cafile)) {
            return false;
        }
        $ok = @openssl_x509_checkpurpose($certificatePem, -1, [$cafile]);

        return $ok === true || $ok === 1;
    }

    private function isRevocationStatusAcceptable(array $certInfo): bool
    {
        if (!defined('ESH_EIMZA_REQUIRE_REVOCATION_CHECK') || ESH_EIMZA_REQUIRE_REVOCATION_CHECK !== true) {
            return true;
        }
        $extensions = isset($certInfo['extensions']) && is_array($certInfo['extensions'])
            ? $certInfo['extensions']
            : [];
        $hasCrl = !empty($extensions['crlDistributionPoints']);
        $hasOcsp = !empty($extensions['authorityInfoAccess']) && stripos((string) $extensions['authorityInfoAccess'], 'OCSP') !== false;

        return $hasCrl || $hasOcsp;
    }

    private function assertRateLimitForEimza(EimzaAuth $eimzaAuth, string $tc): void
    {
        $seconds = defined('ESH_EIMZA_RATE_WINDOW_SECONDS') ? max(30, (int) ESH_EIMZA_RATE_WINDOW_SECONDS) : 300;
        $maxIp = defined('ESH_EIMZA_MAX_FAILED_PER_IP') ? max(3, (int) ESH_EIMZA_MAX_FAILED_PER_IP) : 10;
        $maxTc = defined('ESH_EIMZA_MAX_FAILED_PER_TC') ? max(3, (int) ESH_EIMZA_MAX_FAILED_PER_TC) : 6;
        $ip = isset($_SERVER['REMOTE_ADDR']) ? trim((string) $_SERVER['REMOTE_ADDR']) : '';
        if ($ip !== '' && $eimzaAuth->failedAttemptsFromIp($ip, $seconds) >= $maxIp) {
            http_response_code(429);
            echo json_encode(['ok' => false, 'error' => 'Cok fazla hatali deneme (IP). Lutfen daha sonra tekrar deneyin.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($eimzaAuth->failedAttemptsFromTc($tc, $seconds) >= $maxTc) {
            http_response_code(429);
            echo json_encode(['ok' => false, 'error' => 'Bu TC icin cok fazla hatali deneme. Lutfen bekleyin.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    private function isUserCertificatePinningRequired(User $user): bool
    {
        if (!defined('ESH_EIMZA_ENFORCE_USER_CERT_PINNING') || ESH_EIMZA_ENFORCE_USER_CERT_PINNING !== true) {
            return false;
        }
        $stored = trim((string) ($user->eimza_cert_fingerprint ?? ''));

        return $stored !== '';
    }

    private function isUserCertificateFingerprintMatched(User $user, string $certificatePem): bool
    {
        $stored = strtolower(trim((string) ($user->eimza_cert_fingerprint ?? '')));
        if ($stored === '') {
            return true;
        }
        $current = $this->certificateFingerprint($certificatePem);

        return $current !== '' && hash_equals($stored, $current);
    }

    private function certificateSerial(array $certInfo): string
    {
        if (isset($certInfo['serialNumberHex']) && trim((string) $certInfo['serialNumberHex']) !== '') {
            return strtoupper(trim((string) $certInfo['serialNumberHex']));
        }
        if (isset($certInfo['serialNumber']) && trim((string) $certInfo['serialNumber']) !== '') {
            return trim((string) $certInfo['serialNumber']);
        }

        return '';
    }

    private function certificateFingerprint(string $certificatePem): string
    {
        $fp = @openssl_x509_fingerprint($certificatePem, 'sha256');
        if (!is_string($fp)) {
            return '';
        }

        return strtolower($fp);
    }

    private function certificateSubjectText(array $certInfo): string
    {
        $subject = $certInfo['subject'] ?? null;
        if (!is_array($subject)) {
            return '';
        }
        $pairs = [];
        foreach ($subject as $k => $v) {
            $pairs[] = (string) $k . '=' . (string) $v;
        }

        return implode(', ', $pairs);
    }

    /**
     * Oturumu Kapatır (logout)
     */
    public function logout() {
        AuditLogHelper::authLogout();
        session_destroy();
        header('Location: ' . esh_url('Auth', 'login'));
        exit;
    }
}