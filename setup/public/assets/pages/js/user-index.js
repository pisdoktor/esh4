(function () {
    var container = document.getElementById('esh-user-profile-stats-content');
    if (!container) {
        return;
    }
    var url = container.getAttribute('data-esh-fetch-url');
    if (!url || url === '') {
        return;
    }

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function showError(message) {
        container.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(message) + '</div>';
    }

    eshFetchListHtml(url).then(function (data) {
        container.innerHTML = data.html;
    }).catch(function (err) {
        showError(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
    });
})();
