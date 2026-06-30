/**
 * Uhds randevu takvimi — hasta arama (UhdsController::patientSearch).
 */
(function ($) {
    'use strict';

    function esc(s) {
        return $('<div>').text(s).html();
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

    function initUhdsNewForm() {
        var $formNew = $('#esh-uhds-new-form');
        var $q = $('#esh-uhds-patient-q');
        var $tc = $('#esh-uhds-hastatckimlik');
        if (!$formNew.length) {
            return;
        }

        $('form[action*="Uhds&action=store"]').on('submit', function () {
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
        setTimeout(initUhdsNewForm, 0);

        var dayTbody = document.getElementById('esh-uhds-day-tbody');
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

        var $q = $('#esh-uhds-patient-q');
        var $list = $('#esh-uhds-patient-results');
        var $tc = $('#esh-uhds-hastatckimlik');
        var $picked = $('#esh-uhds-patient-picked');
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
            if (!$(e.target).closest('#esh-uhds-patient-results, #esh-uhds-patient-q').length) {
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
                $.getJSON(eshUrl('Uhds', 'patientSearch', { q: v }))
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
