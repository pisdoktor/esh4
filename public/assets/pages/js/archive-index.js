document.addEventListener('DOMContentLoaded', function () {
    var masterCheck = document.getElementById('masterCheck');
    if (masterCheck) {
        masterCheck.addEventListener('change', function () {
            document.querySelectorAll('.mahalle-check, .ilce-master-check').forEach(function (c) {
                c.checked = masterCheck.checked;
            });
        });
    }

    document.querySelectorAll('.ilce-master-check').forEach(function (master) {
        master.addEventListener('change', function () {
            var targetId = this.getAttribute('data-target');
            var container = document.querySelector('[id="' + targetId + '"]');
            if (container) {
                container.querySelectorAll('.mahalle-check').forEach(function (c) {
                    c.checked = master.checked;
                });
            }
        });
    });

    document.querySelectorAll('.collapse').forEach(function (collapseEl) {
        collapseEl.addEventListener('show.bs.collapse', function () {
            var icon = this.previousElementSibling && this.previousElementSibling.querySelector('.toggle-icon');
            if (icon) {
                icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
            }
        });
        collapseEl.addEventListener('hide.bs.collapse', function () {
            var icon = this.previousElementSibling && this.previousElementSibling.querySelector('.toggle-icon');
            if (icon) {
                icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
            }
        });
    });

    var archiveFilterCollapse = document.getElementById('archive-filter-collapse');
    var archiveFilterToggleText = document.querySelector('#archive-filter-toggle .js-filter-toggle-text');
    if (archiveFilterCollapse && archiveFilterToggleText) {
        archiveFilterCollapse.addEventListener('shown.bs.collapse', function () {
            archiveFilterToggleText.textContent = 'Filtreleri Gizle';
        });
        archiveFilterCollapse.addEventListener('hidden.bs.collapse', function () {
            archiveFilterToggleText.textContent = 'Filtreleri Göster';
        });
    }

    var tbody = document.getElementById('esh-archive-list-tbody');
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
        tbody.innerHTML = '<tr class="esh-archive-list-error-row"><td colspan="6" class="border-0 py-4 text-center text-danger">'
            + escapeHtml(message) + '</td></tr>';
    }

    eshFetchListHtml(url).then(function (data) { tbody.innerHTML = data.html;
    }).catch(function (err) {
        showError(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
    });
});
