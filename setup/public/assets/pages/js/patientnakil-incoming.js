(function () {
    var tbody = document.getElementById('esh-nakil-incoming-tbody');
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

    function showError(message) {
        var col = tbody.querySelector('th') ? 7 : 6;
        var thCount = document.querySelectorAll('#esh-nakil-incoming-tbody').length;
        var headCells = tbody.closest('table') && tbody.closest('table').querySelectorAll('thead th');
        col = headCells ? headCells.length : 6;
        tbody.innerHTML = '<tr><td colspan="' + col + '" class="border-0 py-4 text-center text-danger">' + escapeHtml(message) + '</td></tr>';
    }

    eshFetchListHtml(url).then(function (data) { tbody.innerHTML = typeof data.html === 'string' ? data.html : '';
    }).catch(function (err) {
        showError(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
    });
})();
