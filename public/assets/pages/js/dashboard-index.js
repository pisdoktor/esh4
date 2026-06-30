function dashZamanSections(apiData) {
    var slots = (apiData && apiData.activeSlots && apiData.activeSlots.length)
        ? apiData.activeSlots
        : ((window.ESH_PAGE && window.ESH_PAGE.zamanDilimleri) ? window.ESH_PAGE.zamanDilimleri : null);
    if (slots && slots.length) {
        return slots.map(function (sec) {
            return {
                key: sec.key,
                label: sec.label,
                icon: sec.icon,
                mod: sec.mod
            };
        });
    }
    return [
        { key: 'sabah', label: 'Sabah', icon: 'fa-sun', mod: 'sabah' },
        { key: 'ogle', label: 'Öğle', icon: 'fa-cloud-sun', mod: 'ogle' },
        { key: 'aksam', label: 'Akşam', icon: 'fa-moon', mod: 'aksam' }
    ];
}

function dashZamanByKey(sections) {
    var map = {};
    (sections || []).forEach(function (sec) {
        if (sec.key === 'sabah') map.sabah = 1;
        if (sec.key === 'ogle') map.ogle = 2;
        if (sec.key === 'aksam') map.aksam = 3;
    });
    return map;
}

$(document).ready(function () {
    const initialDate = (window.ESH_PAGE && window.ESH_PAGE.initialDate) ? window.ESH_PAGE.initialDate : '';
    if (initialDate) {
        getDailyTasks(initialDate);
    }
    initDashboardCalendarMonthAjax();
    initDashboardTcLookup();
    if (dashboardCanMernisScan()) {
        initDailyPlanMernisScan();
    }
    if (dashboardCanDailyPlanSms()) {
        initDailyPlanSmsButton();
    }
});

function initDashboardCalendarMonthAjax() {
    const $container = $('#dashboard-calendar-container');
    const $label = $('#dashboard-calendar-month-label');
    if (!$container.length) return;

    let inFlight = false;
    const loadingHtml = '<div class="py-5 text-center text-muted"><span class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></span>Takvim yükleniyor...</div>';

    function setNavState(prevYear, prevMonth, nextYear, nextMonth) {
        const $prev = $('[data-esh-calendar-nav="prev"]');
        const $next = $('[data-esh-calendar-nav="next"]');
        if ($prev.length) {
            const prevHref = eshUrl('Dashboard', 'index', { year: prevYear, month: prevMonth });
            $prev.attr('href', prevHref).attr('data-year', String(prevYear)).attr('data-month', String(prevMonth));
        }
        if ($next.length) {
            const nextHref = eshUrl('Dashboard', 'index', { year: nextYear, month: nextMonth });
            $next.attr('href', nextHref).attr('data-year', String(nextYear)).attr('data-month', String(nextMonth));
        }
    }

    function loadMonth(year, month, fallbackHref) {
        if (inFlight) return;
        inFlight = true;
        $container.html(loadingHtml);
        $.getJSON(eshUrl('Dashboard', 'calendarMonth', { year: year, month: month }), function (res) {
            if (!res || !res.ok || typeof res.html !== 'string') {
                window.location.href = fallbackHref;
                return;
            }
            $container.html(res.html);
            if ($label.length && typeof res.monthLabel === 'string') {
                $label.text(res.monthLabel);
            }
            setNavState(res.prevYear, res.prevMonth, res.nextYear, res.nextMonth);
        }).fail(function () {
            window.location.href = fallbackHref;
        }).always(function () {
            inFlight = false;
        });
    }

    $(document).on('click', '[data-esh-calendar-nav]', function (e) {
        const href = $(this).attr('href') || eshUrl('Dashboard', 'index');
        const y = parseInt($(this).attr('data-year'), 10);
        const m = parseInt($(this).attr('data-month'), 10);
        if (Number.isNaN(y) || Number.isNaN(m)) {
            return;
        }
        e.preventDefault();
        loadMonth(y, m, href);
    });
}

function genderNameClass(genderRaw) {
    const raw = String(genderRaw || '').trim();
    if (!raw) return 'text-dark';
    const g = raw.replace(/ı/g, 'i').replace(/İ/g, 'I').toUpperCase();
    if (g === '1' || g === 'E' || g === 'ERKEK' || g === 'M') return 'text-primary';
    if (g === '2' || g === 'K' || g === 'KADIN' || g === 'F') return 'text-danger';
    return 'text-dark';
}

function formatTcTcNo(tcRaw) {
    const digits = String(tcRaw || '').replace(/\D/g, '');
    if (digits.length !== 11) return tcRaw || 'TC Yok';
    return `${digits.slice(0, 3)} ${digits.slice(3, 6)} ${digits.slice(6, 9)} ${digits.slice(9, 11)}`;
}

/** Mahalle + planlama bölge no (rota ekranı ile aynı: «Mahalle · 3. bölge») */
function formatMahalleBolge(item) {
    const mahalle = trUpper(item.mahalle != null && item.mahalle !== '' ? item.mahalle : '-');
    const bolge = parseInt(item.bolge, 10);
    if (!Number.isNaN(bolge) && bolge > 0) {
        return `${mahalle} · ${bolge}. bölge`;
    }
    return mahalle;
}

/** Türkçe İ/i kurallarıyla büyük harf (CSS text-uppercase yerine) */
function trUpper(str) {
    return String(str || '').toLocaleUpperCase('tr-TR');
}

/**
 * Günün planı yerleşimi:
 * - shell: sekmeler kaydırma dışında (sabit), yalnızca #daily-events-plan kayar
 * - sticky: sekmeler gövde içinde (eski şablon)
 * - legacy: tek #daily-events-container
 */
function dashPlanSlots() {
    var $tabs = $('#daily-events-tabs');
    var $plan = $('#daily-events-plan');
    var $body = $('#daily-events-body');
    if ($tabs.length && $plan.length && $body.length) {
        var tabsInsideScroll = $body[0] && $tabs[0] && $.contains($body[0], $tabs[0]);
        if (!tabsInsideScroll) {
            return { mode: 'shell', $tabs: $tabs, $plan: $plan, $body: $body };
        }
        return { mode: 'sticky', $tabs: $tabs, $plan: $plan, $body: $body };
    }
    if ($tabs.length && $body.length) {
        return { mode: 'split', $tabs: $tabs, $body: $body };
    }
    var $container = $('#daily-events-container');
    if ($container.length) {
        return { mode: 'legacy', $container: $container };
    }
    return { mode: 'none' };
}

function dashPlanHidePlaceholder() {
    $('#daily-events-placeholder').addClass('d-none');
}

function dashPlanInitTabs(tabListEl) {
    if (!tabListEl || typeof bootstrap === 'undefined' || !bootstrap.Tab) {
        return;
    }
    tabListEl.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (btn) {
        bootstrap.Tab.getOrCreateInstance(btn);
    });
}

function dashPlanSetHtml(html) {
    var slots = dashPlanSlots();
    if (slots.mode === 'shell' || slots.mode === 'sticky') {
        slots.$tabs.addClass('d-none').attr('aria-hidden', 'true').empty();
        slots.$plan.html(html);
        dashPlanHidePlaceholder();
        return;
    }
    if (slots.mode === 'split') {
        slots.$tabs.addClass('d-none').attr('aria-hidden', 'true').empty();
        slots.$body.html(html);
        dashPlanHidePlaceholder();
        return;
    }
    if (slots.mode === 'legacy' && slots.$container.length) {
        slots.$container.html(html);
    }
}

function dashPlanSetPlan(navHtml, bodyHtml) {
    var slots = dashPlanSlots();
    if (slots.mode === 'shell' || slots.mode === 'sticky') {
        slots.$tabs.html(navHtml).removeClass('d-none').attr('aria-hidden', 'false');
        slots.$plan.html(bodyHtml);
        dashPlanHidePlaceholder();
        dashPlanInitTabs(slots.$tabs[0]);
        return;
    }
    if (slots.mode === 'split') {
        slots.$tabs.html(navHtml).removeClass('d-none').attr('aria-hidden', 'false');
        slots.$body.html(bodyHtml);
        dashPlanHidePlaceholder();
        dashPlanInitTabs(slots.$tabs[0]);
        return;
    }
    if (slots.mode === 'legacy' && slots.$container.length) {
        slots.$container.html(navHtml + bodyHtml);
        dashPlanInitTabs(slots.$container.find('#taskTab')[0]);
    }
}

function dashboardCanMernisScan() {
    return !!(window.ESH_PAGE && window.ESH_PAGE.canMernisScan);
}

function updateDailyPlanMernisButton(patientCount) {
    if (!dashboardCanMernisScan()) {
        return;
    }
    const $btn = $('#btn-daily-plan-mernis-scan');
    if (!$btn.length) {
        return;
    }
    const n = parseInt(patientCount, 10) || 0;
    window.ESH_DASHBOARD_MERNIS_PATIENT_COUNT = n;
    if ($btn.data('mernis-scanning') === true) {
        return;
    }
    const hasDate = !!(window.ESH_DASHBOARD_PLAN_DATE || '');
    const canScan = hasDate && n > 0;
    $btn.prop('disabled', !canScan);
    if (!canScan) {
        const title = !hasDate
            ? 'Önce takvimden bir gün seçin'
            : 'Seçili günde MERNİS taranacak planlı hasta yok';
        $btn.attr('title', title).attr('aria-disabled', 'true');
    } else {
        $btn.attr('title', 'Seçili gündeki planlı hastalar için MERNİS vefat taraması (' + n + ' hasta)')
            .removeAttr('aria-disabled');
    }
}

function dashboardCanDrawRoute() {
    return !!(window.ESH_PAGE && window.ESH_PAGE.canDrawRoute);
}

function dashboardCanDailyPlanSms() {
    return !!(window.ESH_PAGE && window.ESH_PAGE.canUseDailyPlanSms);
}

function dashboardSmsSendConfigured() {
    return !!(window.ESH_PAGE && window.ESH_PAGE.smsSendConfigured);
}

function updateDailyPlanSmsButton(patientCount) {
    if (!dashboardCanDailyPlanSms()) {
        return;
    }
    const $btn = $('#btn-daily-plan-sms');
    if (!$btn.length) {
        return;
    }
    const n = parseInt(patientCount, 10) || 0;
    const hasDate = !!(window.ESH_DASHBOARD_PLAN_DATE || '');
    const configured = dashboardSmsSendConfigured();
    const canSend = hasDate && n > 0 && configured;
    const baseUrl = (window.ESH_PAGE && window.ESH_PAGE.dashboardPlanSmsComposeUrl)
        ? window.ESH_PAGE.dashboardPlanSmsComposeUrl
        : eshUrl('Sms', 'compose', { segment: 'gunun_plani' });

    if (canSend) {
        const sep = baseUrl.indexOf('?') >= 0 ? '&' : '?';
        const url = baseUrl + sep + 'tarih=' + encodeURIComponent(window.ESH_DASHBOARD_PLAN_DATE);
        $btn.attr('href', url)
            .removeClass('disabled')
            .removeAttr('aria-disabled tabindex')
            .attr('title', 'Günün planına SMS gönder (' + n + ' hasta)');
    } else {
        $btn.attr('href', '#')
            .addClass('disabled')
            .attr('aria-disabled', 'true')
            .attr('tabindex', '-1');
        let title;
        if (!configured) {
            title = 'SMS ayarları yapılandırılmamış (Kurum ayarları → SMS sekmesi)';
        } else if (!hasDate) {
            title = 'Önce takvimden bir gün seçin';
        } else {
            title = 'Seçili günde planlı hasta yok';
        }
        $btn.attr('title', title);
    }
}

function initDailyPlanSmsButton() {
    if (!dashboardCanDailyPlanSms()) {
        return;
    }
    const $btn = $('#btn-daily-plan-sms');
    if (!$btn.length) {
        return;
    }
    $btn.on('click', function (e) {
        if ($btn.hasClass('disabled') || $btn.attr('aria-disabled') === 'true') {
            e.preventDefault();
        }
    });
    updateDailyPlanSmsButton(0);
}

function getDailyTasks(date) {
    window.ESH_DASHBOARD_PLAN_DATE = date;
    var displayDate = date.split('-').reverse().join('-');
    $('#selected-date-label').text(displayDate);
    if (dashboardCanDrawRoute()) {
        $('#view-route-btn').attr('href', eshUrl('Dashboard', 'showRoute', { date: date }));
        $('#route-button-container').removeClass('d-none');
    } else {
        $('#route-button-container').addClass('d-none');
    }
    updateDailyPlanMernisButton(0);
    updateDailyPlanSmsButton(0);
    dashPlanSetHtml('<div class="p-5 text-center mr-dash-plan__loading"><div class="spinner-border text-primary"></div></div>');

    $.getJSON(eshUrl('Dashboard', 'getDailyEvents', { date: date }), function (data) {
        let sections = dashZamanSections(data);

        const slots = dashPlanSlots();
        const tabPanelStyle = (slots.mode === 'shell' || slots.mode === 'sticky' || slots.mode === 'split')
            ? 'min-height:120px;'
            : 'margin-top:-1px;min-height:200px;';
        const zamanByKey = dashZamanByKey(sections);

        let navHtml = '<ul class="nav nav-tabs nav-fill esh-route-tabs border-bottom-0 gap-2 mb-2" id="taskTab" role="tablist">';
        let contentHtml = '<div class="tab-content border rounded bg-body-secondary shadow-sm esh-task-tab-panels" id="taskTabContent" style="' + tabPanelStyle + '">';
        let hasAnyData = false;

        sections.forEach((sec, index) => {
            let navActive = index === 0 ? 'active' : '';
            let paneShow = index === 0 ? 'show active' : '';
            let rawData = data[sec.key] || {};
            let planliList = rawData.planli || [];
            let pansumanList = rawData.pansuman || [];
            let ilkZiyaretList = rawData.ilkziyaret || [];
            let totalCount = pansumanList.length + planliList.length + ilkZiyaretList.length;
            if (totalCount > 0) hasAnyData = true;

            let badgeClass = 'esh-route-tab-badge rounded-pill ' + (totalCount > 0
                ? 'esh-route-tab-badge--on esh-route-tab-badge--' + sec.mod
                : 'esh-route-tab-badge--empty esh-route-tab-badge--' + sec.mod);

            navHtml += `
                <li class="nav-item" role="presentation">
                    <button class="nav-link esh-route-tab esh-route-tab--${sec.mod} ${navActive}"
                            id="${sec.key}-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#tab-${sec.key}"
                            type="button"
                            role="tab"
                            aria-selected="${index === 0 ? 'true' : 'false'}">
                        <span class="esh-route-tab__inner">
                            <i class="fa ${sec.icon} esh-route-tab__icon" aria-hidden="true"></i>
                            <span class="esh-route-tab__label text-uppercase">${sec.label}</span>
                            <span class="badge ${badgeClass}">${totalCount}</span>
                        </span>
                    </button>
                </li>`;

            contentHtml += `<div class="tab-pane fade ${paneShow} p-3" id="tab-${sec.key}" role="tabpanel" aria-labelledby="${sec.key}-tab">`;
            if (totalCount > 0) {
                if (ilkZiyaretList.length > 0) {
                    contentHtml += `
                        <div class="d-flex justify-content-between align-items-center mb-3 px-2">
                            <h6 class="mb-0 fw-bold text-muted text-uppercase"><i class="fa fa-user-plus me-2 text-secondary"></i> İlk ziyaretler (yeni kayıt)</h6>
                            <span class="badge bg-light text-dark border rounded-pill">${ilkZiyaretList.length}</span>
                        </div>`;
                    ilkZiyaretList.forEach(item => { contentHtml += generateIlkKayitRow(item, 'border-info', ''); });
                }
                if (planliList.length > 0) {
                    contentHtml += `
                        <div class="d-flex justify-content-between align-items-center mb-3 mt-4 px-2">
                            <h6 class="mb-0 fw-bold text-muted text-uppercase"><i class="fa fa-calendar-check me-2 text-secondary"></i> Planlı izlemler</h6>
                            <span class="badge bg-light text-dark border rounded-pill">${planliList.length}</span>
                        </div>`;
                    planliList.forEach(item => { contentHtml += generateTaskRow(item, 'border-primary', ''); });
                }
                if (pansumanList.length > 0) {
                    contentHtml += `
                        <div class="d-flex justify-content-between align-items-center mb-3 mt-4 px-2">
                            <h6 class="mb-0 fw-bold text-muted text-uppercase"><i class="fa fa-plus-square me-2 text-secondary"></i> Planlı pansumanlar</h6>
                            <span class="badge bg-light text-dark border rounded-pill">${pansumanList.length}</span>
                        </div>`;
                    pansumanList.forEach(item => { contentHtml += generatePansumanRow(item, 'border-warning', '', date, zamanByKey[sec.key] || (index + 1)); });
                }
            } else {
                contentHtml += '<div class="p-5 text-center text-muted small border rounded-3 bg-light">Bu vaktin planlı görevi bulunmuyor.</div>';
            }
            contentHtml += '</div>';
        });

        navHtml += '</ul>';
        contentHtml += '</div>';

        let nakilHtml = '';
        let nakilList = data.nakiller || [];
        if (nakilList.length > 0) {
            hasAnyData = true;
            nakilHtml = `
                <div class="mt-4 border-top pt-3 shadow-none">
                    <div class="d-flex justify-content-between align-items-center mb-3 px-2">
                        <h6 class="mb-0 fw-bold text-muted text-uppercase"><i class="fa fa-ambulance me-2 text-secondary"></i> Nakiller</h6>
                        <span class="badge bg-light text-dark border rounded-pill">${nakilList.length}</span>
                    </div>
                    <div class="nakil-list px-1">`;
            nakilList.forEach(item => { nakilHtml += generateTaskRow(item, 'border-danger', ''); });
            nakilHtml += '</div></div>';
        }

        if (!hasAnyData) {
            dashPlanSetHtml('<div class="p-5 text-center text-muted mr-dash-plan__empty">Güne ait kayıt bulunamadı.</div>');
            $('#route-button-container').addClass('d-none');
        } else {
            dashPlanSetPlan(navHtml, contentHtml + nakilHtml);
        }
        updateDailyPlanMernisButton(data.mernisPatientCount);
        updateDailyPlanSmsButton(data.mernisPatientCount);
    }).fail(function (jqXHR, textStatus, err) {
        dashPlanSetHtml(
            '<div class="alert alert-danger m-2 small mb-0">' +
            '<i class="fa fa-exclamation-triangle me-2"></i>Günün planı yüklenemedi. Sayfayı yenileyin veya oturumunuzun açık olduğundan emin olun.' +
            '<span class="d-block text-muted mt-1">' + (textStatus || '') + ' ' + (err || '') + '</span></div>'
        );
        $('#route-button-container').addClass('d-none');
        updateDailyPlanMernisButton(0);
        updateDailyPlanSmsButton(0);
    });
}

function dashPhoneTelHref(raw) {
    const digits = String(raw || '').replace(/\s+/g, '').replace(/[^\d+]/g, '');
    return digits.length >= 10 ? 'tel:' + digits : '';
}

function dashMapsHref(coords) {
    const c = String(coords || '').trim();
    if (!c) {
        return '';
    }
    return 'https://www.google.com/maps?q=' + encodeURIComponent(c);
}

function dashDailyCardQuickActions(item) {
    const telHref = dashPhoneTelHref(item.ceptel1);
    const mapHref = dashMapsHref(item.coords);
    if (!telHref && !mapHref) {
        return '';
    }
    let html = '<div class="esh-daily-plan-card__actions d-flex gap-1 ms-auto">';
    if (telHref) {
        html += '<a href="' + telHref + '" class="btn btn-sm btn-outline-success rounded-pill" title="Ara" aria-label="Hastayı ara"><i class="fa-solid fa-phone"></i></a>';
    }
    if (mapHref) {
        html += '<a href="' + mapHref + '" class="btn btn-sm btn-outline-danger rounded-pill" target="_blank" rel="noopener noreferrer" title="Haritada aç" aria-label="Haritada aç"><i class="fa-solid fa-map-location-dot"></i></a>';
    }
    html += '</div>';
    return html;
}

function renderDailyCard(item, opts) {
    const patientUrl = opts.patientUrl || '#';
    const actionUrl = opts.actionUrl || '#';
    const borderColor = opts.borderColor || 'border-primary';
    const bgColor = opts.bgColor || '';
    const islemLbl = trUpper(item.islem_label || 'İşlem Yok');
    const ilceLbl = trUpper(item.ilce != null && item.ilce !== '' ? item.ilce : '-');
    const mahalleLbl = formatMahalleBolge(item);
    const quickActions = dashDailyCardQuickActions(item);
    return `
    <div class="list-group-item p-2 border rounded mb-2 shadow-sm border-start-lg esh-daily-plan-card ${borderColor} ${bgColor}">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="fw-bold text-uppercase small flex-grow-1 min-w-0">
                <a href="${actionUrl}" class="${genderNameClass(item.cinsiyet)} text-decoration-none">
                    <i class="fa fa-user-circle me-1 text-secondary"></i> ${item.isim} ${item.soyisim}
                </a>
            </div>
            <span class="badge border shadow-sm esh-daily-plan-card__badge flex-shrink-0">${islemLbl}</span>
        </div>
        <div class="mt-1 d-flex align-items-center flex-wrap gap-2 small esh-daily-plan-card__meta">
            <span><i class="fa fa-id-card me-1"></i><a href="${patientUrl}" class="text-primary text-decoration-none">${formatTcTcNo(item.tckimlik)}</a></span>
            <span><i class="fa fa-map-marker-alt me-1 text-danger"></i>${ilceLbl} / ${mahalleLbl}</span>
            ${quickActions ? quickActions : ''}
        </div>
    </div>`;
}

function generateTaskRow(item, borderColor, bgColor) {
    const patientUrl = eshUrl('Patient', 'view', { id: item.hastaid });
    const tcEnc = encodeURIComponent(item.tckimlik || '');
    const planId = item.id != null && item.id !== '' ? String(item.id) : '';
    const visitUrl = eshUrl('Visit', 'create', { tc: item.tckimlik, plan_id: planId });
    return renderDailyCard(item, { patientUrl, actionUrl: visitUrl, borderColor, bgColor });
}

function generatePansumanRow(item, borderColor, bgColor, calendarDate, zamanSlot) {
    const patientUrl = eshUrl('Patient', 'view', { id: item.hastaid });
    const tcEnc = encodeURIComponent(item.tckimlik || '');
    const tarihEnc = encodeURIComponent(calendarDate || '');
    const z = (zamanSlot !== undefined && zamanSlot !== null) ? parseInt(zamanSlot, 10) : 1;
    const zSafe = (z >= 1 && z <= 3) ? z : 1;
    let visitParams = { tc: item.tckimlik, tarih: calendarDate, zaman: zSafe };
    const defRaw = (window.ESH_PAGE && window.ESH_PAGE.dashboardPansumanIzlemDefaultIslemId != null)
        ? parseInt(window.ESH_PAGE.dashboardPansumanIzlemDefaultIslemId, 10)
        : 0;
    if (!Number.isNaN(defRaw) && defRaw > 0) {
        visitParams.yapilan = String(defRaw);
    }
    let visitUrl = eshUrl('Visit', 'create', visitParams);
    return renderDailyCard(item, { patientUrl, actionUrl: visitUrl, borderColor, bgColor });
}

function generateIlkKayitRow(item, borderColor, bgColor) {
    const firstSaveUrl = eshUrl('Patient', 'firstSave', { id: item.hastaid });
    return renderDailyCard(item, { patientUrl: firstSaveUrl, actionUrl: firstSaveUrl, borderColor, bgColor });
}

function initDashboardTcLookup() {
    const $form = $('#dashboard-tc-lookup-form');
    const $input = $('#dashboard-tc-lookup-input');
    const $exact = $('#dashboard-tc-lookup-exact');
    const $suggest = $('#dashboard-tc-lookup-suggestions');
    if (!$form.length || !$input.length) return;

    let timer = null;
    let lastQuery = '';
    let lastQueryDigits = '';

    function statusBadgeClass(key) {
        if (key === 'active') return 'bg-success';
        if (key === 'passive') return 'bg-secondary';
        if (key === 'waiting') return 'bg-info text-dark';
        if (key === 'died') return 'bg-danger';
        if (key === 'deleted') return 'bg-dark';
        if (key === 'araf') return 'bg-warning text-dark';
        if (key === 'probable') return 'bg-warning text-dark';
        return 'bg-light text-dark border';
    }

    function adresHtml(adres) {
        const a = String(adres || '').trim();
        if (!a) return '';
        return ' <span class="text-muted small fw-normal">(' + $('<div>').text(a).html() + ')</span>';
    }

    function renderResult(res) {
        const exact = res && res.exact ? res.exact : null;
        const suggestions = (res && Array.isArray(res.suggestions)) ? res.suggestions : [];

        if (exact) {
            $exact.html(
                `<div class="alert alert-success py-2 px-3 mb-0 d-flex flex-wrap align-items-center gap-2">
                    <span class="fw-semibold">${exact.isim || ''} ${exact.soyisim || ''}${adresHtml(exact.adres)}</span>
                    <span class="badge ${statusBadgeClass(exact.status_key)}">${exact.status_text || 'Belirsiz'}</span>
                    <a href="${exact.view_url}" class="btn btn-sm btn-outline-success ms-auto rounded-pill"><i class="fa-solid fa-id-card me-1"></i>Hasta kartına git</a>
                </div>`
            ).removeClass('d-none');
        } else if (String(lastQueryDigits).length === 11) {
            $exact.html('<div class="alert alert-warning py-2 px-3 mb-0"><i class="fa-solid fa-triangle-exclamation me-1"></i>Bu TC Kimlik numarasıyla kayıtlı hasta bulunamadı.</div>').removeClass('d-none');
        } else {
            $exact.addClass('d-none').empty();
        }

        if (suggestions.length > 0) {
            let list = '<div class="list-group shadow-sm">';
            suggestions.forEach(function (s) {
                list += `<a href="${s.view_url}" class="list-group-item list-group-item-action py-2 d-flex justify-content-between align-items-center">
                    <span><strong>${formatTcTcNo(s.tckimlik)}</strong> — ${s.isim || ''} ${s.soyisim || ''}${adresHtml(s.adres)}</span>
                    <span class="badge ${statusBadgeClass(s.status_key)}">${s.status_text || 'Belirsiz'}</span>
                </a>`;
            });
            list += '</div>';
            $suggest.html(list).removeClass('d-none');
        } else {
            $suggest.addClass('d-none').empty();
        }
    }

    function runLookup() {
        const q = String($input.val() || '').trim();
        lastQuery = q;
        lastQueryDigits = q.replace(/\D/g, '');
        if (q.length < 2) {
            $exact.addClass('d-none').empty();
            $suggest.addClass('d-none').empty();
            return;
        }
        $.getJSON(eshUrl('Dashboard', 'tcLookupAjax', { q: q }), function (res) {
            if (q !== lastQuery) return;
            renderResult(res || {});
        }).fail(function () {
            if (q !== lastQuery) return;
            $exact.html('<div class="alert alert-danger py-2 px-3 mb-0">Hasta araması yapılırken hata oluştu.</div>').removeClass('d-none');
        });
    }

    $form.on('submit', function (e) {
        e.preventDefault();
        runLookup();
    });

    $input.on('input', function () {
        if (timer) clearTimeout(timer);
        timer = setTimeout(runLookup, 280);
    });
}

function initDailyPlanMernisScan() {
    if (!dashboardCanMernisScan()) {
        return;
    }
    const $btn = $('#btn-daily-plan-mernis-scan');
    if (!$btn.length) {
        return;
    }
    if (!window.ESH_DASHBOARD_PLAN_DATE) {
        updateDailyPlanMernisButton(0);
    }
    $btn.on('click', function () {
        if ($btn.prop('disabled')) {
            return;
        }
        const date = window.ESH_DASHBOARD_PLAN_DATE
            || (window.ESH_PAGE && window.ESH_PAGE.initialDate)
            || '';
        if (!date) {
            if (typeof toastr !== 'undefined') {
                toastr.warning('Önce takvimden bir gün seçin.', 'MERNİS');
            }
            return;
        }
        if (!window.confirm('Seçili gün (' + date.split('-').reverse().join('-') + ') planındaki hastalar için MERNİS vefat taraması yapılacak. Devam edilsin mi?')) {
            return;
        }
        runDailyPlanMernisScan(date, 0, [], null);
    });
}

function runDailyPlanMernisScan(date, offset, deceasedAccum, originalBtnHtml) {
    if (!dashboardCanMernisScan()) {
        return;
    }
    const $btn = $('#btn-daily-plan-mernis-scan');
    if (!$btn.length) {
        return;
    }
    const scanUrl = (window.ESH_PAGE && window.ESH_PAGE.dashboardPlanMernisScanUrl)
        ? window.ESH_PAGE.dashboardPlanMernisScanUrl
        : eshUrl('Dashboard', 'dailyPlanMernisScan');
    const savedHtml = (originalBtnHtml != null && originalBtnHtml !== '')
        ? originalBtnHtml
        : $btn.html();
    const spinnerHtml = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span><span class="d-none d-md-inline">MERNİS</span>';

    function restoreBtn() {
        $btn.data('mernis-scanning', false).html(savedHtml);
        updateDailyPlanMernisButton(window.ESH_DASHBOARD_MERNIS_PATIENT_COUNT);
    }

    if (offset === 0) {
        $btn.data('mernis-scanning', true).prop('disabled', true).html(spinnerHtml);
    }

    $.getJSON(scanUrl, { date: date, offset: offset, limit: 5 })
        .done(function (res) {
            if (!res || !res.ok) {
                if (typeof toastr !== 'undefined') {
                    toastr.error((res && res.error) ? res.error : 'MERNİS taraması başarısız.', 'Hata');
                }
                restoreBtn();
                return;
            }

            const allDeceased = deceasedAccum.concat(res.deceased || []);
            (res.deceased || []).forEach(function (d) {
                if (typeof toastr === 'undefined') {
                    return;
                }
                const ad = [d.isim, d.soyisim].filter(Boolean).join(' ').trim() || d.tckimlik || 'Hasta';
                const vd = d.olumTarihi || '—';
                toastr.error(
                    '<strong>' + ad + '</strong><br>Vefat tespit edildi. Tarih: <b>' + vd + '</b>',
                    'MERNİS uyarısı',
                    { timeOut: 10000, closeButton: true, progressBar: true, escapeHtml: false }
                );
            });

            if (!res.done) {
                if (typeof toastr !== 'undefined' && res.total > 0) {
                    toastr.info('Taranıyor: ' + res.nextOffset + ' / ' + res.total, 'MERNİS', { timeOut: 1200, progressBar: true });
                }
                runDailyPlanMernisScan(date, res.nextOffset, allDeceased, savedHtml);
                return;
            }

            restoreBtn();
            if (typeof toastr !== 'undefined') {
                if (allDeceased.length === 0) {
                    toastr.success('MERNİS taraması tamamlandı. Vefat tespiti yok.', 'MERNİS');
                } else {
                    toastr.warning(
                        'Tarama tamamlandı. <b>' + allDeceased.length + '</b> vefat tespiti.',
                        'MERNİS',
                        { timeOut: 8000, closeButton: true, escapeHtml: false }
                    );
                }
            }
            if (allDeceased.length > 0 && typeof getDailyTasks === 'function') {
                getDailyTasks(date);
            }
        })
        .fail(function () {
            if (typeof toastr !== 'undefined') {
                toastr.error('MERNİS taraması sırasında sunucu hatası.', 'Hata');
            }
            restoreBtn();
        });
}

/** Takvim hücresi `onclick="getDailyTasks(...)"` için global (inline handler) */
window.getDailyTasks = getDailyTasks;
