(function () {
    var summaryEl = document.getElementById('esh-erapor-hasta-uyum-summary');
    var metricsEl = document.getElementById('esh-erapor-hasta-uyum-metrics');
    if (!summaryEl && !metricsEl) {
        return;
    }

    var headerBadge = document.getElementById('esh-erapor-hasta-uyum-header-badge');

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function showBlockError(el, message) {
        if (!el) {
            return;
        }
        el.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(message) + '</div>';
    }

    function updateHeaderBadge(uyumsuz) {
        if (!headerBadge) {
            return;
        }
        var n = parseInt(uyumsuz, 10);
        if (isNaN(n) || n < 0) {
            return;
        }
        if (n > 0) {
            headerBadge.className = 'badge bg-danger';
            headerBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i>Uyumsuzluk: ' + n;
        } else {
            headerBadge.className = 'badge bg-success';
            headerBadge.innerHTML = '<i class="fa-solid fa-check me-1"></i>Uyumsuzluk yok';
        }
    }

    function fetchJson(url) {
        return fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            if (!res.ok) {
                throw new Error(res.status === 401 ? 'Oturum süresi dolmuş olabilir; sayfayı yenileyin.' : 'İstek başarısız (' + res.status + ').');
            }
            return res.json();
        });
    }

    function loadSummary() {
        if (!summaryEl) {
            return Promise.resolve();
        }
        var url = summaryEl.getAttribute('data-esh-fetch-url');
        if (!url) {
            return Promise.resolve();
        }
        return fetchJson(url).then(function (data) {
            if (!data || !data.ok || typeof data.html !== 'string') {
                showBlockError(summaryEl, (data && data.error) ? String(data.error) : 'Özet yüklenemedi.');
                return;
            }
            summaryEl.innerHTML = data.html;
        }).catch(function (err) {
            showBlockError(summaryEl, err && err.message ? err.message : 'Özet yüklenirken ağ hatası.');
        });
    }

    function loadMetrics() {
        if (!metricsEl) {
            return Promise.resolve();
        }
        var url = metricsEl.getAttribute('data-esh-fetch-url');
        if (!url) {
            return Promise.resolve();
        }
        return fetchJson(url).then(function (data) {
            if (!data || !data.ok || typeof data.html !== 'string') {
                showBlockError(metricsEl, (data && data.error) ? String(data.error) : 'Metrikler yüklenemedi.');
                if (headerBadge) {
                    headerBadge.className = 'badge bg-danger';
                    headerBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i>Hata';
                }
                return;
            }
            metricsEl.innerHTML = data.html;
            if (typeof data.uyumsuz !== 'undefined') {
                updateHeaderBadge(data.uyumsuz);
            }
        }).catch(function (err) {
            showBlockError(metricsEl, err && err.message ? err.message : 'Metrikler yüklenirken ağ hatası.');
            if (headerBadge) {
                headerBadge.className = 'badge bg-danger';
                headerBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i>Hata';
            }
        });
    }

    loadSummary();
    loadMetrics();
})();
