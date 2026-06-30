/* global jQuery, eshBuildListPdf, eshBindListExportButtons, eshEnableListExportButtons */
jQuery(function ($) {
    var $from = $('#erapor-filter-date-from');
    var $to = $('#erapor-filter-date-to');
    var $filterCollapse = $('#erapor-filter-collapse');
    var $toggleText = $('#erapor-filter-toggle .js-filter-toggle-text');

    if ($from.length && $to.length && $.fn.datepicker) {
        $from.on('changeDate', function (e) {
            $to.datepicker('setStartDate', e.date);
        });
        $to.on('changeDate', function (e) {
            $from.datepicker('setEndDate', e.date);
        });
    }

    if ($filterCollapse.length && $toggleText.length) {
        $filterCollapse.on('shown.bs.collapse', function () {
            $toggleText.text('Filtreleri Gizle');
        });
        $filterCollapse.on('hidden.bs.collapse', function () {
            $toggleText.text('Filtreleri Göster');
        });
    }

    var tbody = document.getElementById('esh-erapor-list-tbody');
    if (!tbody) {
        return;
    }
    var url = tbody.getAttribute('data-esh-fetch-url');
    if (!url || url === '') {
        return;
    }

    var pdfBtn = document.getElementById('esh-erapor-index-pdf-btn');
    var excelBtn = document.getElementById('esh-erapor-index-excel-btn');
    var pdfUrl = tbody.getAttribute('data-esh-pdf-url')
        || (document.getElementById('esh-erapor-index-root') || {}).getAttribute?.('data-esh-pdf-url')
        || '';

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function showError(message) {
        tbody.innerHTML = '<tr class="esh-erapor-list-error-row"><td colspan="7" class="border-0 py-4 text-center text-danger">'
            + escapeHtml(message) + '</td></tr>';
    }

    function enableExportButtons() {
        if (pdfUrl && pdfBtn && typeof eshEnableListExportButtons === 'function') {
            eshEnableListExportButtons(pdfBtn, excelBtn, true);
        }
    }

    function buildTcGroupRowsUrl(tc, excludeIds) {
        var base = url.replace(/([?&])action=[^&]*/i, '$1action=tcGroupRows');
        if (base.indexOf('action=') === -1) {
            base += (base.indexOf('?') >= 0 ? '&' : '?') + 'action=tcGroupRows';
        }
        var sep = base.indexOf('?') >= 0 ? '&' : '?';
        var out = base + sep + 'tc=' + encodeURIComponent(tc);
        if (excludeIds && excludeIds.length) {
            out += '&exclude_ids=' + encodeURIComponent(excludeIds.join(','));
        }
        return out;
    }

    function getTcGroupRows(tbodyEl, tc) {
        return Array.prototype.slice.call(
            tbodyEl.querySelectorAll('tr[data-esh-tc-group="' + tc + '"][data-esh-tc-child="1"]')
        );
    }

    function collectTcRowIds(tbodyEl, tc) {
        var ids = [];
        tbodyEl.querySelectorAll('tr[data-esh-tc-group="' + tc + '"][data-esh-row-id]').forEach(function (tr) {
            var id = parseInt(tr.getAttribute('data-esh-row-id'), 10);
            if (id > 0) {
                ids.push(id);
            }
        });
        return ids;
    }

    function setToggleExpanded(toggle, expanded) {
        toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        var chevron = toggle.querySelector('.esh-erapor-tc-chevron');
        if (chevron) {
            chevron.classList.toggle('esh-erapor-tc-chevron-open', expanded);
        }
        var parentTr = toggle.closest('tr');
        if (parentTr) {
            parentTr.classList.toggle('esh-erapor-tc-parent-open', expanded);
        }
    }

    function showTcChildren(rows, show) {
        rows.forEach(function (tr) {
            tr.classList.toggle('d-none', !show);
        });
    }

    function insertAfter(referenceNode, newNodes) {
        var parent = referenceNode.parentNode;
        var cursor = referenceNode;
        newNodes.forEach(function (node) {
            parent.insertBefore(node, cursor.nextSibling);
            cursor = node;
        });
    }

    function bindTcGroupToggles(tbodyEl) {
        tbodyEl.addEventListener('click', function (ev) {
            var toggle = ev.target.closest('.esh-erapor-tc-toggle');
            if (!toggle || !tbodyEl.contains(toggle)) {
                return;
            }
            ev.preventDefault();

            var tc = toggle.getAttribute('data-esh-tc') || '';
            if (tc === '') {
                return;
            }

            var expanded = toggle.getAttribute('aria-expanded') === 'true';
            var childRows = getTcGroupRows(tbodyEl, tc);
            var parentTr = toggle.closest('tr');

            if (expanded) {
                setToggleExpanded(toggle, false);
                showTcChildren(childRows, false);
                return;
            }

            function revealChildren() {
                childRows = getTcGroupRows(tbodyEl, tc);
                setToggleExpanded(toggle, true);
                showTcChildren(childRows, true);
            }

            if (toggle.getAttribute('data-esh-tc-loaded') === '1') {
                revealChildren();
                return;
            }

            toggle.disabled = true;
            var tcUrl = buildTcGroupRowsUrl(tc, collectTcRowIds(tbodyEl, tc));

            eshFetchListHtml(tcUrl).then(function (data) {
                if (data.html.trim() !== '') {
                    var wrap = document.createElement('tbody');
                    wrap.innerHTML = data.html.trim();
                    var newRows = Array.prototype.slice.call(wrap.querySelectorAll('tr'));
                    var anchor = childRows.length ? childRows[childRows.length - 1] : parentTr;
                    insertAfter(anchor, newRows);
                }
                toggle.setAttribute('data-esh-tc-loaded', '1');
                revealChildren();
            }).catch(function (err) {
                alert(err && err.message ? err.message : 'Alt kayıtlar yüklenemedi.');
            }).finally(function () {
                toggle.disabled = false;
            });
        });
    }

    function onListLoaded() {
        enableExportButtons();
        bindTcGroupToggles(tbody);
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
                    title: 'E-RAPOR HAVUZU',
                    headerLeft: 'ESH — e-Rapor havuzu',
                    widths: [62, '*', 48, 56, 52, 44, '*'],
                    defaultFontSize: 7,
                });
            },
        });
    }

    eshFetchListHtml(url).then(function (data) {
        tbody.innerHTML = data.html;
        onListLoaded();
    }).catch(function (err) {
        showError(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
    });
});
