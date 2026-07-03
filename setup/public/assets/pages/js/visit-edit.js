(function () {
    var form = document.querySelector('form[id^="form-izlem-edit-"]');
    var collapseEl = document.querySelector('[id^="izlem-edit-neden-collapse-"]');
    if (!form) {
        return;
    }

    if (window.eshVisitGeo && typeof window.eshVisitGeo.initForm === 'function') {
        window.eshVisitGeo.initForm(form);
    }
    if (window.eshVisitOfflineDraft && typeof window.eshVisitOfflineDraft.bindForm === 'function') {
        window.eshVisitOfflineDraft.bindForm(form);
    }

    if (collapseEl && typeof bootstrap !== 'undefined') {
        var col = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });

        function syncYapildimiNeden() {
            var v = form.querySelector('input[name="yapildimi"]:checked');
            if (v && v.value === '0') {
                col.show();
            } else {
                col.hide();
            }
        }

        form.querySelectorAll('input[name="yapildimi"]').forEach(function (r) {
            r.addEventListener('change', syncYapildimiNeden);
        });

        syncYapildimiNeden();
    }

    form.addEventListener('submit', function (ev) {
        if (window.eshVisitOfflineDraft && window.eshVisitOfflineDraft.isOffline()) {
            ev.preventDefault();
            form.dispatchEvent(new CustomEvent('esh:visit-offline-queue'));
            return;
        }
        if (!window.eshVisitGeo || typeof window.eshVisitGeo.ensureBeforeSubmit !== 'function') {
            return;
        }
        ev.preventDefault();
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        window.eshVisitGeo.ensureBeforeSubmit(form).then(function (res) {
            if (res && res.ok) {
                form.submit();
            }
        });
    });
})();
