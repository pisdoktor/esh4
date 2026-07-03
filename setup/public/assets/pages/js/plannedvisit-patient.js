jQuery(function ($) {
    var tbody = document.getElementById('esh-patient-plans-tbody');
    if (tbody) {
        var fetchUrl = tbody.getAttribute('data-esh-fetch-url');
        if (fetchUrl) {
            function escapeHtml(text) {
                var d = document.createElement('div');
                d.textContent = text || '';
                return d.innerHTML;
            }
            function showFetchError(message) {
                tbody.innerHTML = '<tr class="esh-patient-plans-error-row"><td colspan="6" class="border-0 py-4 text-center text-danger">'
                    + escapeHtml(message) + '</td></tr>';
            }
            eshFetchListHtml(fetchUrl).then(function (data) { tbody.innerHTML = data.html;
            }).catch(function (err) {
                showFetchError(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
            });
        }
    }

    var $collapse = $('#patient-plans-filter-collapse');
    var $toggleText = $('#patient-plans-filter-toggle .js-filter-toggle-text');

    if ($collapse.length && $toggleText.length) {
        $collapse.on('shown.bs.collapse', function () {
            $toggleText.text('Filtreleri Gizle');
        });
        $collapse.on('hidden.bs.collapse', function () {
            $toggleText.text('Filtreleri Göster');
        });
    }

    $('.esh-history-patient-dropdown').each(function () {
        var $root = $(this);
        var toggleEl = $root.find('[data-bs-toggle="dropdown"]')[0];
        if (!toggleEl || typeof bootstrap === 'undefined' || !bootstrap.Dropdown) {
            return;
        }
        var dd = bootstrap.Dropdown.getOrCreateInstance(toggleEl);
        var hideTimer = null;

        $root.on('mouseenter', function () {
            clearTimeout(hideTimer);
            dd.show();
        });
        $root.on('mouseleave', function () {
            hideTimer = setTimeout(function () {
                dd.hide();
            }, 220);
        });
    });
});
