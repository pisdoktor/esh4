(function () {
    function adrestanimUrl(action, queryString) {
        if (typeof window.eshUrlWithQuery === 'function') {
            return window.eshUrlWithQuery('Adrestanim', action, queryString || '');
        }
        return (typeof eshUrl === 'function' ? eshUrl('Adrestanim', action) : (String(window.ESH_PUBLIC_WEB || '/public/').replace(/\/?$/, '/') + 'index.php?controller=Adrestanim&action=' + encodeURIComponent(action)))
            + (queryString ? '&' + String(queryString).replace(/^&/, '') : '');
    }

    var SECILI = { ilce: '', mahalle: '', sokak: '' };
    var tipIsimleri = { ilce: 'İlçe', mahalle: 'Mahalle', sokak: 'Sokak / Cadde', kapino: 'Kapı no' };
    var modalBs = null;
    var kapinoGeocodeBusy = false;

    function canManageIlce() {
        var el = document.getElementById('adrestanim-can-manage-ilce');
        return el && String(el.value) === '1';
    }

    function getModal() {
        if (!modalBs && window.bootstrap && document.getElementById('modalAdresEditor')) {
            modalBs = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAdresEditor'));
        }
        return modalBs;
    }

    function kapinoGeocodeDurum(metin) {
        var $box = $('#box-kapino');
        $box.find('.geocode-status').remove();
        if (metin) {
            $box.prepend('<div class="geocode-status empty-msg border-bottom mb-1 pb-2"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + metin + '</div>');
        }
    }

    function kapinoSyncToast(res) {
        if (typeof toastr === 'undefined' || !res || res.durum !== 'tamam') {
            return;
        }
        var bulunan = typeof res.bulunan === 'number' ? res.bulunan : 0;
        var guncellenen = typeof res.guncellenen === 'number' ? res.guncellenen : 0;
        if (bulunan > 0) {
            toastr.info(bulunan + ' kapı için yeni koordinat kaydedildi.', 'TomTom', { timeOut: 4500 });
        }
        if (guncellenen > 0) {
            toastr.warning(guncellenen + ' kapının koordinatı güncellendi (adres değişmiş).', 'TomTom', { timeOut: 5500 });
        }
    }

    function kapinoCoordsTopla(sokakId, afterId, done) {
        if (!sokakId || kapinoGeocodeBusy) {
            if (done) done();
            return;
        }
        kapinoGeocodeBusy = true;
        kapinoGeocodeDurum('Kapı koordinatları TomTom ile kontrol ediliyor…');
        $.post(adrestanimUrl('ajaxGeocodeKapinoBulk'), { sokak_id: sokakId, limit: 35, after_id: afterId || '' }, function (res) {
            if (res.durum === 'tamam') {
                kapinoSyncToast(res);
                var kalan = typeof res.kalan === 'number' ? res.kalan : 0;
                var lastId = res.last_id ? String(res.last_id) : '';
                if (kalan > 0 && lastId !== '') {
                    kapinoGeocodeBusy = false;
                    kapinoCoordsTopla(sokakId, lastId, done);
                    return;
                }
            }
            kapinoGeocodeDurum('');
            kapinoGeocodeBusy = false;
            if (done) done();
        }, 'json').fail(function () {
            kapinoGeocodeDurum('');
            kapinoGeocodeBusy = false;
            if (typeof toastr !== 'undefined') toastr.error('Koordinat kontrolü başarısız.', 'TomTom');
            if (done) done();
        });
    }

    function syncExternalThenYukle(childTip, parentId) {
        var $box = $('#box-' + childTip);
        $box.html('<div class="empty-msg"><span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Belediye adres servisiyle senkronize ediliyor…</div>');
        $.getJSON(adrestanimUrl('ajaxExternalFill', 'parent_id=' + encodeURIComponent(parentId) + '&tip=' + encodeURIComponent(childTip)))
            .done(function (res) {
                if (typeof toastr !== 'undefined') {
                    if (res.durum === 'tamam') {
                        var n = typeof res.api_kayit === 'number' ? res.api_kayit : 0;
                        if (n > 0) toastr.info("API'den " + n + " yeni kayıt eklendi.", 'Adres senkronu', { timeOut: 4500 });
                        if (childTip === 'kapino') {
                            kapinoSyncToast(res);
                        }
                    } else if (res.mesaj) {
                        toastr.warning(res.mesaj, 'Adres senkronu', { timeOut: 7000 });
                    }
                }
            })
            .fail(function () {
                if (typeof toastr !== 'undefined') toastr.error('Dış servis yanıt vermedi; yerel liste yüklenecek.', 'Adres senkronu', { timeOut: 6000 });
            })
            .always(function () { yukle(childTip, parentId); });
    }

    function yukle(tip, ustId, skipGeocode) {
        var u = tip === 'ilce' ? '0' : ustId;
        $.getJSON(adrestanimUrl('ajaxList', 'tip=' + encodeURIComponent(tip) + '&ust_id=' + encodeURIComponent(u)))
            .done(function (data) {
                var h = '';
                if (!data || data.length === 0) {
                    h = '<div class="empty-msg">Kayıt yok.</div>';
                } else {
                    $.each(data, function (i, o) {
                        var idAttr = String(o.id).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
                        var adEsc = $('<div>').text(o.adi).html();
                        var coordsVal = (o.coords && String(o.coords).trim()) ? String(o.coords).trim() : '';
                        var coordsAttr = coordsVal ? ' data-coords="' + coordsVal.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '"' : '';
                        var pin = (tip === 'kapino' && coordsVal) ? ' <i class="fa-solid fa-location-dot text-success ms-1" title="Koordinatlı"></i>' : '';
                        var actions = '';
                        if (tip !== 'ilce' || canManageIlce()) {
                            actions = '<div class="hier-actions"><button type="button" class="btn btn-sm btn-link p-0 me-2 btn-edit-item" title="Düzenle"><i class="fa-solid fa-pen text-warning"></i></button><button type="button" class="btn btn-sm btn-link p-0 btn-del-item" title="Sil"><i class="fa-solid fa-trash text-danger"></i></button></div>';
                        }
                        h += '<div class="hier-item item-' + tip + '" data-tip="' + tip + '" data-id="' + idAttr + '"' + coordsAttr + '><span>' + adEsc + pin + '</span>' + actions + '</div>';
                    });
                }
                $('#box-' + tip).html(h);
                if (tip === 'kapino' && !skipGeocode && ustId && data && data.length > 0) {
                    kapinoCoordsTopla(ustId, '', function () {
                        yukle('kapino', ustId, true);
                    });
                }
            })
            .fail(function () { $('#box-' + tip).html('<div class="empty-msg text-danger">Liste yüklenemedi.</div>'); });
    }

    function sec(tip, id) {
        $('.item-' + tip).removeClass('active');
        $('.item-' + tip).filter(function () { return String($(this).data('id')) === String(id); }).addClass('active');
        if (tip === 'ilce') {
            SECILI.ilce = id; $('#adres-hierarchy .search-box[data-tip="mahalle"]').val(''); $('#btn-add-mahalle').prop('disabled', false); yukle('mahalle', id); $('#box-sokak, #box-kapino').html('<div class="empty-msg">Üst birim seçiniz…</div>'); SECILI.mahalle = ''; SECILI.sokak = ''; $('#btn-add-sokak, #btn-add-kapino').prop('disabled', true);
        } else if (tip === 'mahalle') {
            SECILI.mahalle = id; $('#adres-hierarchy .search-box[data-tip="sokak"]').val(''); $('#btn-add-sokak').prop('disabled', false); syncExternalThenYukle('sokak', id); $('#box-kapino').html('<div class="empty-msg">Üst birim seçiniz…</div>'); SECILI.sokak = ''; $('#btn-add-kapino').prop('disabled', true);
        } else if (tip === 'sokak') {
            SECILI.sokak = id; $('#adres-hierarchy .search-box[data-tip="kapino"]').val(''); $('#btn-add-kapino').prop('disabled', false); syncExternalThenYukle('kapino', id);
        }
    }

    function formAc(tip, ustId, id, adi, coords) {
        var turAdi = tipIsimleri[tip] || 'Kayıt';
        $('#f-adres-tip').val(tip); $('#f-adres-id').val(id || ''); $('#f-adres-adi').val(adi || '');
        if (tip === 'kapino') {
            $('#wrap-kapino-coords').removeClass('d-none');
            $('#f-adres-coords').val(coords || '');
        } else {
            $('#wrap-kapino-coords').addClass('d-none');
            $('#f-adres-coords').val('');
        }
        if (id) {
            $('#modal-adres-baslik').html('<i class="fa-solid fa-pen me-1"></i>' + turAdi + ' düzenle'); $('#f-adres-bilgi').text('Düzenlenen: ' + turAdi);
        } else {
            $('#modal-adres-baslik').html('<i class="fa-solid fa-plus me-1"></i>Yeni ' + turAdi);
            if (tip === 'kapino') {
                $('#f-adres-bilgi').text('Yeni kayıt — kayıttan sonra koordinat otomatik aranır.');
            } else {
                $('#f-adres-bilgi').text('Yeni ' + turAdi + ' kaydı.');
            }
            if (tip === 'mahalle') ustId = SECILI.ilce;
            else if (tip === 'sokak') ustId = SECILI.mahalle;
            else if (tip === 'kapino') ustId = SECILI.sokak;
            else if (tip === 'ilce') ustId = '0';
        }
        $('#f-adres-ustid').val(ustId || ''); $('#label-adres-adi').text(turAdi + ' adı');
        var m = getModal(); if (m) m.show();
        setTimeout(function () { $('#f-adres-adi').trigger('focus'); }, 300);
    }

    function anlikFiltrele(tip, metin) {
        var filtre = (metin || '').toLocaleUpperCase('tr-TR');
        var $items = $('#box-' + tip + ' .hier-item');
        $items.each(function () {
            var t = $(this).find('span').first().text().toLocaleUpperCase('tr-TR');
            $(this).toggle(t.indexOf(filtre) > -1);
        });
        $('#box-' + tip + ' .search-empty').remove();
        if ($items.length && $items.filter(':visible').length === 0) $('#box-' + tip).append('<div class="empty-msg search-empty">Sonuç bulunamadı.</div>');
    }

    $(document).ready(function () {
        yukle('ilce', '0');
        $('#adres-hierarchy').on('click', '.hier-item', function (e) {
            if ($(e.target).closest('.hier-actions').length) return;
            sec($(this).data('tip'), $(this).data('id'));
        });
        $('#adres-hierarchy').on('click', '.btn-edit-item', function (e) {
            e.stopPropagation();
            var $row = $(this).closest('.hier-item');
            var tip = $row.data('tip');
            if (tip === 'ilce' && !canManageIlce()) {
                return;
            }
            var ust = tip === 'ilce' ? '0' : (tip === 'mahalle' ? SECILI.ilce : (tip === 'sokak' ? SECILI.mahalle : SECILI.sokak));
            var coords = $row.attr('data-coords') || '';
            var adi = $row.find('span').first().clone().children().remove().end().text().trim();
            formAc(tip, ust, $row.data('id'), adi, coords);
        });
        $('#adres-hierarchy').on('click', '.btn-del-item', function (e) {
            e.stopPropagation();
            var $row = $(this).closest('.hier-item');
            var tip = $row.data('tip');
            if (tip === 'ilce' && !canManageIlce()) {
                return;
            }
            var id = $row.data('id');
            if (!confirm('Silmek istediğinize emin misiniz?')) return;
            var ustList = tip === 'ilce' ? '0' : (tip === 'mahalle' ? SECILI.ilce : (tip === 'sokak' ? SECILI.mahalle : SECILI.sokak));
            $.getJSON(adrestanimUrl('ajaxDelete', 'id=' + encodeURIComponent(id) + '&ust_id=' + encodeURIComponent(ustList)))
                .done(function (res) {
                    if (res.durum === 'tamam') {
                        yukle(tip, ustList);
                        if (tip === 'ilce' && SECILI.ilce === id) {
                            SECILI.ilce = '';
                            $('#box-mahalle, #box-sokak, #box-kapino').html('<div class="empty-msg">Üst birim seçiniz…</div>');
                            $('#btn-add-mahalle, #btn-add-sokak, #btn-add-kapino').prop('disabled', true);
                        }
                    } else alert(res.mesaj || 'Silinemedi.');
                })
                .fail(function () { alert('Sunucu hatası.'); });
        });
        $('#adres-hierarchy').on('click', '.btn-add-tier', function () {
            var tier = $(this).data('tier');
            if (tier === 'ilce' && !canManageIlce()) {
                return;
            }
            if (tier === 'mahalle' && !SECILI.ilce) {
                alert('Mahalle eklemek için önce ilçe seçin.');
                return;
            }
            formAc(tier, '', '', '', '');
        });
        $('#adres-hierarchy').on('input', '.search-box', function () { anlikFiltrele($(this).data('tip'), $(this).val()); });
        $('#btn-adres-geocode').on('click', function () {
            var id = $('#f-adres-id').val();
            if (!id) {
                alert('Önce kapı kaydını kaydedin, sonra koordinat bulun.');
                return;
            }
            var $btn = $(this).prop('disabled', true);
            $.post(adrestanimUrl('ajaxGeocodeKapino'), { id: id }, function (res) {
                if (res.durum === 'tamam' && res.coords) {
                    $('#f-adres-coords').val(res.coords);
                    if (typeof toastr !== 'undefined') {
                        if (res.changed) {
                            toastr.warning('Koordinat güncellendi.', 'TomTom');
                        } else {
                            toastr.success('Koordinat doğrulandı (değişiklik yok).', 'TomTom');
                        }
                    }
                } else {
                    alert(res.mesaj || 'Koordinat bulunamadı.');
                }
            }, 'json').fail(function () { alert('Sunucu hatası.'); }).always(function () { $btn.prop('disabled', false); });
        });
        $('#btn-adres-kaydet').on('click', function () {
            var d = { id: $('#f-adres-id').val(), adi: $('#f-adres-adi').val(), tip: $('#f-adres-tip').val(), ust_id: $('#f-adres-ustid').val() };
            if (d.tip === 'kapino') {
                d.coords = $('#f-adres-coords').val();
            }
            var $saveBtn = $(this).prop('disabled', true);
            $.post(adrestanimUrl('ajaxSave'), d, function (res) {
                if (res.durum === 'tamam') {
                    if (d.tip === 'kapino' && res.coords) {
                        $('#f-adres-coords').val(res.coords);
                        if (typeof toastr !== 'undefined') toastr.success('Kapı kaydedildi, koordinat bulundu.', 'TomTom', { timeOut: 4500 });
                    }
                    var m = getModal(); if (m) m.hide();
                    yukle(d.tip, d.tip === 'ilce' ? '0' : d.ust_id);
                } else alert(res.mesaj || 'Kayıt başarısız.');
            }, 'json').fail(function () { alert('Sunucu hatası.'); }).always(function () { $saveBtn.prop('disabled', false); });
        });
    });
})();
