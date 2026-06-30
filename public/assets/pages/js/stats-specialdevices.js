(function () {
    var tbody = document.getElementById('esh-special-devices-list-tbody');
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

    function showError(message) {
        tbody.innerHTML = '<tr class="esh-special-devices-list-error-row"><td colspan="9" class="border-0 py-4 text-center text-danger">'
            + escapeHtml(message) + '</td></tr>';
    }

    eshFetchListHtml(url).then(function (data) { tbody.innerHTML = data.html;
    }).catch(function (err) {
        showError(err && err.message ? err.message : 'Ag hatasi; baglantinizi kontrol edin.');
    });
})();
