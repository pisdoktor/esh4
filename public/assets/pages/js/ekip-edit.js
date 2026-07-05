(function () {
    'use strict';

    function userOptionsHtml() {
        return typeof window.eshEkipUserOptions === 'string' ? window.eshEkipUserOptions : '';
    }

    function getSelectValues(selectEl) {
        if (selectEl.tomselect) {
            var raw = selectEl.tomselect.getValue();
            if (Array.isArray(raw)) {
                return raw.map(String);
            }
            return raw ? [String(raw)] : [];
        }
        var viaJq = jQuery(selectEl).val();
        if (!viaJq) {
            return [];
        }
        return Array.isArray(viaJq) ? viaJq.map(String) : [String(viaJq)];
    }

    function updateDisabledOptionsNative(vardiyaId) {
        var vardiyaGrubu = jQuery('.vardiya-grubu[data-vardiya-id="' + vardiyaId + '"]');
        var allSelects = vardiyaGrubu.find('.ekip-select');
        var selectedValues = [];
        allSelects.each(function () {
            selectedValues = selectedValues.concat(getSelectValues(this));
        });
        allSelects.each(function () {
            var currentVals = getSelectValues(this);
            jQuery(this).find('option').each(function () {
                var val = this.value;
                if (val === '') {
                    return;
                }
                if (selectedValues.indexOf(val) !== -1 && currentVals.indexOf(val) === -1) {
                    jQuery(this).prop('disabled', true);
                } else {
                    jQuery(this).prop('disabled', false);
                }
            });
        });
    }

    function syncEkipTomSelectDisabled(vardiyaId) {
        jQuery('.vardiya-grubu[data-vardiya-id="' + vardiyaId + '"] .ekip-select').each(function () {
            var el = this;
            var ts = el.tomselect;
            if (!ts) {
                return;
            }
            jQuery(el).find('option').each(function () {
                var val = this.value;
                if (val === '' || !ts.options[val]) {
                    return;
                }
                var opt = ts.options[val];
                var disabled = !!this.disabled;
                if (!!opt.disabled !== disabled) {
                    ts.updateOption(val, Object.assign({}, opt, { disabled: disabled }));
                }
            });
        });
    }

    function initEkipTomSelect(el) {
        if (!el || typeof TomSelect === 'undefined' || el.tomselect) {
            return;
        }
        if (typeof window.eshInitTomSelectElement === 'function') {
            window.eshInitTomSelectElement(el, { force: true });
        }
        var ts = el.tomselect;
        if (!ts) {
            return;
        }
        if (el.options && el.options.length > 0 && Object.keys(ts.options).length < 1) {
            ts.sync();
            ts.refreshOptions(false);
        }
    }

    function initEkipTomSelects(root) {
        var scope = root ? jQuery(root) : jQuery(document);
        scope.find('.ekip-select').each(function () {
            initEkipTomSelect(this);
        });
    }

    function updateDisabledOptions(vardiyaId) {
        updateDisabledOptionsNative(vardiyaId);
        syncEkipTomSelectDisabled(vardiyaId);
    }

    window.updateDisabledOptionsNative = updateDisabledOptionsNative;
    window.updateDisabledOptions = updateDisabledOptions;

    window.ekipEkle = function (vKey) {
        var container = jQuery('#vardiya-container-' + vKey + ' .ekip-listesi');
        var nextNo = container.find('.ekip-box').length + 1;
        var html = '<div class="ekip-box"><i class="fa fa-times remove-ekip" title="Kaldır" data-esh-action="ekip-box-remove" data-esh-vkey="' + vKey + '"></i>'
            + '<div class="small fw-semibold mb-1">' + nextNo + '. Ekip</div>'
            + '<select name="ekipler[' + vKey + '][' + nextNo + '][]" class="form-select form-select-sm esh-tomselect ekip-select" multiple data-placeholder="Personel seçin">'
            + userOptionsHtml()
            + '</select></div>';
        var $newBox = jQuery(html);
        container.append($newBox);
        var newSelect = $newBox.find('.ekip-select')[0];
        if (newSelect) {
            initEkipTomSelect(newSelect);
        }
        updateDisabledOptions(vKey);
    };

    function bootEkipEditPage() {
        if (!document.querySelector('.esh-page-ekip')) {
            return;
        }
        initEkipTomSelects(jQuery('.esh-page-ekip'));
        [0, 1, 2].forEach(updateDisabledOptions);
    }

    jQuery(function ($) {
        if (!document.querySelector('.esh-page-ekip')) {
            return;
        }

        $(document).on('change', '.ekip-select', function () {
            var vid = $(this).closest('.vardiya-grubu').data('vardiya-id');
            updateDisabledOptions(vid);
        });

        $('#planTarihi').datepicker({
            format: 'dd.mm.yyyy',
            autoclose: true,
            todayHighlight: true,
            language: 'tr',
            weekStart: 1
        }).on('changeDate', function (e) {
            var d = e.date;
            var yil = d.getFullYear();
            var ay = ('0' + (d.getMonth() + 1)).slice(-2);
            var gun = ('0' + d.getDate()).slice(-2);
            var urlTarih = yil + '-' + ay + '-' + gun;
            window.location.href = (typeof window.eshUrl === 'function'
                ? window.eshUrl('Ekip', 'edit', { tarih: urlTarih })
                : 'index.php?controller=Ekip&action=edit&tarih=' + encodeURIComponent(urlTarih));
        });
    });

    window.addEventListener('load', bootEkipEditPage);
})();
