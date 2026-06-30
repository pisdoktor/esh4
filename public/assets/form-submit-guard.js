/**
 * POST formları:
 *  - [required] alanların etiketine otomatik kırmızı * + sr-only "(zorunlu)"
 *  - İsteğe bağlı üst not: form[data-esh-required-legend="off"] ile kapatılır
 *  - İşaretleri kapatma: form[data-esh-required-markers="off"]
 *
 * Gönder düğmesi: form geçerli olana kadar type="submit" disabled (Constraint Validation API).
 * İstisna: form[data-esh-submit-guard="off"]
 *
 * Klavye kısayolları (kayıt formları):
 *  - Ctrl+S / Cmd+S → kaydet (primary submit)
 *  - Esc → iptal / vazgeç (data-esh-form-cancel veya eşleşen düğme)
 *  - Kapat: form[data-esh-form-shortcuts="off"]
 */
(function () {
    'use strict';

    function cssEscapeId(id) {
        if (typeof CSS !== 'undefined' && typeof CSS.escape === 'function') {
            return CSS.escape(id);
        }
        return String(id).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
    }

    function isOptionButtonLabel(label) {
        return !!(label && label.classList && label.classList.contains('btn'));
    }

    /**
     * btn-group / radio: üstteki alan etiketi (Cinsiyet), seçenek düğmesi (Erkek) değil.
     * @returns {HTMLLabelElement|null}
     */
    function findFieldLabelForRadioGroup(radioEl) {
        var btnGroup = radioEl.closest('.btn-group');
        if (!btnGroup) {
            return null;
        }
        var prev = btnGroup.previousElementSibling;
        while (prev) {
            if (prev.tagName === 'LABEL' && !isOptionButtonLabel(prev)) {
                return prev;
            }
            prev = prev.previousElementSibling;
        }
        var mb = btnGroup.closest('.mb-3');
        if (mb) {
            var mbLbl = mb.querySelector(':scope > label.form-label, :scope > label');
            if (mbLbl && !isOptionButtonLabel(mbLbl)) {
                return mbLbl;
            }
        }
        var col = btnGroup.closest('[class*="col-"]');
        if (col) {
            var colLbl = col.querySelector(':scope > label.form-label, :scope > label');
            if (colLbl && !isOptionButtonLabel(colLbl)) {
                return colLbl;
            }
        }
        return null;
    }

    /**
     * @returns {HTMLLabelElement|null}
     */
    function findLabelForRequiredControl(el) {
        var form = el.form;
        if (!form) {
            return null;
        }

        if (el.type === 'radio') {
            var nm = el.name;
            var firstReq = null;
            var all = form.querySelectorAll('input[type="radio"]');
            for (var ri = 0; ri < all.length; ri++) {
                if (all[ri].name === nm && all[ri].required) {
                    firstReq = all[ri];
                    break;
                }
            }
            if (!firstReq || el !== firstReq) {
                return null;
            }
            el = firstReq;
            var groupLbl = findFieldLabelForRadioGroup(el);
            if (groupLbl) {
                return groupLbl;
            }
        }

        if (el.id) {
            var byFor = form.querySelector('label[for="' + cssEscapeId(el.id) + '"]');
            if (byFor && !isOptionButtonLabel(byFor)) {
                return byFor;
            }
        }

        var wrapLbl = el.closest('label');
        if (wrapLbl && !isOptionButtonLabel(wrapLbl)) {
            return wrapLbl;
        }

        var mb = el.closest('.mb-3');
        if (mb) {
            var d = mb.querySelector(':scope > label');
            if (d && d.tagName === 'LABEL') {
                return d;
            }
        }

        var col = el.closest('[class*="col-"]');
        if (col) {
            var cl = col.querySelector('label.form-label');
            if (cl) {
                return cl;
            }
            var cl2 = col.querySelector('label');
            if (cl2) {
                return cl2;
            }
        }

        var ig = el.closest('.input-group');
        if (ig && ig.parentElement) {
            var pe = ig.parentElement;
            var prev = pe.querySelector(':scope > label');
            if (prev && prev.tagName === 'LABEL') {
                return prev;
            }
        }

        return null;
    }

    function appendRequiredStar(label) {
        if (!label || label.querySelector('.esh-required-mark')) {
            return;
        }
        var span = document.createElement('span');
        span.className = 'esh-required-mark text-danger fw-semibold ms-1';
        span.setAttribute('aria-hidden', 'true');
        span.title = 'Zorunlu alan';
        span.textContent = '*';
        label.appendChild(span);
        var sr = document.createElement('span');
        sr.className = 'visually-hidden';
        sr.textContent = ' (zorunlu)';
        label.appendChild(sr);
    }

    function insertRequiredLegend(form) {
        if (form.getAttribute('data-esh-required-legend') === 'off') {
            return;
        }
        if (form.querySelector('.esh-required-legend-note')) {
            return;
        }
        if (!form.querySelector('input[required]:not([type="hidden"]), select[required], textarea[required]')) {
            return;
        }
        var p = document.createElement('p');
        p.className = 'small text-muted mb-3 pb-2 border-bottom esh-required-legend-note';
        p.innerHTML = '<span class="text-danger fw-semibold" aria-hidden="true">*</span> <span>işaretli alanlar zorunludur.</span>';
        var first = form.firstElementChild;
        while (first && first.tagName === 'INPUT' && first.getAttribute('type') === 'hidden') {
            first = first.nextElementSibling;
        }
        if (first) {
            form.insertBefore(p, first);
        } else {
            form.appendChild(p);
        }
    }

    function markRequiredLabels(form) {
        if (form.getAttribute('data-esh-required-markers') === 'off') {
            return;
        }
        var sel = 'input[required]:not([type="hidden"]), select[required], textarea[required]';
        var controls = form.querySelectorAll(sel);
        controls.forEach(function (el) {
            var lbl = findLabelForRequiredControl(el);
            appendRequiredStar(lbl);
        });
        insertRequiredLegend(form);
    }

    function refreshForm(form) {
        if (!form || form.getAttribute('data-esh-submit-guard') === 'off') {
            return;
        }
        var submits = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        if (!submits.length) {
            return;
        }
        if (!form.querySelector('[required]')) {
            submits.forEach(function (btn) {
                btn.disabled = false;
            });
            return;
        }
        var ok = false;
        try {
            ok = form.checkValidity();
        } catch (e) {
            ok = true;
        }
        submits.forEach(function (btn) {
            btn.disabled = !ok;
        });
    }

    function bindForm(form) {
        if (form.getAttribute('data-esh-submit-guard') === 'off') {
            return;
        }
        if (form.dataset.eshGuardBound === '1') {
            return;
        }
        var method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'post') {
            return;
        }
        if (!form.querySelector('[required]')) {
            return;
        }
        form.dataset.eshGuardBound = '1';

        refreshForm(form);

        ['input', 'change', 'blur'].forEach(function (evName) {
            form.addEventListener(evName, function () {
                refreshForm(form);
            }, true);
        });
    }

    function eshCancelLabelMatches(text) {
        var t = String(text || '').replace(/\s+/g, ' ').trim().toLowerCase();
        if (t === '') {
            return false;
        }
        return /iptal|vazgeç|vazgec|geri dön|geri\b/.test(t);
    }

    function eshFormPrimarySubmit(form) {
        var explicit = form.querySelector('[data-esh-form-save]');
        if (explicit) {
            return explicit;
        }
        var primary = form.querySelector('button[type="submit"].btn-primary, input[type="submit"].btn-primary');
        if (primary) {
            return primary;
        }
        primary = form.querySelector('button[type="submit"].btn-success, input[type="submit"].btn-success');
        if (primary) {
            return primary;
        }
        return form.querySelector('button[type="submit"], input[type="submit"]');
    }

    function eshFormShortcutCancelEl(form) {
        var explicit = form.querySelector('[data-esh-form-cancel]');
        if (explicit) {
            return explicit;
        }
        var nodes = form.querySelectorAll('a.btn, button.btn');
        var i;
        for (i = nodes.length - 1; i >= 0; i--) {
            var el = nodes[i];
            if (el.type === 'submit') {
                continue;
            }
            if (el.getAttribute('data-bs-dismiss') === 'modal') {
                continue;
            }
            if (!eshCancelLabelMatches(el.textContent)) {
                continue;
            }
            if (el.tagName === 'A') {
                var href = el.getAttribute('href') || '';
                if (href !== '' && href !== '#') {
                    return el;
                }
            }
            if (el.tagName === 'BUTTON' && el.type !== 'submit') {
                return el;
            }
        }
        return null;
    }

    function eshFormEligibleForShortcuts(form) {
        if (!form || form.tagName !== 'FORM') {
            return false;
        }
        if (form.getAttribute('data-esh-form-shortcuts') === 'off') {
            return false;
        }
        if (document.body && document.body.classList.contains('page-login')) {
            return false;
        }
        var method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'post') {
            return false;
        }
        if (form.hasAttribute('data-esh-filter-form')) {
            return false;
        }
        if (!eshFormPrimarySubmit(form)) {
            return false;
        }
        if (form.getAttribute('data-esh-form-shortcuts') === 'on') {
            return true;
        }
        if (form.closest('.esh-stats-date-filter, .modal:not(.show)')) {
            return false;
        }
        return true;
    }

    function eshListEligibleRecordForms() {
        var out = [];
        document.querySelectorAll('form').forEach(function (form) {
            if (eshFormEligibleForShortcuts(form)) {
                out.push(form);
            }
        });
        return out;
    }

    function eshActiveRecordForm() {
        var active = document.activeElement;
        if (active && active.form && eshFormEligibleForShortcuts(active.form)) {
            return active.form;
        }
        var eligible = eshListEligibleRecordForms();
        if (eligible.length === 1) {
            return eligible[0];
        }
        return null;
    }

    function eshFormShortcutsBlocked() {
        if (document.querySelector('.modal.show')) {
            return true;
        }
        if (document.querySelector('.ts-wrapper.dropdown-active')) {
            return true;
        }
        if (document.querySelector('.dropdown-menu.show')) {
            return true;
        }
        return false;
    }

    function eshEnhanceFormShortcutHints(form) {
        if (!eshFormEligibleForShortcuts(form)) {
            return;
        }
        var submit = eshFormPrimarySubmit(form);
        var cancel = eshFormShortcutCancelEl(form);
        if (submit && submit.dataset.eshShortcutHint !== '1') {
            submit.dataset.eshShortcutHint = '1';
            var saveHint = 'Kısayol: Ctrl+S';
            submit.title = submit.title ? (submit.title + ' · ' + saveHint) : saveHint;
        }
        if (cancel && cancel.dataset.eshShortcutHint !== '1') {
            cancel.dataset.eshShortcutHint = '1';
            var cancelHint = 'Kısayol: Esc';
            cancel.title = cancel.title ? (cancel.title + ' · ' + cancelHint) : cancelHint;
        }
    }

    function eshSubmitRecordForm(form) {
        var btn = eshFormPrimarySubmit(form);
        if (!btn) {
            return;
        }
        if (btn.disabled) {
            if (window.toastr && typeof window.toastr.warning === 'function') {
                window.toastr.warning('Eksik veya hatalı alanları tamamlayın.', 'Kaydedilemedi');
            }
            return;
        }
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(btn);
        } else {
            btn.click();
        }
    }

    function eshBindFormShortcuts() {
        document.addEventListener('keydown', function (ev) {
            var saveKey = (ev.ctrlKey || ev.metaKey) && (ev.key === 's' || ev.key === 'S');
            var cancelKey = ev.key === 'Escape';
            if (!saveKey && !cancelKey) {
                return;
            }
            if (cancelKey && eshFormShortcutsBlocked()) {
                return;
            }
            var form = eshActiveRecordForm();
            if (!form) {
                return;
            }
            if (saveKey) {
                ev.preventDefault();
                eshSubmitRecordForm(form);
                return;
            }
            var cancel = eshFormShortcutCancelEl(form);
            if (!cancel) {
                return;
            }
            ev.preventDefault();
            cancel.click();
        });
    }

    function initFormShortcuts() {
        eshBindFormShortcuts();
        document.querySelectorAll('form').forEach(eshEnhanceFormShortcutHints);
        if (typeof MutationObserver !== 'undefined' && document.body) {
            var hintTimer = null;
            var observer = new MutationObserver(function (mutations) {
                var touched = false;
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node && node.nodeType === 1) {
                            touched = true;
                        }
                    });
                });
                if (!touched) {
                    return;
                }
                window.clearTimeout(hintTimer);
                hintTimer = window.setTimeout(function () {
                    document.querySelectorAll('form').forEach(eshEnhanceFormShortcutHints);
                }, 60);
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    }

    function init() {
        document.querySelectorAll('form').forEach(function (f) {
            var m = (f.getAttribute('method') || 'get').toLowerCase();
            if (m === 'post') {
                markRequiredLabels(f);
            }
        });

        document.querySelectorAll('form').forEach(bindForm);

        initFormShortcuts();

        if (window.jQuery) {
            window.jQuery(document).on('changeDate', '.datepicker', function (e) {
                var t = e.target;
                if (!t || !t.form || t.form.getAttribute('data-esh-submit-guard') === 'off') {
                    return;
                }
                refreshForm(t.form);
            });
            window.jQuery(document).on('change', 'select.esh-tomselect', function () {
                if (this.form && this.form.getAttribute('data-esh-submit-guard') !== 'off') {
                    refreshForm(this.form);
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
