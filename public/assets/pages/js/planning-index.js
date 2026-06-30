document.addEventListener('change', function (ev) {
    var el = ev.target;
    if (!el.matches || !el.matches('input.btn-check[name^="gun["]')) {
        return;
    }
    var lab = document.querySelector('label[for="' + el.id + '"]');
    if (!lab) {
        return;
    }
    lab.classList.toggle('btn-primary', el.checked);
    lab.classList.toggle('btn-outline-secondary', !el.checked);
});

jQuery(function ($) {
    var $filterCollapse = $('#planning-filter-collapse');
    var $toggleText = $('#planning-filter-toggle .js-filter-toggle-text');

    if ($filterCollapse.length && $toggleText.length) {
        $filterCollapse.on('shown.bs.collapse', function () {
            $toggleText.text('Filtreleri Gizle');
        });
        $filterCollapse.on('hidden.bs.collapse', function () {
            $toggleText.text('Filtreleri Göster');
        });
    }

    var tbody = document.getElementById('esh-planning-list-tbody');
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
        tbody.innerHTML = '<tr class="esh-planning-list-error-row"><td colspan="4" class="border-0 py-4 text-center text-danger">'
            + escapeHtml(message) + '</td></tr>';
    }

    eshFetchListHtml(url).then(function (data) { tbody.innerHTML = data.html;
    }).catch(function (err) {
        showError(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
    });
});
