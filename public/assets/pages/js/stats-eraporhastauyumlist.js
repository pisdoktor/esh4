(function () {
    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function fetchJson(url) {
        return fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            if (!res.ok) {
                throw new Error(res.status === 401 ? 'Oturum süresi dolmuş olabilir.' : 'İstek başarısız (' + res.status + ').');
            }
            return res.json();
        });
    }

    var metricsEl = document.getElementById('esh-erapor-hasta-uyum-list-metrics');
    if (metricsEl) {
        var metricsUrl = metricsEl.getAttribute('data-esh-fetch-url');
        if (metricsUrl) {
            fetchJson(metricsUrl).then(function (data) {
                if (!data || !data.ok || typeof data.html !== 'string') {
                    metricsEl.innerHTML = '<div class="alert alert-danger mb-0 py-2 small">'
                        + escapeHtml((data && data.error) ? data.error : 'Metrikler yüklenemedi.') + '</div>';
                    return;
                }
                metricsEl.innerHTML = data.html;
            }).catch(function (err) {
                metricsEl.innerHTML = '<div class="alert alert-danger mb-0 py-2 small">'
                    + escapeHtml(err && err.message ? err.message : 'Metrikler yüklenirken ağ hatası.') + '</div>';
            });
        }
    }

    var tbody = document.getElementById('esh-erapor-hasta-uyum-list-tbody');
    if (!tbody) {
        return;
    }
    var listUrl = tbody.getAttribute('data-esh-fetch-url');
    if (!listUrl) {
        return;
    }

    var colCount = tbody.closest('table') ? tbody.closest('table').querySelectorAll('thead th').length : 9;

    fetchJson(listUrl).then(function (data) {
        if (!data || !data.ok || typeof data.html !== 'string') {
            tbody.innerHTML = '<tr><td colspan="' + colCount + '" class="text-center text-danger py-4">'
                + escapeHtml((data && data.error) ? data.error : 'Yanıt geçersiz.') + '</td></tr>';
            return;
        }
        tbody.innerHTML = data.html;
    }).catch(function (err) {
        tbody.innerHTML = '<tr><td colspan="' + colCount + '" class="text-center text-danger py-4">'
            + escapeHtml(err && err.message ? err.message : 'Ağ hatası.') + '</td></tr>';
    });
})();
