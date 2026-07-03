jQuery(function ($) {
    if (typeof window.eshPrepareDatepickerInputs === 'function') {
        window.eshPrepareDatepickerInputs(document);
    }
    if ($.fn.datepicker) {
        $('.datepicker').datepicker({ format: 'dd-mm-yyyy', language: 'tr', autoclose: true, todayHighlight: true });
    }

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function fetchTbody(tbodyId, colspan) {
        var tbody = document.getElementById(tbodyId);
        if (!tbody) {
            return;
        }
        var url = tbody.getAttribute('data-esh-fetch-url');
        if (!url) {
            return;
        }

        eshFetchListHtml(url).then(function (data) { tbody.innerHTML = typeof data.html === 'string' ? data.html : '';
        }).catch(function (err) {
            tbody.innerHTML = '<tr><td colspan="' + colspan + '" class="text-center text-danger py-3 small">' + escapeHtml(err && err.message ? err.message : 'Yüklenemedi') + '</td></tr>';
        });
    }

    fetchTbody('esh-nobet-mine-izin-tbody', 3);
    fetchTbody('esh-nobet-mine-istek-tbody', 3);
});
