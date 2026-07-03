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

    function initHastalikPicker(root) {
        var fetchUrl = root.getAttribute('data-esh-fetch-url') || '';
        var searchUrl = root.getAttribute('data-esh-search-url') || '';
        if (!fetchUrl || !searchUrl) {
            return;
        }

        var catalogSelect = root.querySelector('.esh-shuttle-catalog');
        var assignedPane = root.querySelector('.esh-shuttle-assigned');
        var addBtn = root.querySelector('.esh-shuttle-add');
        var removeBtn = root.querySelector('.esh-shuttle-remove');
        var loadingEl = root.querySelector('.esh-shuttle-loading');
        var statusEl = root.querySelector('.esh-shuttle-status');
        var gridRow = root.querySelector('.esh-hastalik-picker-grid');
        var formFieldsEl = root.querySelector('.esh-shuttle-form-fields');
        var searchInput = root.querySelector('.esh-hastalik-picker-search');
        var searchBtn = root.querySelector('.esh-hastalik-picker-search-btn');
        var catSelect = root.querySelector('.esh-hastalik-picker-cat');
        var hintEl = root.querySelector('.esh-hastalik-picker-catalog-hint');

        if (!catalogSelect || !assignedPane || !addBtn || !removeBtn) {
            return;
        }

        var catalogItems = [];
        var assignedItems = [];
        var assignedIdSet = {};

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

        function refreshAssignedIdSet() {
            assignedIdSet = {};
            assignedItems.forEach(function (item) {
                assignedIdSet[item.id] = true;
            });
        }

        function renderCatalogSelect() {
            catalogSelect.innerHTML = '';
            catalogItems.slice().sort(sortByLabel).forEach(function (item) {
                if (assignedIdSet[item.id]) {
                    return;
                }
                var opt = document.createElement('option');
                opt.value = String(item.id);
                opt.textContent = item.label;
                catalogSelect.appendChild(opt);
            });
            if (catalogSelect.options.length === 0 && hintEl) {
                hintEl.textContent = 'Sonuç yok veya tümü kuruma eklenmiş.';
            } else if (hintEl) {
                hintEl.textContent = 'Çoklu seçim: Ctrl veya Shift ile işaretleyin.';
            }
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
            refreshAssignedIdSet();
            renderCatalogSelect();
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

        function moveToAssigned(selectedIds) {
            selectedIds.forEach(function (id) {
                var idx = catalogItems.findIndex(function (x) { return x.id === id; });
                var item = idx >= 0 ? catalogItems.splice(idx, 1)[0] : null;
                if (!item) {
                    item = { id: id, label: '#' + id };
                }
                if (!assignedItems.some(function (x) { return x.id === id; })) {
                    assignedItems.push(item);
                }
            });
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
                if (!catalogItems.some(function (x) { return x.id === item.id; })) {
                    catalogItems.push(item);
                }
            });
            renderAssignedPane();
            showStatus('');
        }

        function buildSearchParams() {
            var params = new URLSearchParams(window.location.search);
            params.set('controller', 'Hastalik');
            params.set('action', 'searchCatalog');
            var q = searchInput ? String(searchInput.value || '').trim() : '';
            if (q !== '') {
                params.set('q', q);
            }
            var cat = catSelect ? String(catSelect.value || '').trim() : '';
            if (cat !== '') {
                params.set('cat', cat);
            }
            params.set('limit', '200');
            return params;
        }

        function runCatalogSearch() {
            var q = searchInput ? String(searchInput.value || '').trim() : '';
            if (q.length > 0 && q.length < 2) {
                showStatus('Arama için en az 2 karakter girin.');
                return;
            }
            showStatus('');
            if (hintEl) {
                hintEl.textContent = 'Aranıyor…';
            }
            var url = searchUrl;
            if (url.indexOf('?') >= 0) {
                url = url.split('?')[0];
            }
            url += '?' + buildSearchParams().toString();

            eshFetchJson(url, { method: 'GET', credentials: 'same-origin' }).then(function (data) {
                if (!data || !data.ok || !Array.isArray(data.items)) {
                    throw new Error((data && data.error) ? String(data.error) : 'Arama yanıtı geçersiz.');
                }
                var merged = {};
                catalogItems.forEach(function (item) {
                    merged[item.id] = item;
                });
                data.items.forEach(function (item) {
                    var id = parseInt(item.id, 10);
                    if (id > 0 && !assignedIdSet[id]) {
                        merged[id] = { id: id, label: String(item.label || ('#' + id)) };
                    }
                });
                catalogItems = Object.keys(merged).map(function (k) { return merged[parseInt(k, 10)]; });
                renderCatalogSelect();
            }).catch(function (err) {
                showStatus(err && err.message ? err.message : 'Arama başarısız.');
                if (hintEl) {
                    hintEl.textContent = 'Arama hatası.';
                }
            });
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

        if (searchBtn) {
            searchBtn.addEventListener('click', runCatalogSearch);
        }
        if (searchInput) {
            searchInput.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter') {
                    ev.preventDefault();
                    runCatalogSearch();
                }
            });
        }

        var form = root.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                syncFormFields();
            });
        }

        setLoading(true);
        eshFetchJson(fetchUrl, { method: 'GET', credentials: 'same-origin' }).then(function (data) {
            if (!data || !data.ok || !data.picker) {
                throw new Error((data && data.error) ? String(data.error) : 'Liste yanıtı geçersiz.');
            }
            assignedItems = Array.isArray(data.assigned) ? data.assigned.slice() : [];
            catalogItems = Array.isArray(data.catalog) ? data.catalog.slice() : [];
            refreshAssignedIdSet();
            catalogItems = catalogItems.filter(function (item) { return !assignedIdSet[item.id]; });
            renderCatalogSelect();
            renderAssignedPane();
            setLoading(false);
            if (searchInput && String(searchInput.value || '').trim().length >= 2) {
                runCatalogSearch();
            }
        }).catch(function (err) {
            setLoading(false);
            if (gridRow) {
                gridRow.classList.add('d-none');
            }
            showStatus(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
        });
    }

    document.querySelectorAll('.esh-hastalik-catalog-picker').forEach(initHastalikPicker);
})();
