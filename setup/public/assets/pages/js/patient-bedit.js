function patientBeditGetMainIlceOptionsHtml() {
    var fromMain = $('#addr-main-ilce').html() || '';
    if (fromMain) {
        return fromMain;
    }
    var cfg = document.getElementById('patient-bedit-config');
    if (cfg) {
        return String(cfg.getAttribute('data-main-ilce-options') || '');
    }
    return String((window.ESH_PAGE && window.ESH_PAGE.beditMainIlceOptionsInner) || '');
}

function patientBeditNormalizeAddressList(data) {
    if (!data) return [];
    if (Array.isArray(data)) return data.slice();
    if (typeof data === 'object') {
        return Object.keys(data).map(function (k) { return data[k]; });
    }
    return [];
}

function patientBeditSortKapinoList(list) {
    return list.slice().sort(function (a, b) {
        return String(a.adi || '').localeCompare(String(b.adi || ''), 'tr', { numeric: true, sensitivity: 'base' });
    });
}

function patientBeditSortSokakList(list) {
    return list.slice().sort(function (a, b) {
        return String(a.adi || '').localeCompare(String(b.adi || ''), 'tr', { sensitivity: 'base' });
    });
}

function patientBeditGetSelectValue(elOr$) {
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

function patientBeditGetJqSelectValue($select) {
    return patientBeditGetSelectValue($select[0] || $select);
}

function patientBeditRefreshTomSelect($select, silent) {
    if (window.eshRefreshTomSelect) {
        window.eshRefreshTomSelect($select[0] || $select, silent ? { silent: true } : undefined);
    }
}

function patientBeditSetSelectHtml($select, html, disabled, silent) {
    $select.html(html).prop('disabled', !!disabled);
    patientBeditRefreshTomSelect($select, silent !== false);
}

function patientBeditUpdateAddressAddButtons($block) {
    var mahalleId = patientBeditGetJqSelectValue($block.find('.mahalle-trigger'));
    var sokakId = patientBeditGetJqSelectValue($block.find('.sokak-trigger'));
    $block.find('.js-add-sokak-btn').prop('disabled', !mahalleId);
    $block.find('.js-add-kapino-btn').prop('disabled', !sokakId);
}

function patientBeditLoadSokakList($block, mahalleId, selectedId) {
    var $s = $block.find('.sokak-target');
    var $k = $block.find('.kapino-target');
    if (!mahalleId) {
        patientBeditSetSelectHtml($s, '<option value="">Sokak Seçin</option>', true);
        patientBeditSetSelectHtml($k, '<option value="">Kapı No Seçin</option>', true);
        patientBeditUpdateAddressAddButtons($block);
        return $.Deferred().resolve().promise();
    }
    patientBeditSetSelectHtml($s, '<option>Yükleniyor...</option>', true);
    return $.getJSON(eshUrl('Address', 'getSubAddresses'), { parent_id: mahalleId, type: 'sokak' })
        .done(function (data) {
            var list = patientBeditSortSokakList(patientBeditNormalizeAddressList(data));
            var opts = '<option value="">Sokak Seçin</option>';
            list.forEach(function (item) {
                opts += '<option value="' + item.id + '">' + $('<div>').text(item.adi).html() + '</option>';
            });
            patientBeditSetSelectHtml($s, opts, false);
            if (selectedId) {
                $s.val(String(selectedId));
                patientBeditRefreshTomSelect($s, true);
                patientBeditLoadKapinoList($block, String(selectedId), null);
            } else {
                patientBeditSetSelectHtml($k, '<option value="">Kapı No Seçin</option>', true);
            }
            patientBeditUpdateAddressAddButtons($block);
        })
        .fail(function () {
            patientBeditSetSelectHtml($s, '<option value="">Veri alınamadı</option>', true);
            patientBeditUpdateAddressAddButtons($block);
        });
}

function patientBeditLoadKapinoList($block, sokakId, selectedId) {
    var $k = $block.find('.kapino-target');
    if (!sokakId) {
        patientBeditSetSelectHtml($k, '<option value="">Kapı No Seçin</option>', true);
        patientBeditUpdateAddressAddButtons($block);
        return $.Deferred().resolve().promise();
    }
    patientBeditSetSelectHtml($k, '<option>Yükleniyor...</option>', true);
    return $.getJSON(eshUrl('Address', 'getSubAddresses'), { parent_id: sokakId, type: 'kapino' })
        .done(function (data) {
            var list = patientBeditSortKapinoList(patientBeditNormalizeAddressList(data));
            var opts = '<option value="">Kapı No Seçin</option>';
            list.forEach(function (item) {
                opts += '<option value="' + item.id + '">' + $('<div>').text(item.adi).html() + '</option>';
            });
            patientBeditSetSelectHtml($k, opts, false);
            if (selectedId) {
                $k.val(String(selectedId));
                patientBeditRefreshTomSelect($k, true);
            }
            patientBeditUpdateAddressAddButtons($block);
        })
        .fail(function () {
            patientBeditSetSelectHtml($k, '<option value="">Veri alınamadı</option>', true);
            patientBeditUpdateAddressAddButtons($block);
        });
}

function loadAndSetSubAddress($targetSelect, parentId, type, selectedId, placeholder) {
    return new Promise(function (resolve) {
        $.getJSON(eshUrl('Address', 'getSubAddresses', { parent_id: parentId, type: type }), function (data) {
            var list = patientBeditNormalizeAddressList(data);
            if (type === 'kapino') {
                list = patientBeditSortKapinoList(list);
            } else if (type === 'sokak') {
                list = patientBeditSortSokakList(list);
            }
            var options = '<option value="">' + placeholder + ' Seçin</option>';
            list.forEach(function (item) {
                var sel = (String(item.id) === String(selectedId)) ? ' selected="selected"' : '';
                options += '<option value="' + item.id + '"' + sel + '>' + $('<div>').text(item.adi).html() + '</option>';
            });
            patientBeditSetSelectHtml($targetSelect, options, false);
            resolve();
        }).fail(function () {
            var selectedSafe = selectedId || '';
            var options = '<option value="">' + placeholder + ' Seçin</option>';
            if (selectedSafe) {
                options += '<option value="' + selectedSafe + '" selected="selected">' + $('<div>').text(selectedSafe).html() + '</option>';
            }
            patientBeditSetSelectHtml($targetSelect, options, false);
            resolve();
        });
    });
}

async function initializeExtraAddresses() {
    var rows = $('.extra-address-row');
    if (!rows.length) {
        return;
    }
    var ilceOptionsHtml = patientBeditGetMainIlceOptionsHtml();
    for (var i = 0; i < rows.length; i++) {
        var $row = $(rows[i]);
        var ids = {
            ilce: String($row.data('ilce') || $row.attr('data-ilce') || ''),
            mahalle: String($row.data('mahalle') || $row.attr('data-mahalle') || ''),
            sokak: String($row.data('sokak') || $row.attr('data-sokak') || ''),
            kapino: String($row.data('kapino') || $row.attr('data-kapino') || '')
        };
        var $ilceSelect = $row.find('.ilce-trigger');
        if (ilceOptionsHtml) {
            $ilceSelect.html(ilceOptionsHtml);
            if (ids.ilce) {
                $ilceSelect.val(ids.ilce);
            }
        } else if (ids.ilce) {
            $ilceSelect.html('<option value="' + ids.ilce + '" selected>' + ids.ilce + '</option>');
        }
        patientBeditRefreshTomSelect($ilceSelect, true);
        if (ids.ilce) {
            await loadAndSetSubAddress($row.find('.mahalle-target'), ids.ilce, 'mahalle', ids.mahalle, 'Mahalle');
        }
        if (ids.mahalle) {
            await loadAndSetSubAddress($row.find('.sokak-target'), ids.mahalle, 'sokak', ids.sokak, 'Sokak');
        }
        if (ids.sokak) {
            await loadAndSetSubAddress($row.find('.kapino-target'), ids.sokak, 'kapino', ids.kapino, 'Kapı No');
        }
        patientBeditUpdateAddressAddButtons($row);
    }
}

$(document).ready(function () {
    const cfg = document.getElementById('patient-bedit-config');
    const cfgMainIlce = cfg ? String(cfg.getAttribute('data-main-ilce-options') || '') : '';
    const cfgTomtomKey = cfg ? String(cfg.getAttribute('data-tomtom-key') || '') : '';
    const tomSelectOpts = window.eshTomSelectDefaults ? window.eshTomSelectDefaults() : {
        allowEmptyOption: true
    };
    if (typeof window.eshPrepareDatepickerInputs === 'function') {
        window.eshPrepareDatepickerInputs(document);
    }
    $('.datepicker').datepicker({ format: "dd-mm-yyyy", autoclose: true, language: "tr" });

    $(document).on('change', 'input[name="pasif"]', function () {
        if ($(this).val() === '1') {
            $('#pasifDetailsArea').slideDown(300);
        } else {
            $('#pasifDetailsArea').slideUp(300);
        }
    });

    let lastRandevuStartDate = null;
    function trDateToMs(value) {
        const m = /^(\d{2})-(\d{2})-(\d{4})$/.exec(String(value || '').trim());
        if (!m) return NaN;
        return new Date(parseInt(m[3], 10), parseInt(m[2], 10) - 1, parseInt(m[1], 10)).getTime();
    }
    function enforceRandevuNotBeforeKayit(updateStartDate) {
        const $kayit = $('input[name="kayittarihi"]');
        const $randevu = $('input[name="randevutarihi"]');
        if (!$kayit.length || !$randevu.length) return true;

        const kayitVal = String($kayit.val() || '').trim();
        const randevuVal = String($randevu.val() || '').trim();
        const kayitMs = trDateToMs(kayitVal);
        const randevuMs = trDateToMs(randevuVal);

        if (updateStartDate && !isNaN(kayitMs) && kayitVal !== lastRandevuStartDate) {
            $randevu.datepicker('setStartDate', kayitVal);
            lastRandevuStartDate = kayitVal;
        }

        if (!randevuVal || isNaN(kayitMs) || isNaN(randevuMs) || randevuMs >= kayitMs) {
            $randevu[0].setCustomValidity('');
            return true;
        }

        $randevu[0].setCustomValidity('Ilk randevu tarihi, sisteme kayit tarihinden kucuk olamaz.');
        return false;
    }
    $(document).on('change blur', 'input[name="kayittarihi"]', function () { enforceRandevuNotBeforeKayit(true); });
    $(document).on('change blur', 'input[name="randevutarihi"]', function () { enforceRandevuNotBeforeKayit(false); });
    enforceRandevuNotBeforeKayit(true);

    function loadCascadeOptions($select, parentId, type, placeholder) {
        var $block = $select.closest('.js-address-cascade');
        if (!parentId) {
            patientBeditSetSelectHtml($select, '<option value="">' + placeholder + ' Seçin</option>', true);
            if ($block.length) {
                patientBeditUpdateAddressAddButtons($block);
            }
            return $.Deferred().resolve().promise();
        }
        patientBeditSetSelectHtml($select, '<option>Yükleniyor...</option>', true);
        return $.getJSON(eshUrl('Address', 'getSubAddresses', { parent_id: parentId, type: type }))
            .done(function (data) {
                var list = patientBeditNormalizeAddressList(data);
                if (type === 'kapino') {
                    list = patientBeditSortKapinoList(list);
                } else if (type === 'sokak') {
                    list = patientBeditSortSokakList(list);
                }
                var opts = '<option value="">' + placeholder + ' Seçin</option>';
                list.forEach(function (item) {
                    opts += '<option value="' + item.id + '">' + $('<div>').text(item.adi).html() + '</option>';
                });
                patientBeditSetSelectHtml($select, opts, false);
                if ($block.length) {
                    patientBeditUpdateAddressAddButtons($block);
                }
            })
            .fail(function () {
                patientBeditSetSelectHtml($select, '<option value="">Veri alınamadı</option>', true);
                if ($block.length) {
                    patientBeditUpdateAddressAddButtons($block);
                }
            });
    }

    $(document).on('change', '.js-address-cascade .ilce-trigger, .js-address-cascade .mahalle-trigger, .js-address-cascade .sokak-trigger', function () {
        var $this = $(this);
        var parentId = patientBeditGetSelectValue(this);
        var $block = $this.closest('.js-address-cascade');

        if ($this.hasClass('ilce-trigger')) {
            if (!parentId) {
                patientBeditSetSelectHtml($block.find('.mahalle-target'), '<option value="">Mahalle Seçin</option>', true);
                patientBeditSetSelectHtml($block.find('.sokak-target'), '<option value="">Sokak Seçin</option>', true);
                patientBeditSetSelectHtml($block.find('.kapino-target'), '<option value="">Kapı No Seçin</option>', true);
                patientBeditUpdateAddressAddButtons($block);
                return;
            }
            loadCascadeOptions($block.find('.mahalle-target'), parentId, 'mahalle', 'Mahalle').always(function () {
                patientBeditSetSelectHtml($block.find('.sokak-target'), '<option value="">Sokak Seçin</option>', true);
                patientBeditSetSelectHtml($block.find('.kapino-target'), '<option value="">Kapı No Seçin</option>', true);
                patientBeditUpdateAddressAddButtons($block);
            });
            return;
        }

        if ($this.hasClass('mahalle-trigger')) {
            patientBeditLoadSokakList($block, parentId, null);
            return;
        }

        if ($this.hasClass('sokak-trigger')) {
            patientBeditLoadKapinoList($block, parentId, null);
        }
    });

    $(document).on('click', '.js-add-sokak-btn', function () {
        var $block = $(this).closest('.js-address-cascade');
        var mahalleId = patientBeditGetJqSelectValue($block.find('.mahalle-trigger'));
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
                    patientBeditLoadSokakList($block, mahalleId, res.id);
                    if (typeof toastr !== 'undefined' && toastr.success) {
                        toastr.success(res.mesaj || 'Sokak eklendi.');
                    }
                } else {
                    alert((res && res.mesaj) ? res.mesaj : 'Sokak eklenemedi.');
                    patientBeditUpdateAddressAddButtons($block);
                }
            })
            .fail(function () {
                alert('Sunucu hatası.');
                patientBeditUpdateAddressAddButtons($block);
            })
            .always(function () {
                if (mahalleId) $btn.prop('disabled', false);
            });
    });
    $(document).on('click', '.js-add-kapino-btn', function () {
        var $block = $(this).closest('.js-address-cascade');
        var sokakId = patientBeditGetJqSelectValue($block.find('.sokak-trigger'));
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
                    patientBeditLoadKapinoList($block, sokakId, res.id).always(function () {
                        if ($block.is($('#address-container .js-address-cascade').first())) fetchCoordinates();
                    });
                    if (typeof toastr !== 'undefined' && toastr.success) {
                        toastr.success(res.mesaj || 'Kapı numarası eklendi.');
                    }
                } else {
                    alert((res && res.mesaj) ? res.mesaj : 'Kapı numarası eklenemedi.');
                    patientBeditUpdateAddressAddButtons($block);
                }
            })
            .fail(function () {
                alert('Sunucu hatası.');
                patientBeditUpdateAddressAddButtons($block);
            })
            .always(function () {
                if (sokakId) $btn.prop('disabled', false);
            });
    });
    $(document).on('change', '.js-address-cascade .kapino-target', function () {
        if (!$(this).closest('.js-address-cascade').is($('#address-container .js-address-cascade').first())) {
            return;
        }
        fetchCoordinates();
    });
    $('.js-address-cascade').each(function () {
        patientBeditUpdateAddressAddButtons($(this));
    });

    let addrCount = Math.max(1, $('.extra-address-row').length + 1);
    $('#btn-add-address').on('click', function (e) {
        e.preventDefault();
        let ilceOptions = $('#addr-main-ilce').html() || '';
        ilceOptions = ilceOptions.replace(/selected\s*=\s*\"selected\"/gi, '').replace(/\sselected\b/gi, '');
        const html = `<div class="p-4 border rounded bg-white mb-3 position-relative shadow-sm border-start border-success border-4 animate__animated animate__fadeIn js-removable-address js-address-cascade"><button type="button" class="btn-close position-absolute top-0 end-0 m-2 remove-addr" title="Bu ek adresi kaldır" aria-label="Bu ek adresi kaldır"></button><h6 class="mb-3 text-success fw-bold small"><i class="fa-solid fa-location-dot me-2"></i>Ek Adres</h6><div class="row g-2"><div class="col-md-6"><label class="small fw-bold text-muted">İlçe</label><select name="adres[${addrCount}][ilce]" class="form-select ilce-trigger esh-tomselect">${ilceOptions}</select></div><div class="col-md-6"><label class="small fw-bold text-muted">Mahalle</label><select name="adres[${addrCount}][mahalle]" class="form-select mahalle-target mahalle-trigger esh-tomselect" disabled><option value="">İlçe Seçin...</option></select></div><div class="col-md-6 mt-2"><label class="small fw-bold text-muted">Sokak/Cadde</label><div class="d-flex gap-1 align-items-stretch js-sokak-add-wrap"><div class="flex-grow-1 min-w-0"><select name="adres[${addrCount}][sokak]" class="form-select sokak-target sokak-trigger esh-tomselect" disabled><option value="">Mahalle Seçin...</option></select></div><button type="button" class="btn btn-outline-secondary btn-sm js-add-sokak-btn flex-shrink-0" title="Yeni sokak ekle" disabled aria-label="Yeni sokak ekle"><i class="fa-solid fa-plus" aria-hidden="true"></i></button></div></div><div class="col-md-6 mt-2"><label class="small fw-bold text-muted">Kapı No</label><div class="d-flex gap-1 align-items-stretch js-kapino-add-wrap"><div class="flex-grow-1 min-w-0"><select name="adres[${addrCount}][kapino]" class="form-select kapino-target esh-tomselect" disabled><option value="">Sokak Seçin...</option></select></div><button type="button" class="btn btn-outline-secondary btn-sm js-add-kapino-btn flex-shrink-0" title="Yeni kapı no ekle" disabled aria-label="Yeni kapı no ekle"><i class="fa-solid fa-plus" aria-hidden="true"></i></button></div></div><div class="col-12 mt-3"><textarea name="adres[${addrCount}][adres_aciklama]" class="form-control small" rows="2" placeholder="Adres açıklaması..."></textarea></div></div></div>`;
        const $wrap = $(html);
        $('#address-container').append($wrap);
        if (window.eshInitTomSelect) {
            window.eshInitTomSelect($wrap[0], tomSelectOpts);
        }
        patientBeditUpdateAddressAddButtons($wrap);
        addrCount++;
    });
    $(document).on('click', '.remove-addr', function () { const $element = $(this).closest('.js-removable-address'); if (!$element.length) return; if (confirm('Bu ek adresi silmek istediğinize emin misiniz?')) $element.fadeOut(300, function () { $(this).remove(); }); });
    $('#patientForm').on('submit', function () {
        var $btn = $('#save');
        if ($btn.prop('disabled')) return false;
        if (!enforceRandevuNotBeforeKayit(false)) {
            if (typeof window.toastr !== 'undefined' && window.toastr.error) {
                window.toastr.error('Ilk randevu tarihi, sisteme kayit tarihinden kucuk olamaz.');
            }
            this.reportValidity();
            return false;
        }
        $btn.prop('disabled', true).attr('aria-busy', 'true');
        $btn.data('orig', $btn.html());
        $btn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Kaydediliyor...');
    });

    function fetchCoordinates() {
        var $b = $('#address-container .js-address-cascade').first();
        var $sokak = $b.find('.sokak-target');
        var $kapino = $b.find('.kapino-target');
        var sokakId = patientBeditGetJqSelectValue($sokak);
        var kapinoId = patientBeditGetJqSelectValue($kapino);
        if (!sokakId || !kapinoId) {
            return;
        }
        $.ajax({
            url: typeof eshUrl === 'function' ? eshUrl('Dashboard', 'tomtomGeocodeAjax') : esh_url('Dashboard', 'tomtomGeocodeAjax'),
            data: { kapino_id: kapinoId },
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res && res.ok && res.coords) {
                    $('#kapino-coords-display').val(res.coords);
                    toastr.success('Konum bulundu!');
                    return;
                }
                toastr.warning((res && res.mesaj) ? res.mesaj : 'Koordinat bulunamadı.');
            },
            error: function (xhr) { toastr.error('Konum servisi hatası: ' + xhr.status); }
        });
    }

    initializeExtraAddresses().then(function () {
        $('.js-address-cascade').each(function () { patientBeditUpdateAddressAddButtons($(this)); });
    });
});
