/**
 * Türkiye cep telefonu: 0 (5xx) xxx xx xx — 11 rakam, zorunlu tam format (required alanlarda).
 */
(function () {
    'use strict';

    var PLACEHOLDER = '0 (5xx) xxx xx xx';
    var MAX_LEN = 17;
    var PATTERN = '^0 \\(\\d{3}\\) \\d{3} \\d{2} \\d{2}$';
    var SELECTOR = '.tel-mask, .phone-mask, input[name="ceptel1"], input[name="ceptel2"], input[type="tel"][name*="ceptel"]';

    function digitsOnly(value) {
        var d = String(value || '').replace(/\D/g, '');
        if (d.length === 0) {
            return '';
        }
        if (d.charAt(0) !== '0') {
            d = '0' + d;
        }
        return d.slice(0, 11);
    }

    function formatFromDigits(digits) {
        if (!digits) {
            return '';
        }
        var v = digits.charAt(0);
        if (digits.length > 1) {
            v += ' (' + digits.substring(1, Math.min(4, digits.length));
            if (digits.length >= 4) {
                v += ')';
            }
        }
        if (digits.length > 4) {
            v += ' ' + digits.substring(4, Math.min(7, digits.length));
        }
        if (digits.length > 7) {
            v += ' ' + digits.substring(7, Math.min(9, digits.length));
        }
        if (digits.length > 9) {
            v += ' ' + digits.substring(9, 11);
        }
        return v;
    }

    function isComplete(value) {
        return digitsOnly(value).length === 11;
    }

    function syncValidity(el) {
        var raw = String(el.value || '').trim();
        if (raw === '') {
            if (el.required) {
                el.setCustomValidity('Telefon numarası zorunludur.');
            } else {
                el.setCustomValidity('');
            }
            return;
        }
        if (!isComplete(raw)) {
            el.setCustomValidity('Telefon formatı: 0 (5xx) xxx xx xx şeklinde 11 hane olmalıdır.');
            return;
        }
        el.setCustomValidity('');
    }

    function applyMaskInput(el) {
        var digits = digitsOnly(el.value);
        var formatted = formatFromDigits(digits);
        if (el.value !== formatted) {
            el.value = formatted;
        }
        syncValidity(el);
        if (el.form) {
            el.form.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    function preparePhoneInput(el) {
        if (!el || el.dataset.eshPhoneMask === '1') {
            return;
        }
        el.dataset.eshPhoneMask = '1';
        el.classList.add('esh-phone-mask');
        el.setAttribute('inputmode', 'numeric');
        el.setAttribute('autocomplete', 'tel');
        el.setAttribute('maxlength', String(MAX_LEN));
        el.setAttribute('placeholder', PLACEHOLDER);
        el.setAttribute('pattern', PATTERN);
        el.setAttribute('title', 'Format: 0 (5xx) xxx xx xx');

        if (el.value && el.value.trim() !== '') {
            el.value = formatFromDigits(digitsOnly(el.value));
        }

        el.addEventListener('input', function () {
            applyMaskInput(el);
        });
        el.addEventListener('blur', function () {
            applyMaskInput(el);
        });
        el.addEventListener('change', function () {
            syncValidity(el);
        });

        syncValidity(el);
    }

    window.eshInitPhoneMasks = function (root) {
        var scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll(SELECTOR).forEach(preparePhoneInput);
    };

    window.eshFormatTrPhone = formatFromDigits;
    window.eshPhoneDigits = digitsOnly;
    window.eshIsCompleteTrPhone = isComplete;

    function init() {
        window.eshInitPhoneMasks(document);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    if (typeof MutationObserver !== 'undefined' && document.body) {
        var obs = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (!node || node.nodeType !== 1) {
                        return;
                    }
                    if (node.matches && node.matches(SELECTOR)) {
                        preparePhoneInput(node);
                    }
                    if (node.querySelectorAll) {
                        window.eshInitPhoneMasks(node);
                    }
                });
            });
        });
        obs.observe(document.body, { childList: true, subtree: true });
    }
})();
