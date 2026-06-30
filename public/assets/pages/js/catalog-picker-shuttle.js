(function () {
    'use strict';

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function sortByLabel(a, b) {
        return String(a.label || '').localeCompare(String(b.label || ''), 'tr', { sensitivity: 'base' });
    }

    function initShuttle(root) {
        var url = root.getAttribute('data-esh-fetch-url') || '';
        if (!url) {
            return;
        }

        var showKota = root.getAttribute('data-esh-show-kota') === '1';
        var catalogSelect = root.querySelector('.esh-shuttle-catalog');
        var assignedPane = root.querySelector('.esh-shuttle-assigned');
        var addBtn = root.querySelector('.esh-shuttle-add');
        var removeBtn = root.querySelector('.esh-shuttle-remove');
        var loadingEl = root.querySelector('.esh-shuttle-loading');
        var statusEl = root.querySelector('.esh-shuttle-status');
        var gridRow = root.querySelector('.row');
        var formFieldsEl = root.querySelector('.esh-shuttle-form-fields');

        if (!catalogSelect || !assignedPane || !addBtn || !removeBtn) {
            return;
        }

        var catalogItems = [];
        var assignedItems = [];

        function showStatus(message) {
            if (!statusEl) {
                return;
            }
            if (!message) {
                statusEl.textContent = '';
                statusEl.classList.add('d-none');
                return;
            }
            statusEl.textContent = message;
            statusEl.classList.remove('d-none');
        }

        function setLoading(isLoading) {
            if (loadingEl) {
                loadingEl.classList.toggle('d-none', !isLoading);
            }
            if (gridRow) {
                gridRow.classList.toggle('d-none', isLoading);
            }
        }

        function renderCatalogSelect() {
            catalogSelect.innerHTML = '';
            catalogItems.slice().sort(sortByLabel).forEach(function (item) {
                var opt = document.createElement('option');
                opt.value = String(item.id);
                opt.textContent = item.label;
                catalogSelect.appendChild(opt);
            });
        }

        function updateAssignedEmptyState() {
            var empty = assignedPane.querySelector('.esh-shuttle-assigned-empty');
            if (assignedItems.length === 0) {
                if (!empty) {
                    assignedPane.innerHTML = '<p class="esh-shuttle-assigned-empty small text-muted text-center py-5 mb-0">Henüz seçim yok.</p>';
                }
                return;
            }
            if (empty) {
                empty.remove();
            }
        }

        function buildAssignedRow(item) {
            var row = document.createElement('div');
            row.className = 'esh-shuttle-assigned-item d-flex align-items-center gap-2 mb-1 p-1 rounded border border-transparent';
            row.setAttribute('data-id', String(item.id));
            row.setAttribute('role', 'option');

            var check = document.createElement('input');
            check.type = 'checkbox';
            check.className = 'form-check-input esh-shuttle-item-check flex-shrink-0 mt-0';
            check.setAttribute('aria-label', 'Seç: ' + item.label);

            var label = document.createElement('span');
            label.className = 'flex-grow-1 small';
            label.textContent = item.label;

            row.appendChild(check);
            row.appendChild(label);

            if (showKota) {
                var kota = document.createElement('input');
                kota.type = 'number';
                kota.className = 'form-control form-control-sm flex-shrink-0';
                kota.style.width = '6.5rem';
                kota.name = 'hasta_kotasi[' + item.id + ']';
                kota.min = '0';
                kota.max = '9999';
                kota.step = '1';
                kota.placeholder = 'Sınırsız';
                kota.setAttribute('inputmode', 'numeric');
                kota.setAttribute('aria-label', 'Günlük kota: ' + item.label);
                if (item.kota !== null && item.kota !== undefined && item.kota !== '') {
                    kota.value = String(item.kota);
                }
                row.appendChild(kota);
            }

            row.addEventListener('click', function (ev) {
                if (ev.target === check || ev.target.tagName === 'INPUT') {
                    return;
                }
                check.checked = !check.checked;
                row.classList.toggle('bg-white', check.checked);
                row.classList.toggle('border-primary', check.checked);
            });

            check.addEventListener('change', function () {
                row.classList.toggle('bg-white', check.checked);
                row.classList.toggle('border-primary', check.checked);
            });

            return row;
        }

        function renderAssignedPane() {
            assignedPane.innerHTML = '';
            assignedItems.slice().sort(sortByLabel).forEach(function (item) {
                assignedPane.appendChild(buildAssignedRow(item));
            });
            updateAssignedEmptyState();
            syncFormFields();
        }

        function syncFormFields() {
            if (!formFieldsEl) {
                return;
            }
            formFieldsEl.innerHTML = '';
            assignedItems.forEach(function (item) {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'assigned[]';
                hidden.value = String(item.id);
                formFieldsEl.appendChild(hidden);
            });
        }

        function readKotaFromDom(id) {
            var input = assignedPane.querySelector('input[name="hasta_kotasi[' + id + ']"]');
            if (!input || input.value === '') {
                return null;
            }
            var n = parseInt(input.value, 10);
            return n > 0 ? n : null;
        }

        function moveToAssigned(selectedIds) {
            selectedIds.forEach(function (id) {
                var idx = catalogItems.findIndex(function (x) { return x.id === id; });
                if (idx < 0) {
                    return;
                }
                var item = catalogItems.splice(idx, 1)[0];
                assignedItems.push(item);
            });
            renderCatalogSelect();
            renderAssignedPane();
            showStatus('');
        }

        function moveToCatalog(selectedIds) {
            selectedIds.forEach(function (id) {
                var idx = assignedItems.findIndex(function (x) { return x.id === id; });
                if (idx < 0) {
                    return;
                }
                var item = assignedItems.splice(idx, 1)[0];
                if (showKota) {
                    item.kota = readKotaFromDom(id);
                }
                catalogItems.push(item);
            });
            renderCatalogSelect();
            renderAssignedPane();
            showStatus('');
        }

        addBtn.addEventListener('click', function () {
            var ids = Array.prototype.map.call(catalogSelect.selectedOptions, function (opt) {
                return parseInt(opt.value, 10);
            }).filter(function (id) { return id > 0; });
            if (ids.length === 0) {
                showStatus('Soldan en az bir kayıt seçin.');
                return;
            }
            moveToAssigned(ids);
        });

        removeBtn.addEventListener('click', function () {
            var ids = [];
            assignedPane.querySelectorAll('.esh-shuttle-assigned-item').forEach(function (row) {
                var check = row.querySelector('.esh-shuttle-item-check');
                if (check && check.checked) {
                    ids.push(parseInt(row.getAttribute('data-id') || '0', 10));
                }
            });
            ids = ids.filter(function (id) { return id > 0; });
            if (ids.length === 0) {
                showStatus('Sağdan çıkarılacak kayıtları işaretleyin.');
                return;
            }
            moveToCatalog(ids);
        });

        catalogSelect.addEventListener('dblclick', function () {
            var ids = Array.prototype.map.call(catalogSelect.selectedOptions, function (opt) {
                return parseInt(opt.value, 10);
            }).filter(function (id) { return id > 0; });
            if (ids.length) {
                moveToAssigned(ids);
            }
        });

        var form = root.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                if (showKota) {
                    assignedItems.forEach(function (item) {
                        item.kota = readKotaFromDom(item.id);
                    });
                }
                syncFormFields();
            });
        }

        setLoading(true);
        eshFetchJson(url, {
            method: 'GET',
            credentials: 'same-origin',
        }).then(function (data) {
            if (!data || !data.ok || !data.picker) {
                throw new Error((data && data.error) ? String(data.error) : 'Liste yanıtı geçersiz.');
            }
            catalogItems = Array.isArray(data.catalog) ? data.catalog.slice() : [];
            assignedItems = Array.isArray(data.assigned) ? data.assigned.slice() : [];
            renderCatalogSelect();
            renderAssignedPane();
            setLoading(false);
        }).catch(function (err) {
            setLoading(false);
            if (gridRow) {
                gridRow.classList.add('d-none');
            }
            showStatus(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
        });
    }

    document.querySelectorAll('.esh-catalog-picker-shuttle').forEach(initShuttle);
})();
