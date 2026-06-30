(function () {
    var tbody = document.getElementById('esh-stok-malzeme-list-tbody');
    if (!tbody) {
        return;
    }
    var url = tbody.getAttribute('data-esh-fetch-url');
    if (!url) {
        return;
    }

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function load() {
        if (typeof eshFetchListHtml === 'function') {
            eshFetchListHtml(url).then(function (data) {
                tbody.innerHTML = data.html;
            }).catch(function (err) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger py-4">'
                    + escapeHtml(err && err.message ? err.message : 'Yüklenemedi') + '</td></tr>';
            });
            return;
        }
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                tbody.innerHTML = (res && res.ok && res.html) ? res.html : '<tr><td colspan="8" class="text-muted text-center py-4">Kayıt yok</td></tr>';
            });
    }

    load();
})();
