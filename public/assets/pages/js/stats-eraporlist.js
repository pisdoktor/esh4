jQuery(function ($) {
    var $collapse = $('#stats-erapor-list-filter-collapse');
    var $toggleText = $('#stats-erapor-list-filter-toggle .js-filter-toggle-text');
    if ($collapse.length && $toggleText.length) {
        $collapse.on('shown.bs.collapse', function () {
            $toggleText.text('Filtreleri Gizle');
        });
        $collapse.on('hidden.bs.collapse', function () {
            $toggleText.text('Filtreleri Göster');
        });
    }
});

(function () {
    var tbody = document.getElementById('esh-erapor-list-tbody');
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
        tbody.innerHTML = '<tr class="esh-erapor-list-error-row"><td colspan="11" class="border-0 py-4 text-center text-danger">'
            + escapeHtml(message) + '</td></tr>';
    }

    eshFetchListHtml(url).then(function (data) { tbody.innerHTML = data.html;
    }).catch(function (err) {
        showError(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
    });
})();
