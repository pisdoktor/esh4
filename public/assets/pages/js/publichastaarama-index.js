$(function () {
    const $inp = $('#guestHastaTc');
    const $captcha = $('#guestHastaCaptcha');
    const $form = $inp.closest('form');
    const $btn = $form.find('.pha-btn-submit');
    const $hint = $('#phaTcHint');
    const $dots = $('.pha-tc-meter__dot');

    if (!$inp.length) {
        return;
    }

    function syncTcUi() {
        const raw = String($inp.val() || '').replace(/\D/g, '').slice(0, 11);
        if ($inp.val() !== raw) {
            $inp.val(raw);
        }
        const len = raw.length;
        const captchaRaw = $captcha.length
            ? String($captcha.val() || '').replace(/\D/g, '').slice(0, 2)
            : '';
        if ($captcha.length && $captcha.val() !== captchaRaw) {
            $captcha.val(captchaRaw);
        }
        $dots.each(function (i) {
            $(this).toggleClass('is-on', i < len);
        });
        const captchaOk = !$captcha.length || captchaRaw.length > 0;
        if (len === 11) {
            $inp.removeClass('is-invalid').addClass('is-valid');
            $hint.text('11 hane tamam — sorgulayabilirsiniz.').addClass('is-ok');
            $btn.prop('disabled', !captchaOk);
        } else if (len > 0) {
            $inp.removeClass('is-valid').addClass('is-invalid');
            $hint.removeClass('is-ok').text((11 - len) + ' hane daha girin.');
            $btn.prop('disabled', true);
        } else {
            $inp.removeClass('is-valid is-invalid');
            $hint.removeClass('is-ok').text('Yalnızca rakam; toplam 11 hane.');
            $btn.prop('disabled', true);
        }
    }

    $inp.on('input blur', syncTcUi);
    if ($captcha.length) {
        $captcha.on('input blur', syncTcUi);
    }
    syncTcUi();

    $form.on('submit', function () {
        const raw = String($inp.val() || '').replace(/\D/g, '');
        const captchaRaw = $captcha.length
            ? String($captcha.val() || '').replace(/\D/g, '')
            : '';
        if (raw.length !== 11 || ($captcha.length && captchaRaw.length === 0)) {
            return false;
        }
        $btn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Sorgulanıyor…'
        );
    });
});
