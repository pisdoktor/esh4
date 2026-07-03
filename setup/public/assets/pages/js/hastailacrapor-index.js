/**
 * Tanı raporu: tek modal, filtre, collapse + datepicker.
 * İlaçlar: düzenleme modalı + ilaç adı öneri listesi (JSON).
 */
jQuery(function ($) {
    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function fetchListTbody(tbody, onDone) {
        if (!tbody) {
            return;
        }
        var url = tbody.getAttribute('data-esh-fetch-url');
        if (!url) {
            return;
        }
        eshFetchListHtml(url).then(function (data) {
            tbody.innerHTML = typeof data.html === 'string' ? data.html : '';
            if (typeof onDone === 'function') {
                onDone(data);
            }
        }).catch(function (err) {
            var col = tbody.querySelector('tr td') ? tbody.querySelector('tr td').getAttribute('colspan') : '6';
            tbody.innerHTML = '<tr><td colspan="' + col + '" class="text-center text-danger py-4">' + escapeHtml(err.message || 'Hata') + '</td></tr>';
        });
    }

    var raporTbody = document.getElementById('esh-hir-rapor-tbody');
    fetchListTbody(raporTbody, function (data) {
        if (data.summary) {
            var s = data.summary;
            var el;
            el = document.getElementById('esh-hir-cnt-raporlu');
            if (el) {
                el.textContent = String(s.cntRaporlu != null ? s.cntRaporlu : 0);
            }
            el = document.getElementById('esh-hir-cnt-expired');
            if (el) {
                el.textContent = String(s.cntExpired != null ? s.cntExpired : 0);
            }
            el = document.getElementById('esh-hir-cnt-expiring');
            if (el) {
                el.textContent = String(s.cntExpiring != null ? s.cntExpiring : 0);
            }
        }
        if (typeof applyRaporFilter === 'function') {
            var activeBtn = document.querySelector('.hastailacrapor-filter-group button.active[data-hastailac-filter]');
            var mode = activeBtn ? activeBtn.getAttribute('data-hastailac-filter') : 'all';
            // applyRaporFilter tanımı aşağıda; fetch tamamlandığında mevcut
            setTimeout(function () {
                $('.hastailacrapor-filter-group button.active[data-hastailac-filter]').each(function () {
                    applyRaporFilter(this.getAttribute('data-hastailac-filter'));
                });
            }, 0);
        }
    });
    fetchListTbody(document.getElementById('esh-hir-ilac-tbody'));

    var ILAC_SUGGEST_MAX = 15;
    var ILAC_SUGGEST_MIN_LEN = 1;
    var ILAC_SUGGEST_DEBOUNCE_MS = 150;

    function escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function trLower(s) {
        return String(s).toLocaleLowerCase('tr-TR');
    }

    /** Arama: ı/i, ş/s vb. farkını yok say (DIASIP ↔ diasip ↔ dıasıp). */
    function foldTrSearch(s) {
        return trLower(String(s || ''))
            .replace(/ı/g, 'i')
            .replace(/ş/g, 's')
            .replace(/ğ/g, 'g')
            .replace(/ü/g, 'u')
            .replace(/ö/g, 'o')
            .replace(/ç/g, 'c');
    }

    function normalizeDrugEntry(item) {
        if (typeof item === 'string') {
            var adOnly = item.trim();
            if (!adOnly) {
                return null;
            }
            return { ad: adOnly, etken_madde: '', recete_turu: '' };
        }
        if (!item || typeof item !== 'object') {
            return null;
        }
        var ad = String(item.ad || item.ilac_adi || '').trim();
        if (!ad) {
            return null;
        }
        return {
            ad: ad,
            etken_madde: String(item.etken_madde || '').trim(),
            recete_turu: String(item.recete_turu || '').trim(),
        };
    }

    function drugSearchHaystack(entry) {
        if (entry._searchHaystack) {
            return entry._searchHaystack;
        }
        entry._searchHaystack =
            foldTrSearch(entry.ad) +
            ' ' +
            foldTrSearch(entry.etken_madde) +
            ' ' +
            foldTrSearch(entry.recete_turu);
        return entry._searchHaystack;
    }

    function drugMetaLine(entry) {
        var parts = [];
        if (entry.etken_madde) {
            parts.push(entry.etken_madde);
        }
        if (entry.recete_turu) {
            parts.push(entry.recete_turu);
        }
        return parts.join(' · ');
    }

    function ilacMetaScope($wrap) {
        var $form = $wrap.closest('form');
        return $form.length ? $form : $wrap;
    }

    function setIlacMetaFields($wrap, entry) {
        var $scope = ilacMetaScope($wrap);
        $scope.find('input[name="recete_turu"]').val(entry ? entry.recete_turu : '');
    }

    function initIlacAdiAutocomplete() {
        var $root = $('.hastailacrapor-index').first();
        var listUrl = String($root.attr('data-ilac-listesi-url') || '').trim();
        if (!listUrl || !$root.length) {
            return;
        }

        var drugList = null;
        var loadPromise = null;

        function loadDrugList() {
            if (drugList) {
                return $.Deferred().resolve(drugList).promise();
            }
            if (loadPromise) {
                return loadPromise;
            }
            loadPromise = $.getJSON(listUrl)
                .then(function (data) {
                    var seen = {};
                    var out = [];
                    if (!Array.isArray(data)) {
                        drugList = [];
                        return drugList;
                    }
                    data.forEach(function (item) {
                        var entry = normalizeDrugEntry(item);
                        if (!entry) {
                            return;
                        }
                        var key = foldTrSearch(entry.ad);
                        if (seen[key]) {
                            return;
                        }
                        seen[key] = true;
                        drugSearchHaystack(entry);
                        out.push(entry);
                    });
                    out.sort(function (a, b) {
                        return a.ad.localeCompare(b.ad, 'tr', { sensitivity: 'base' });
                    });
                    drugList = out;
                    return drugList;
                })
                .fail(function () {
                    drugList = [];
                    return drugList;
                });
            return loadPromise;
        }

        function filterDrugs(query) {
            if (!drugList || !drugList.length) {
                return [];
            }
            var q = foldTrSearch(String(query || '').trim());
            if (!q) {
                return [];
            }
            var matches = [];
            var i;
            for (i = 0; i < drugList.length; i++) {
                if (drugSearchHaystack(drugList[i]).indexOf(q) !== -1) {
                    matches.push(drugList[i]);
                    if (matches.length >= ILAC_SUGGEST_MAX) {
                        break;
                    }
                }
            }
            return matches;
        }

        function hideSuggest($list, $input) {
            $list.empty().attr('hidden', 'hidden').hide();
            $input.attr('aria-expanded', 'false');
        }

        function showSuggest($list, $input, items) {
            if (!items.length) {
                hideSuggest($list, $input);
                return;
            }
            var html = items.map(function (entry) {
                var meta = drugMetaLine(entry);
                var metaHtml = meta
                    ? '<span class="d-block small text-muted hastailacrapor-ilac-suggest__meta">' + escHtml(meta) + '</span>'
                    : '';
                return '<button type="button" class="list-group-item list-group-item-action py-2 text-start hastailacrapor-ilac-suggest__item"' +
                    ' data-ilac-name="' + escHtml(entry.ad) + '"' +
                    ' data-recete-turu="' + escHtml(entry.recete_turu) + '">' +
                    escHtml(entry.ad) + metaHtml + '</button>';
            }).join('');
            $list.html(html).removeAttr('hidden').show();
            $input.attr('aria-expanded', 'true');
        }

        function bindIlacInput($input) {
            var $wrap = $input.closest('.hastailacrapor-ilac-adi-wrap');
            var $list = $wrap.find('.hastailacrapor-ilac-suggest').first();
            if (!$list.length) {
                return;
            }

            var debounceT = null;

            function pick(entry) {
                $input.val(entry.ad);
                setIlacMetaFields($wrap, entry);
                hideSuggest($list, $input);
                $input.trigger('change');
            }

            $list.on('mousedown', 'button[data-ilac-name]', function (e) {
                e.preventDefault();
            });

            $list.on('click', 'button[data-ilac-name]', function () {
                var $btn = $(this);
                pick({
                    ad: String($btn.attr('data-ilac-name') || ''),
                    recete_turu: String($btn.attr('data-recete-turu') || ''),
                });
            });

            $input.on('input', function () {
                clearTimeout(debounceT);
                var v = String($input.val() || '');
                if (v.trim().length < ILAC_SUGGEST_MIN_LEN) {
                    hideSuggest($list, $input);
                    return;
                }
                debounceT = setTimeout(function () {
                    if (!drugList) {
                        return;
                    }
                    showSuggest($list, $input, filterDrugs(v));
                }, ILAC_SUGGEST_DEBOUNCE_MS);
            });

            $input.on('keydown', function (e) {
                if (e.key === 'Escape') {
                    hideSuggest($list, $input);
                }
            });

            $input.on('blur', function () {
                setTimeout(function () {
                    hideSuggest($list, $input);
                }, 180);
            });
        }

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.hastailacrapor-ilac-adi-wrap').length) {
                $('.hastailacrapor-ilac-suggest').each(function () {
                    var $list = $(this);
                    var $input = $list.closest('.hastailacrapor-ilac-adi-wrap').find('.hastailacrapor-ilac-adi-input').first();
                    if ($input.length) {
                        hideSuggest($list, $input);
                    }
                });
            }
        });

        loadDrugList().done(function () {
            $root.find('input[name="ilac_adi"]').each(function () {
                bindIlacInput($(this));
            });
        });
    }

    initIlacAdiAutocomplete();

    var opts = {
        format: 'dd-mm-yyyy',
        language: 'tr',
        autoclose: true,
        todayHighlight: true,
        forceParse: false,
    };

    function initHastailacModalDatepickers($modal) {
        if (typeof window.eshPrepareDatepickerInputs === 'function') {
            window.eshPrepareDatepickerInputs($modal[0] || $modal);
        }
        $modal.find('.datepicker').each(function () {
            var $el = $(this);
            if ($el.data('datepicker')) {
                $el.datepicker('destroy');
            }
            $el.datepicker(opts);
        });
    }

    function syncHastailacRaporDetay($modal) {
        var $det = $modal.find('.hastailacrapor-rapor-detay');
        if (!$det.length || typeof bootstrap === 'undefined' || !bootstrap.Collapse) {
            return;
        }
        var show = $modal.find('input[name="rapor"][value="1"]').prop('checked');
        var inst = bootstrap.Collapse.getOrCreateInstance($det[0], { toggle: false });
        if (show) {
            inst.show();
        } else {
            inst.hide();
        }
        var $evet = $modal.find('input[name="rapor"][value="1"]');
        if ($evet.attr('aria-controls')) {
            $evet.attr('aria-expanded', show ? 'true' : 'false');
        }
    }

    function applyBransSelection($modal, csv) {
        var ids = {};
        if (csv) {
            String(csv).split(',').forEach(function (part) {
                var n = parseInt(part, 10);
                if (n > 0) {
                    ids[n] = true;
                }
            });
        }
        $modal.find('.hastailacrapor-brans-cb').each(function () {
            var $cb = $(this);
            var val = parseInt($cb.val(), 10);
            $cb.prop('checked', !!ids[val]);
        });
    }

    function updateRaporStatusBadge($btn) {
        var badge = String($btn.attr('data-status-badge') || 'secondary');
        var text = String($btn.attr('data-status-text') || '').trim();
        var $el = $('#hastailacRaporModalStatusBadge');
        if (!text) {
            $el.attr('hidden', 'hidden').removeClass().addClass('badge hastailacrapor-modal-status-badge');
            return;
        }
        $el.removeAttr('hidden')
            .attr('class', 'badge bg-' + badge + ' hastailacrapor-modal-status-badge')
            .text(text);
    }

    function filterBransInModal($modal, query) {
        var q = trLower(String(query || '').trim());
        var visible = 0;
        $modal.find('.hastailacrapor-brans-picker__item').each(function () {
            var $item = $(this);
            var label = trLower(String($item.attr('data-brans-label') || ''));
            var show = !q || label.indexOf(q) !== -1;
            $item.toggleClass('hastailacrapor-brans-picker__item--hidden', !show);
            if (show) {
                visible++;
            }
        });
        var $empty = $modal.find('#hastailacBransFilterEmpty');
        if ($empty.length) {
            if (q && visible === 0) {
                $empty.removeAttr('hidden');
            } else {
                $empty.attr('hidden', 'hidden');
            }
        }
    }

    function resetBransFilter($modal) {
        var $filter = $modal.find('#hastailacBransFilter');
        if ($filter.length) {
            $filter.val('');
        }
        filterBransInModal($modal, '');
    }

    function populateRaporModal($btn) {
        var $modal = $('#hastailacRaporModal');
        var hastalikAdi = String($btn.attr('data-hastalik-adi') || '').trim();
        var raporlu = String($btn.attr('data-rapor')) === '1';

        $('#hastailacRaporModalTitle').text(hastalikAdi || 'Tanı raporu');
        updateRaporStatusBadge($btn);
        $('#hastailacRaporHastalikId').val($btn.attr('data-hastalik-id') || '');
        $('#hastailacRaporRowId').val($btn.attr('data-rapor-id') || '');
        $('#hastailacRaporBitis').val($btn.attr('data-bitis') || '');

        $modal.find('input[name="rapor"][value="1"]').prop('checked', raporlu);
        $modal.find('input[name="rapor"][value="0"]').prop('checked', !raporlu);

        var ry = String($btn.attr('data-raporyeri')) === '1' ? '1' : '0';
        $modal.find('input[name="raporyeri"][value="1"]').prop('checked', ry === '1');
        $modal.find('input[name="raporyeri"][value="0"]').prop('checked', ry !== '1');

        applyBransSelection($modal, $btn.attr('data-brans') || '');
        resetBransFilter($modal);
        syncHastailacRaporDetay($modal);
    }

    function focusRaporModalEntry($modal) {
        var $target = $modal.find('input[name="rapor"]:checked').first();
        if ($target.length) {
            $target.trigger('focus');
        }
    }

    function populateIlacModal($btn) {
        $('#hastailacIlacId').val($btn.data('ilac-id') || '');
        $('#hastailacIlacAdi').val($btn.data('ilac-adi') || '');
        $('#hastailacIlacNot').val($btn.data('not') || '');
        $('#hastailacIlacRecete').val($btn.attr('data-recete-turu') || '');
        var hid = $btn.data('hastalikid');
        $('#hastailacIlacTani').val(hid !== undefined && hid !== '' ? String(hid) : '');
    }

    /** HTML data-* values; avoid jQuery .data() (coerces "0"/"1" to numbers and breaks ===). */
    function normalizeRaporFilterMode(mode) {
        var s = mode === null || mode === undefined ? '' : String(mode);
        return s === 'all' || s === '1' || s === '0' ? s : 'all';
    }

    function applyRaporFilter(mode) {
        var filterMode = normalizeRaporFilterMode(mode);
        $('.hastailacrapor-rapor-row').each(function () {
            var raporlu = this.getAttribute('data-raporlu');
            raporlu = raporlu === '1' ? '1' : '0';
            var show = filterMode === 'all' || raporlu === filterMode;
            $(this).toggle(show);
        });
    }

    $(document).on('click', '.hastailacrapor-edit-rapor-btn', function () {
        populateRaporModal($(this));
    });

    $(document).on('click', '.hastailacrapor-edit-ilac-btn', function () {
        populateIlacModal($(this));
    });

    $(document).on('change', '.hastailacrapor-edit-modal input[name="rapor"]', function () {
        syncHastailacRaporDetay($(this).closest('.hastailacrapor-edit-modal'));
    });

    $(document).on('input', '#hastailacBransFilter', function () {
        filterBransInModal($('#hastailacRaporModal'), $(this).val());
    });

    $(document).on('shown.bs.modal', '.hastailacrapor-edit-modal', function () {
        var $m = $(this);
        initHastailacModalDatepickers($m);
        syncHastailacRaporDetay($m);
        focusRaporModalEntry($m);
    });

    $(document).on('shown.bs.collapse', '.hastailacrapor-rapor-detay', function () {
        initHastailacModalDatepickers($(this).closest('.hastailacrapor-edit-modal'));
    });

    $(document).on('click', '.hastailacrapor-filter-group button[data-hastailac-filter]', function () {
        var $btn = $(this);
        $btn.addClass('active').siblings().removeClass('active');
        applyRaporFilter(this.getAttribute('data-hastailac-filter'));
    });

    if (window.location.hash === '#tab-ilaclar') {
        var tabBtn = document.getElementById('tab-ilaclar-btn');
        if (tabBtn && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(tabBtn).show();
        }
    }
});
