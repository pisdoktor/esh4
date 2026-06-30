/* global jQuery, eshBuildListPdf, eshBindListExportButtons */
jQuery(function ($) {
    var tbody = document.getElementById('esh-visit-list-tbody');
    if (tbody) {
        var fetchUrl = tbody.getAttribute('data-esh-fetch-url');
        if (fetchUrl) {
            function escapeHtml(text) {
                var d = document.createElement('div');
                d.textContent = text || '';
                return d.innerHTML;
            }
            function showFetchError(message) {
                tbody.innerHTML = '<tr class="esh-visit-list-error-row"><td colspan="9" class="border-0 py-4 text-center text-danger">'
                    + escapeHtml(message) + '</td></tr>';
            }
            eshFetchListHtml(fetchUrl).then(function (data) { tbody.innerHTML = data.html;
                if (typeof window.eshApplyPatientMegaDropdownPrep === 'function') {
                    window.eshApplyPatientMegaDropdownPrep(tbody);
                }
            }).catch(function (err) {
                showFetchError(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
            });
        }
    }

    var $from = $('#visit-filter-date-from');
    var $to = $('#visit-filter-date-to');
    var $visitFilterCollapse = $('#visit-filter-collapse');
    var $visitFilterToggleText = $('#visit-filter-toggle .js-filter-toggle-text');

    if ($from.length && $to.length && $.fn.datepicker) {
        $from.on('changeDate', function (e) {
            $to.datepicker('setStartDate', e.date);
        });
        $to.on('changeDate', function (e) {
            $from.datepicker('setEndDate', e.date);
        });
    }
    if ($visitFilterCollapse.length && $visitFilterToggleText.length) {
        $visitFilterCollapse.on('shown.bs.collapse', function () {
            $visitFilterToggleText.text('Filtreleri Gizle');
        });
        $visitFilterCollapse.on('hidden.bs.collapse', function () {
            $visitFilterToggleText.text('Filtreleri Göster');
        });
    }

    var pdfBtn = document.getElementById('esh-visit-index-pdf-btn');
    var excelBtn = document.getElementById('esh-visit-index-excel-btn');
    var root = document.getElementById('esh-visit-index-root');
    if (!pdfBtn || !root) {
        return;
    }

    eshBindListExportButtons({
        pdfBtn: pdfBtn,
        excelBtn: excelBtn,
        getUrl: function () {
            return root.getAttribute('data-esh-pdf-url') || '';
        },
        onPdf: function (data) {
            if (typeof eshBuildListPdf !== 'function') {
                throw new Error('PDF modülü yüklenemedi; sayfayı yenileyin.');
            }
            eshBuildListPdf(data, {
                title: 'AKTİF İZLEM LİSTESİ',
                headerLeft: 'ESH — Aktif izlem listesi',
                widths: ['*', 62, 72, 48, 40, 44, '*', 90],
            });
        },
    });
});
