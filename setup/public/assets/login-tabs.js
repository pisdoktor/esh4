/*
 * Giriş ekranı sekmeleri (Normal giriş / E-imza ile giriş).
 * Bootstrap JS bundle giriş sayfasında yüklenmediğinden, sekme geçişi
 * burada bağımsız (vanilla) olarak yönetilir. Her sekme grubu
 * `[data-login-tabs]` ile işaretlenir; sekme düğmeleri `[data-login-tab]`
 * ve `data-login-target="#pane-id"`, içerik panelleri ise `[data-login-pane]`
 * taşır. Bootstrap'in `.tab-pane/.fade/.show/.active` görünüm sınıfları kullanılır.
 */
(function () {
    'use strict';

    function tabsIn(group) {
        return Array.prototype.slice.call(group.querySelectorAll('[data-login-tab]'));
    }

    function activate(group, btn, focusPane) {
        var targetSel = btn.getAttribute('data-login-target');
        var target = targetSel ? group.querySelector(targetSel) : null;

        tabsIn(group).forEach(function (b) {
            var on = b === btn;
            b.classList.toggle('active', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
            b.setAttribute('tabindex', on ? '0' : '-1');
        });

        group.querySelectorAll('[data-login-pane]').forEach(function (pane) {
            var on = pane === target;
            pane.classList.toggle('active', on);
            pane.classList.toggle('show', on);
        });

        if (focusPane && target) {
            var field = target.querySelector('input:not([type="hidden"]):not([disabled]), button:not([disabled])');
            if (field) {
                field.focus();
            }
        }
    }

    function bind(group) {
        var tabs = tabsIn(group);
        tabs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activate(group, btn, true);
            });
            btn.addEventListener('keydown', function (e) {
                if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft' &&
                    e.key !== 'Home' && e.key !== 'End') {
                    return;
                }
                e.preventDefault();
                var idx = tabs.indexOf(btn);
                var next;
                if (e.key === 'Home') {
                    next = tabs[0];
                } else if (e.key === 'End') {
                    next = tabs[tabs.length - 1];
                } else if (e.key === 'ArrowRight') {
                    next = tabs[(idx + 1) % tabs.length];
                } else {
                    next = tabs[(idx - 1 + tabs.length) % tabs.length];
                }
                next.focus();
                activate(group, next, true);
            });
        });
    }

    function init() {
        document.querySelectorAll('[data-login-tabs]').forEach(bind);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
