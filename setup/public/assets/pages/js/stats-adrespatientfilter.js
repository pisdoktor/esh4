(function () {
    if (window.jQuery) {
        var $collapse = window.jQuery('#stats-adres-filter-collapse');
        var $toggleText = window.jQuery('#stats-adres-filter-toggle .js-filter-toggle-text');
        if ($collapse.length && $toggleText.length) {
            $collapse.on('shown.bs.collapse', function () {
                $toggleText.text('Filtreleri Gizle');
            });
            $collapse.on('hidden.bs.collapse', function () {
                $toggleText.text('Filtreleri Göster');
            });
        }
    }

    var form = document.getElementById('stats-adres-filter-form');
    var tbody = document.getElementById('esh-adres-patient-filter-tbody');
    if (!tbody) {
        return;
    }

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function showRowsError(message) {
        tbody.innerHTML = '<tr><td colspan="6" class="border-0 py-4 text-center text-danger">'
            + escapeHtml(message) + '</td></tr>';
    }

    var fetchUrl = tbody.getAttribute('data-esh-fetch-url');
    if (fetchUrl) {
        eshFetchListHtml(fetchUrl).then(function (data) {
            tbody.innerHTML = data.html;
        }).catch(function (err) {
            showRowsError(err && err.message ? err.message : 'Ağ hatası.');
        });
    }

    if (!form) {
        return;
    }

    var ajaxBase = form.getAttribute('data-adres-ajax-url') || '';
    var ilceSel = document.getElementById('stats-adres-ilce');
    var mahalleSel = document.getElementById('stats-adres-mahalle');
    var sokakSel = document.getElementById('stats-adres-sokak');
    var kapinoSel = document.getElementById('stats-adres-kapino');

    function resetSelect(sel, placeholder, disabled) {
        if (!sel) {
            return;
        }
        sel.innerHTML = '<option value="">' + escapeHtml(placeholder) + '</option>';
        sel.disabled = !!disabled;
        sel.value = '';
    }

    function fillSelect(sel, items, placeholder) {
        if (!sel) {
            return;
        }
        var html = '<option value="">' + escapeHtml(placeholder) + '</option>';
        items.forEach(function (item) {
            var adi = item.adi || '';
            var sayi = item.sayi != null ? item.sayi : 0;
            html += '<option value="' + escapeHtml(String(item.id || '')) + '">'
                + escapeHtml(adi + ' [' + sayi + ']') + '</option>';
        });
        sel.innerHTML = html;
        sel.disabled = false;
    }

    function loadChildren(tip, ustId, targetSel, placeholder, clearAfter) {
        if (!targetSel || !ajaxBase || !ustId) {
            resetSelect(targetSel, placeholder, true);
            clearAfter.forEach(function (sel) {
                resetSelect(sel, 'Seçiniz…', true);
            });
            return;
        }
        targetSel.disabled = true;
        targetSel.innerHTML = '<option value="">Yükleniyor…</option>';
        clearAfter.forEach(function (sel) {
            resetSelect(sel, 'Seçiniz…', true);
        });

        var url = ajaxBase + (ajaxBase.indexOf('?') >= 0 ? '&' : '?')
            + 'tip=' + encodeURIComponent(tip)
            + '&ust_id=' + encodeURIComponent(ustId);

        eshFetchJson(url, {
            method: 'GET',
            credentials: 'same-origin',
        }).then(function (data) {
            if (!data || !data.ok) {
                resetSelect(targetSel, 'Yüklenemedi', true);
                return;
            }
            fillSelect(targetSel, data.items || [], placeholder);
        }).catch(function () {
            resetSelect(targetSel, 'Yüklenemedi', true);
        });
    }

    if (ilceSel) {
        ilceSel.addEventListener('change', function () {
            var id = ilceSel.value;
            resetSelect(mahalleSel, 'Bir mahalle seçin', true);
            resetSelect(sokakSel, 'Bir sokak seçin', true);
            resetSelect(kapinoSel, 'Bir kapı no seçin', true);
            if (id) {
                loadChildren('mahalle', id, mahalleSel, 'Bir mahalle seçin', [sokakSel, kapinoSel]);
            }
        });
    }
    if (mahalleSel) {
        mahalleSel.addEventListener('change', function () {
            var id = mahalleSel.value;
            resetSelect(sokakSel, 'Bir sokak seçin', true);
            resetSelect(kapinoSel, 'Bir kapı no seçin', true);
            if (id) {
                loadChildren('sokak', id, sokakSel, 'Bir sokak seçin', [kapinoSel]);
            }
        });
    }
    if (sokakSel) {
        sokakSel.addEventListener('change', function () {
            var id = sokakSel.value;
            resetSelect(kapinoSel, 'Bir kapı no seçin', true);
            if (id) {
                loadChildren('kapino', id, kapinoSel, 'Bir kapı no seçin', []);
            }
        });
    }
})();
