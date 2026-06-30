/**
 * CSRF: POST formlarına token enjekte eder; jQuery AJAX ve fetch() isteklerine başlık ekler.
 */
(function () {
    'use strict';

    function tokenFromMeta() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? String(meta.getAttribute('content') || '') : '';
    }

    function injectFormTokens() {
        var token = tokenFromMeta();
        if (!token) {
            return;
        }
        document.querySelectorAll('form').forEach(function (form) {
            var method = (form.getAttribute('method') || 'get').toLowerCase();
            if (method !== 'post') {
                return;
            }
            if (form.querySelector('input[name="csrf_token"]')) {
                return;
            }
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'csrf_token';
            input.value = token;
            form.appendChild(input);
        });
    }

    function patchFetch() {
        if (typeof window.fetch !== 'function' || window.fetch.__eshCsrfPatched) {
            return;
        }
        var nativeFetch = window.fetch.bind(window);
        window.fetch = function (input, init) {
            init = init || {};
            var method = String(
                init.method
                || (input && typeof input === 'object' && input.method)
                || 'GET'
            ).toUpperCase();
            if (method === 'POST' || method === 'PUT' || method === 'PATCH' || method === 'DELETE') {
                var token = tokenFromMeta();
                if (token) {
                    var headers = new Headers(init.headers || (input && typeof input === 'object' && input.headers) || undefined);
                    if (!headers.has('X-CSRF-Token')) {
                        headers.set('X-CSRF-Token', token);
                    }
                    init.headers = headers;
                }
            }
            return nativeFetch(input, init);
        };
        window.fetch.__eshCsrfPatched = true;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectFormTokens);
    } else {
        injectFormTokens();
    }
    patchFetch();

    if (typeof window.jQuery !== 'undefined') {
        window.jQuery(function ($) {
            $.ajaxSetup({
                beforeSend: function (xhr, settings) {
                    var type = (settings.type || 'GET').toUpperCase();
                    if (type === 'POST' || type === 'PUT' || type === 'PATCH' || type === 'DELETE') {
                        var liveToken = tokenFromMeta();
                        if (liveToken) {
                            xhr.setRequestHeader('X-CSRF-Token', liveToken);
                        }
                    }
                }
            });
        });
    }
})();
