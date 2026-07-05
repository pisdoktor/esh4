/**
 * CSP uyumlu olay delegasyonu — inline onclick/onsubmit/onchange yerine data-* öznitelikleri.
 */
(function () {
    'use strict';

    function confirmMessage(el) {
        var msg = el.getAttribute('data-esh-confirm');
        if (!msg) {
            return true;
        }
        return window.confirm(msg);
    }

    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (!form || form.tagName !== 'FORM') {
            return;
        }
        if (form.getAttribute('data-esh-confirm') && !confirmMessage(form)) {
            ev.preventDefault();
            ev.stopPropagation();
        }
    }, true);

    document.addEventListener('click', function (ev) {
        var confirmEl = ev.target.closest('[data-esh-confirm]');
        if (confirmEl) {
            if (confirmEl.tagName === 'A') {
                if (!confirmMessage(confirmEl)) {
                    ev.preventDefault();
                    return;
                }
            } else if (confirmEl.tagName === 'BUTTON' || confirmEl.tagName === 'INPUT') {
                if ((confirmEl.type === 'submit' || confirmEl.getAttribute('type') === 'submit') && !confirmMessage(confirmEl)) {
                    ev.preventDefault();
                    return;
                }
            }
        }

        var actionEl = ev.target.closest('[data-esh-action]');
        if (actionEl) {
            var action = actionEl.getAttribute('data-esh-action');
            if (action === 'history-back') {
                ev.preventDefault();
                window.history.back();
                return;
            }
            if (action === 'window-print') {
                ev.preventDefault();
                window.print();
                return;
            }
            if (action === 'prevent-default') {
                ev.preventDefault();
                return;
            }
            if (action === 'focus') {
                ev.preventDefault();
                var sel = actionEl.getAttribute('data-esh-focus');
                if (sel) {
                    var focusEl = document.querySelector(sel);
                    if (focusEl && typeof focusEl.focus === 'function') {
                        focusEl.focus();
                    }
                }
                return;
            }
            if (action === 'clear-focus') {
                ev.preventDefault();
                var inputSel = actionEl.getAttribute('data-esh-clear-input');
                if (inputSel) {
                    var input = document.querySelector(inputSel);
                    if (input) {
                        input.value = '';
                        if (typeof input.focus === 'function') {
                            input.focus();
                        }
                    }
                }
                return;
            }
            if (action === 'ekip-box-remove') {
                ev.preventDefault();
                var box = actionEl.closest('.ekip-box');
                if (box) {
                    box.remove();
                }
                var vKey = parseInt(actionEl.getAttribute('data-esh-vkey') || '0', 10);
                if (vKey > 0 && typeof window.updateDisabledOptions === 'function') {
                    window.updateDisabledOptions(vKey);
                }
                return;
            }
        }

        var jumpEl = ev.target.closest('[data-esh-pagination-jump]');
        if (jumpEl) {
            ev.preventDefault();
            var jumpInputSel = jumpEl.getAttribute('data-esh-jump-input') || '#jump_page';
            var jumpInput = document.querySelector(jumpInputSel);
            var jumpMax = parseInt(jumpEl.getAttribute('data-esh-jump-max') || '0', 10);
            var jumpBase = jumpEl.getAttribute('data-esh-jump-url-base') || '';
            if (jumpInput && jumpBase) {
                var pageNum = parseInt(jumpInput.value, 10);
                if (pageNum > 0 && (!jumpMax || pageNum <= jumpMax)) {
                    window.location.href = jumpBase + '&page=' + pageNum;
                }
            }
            return;
        }

        var rotaEl = ev.target.closest('[data-esh-harita-rota]');
        if (rotaEl && typeof window.ESH_HARITA_ROTA === 'function') {
            ev.preventDefault();
            try {
                var coords = JSON.parse(rotaEl.getAttribute('data-esh-harita-rota') || '[]');
                window.ESH_HARITA_ROTA(coords);
            } catch (e) { /* ignore */ }
            return;
        }

        var pdfEl = ev.target.closest('[data-esh-pdfmake]');
        if (pdfEl && typeof window.pdfMake !== 'undefined' && typeof window.dd !== 'undefined') {
            ev.preventDefault();
            var mode = pdfEl.getAttribute('data-esh-pdfmake');
            var pdf = window.pdfMake.createPdf(window.dd);
            if (mode === 'print') {
                pdf.print();
            } else if (mode === 'download') {
                var name = pdfEl.getAttribute('data-esh-pdfmake-filename') || 'document.pdf';
                pdf.download(name);
            }
            return;
        }

        var callEl = ev.target.closest('[data-esh-call]');
        if (callEl) {
            var interactiveEl = ev.target.closest('a, button, input, select, textarea, label');
            if (interactiveEl && interactiveEl !== callEl) {
                return;
            }
            var fnName = callEl.getAttribute('data-esh-call');
            var fn = fnName ? window[fnName] : null;
            if (typeof fn !== 'function') {
                return;
            }
            ev.preventDefault();
            var args = [];
            if (callEl.getAttribute('data-esh-call-self') === '1') {
                args.push(callEl);
            }
            var argsJson = callEl.getAttribute('data-esh-call-args');
            if (argsJson) {
                try {
                    var parsed = JSON.parse(argsJson);
                    if (Array.isArray(parsed)) {
                        args = args.concat(parsed);
                    }
                } catch (e) { /* ignore */ }
            }
            var singleArg = callEl.getAttribute('data-esh-call-arg');
            if (singleArg !== null && singleArg !== '') {
                args.push(singleArg);
            }
            fn.apply(null, args);
        }
    }, true);

    document.addEventListener('change', function (ev) {
        var el = ev.target;
        if (!el || !el.hasAttribute) {
            return;
        }
        if (el.hasAttribute('data-esh-navigate')) {
            var navUrl = el.value;
            if (navUrl) {
                window.location.href = navUrl;
            }
            return;
        }
        if (!el.hasAttribute('data-esh-auto-submit')) {
            return;
        }
        if (el.form) {
            el.form.submit();
        }
    }, true);
})();
