(function () {
    var container = document.getElementById('esh-data-health-content');
    if (!container) {
        return;
    }
    var url = container.getAttribute('data-esh-fetch-url');
    if (!url || url === '') {
        return;
    }

    var headerBadge = document.getElementById('esh-data-health-header-badge');

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function showError(message) {
        container.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(message) + '</div>';
        if (headerBadge) {
            headerBadge.className = 'badge bg-danger';
            headerBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i>Hata';
        }
    }

    function updateHeaderBadge(kritik) {
        if (!headerBadge) {
            return;
        }
        var n = parseInt(kritik, 10);
        if (isNaN(n) || n < 0) {
            return;
        }
        if (n > 0) {
            headerBadge.className = 'badge bg-danger';
            headerBadge.innerHTML = '<i class="fa-solid fa-triangle-exclamation me-1"></i>Kritik: ' + n;
        } else {
            headerBadge.className = 'badge bg-success';
            headerBadge.innerHTML = '<i class="fa-solid fa-check me-1"></i>Kritik bulunamadı';
        }
    }

    eshFetchListHtml(url).then(function (data) { container.innerHTML = data.html;
        if (typeof data.kritik !== 'undefined') {
            updateHeaderBadge(data.kritik);
        }
    }).catch(function (err) {
        showError(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
    });
})();
