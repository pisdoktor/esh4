/**
 * User edit — yeni şifre alanlarında göster/gizle ve eşleşme uyarısı.
 */
(function () {
    'use strict';

    var form = document.querySelector('.esh-profile-edit-form');
    if (!form) {
        return;
    }

    var newPw = form.querySelector('[name="new_password"]');
    var confirmPw = form.querySelector('[name="confirm_password"]');
    if (!newPw || !confirmPw) {
        return;
    }

    var mismatchEl = document.getElementById('esh-profile-password-mismatch');

    function enhanceToggle(input) {
        if (!input || input.dataset.eshPwToggleBound === '1' || input.disabled) {
            return;
        }
        var group = input.closest('.input-group');
        if (!group || group.querySelector('.esh-profile-pw-toggle')) {
            return;
        }

        input.dataset.eshPwToggleBound = '1';
        input.classList.add('esh-profile-pw-input');

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'input-group-text esh-profile-pw-toggle border-start-0';
        btn.setAttribute('aria-label', 'Şifreyi göster');
        btn.setAttribute('aria-pressed', 'false');
        btn.setAttribute('tabindex', '0');
        btn.innerHTML = '<i class="fa-solid fa-eye" aria-hidden="true"></i>';

        btn.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'Şifreyi gizle' : 'Şifreyi göster');
            var icon = btn.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye', !show);
                icon.classList.toggle('fa-eye-slash', show);
            }
        });

        group.appendChild(btn);
    }

    function shouldValidate() {
        return newPw.value !== '' || confirmPw.value !== '';
    }

    function setInvalidState(invalid) {
        newPw.classList.toggle('is-invalid', invalid);
        confirmPw.classList.toggle('is-invalid', invalid);
        if (invalid) {
            confirmPw.setCustomValidity('Şifreler uyuşmuyor.');
        } else {
            newPw.setCustomValidity('');
            confirmPw.setCustomValidity('');
        }
    }

    function showMismatch() {
        if (mismatchEl) {
            mismatchEl.classList.remove('d-none');
        }
    }

    function hideMismatch() {
        if (mismatchEl) {
            mismatchEl.classList.add('d-none');
        }
    }

    function checkMatch() {
        if (!shouldValidate()) {
            hideMismatch();
            setInvalidState(false);
            return true;
        }

        if (newPw.value === confirmPw.value) {
            hideMismatch();
            setInvalidState(false);
            return true;
        }

        showMismatch();
        setInvalidState(true);
        return false;
    }

    enhanceToggle(newPw);
    enhanceToggle(confirmPw);

    newPw.addEventListener('input', checkMatch);
    confirmPw.addEventListener('input', checkMatch);

    form.addEventListener('submit', function (e) {
        if (!checkMatch()) {
            e.preventDefault();
            (confirmPw.value === '' ? newPw : confirmPw).focus();
        }
    });
})();
