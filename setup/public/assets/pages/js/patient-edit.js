function toggleBtnColor(element) {
    var label = document.querySelector('label[for="' + element.id + '"]');
    if (!label) return;
    if (element.checked) {
        label.classList.remove('btn-outline-secondary');
        label.classList.add('btn-primary');
    } else {
        label.classList.remove('btn-primary');
        label.classList.add('btn-outline-secondary');
    }
}

function patientEditNormalizeAddressList(data) {
    if (!data) return [];
    if (Array.isArray(data)) return data.slice();
    if (typeof data === 'object') {
        return Object.keys(data).map(function (k) { return data[k]; });
    }
    return [];
}

function patientEditSortKapinoList(list) {
    return list.slice().sort(function (a, b) {
        return String(a.adi || '').localeCompare(String(b.adi || ''), 'tr', { numeric: true, sensitivity: 'base' });
    });
}

function patientEditGetSelectValue(elOr$) {
    var el = elOr$ && elOr$.jquery ? elOr$[0] : elOr$;
    if (!el) {
        return '';
    }
    if (el.tomselect) {
        var v = el.tomselect.getValue();
        if (Array.isArray(v)) {
            return v.length ? String(v[0]) : '';
        }
        return v != null ? String(v) : '';
    }
    return String(el.value || '');
}

function patientEditGetJqSelectValue($select) {
    return patientEditGetSelectValue($select[0] || $select);
}

/** Kuruma atanmış ICD tanıları — Tom Select ajax (Hastalik/searchAssigned). */
window.eshInitHastalikAjaxTomSelectElement = function (el, options) {
    if (!el || typeof TomSelect === 'undefined' || el.tomselect) {
        return el && el.tomselect ? el.tomselect : null;
    }
    if (el.disabled && !(options && options.force)) {
        return null;
    }
    var searchUrl = el.getAttribute('data-search-url') || '';
    var kurumId = el.getAttribute('data-kurum-id') || '';
    var ensureIds = [];
    Array.prototype.forEach.call(el.options, function (opt) {
        if (opt.value) {
            ensureIds.push(String(opt.value));
        }
    });
    var baseOpts = window.eshTomSelectOptionsForElement(el, Object.assign({}, options || {}, {
        maxOptions: 50,
        valueField: 'id',
        labelField: 'text',
        searchField: ['text'],
        shouldLoad: function (q) {
            var qs = String(q || '');
            if (ensureIds.length > 0) {
                return true;
            }
            if (kurumId && kurumId !== '0') {
                return true;
            }
            return qs.length >= 1;
        },
        load: function (query, callback) {
            if (!searchUrl) {
                callback();
                return;
            }
            var q = String(query || '').trim();
            if (q.length < 1 && (!kurumId || kurumId === '0') && ensureIds.length === 0) {
                callback();
                return;
            }
            var params = new URLSearchParams();
            if (q.length >= 1) {
                params.set('q', q);
            }
            if (kurumId) {
                params.set('kurum_id', kurumId);
            }
            if (ensureIds.length) {
                params.set('ensure_ids', ensureIds.join(','));
            }
            var url = searchUrl;
            var sep = url.indexOf('?') >= 0 ? '&' : '?';
            if (typeof eshFetchJson === 'function') {
                eshFetchJson(url + sep + params.toString()).then(function (items) {
                    callback(Array.isArray(items) ? items : []);
                }).catch(function () {
                    callback();
                });
                return;
            }
            fetch(url + sep + params.toString(), { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (items) { callback(Array.isArray(items) ? items : []); })
                .catch(function () { callback(); });
        }
    }));
    if (ensureIds.length > 0 || (kurumId && kurumId !== '0')) {
        baseOpts.preload = true;
    }
    el.removeAttribute('size');
    try {
        var ts = new TomSelect(el, baseOpts);
        if (window.eshTomSelectAttachStacking) {
            window.eshTomSelectAttachStacking(ts);
        }
        if (window.eshTomSelectAttachDomChangeBridge) {
            window.eshTomSelectAttachDomChangeBridge(ts, el);
        }
        if (el.disabled) {
            ts.disable();
        }
        return ts;
    } catch (err) {
        if (typeof console !== 'undefined' && console.error) {
            console.error('eshInitHastalikAjaxTomSelectElement:', el.name || el.id || el, err);
        }
        return null;
    }
};

function patientEditRefreshTomSelect($select, silent) {
    if (window.eshRefreshTomSelect) {
        window.eshRefreshTomSelect($select[0] || $select, silent ? { silent: true } : undefined);
    }
}

function patientEditSetSelectHtml($select, html, disabled, silent) {
    $select.html(html).prop('disabled', !!disabled);
    patientEditRefreshTomSelect($select, silent !== false);
}

function patientEditUpdateAddressAddButtons($block) {
    var mahalleId = patientEditGetJqSelectValue($block.find('.mahalle-trigger'));
    var sokakId = patientEditGetJqSelectValue($block.find('.sokak-trigger'));
    $block.find('.js-add-sokak-btn').prop('disabled', !mahalleId);
    $block.find('.js-add-kapino-btn').prop('disabled', !sokakId);
}

function patientEditReadRowId($row, name) {
    var v = $row.attr('data-' + name);
    if (v === undefined || v === null || v === '') {
        v = $row.data(name);
    }
    return String(v || '').trim();
}

function patientEditGetMainIlceOptionsHtml() {
    var fromMain = $('#addr-main-ilce').html() || '';
    if (fromMain) {
        return fromMain;
    }
    var cfg = document.getElementById('patient-edit-config');
    if (cfg) {
        return String(cfg.getAttribute('data-main-ilce-options') || '');
    }
    return String((window.ESH_PAGE && window.ESH_PAGE.editMainIlceOptionsInner) || '');
}

function patientEditSortSokakList(list) {
    return list.slice().sort(function (a, b) {
        return String(a.adi || '').localeCompare(String(b.adi || ''), 'tr', { sensitivity: 'base' });
    });
}

function patientEditLoadSokakList($block, mahalleId, selectedId) {
    var $s = $block.find('.sokak-target');
    var $k = $block.find('.kapino-target');
    if (!mahalleId) {
        patientEditSetSelectHtml($s, '<option value="">Sokak Seçin</option>', true);
        patientEditSetSelectHtml($k, '<option value="">Kapı No Seçin</option>', true);
        patientEditUpdateAddressAddButtons($block);
        return $.Deferred().resolve().promise();
    }
    patientEditSetSelectHtml($s, '<option>Yükleniyor...</option>', true);
    return $.getJSON(eshUrl('Address', 'getSubAddresses'), { parent_id: mahalleId, type: 'sokak' })
        .done(function (data) {
            var items = patientEditSortSokakList(patientEditNormalizeAddressList(data));
            var opts = '<option value="">Sokak Seçin</option>';
            items.forEach(function (item) {
                opts += '<option value="' + item.id + '">' + $('<div>').text(item.adi).html() + '</option>';
            });
            patientEditSetSelectHtml($s, opts, false);
            if (selectedId) {
                $s.val(String(selectedId));
                patientEditRefreshTomSelect($s, true);
                patientEditLoadKapinoList($block, String(selectedId), null);
            } else {
                patientEditSetSelectHtml($k, '<option value="">Kapı No Seçin</option>', true);
            }
            patientEditUpdateAddressAddButtons($block);
        })
        .fail(function () {
            patientEditSetSelectHtml($s, '<option value="">Veri alınamadı</option>', true);
            patientEditUpdateAddressAddButtons($block);
        });
}

function patientEditLoadKapinoList($block, sokakId, selectedId) {
    var $k = $block.find('.kapino-target');
    if (!sokakId) {
        patientEditSetSelectHtml($k, '<option value="">Kapı No Seçin</option>', true);
        patientEditUpdateAddressAddButtons($block);
        return $.Deferred().resolve().promise();
    }
    patientEditSetSelectHtml($k, '<option>Yükleniyor...</option>', true);
    return $.getJSON(eshUrl('Address', 'getSubAddresses'), { parent_id: sokakId, type: 'kapino' })
        .done(function (data) {
            var items = patientEditSortKapinoList(patientEditNormalizeAddressList(data));
            var opts = '<option value="">Kapı No Seçin</option>';
            items.forEach(function (item) {
                opts += '<option value="' + item.id + '">' + $('<div>').text(item.adi).html() + '</option>';
            });
            patientEditSetSelectHtml($k, opts, false);
            if (selectedId) {
                $k.val(String(selectedId));
                patientEditRefreshTomSelect($k, true);
            }
            patientEditUpdateAddressAddButtons($block);
        })
        .fail(function () {
            patientEditSetSelectHtml($k, '<option value="">Veri alınamadı</option>', true);
            patientEditUpdateAddressAddButtons($block);
        });
}

function patientEditNormalizeDotDecimal(value) {
    var s = String(value || '').trim().replace(/,/g, '.');
    s = s.replace(/[^0-9.]/g, '');
    var dot = s.indexOf('.');
    if (dot !== -1) {
        s = s.slice(0, dot + 1) + s.slice(dot + 1).replace(/\./g, '');
    }
    return s;
}

function patientEditBindDotDecimalInputs($root) {
    var $scope = $root && $root.length ? $root : $(document);
    $scope.find('input.esh-decimal-dot').each(function () {
        var el = this;
        var run = function () {
            var n = patientEditNormalizeDotDecimal(el.value);
            if (n !== el.value) {
                el.value = n;
            }
        };
        $(el).off('.eshDecimalDot').on('input.eshDecimalDot', run).on('paste.eshDecimalDot', function () {
            setTimeout(run, 0);
        });
        run();
    });
}

$(document).ready(function () {
    patientEditBindDotDecimalInputs($('#patientForm'));
    const cfg = document.getElementById('patient-edit-config');
    const cfgPatientId = cfg ? Number(cfg.getAttribute('data-patient-id') || 0) : 0;
    const cfgMainIlce = cfg ? String(cfg.getAttribute('data-main-ilce-options') || '') : '';
    const yupasGuvenceIds = (function () {
        const raw = cfg ? String(cfg.getAttribute('data-yupas-guvence-ids') || '') : '';
        return raw.split(/[\s,]+/).map(function (x) {
            const n = parseInt(x, 10);
            return isNaN(n) || n <= 0 ? null : n;
        }).filter(function (n) {
            return n !== null;
        });
    })();
    const tomSelectOpts = window.eshTomSelectDefaults ? window.eshTomSelectDefaults() : {
        allowEmptyOption: true,
        placeholder: 'Seçiniz...',
        placeholderMultiple: 'Hastalıkları seçiniz...'
    };

    /** Yalnızca henüz Tom Select uygulanmamış select'leri başlatır (mevcut instance'ları yıkmaz). */
    function patientEditInitMissingTomSelects($root) {
        var $scope = $root && $root.length ? $root : $('#patientForm');
        if (!window.eshInitTomSelect) {
            return;
        }
        $scope.find('select.esh-tomselect').each(function () {
            if (!this.tomselect) {
                window.eshInitTomSelect(this.parentNode || $scope[0] || document, tomSelectOpts);
            }
        });
    }

    function patientEditDestroyTomSelectsInScope($root) {
        var $scope = $root && $root.length ? $root : $(document);
        $scope.find('select.esh-tomselect').each(function () {
            if (this.tomselect) {
                try {
                    this.tomselect.destroy();
                } catch (ignoreDestroy) { /* yoksay */ }
            }
        });
    }

    /** Patient/view parçalı modal: gizliyken init edilmiş Tom Select'leri temizleyip görünürken yeniden kur. */
    function patientEditFinishPartialModalTomSelects($modal) {
        if (!$modal || !$modal.length || !window.eshInitTomSelect) {
            return;
        }
        patientEditDestroyTomSelectsInScope($modal);
        window.eshInitTomSelect($modal[0], Object.assign({}, tomSelectOpts, {
            force: true,
            includePartialModals: true
        }));
        $modal.find('.js-address-cascade').each(function () {
            patientEditUpdateAddressAddButtons($(this));
        });
        $modal.find('select[name="guvence"]').trigger('change');
    }

    function patientEditTsWrapper($s) {
        var el = $s && $s.jquery ? $s[0] : $s;
        if (!el) {
            return $();
        }
        var w = window.eshTomSelectWrapper ? window.eshTomSelectWrapper(el) : (el.tomselect && el.tomselect.wrapper);
        return w ? $(w) : $();
    }

    function syncYupasNoField() {
        var $wrap = $('#hasta-yupasno-wrap');
        var $inp = $('#hasta-yupasno');
        var $sel = $('#hasta-guvence');
        if (!$wrap.length || !$inp.length || !$sel.length) {
            return;
        }
        var v = parseInt(patientEditGetSelectValue($sel[0]), 10);
        var show = yupasGuvenceIds.some(function (id) {
            return Number(id) === v;
        });
        if (show) {
            $wrap.removeClass('d-none');
        } else {
            $wrap.addClass('d-none');
            $inp.val('');
        }
    }

    $('#patientForm').on('change', '#hasta-guvence', syncYupasNoField);
    $(document).on('change', '.patient-partial-edit-form select[name="guvence"]', function () {
        var $form = $(this).closest('form');
        var $wrap = $form.find('[id$="hasta-yupasno-wrap"]');
        var $inp = $form.find('[id$="hasta-yupasno"]');
        if (!$wrap.length || !$inp.length) {
            return;
        }
        var v = parseInt(patientEditGetSelectValue(this), 10);
        var show = yupasGuvenceIds.some(function (id) {
            return Number(id) === v;
        });
        if (show) {
            $wrap.removeClass('d-none');
        } else {
            $wrap.addClass('d-none');
            $inp.val('');
        }
    });
    setTimeout(syncYupasNoField, 0);

    function loadCascadeOptions($select, parentId, type, placeholder) {
        var $block = $select.closest('.js-address-cascade');
        if (!parentId) {
            patientEditSetSelectHtml($select, '<option value="">' + placeholder + ' Seçin</option>', true);
            if ($block.length) patientEditUpdateAddressAddButtons($block);
            return $.Deferred().resolve().promise();
        }
        patientEditSetSelectHtml($select, '<option>Yükleniyor...</option>', true);
        return $.getJSON(eshUrl('Address', 'getSubAddresses', { parent_id: parentId, type: type }))
            .done(function (data) {
                var list = patientEditNormalizeAddressList(data);
                if (type === 'kapino') {
                    list = patientEditSortKapinoList(list);
                } else if (type === 'sokak') {
                    list = patientEditSortSokakList(list);
                }
                var opts = '<option value="">' + placeholder + ' Seçin</option>';
                list.forEach(function (item) { opts += '<option value="' + item.id + '">' + $('<div>').text(item.adi).html() + '</option>'; });
                patientEditSetSelectHtml($select, opts, false);
                if ($block.length) patientEditUpdateAddressAddButtons($block);
            }).fail(function () {
                patientEditSetSelectHtml($select, '<option value="">Veri alınamadı</option>', true);
                if ($block.length) patientEditUpdateAddressAddButtons($block);
            });
    }
    $(document).on('change', '.js-address-cascade .ilce-trigger', function () {
        var $block = $(this).closest('.js-address-cascade');
        var pid = patientEditGetSelectValue(this);
        var $m = $block.find('.mahalle-target');
        var $s = $block.find('.sokak-target');
        var $k = $block.find('.kapino-target');
        if (!pid) {
            patientEditSetSelectHtml($m, '<option value="">Mahalle Seçin</option>', true);
            patientEditSetSelectHtml($s, '<option value="">Sokak Seçin</option>', true);
            patientEditSetSelectHtml($k, '<option value="">Kapı No Seçin</option>', true);
            patientEditUpdateAddressAddButtons($block);
            return;
        }
        loadCascadeOptions($m, pid, 'mahalle', 'Mahalle').always(function () {
            patientEditSetSelectHtml($s, '<option value="">Sokak Seçin</option>', true);
            patientEditSetSelectHtml($k, '<option value="">Kapı No Seçin</option>', true);
            patientEditUpdateAddressAddButtons($block);
        });
    });
    $(document).on('change', '.js-address-cascade .mahalle-trigger', function () {
        var $block = $(this).closest('.js-address-cascade');
        var pid = patientEditGetSelectValue(this);
        if (!pid) {
            patientEditLoadSokakList($block, '', null);
            return;
        }
        patientEditLoadSokakList($block, pid, null);
    });
    $(document).on('change', '.js-address-cascade .sokak-trigger', function () {
        var $block = $(this).closest('.js-address-cascade');
        var pid = patientEditGetSelectValue(this);
        if (!pid) {
            patientEditLoadKapinoList($block, '', null);
            return;
        }
        patientEditLoadKapinoList($block, pid, null);
    });
    $(document).on('click', '.js-add-sokak-btn', function () {
        var $block = $(this).closest('.js-address-cascade');
        var mahalleId = patientEditGetJqSelectValue($block.find('.mahalle-trigger'));
        if (!mahalleId) {
            alert('Önce mahalle seçiniz.');
            return;
        }
        var adiRaw = window.prompt('Yeni sokak / cadde adı:', '');
        if (adiRaw === null) return;
        var adi = String(adiRaw).trim();
        if (!adi) {
            alert('Sokak adı boş olamaz.');
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.post(eshUrl('Address', 'saveSokak'), { mahalle_id: mahalleId, adi: adi }, null, 'json')
            .done(function (res) {
                if (res && res.durum === 'tamam' && res.id) {
                    patientEditLoadSokakList($block, mahalleId, res.id);
                    if (typeof toastr !== 'undefined' && toastr.success) {
                        toastr.success(res.mesaj || 'Sokak eklendi.');
                    }
                } else {
                    alert((res && res.mesaj) ? res.mesaj : 'Sokak eklenemedi.');
                    patientEditUpdateAddressAddButtons($block);
                }
            })
            .fail(function () {
                alert('Sunucu hatası.');
                patientEditUpdateAddressAddButtons($block);
            })
            .always(function () {
                if (mahalleId) $btn.prop('disabled', false);
            });
    });
    $(document).on('click', '.js-add-kapino-btn', function () {
        var $block = $(this).closest('.js-address-cascade');
        var sokakId = patientEditGetJqSelectValue($block.find('.sokak-trigger'));
        if (!sokakId) {
            alert('Önce sokak seçiniz.');
            return;
        }
        var adiRaw = window.prompt('Yeni kapı numarası:', '');
        if (adiRaw === null) return;
        var adi = String(adiRaw).trim();
        if (!adi) {
            alert('Kapı numarası boş olamaz.');
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.post(eshUrl('Address', 'saveKapino'), { sokak_id: sokakId, adi: adi }, null, 'json')
            .done(function (res) {
                if (res && res.durum === 'tamam' && res.id) {
                    patientEditLoadKapinoList($block, sokakId, res.id);
                    if (typeof toastr !== 'undefined' && toastr.success) {
                        toastr.success(res.mesaj || 'Kapı numarası eklendi.');
                    }
                } else {
                    alert((res && res.mesaj) ? res.mesaj : 'Kapı numarası eklenemedi.');
                    patientEditUpdateAddressAddButtons($block);
                }
            })
            .fail(function () {
                alert('Sunucu hatası.');
                patientEditUpdateAddressAddButtons($block);
            })
            .always(function () {
                if (sokakId) $btn.prop('disabled', false);
            });
    });
    $('.js-address-cascade').each(function () { patientEditUpdateAddressAddButtons($(this)); });

    let addrCount = Math.max(1, $('.extra-address-row').length + 1);
    $(document).on('click', '#btn-add-address, .js-btn-add-address', function () {
        let ilceOptions = $('#addr-main-ilce').html() || '';
        ilceOptions = ilceOptions.replace(/selected\s*=\s*\"selected\"/gi, '').replace(/\sselected\b/gi, '');
        const $container = $(this).closest('form').find('.js-address-container, #address-container').first();
        const html = `<div class="p-4 border rounded bg-white mb-3 position-relative shadow-sm border-start border-success border-4 animate__animated animate__fadeIn js-removable-address js-address-cascade"><button type="button" class="btn-close position-absolute top-0 end-0 m-2 remove-addr" title="Bu ek adresi kaldır" aria-label="Bu ek adresi kaldır"></button><div class="d-flex justify-content-between align-items-center mb-3 me-4"><h6 class="text-success fw-bold small mb-0"><i class="fa-solid fa-location-dot me-2"></i>Ek Adres</h6><div class="form-check form-check-inline mb-0"><input class="form-check-input" type="radio" name="ana_adres_index" id="anaAddress${addrCount}" value="${addrCount}"><label class="form-check-label small" for="anaAddress${addrCount}">Ana adres yap</label></div></div><div class="row g-2"><div class="col-md-6 esh-tomselect-field"><label class="small fw-bold text-muted">İlçe</label><select name="adres[${addrCount}][ilce]" class="form-select ilce-trigger esh-tomselect">${ilceOptions}</select></div><div class="col-md-6 esh-tomselect-field"><label class="small fw-bold text-muted">Mahalle</label><select name="adres[${addrCount}][mahalle]" class="form-select mahalle-target mahalle-trigger esh-tomselect" disabled><option value="">İlçe Seçin...</option></select></div><div class="col-md-6 mt-2 esh-tomselect-field"><label class="small fw-bold text-muted">Sokak/Cadde</label><div class="d-flex gap-1 align-items-stretch js-sokak-add-wrap"><div class="flex-grow-1 min-w-0 esh-tomselect-field"><select name="adres[${addrCount}][sokak]" class="form-select sokak-target sokak-trigger esh-tomselect" disabled><option value="">Mahalle Seçin...</option></select></div><button type="button" class="btn btn-outline-secondary btn-sm js-add-sokak-btn flex-shrink-0" title="Yeni sokak ekle" disabled aria-label="Yeni sokak ekle"><i class="fa-solid fa-plus" aria-hidden="true"></i></button></div></div><div class="col-md-6 mt-2 esh-tomselect-field"><label class="small fw-bold text-muted">Kapı No</label><div class="d-flex gap-1 align-items-stretch js-kapino-add-wrap"><div class="flex-grow-1 min-w-0 esh-tomselect-field"><select name="adres[${addrCount}][kapino]" class="form-select kapino-target esh-tomselect" disabled><option value="">Sokak Seçin...</option></select></div><button type="button" class="btn btn-outline-secondary btn-sm js-add-kapino-btn flex-shrink-0" title="Yeni kapı no ekle" disabled aria-label="Yeni kapı no ekle"><i class="fa-solid fa-plus" aria-hidden="true"></i></button></div></div><div class="col-12 mt-3"><textarea name="adres[${addrCount}][adres_aciklama]" class="form-control small" rows="2" placeholder="Örn: Mavi apartman, kat 2, daire 5..."></textarea></div></div></div>`;
        const $wrap = $(html);
        $container.append($wrap);
        if (window.eshInitTomSelect) {
            window.eshInitTomSelect($wrap[0], tomSelectOpts);
        }
        patientEditUpdateAddressAddButtons($wrap);
        addrCount++;
    });
    $(document).on('click', '.remove-addr', function () { const $element = $(this).closest('.js-removable-address'); if (!$element.length) return; if (confirm('Bu ek adresi silmek istediğinize emin misiniz?')) $element.fadeOut(300, function () { $(this).remove(); }); });

    function patientEditFieldLabel(el) {
        if (!el || !el.nodeType) return '';
        if (el.labels && el.labels.length) {
            return String(el.labels[0].textContent || '').replace(/\s+/g, ' ').trim().replace(/\*+$/g, '').trim();
        }
        const id = el.getAttribute('id');
        if (id) {
            const lb = document.querySelector('label[for="' + id + '"]');
            if (lb) return String(lb.textContent || '').replace(/\s+/g, ' ').trim().replace(/\*+$/g, '').trim();
        }
        const wrap = el.closest('.col-md-6, .col-md-4, .col-md-3, .col-md-12, .col-12, .col-md-8');
        if (wrap) {
            const fl = wrap.querySelector('label.form-label, label.small.fw-bold');
            if (fl) return String(fl.textContent || '').replace(/\s+/g, ' ').trim().replace(/\*+$/g, '').trim();
        }
        const nm = el.getAttribute('name') || '';
        const m = {
            ilce: 'İlçe',
            mahalle: 'Mahalle',
            sokak: 'Sokak / cadde',
            kapino: 'Kapı no',
            guvence: 'Güvence',
            tckimlik: 'TC Kimlik No',
            isim: 'Ad',
            soyisim: 'Soyad',
            anneAdi: 'Anne adı',
            babaAdi: 'Baba adı',
            dogumtarihi: 'Doğum tarihi',
            kayittarihi: 'Kayıt tarihi',
            randevutarihi: 'Randevu tarihi',
            cinsiyet: 'Cinsiyet',
            ceptel1: 'Telefon 1 (cep)',
            boy: 'Boy',
            kilo: 'Kilo',
            pasif: 'Kayıt durumu (pasif)',
            pasiftarihi: 'Pasif tarihi',
            'hastaliklar[]': 'Hastalıklar (tanılar)'
        };
        if (nm && m[nm]) return m[nm];
        var mAd = nm.match(/\[(\d+)\]\[(ilce|mahalle|sokak|kapino)\]/);
        if (mAd && m[mAd[2]]) return 'Ek adres #' + mAd[1] + ' — ' + m[mAd[2]];
        return nm || 'Alan';
    }

    function patientEditTomSelectMark($form, showInvalid) {
        $form.find('select.esh-tomselect').each(function () {
            var $s = $(this);
            var $c = patientEditTsWrapper($s);
            if (!$c.length) return;
            var inv = showInvalid && this.validity && !this.validity.valid;
            if (inv) {
                $c.addClass('border border-danger rounded');
            } else {
                $c.removeClass('border border-danger rounded');
            }
        });
    }

    function patientEditClearInvalidUi(form) {
        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
        form.querySelectorAll('.esh-field-invalid').forEach(function (wrap) {
            wrap.classList.remove('esh-field-invalid');
        });
        patientEditTomSelectMark($(form), false);
    }

    function patientEditApplyInvalidUi(form, invalidEls) {
        patientEditClearInvalidUi(form);
        if (!invalidEls || !invalidEls.length) {
            return;
        }
        invalidEls.forEach(function (el) {
            el.classList.add('is-invalid');
            var wrap = el.closest('.esh-tomselect-field, .col-md-6, .col-md-4, .col-md-3, .col-12');
            if (wrap) {
                wrap.classList.add('esh-field-invalid');
            }
        });
        patientEditTomSelectMark($(form), true);
    }

    function patientEditEnableAddressFieldsForSubmit(form) {
        form.querySelectorAll('.js-address-cascade select').forEach(function (el) {
            if (el.disabled) {
                el.disabled = false;
            }
            if (window.eshRefreshTomSelect) {
                window.eshRefreshTomSelect(el, { silent: true });
            }
        });
    }

    function patientEditRunCustomValidation(form) {
        var invalid = [];
        var hl = form.querySelector('select[name="hastaliklar[]"]');
        if (hl) {
            hl.setCustomValidity('');
            if (hl.hasAttribute('required')) {
                var hlCount = 0;
                if (hl.tomselect) {
                    var hv = hl.tomselect.getValue();
                    if (Array.isArray(hv)) {
                        hlCount = hv.filter(function (x) {
                            return String(x || '').trim() !== '' && String(x) !== '0';
                        }).length;
                    } else if (hv != null && String(hv).trim() !== '' && String(hv) !== '0') {
                        hlCount = 1;
                    }
                } else {
                    Array.prototype.forEach.call(hl.options, function (opt) {
                        if (opt.selected && String(opt.value || '').trim() !== '' && String(opt.value) !== '0') {
                            hlCount += 1;
                        }
                    });
                }
                if (hlCount < 1) {
                    hl.setCustomValidity('En az bir tanı seçiniz.');
                    invalid.push(hl);
                }
            }
        }
        var pasifRadios = form.querySelectorAll('input[type="radio"][name="pasif"]');
        if (pasifRadios.length) {
            var pasifOk = false;
            var pasifVal = '';
            Array.prototype.forEach.call(pasifRadios, function (r) {
                if (r.checked) {
                    pasifOk = true;
                    pasifVal = String(r.value || '');
                }
            });
            if (!pasifOk && pasifRadios[0]) {
                invalid.push(pasifRadios[0]);
            }
            if (pasifVal === '1') {
                var pasifTarih = form.querySelector('.esh-pasif-tarihi-input, input[name="pasiftarihi"]');
                if (pasifTarih) {
                    pasifTarih.setCustomValidity('');
                    if (!String(pasifTarih.value || '').trim()) {
                        pasifTarih.setCustomValidity('Pasif tarihi zorunludur.');
                        invalid.push(pasifTarih);
                    }
                }
            }
        }
        return invalid;
    }

    function patientEditRunValidation(form) {
        var invalid = [];
        var phone = form.querySelector('[name="ceptel1"]');
        if (phone) {
            phone.setCustomValidity('');
            if (phone.hasAttribute('required')) {
                var digits = String(phone.value || '').replace(/\D/g, '');
                if (digits.length !== 11) {
                    phone.setCustomValidity('Geçerli bir cep telefonu girin (11 hane).');
                }
            }
        }
        form.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (el) {
            if (el.type === 'hidden') {
                return;
            }
            var wasDisabled = el.disabled;
            if (wasDisabled) {
                el.disabled = false;
            }
            if (!el.checkValidity()) {
                invalid.push(el);
            }
            if (wasDisabled) {
                el.disabled = wasDisabled;
            }
        });
        var customInvalid = patientEditRunCustomValidation(form);
        customInvalid.forEach(function (el) {
            if (invalid.indexOf(el) === -1) {
                invalid.push(el);
            }
        });
        return invalid;
    }

    function patientEditFillSummaryFromInvalid(invalidEls, $box) {
        var seen = {};
        var labels = [];
        (invalidEls || []).forEach(function (el) {
            var t = patientEditFieldLabel(el);
            if (!t || seen[t]) {
                return;
            }
            seen[t] = true;
            labels.push(t);
        });
        if (!labels.length) {
            $box.addClass('d-none').empty();
            return;
        }
        var esc = function (s) { return $('<div>').text(s).html(); };
        var items = labels.map(function (l) { return '<li>' + esc(l) + '</li>'; }).join('');
        $box.removeClass('d-none').html(
            '<strong><i class="fa-solid fa-circle-exclamation me-1"></i>Kayıt tamamlanamadı.</strong> '
            + 'Aşağıdaki alanları doldurun veya düzeltin:'
            + '<ul class="mb-0 mt-2 ps-3 small">' + items + '</ul>'
        );
    }

    function patientEditResetSubmitButton() {
        var $btn = $('#patientFormSubmit');
        if (!$btn.length) {
            return;
        }
        $btn.prop('disabled', false).removeAttr('aria-busy');
        var orig = $btn.data('orig-html');
        if (orig) {
            $btn.html(orig);
        }
    }

    function patientEditFocusFirstInvalid(form, invalidEls) {
        var first = (invalidEls && invalidEls.length) ? invalidEls[0] : form.querySelector(':invalid');
        if (!first) {
            return;
        }
        var $tsWrap = patientEditTsWrapper($(first));
        if ($tsWrap.length && $tsWrap.is(':visible') && first.tomselect) {
            try {
                first.tomselect.focus();
            } catch (ignoreTs) { /* ignore */ }
        } else {
            try {
                first.focus({ preventScroll: true });
            } catch (errFocus) {
                try {
                    first.focus();
                } catch (ignoreFocus) { /* ignore */ }
            }
        }
        var scrollTarget = first.closest('.card, .esh-field-invalid, .col-md-6, .col-12') || first;
        scrollTarget.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    $('#patientForm').on('submit', function (e) {
        var form = this;
        var $form = $(form);
        var $btn = $('#patientFormSubmit');
        if ($btn.attr('aria-busy') === 'true') {
            e.preventDefault();
            return false;
        }

        $form.removeClass('was-validated');
        patientEditClearInvalidUi(form);
        var $summary = $('#patientFormValidationSummary');
        if (!$summary.find('ul').length) {
            $summary.addClass('d-none').empty();
        }

        patientEditEnableAddressFieldsForSubmit(form);
        var invalid = patientEditRunValidation(form);
        if (invalid.length) {
            e.preventDefault();
            e.stopPropagation();
            patientEditResetSubmitButton();
            $form.addClass('was-validated');
            patientEditApplyInvalidUi(form, invalid);
            patientEditFillSummaryFromInvalid(invalid, $summary);
            patientEditFocusFirstInvalid(form, invalid);
            if (typeof toastr !== 'undefined') {
                toastr.warning('Eksik veya geçersiz alanlar için üstteki listeyi ve kırmızı işaretli alanları kontrol edin.', 'Doğrulama');
            }
            return false;
        }

        $btn.prop('disabled', true).attr('aria-busy', 'true');
        $btn.data('orig-html', $btn.html());
        $btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Kaydediliyor...');
    });

    (function () {
        var $sum = $('#patientFormValidationSummary');
        if ($sum.length && $sum.find('ul li').length) {
            setTimeout(function () {
                $sum[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 200);
        }
    })();

    $('#patientForm').on('input change', 'input, select, textarea', function () {
        var el = this;
        if (el.setCustomValidity && el.name === 'ceptel1') {
            el.setCustomValidity('');
        }
        el.classList.remove('is-invalid');
        var wrap = el.closest('.esh-field-invalid');
        if (wrap) {
            wrap.classList.remove('esh-field-invalid');
        }
        if (el.validity && el.validity.valid) {
            patientEditTsWrapper($(el)).removeClass('border border-danger rounded');
        }
        var $btn = $('#patientFormSubmit');
        if ($btn.length && $btn.prop('disabled') && $btn.attr('aria-busy') !== 'true') {
            patientEditResetSubmitButton();
        }
    });

    let tcCheckTimer = null;
    let lastTcChecked = '';
    let lastWarnedTc = '';
    const editPatientId = Number((window.ESH_PAGE && window.ESH_PAGE.editPatientId) || cfgPatientId || 0);
    const $tcInput = $('#tckimlik');
    const $tcResult = $('#sonuc');

    function renderTcResult(lenOk, checksumOk, exists) {
        if (!lenOk) {
            $tcResult.html('<span class="text-danger"><i class="fa-solid fa-xmark"></i> TC tam 11 haneli olmalıdır</span>');
            return;
        }
        if (!checksumOk) {
            $tcResult.html('<span class="text-warning"><i class="fa-solid fa-triangle-exclamation"></i> Kontrol hanesi uyarısı — kayıt yine de yapılabilir</span>');
            if (exists) {
                $tcResult.append(' <span class="text-warning">· sistemde kayıtlı</span>');
            }
            return;
        }
        if (exists) {
            $tcResult.html('<span class="text-warning"><i class="fa-solid fa-triangle-exclamation"></i> TC Kimlik Numarası sistemde kayıtlı</span>');
            return;
        }
        $tcResult.html('<span class="text-success"><i class="fa-solid fa-check"></i> TC Kimlik Numarası geçerli</span>');
    }

    function checkTcValidity() {
        if (!$tcInput.length || !$tcResult.length) return;
        const tc = String($tcInput.val() || '').replace(/\D/g, '');
        if (tc.length !== 11) {
            $tcResult.empty();
            return;
        }
        if (tc === lastTcChecked) return;
        lastTcChecked = tc;
        $.getJSON(eshUrl('Patient', 'checkTC', {
            tc: tc,
            format: 'json',
            exclude_id: editPatientId
        })).done(function (res) {
            const lenOk = Number(res && res.valid) === 1;
            const checksumOk = res && res.checksum_ok !== undefined
                ? Number(res.checksum_ok) === 1
                : lenOk;
            const exists = Number(res && res.exists) === 1;
            renderTcResult(lenOk, checksumOk, exists);
            if (lenOk && !checksumOk && typeof toastr !== 'undefined') {
                toastr.warning('TC kontrol hanesi algoritmasına uymuyor; kayıt yine de yapılabilir.', 'TC uyarısı');
            }
            if (lenOk && exists && lastWarnedTc !== tc) {
                toastr.warning('Bu TC kimlik numarası başka bir hasta kaydında mevcut.', 'Kayıtlı TC');
                lastWarnedTc = tc;
            }
        });
    }

    if ($tcInput.length) {
        $tcInput.on('input', function () {
            this.value = String(this.value || '').replace(/\D/g, '').slice(0, 11);
            clearTimeout(tcCheckTimer);
            tcCheckTimer = setTimeout(checkTcValidity, 250);
        });
        $tcInput.on('blur', checkTcValidity);
    }

    function patientEditInitAdresScope($scope) {
        if (!$scope || !$scope.length) {
            return $.Deferred().resolve().promise();
        }
        $scope.find('.js-address-cascade').each(function () {
            patientEditUpdateAddressAddButtons($(this));
        });
        return initializeExtraAddresses($scope.find('.extra-address-row'));
    }

    var $pageExtraAddrRows = $('.extra-address-row').filter(function () {
        return $(this).closest('.patient-partial-edit-modal').length === 0;
    });
    if ($pageExtraAddrRows.length) {
        initializeExtraAddresses($pageExtraAddrRows).catch(function () {
            /* sessiz: ek adres yoksa veya AJAX hatası */
        }).finally(function () {
            $('.js-address-cascade').not('.patient-partial-edit-modal .js-address-cascade').each(function () {
                patientEditUpdateAddressAddButtons($(this));
            });
            syncYupasNoField();
            patientEditResetSubmitButton();
        });
    }

    $(window).on('load', function () {
        syncYupasNoField();
    });

    patientEditResetSubmitButton();
    setTimeout(patientEditResetSubmitButton, 400);

    window.addEventListener('pageshow', function (ev) {
        if (ev.persisted) {
            patientEditResetSubmitButton();
        }
    });

    function eshSyncPasifTarihiRequired($scope) {
        if (!$scope || !$scope.length) {
            return;
        }
        var pasifOn = $scope.find('input[name="pasif"]:checked').val() === '1';
        $scope.find('.esh-pasif-tarihi-input').each(function () {
            if (pasifOn) {
                this.setAttribute('required', 'required');
            } else {
                this.removeAttribute('required');
                this.setCustomValidity('');
            }
        });
        $scope.find('.esh-pasif-tarihi-required-mark').toggle(!!pasifOn);
    }
    window.eshSyncPasifTarihiRequired = eshSyncPasifTarihiRequired;

    function patientEditValidateNakilHedef(form) {
        var $form = $(form);
        var pasifOn = $form.find('input[name="pasif"]:checked').val() === '1';
        var nakilOn = $form.find('input.esh-pasif-nedeni-radio[data-esh-nakil-nedeni="1"]:checked').length > 0;
        if (pasifOn && nakilOn) {
            if ($form.find('[data-esh-nakil-eligible="1"]').length === 0) {
                return 'Nakil işlemleri yalnızca aktif hasta dosyaları için yapılabilir.';
            }
            if ($form.find('input[name="nakil_hedef"]:checked').length === 0) {
                return 'Başka kuruma nakil için il dışı veya hedef kurum seçin.';
            }
        }
        return null;
    }

    function patientEditHandleFormSubmit(e, form) {
        var $form = $(form);
        var $btn = $form.find('button[type="submit"]').filter(':visible').first();
        if ($btn.length && $btn.attr('aria-busy') === 'true') {
            e.preventDefault();
            return false;
        }

        $form.removeClass('was-validated');
        patientEditClearInvalidUi(form);
        patientEditEnableAddressFieldsForSubmit(form);
        var invalid = patientEditRunValidation(form);
        var nakilErr = patientEditValidateNakilHedef(form);
        if (nakilErr) {
            e.preventDefault();
            if (typeof toastr !== 'undefined') {
                toastr.warning(nakilErr, 'Doğrulama');
            } else {
                alert(nakilErr);
            }
            return false;
        }
        if (invalid.length) {
            e.preventDefault();
            e.stopPropagation();
            $form.addClass('was-validated');
            patientEditApplyInvalidUi(form, invalid);
            if (typeof toastr !== 'undefined') {
                toastr.warning('Eksik veya geçersiz alanları kontrol edin.', 'Doğrulama');
            }
            patientEditFocusFirstInvalid(form, invalid);
            return false;
        }

        if ($btn.length) {
            $btn.prop('disabled', true).attr('aria-busy', 'true');
            $btn.data('orig-html', $btn.html());
            $btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Kaydediliyor...');
        }
        return true;
    }

    $(document).on('submit', '.patient-partial-edit-form', function (e) {
        return patientEditHandleFormSubmit(e, this);
    });

    $(document).on('shown.bs.modal', '.patient-partial-edit-modal', function () {
        var $modal = $(this);
        $modal.find('.modal-body').scrollTop(0);
        eshSyncPasifTarihiRequired($modal);
        patientEditBindDotDecimalInputs($modal);
        var finishTom = function () {
            patientEditFinishPartialModalTomSelects($modal);
        };
        if ($modal.attr('data-esh-section') === 'adres') {
            patientEditInitAdresScope($modal).catch(function () { /* ignore */ }).finally(finishTom);
        } else {
            window.setTimeout(finishTom, 0);
        }
    });
    $('.patient-partial-edit-modal').on('hidden.bs.modal', function () {
        $(this).find('.modal-body').scrollTop(0);
    });
});

$(document).ready(function () {
    function toggleSlide(inputName, targetSuffix) {
        $(document).on('change', 'input[name="' + inputName + '"]', function () {
            var $scope = $(this).closest('.modal-body, form');
            var $target = $scope.find('[id$="' + targetSuffix + '"]');
            if (!$target.length) {
                $target = $('#' + targetSuffix);
            }
            if ($(this).val() == '1') {
                $target.slideDown(300);
            } else {
                $target.slideUp(300);
            }
        });
    }
    toggleSlide('sonda', 'sondaTarihiArea');
    toggleSlide('mama', 'mamaDetailsArea');
    toggleSlide('bez', 'bezDetailsArea');
    toggleSlide('bezrapor', 'bezRaporBitisArea');
    toggleSlide('pansuman', 'pansumanDetailsArea');
    toggleSlide('pasif', 'pasifDetailsArea');

    function eshToggleNakilHedefArea($scope) {
        var $area = $scope.find('[id$="eshNakilHedefArea"]');
        if (!$area.length) {
            $area = $('#eshNakilHedefArea');
        }
        var nakilSelected = $scope.find('input.esh-pasif-nedeni-radio[data-esh-nakil-nedeni="1"]:checked').length > 0;
        var pasifSelected = $scope.find('input[name="pasif"]:checked').val() === '1';
        var nakilEligible = $scope.find('[data-esh-nakil-eligible="1"]').length > 0;
        if (nakilSelected && pasifSelected && nakilEligible) {
            $area.slideDown(200);
        } else {
            $area.slideUp(200);
        }
    }

    $(document).on('change', 'input[name="pasif"]', function () {
        var $scope = $(this).closest('.modal, .modal-body, form');
        eshToggleNakilHedefArea($scope);
        if (typeof window.eshSyncPasifTarihiRequired === 'function') {
            window.eshSyncPasifTarihiRequired($scope.closest('.modal').length ? $scope.closest('.modal') : $scope);
        }
    });
    $(document).on('change', 'input.esh-pasif-nedeni-radio', function () {
        var $scope = $(this).closest('.modal-body, form');
        eshToggleNakilHedefArea($scope);
    });

    $('#patientForm').on('submit', function (e) {
        var $form = $(this);
        var pasifOn = $form.find('input[name="pasif"]:checked').val() === '1';
        var nakilOn = $form.find('input.esh-pasif-nedeni-radio[data-esh-nakil-nedeni="1"]:checked').length > 0;
        if (pasifOn && nakilOn) {
            if ($form.find('[data-esh-nakil-eligible="1"]').length === 0) {
                e.preventDefault();
                alert('Nakil işlemleri yalnızca aktif hasta dosyaları için yapılabilir.');
                return false;
            }
            if ($form.find('input[name="nakil_hedef"]:checked').length === 0) {
                e.preventDefault();
                alert('Başka kuruma nakil için il dışı veya hedef kurum seçin.');
                return false;
            }
        }
        return true;
    });

    if (typeof window.eshSyncPasifTarihiRequired === 'function') {
        window.eshSyncPasifTarihiRequired($('#patientForm'));
    }
});

$(document).ready(function () {
    const $boy = document.querySelector('input[name="boy"]');
    const $kilo = document.querySelector('input[name="kilo"]');
    const vkiContainer = document.getElementById('vki-container');
    const vkiBadge = document.getElementById('vki-badge');
    const vkiLabel = document.getElementById('vki-label');

    if (!$boy || !$kilo || !vkiContainer || !vkiBadge || !vkiLabel) return;

    function classifyVki(vki) {
        if (vki < 18.5) {
            return { label: 'Zayıf', color: 'warning' };
        }
        if (vki <= 24.9) {
            return { label: 'Normal', color: 'success' };
        }
        if (vki <= 29.9) {
            return { label: 'Obez', color: 'warning' };
        }
        return { label: 'Morbid obez', color: 'danger' };
    }

    function renderVki() {
        // Backend boy değerleri geçmişten kalma olarak "metre" (örn: 1.55) olabilir.
        // UI isteği: cm girilsin (örn: 155). Çok küçük değerlerde otomatik cm'e çeviriyoruz.
        const boyRaw = parseFloat(patientEditNormalizeDotDecimal($boy.value) || '0');
        const boyVal = boyRaw;
        const kiloVal = parseFloat(patientEditNormalizeDotDecimal($kilo.value) || '0');
        if (!(boyVal > 0) || !(kiloVal > 0)) {
            vkiContainer.classList.add('d-none');
            return;
        }
        const boyCm = boyVal <= 3 ? (boyVal * 100) : boyVal;
        if (boyVal <= 3 && Math.abs(parseFloat($boy.value || '0')) !== boyCm) {
            // Kaydetmeden önce görünen değeri cm’e çekelim.
            // (İstersen sadece hesap için çevirip input'u değiştirmeyebiliriz.)
            $boy.value = (Math.round(boyCm * 10) / 10).toString().replace(/\.0$/, '');
        }

        const vki = kiloVal / ((boyCm / 100) * (boyCm / 100));
        const vkiRounded = Math.round(vki * 10) / 10;

        const cls = classifyVki(vkiRounded);
        vkiBadge.textContent = vkiRounded.toFixed(1).replace(/\.0$/, '');
        vkiBadge.className = 'badge bg-' + cls.color + (cls.color === 'warning' ? ' text-dark' : '');
        vkiLabel.textContent = cls.label;
        vkiLabel.className = 'badge bg-' + cls.color + (cls.color === 'warning' ? ' text-dark' : '');
        vkiContainer.classList.remove('d-none');
    }

    $boy.addEventListener('input', renderVki);
    $kilo.addEventListener('input', renderVki);
    renderVki();
});

async function initializeExtraAddresses($root) {
    const rows = ($root && $root.length ? $root : $('.extra-address-row'));
    if (!rows.length) {
        return;
    }
    const ilceOptionsHtml = patientEditGetMainIlceOptionsHtml();
    for (let i = 0; i < rows.length; i++) {
        const $row = $(rows[i]);
        const ids = {
            ilce: patientEditReadRowId($row, 'ilce'),
            mahalle: patientEditReadRowId($row, 'mahalle'),
            sokak: patientEditReadRowId($row, 'sokak'),
            kapino: patientEditReadRowId($row, 'kapino')
        };
        const $ilceSelect = $row.find('.ilce-trigger');
        if (ilceOptionsHtml) {
            $ilceSelect.html(ilceOptionsHtml);
            if (ids.ilce) {
                $ilceSelect.val(ids.ilce);
            }
        } else if (ids.ilce) {
            $ilceSelect.html('<option value="' + ids.ilce + '" selected>' + ids.ilce + '</option>');
        }
        patientEditRefreshTomSelect($ilceSelect, true);
        if (ids.ilce) {
            await loadAndSetSubAddress($row.find('.mahalle-target'), ids.ilce, 'mahalle', ids.mahalle, 'Mahalle');
        }
        if (ids.mahalle) {
            await loadAndSetSubAddress($row.find('.sokak-target'), ids.mahalle, 'sokak', ids.sokak, 'Sokak');
        }
        if (ids.sokak) {
            await loadAndSetSubAddress($row.find('.kapino-target'), ids.sokak, 'kapino', ids.kapino, 'Kapı No');
        }
        patientEditUpdateAddressAddButtons($row);
    }
}

function loadAndSetSubAddress($targetSelect, parentId, type, selectedId, placeholder) {
    return new Promise((resolve) => {
        $.getJSON(eshUrl('Address', 'getSubAddresses', { parent_id: parentId, type: type }), function (data) {
            let list = patientEditNormalizeAddressList(data);
            if (type === 'kapino') {
                list = patientEditSortKapinoList(list);
            } else if (type === 'sokak') {
                list = patientEditSortSokakList(list);
            }
            let options = `<option value="">${placeholder} Seçin</option>`;
            list.forEach(function (item) {
                const selected = (String(item.id) === String(selectedId)) ? ' selected="selected"' : '';
                options += `<option value="${item.id}"${selected}>${$('<div>').text(item.adi).html()}</option>`;
            });
            patientEditSetSelectHtml($targetSelect, options, false);
            resolve();
        }).fail(function () {
            const selectedSafe = selectedId || '';
            let options = `<option value="">${placeholder} Seçin</option>`;
            if (selectedSafe) options += `<option value="${selectedSafe}" selected="selected">${$('<div>').text(selectedSafe).html()}</option>`;
            patientEditSetSelectHtml($targetSelect, options, false);
            resolve();
        });
    });
}
