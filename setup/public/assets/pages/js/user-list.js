jQuery(function ($) {
    var $filterCollapse = $('#user-list-filter-collapse');
    var $toggleText = $('#user-list-filter-toggle .js-filter-toggle-text');

    if ($filterCollapse.length && $toggleText.length) {
        $filterCollapse.on('shown.bs.collapse', function () {
            $toggleText.text('Filtreleri Gizle');
        });
        $filterCollapse.on('hidden.bs.collapse', function () {
            $toggleText.text('Filtreleri Göster');
        });
    }
});

(function () {
    var bolgeSelect = document.getElementById('esh-user-filter-bolge');
    var kurumSelect = document.getElementById('esh-user-filter-kurum');
    if (bolgeSelect && kurumSelect && kurumSelect.getAttribute('data-esh-bolge-cascade') === '1') {
        function syncKurumOptions() {
            var bolgeId = String(bolgeSelect.value || '');
            var options = kurumSelect.querySelectorAll('option[data-bolge-id]');
            var selectedStillVisible = false;
            options.forEach(function (opt) {
                var optBolge = String(opt.getAttribute('data-bolge-id') || '0');
                var show = bolgeId === '' || optBolge === bolgeId;
                opt.hidden = !show;
                opt.disabled = !show;
                if (show && opt.selected) {
                    selectedStillVisible = true;
                }
            });
            if (!selectedStillVisible) {
                kurumSelect.value = '';
            }
        }
        bolgeSelect.addEventListener('change', syncKurumOptions);
        syncKurumOptions();
    }
})();

(function () {
    var tbody = document.getElementById('esh-user-list-tbody');
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
        tbody.innerHTML = '<tr class="esh-user-list-error-row"><td colspan="8" class="border-0 py-4 text-center text-danger">'
            + escapeHtml(message) + '</td></tr>';
    }

    eshFetchListHtml(url).then(function (data) { tbody.innerHTML = data.html;
    }).catch(function (err) {
        showError(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
    });
})();
