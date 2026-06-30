/* global XLSX */
(function (global) {
    'use strict';

    function cellValue(value) {
        return value == null ? '' : String(value);
    }

    function excelFilename(payload) {
        var filename = payload.filename || 'Liste.pdf';
        if (/\.pdf$/i.test(filename)) {
            return filename.replace(/\.pdf$/i, '.xlsx');
        }
        if (/\.xlsx$/i.test(filename)) {
            return filename;
        }
        return filename + '.xlsx';
    }

    /**
     * @param {object} payload — { headers, rows, meta, filename, title }
     */
    function buildListExcel(payload) {
        if (typeof XLSX === 'undefined') {
            alert('Excel kütüphanesi yüklenemedi. Sayfayı yenileyin.');
            return;
        }

        var headers = payload.headers || [];
        var rows = payload.rows || [];
        var meta = payload.meta || {};
        var aoa = [];
        var title = payload.title ? String(payload.title) : '';
        var subtitle = meta.filterSummary ? String(meta.filterSummary) : '';
        var generatedAt = meta.generatedAt ? String(meta.generatedAt) : '';

        if (title !== '') {
            aoa.push([title]);
        }
        if (subtitle !== '') {
            aoa.push([subtitle]);
        }
        if (generatedAt !== '') {
            aoa.push([generatedAt]);
        }
        if (aoa.length > 0) {
            aoa.push([]);
        }

        if (headers.length > 0) {
            aoa.push(headers.map(cellValue));
        }

        if (rows.length === 0) {
            aoa.push(['Bu sayfada kayıt yok.']);
        } else {
            rows.forEach(function (row) {
                aoa.push(row.map(cellValue));
            });
        }

        var ws = XLSX.utils.aoa_to_sheet(aoa);
        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Rapor');
        XLSX.writeFile(wb, excelFilename(payload));
    }

    global.eshBuildListExcel = buildListExcel;
})(typeof window !== 'undefined' ? window : this);
