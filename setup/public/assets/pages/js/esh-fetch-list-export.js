(function (global) {

    'use strict';



    /**

     * @param {string} url

     * @returns {Promise<object>}

     */

    function fetchListExportData(url) {

        if (typeof global.eshFetchJson !== 'function') {

            return Promise.reject(new Error('esh-fetch-json.js yüklenemedi; sayfayı yenileyin.'));

        }

        return global.eshFetchJson(url, {

            method: 'GET',

            credentials: 'same-origin',

        }).then(function (data) {

            if (!data || data.ok !== true) {

                throw new Error(data && data.error ? String(data.error) : 'Liste yanıtı geçersiz.');

            }

            return data;

        });

    }



    /**

     * @param {HTMLButtonElement} btn

     * @param {string} url

     * @param {{ onSuccess?: function, errorMessage?: string, spinnerLabel?: string }} handlers

     */

    function runExportClick(btn, url, handlers) {

        handlers = handlers || {};

        if (!btn || !url || btn.disabled) {

            return;

        }

        var prevHtml = btn.innerHTML;

        btn.disabled = true;

        btn.innerHTML =

            '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' +

            (handlers.spinnerLabel || '…');



        fetchListExportData(url)

            .then(function (data) {

                if (typeof handlers.onSuccess === 'function') {

                    handlers.onSuccess(data);

                }

            })

            .catch(function (err) {

                var fallback = handlers.errorMessage || 'Dışa aktarım oluşturulamadı.';

                var msg = err && err.message ? err.message : fallback;

                if (typeof global.eshNotifyAppError === 'function') {

                    global.eshNotifyAppError(msg, err && err.status);

                } else {

                    alert(msg);

                }

            })

            .finally(function () {

                btn.disabled = false;

                btn.innerHTML = prevHtml;

            });

    }



    /**

     * @param {{ pdfBtn?: HTMLElement, excelBtn?: HTMLElement, getUrl?: function|string, onPdf?: function, onExcel?: function }} opts

     */

    function bindListExportButtons(opts) {

        opts = opts || {};

        var pdfBtn = opts.pdfBtn;

        var excelBtn = opts.excelBtn;

        var getUrl = opts.getUrl;

        if (typeof getUrl !== 'function') {

            var fixedUrl = getUrl || '';

            getUrl = function () {

                return fixedUrl;

            };

        }



        function attach(btn, format) {

            if (!btn) {

                return;

            }

            btn.addEventListener('click', function () {

                var url = getUrl();

                if (!url) {

                    return;

                }

                var isExcel = format === 'excel';

                runExportClick(btn, url, {

                    spinnerLabel: isExcel ? 'Excel…' : 'PDF…',

                    errorMessage: isExcel ? 'Excel oluşturulamadı.' : 'PDF oluşturulamadı.',

                    onSuccess: function (data) {

                        if (isExcel) {

                            if (typeof opts.onExcel === 'function') {

                                opts.onExcel(data);

                                return;

                            }

                            if (typeof global.eshBuildListExcel !== 'function') {

                                throw new Error('Excel modülü yüklenemedi; sayfayı yenileyin.');

                            }

                            global.eshBuildListExcel(data);

                            return;

                        }

                        if (typeof opts.onPdf !== 'function') {

                            throw new Error('PDF modülü yüklenemedi; sayfayı yenileyin.');

                        }

                        opts.onPdf(data);

                    },

                });

            });

        }



        attach(pdfBtn, 'pdf');

        attach(excelBtn, 'excel');

    }



    function enableExportButtons(pdfBtn, excelBtn, enabled) {

        var on = enabled !== false;

        if (pdfBtn) {

            pdfBtn.disabled = !on;

        }

        if (excelBtn) {

            excelBtn.disabled = !on;

        }

    }



    global.eshFetchListExportData = fetchListExportData;

    global.eshRunListExportClick = runExportClick;

    global.eshBindListExportButtons = bindListExportButtons;

    global.eshEnableListExportButtons = enableExportButtons;

})(typeof window !== 'undefined' ? window : this);

