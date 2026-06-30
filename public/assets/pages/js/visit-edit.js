(function () {
    var form = document.querySelector('form[id^="form-izlem-edit-"]');
    var collapseEl = document.querySelector('[id^="izlem-edit-neden-collapse-"]');
    if (!form || !collapseEl || typeof bootstrap === 'undefined') {
        return;
    }

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
})();
