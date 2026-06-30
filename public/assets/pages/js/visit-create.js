(function () {
    var form = document.getElementById('form-izlem-create');
    if (!form) {
        return;
    }

    function submitFormWithGeo(f) {
        if (!navigator.geolocation || typeof navigator.geolocation.getCurrentPosition !== 'function') {
            f.submit();
            return;
        }
        var latInput = f.querySelector('input[name="checkin_lat"]');
        var lonInput = f.querySelector('input[name="checkin_lon"]');
        var atInput = f.querySelector('input[name="checkin_at"]');
        var finished = false;
        function finish() {
            if (finished) {
                return;
            }
            finished = true;
            f.submit();
        }
        var timer = window.setTimeout(finish, 5000);
        navigator.geolocation.getCurrentPosition(
            function (pos) {
                window.clearTimeout(timer);
                if (pos && pos.coords && latInput && lonInput) {
                    latInput.value = String(pos.coords.latitude);
                    lonInput.value = String(pos.coords.longitude);
                    if (atInput) {
                        atInput.value = new Date().toISOString();
                    }
                }
                finish();
            },
            function () {
                window.clearTimeout(timer);
                finish();
            },
            { enableHighAccuracy: false, timeout: 4500, maximumAge: 60000 }
        );
    }

    var collapseEl = document.getElementById('izlem-create-neden-collapse');
    if (collapseEl && typeof bootstrap !== 'undefined') {
        var col = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });

        function syncYapildimiNeden() {
            var v = form.querySelector('input[name="yapildimi"]:checked');
            if (v && v.value === '0') {
                col.show();
            } else {
                col.hide();
            }
        }

        form.querySelectorAll('input[name="yapildimi"]').forEach(function (r) {
            r.addEventListener('change', syncYapildimiNeden);
        });

        syncYapildimiNeden();
    }

    var alertEl = document.getElementById('izlem-create-same-day-alert');
    var plannedAlertEl = document.getElementById('izlem-create-planned-alert');
    var bannerEl = document.getElementById('izlem-create-warnings-banner');
    var dateInput = form.querySelector('input[name="izlemtarihi"]');
    var skipDupSubmit = false;
    var dupBlocked = false;
    var dupBlockMsg = 'Seçilen gün ve zaman diliminde bu işlem(ler) için zaten izlem var.';
    var zamanLabels = (function () {
        var slots = (window.ESH_PAGE && window.ESH_PAGE.zamanDilimleri) ? window.ESH_PAGE.zamanDilimleri : null;
        if (slots && slots.length) {
            var map = {};
            slots.forEach(function (sec) {
                map[sec.code] = sec.label;
            });
            return map;
        }
        return { 1: 'Sabah', 2: 'Öğle', 3: 'Akşam' };
    })();
    var futureDateMsg = 'İzlem tarihi bugünden ileri olamaz.';
    var submitBtn = form.querySelector('button[type="submit"]');

    function todayYmdLocal() {
        var t = new Date();
        return t.getFullYear() + '-'
            + String(t.getMonth() + 1).padStart(2, '0') + '-'
            + String(t.getDate()).padStart(2, '0');
    }

    function trDateToYmd(tr) {
        var s = String(tr || '').trim();
        var m = s.match(/^(\d{2})-(\d{2})-(\d{4})$/);
        if (!m) {
            return null;
        }
        return m[3] + '-' + m[2] + '-' + m[1];
    }

    function isFutureIzlemDate(tr) {
        var ymd = trDateToYmd(tr);
        return !!(ymd && ymd > todayYmdLocal());
    }

    function syncIzlemDateValidity() {
        if (!dateInput) {
            return true;
        }
        var raw = String(dateInput.value || '').trim();
        if (raw && isFutureIzlemDate(raw)) {
            dateInput.setCustomValidity(futureDateMsg);
            return false;
        }
        dateInput.setCustomValidity('');
        return true;
    }

    if (dateInput && window.jQuery && typeof window.jQuery.fn.datepicker === 'function') {
        window.jQuery(function () {
            var $d = window.jQuery(dateInput);
            if (!$d.length) {
                return;
            }
            if (typeof window.eshPrepareDatepickerInputs === 'function') {
                window.eshPrepareDatepickerInputs(dateInput.parentNode || document);
            }
            if (!$d.data('datepicker')) {
                var opts = typeof window.eshDatepickerDefaults === 'function'
                    ? window.eshDatepickerDefaults()
                    : { format: 'dd-mm-yyyy', autoclose: true, language: 'tr' };
                $d.datepicker(opts);
            }
            $d.datepicker('setEndDate', new Date());
            $d.on('changeDate change blur', syncIzlemDateValidity);
            syncIzlemDateValidity();
        });
    } else if (dateInput) {
        dateInput.addEventListener('change', syncIzlemDateValidity);
        dateInput.addEventListener('blur', syncIzlemDateValidity);
        syncIzlemDateValidity();
    }

    function escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function clearAlert(el) {
        if (!el) {
            return;
        }
        el.innerHTML = '';
        el.classList.add('d-none');
    }

    function syncWarningBanner() {
        if (!bannerEl) {
            return;
        }
        var dup = alertEl && !alertEl.classList.contains('d-none') && alertEl.innerHTML.trim() !== '';
        var pl = plannedAlertEl && !plannedAlertEl.classList.contains('d-none') && plannedAlertEl.innerHTML.trim() !== '';
        if (dup || pl) {
            var wasHidden = bannerEl.classList.contains('d-none');
            bannerEl.classList.remove('d-none');
            if (wasHidden) {
                try {
                    bannerEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } catch (e) {
                    /* IE yok */
                }
            }
        } else {
            bannerEl.classList.add('d-none');
        }
    }

    function getTc() {
        var h = form.querySelector('input[name="hastatckimlik"]');
        return h ? String(h.value || '').trim() : '';
    }

    function hasPlanId() {
        var inp = form.querySelector('input[name="plan_id"]');
        return !!(inp && String(inp.value || '').trim() !== '');
    }

    function getSelectedYapilanIds() {
        var sel = form.querySelector('select[name="yapilan[]"]');
        if (!sel) {
            return [];
        }
        return Array.prototype.map
            .call(sel.selectedOptions, function (o) {
                return parseInt(o.value, 10);
            })
            .filter(function (id) {
                return id > 0;
            });
    }

    function getSelectedZaman() {
        var r = form.querySelector('input[name="zaman"]:checked');
        if (!r) {
            return null;
        }
        var z = parseInt(r.value, 10);
        return z >= 1 && z <= 3 ? z : null;
    }

    function zamanLabel(z, fromApi) {
        if (fromApi) {
            return fromApi;
        }
        return zamanLabels[z] || '';
    }

    function checkUrl(trRaw) {
        var tc = getTc();
        var params = { tc: tc, tarih: trRaw };
        var ids = getSelectedYapilanIds();
        if (ids.length) {
            params.yapilan = ids.join(',');
        }
        var z = getSelectedZaman();
        if (z !== null) {
            params.zaman = z;
        }
        return eshUrl('Visit', 'checkVisitSameDay', params);
    }

    function fetchCheck(trRaw) {
        if (!trRaw || !getTc()) {
            return Promise.resolve({ overlapCount: 0, overlapNames: '', overlapZamanLabel: '', plannedCount: 0, isToday: false });
        }
        if (!getSelectedYapilanIds().length || getSelectedZaman() === null) {
            return Promise.resolve({ overlapCount: 0, overlapNames: '', overlapZamanLabel: '', plannedCount: 0, isToday: false });
        }
        return fetch(checkUrl(trRaw), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (data && data.ok) {
                    var overlapCount = typeof data.overlapCount === 'number'
                        ? data.overlapCount
                        : (typeof data.count === 'number' ? data.count : 0);
                    return {
                        overlapCount: overlapCount,
                        overlapNames: typeof data.overlapNames === 'string' ? data.overlapNames : '',
                        overlapZamanLabel: typeof data.overlapZamanLabel === 'string' ? data.overlapZamanLabel : '',
                        plannedCount: typeof data.plannedCount === 'number' ? data.plannedCount : 0,
                        isToday: !!data.isToday,
                    };
                }
                return { overlapCount: 0, overlapNames: '', overlapZamanLabel: '', plannedCount: 0, isToday: false };
            })
            .catch(function () {
                return { overlapCount: 0, overlapNames: '', overlapZamanLabel: '', plannedCount: 0, isToday: false };
            });
    }

    function setSubmitEnabled(enabled) {
        if (!submitBtn) {
            return;
        }
        submitBtn.disabled = !enabled;
        submitBtn.setAttribute('aria-disabled', enabled ? 'false' : 'true');
    }

    function showDupAlert(overlapCount, tarihLabel, overlapNames, overlapZamanLabel) {
        dupBlocked = !!(overlapCount && overlapCount >= 1);
        setSubmitEnabled(!dupBlocked);
        if (!alertEl) {
            return;
        }
        if (!overlapCount || overlapCount < 1) {
            clearAlert(alertEl);
            return;
        }
        var zPart = overlapZamanLabel ? overlapZamanLabel + ' diliminde ' : '';
        var islemPart = overlapNames ? ' («' + overlapNames + '»)' : '';
        var full =
            'Seçilen gün (' +
            tarihLabel +
            ') ' +
            zPart +
            'için bu hasta' +
            islemPart +
            ' işlemi zaten kayıtlı. Aynı gün ve aynı zaman diliminde aynı işlemden tekrar izlem girilemez.';
        alertEl.innerHTML =
            '<i class="fa-solid fa-triangle-exclamation me-2 text-danger" aria-hidden="true"></i><span>' +
            escHtml(full) +
            '</span>';
        alertEl.classList.remove('d-none');
    }

    function showPlannedAlert(plannedCount, tarihLabel, isToday) {
        if (!plannedAlertEl) {
            return;
        }
        if (!plannedCount || plannedCount < 1) {
            clearAlert(plannedAlertEl);
            return;
        }
        if (hasPlanId() && isToday) {
            clearAlert(plannedAlertEl);
            return;
        }
        var msg;
        if (isToday) {
            msg =
                plannedCount === 1
                    ? 'Bugün için planlı bir izlem görünüyor.'
                    : 'Bugün için ' + plannedCount + ' adet bekleyen planlı izlem görünüyor.';
        } else {
            msg =
                plannedCount === 1
                    ? 'Seçilen tarihte (' + tarihLabel + ') planlı bir izlem görünüyor.'
                    : 'Seçilen tarihte (' + tarihLabel + ') ' + plannedCount + ' adet bekleyen planlı izlem görünüyor.';
        }
        plannedAlertEl.innerHTML =
            '<i class="fa-solid fa-calendar-check me-2 text-info-emphasis" aria-hidden="true"></i><span>' +
            escHtml(msg) +
            '</span>';
        plannedAlertEl.classList.remove('d-none');
    }

    var debounceT = null;
    function scheduleDupCheck() {
        if (!dateInput) {
            return;
        }
        var raw = String(dateInput.value || '').trim();
        clearTimeout(debounceT);
        debounceT = setTimeout(function () {
            if (!raw) {
                showDupAlert(0, '', '', '');
                showPlannedAlert(0, '', false);
                syncWarningBanner();
                return;
            }
            fetchCheck(raw).then(function (res) {
                showDupAlert(res.overlapCount, raw, res.overlapNames, res.overlapZamanLabel);
                showPlannedAlert(res.plannedCount, raw, res.isToday);
                syncWarningBanner();
            });
        }, 350);
    }

    var yapilanSelect = form.querySelector('select[name="yapilan[]"]');
    if (yapilanSelect) {
        yapilanSelect.addEventListener('change', scheduleDupCheck);
    }

    form.querySelectorAll('input[name="zaman"]').forEach(function (r) {
        r.addEventListener('change', scheduleDupCheck);
    });

    if (dateInput && (alertEl || plannedAlertEl)) {
        if (window.jQuery && typeof window.jQuery.fn.datepicker === 'function') {
            window.jQuery(dateInput).on('changeDate change blur', scheduleDupCheck);
        } else {
            dateInput.addEventListener('change', scheduleDupCheck);
            dateInput.addEventListener('blur', scheduleDupCheck);
        }
        scheduleDupCheck();
    }

    form.addEventListener('submit', function (ev) {
        if (skipDupSubmit) {
            skipDupSubmit = false;
            return;
        }
        ev.preventDefault();
        if (!syncIzlemDateValidity()) {
            if (dateInput) {
                dateInput.reportValidity();
            }
            if (typeof window.toastr !== 'undefined' && window.toastr.error) {
                window.toastr.error(futureDateMsg);
            }
            return;
        }
        var raw = dateInput ? String(dateInput.value || '').trim() : '';
        if (!raw) {
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            skipDupSubmit = true;
            submitFormWithGeo(form);
            return;
        }
        fetchCheck(raw).then(function (res) {
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            if (res.overlapCount >= 1) {
                showDupAlert(res.overlapCount, raw, res.overlapNames, res.overlapZamanLabel);
                syncWarningBanner();
                if (typeof window.toastr !== 'undefined' && window.toastr.error) {
                    var zLbl = zamanLabel(getSelectedZaman(), res.overlapZamanLabel);
                    var extra = [];
                    if (zLbl) {
                        extra.push(zLbl);
                    }
                    if (res.overlapNames) {
                        extra.push(res.overlapNames);
                    }
                    window.toastr.error(
                        extra.length ? dupBlockMsg + ' (' + extra.join(' · ') + ')' : dupBlockMsg
                    );
                }
                return;
            }
            skipDupSubmit = true;
            submitFormWithGeo(form);
        });
    });
})();
