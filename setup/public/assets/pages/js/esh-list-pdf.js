/* global pdfMake */
(function (global) {
    'use strict';

    function cellText(value) {
        return { text: value == null ? '' : String(value) };
    }

    /**
     * @param {object} payload — { headers, rows, meta, filename }
     * @param {object} options — title, headerLeft, widths, defaultFontSize
     */
    function buildListPdf(payload, options) {
        if (typeof pdfMake === 'undefined') {
            alert('PDF kütüphanesi yüklenemedi. Sayfayı yenileyin.');
            return;
        }

        options = options || {};
        var headers = payload.headers || [];
        var rows = payload.rows || [];
        var meta = payload.meta || {};
        var colCount = headers.length || 1;
        var widths = options.widths || Array(colCount).fill('*');

        var body = [headers.map(function (h) {
            return { text: h, style: 'tableHeader' };
        })];

        if (rows.length === 0) {
            var emptyRow = [
                { text: 'Bu sayfada kayıt yok.', colSpan: colCount, alignment: 'center', italics: true },
            ];
            for (var p = 1; p < colCount; p++) {
                emptyRow.push({});
            }
            body.push(emptyRow);
        } else {
            rows.forEach(function (row) {
                body.push(row.map(cellText));
            });
        }

        var subtitle = meta.filterSummary ? String(meta.filterSummary) : '';
        var generatedAt = meta.generatedAt ? String(meta.generatedAt) : '';
        var title = options.title || 'LİSTE';
        var headerLeft = options.headerLeft || 'ESH';
        var fontSize = options.defaultFontSize != null ? options.defaultFontSize : 7.5;

        var content = [
            { text: title, style: 'mainTitle' },
        ];
        if (subtitle !== '') {
            content.push({ text: subtitle, style: 'subTitle', margin: [0, 0, 0, 12] });
        }
        content.push({
            table: {
                headerRows: 1,
                dontBreakRows: true,
                widths: widths,
                body: body,
            },
            layout: {
                hLineWidth: function (i, node) {
                    return (i === 0 || i === node.table.body.length) ? 1.2 : 0.4;
                },
                vLineWidth: function () {
                    return 0.4;
                },
                hLineColor: function () {
                    return '#bdc3c7';
                },
                vLineColor: function () {
                    return '#bdc3c7';
                },
            },
        });

        var docDefinition = {
            pageSize: 'A4',
            pageOrientation: 'landscape',
            pageMargins: [28, 52, 28, 36],
            header: function () {
                return {
                    columns: [
                        { text: headerLeft, style: 'pageHeader', width: '*' },
                        { text: generatedAt, style: 'pageHeader', alignment: 'right', width: 'auto' },
                    ],
                    margin: [28, 16, 28, 0],
                };
            },
            footer: function (page, pages) {
                return {
                    text: 'Sayfa ' + page + ' / ' + pages,
                    alignment: 'right',
                    style: 'pageFooter',
                    margin: [28, 0, 28, 12],
                };
            },
            content: content,
            styles: {
                pageHeader: { fontSize: 8, color: '#7f8c8d' },
                pageFooter: { fontSize: 8, color: '#7f8c8d' },
                mainTitle: { fontSize: 14, bold: true, alignment: 'center', margin: [0, 0, 0, 6] },
                subTitle: { fontSize: 9, alignment: 'center', color: '#555555' },
                tableHeader: {
                    bold: true,
                    fontSize: 8,
                    color: '#ffffff',
                    fillColor: '#2c3e50',
                    alignment: 'center',
                },
            },
            defaultStyle: { fontSize: fontSize },
        };

        var filename = payload.filename || 'Liste.pdf';
        pdfMake.createPdf(docDefinition).download(filename);
    }

    global.eshBuildListPdf = buildListPdf;
})(typeof window !== 'undefined' ? window : this);
