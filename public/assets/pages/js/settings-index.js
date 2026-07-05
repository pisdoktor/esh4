(function () {
    'use strict';

    var form = document.querySelector('[data-esh-settings-form]');
    var dirty = false;

    function markDirty() {
        dirty = true;
    }

    function confirmLeave(href) {
        if (!dirty) {
            return true;
        }
        return window.confirm('Kaydedilmemiş değişiklikler var. Sekmeden ayrılmak istiyor musunuz?');
    }

    if (form) {
        form.addEventListener('change', markDirty);
        form.addEventListener('input', markDirty);
        form.addEventListener('submit', function () {
            dirty = false;
        });
    }

    document.querySelectorAll('[data-esh-settings-tab-link]').forEach(function (link) {
        link.addEventListener('click', function (ev) {
            var href = link.getAttribute('href') || '';
            if (href === '' || href === '#') {
                return;
            }
            if (!confirmLeave(href)) {
                ev.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-esh-settings-leave]').forEach(function (link) {
        link.addEventListener('click', function (ev) {
            if (!confirmLeave(link.getAttribute('href') || '')) {
                ev.preventDefault();
            }
        });
    });

    var mobileSelect = document.querySelector('[data-esh-settings-mobile-tab]');
    if (mobileSelect) {
        mobileSelect.addEventListener('change', function () {
            var href = mobileSelect.value || '';
            if (href === '') {
                return;
            }
            if (confirmLeave(href)) {
                window.location.href = href;
            } else {
                mobileSelect.blur();
            }
        });
    }

    window.addEventListener('beforeunload', function (ev) {
        if (!dirty) {
            return;
        }
        ev.preventDefault();
        ev.returnValue = '';
    });

    var filterInput = document.querySelector('[data-esh-module-filter]');
    if (filterInput) {
        filterInput.addEventListener('input', function () {
            var q = (filterInput.value || '').trim().toLowerCase();
            document.querySelectorAll('.esh-settings-module-group').forEach(function (group) {
                var visible = 0;
                group.querySelectorAll('.esh-settings-module-row').forEach(function (row) {
                    var label = row.getAttribute('data-mod-label') || '';
                    var show = q === '' || label.indexOf(q) !== -1;
                    row.classList.toggle('esh-settings-module-row--hidden', !show);
                    if (show) {
                        visible++;
                    }
                });
                group.classList.toggle('esh-settings-module-group--empty', visible === 0);
            });
        });
    }
})();
