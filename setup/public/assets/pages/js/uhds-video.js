(function () {
    'use strict';

    var cfg = window.__eshUhdsVideo || {};
    var container = document.getElementById('esh-uhds-jitsi');
    if (!container || !cfg.configUrl) {
        return;
    }

    function loadScript(src) {
        return new Promise(function (resolve, reject) {
            if (!src) {
                reject(new Error('script'));
                return;
            }
            var existing = document.querySelector('script[data-esh-jitsi="1"]');
            if (existing) {
                resolve();
                return;
            }
            var s = document.createElement('script');
            s.src = src;
            s.async = true;
            s.setAttribute('data-esh-jitsi', '1');
            s.onload = function () { resolve(); };
            s.onerror = function () { reject(new Error('load')); };
            document.head.appendChild(s);
        });
    }

    function startMeeting(data) {
        if (typeof JitsiMeetExternalAPI === 'undefined') {
            container.innerHTML = '<p class="text-white p-3 small">Video bileşeni yüklenemedi.</p>';
            return;
        }
        var domain = data.domain || 'meet.jit.si';
        var options = {
            roomName: data.roomName,
            parentNode: container,
            width: '100%',
            height: Math.max(360, container.clientHeight || 420),
            userInfo: { displayName: data.displayName || 'Katılımcı' },
            configOverwrite: data.config || {},
            interfaceConfigOverwrite: data.interfaceConfig || {}
        };
        var api = new JitsiMeetExternalAPI(domain, options);
        window.__eshUhdsJitsiApi = api;
    }

    fetch(cfg.configUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data || !data.ok) {
                throw new Error('config');
            }
            return loadScript(cfg.jitsiScriptUrl).then(function () { startMeeting(data); });
        })
        .catch(function () {
            container.innerHTML = '<p class="text-white p-3 small">Görüntülü görüşme başlatılamadı.</p>';
        });

    var copyBtn = document.getElementById('esh-uhds-copy-link');
    var linkInput = document.getElementById('esh-uhds-patient-link');
    if (copyBtn && linkInput) {
        copyBtn.addEventListener('click', function () {
            linkInput.select();
            linkInput.setSelectionRange(0, 99999);
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(linkInput.value);
            } else {
                document.execCommand('copy');
            }
            copyBtn.classList.add('btn-success');
            copyBtn.classList.remove('btn-outline-primary');
            setTimeout(function () {
                copyBtn.classList.remove('btn-success');
                copyBtn.classList.add('btn-outline-primary');
            }, 1200);
        });
    }

    var completeForm = document.getElementById('esh-uhds-complete-form');
    if (completeForm && cfg.completeUrl && !cfg.isPatientJoin) {
        completeForm.addEventListener('submit', function (ev) {
            if (!window.fetch) {
                return;
            }
            ev.preventDefault();
            var fd = new FormData(completeForm);
            fetch(cfg.completeUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                body: fd
            })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (!res || !res.ok) {
                        alert('Kayıt tamamlanamadı.');
                        return;
                    }
                    if (window.__eshUhdsJitsiApi && typeof window.__eshUhdsJitsiApi.dispose === 'function') {
                        window.__eshUhdsJitsiApi.dispose();
                    }
                    if (cfg.autoPromptVisit && res.visitCreateUrl) {
                        window.location.href = res.visitCreateUrl;
                        return;
                    }
                    var indexUrl = completeForm.getAttribute('data-index-url');
                    if (indexUrl) {
                        window.location.href = indexUrl;
                    }
                })
                .catch(function () {
                    completeForm.submit();
                });
        });
    }
})();
