<?php

namespace App\Controllers;

use App\Helpers\OperationalSettings;
use App\Helpers\RateLimitHelper;
use App\Helpers\SimpleCaptchaHelper;
use App\Helpers\ValidationHelper;
use App\Models\Patient;

/**
 * Giriş gerektirmeyen kayıtlı hasta TC sorgusu (eski kök `hastaarama.php`).
 */
class PublicHastaaramaController {

    public function __construct()
    {
        if (!OperationalSettings::isPublicHastaaramaEnabled()) {
            http_response_code(404);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>Hizmet kullanılamıyor</title></head><body style="font-family:sans-serif;padding:2rem;max-width:32rem;margin:auto;">';
            echo '<h1>Hizmet kullanılamıyor</h1>';
            echo '<p class="text-muted">Kamu hasta TC sorgulama özelliği yönetici tarafından kapatılmıştır.</p>';
            echo '<p><a href="' . htmlspecialchars(esh_url('Auth', 'login', [], true), ENT_QUOTES, 'UTF-8') . '">Yönetim paneli girişi</a></p>';
            echo '</body></html>';
            exit;
        }
    }

    public function index(): void {
        $eshGuestPageTitle = 'Kayıtlı hasta sorgulama';
        $eshGuestScript = 'index';
        $eshGuestBilgilendirme = OperationalSettings::publicHastaaramaBilgilendirmeMetni();
        $eshGuestCaptcha = SimpleCaptchaHelper::issue('public_hastaarama');
        $eshGuestCaptchaError = SimpleCaptchaHelper::consumeFlashError('public_hastaarama');
        $eshGuestInnerFile = ROOT_PATH . '/views/guest/hastaarama_form.php';
        include ROOT_PATH . '/views/guest/hastaarama_shell.php';
    }

    public function sonuc(): void {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            header('Location: ' . esh_url('PublicHastaarama', 'index', [], true), true, 303);
            exit;
        }

        if (!SimpleCaptchaHelper::validate($_POST[SimpleCaptchaHelper::INPUT_FIELD] ?? null, 'public_hastaarama')) {
            SimpleCaptchaHelper::setFlashError(
                'public_hastaarama',
                'Güvenlik sorusu yanlış veya eksik. Lütfen tekrar deneyin.'
            );
            header('Location: ' . esh_url('PublicHastaarama', 'index', [], true), true, 303);
            exit;
        }

        $ip = RateLimitHelper::clientIp();
        if (RateLimitHelper::tooManyAttempts(
            'public_hastaarama',
            $ip,
            ESH_PUBLIC_HASTAARAMA_RATE_MAX_ATTEMPTS,
            ESH_PUBLIC_HASTAARAMA_RATE_WINDOW_SECONDS
        )) {
            http_response_code(429);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>Çok fazla istek</title></head><body style="font-family:sans-serif;padding:2rem;max-width:32rem;margin:auto;">';
            echo '<h1>Çok fazla sorgu</h1>';
            echo '<p>Lütfen bir süre bekleyip tekrar deneyin.</p>';
            echo '<p><a href="' . htmlspecialchars(esh_url('PublicHastaarama', 'index', [], true), ENT_QUOTES, 'UTF-8') . '">Geri dön</a></p>';
            echo '</body></html>';
            exit;
        }

        RateLimitHelper::hit('public_hastaarama', $ip, ESH_PUBLIC_HASTAARAMA_RATE_WINDOW_SECONDS);

        $tc = ValidationHelper::tcDigitsOnly($_POST['tckimlik'] ?? '');
        $eshGuestSonucState = 'invalid';
        $eshGuestSonucRow = null;

        if (ValidationHelper::isTcLength11($tc)) {
            $kurumId = null;
            $kurumKod = trim((string) ($_POST['kurum_kod'] ?? ''));
            if ($kurumKod !== '') {
                $kurum = new \App\Models\Kurum();
                if ($kurum->loadByKod($kurumKod)) {
                    $kurumId = (int) $kurum->id;
                }
            }
            $row = (new Patient())->findByTckimlikForPublicLookup($tc, $kurumId);
            if ($row) {
                $eshGuestSonucState = 'found';
                $eshGuestSonucRow = $row;
            } else {
                $eshGuestSonucState = 'not_found';
            }
        }

        $eshGuestPageTitle = 'Sorgu sonucu';
        $eshGuestScript = 'sonuc';
        $eshGuestInnerFile = ROOT_PATH . '/views/guest/hastaarama_sonuc.php';
        include ROOT_PATH . '/views/guest/hastaarama_shell.php';
    }
}
