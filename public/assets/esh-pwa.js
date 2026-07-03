/* ESH PWA — service worker kaydı */
(function () {
    'use strict';
    if (!('serviceWorker' in navigator)) {
        return;
    }
    window.addEventListener('load', function () {
        var swUrl = (window.ESH_PWA && window.ESH_PWA.swUrl) ? window.ESH_PWA.swUrl : '/public/sw.js';
        navigator.serviceWorker.register(swUrl, { scope: '/public/' }).catch(function () {
            /* sessiz */
        });
    });

    function updateBanner() {
        var el = document.getElementById('esh-offline-banner');
        if (!el) {
            return;
        }
        if (navigator.onLine) {
            el.classList.add('d-none');
            el.setAttribute('aria-hidden', 'true');
        } else {
            el.classList.remove('d-none');
            el.setAttribute('aria-hidden', 'false');
        }
    }

    window.addEventListener('online', updateBanner);
    window.addEventListener('offline', updateBanner);
    document.addEventListener('DOMContentLoaded', updateBanner);
})();
