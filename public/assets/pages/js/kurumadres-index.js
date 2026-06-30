(function () {
    var cfg = document.getElementById('esh-kurum-adres-config');
    if (!cfg) {
        return;
    }

    var kurumId = parseInt(cfg.getAttribute('data-kurum-id') || '0', 10);
    var csrfToken = cfg.getAttribute('data-csrf') || '';
    var SECILI = { ilce: '', mahalle: '', sokak: '' };

    function kurumAdresUrl(action, queryString) {
        if (typeof window.eshUrlWithQuery === 'function') {
            return window.eshUrlWithQuery('KurumAdres', action, queryString || '');
        }
        return (typeof eshUrl === 'function' ? eshUrl('KurumAdres', action) : '')
            + (queryString ? '&' + String(queryString).replace(/^&/, '') : '');
    }

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function tipBadge(tip) {
        var map = { ilce: 'İlçe', mahalle: 'Mahalle', sokak: 'Sokak' };
        return map[tip] || tip;
    }

    function yukle(tip, ustId) {
        var u = tip === 'ilce' ? '0' : ustId;
        $.getJSON(kurumAdresUrl('ajaxChildren', 'tip=' + encodeURIComponent(tip) + '&ust_id=' + encodeURIComponent(u)))
            .done(function (res) {
                var data = res && res.items ? res.items : [];
                var h = '';
                if (!data.length) {
                    h = '<div class="empty-msg text-muted small p-3">Kayıt yok.</div>';
                } else {
                    $.each(data, function (i, o) {
                        var idAttr = String(o.id).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
                        var adEsc = escapeHtml(o.adi);
                        h += '<div class="hier-item item-' + tip + ' d-flex justify-content-between align-items-center px-2 py-2 border-bottom" data-tip="' + tip + '" data-id="' + idAttr + '">'
                            + '<span class="esh-kurum-adres-pick flex-grow-1 cursor-pointer">' + adEsc + '</span>'
                            + '<button type="button" class="btn btn-sm btn-outline-success esh-kurum-adres-assign ms-2" data-tip="' + tip + '" data-id="' + idAttr + '" title="Kuruma ata">'
                            + '<i class="fa-solid fa-plus"></i></button></div>';
                    });
                }
                $('#box-' + tip).html(h);
            })
            .fail(function () {
                $('#box-' + tip).html('<div class="empty-msg text-danger small p-3">Liste yüklenemedi.</div>');
            });
    }

    function resetBelow(tip) {
        var order = ['ilce', 'mahalle', 'sokak'];
        var idx = order.indexOf(tip);
        for (var i = idx + 1; i < order.length; i++) {
            var t = order[i];
            SECILI[t] = '';
            $('#box-' + t).html('<div class="empty-msg text-muted small p-3">Üst birim seçiniz…</div>');
            $('.esh-kurum-adres-search[data-tip="' + t + '"]').prop('disabled', true).val('');
        }
    }

    function postAssign(adresId, done) {
        $.post(kurumAdresUrl('ajaxAssign'), {
            kurum_id: kurumId,
            adres_id: adresId,
            csrf_token: csrfToken
        }, function (res) {
            if (res && res.ok && typeof res.html === 'string') {
                $('#esh-kurum-adres-tbody').html(res.html);
                if (typeof toastr !== 'undefined' && res.mesaj) {
                    toastr.success(res.mesaj);
                }
            } else if (typeof toastr !== 'undefined') {
                toastr.error((res && res.error) ? res.error : 'Atama başarısız.');
            }
            if (done) done();
        }, 'json').fail(function () {
            if (typeof toastr !== 'undefined') toastr.error('Sunucu hatası.');
            if (done) done();
        });
    }

    $(document).on('click', '.esh-kurum-adres-pick', function () {
        var $item = $(this).closest('.hier-item');
        var tip = $item.data('tip');
        var id = String($item.data('id') || '');
        if (!tip || !id) return;
        SECILI[tip] = id;
        $item.siblings().removeClass('active bg-primary-subtle');
        $item.addClass('active bg-primary-subtle');
        if (tip === 'ilce') {
            resetBelow('ilce');
            $('.esh-kurum-adres-search[data-tip="mahalle"]').prop('disabled', false);
            yukle('mahalle', id);
        } else if (tip === 'mahalle') {
            resetBelow('mahalle');
            $('.esh-kurum-adres-search[data-tip="sokak"]').prop('disabled', false);
            yukle('sokak', id);
        }
    });

    $(document).on('click', '.esh-kurum-adres-assign', function (e) {
        e.stopPropagation();
        var id = String($(this).data('id') || '');
        if (!id) return;
        var $btn = $(this).prop('disabled', true);
        postAssign(id, function () { $btn.prop('disabled', false); });
    });

    $(document).on('click', '.esh-kurum-adres-unassign', function () {
        var adresId = String($(this).data('adres-id') || '');
        if (!adresId || !window.confirm('Bu adres atamasını kaldırmak istiyor musunuz?')) {
            return;
        }
        var $btn = $(this).prop('disabled', true);
        $.post(kurumAdresUrl('ajaxUnassign'), {
            kurum_id: kurumId,
            adres_id: adresId,
            csrf_token: csrfToken
        }, function (res) {
            if (res && res.ok && typeof res.html === 'string') {
                $('#esh-kurum-adres-tbody').html(res.html);
                if (typeof toastr !== 'undefined' && res.mesaj) toastr.info(res.mesaj);
            } else if (typeof toastr !== 'undefined') {
                toastr.error((res && res.error) ? res.error : 'İşlem başarısız.');
            }
            $btn.prop('disabled', false);
        }, 'json').fail(function () {
            if (typeof toastr !== 'undefined') toastr.error('Sunucu hatası.');
            $btn.prop('disabled', false);
        });
    });

    $(document).on('input', '.esh-kurum-adres-search', function () {
        var q = String($(this).val() || '').toLowerCase();
        var tip = $(this).data('tip');
        $('#box-' + tip + ' .hier-item').each(function () {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(q) !== -1);
        });
    });

    if (kurumId > 0) {
        yukle('ilce', '0');
    }
})();
