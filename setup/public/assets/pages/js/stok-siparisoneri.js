(function () {
    var root = document.getElementById('esh-stok-siparis-root');
    if (!root) {
        return;
    }
    var pdfBtn = document.getElementById('esh-stok-siparis-pdf-btn');
    var excelBtn = document.getElementById('esh-stok-siparis-excel-btn');
    var pdfUrl = root.getAttribute('data-esh-pdf-url') || '';

    if (pdfBtn && pdfUrl && typeof eshBindListExportButtons === 'function') {
        if (typeof eshEnableListExportButtons === 'function') {
            eshEnableListExportButtons(pdfBtn, excelBtn, !pdfBtn.disabled);
        }
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
                    title: 'SİPARİŞ ÖNERİSİ',
                    headerLeft: 'ESH — Stok sipariş önerisi',
                    widths: [44, '*', 52, 44, 40, 44, 48, 56],
                    defaultFontSize: 7.5,
                });
            },
        });
    }
})();
