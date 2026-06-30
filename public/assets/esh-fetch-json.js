(function (global) {
    'use strict';

    var DEFAULT_FORBIDDEN = 'Bu alana erişim yetkiniz bulunmamaktadır!';
    var DEFAULT_SESSION = 'Oturum süresi dolmuş olabilir; sayfayı yenileyin.';

    function parseJsonResponse(res) {
        var ct = (res.headers.get('content-type') || '').toLowerCase();
        if (ct.indexOf('application/json') === -1) {
            return res.text().then(function (body) {
                var snippet = (body || '').replace(/\s+/g, ' ').trim().slice(0, 120);
                throw new Error(
                    'Sunucu JSON yerine HTML döndü. Oturum veya adres yolunu kontrol edin.' +
                        (snippet ? ' (' + snippet + '…)' : '')
                );
            });
        }
        return res.json();
    }

    function httpErrorMessage(res, data, fallback) {
        if (data && (data.error || data.mesaj)) {
            return String(data.error || data.mesaj);
        }
        if (res.status === 401) {
            return DEFAULT_SESSION;
        }
        if (res.status === 403) {
            return DEFAULT_FORBIDDEN;
        }
        if (fallback) {
            return fallback;
        }
        return 'İstek başarısız (' + res.status + ').';
    }

    function notifyAppError(message, status) {
        if (!message) {
            return;
        }
        if (typeof toastr !== 'undefined' && toastr && typeof toastr.error === 'function') {
            toastr.error(message);
            return;
        }
        if (status === 403 || status === 401) {
            // Toastr henüz yüklenmediyse en azından konsola yaz
            if (typeof console !== 'undefined' && console.warn) {
                console.warn('[ESH]', message);
            }
        }
    }

    /**
     * JSON API fetch — 401/403 gövdesindeki error/mesaj alanını okur, toastr gösterir.
     * @param {string} url
     * @param {RequestInit} [init]
     * @returns {Promise<object>}
     */
    function eshFetchJson(url, init) {
        init = init || {};
        var headers = Object.assign(
            {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            init.headers || {}
        );

        return fetch(url, Object.assign({}, init, { headers: headers })).then(function (res) {
            if (!res.ok) {
                return parseJsonResponse(res)
                    .then(function (data) {
                        var msg = httpErrorMessage(res, data, null);
                        if (res.status === 403 || res.status === 401) {
                            notifyAppError(msg, res.status);
                        }
                        var err = new Error(msg);
                        err.status = res.status;
                        throw err;
                    })
                    .catch(function (err) {
                        if (err && err.status) {
                            throw err;
                        }
                        var msg = httpErrorMessage(res, null, null);
                        if (res.status === 403 || res.status === 401) {
                            notifyAppError(msg, res.status);
                        }
                        var wrapped = new Error(msg);
                        wrapped.status = res.status;
                        throw wrapped;
                    });
            }
            return parseJsonResponse(res);
        });
    }

    /**
     * Liste satırı XHR yanıtı: ok:true ve html string zorunlu.
     * @param {string} url
     * @param {RequestInit} [init]
     * @returns {Promise<{ok:boolean, html:string}>}
     */
    function eshFetchListHtml(url, init) {
        return eshFetchJson(url, init).then(function (data) {
            if (!data || data.ok !== true) {
                var msg = data && data.error ? String(data.error) : 'Liste yanıtı geçersiz.';
                notifyAppError(msg);
                throw new Error(msg);
            }
            if (typeof data.html !== 'string') {
                var invalid = 'Liste yanıtı geçersiz.';
                notifyAppError(invalid);
                throw new Error(invalid);
            }
            return data;
        });
    }

    function escapeHtml(text) {
        var d = global.document && global.document.createElement('div');
        if (!d) {
            return String(text || '');
        }
        d.textContent = text || '';
        return d.innerHTML;
    }

    function detectColspan(tbody, fallback) {
        if (!tbody) {
            return fallback || 6;
        }
        var cell = tbody.querySelector('tr td[colspan]');
        if (cell) {
            var n = parseInt(cell.getAttribute('colspan'), 10);
            if (n > 0) {
                return n;
            }
        }
        return fallback || 6;
    }

    /**
     * tbody[data-esh-fetch-url] için standart liste satırı yükleyici.
     * @param {{ tbody?: HTMLElement, url?: string, colspan?: number, errorRowClass?: string, fetchInit?: RequestInit, onSuccess?: function }} opts
     * @returns {Promise<void>}
     */
    function eshLoadListTbody(opts) {
        opts = opts || {};
        var tbody = opts.tbody;
        if (!tbody) {
            return Promise.resolve();
        }
        var url = opts.url || tbody.getAttribute('data-esh-fetch-url');
        if (!url) {
            return Promise.resolve();
        }
        var colspan = opts.colspan || detectColspan(tbody, 6);
        var rowClass = opts.errorRowClass || 'esh-list-fetch-error-row';

        function showError(message) {
            tbody.innerHTML =
                '<tr class="' +
                rowClass +
                '"><td colspan="' +
                colspan +
                '" class="border-0 py-4 text-center text-danger">' +
                escapeHtml(message) +
                '</td></tr>';
        }

        return eshFetchListHtml(url, opts.fetchInit)
            .then(function (data) {
                tbody.innerHTML = data.html;
                if (typeof global.eshApplyPatientMegaDropdownPrep === 'function') {
                    global.eshApplyPatientMegaDropdownPrep(tbody);
                }
                if (typeof opts.onSuccess === 'function') {
                    opts.onSuccess(data);
                }
            })
            .catch(function (err) {
                showError(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
            });
    }

    global.eshNotifyAppError = notifyAppError;
    global.eshFetchJson = eshFetchJson;
    global.eshFetchListHtml = eshFetchListHtml;
    global.eshLoadListTbody = eshLoadListTbody;

    if (global.jQuery) {
        global.jQuery(document).ajaxError(function (_event, jqXHR) {
            if (!jqXHR || (jqXHR.status !== 403 && jqXHR.status !== 401)) {
                return;
            }
            var data = jqXHR.responseJSON;
            var msg = DEFAULT_FORBIDDEN;
            if (data && (data.error || data.mesaj)) {
                msg = String(data.error || data.mesaj);
            } else if (jqXHR.status === 401) {
                msg = DEFAULT_SESSION;
            }
            notifyAppError(msg, jqXHR.status);
        });
    }
})(typeof window !== 'undefined' ? window : this);
