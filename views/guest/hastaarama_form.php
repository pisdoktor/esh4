<?php
declare(strict_types=1);
use App\Helpers\FormHelper;
use App\Helpers\SimpleCaptchaHelper;

$__action = htmlspecialchars(esh_url('PublicHastaarama', 'sonuc', [], true), ENT_QUOTES, 'UTF-8');
$eshGuestCaptchaQuestion = htmlspecialchars((string) ($eshGuestCaptcha['question'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<article class="pha-card">
    <header class="pha-card__hero">
        <div class="pha-card__icon" aria-hidden="true">
            <i class="fa-solid fa-fingerprint"></i>
        </div>
        <h1 class="pha-card__title">Kayıtlı hasta sorgulama</h1>
        <p class="pha-card__lead">11 haneli TC ile dosya var mı ve durumunu (aktif, pasif, bekleyen vb.) anında görün; oturum açmanız gerekmez.</p>
    </header>
    <div class="pha-card__body">
        <?php if (!empty($eshGuestBilgilendirme)): ?>
            <div class="alert alert-info small mb-3" role="note"><?= nl2br(htmlspecialchars((string) $eshGuestBilgilendirme, ENT_QUOTES, 'UTF-8')) ?></div>
        <?php endif; ?>
        <div class="pha-pills" role="list">
            <span class="pha-pill" role="listitem"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Güvenli</span>
            <span class="pha-pill" role="listitem"><i class="fa-solid fa-user-check" aria-hidden="true"></i> Kayıt durumu</span>
            <span class="pha-pill" role="listitem"><i class="fa-solid fa-clock" aria-hidden="true"></i> Anlık sonuç</span>
        </div>

        <form action="<?= $__action ?>" method="post" autocomplete="off" novalidate id="phaTcForm">
            <?= esh_csrf_field() ?>
            <div class="pha-tc-wrap">
                <?php
                $eshPhaTcMeter = '<div class="pha-tc-meter" id="phaTcMeter" aria-hidden="true">';
                for ($__i = 0; $__i < 11; $__i++) {
                    $eshPhaTcMeter .= '<span class="pha-tc-meter__dot"></span>';
                }
                $eshPhaTcMeter .= '</div><p class="pha-tc-hint" id="phaTcHint">Yalnızca rakam; toplam 11 hane.</p>';
                echo FormHelper::fieldInputGroup('tckimlik', 'Hasta TC kimlik numarası', '', [
                    'col' => '',
                    'id' => 'guestHastaTc',
                    'labelClass' => 'form-label',
                    'labelFor' => 'guestHastaTc',
                    'inputGroupExtraClass' => 'pha-tc-input-group',
                    'prefixIcon' => 'fa-solid fa-id-card',
                    'maxlength' => '11',
                    'pattern' => '[0-9]*',
                    'inputmode' => 'numeric',
                    'required' => true,
                    'placeholder' => '00000000000',
                    'autocomplete' => 'off',
                    'ariaDescribedby' => 'phaTcHint phaTcMeter',
                    'extraAttrs' => ['autofocus' => 'autofocus'],
                    'afterInput' => $eshPhaTcMeter,
                ]);
                ?>
            </div>
            <?= FormHelper::fieldInput('kurum_kod', 'Kurum kodu', '', [
                'col' => 'mb-3',
                'id' => 'guestKurumKod',
                'labelClass' => 'form-label',
                'labelFor' => 'guestKurumKod',
                'placeholder' => 'ornek-kurum',
                'autocomplete' => 'off',
                'ariaDescribedby' => 'phaKurumHint',
                'afterInput' => '<p class="pha-tc-hint" id="phaKurumHint">Hizmet aldığınız kurumun kısa kodu (yöneticinizden öğrenin).</p>',
            ]) ?>
            <?php if (!empty($eshGuestCaptchaError)): ?>
                <div class="alert alert-warning small mb-3" role="alert"><?= htmlspecialchars((string) $eshGuestCaptchaError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <div class="mb-3 pha-captcha-wrap">
                <?= FormHelper::fieldInputGroup(SimpleCaptchaHelper::INPUT_FIELD, 'Güvenlik sorusu', '', [
                    'col' => '',
                    'id' => 'guestHastaCaptcha',
                    'labelClass' => 'form-label',
                    'labelFor' => 'guestHastaCaptcha',
                    'prefixText' => $eshGuestCaptchaQuestion . ' = ?',
                    'prefixIconClass' => 'pha-captcha__question',
                    'maxlength' => '2',
                    'pattern' => '[0-9]*',
                    'inputmode' => 'numeric',
                    'required' => true,
                    'placeholder' => 'Cevap',
                    'autocomplete' => 'off',
                    'ariaDescribedby' => 'phaCaptchaHint',
                    'afterInput' => '<p class="pha-tc-hint" id="phaCaptchaHint">Yukarıdaki işlemin sonucunu girin.</p>',
                ]) ?>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary pha-btn-submit" disabled>
                    <i class="fa-solid fa-magnifying-glass me-2" aria-hidden="true"></i> Sorgula
                </button>
            </div>
        </form>
    </div>
</article>
