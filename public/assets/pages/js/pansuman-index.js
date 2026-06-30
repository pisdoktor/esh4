function toggleBtnColor(element) {
    var label = document.querySelector('label[for="' + element.id + '"]');
    if (!label) return;
    if (element.checked) {
        label.classList.remove('btn-outline-secondary');
        label.classList.add('btn-primary');
        label.style.fontWeight = 'bold';
    } else {
        label.classList.remove('btn-primary');
        label.classList.add('btn-outline-secondary');
        label.style.fontWeight = 'normal';
    }
}

document.addEventListener('change', function (ev) {
    var el = ev.target;
    if (!el.matches || !el.matches('input.pansuman-check')) {
        return;
    }
    toggleBtnColor(el);
});

jQuery(function ($) {
    var $filterCollapse = $('#pansuman-filter-collapse');
    var $toggleText = $('#pansuman-filter-toggle .js-filter-toggle-text');

    if ($filterCollapse.length && $toggleText.length) {
        $filterCollapse.on('shown.bs.collapse', function () {
            $toggleText.text('Filtreleri Gizle');
        });
        $filterCollapse.on('hidden.bs.collapse', function () {
            $toggleText.text('Filtreleri Göster');
        });
    }

    var tbody = document.getElementById('esh-pansuman-list-tbody');
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
        tbody.innerHTML = '<tr class="esh-pansuman-list-error-row"><td colspan="4" class="border-0 py-4 text-center text-danger">'
            + escapeHtml(message) + '</td></tr>';
    }

    eshFetchListHtml(url).then(function (data) { tbody.innerHTML = data.html;
    }).catch(function (err) {
        showError(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
    });
});
