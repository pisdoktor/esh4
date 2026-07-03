<?php
declare(strict_types=1);

use App\Helpers\FormHelper;
use App\Helpers\SimpleCaptchaHelper;

$__action = htmlspecialchars(esh_url('PatientPortal', 'doLogin', [], true), ENT_QUOTES, 'UTF-8');
$eshPortalCaptchaQuestion = htmlspecialchars((string) ($eshPortalCaptcha['question'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<article class="pha-card">
    <header class="pha-card__hero">
        <div class="pha-card__icon" aria-hidden="true">
            <i class="fa-solid fa-user-nurse"></i>
        </div>
        <h1 class="pha-card__title">Hasta / bakım veren girişi</h1>
        <p class="pha-card__lead">Kayıtlı TC kimlik numaranız ve dosyada kayıtlı cep telefonu (veya bakım veren telefonu) ile güvenli giriş yapın.</p>
    </header>
    <div class="pha-card__body">
        <?php if (!empty($eshPortalBilgilendirme)): ?>
            <div class="alert alert-info small mb-3" role="note"><?= nl2br(htmlspecialchars((string) $eshPortalBilgilendirme, ENT_QUOTES, 'UTF-8')) ?></div>
        <?php endif; ?>
        <?php if (!empty($eshPortalCaptchaError)): ?>
            <div class="alert alert-danger small mb-3" role="alert"><?= htmlspecialchars((string) $eshPortalCaptchaError, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div class="pha-pills mb-3" role="list">
            <span class="pha-pill" role="listitem"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Planlı ziyaretler</span>
            <span class="pha-pill" role="listitem"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Ziyaret özeti</span>
            <span class="pha-pill" role="listitem"><i class="fa-solid fa-comment-sms" aria-hidden="true"></i> SMS onayı</span>
        </div>

        <form action="<?= $__action ?>" method="post" autocomplete="off" novalidate id="portalLoginForm">
            <?= esh_csrf_field() ?>
            <?= FormHelper::fieldInputGroup('tckimlik', 'Hasta TC kimlik numarası', '', [
                'col' => '',
                'id' => 'portalHastaTc',
                'labelClass' => 'form-label',
                'inputGroupExtraClass' => 'pha-tc-input-group',
                'prefixIcon' => 'fa-solid fa-id-card',
                'maxlength' => '11',
                'pattern' => '[0-9]*',
                'inputmode' => 'numeric',
                'required' => true,
                'placeholder' => '00000000000',
                'autocomplete' => 'off',
                'extraAttrs' => ['autofocus' => 'autofocus'],
            ]) ?>
            <?= FormHelper::fieldPhone('telefon', 'Kayıtlı telefon', '', [
                'col' => 'mt-3',
                'labelClass' => 'form-label',
                'required' => true,
                'placeholder' => '05xx xxx xx xx',
                'afterInput' => '<div class="form-text small">Hasta cep telefonu veya bakım veren telefonu (dosyada kayıtlı olmalı).</div>',
            ]) ?>

            <div class="mt-3 p-3 rounded-3 bg-body-tertiary border">
                <label class="form-label small fw-semibold" for="portalCaptchaAnswer">Güvenlik sorusu</label>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge text-bg-secondary font-monospace px-3 py-2" id="portalCaptchaQuestion"><?= $eshPortalCaptchaQuestion ?></span>
                    <?= FormHelper::fieldInput(SimpleCaptchaHelper::INPUT_FIELD, '', '', [
                        'col' => 'flex-grow-1',
                        'id' => 'portalCaptchaAnswer',
                        'labelClass' => 'visually-hidden',
                        'class' => 'form-control',
                        'required' => true,
                        'inputmode' => 'numeric',
                        'autocomplete' => 'off',
                        'placeholder' => 'Cevap',
                    ]) ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 rounded-pill mt-4 py-2 fw-semibold">
                <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Giriş yap
            </button>
        </form>

        <p class="small text-muted text-center mt-4 mb-0">
            Dosya durumu sorgusu için
            <a href="<?= htmlspecialchars(esh_url('PublicHastaarama', 'index', [], true), ENT_QUOTES, 'UTF-8') ?>">kamu TC sorgulama</a>
            sayfasını kullanabilirsiniz.
        </p>
    </div>
</article>
