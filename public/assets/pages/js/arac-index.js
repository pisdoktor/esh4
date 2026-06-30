(function () {
    var tbody = document.getElementById('esh-arac-list-tbody');
    if (!tbody) {
        return;
    }
    var url = tbody.getAttribute('data-esh-fetch-url');
    if (!url || url === '') {
        return;
    }

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function listColspan() {
        var n = parseInt(tbody.getAttribute('data-esh-colspan') || '4', 10);
        return Number.isFinite(n) && n > 0 ? n : 4;
    }

    function showError(message) {
        tbody.innerHTML = '<tr class="esh-arac-list-error-row"><td colspan="' + listColspan() + '" class="border-0 py-4 text-center text-danger">'
            + escapeHtml(message) + '</td></tr>';
    }

    eshFetchListHtml(url).then(function (data) { tbody.innerHTML = data.html;
    }).catch(function (err) {
        showError(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
    });
})();
