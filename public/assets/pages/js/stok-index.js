(function () {
    var root = document.getElementById('esh-stok-index-root');
    var tbody = document.getElementById('esh-stok-list-tbody');
    if (!tbody) {
        return;
    }
    var url = tbody.getAttribute('data-esh-fetch-url');
    if (!url) {
        return;
    }

    var pdfBtn = document.getElementById('esh-stok-index-pdf-btn');
    var excelBtn = document.getElementById('esh-stok-index-excel-btn');
    var pdfUrl = root ? (root.getAttribute('data-esh-pdf-url') || '') : '';

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function showError(message) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">'
            + escapeHtml(message) + '</td></tr>';
    }

    function enableExportButtons() {
        if (pdfUrl && pdfBtn && typeof eshEnableListExportButtons === 'function') {
            eshEnableListExportButtons(pdfBtn, excelBtn, true);
        }
    }

    if (pdfBtn && pdfUrl && typeof eshBindListExportButtons === 'function') {
        eshBindListExportButtons({
            pdfBtn: pdfBtn,
            excelBtn: excelBtn,
            getUrl: function () {
                return pdfUrl;
            },
            onPdf: function (data) {
                if (typeof eshBuildListPdf !== 'function') {
                    throw new Error('PDF modülü yüklenemedi; sayfayı yenileyin.');
                }
                eshBuildListPdf(data, {
                    title: 'STOK DURUMU',
                    headerLeft: 'ESH — Stok durumu',
                    widths: [48, '*', 52, 40, 48, 40, 44],
                    defaultFontSize: 7.5,
                });
            },
        });
    }

    function loadList() {
        if (typeof eshFetchListHtml === 'function') {
            eshFetchListHtml(url).then(function (data) {
                tbody.innerHTML = data.html;
                enableExportButtons();
            }).catch(function (err) {
                showError(err && err.message ? err.message : 'Liste yüklenemedi.');
            });
        } else {
            fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (!res || !res.ok) {
                        throw new Error((res && res.error) || 'Liste yüklenemedi.');
                    }
                    tbody.innerHTML = res.html || '';
                    enableExportButtons();
                })
                .catch(function (err) {
                    showError(err.message || 'Liste yüklenemedi.');
                });
        }
    }

    loadList();
})();
