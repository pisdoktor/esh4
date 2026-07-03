(function () {
    var sel = document.getElementById('plan-tekrar-araligi');
    var num = document.getElementById('plan-tekrar-sayisi');
    var planDate = document.querySelector('.esh-plan-form input[name="planlanantarih_date"]');
    var bannerEl = document.getElementById('plan-doluluk-banner');
    var warningEl = document.getElementById('plan-doluluk-uyari');
    var zamanInputs = document.querySelectorAll('.esh-plan-form input[name="zaman"]');
    var planForm = document.querySelector('.esh-plan-form');
    var overlapAlertEl = document.getElementById('plan-overlap-alert');
    var warningsBannerEl = document.getElementById('plan-warnings-banner');
    var submitBtn = planForm ? planForm.querySelector('button[type="submit"]') : null;
    var overlapBlocked = false;
    var skipOverlapSubmit = false;

    var YOK = 'yok';

    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function syncWarningsBanner() {
        if (!warningsBannerEl) {
            return;
        }
        var show = overlapAlertEl && !overlapAlertEl.classList.contains('d-none');
        if (show) {
            warningsBannerEl.classList.remove('d-none');
        } else {
            warningsBannerEl.classList.add('d-none');
        }
    }

    function getTc() {
        if (!planForm) {
            return '';
        }
        var inp = planForm.querySelector('input[name="hastatckimlik"]');
        return inp ? String(inp.value || '').trim() : '';
    }

    function getExcludePlanId() {
        if (!planForm) {
            return 0;
        }
        var raw = planForm.getAttribute('data-exclude-plan-id');
        var n = parseInt(raw, 10);
        return isNaN(n) || n < 1 ? 0 : n;
    }

    function getSelectedYapilacakIds() {
        if (!planForm) {
            return [];
        }
        var select = planForm.querySelector('select[name="yapilacak[]"]');
        if (!select) {
            return [];
        }
        var ids = [];
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].selected) {
                var v = parseInt(select.options[i].value, 10);
                if (!isNaN(v) && v > 0) {
                    ids.push(v);
                }
            }
        }
        return ids;
    }

    function getSelectedZaman() {
        var selected = planForm
            ? planForm.querySelector('input[name="zaman"]:checked')
            : document.querySelector('input[name="zaman"]:checked');
        if (!selected) {
            return null;
        }
        var z = parseInt(selected.value, 10);
        return isNaN(z) ? null : z;
    }

    function setSubmitEnabled(enabled) {
        if (!submitBtn) {
            return;
        }
        submitBtn.disabled = !enabled;
        submitBtn.setAttribute('aria-disabled', enabled ? 'false' : 'true');
    }

    function showOverlapAlert(overlapCount, tarihLabel, overlapNames, overlapZamanLabel) {
        overlapBlocked = !!(overlapCount && overlapCount >= 1);
        setSubmitEnabled(!overlapBlocked);
        if (!overlapAlertEl) {
            syncWarningsBanner();
            return;
        }
        if (!overlapCount || overlapCount < 1) {
            overlapAlertEl.innerHTML = '';
            overlapAlertEl.classList.add('d-none');
            syncWarningsBanner();
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
            ' işlemi zaten planlanmış. Aynı gün ve aynı zaman diliminde aynı işlemden tekrar plan oluşturulamaz.';
        overlapAlertEl.innerHTML =
            '<i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i><span>' +
            escHtml(full) +
            '</span>';
        overlapAlertEl.classList.remove('d-none');
        syncWarningsBanner();
    }

    function overlapCheckUrl(trRaw) {
        var tc = getTc();
        var params = { tc: tc, tarih: trRaw };
        var ids = getSelectedYapilacakIds();
        if (ids.length) {
            params.yapilacak = ids.join(',');
        }
        var z = getSelectedZaman();
        if (z !== null) {
            params.zaman = z;
        }
        var excludeId = getExcludePlanId();
        if (excludeId > 0) {
            params.exclude_plan_id = excludeId;
        }
        return eshUrl('PlannedVisit', 'checkPlanSameSlot', params);
    }

    function fetchOverlapCheck(trRaw) {
        if (!trRaw || !getTc()) {
            return Promise.resolve({ overlapCount: 0, overlapNames: '', overlapZamanLabel: '' });
        }
        if (!getSelectedYapilacakIds().length || getSelectedZaman() === null) {
            return Promise.resolve({ overlapCount: 0, overlapNames: '', overlapZamanLabel: '' });
        }
        return fetch(overlapCheckUrl(trRaw), {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (data && data.ok) {
                    var overlapCount =
                        typeof data.overlapCount === 'number' ? data.overlapCount : 0;
                    return {
                        overlapCount: overlapCount,
                        overlapNames:
                            typeof data.overlapNames === 'string' ? data.overlapNames : '',
                        overlapZamanLabel:
                            typeof data.overlapZamanLabel === 'string'
                                ? data.overlapZamanLabel
                                : '',
                    };
                }
                return { overlapCount: 0, overlapNames: '', overlapZamanLabel: '' };
            })
            .catch(function () {
                return { overlapCount: 0, overlapNames: '', overlapZamanLabel: '' };
            });
    }

    var overlapDebounceT = null;
    function scheduleOverlapCheck() {
        if (!planDate) {
            return;
        }
        var raw = String(planDate.value || '').trim();
        clearTimeout(overlapDebounceT);
        overlapDebounceT = setTimeout(function () {
            if (!raw) {
                showOverlapAlert(0, '', '', '');
                return;
            }
            fetchOverlapCheck(raw).then(function (res) {
                showOverlapAlert(
                    res.overlapCount,
                    raw,
                    res.overlapNames,
                    res.overlapZamanLabel
                );
            });
        }, 350);
    }

    if (planForm && overlapAlertEl) {
        var yapilacakSelect = planForm.querySelector('select[name="yapilacak[]"]');
        if (yapilacakSelect) {
            yapilacakSelect.addEventListener('change', scheduleOverlapCheck);
        }
        planForm.querySelectorAll('input[name="zaman"]').forEach(function (r) {
            r.addEventListener('change', scheduleOverlapCheck);
        });
        if (planDate) {
            planDate.addEventListener('change', scheduleOverlapCheck);
            planDate.addEventListener('blur', scheduleOverlapCheck);
            if (window.jQuery && typeof window.jQuery.fn.datepicker === 'function') {
                window.jQuery(planDate).on('changeDate', scheduleOverlapCheck);
            }
            scheduleOverlapCheck();
        }
        planForm.addEventListener('submit', function (ev) {
            if (skipOverlapSubmit) {
                skipOverlapSubmit = false;
                return;
            }
            if (!overlapBlocked) {
                return;
            }
            ev.preventDefault();
            var raw = planDate ? String(planDate.value || '').trim() : '';
            fetchOverlapCheck(raw).then(function (res) {
                if (res.overlapCount >= 1) {
                    showOverlapAlert(
                        res.overlapCount,
                        raw,
                        res.overlapNames,
                        res.overlapZamanLabel
                    );
                    if (typeof window.toastr !== 'undefined' && window.toastr.error) {
                        window.toastr.error(
                            'Aynı gün ve zaman diliminde bu işlem zaten planlanmış.'
                        );
                    }
                } else {
                    skipOverlapSubmit = true;
                    planForm.submit();
                }
            });
        });
    }

    if (!sel || !num) {
        return;
    }

    function syncTekrarSayi() {
        if (sel.value === YOK) {
            num.value = '1';
            num.setAttribute('readonly', 'readonly');
            num.classList.add('bg-light');
        } else {
            num.removeAttribute('readonly');
            num.classList.remove('bg-light');
            if (parseInt(num.value, 10) < 2) {
                num.value = '2';
            }
        }
    }

    sel.addEventListener('change', syncTekrarSayi);
    syncTekrarSayi();

    function setBannerVisible(visible) {
        if (!bannerEl) {
            return;
        }
        if (visible) {
            bannerEl.classList.remove('d-none');
            bannerEl.setAttribute('aria-hidden', 'false');
        } else {
            bannerEl.classList.add('d-none');
            bannerEl.setAttribute('aria-hidden', 'true');
        }
    }

    function renderAlert(level, iconClass, title, detail) {
        return (
            '<div class="alert alert-' +
            level +
            ' plan-doluluk-alert mb-0 d-flex align-items-start gap-3 shadow-sm border-start border-4" role="alert">' +
            '<span class="plan-doluluk-alert__icon flex-shrink-0 mt-1"><i class="fa-solid ' +
            iconClass +
            ' fa-lg" aria-hidden="true"></i></span>' +
            '<div class="plan-doluluk-alert__body min-w-0">' +
            '<div class="fw-bold fs-6 mb-1">' +
            title +
            '</div>' +
            (detail ? '<div class="mb-0 opacity-90">' + detail + '</div>' : '') +
            '</div></div>'
        );
    }

    function clearWarning() {
        if (!warningEl) {
            return;
        }
        warningEl.innerHTML = '';
        setBannerVisible(false);
    }

    function showWarning(html) {
        if (!warningEl) {
            return;
        }
        warningEl.innerHTML = html;
        setBannerVisible(true);
        if (bannerEl && typeof bannerEl.scrollIntoView === 'function') {
            bannerEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

    function renderWarning(count) {
        if (!warningEl) {
            return;
        }
        if (count === 0) {
            showWarning(
                renderAlert(
                    'success',
                    'fa-calendar-check',
                    'Bu vakit tamamen müsait',
                    'Seçilen tarih ve zaman diliminde planlı izlem, ilk kayıt randevusu veya pansuman yükü görünmüyor.'
                )
            );
            return;
        }
        if (count < 5) {
            showWarning(
                renderAlert(
                    'info',
                    'fa-circle-info',
                    'Bu vakitte ' + count + ' kayıt var',
                    'Planlı izlem, ilk kayıt randevusu ve pansuman kayıtları toplamı. Planlamaya devam edebilirsiniz.'
                )
            );
            return;
        }
        if (count < 10) {
            showWarning(
                renderAlert(
                    'warning',
                    'fa-triangle-exclamation',
                    'Dikkat: yoğunluk artıyor (' + count + ' kayıt)',
                    'Seçilen vakitte birden fazla planlı izlem veya randevu olabilir. Takvimi kontrol edin.'
                )
            );
            return;
        }
        showWarning(
            renderAlert(
                'danger',
                'fa-circle-xmark',
                'Yoğun vakit (' + count + ' kayıt)',
                'Bu tarih ve zaman diliminde çok sayıda planlı izlem veya randevu var. Mümkünse başka vakit seçin.'
            )
        );
    }

    function selectedZaman() {
        var selected = document.querySelector('input[name="zaman"]:checked');
        if (!selected) {
            return '';
        }
        return selected.value || '';
    }

    function parseTrDateToMs(dateStr) {
        var m = /^(\d{2})-(\d{2})-(\d{4})$/.exec(dateStr || '');
        if (!m) {
            return NaN;
        }
        return new Date(
            parseInt(m[3], 10),
            parseInt(m[2], 10) - 1,
            parseInt(m[1], 10)
        ).getTime();
    }

    function isPastPlanDate(dateStr) {
        var picked = parseTrDateToMs(dateStr);
        if (isNaN(picked)) {
            return false;
        }
        var now = new Date();
        var today = new Date(
            now.getFullYear(),
            now.getMonth(),
            now.getDate()
        ).getTime();
        return picked < today;
    }

    function planYogunlukKontrol() {
        if (!planDate || !warningEl) {
            return;
        }
        var tarih = (planDate.value || '').trim();
        var zaman = selectedZaman();
        if (tarih.length !== 10 || zaman === '') {
            clearWarning();
            return;
        }
        if (isPastPlanDate(tarih)) {
            showWarning(
                renderAlert(
                    'danger',
                    'fa-ban',
                    'Planlama tarihi geçmiş olamaz',
                    'Bugünden önceki bir tarih için plan oluşturulamaz.'
                )
            );
            return;
        }

        showWarning(
            renderAlert(
                'secondary',
                'fa-spinner fa-spin',
                'Yoğunluk kontrol ediliyor…',
                'Seçilen tarih ve vakit için planlı izlem, ilk kayıt ve pansuman sayıları hesaplanıyor.'
            )
        );

        var url =
            eshUrl('PlannedVisit', 'plan_yogunluk_kontrol') +
            '&tarih=' +
            encodeURIComponent(tarih) +
            '&zaman=' +
            encodeURIComponent(zaman);

        fetch(url, { credentials: 'same-origin' })
            .then(function (res) {
                return res.text();
            })
            .then(function (txt) {
                var count = parseInt(txt, 10);
                if (isNaN(count) || count < 0) {
                    count = 0;
                }
                renderWarning(count);
            })
            .catch(function () {
                showWarning(
                    renderAlert(
                        'danger',
                        'fa-triangle-exclamation',
                        'Yoğunluk kontrolü yapılamadı',
                        'Sunucuya ulaşılamadı. Tarih ve vakit seçimini kontrol edip tekrar deneyin.'
                    )
                );
            });
    }

    if (planDate && warningEl) {
        planDate.addEventListener('change', planYogunlukKontrol);
        planDate.addEventListener('blur', planYogunlukKontrol);
        for (var i = 0; i < zamanInputs.length; i++) {
            zamanInputs[i].addEventListener('change', planYogunlukKontrol);
        }
        planYogunlukKontrol();
    }

    var storeForm = document.querySelector(
        'form.esh-plan-form[action*="controller=PlannedVisit&action=store"]'
    );
    if (storeForm && planDate) {
        storeForm.addEventListener('submit', function (e) {
            if (skipOverlapSubmit) {
                return;
            }
            var tarih = (planDate.value || '').trim();
            if (isPastPlanDate(tarih)) {
                e.preventDefault();
                showWarning(
                    renderAlert(
                        'danger',
                        'fa-ban',
                        'Planlama tarihi geçmiş olamaz',
                        'Bugünden önceki bir tarih için plan oluşturulamaz.'
                    )
                );
                if (typeof window.toastr !== 'undefined' && window.toastr.error) {
                    window.toastr.error('Planlama tarihi geçmiş bir tarih olamaz.');
                }
            }
        });
    }

    if (window.jQuery && typeof window.jQuery.fn.datepicker === 'function' && planDate) {
        window.jQuery(function () {
            var $planDate = window.jQuery(planDate);
            if (!$planDate.length) {
                return;
            }
            if (typeof window.eshPrepareDatepickerInputs === 'function') {
                window.eshPrepareDatepickerInputs(planDate.parentNode || document);
            }
            if (!$planDate.data('datepicker')) {
                $planDate.datepicker({
                    format: 'dd-mm-yyyy',
                    language: 'tr',
                    autoclose: true,
                    todayHighlight: true,
                    forceParse: false,
                });
            }
            $planDate.datepicker('setStartDate', '+0d');
            $planDate.on('changeDate', planYogunlukKontrol);
        });
    }
})();
