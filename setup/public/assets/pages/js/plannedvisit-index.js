/* global jQuery, eshBuildListPdf, eshBindListExportButtons */
jQuery(function ($) {
    var tbody = document.getElementById('esh-planned-visit-list-tbody');
    if (tbody) {
        var fetchUrl = tbody.getAttribute('data-esh-fetch-url');
        if (fetchUrl) {
            function escapeHtml(text) {
                var d = document.createElement('div');
                d.textContent = text || '';
                return d.innerHTML;
            }
            function showFetchError(message) {
                tbody.innerHTML = '<tr class="esh-planned-visit-list-error-row"><td colspan="10" class="border-0 py-4 text-center text-danger">'
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

    var $from = $('#plan-filter-date-from');
    var $to = $('#plan-filter-date-to');
    var $planFilterCollapse = $('#plan-filter-collapse');
    var $planFilterToggleText = $('#plan-filter-toggle .js-filter-toggle-text');

    if ($from.length && $to.length && $.fn.datepicker) {
        $from.on('changeDate', function (e) {
            $to.datepicker('setStartDate', e.date);
        });
        $to.on('changeDate', function (e) {
            $from.datepicker('setEndDate', e.date);
        });
    }
    if ($planFilterCollapse.length && $planFilterToggleText.length) {
        $planFilterCollapse.on('shown.bs.collapse', function () {
            $planFilterToggleText.text('Filtreleri Gizle');
        });
        $planFilterCollapse.on('hidden.bs.collapse', function () {
            $planFilterToggleText.text('Filtreleri Göster');
        });
    }

    var pdfBtn = document.getElementById('esh-planned-visit-index-pdf-btn');
    var excelBtn = document.getElementById('esh-planned-visit-index-excel-btn');
    var root = document.getElementById('esh-planned-visit-index-root');
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
                title: 'PLANLI İZLEM LİSTESİ',
                headerLeft: 'ESH — Planlı izlem listesi',
                widths: ['*', 62, 72, 48, 40, 44, '*', 80, 48],
            });
        },
    });
});
