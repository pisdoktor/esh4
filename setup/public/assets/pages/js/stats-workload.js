(function () {
    var tbody = document.getElementById('esh-workload-list-tbody');
    if (!tbody) {
        return;
    }
    var url = tbody.getAttribute('data-esh-fetch-url');
    if (!url || url === '') {
        return;
    }

    var colspanRaw = tbody.getAttribute('data-esh-colspan');
    var colspan = parseInt(colspanRaw, 10);
    if (isNaN(colspan) || colspan < 1) {
        colspan = 9;
    }

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function showError(message) {
        tbody.innerHTML = '<tr class="esh-workload-list-error-row"><td colspan="' + colspan + '" class="border-0 py-4 text-center text-danger">' +
            escapeHtml(message) + '</td></tr>';
    }

    eshFetchListHtml(url).then(function (data) { tbody.innerHTML = data.html;
    }).catch(function (err) {
        showError(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
    });
})();
