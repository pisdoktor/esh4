(function () {
    var tbody = document.getElementById('esh-stats-hp-tbody');
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

    eshFetchListHtml(url).then(function (data) { tbody.innerHTML = typeof data.html === 'string' ? data.html : '';
    }).catch(function (err) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-danger">' + escapeHtml(err.message || 'Hata') + '</td></tr>';
    });
})();
