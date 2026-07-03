/* global bootstrap, eshBuildListPdf, eshBindListExportButtons, eshEnableListExportButtons */
if (typeof window.eshPrepareDatepickerInputs === 'function') {
    window.eshPrepareDatepickerInputs(document);
}
document.querySelectorAll('.datepicker').forEach(function (el) {
    if (window.jQuery && jQuery.fn.datepicker) {
        jQuery(el).datepicker({ format: 'dd-mm-yyyy', language: 'tr', autoclose: true, todayHighlight: true });
    }
});

(function () {
    var tbody = document.getElementById('esh-unified-list-tbody');
    if (!tbody) {
        return;
    }
    var url = tbody.getAttribute('data-esh-fetch-url');
    if (!url || url === '') {
        return;
    }

    var pdfBtn = document.getElementById('esh-unified-pdf-btn');
    var excelBtn = document.getElementById('esh-unified-excel-btn');
    var pdfUrl = tbody.getAttribute('data-esh-pdf-url') || '';

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function showError(message) {
        tbody.innerHTML = '<tr class="esh-unified-list-error-row"><td colspan="11" class="border-0 py-4 text-center text-danger">'
            + escapeHtml(message) + '</td></tr>';
    }

    function enableExportButtons() {
        if (pdfUrl && pdfBtn && typeof eshEnableListExportButtons === 'function') {
            eshEnableListExportButtons(pdfBtn, excelBtn, true);
        }
    }

    function downloadPdf(payload) {
        if (typeof eshBuildListPdf !== 'function') {
            alert('PDF modülü yüklenemedi. Sayfayı yenileyin.');
            return;
        }
        eshBuildListPdf(payload, {
            title: 'HASTA LİSTESİ',
            headerLeft: 'ESH — Hasta listesi',
            widths: [42, '*', 62, 78, 62, 44, 52, 44, 44, 44, 38],
        });
    }

    if (pdfBtn && pdfUrl && typeof eshBindListExportButtons === 'function') {
        eshBindListExportButtons({
            pdfBtn: pdfBtn,
            excelBtn: excelBtn,
            getUrl: function () {
                return pdfUrl;
            },
            onPdf: downloadPdf,
        });
    }

    eshFetchListHtml(url).then(function (data) {
        tbody.innerHTML = data.html;

        if (typeof window.eshApplyPatientMegaDropdownPrep === 'function') {
            window.eshApplyPatientMegaDropdownPrep(tbody);
        }

        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            tbody.querySelectorAll('[data-bs-toggle="tooltip"]:not(.esh-barthel-tt-trigger)').forEach(function (el) {
                try {
                    new bootstrap.Tooltip(el);
                } catch (e) { /* yoksay */ }
            });
        }

        enableExportButtons();
    }).catch(function (err) {
        showError(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
    });
})();
