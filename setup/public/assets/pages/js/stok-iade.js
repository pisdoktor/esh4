(function () {
    var root = document.getElementById('esh-stok-cikis-root') || document.getElementById('esh-stok-iade-root');
    if (!root) {
        return;
    }
    var lookupUrl = root.getAttribute('data-esh-hasta-lookup-url');
    var input = document.getElementById('esh-stok-hasta-q');
    var hidden = document.getElementById('esh-stok-hasta-id');
    var suggestions = document.getElementById('esh-stok-hasta-suggestions');
    var careEl = document.getElementById('esh-stok-hasta-care');
    if (!lookupUrl || !input || !hidden || !suggestions) {
        return;
    }

    var debounceTimer = null;

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function hideSuggestions() {
        suggestions.classList.add('d-none');
        suggestions.innerHTML = '';
    }

    function selectPatient(p) {
        if (!p) {
            return;
        }
        hidden.value = String(p.id || '');
        input.value = ((p.isim || '') + ' ' + (p.soyisim || '')).trim() + ' — ' + (p.tckimlik || '');
        if (careEl && p.care_summary) {
            careEl.textContent = p.care_summary;
        }
        hideSuggestions();
    }

    function renderSuggestions(list) {
        if (!list || !list.length) {
            hideSuggestions();
            return;
        }
        suggestions.innerHTML = list.map(function (p) {
            var label = escapeHtml(((p.isim || '') + ' ' + (p.soyisim || '')).trim())
                + ' <span class="text-muted small">' + escapeHtml(p.tckimlik || '') + '</span>';
            return '<button type="button" class="list-group-item list-group-item-action py-2" data-id="' + escapeHtml(String(p.id)) + '">'
                + label + '</button>';
        }).join('');
        suggestions.classList.remove('d-none');
        suggestions.querySelectorAll('button[data-id]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(btn.getAttribute('data-id'), 10);
                var found = list.find(function (x) { return parseInt(x.id, 10) === id; });
                selectPatient(found);
            });
        });
    }

    function fetchLookup(q) {
        var url = lookupUrl + (lookupUrl.indexOf('?') >= 0 ? '&' : '?') + 'q=' + encodeURIComponent(q);
        fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.exact) {
                    selectPatient(res.exact);
                    return;
                }
                renderSuggestions(res && res.suggestions ? res.suggestions : []);
            })
            .catch(function () {
                hideSuggestions();
            });
    }

    input.addEventListener('input', function () {
        hidden.value = '';
        if (careEl) {
            careEl.textContent = '';
        }
        var q = input.value.trim();
        if (q.length < 2) {
            hideSuggestions();
            return;
        }
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            fetchLookup(q);
        }, 280);
    });

    input.addEventListener('blur', function () {
        setTimeout(hideSuggestions, 200);
    });

    document.addEventListener('click', function (e) {
        if (!root.contains(e.target)) {
            hideSuggestions();
        }
    });
})();
