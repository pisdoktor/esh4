(function () {
    var root = document.getElementById('esh-stok-hareketler-root');
    if (!root) {
        return;
    }
    var pdfBtn = document.getElementById('esh-stok-hareketler-pdf-btn');
    var excelBtn = document.getElementById('esh-stok-hareketler-excel-btn');
    var pdfUrl = root.getAttribute('data-esh-pdf-url') || '';

    if (pdfBtn && pdfUrl && typeof eshBindListExportButtons === 'function') {
        if (typeof eshEnableListExportButtons === 'function') {
            eshEnableListExportButtons(pdfBtn, excelBtn, true);
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
                    title: 'STOK HAREKETLERİ',
                    headerLeft: 'ESH — Stok hareketleri',
                    widths: [52, 40, '*', 44, 36, '*', 48, 48, '*'],
                    defaultFontSize: 7,
                });
            },
        });
    }
})();
