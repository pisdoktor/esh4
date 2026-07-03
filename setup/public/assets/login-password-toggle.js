/**
 * Login sayfası — şifre alanında göster/gizle (yalnızca name="password").
 */
(function () {
    'use strict';

    function enhance(input) {
        if (!input || input.dataset.eshPwToggleBound === '1' || input.disabled) {
            return;
        }
        var group = input.closest('.input-group');
        if (!group || group.querySelector('.esh-login-pw-toggle')) {
            return;
        }

        input.dataset.eshPwToggleBound = '1';
        input.classList.add('esh-login-pw-input');

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'input-group-text esh-login-pw-toggle border-start-0';
        btn.setAttribute('aria-label', 'Şifreyi göster');
        btn.setAttribute('aria-pressed', 'false');
        btn.setAttribute('tabindex', '0');
        btn.innerHTML = '<i class="fa-solid fa-eye" aria-hidden="true"></i>';

        btn.addEventListener('click', function () {
            toggleVisibility(input, btn);
        });

        group.appendChild(btn);
    }

    function toggleVisibility(input, btn) {
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Şifreyi gizle' : 'Şifreyi göster');
        var icon = btn.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-eye', !show);
            icon.classList.toggle('fa-eye-slash', show);
        }
    }

    function init() {
        document.querySelectorAll('body.page-login input[type="password"][name="password"]:not([disabled])').forEach(enhance);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
