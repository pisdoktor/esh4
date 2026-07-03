/**
 * Branş randevu takvimi — hasta arama (RandevuController::patientSearch).
 */
(function ($) {
    'use strict';

    function esc(s) {
        return $('<div>').text(s).html();
    }

    function formatKotaDate(ymd) {
        if (!ymd) {
            return '';
        }
        var p = String(ymd).split('-');
        if (p.length !== 3) {
            return String(ymd);
        }
        var months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
        var mi = parseInt(p[1], 10) - 1;
        var di = parseInt(p[2], 10);
        if (mi < 0 || mi > 11 || !di) {
            return String(ymd);
        }
        return di + ' ' + months[mi] + ' ' + p[0];
    }

    function buildKotaHtml(info, dateYmd) {
        if (!info) {
            return (
                '<div class="esh-randevu-kota esh-randevu-kota--empty" role="status">' +
                '<i class="fa-solid fa-circle-info me-2" aria-hidden="true"></i>' +
                '<span>Kota bilgisi bulunamadı.</span></div>'
            );
        }
        var dateLbl = formatKotaDate(dateYmd);
        var dateHtml = dateLbl ? '<span class="esh-randevu-kota__date">' + esc(dateLbl) + '</span>' : '';

        if (info.unlimited) {
            var usedU = parseInt(String(info.used || 0), 10) || 0;
            return (
                '<div class="esh-randevu-kota esh-randevu-kota--unlimited" role="status">' +
                '<div class="esh-randevu-kota__head">' +
                '<span class="esh-randevu-kota__icon" aria-hidden="true"><i class="fa-solid fa-infinity"></i></span>' +
                '<div class="esh-randevu-kota__head-text">' +
                '<span class="esh-randevu-kota__title">Günlük hasta kotası</span>' + dateHtml +
                '</div>' +
                '<span class="esh-randevu-kota__pill">Sınırsız</span>' +
                '</div>' +
                '<div class="esh-randevu-kota__hero">' +
                '<span class="esh-randevu-kota__hero-val">' + esc(String(usedU)) + '</span>' +
                '<span class="esh-randevu-kota__hero-lbl">Bu gün kayıtlı hasta</span>' +
                '</div>' +
                '<p class="esh-randevu-kota__hint mb-0">Bu branş için günlük hasta limiti tanımlı değil.</p>' +
                '</div>'
            );
        }

        var usedNum = parseInt(String(info.used || 0), 10) || 0;
        var kotaNum = parseInt(String(info.kota || 0), 10) || 0;
        var rem = info.remaining != null ? parseInt(String(info.remaining), 10) : Math.max(0, kotaNum - usedNum);
        var tone = info.full ? 'danger' : (rem <= 2 ? 'warning' : 'success');
        var pct = kotaNum > 0 ? Math.min(100, Math.round((usedNum / kotaNum) * 100)) : 0;
        var pillText = info.full ? 'Kota dolu' : (rem + ' kalan');
        var pillIcon = info.full ? 'fa-ban' : (rem <= 2 ? 'fa-triangle-exclamation' : 'fa-check');
        var noteHtml = info.full
            ? '<p class="esh-randevu-kota__alert"><i class="fa-solid fa-circle-xmark me-1" aria-hidden="true"></i>Günlük kota dolu; yeni hasta eklenemez.</p>'
            : '';

        return (
            '<div class="esh-randevu-kota esh-randevu-kota--' + tone + '" role="status">' +
            '<div class="esh-randevu-kota__head">' +
            '<span class="esh-randevu-kota__icon" aria-hidden="true"><i class="fa-solid fa-user-group"></i></span>' +
            '<div class="esh-randevu-kota__head-text">' +
            '<span class="esh-randevu-kota__title">Günlük hasta kotası</span>' + dateHtml +
            '</div>' +
            '<span class="esh-randevu-kota__pill"><i class="fa-solid ' + pillIcon + ' me-1" aria-hidden="true"></i>' + esc(pillText) + '</span>' +
            '</div>' +
            '<div class="esh-randevu-kota__meter" aria-hidden="true">' +
            '<div class="esh-randevu-kota__meter-fill" style="width:' + pct + '%"></div>' +
            '</div>' +
            '<div class="esh-randevu-kota__stats">' +
            '<div class="esh-randevu-kota__stat"><span class="esh-randevu-kota__stat-val">' + esc(String(usedNum)) + '</span><span class="esh-randevu-kota__stat-lbl">Kayıtlı</span></div>' +
            '<div class="esh-randevu-kota__stat"><span class="esh-randevu-kota__stat-val">' + esc(String(kotaNum)) + '</span><span class="esh-randevu-kota__stat-lbl">Kota</span></div>' +
            '<div class="esh-randevu-kota__stat"><span class="esh-randevu-kota__stat-val">' + esc(String(rem)) + '</span><span class="esh-randevu-kota__stat-lbl">Kalan</span></div>' +
            '</div>' +
            '<p class="esh-randevu-kota__ratio mb-0"><strong>' + esc(String(usedNum)) + '</strong> / ' + esc(String(kotaNum)) + ' hasta</p>' +
            noteHtml +
            '</div>'
        );
    }

    function searchResultHtml(it) {
        var label = String(it.label || it.tckimlik || '');
        var adres = String(it.adres || '').trim();
        var html = esc(label);
        if (adres) {
            html += ' <span class="text-muted small">' + esc(adres) + '</span>';
        }
        return html;
    }

    function initRandevuNewForm() {
        var $brans = $('#esh-randevu-brans');
        var $kotaBox = $('#esh-randevu-brans-kota');
        var $formNew = $('#esh-randevu-new-form');
        var $q = $('#esh-randevu-patient-q');
        var $tc = $('#esh-randevu-hastatckimlik');
        if (!$formNew.length) {
            return;
        }

        var kotaCfg = { date: '', branches: {} };
        try {
            var cfgEl = document.getElementById('esh-randevu-kota-config');
            if (cfgEl && cfgEl.textContent) {
                kotaCfg = JSON.parse(cfgEl.textContent);
            }
        } catch (e) {
            kotaCfg = { date: '', branches: {} };
        }

        function setFormLocked(locked) {
            $formNew.find('input, select, textarea, button').not('[name="y"], [name="m"], [name="randevu_tarihi"]').each(function () {
                var el = this;
                if (el.tagName === 'SELECT' && el.tomselect) {
                    if (locked) {
                        el.tomselect.disable();
                    } else {
                        el.tomselect.enable();
                    }
                }
                $(el).prop('disabled', locked);
            });
            if ($q.length && !$q.prop('disabled')) {
                $q.prop('disabled', locked);
            }
        }

        function renderKota(bransId) {
            if (!$kotaBox.length) {
                return;
            }
            var id = parseInt(String(bransId || ''), 10);
            if (!id) {
                $kotaBox.empty();
                setFormLocked(false);
                return;
            }
            var info = (kotaCfg.branches && kotaCfg.branches[id]) ? kotaCfg.branches[id] : null;
            $kotaBox.html(buildKotaHtml(info, kotaCfg.date || ''));
            setFormLocked(!!(info && info.full));
        }

        function onBransChange() {
            var val = $brans[0] && $brans[0].tomselect ? $brans[0].tomselect.getValue() : $brans.val();
            renderKota(val);
        }

        if ($brans.length) {
            $brans.on('change', onBransChange);
            setTimeout(onBransChange, 0);
        }

        $('form[action*="Randevu&action=store"]').on('submit', function () {
            var bransId = String(($brans[0] && $brans[0].tomselect ? $brans[0].tomselect.getValue() : $brans.val()) || '');
            var info = (kotaCfg.branches && bransId) ? kotaCfg.branches[parseInt(bransId, 10)] : null;
            if (info && info.full) {
                if (window.toastr) {
                    window.toastr.error('Bu branş için günlük hasta kotası dolu.');
                } else {
                    alert('Bu branş için günlük hasta kotası dolu.');
                }
                return false;
            }
            if (!$tc.length) {
                return true;
            }
            var tc = String($tc.val() || '').replace(/\D/g, '');
            if (tc.length !== 11 && $q.length) {
                var manual = String($q.val() || '').replace(/\D/g, '');
                if (manual.length === 11) {
                    tc = manual;
                    $tc.val(tc);
                }
            }
            if (tc.length !== 11) {
                if (window.toastr) {
                    window.toastr.error('Listeden hasta seçin veya geçerli TC girin.');
                } else {
                    alert('Listeden hasta seçin veya geçerli TC girin.');
                }
                return false;
            }
            $tc.val(tc);
            return true;
        });
    }

    $(function () {
        setTimeout(initRandevuNewForm, 0);

        var dayTbody = document.getElementById('esh-randevu-day-tbody');
        if (dayTbody) {
            var dayUrl = dayTbody.getAttribute('data-esh-fetch-url');
            if (dayUrl) {
                fetch(dayUrl, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function (res) {
                    if (!res.ok) {
                        throw new Error('Gün listesi yüklenemedi.');
                    }
                    return res.json();
                }).then(function (data) {
                    if (data && data.ok && typeof data.html === 'string') {
                        dayTbody.innerHTML = data.html;
                    }
                }).catch(function () {
                    dayTbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4 small">Liste yüklenemedi.</td></tr>';
                });
            }
        }

        var $q = $('#esh-randevu-patient-q');
        var $list = $('#esh-randevu-patient-results');
        var $tc = $('#esh-randevu-hastatckimlik');
        var $picked = $('#esh-randevu-patient-picked');
        if (!$q.length || !$list.length || !$tc.length) {
            return;
        }

        var t = null;

        var tc0 = String($tc.val() || '').replace(/\D/g, '');
        var preLab = $tc.attr('data-esh-prefill-label');
        if (tc0.length === 11 && preLab && $picked.length) {
            $picked.html('<i class="fa-solid fa-check me-1"></i>Seçildi: <strong>' + esc(String(preLab)) + '</strong>');
        }

        function hideList() {
            $list.empty().hide();
        }

        function pick(tc, label) {
            $tc.val(tc);
            if ($picked.length) {
                $picked.html('<i class="fa-solid fa-check me-1"></i>Seçildi: <strong>' + esc(label) + '</strong>');
            }
            hideList();
            $q.val('');
        }

        $list.on('click', 'button[data-tc]', function () {
            var $b = $(this);
            pick(String($b.data('tc')), String($b.data('label')));
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#esh-randevu-patient-results, #esh-randevu-patient-q').length) {
                hideList();
            }
        });

        $q.on('input', function () {
            clearTimeout(t);
            var v = String($q.val() || '').trim();
            if (v.length < 2) {
                hideList();
                return;
            }
            t = setTimeout(function () {
                $.getJSON(eshUrl('Randevu', 'patientSearch', { q: v }))
                    .done(function (items) {
                        if (!Array.isArray(items) || !items.length) {
                            hideList();
                            return;
                        }
                        var html = items.map(function (it) {
                            var tc = String(it.tckimlik || '');
                            var label = String(it.label || tc);
                            return '<button type="button" class="list-group-item list-group-item-action py-2 text-start" data-tc="' +
                                esc(tc) + '" data-label="' + esc(label) + '">' + searchResultHtml(it) + '</button>';
                        }).join('');
                        $list.html(html).show();
                    })
                    .fail(function () {
                        hideList();
                    });
            }, 280);
        });
    });
})(jQuery);
