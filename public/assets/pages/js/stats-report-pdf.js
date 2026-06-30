/* global eshBuildListPdf, eshBuildListExcel, eshUrl */

(function () {
    'use strict';

    var FILTER_KEYS = ['months', 'date_from', 'date_to', 'tab', 'device', 'metric', 'id'];

    function pathSegmentsAfterPublic() {
        try {
            var path = window.location.pathname.replace(/\\/g, '/');
            var parts = path.split('/').filter(function (p) {
                return p !== '' && p !== 'index.php';
            });
            var publicIdx = -1;
            for (var i = 0; i < parts.length; i++) {
                if (parts[i].toLowerCase() === 'public') {
                    publicIdx = i;
                }
            }
            if (publicIdx >= 0) {
                return parts.slice(publicIdx + 1);
            }
            return parts;
        } catch (e) {
            return [];
        }
    }

    function pageReportAction() {
        if (window.ESH_PAGE && window.ESH_PAGE.statsReportAction) {
            return String(window.ESH_PAGE.statsReportAction);
        }
        try {
            var qs = new URLSearchParams(window.location.search);
            var fromQuery = qs.get('action') || '';
            if (fromQuery && fromQuery !== 'reportPdfData') {
                return fromQuery;
            }
            var segments = pathSegmentsAfterPublic();
            if (segments.length >= 2) {
                var ctrl = segments[0];
                if (ctrl.toLowerCase() === 'stats') {
                    var action = segments[1];
                    if (action && action !== 'index' && action !== 'reportPdfData') {
                        return action;
                    }
                }
            }
        } catch (e) {
            return '';
        }
        return '';
    }

    function filterParamsFromPage() {
        var out = {};
        try {
            var src = new URLSearchParams(window.location.search);
            FILTER_KEYS.forEach(function (key) {
                var val = src.get(key);
                if (val !== null && val !== '') {
                    out[key] = val;
                }
            });
        } catch (e) {
            /* ignore */
        }
        return out;
    }

    function buildPdfUrl(reportAction, block) {
        var params = filterParamsFromPage();
        params.report = reportAction;
        params.block = block;
        if (typeof eshUrl === 'function') {
            return eshUrl('Stats', 'reportPdfData', params);
        }
        var base = String(window.ESH_PUBLIC_WEB || '/public/').replace(/\/?$/, '/');
        var legacy = new URLSearchParams();
        legacy.set('controller', 'Stats');
        legacy.set('action', 'reportPdfData');
        Object.keys(params).forEach(function (key) {
            legacy.set(key, String(params[key]));
        });
        return base + 'index.php?' + legacy.toString();
    }

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

    function fetchReportPayload(report, block) {
        var pdfUrl = buildPdfUrl(report, block);
        return fetch(pdfUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        }).then(function (res) {
            if (!res.ok) {
                return parseJsonResponse(res).then(function (data) {
                    var msg =
                        data && data.error
                            ? String(data.error)
                            : res.status === 401
                              ? 'Oturum süresi dolmuş olabilir; sayfayı yenileyin.'
                              : 'Rapor verisi alınamadı (' + res.status + ').';
                    throw new Error(msg);
                });
            }
            return parseJsonResponse(res);
        }).then(function (data) {
            if (!data || !data.ok) {
                throw new Error(data && data.error ? String(data.error) : 'Rapor yanıtı geçersiz.');
            }
            return data;
        });
    }

    function onExportButtonClick(ev, format) {
        var selector =
            format === 'excel' ? '.esh-stats-block-excel-btn' : '.esh-stats-block-pdf-btn';
        var btn = ev.target.closest(selector);
        if (!btn) {
            return;
        }
        ev.preventDefault();
        ev.stopPropagation();

        var block = btn.getAttribute('data-esh-block') || '';
        var report = pageReportAction();
        if (!block || !report) {
            return;
        }
        if (btn.disabled) {
            return;
        }

        var isExcel = format === 'excel';
        var prevHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML =
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

        fetchReportPayload(report, block)
            .then(function (data) {
                if (isExcel) {
                    if (typeof eshBuildListExcel !== 'function') {
                        throw new Error('Excel modülü yüklenemedi; sayfayı yenileyin.');
                    }
                    eshBuildListExcel(data);
                    return;
                }
                if (typeof eshBuildListPdf !== 'function') {
                    throw new Error('PDF modülü yüklenemedi; sayfayı yenileyin.');
                }
                var colCount = (data.headers && data.headers.length) || 2;
                var widths = data.widths || Array(colCount).fill('*');
                eshBuildListPdf(data, {
                    title: data.title || 'İSTATİSTİK',
                    headerLeft: data.headerLeft || 'ESH — İstatistik',
                    widths: widths,
                });
            })
            .catch(function (err) {
                var fallback = isExcel ? 'Excel oluşturulamadı.' : 'PDF oluşturulamadı.';
                alert(err && err.message ? err.message : fallback);
            })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = prevHtml;
            });
    }

    function onDocumentClick(ev) {
        if (ev.target.closest('.esh-stats-block-pdf-btn')) {
            onExportButtonClick(ev, 'pdf');
            return;
        }
        if (ev.target.closest('.esh-stats-block-excel-btn')) {
            onExportButtonClick(ev, 'excel');
        }
    }

    function bind() {
        document.addEventListener('click', onDocumentClick);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})();
