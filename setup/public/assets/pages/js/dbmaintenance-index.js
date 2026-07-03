(function () {
    'use strict';

    var cfg = window.ESH_DB_MAINT || { mysqlAvailable: false, restoreLimitBytes: 52428800 };

    function filterTableRows() {
        var searchEl = document.getElementById('db-maint-table-search');
        var groupEl = document.getElementById('db-maint-table-group-filter');
        var rows = document.querySelectorAll('.db-maint-table-row');
        if (!rows.length) return;

        var q = searchEl ? searchEl.value.trim().toLowerCase() : '';
        var group = groupEl ? groupEl.value : '';

        rows.forEach(function (row) {
            var table = (row.getAttribute('data-table') || '').toLowerCase();
            var rowGroup = row.getAttribute('data-group') || '';
            var show = true;
            if (q && table.indexOf(q) === -1) show = false;
            if (group && rowGroup !== group) show = false;
            row.style.display = show ? '' : 'none';
        });
    }

    function filterMaintResults() {
        var filterEl = document.getElementById('db-maint-result-filter');
        var rows = document.querySelectorAll('.db-maint-result-row');
        if (!filterEl || !rows.length) return;
        var val = filterEl.value;
        rows.forEach(function (row) {
            var type = row.getAttribute('data-msg-type') || '';
            if (!val || type === val) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function showRestoreWarning(selectEl, warningEl) {
        if (!selectEl || !warningEl) return;
        var opt = selectEl.options[selectEl.selectedIndex];
        var size = opt ? parseInt(opt.getAttribute('data-size') || '0', 10) : 0;
        warningEl.classList.add('d-none');
        warningEl.classList.remove('text-danger', 'text-warning');
        warningEl.textContent = '';
        if (!opt || !opt.value) return;
        if (!cfg.mysqlAvailable && size > cfg.restoreLimitBytes) {
            warningEl.textContent = 'Bu dosya 50 MB üzeri; mysql istemcisi olmadan geri yüklenemez. ESH_MYSQL_BIN ayarlayın.';
            warningEl.classList.remove('d-none');
            warningEl.classList.add('text-danger');
        } else if (size > cfg.restoreLimitBytes && !cfg.mysqlAvailable) {
            warningEl.textContent = 'Büyük dosya — geri yükleme uzun sürebilir.';
            warningEl.classList.remove('d-none');
            warningEl.classList.add('text-warning');
        }
    }

    function filterRestoreTableFiles() {
        var tableSel = document.getElementById('restore-table-select');
        var fileSel = document.getElementById('restore-table-file');
        if (!tableSel || !fileSel) return;

        var allOpts = Array.prototype.slice.call(fileSel.querySelectorAll('option'));
        var cur = fileSel.value;
        var t = tableSel.value;

        fileSel.innerHTML = '';
        allOpts.forEach(function (opt) {
            if (!opt.value) {
                fileSel.appendChild(opt.cloneNode(true));
                return;
            }
            if (opt.getAttribute('data-scope') !== 'table') return;
            if (t && opt.getAttribute('data-table') !== t) return;
            fileSel.appendChild(opt.cloneNode(true));
        });

        if (cur) {
            var match = fileSel.querySelector('option[value="' + CSS.escape(cur) + '"]');
            if (match) fileSel.value = cur;
        }
        showRestoreWarning(fileSel, document.getElementById('restore-table-warning'));
    }

    function initRestoreWarnings() {
        var fullFile = document.getElementById('restore-full-file');
        var tableFile = document.getElementById('restore-table-file');
        if (fullFile) {
            fullFile.addEventListener('change', function () {
                showRestoreWarning(fullFile, document.getElementById('restore-full-warning'));
            });
        }
        if (tableFile) {
            tableFile.addEventListener('change', function () {
                showRestoreWarning(tableFile, document.getElementById('restore-table-warning'));
            });
        }
    }

    function initTableSearchFromUrl() {
        var params = new URLSearchParams(window.location.search);
        var table = params.get('table');
        if (!table) return;
        var searchEl = document.getElementById('db-maint-table-search');
        if (searchEl) {
            searchEl.value = table;
            filterTableRows();
        }
        var maintSel = document.getElementById('db-maint-maint-tables');
        if (maintSel) {
            Array.prototype.forEach.call(maintSel.options, function (opt) {
                if (opt.value === table) opt.selected = true;
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var searchEl = document.getElementById('db-maint-table-search');
        var groupEl = document.getElementById('db-maint-table-group-filter');
        if (searchEl) searchEl.addEventListener('input', filterTableRows);
        if (groupEl) groupEl.addEventListener('change', filterTableRows);

        var resultFilter = document.getElementById('db-maint-result-filter');
        if (resultFilter) resultFilter.addEventListener('change', filterMaintResults);

        var tableSel = document.getElementById('restore-table-select');
        if (tableSel) tableSel.addEventListener('change', filterRestoreTableFiles);

        filterRestoreTableFiles();
        initRestoreWarnings();
        initTableSearchFromUrl();
    });
})();
