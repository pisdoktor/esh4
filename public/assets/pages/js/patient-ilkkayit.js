$(document).ready(function () {
    const cfg = document.getElementById('patient-ilkkayit-config');
    const cfgTomtomKey = cfg ? String(cfg.getAttribute('data-tomtom-key') || '') : '';
    const tomSelectOpts = window.eshTomSelectDefaults ? window.eshTomSelectDefaults() : {
        allowEmptyOption: true
    };
    const ilceEl = document.getElementById('ilce');
    const ilceOptionsTemplate = ilceEl ? ilceEl.innerHTML : '';

    function getSelectValue(el) {
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

    function getJqSelectValue($select) {
        var el = $select[0];
        return el ? getSelectValue(el) : String($select.val() || '');
    }

    function refreshTomSelect($select, silent) {
        if (window.eshRefreshTomSelect) {
            window.eshRefreshTomSelect($select[0] || $select, silent ? { silent: true } : undefined);
        }
    }

    function setSelectHtml($select, html, disabled, silent) {
        $select.html(html).prop('disabled', !!disabled);
        refreshTomSelect($select, silent !== false);
    }

    function initPlugins(container) {
        if (!container) {
            return;
        }
        if (window.eshInitTomSelect) {
            window.eshInitTomSelect(container, tomSelectOpts);
        }
        if (typeof window.eshPrepareDatepickerInputs === 'function') {
            window.eshPrepareDatepickerInputs(container);
        }
        $(container).find('.datepicker').each(function () {
            const $dp = $(this);
            if (!$dp.data('datepicker')) {
                $dp.datepicker({ format: 'dd-mm-yyyy', autoclose: true, language: 'tr' });
            }
        });
    }

    /** AJAX yanıtını diziye çevirir (PHP json_encode bazen nesne döner). */
    function normalizeAddressList(data) {
        if (!data) {
            return [];
        }
        if (Array.isArray(data)) {
            return data.slice();
        }
        if (typeof data === 'object') {
            return Object.keys(data)
                .sort(function (a, b) {
                    return Number(a) - Number(b);
                })
                .map(function (k) {
                    return data[k];
                });
        }
        return [];
    }

    /** Kapı no: 1, 2, 10; diğer tipler sunucu sırası. */
    function sortAddressListByType(list, type) {
        if (type !== 'kapino' || !list.length) {
            return list;
        }
        return list.slice().sort(function (a, b) {
            return String(a.adi || '').localeCompare(String(b.adi || ''), 'tr', {
                numeric: true,
                sensitivity: 'base'
            });
        });
    }

    function sortSokakList(list) {
        return list.slice().sort(function (a, b) {
            return String(a.adi || '').localeCompare(String(b.adi || ''), 'tr', { sensitivity: 'base' });
        });
    }

    function updateAddressAddButtons($block) {
        const mahalleId = getJqSelectValue($block.find('.mahalle-trigger'));
        const sokakId = getJqSelectValue($block.find('.sokak-trigger'));
        $block.find('.js-add-sokak-btn').prop('disabled', !mahalleId);
        $block.find('.js-add-kapino-btn').prop('disabled', !sokakId);
    }

    function fillSokakSelect($target, items, selectedId) {
        let opts = '<option value="">Sokak Seçiniz</option>';
        items.forEach(function (item) {
            opts += '<option value="' + item.id + '">' + $('<div>').text(item.adi).html() + '</option>';
        });
        setSelectHtml($target, opts, false);
        if (selectedId) {
            $target.val(String(selectedId));
            refreshTomSelect($target, true);
        }
    }

    function loadSokakList($block, mahalleId, selectedId) {
        const $target = $block.find('.sokak-target');
        const $kapino = $block.find('.kapino-target');
        if (!mahalleId) {
            setSelectHtml($target, '<option value="">Mahalle Seçin...</option>', true);
            setSelectHtml($kapino, '<option value="">Kapı No Seçin...</option>', true);
            updateAddressAddButtons($block);
            return;
        }
        $target.html('<option>Yükleniyor...</option>').prop('disabled', true);
        refreshTomSelect($target, true);
        $.getJSON(eshUrl('Address', 'getSubAddresses'), { parent_id: mahalleId, type: 'sokak' })
            .done(function (data) {
                const items = sortSokakList(normalizeAddressList(data));
                fillSokakSelect($target, items, selectedId);
                if (selectedId) {
                    loadKapinoList($block, String(selectedId), null);
                } else {
                    setSelectHtml($kapino, '<option value="">Kapı No Seçin...</option>', true);
                }
                updateAddressAddButtons($block);
            })
            .fail(function () {
                setSelectHtml($target, '<option value="">Veri alınamadı</option>', true);
                updateAddressAddButtons($block);
            });
    }

    function fillKapinoSelect($target, items, selectedId) {
        let opts = '<option value="">Kapı No Seçiniz</option>';
        items.forEach(function (item) {
            opts += '<option value="' + item.id + '">' + $('<div>').text(item.adi).html() + '</option>';
        });
        setSelectHtml($target, opts, false);
        if (selectedId) {
            $target.val(String(selectedId));
            refreshTomSelect($target, true);
        }
    }

    function loadKapinoList($block, sokakId, selectedId) {
        const $target = $block.find('.kapino-target');
        if (!sokakId) {
            setSelectHtml($target, '<option value="">Sokak Seçin...</option>', true);
            updateAddressAddButtons($block);
            return;
        }
        $target.html('<option>Yükleniyor...</option>').prop('disabled', true);
        refreshTomSelect($target, true);
        $.getJSON(eshUrl('Address', 'getSubAddresses'), { parent_id: sokakId, type: 'kapino' })
            .done(function (data) {
                const items = sortAddressListByType(normalizeAddressList(data), 'kapino');
                fillKapinoSelect($target, items, selectedId);
                updateAddressAddButtons($block);
            })
            .fail(function () {
                setSelectHtml($target, '<option value="">Veri alınamadı</option>', true);
                updateAddressAddButtons($block);
            });
    }

    function trDateToMs(value) {
        const m = /^(\d{2})-(\d{2})-(\d{4})$/.exec(String(value || '').trim());
        if (!m) return NaN;
        return new Date(parseInt(m[3], 10), parseInt(m[2], 10) - 1, parseInt(m[1], 10)).getTime();
    }

    let lastRandevuStartDate = null;

    function enforceRandevuNotBeforeKayit(updateStartDate) {
        const $kayit = $('input[name="kayittarihi"]');
        const $randevu = $('input[name="randevutarihi"]');
        if (!$kayit.length || !$randevu.length) {
            return true;
        }

        const kayitVal = String($kayit.val() || '').trim();
        const randevuVal = String($randevu.val() || '').trim();
        const kayitMs = trDateToMs(kayitVal);
        const randevuMs = trDateToMs(randevuVal);

        if (updateStartDate && !isNaN(kayitMs) && window.jQuery && typeof window.jQuery.fn.datepicker === 'function' && kayitVal !== lastRandevuStartDate) {
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

    $(document).on('change blur', 'input[name="kayittarihi"]', function () {
        enforceRandevuNotBeforeKayit(true);
    });
    $(document).on('change blur', 'input[name="randevutarihi"]', function () {
        enforceRandevuNotBeforeKayit(false);
    });
    enforceRandevuNotBeforeKayit(true);

    function renderRandevuYogunluk(count) {
        const $warning = $('#randevu-doluluk');
        if (!$warning.length) {
            return;
        }
        if (count === 0) {
            $warning.html('<span class="text-success"><i class="fa-solid fa-calendar-check me-1"></i>Bu vakit tamamen musait.</span>');
            return;
        }
        if (count < 5) {
            $warning.html('<span class="text-info"><i class="fa-solid fa-circle-info me-1"></i>Bu vakte planlanmis ' + count + ' izlem var.</span>');
            return;
        }
        if (count < 10) {
            $warning.html('<span class="text-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i>Dikkat: ' + count + ' planli izlem var.</span>');
            return;
        }
        $warning.html('<span class="text-danger"><i class="fa-solid fa-circle-xmark me-1"></i>Yogunluk: ' + count + ' planli izlem var!</span>');
    }

    function checkRandevuYogunluk() {
        const $warning = $('#randevu-doluluk');
        const tarih = String($('input[name="randevutarihi"]').val() || '').trim();
        const zaman = $('input[name="zaman"]:checked').val();
        if (!$warning.length) {
            return;
        }
        if (tarih.length !== 10 || typeof zaman === 'undefined') {
            $warning.html('');
            return;
        }

        $warning.html('<small class="text-muted"><i class="fa fa-spinner fa-spin me-1"></i>Kontrol ediliyor...</small>');
        $.get(eshUrl('Patient', 'randevu_yogunluk_kontrol', { tarih: tarih, zaman: zaman })).done(function (res) {
            const count = parseInt(res, 10);
            renderRandevuYogunluk(isNaN(count) || count < 0 ? 0 : count);
        }).fail(function () {
            $warning.html('<small class="text-danger">Kontrol yapilamadi.</small>');
        });
    }

    $(document).on('change blur', 'input[name="randevutarihi"]', checkRandevuYogunluk);
    $(document).on('change', 'input[name="zaman"]', checkRandevuYogunluk);
    checkRandevuYogunluk();

    let lastTcChecked = '';
    let lastTcWarned = '';

    function checkTcDuplicateAjax() {
        const tc = String($('#tckimlik').val() || '').replace(/\D/g, '');
        if (tc.length !== 11 || tc === lastTcChecked) {
            return;
        }
        lastTcChecked = tc;
        $.getJSON(eshUrl('Patient', 'checkTC', { tc: tc, format: 'json' })).done(function (res) {
            const lenOk = res && Number(res.valid) === 1;
            const checksumOk = res && res.checksum_ok !== undefined
                ? Number(res.checksum_ok) === 1
                : lenOk;
            if (!lenOk) {
                $('#sonuc').html('<span class="text-danger small"><i class="fa-solid fa-xmark"></i> TC tam 11 haneli olmalıdır</span>');
                return;
            }
            if (!checksumOk) {
                $('#sonuc').html('<span class="text-warning small"><i class="fa-solid fa-triangle-exclamation"></i> Kontrol hanesi uyarısı — kayıt yine de yapılabilir</span>');
                if (typeof toastr !== 'undefined') {
                    toastr.warning('TC kontrol hanesi algoritmasına uymuyor; kayıt yine de yapılabilir.', 'TC uyarısı');
                }
            } else if (Number(res.exists) === 1) {
                $('#sonuc').html('<span class="text-warning small"><i class="fa-solid fa-triangle-exclamation"></i> TC Kimlik Numarası sistemde kayıtlı</span>');
                if (lastTcWarned !== tc) {
                    toastr.warning('Bu TC kimlik numarası daha önce kayıtlı.', 'Kayıtlı TC');
                    lastTcWarned = tc;
                }
            } else {
                $('#sonuc').html('<span class="text-success small"><i class="fa-solid fa-check"></i> TC Kimlik Numarası geçerli</span>');
            }
        });
    }

    $('#tckimlik').on('blur', checkTcDuplicateAjax);
    $('#tckimlik').on('keyup', function () {
        const tc = String($(this).val() || '').replace(/\D/g, '');
        if (tc.length === 11) {
            checkTcDuplicateAjax();
        }
    });

    let addrCount = 1;
    $('#btn-add-address').on('click', function (e) {
        e.preventDefault();

        const ilceOptions = ilceOptionsTemplate || $('.ilce-trigger').first().html();
        const html = `
        <div class="p-3 border rounded bg-white mb-3 address-row js-removable-address js-address-cascade position-relative animate__animated animate__fadeIn">
            <button type="button" class="btn-close position-absolute top-0 end-0 m-2 remove-addr" title="Bu ek adresi kaldır" aria-label="Bu ek adresi kaldır"></button>
            <h6 class="text-success small fw-bold mb-3"><i class="fa-solid fa-location-dot me-1"></i> Ek Adres</h6>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="small fw-bold">İlçe</label>
                    <select name="adres[${addrCount}][ilce]" class="form-select ilce-trigger esh-tomselect">
                        ${ilceOptions}
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold">Mahalle</label>
                    <select name="adres[${addrCount}][mahalle]" class="form-select mahalle-target mahalle-trigger esh-tomselect" disabled required>
                        <option value="">İlçe Seçin...</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold">Sokak/Cadde</label>
                    <div class="d-flex gap-1 align-items-stretch js-sokak-add-wrap">
                        <div class="flex-grow-1 min-w-0">
                            <select name="adres[${addrCount}][sokak]" class="form-select sokak-target sokak-trigger esh-tomselect" disabled>
                                <option value="">Mahalle Seçin...</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm js-add-sokak-btn flex-shrink-0" title="Yeni sokak ekle" disabled aria-label="Yeni sokak ekle">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="small fw-bold">Kapı No</label>
                    <div class="d-flex gap-1 align-items-stretch js-kapino-add-wrap">
                        <div class="flex-grow-1 min-w-0">
                            <select name="adres[${addrCount}][kapino]" class="form-select kapino-target esh-tomselect" disabled>
                                <option value="">Sokak Seçin...</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm js-add-kapino-btn flex-shrink-0" title="Yeni kapı no ekle" disabled aria-label="Yeni kapı no ekle">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>`;

        const $newRow = $(html);
        $('#address-container').append($newRow);
        initPlugins($newRow);
        updateAddressAddButtons($newRow);
        addrCount++;
    });

    $(document).on('change', '.js-address-cascade .ilce-trigger, .js-address-cascade .mahalle-trigger, .js-address-cascade .sokak-trigger', function () {
        const $this = $(this);
        const parentId = getSelectValue(this);
        const $block = $this.closest('.js-address-cascade');

        let type = '';
        let $target = null;
        let placeholder = '';

        if ($this.hasClass('ilce-trigger')) {
            type = 'mahalle';
            $target = $block.find('.mahalle-target');
            placeholder = 'Mahalle';
            $block.find('.sokak-target, .kapino-target').each(function () {
                setSelectHtml($(this), '<option value="">Bekliyor...</option>', true);
            });
            updateAddressAddButtons($block);
        } else if ($this.hasClass('mahalle-trigger')) {
            if (!parentId) {
                loadSokakList($block, '', null);
                return;
            }
            loadSokakList($block, parentId, null);
            return;
        } else if ($this.hasClass('sokak-trigger')) {
            if (!parentId) {
                setSelectHtml($block.find('.kapino-target'), '<option value="">Sokak Seçin...</option>', true);
                updateAddressAddButtons($block);
                return;
            }
            loadKapinoList($block, parentId, null);
            return;
        }

        if (!parentId || !$target) {
            if ($this.hasClass('ilce-trigger')) {
                setSelectHtml($block.find('.mahalle-target'), '<option value="">İlçe Seçin...</option>', true);
            }
            updateAddressAddButtons($block);
            return;
        }

        setSelectHtml($target, '<option>Yükleniyor...</option>', true);
        $.getJSON(eshUrl('Address', 'getSubAddresses'), { parent_id: parentId, type: type })
            .done(function (data) {
                const items = sortAddressListByType(normalizeAddressList(data), type);
                let opts = `<option value="">${placeholder} Seçiniz</option>`;
                items.forEach(function (item) {
                    opts += `<option value="${item.id}">${$('<div>').text(item.adi).html()}</option>`;
                });
                setSelectHtml($target, opts, false);
                updateAddressAddButtons($block);
            })
            .fail(function () {
                setSelectHtml($target, '<option value="">Veri alınamadı</option>', true);
                updateAddressAddButtons($block);
            });
    });

    $(document).on('click', '.js-add-sokak-btn', function () {
        const $block = $(this).closest('.js-address-cascade');
        const mahalleId = getJqSelectValue($block.find('.mahalle-trigger'));
        if (!mahalleId) {
            alert('Önce mahalle seçiniz.');
            return;
        }
        const adiRaw = window.prompt('Yeni sokak / cadde adı:', '');
        if (adiRaw === null) {
            return;
        }
        const adi = String(adiRaw).trim();
        if (!adi) {
            alert('Sokak adı boş olamaz.');
            return;
        }
        const $btn = $(this).prop('disabled', true);
        $.post(eshUrl('Address', 'saveSokak'), { mahalle_id: mahalleId, adi: adi }, null, 'json')
            .done(function (res) {
                if (res && res.durum === 'tamam' && res.id) {
                    loadSokakList($block, mahalleId, res.id);
                    if (typeof toastr !== 'undefined' && toastr.success) {
                        toastr.success(res.mesaj || 'Sokak eklendi.');
                    }
                } else {
                    alert((res && res.mesaj) ? res.mesaj : 'Sokak eklenemedi.');
                    updateAddressAddButtons($block);
                }
            })
            .fail(function () {
                alert('Sunucu hatası.');
                updateAddressAddButtons($block);
            })
            .always(function () {
                if (mahalleId) {
                    $btn.prop('disabled', false);
                }
            });
    });

    $(document).on('click', '.js-add-kapino-btn', function () {
        const $block = $(this).closest('.js-address-cascade');
        const sokakId = getJqSelectValue($block.find('.sokak-trigger'));
        if (!sokakId) {
            alert('Önce sokak seçiniz.');
            return;
        }
        const adiRaw = window.prompt('Yeni kapı numarası:', '');
        if (adiRaw === null) {
            return;
        }
        const adi = String(adiRaw).trim();
        if (!adi) {
            alert('Kapı numarası boş olamaz.');
            return;
        }
        const $btn = $(this).prop('disabled', true);
        $.post(eshUrl('Address', 'saveKapino'), { sokak_id: sokakId, adi: adi }, null, 'json')
            .done(function (res) {
                if (res && res.durum === 'tamam' && res.id) {
                    loadKapinoList($block, sokakId, res.id);
                    fetchCoordinates();
                    if (typeof toastr !== 'undefined' && toastr.success) {
                        toastr.success(res.mesaj || 'Kapı numarası eklendi.');
                    }
                } else {
                    alert((res && res.mesaj) ? res.mesaj : 'Kapı numarası eklenemedi.');
                    updateAddressAddButtons($block);
                }
            })
            .fail(function () {
                alert('Sunucu hatası.');
                updateAddressAddButtons($block);
            })
            .always(function () {
                if (sokakId) {
                    $btn.prop('disabled', false);
                }
            });
    });

    $('#address-container .js-address-cascade').each(function () {
        updateAddressAddButtons($(this));
    });

    $(document).on('click', '.remove-addr', function () {
        const $blk = $(this).closest('.js-removable-address');
        if ($blk.length && confirm('Bu ek adresi silmek istediğinize emin misiniz?')) {
            $blk.fadeOut(200, function () { $(this).remove(); });
        }
    });

    $('#patientForm').on('submit', function () {
        const $btn = $('#save');
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
        const sokakId = getJqSelectValue($('#sokak'));
        const kapinoId = getJqSelectValue($('#kapino'));
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
            error: function (xhr) {
                toastr.error('Konum servisi hatası: ' + xhr.status);
            }
        });
    }

    $(document).on('change', '#kapino', fetchCoordinates);
});
