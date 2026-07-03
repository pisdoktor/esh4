/**
 * Metin girişlerini yazılırken Türkçe kurallarıyla büyük harfe çevirir (İ/i).
 * Hariç: şifre, e-posta, sayı, tarih-saat tipleri, readonly/disabled, .datepicker,
 * .preserve-case / data-preserve-case, giriş kullanıcı adı (name=username).
 */
(function () {
    'use strict';

    var SKIP_TYPES = [
        'password', 'email', 'hidden', 'file', 'number', 'range', 'color',
        'checkbox', 'radio', 'submit', 'button', 'reset', 'date', 'datetime-local',
        'month', 'week', 'time', 'tel', 'url', 'search'
    ];

    function shouldSkip(el) {
        if (!el || (el.nodeName !== 'INPUT' && el.nodeName !== 'TEXTAREA')) {
            return true;
        }
        if (el.closest('[data-preserve-case]')) {
            return true;
        }
        if (el.hasAttribute('data-preserve-case')) {
            return true;
        }
        if (el.classList && el.classList.contains('preserve-case')) {
            return true;
        }
        if (el.readOnly || el.disabled) {
            return true;
        }
        if (el.classList && el.classList.contains('datepicker')) {
            return true;
        }
        if (el.classList && (el.classList.contains('tel-mask') || el.classList.contains('phone-mask'))) {
            return true;
        }
        var t = el.type ? String(el.type).toLowerCase() : 'text';
        if (SKIP_TYPES.indexOf(t) !== -1) {
            return true;
        }
        var nm = el.name ? String(el.name).toLowerCase() : '';
        if (nm === 'username') {
            return true;
        }
        return false;
    }

    function applyUpper(el) {
        var val = el.value;
        var up = val.toLocaleUpperCase('tr-TR');
        if (val === up) {
            return;
        }
        var start = el.selectionStart;
        var end = el.selectionEnd;
        el.value = up;
        if (start != null && typeof el.setSelectionRange === 'function') {
            try {
                el.setSelectionRange(start, end);
            } catch (e) {
                /* ignore */
            }
        }
    }

    document.addEventListener(
        'input',
        function (e) {
            var el = e.target;
            if (shouldSkip(el)) {
                return;
            }
            applyUpper(el);
        },
        true
    );

    document.addEventListener(
        'blur',
        function (e) {
            var el = e.target;
            if (shouldSkip(el)) {
                return;
            }
            applyUpper(el);
        },
        true
    );
})();
