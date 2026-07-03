// jQuery 4: $.trim shim (jQuery'den sonra yüklenir)
if (window.jQuery && typeof jQuery.trim !== 'function') {
    jQuery.trim = function (text) {
        return text == null ? '' : String(text).trim();
    };
}

/**
 * Uygulama route URL'si (SEF veya legacy query-string).
 * @param {string} controller
 * @param {string} [action='index']
 * @param {Record<string, string|number|boolean|null|undefined>} [params]
 * @returns {string}
 */
window.eshUrl = function (controller, action, params) {
    action = action || 'index';
    params = params && typeof params === 'object' ? params : {};
    var base = String(window.ESH_PUBLIC_WEB || '/public/').replace(/\/?$/, '/');
    if (window.ESH_SEF_ENABLED) {
        var url = base + controller + '/' + action;
        var qs = new URLSearchParams();
        Object.keys(params).forEach(function (key) {
            var val = params[key];
            if (val === null || val === undefined || val === '') {
                return;
            }
            qs.set(key, String(val));
        });
        var q = qs.toString();
        return q ? url + '?' + q : url;
    }
    var legacy = new URLSearchParams();
    legacy.set('controller', controller);
    legacy.set('action', action);
    Object.keys(params).forEach(function (key) {
        var val = params[key];
        if (val === null || val === undefined || val === '') {
            return;
        }
        legacy.set(key, String(val));
    });
    return base + 'index.php?' + legacy.toString();
};

window.eshUrlWithQuery = function (controller, action, queryString) {
    var url = eshUrl(controller, action);
    if (!queryString) {
        return url;
    }
    var q = String(queryString).replace(/^&/, '');
    if (q === '') {
        return url;
    }
    return url + (url.indexOf('?') >= 0 ? '&' : '?') + q;
};

// 1. Toastr Ayarları (tema önizleme iframe’inde toastr yok — guard)
if (typeof toastr !== 'undefined' && toastr) {
    toastr.options = {
        "escapeHtml": false,
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "3000"
    };
}

// Bootstrap 5: `.modal-backdrop` body düzeyinde (z-index ~1050). Modal kökü `<main>` içindeyse
// `global.css` içindeki `body.app-shell > main { z-index: 1 }` yüzünden tüm main backdrop'un
// altında kalır — diyalog görünmez, yalnız kararma / «kilit» olur. Açılışta modalı body'ye taşı.
if (window.jQuery) {
    jQuery(document).on('show.bs.modal', '.modal', function () {
        if (this.parentNode !== document.body) {
            document.body.appendChild(this);
        }
    });
}

/** Tom Select varsayılanları (.esh-tomselect işaretçi sınıfı korunur). */
window.eshTomSelectDefaults = function () {
    return {
        allowEmptyOption: true,
        maxOptions: 10000,
        placeholder: 'Seçiniz...',
        placeholderMultiple: 'Bazı seçenekler seçin'
    };
};

/** Tom Select'e verilecek temiz ayarlar (eski/özel anahtarlar ayıklanır). */
window.eshTomSelectOptionsForElement = function (el, baseOpts) {
    var raw = Object.assign({}, window.eshTomSelectDefaults(), baseOpts || {});
    var ph = el.getAttribute('data-placeholder');
    var placeholder = ph || (el.multiple
        ? (raw.placeholderMultiple || raw.placeholder_text_multiple || 'Bazı seçenekler seçin')
        : (raw.placeholder || raw.placeholder_text_single || 'Seçiniz...'));

    var opts = {
        allowEmptyOption: raw.allowEmptyOption !== false,
        maxOptions: typeof raw.maxOptions === 'number' ? raw.maxOptions : 10000,
        placeholder: placeholder,
        dropdownParent: 'body',
        plugins: [],
        hideSelected: !!el.multiple ? false : undefined,
        render: {
            no_results: function () {
                return '<div class="no-results px-2 py-1 text-muted">Kayıt bulunamadı</div>';
            }
        }
    };
    if (el.multiple) {
        opts.plugins = {
            remove_button: {
                label: '&times;',
                title: 'Kaldır',
                className: 'remove'
            }
        };
    } else {
        opts.maxItems = 1;
    }
    /* Ajax / remote Tom Select (ör. hasta düzenle — Hastalik/searchAssigned) */
    ['load', 'shouldLoad', 'preload', 'valueField', 'labelField', 'searchField', 'score', 'sortField'].forEach(function (key) {
        if (raw[key] !== undefined) {
            opts[key] = raw[key];
        }
    });
    return opts;
};

/** Tom Select iç change → native select change (jQuery cascade / delege dinleyicileri). */
window.eshTomSelectAttachDomChangeBridge = function (ts, el) {
    if (!ts || !el || ts._eshDomChangeBridge) {
        return;
    }
    ts._eshDomChangeBridge = true;
    ts.on('change', function () {
        if (el._eshDomChangeBridgeLock) {
            return;
        }
        el._eshDomChangeBridgeLock = true;
        try {
            el.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (ignoreEv) {
            try {
                var ev = document.createEvent('HTMLEvents');
                ev.initEvent('change', true, false);
                el.dispatchEvent(ev);
            } catch (ignoreEv2) {
                /* yoksay */
            }
        } finally {
            el._eshDomChangeBridgeLock = false;
        }
    });
};

/** Tom Select stacking: dropdown açılınca z-index / overflow düzeltmesi. */
window.eshTomSelectAttachStacking = function (ts) {
    if (!ts || ts._eshStackingAttached) {
        return;
    }
    ts._eshStackingAttached = true;
    var wrapSel = '.esh-tomselect-field, .izlem-form-select-wrap, .izlem-plan-select-wrap, .js-address-cascade .col-md-6';

    function eshTomSelectOpenZ() {
        /* Bootstrap .modal ≈ 1055; body'ye taşınan dropdown modalın arkasında kalmasın */
        if (ts.wrapper && ts.wrapper.closest && ts.wrapper.closest('.modal')) {
            return 3120;
        }
        /* Hasta düzenle yapışkan çubuk z-index: 3060 — dropdown üstte kalmalı */
        return 3070;
    }

    function resetAll() {
        if (document.querySelector('.ts-wrapper.dropdown-active')) {
            return;
        }
        if (window.jQuery) {
            jQuery('.ts-wrapper').css('z-index', '');
            jQuery('.ts-dropdown').css('z-index', '');
            jQuery('[data-esh-tomselect-wrap]').css({ position: '', zIndex: '' }).removeAttr('data-esh-tomselect-wrap');
            jQuery('[data-esh-tomselect-overflow]').each(function () {
                var $el = jQuery(this);
                if ($el.data('eshHadOverflowHidden')) {
                    $el.addClass('overflow-hidden');
                    $el.removeData('eshHadOverflowHidden');
                }
                $el.css('overflow', $el.data('eshPrevOverflow') || '');
                $el.removeAttr('data-esh-tomselect-overflow').removeData('eshPrevOverflow');
            });
        }
    }

    function overflowVisibleOnly(wrapper, selector) {
        if (!window.jQuery || !wrapper) {
            return;
        }
        var $c = jQuery(wrapper);
        var $a = $c.closest(selector);
        if (!$a.length || $a.is('[data-esh-tomselect-overflow]')) {
            return;
        }
        $a.attr('data-esh-tomselect-overflow', '1');
        $a.data('eshPrevOverflow', $a.css('overflow'));
        if ($a.hasClass('overflow-hidden')) {
            $a.data('eshHadOverflowHidden', true);
            $a.removeClass('overflow-hidden');
        }
        $a.css('overflow', 'visible');
    }

    ts.on('dropdown_open', function () {
        if (!window.jQuery || !ts.wrapper) {
            return;
        }
        var openZ = eshTomSelectOpenZ();
        var $w = jQuery(ts.wrapper);
        jQuery('.ts-wrapper').not($w).css('z-index', '');
        $w.css('z-index', openZ - 1);
        if (ts.dropdown) {
            ts.dropdown.style.zIndex = String(openZ);
            if (openZ >= 3120) {
                ts.dropdown.classList.add('esh-ts-dropdown-in-modal');
            } else {
                ts.dropdown.classList.add('esh-ts-dropdown-stacked');
            }
        }
        $w.closest(wrapSel).attr('data-esh-tomselect-wrap', '1').css({
            position: 'relative',
            zIndex: openZ - 2
        });
        overflowVisibleOnly(ts.wrapper, '.card');
        overflowVisibleOnly(ts.wrapper, '.card-body');
        overflowVisibleOnly(ts.wrapper, '.izlem-form-panel');
        overflowVisibleOnly(ts.wrapper, '.js-address-cascade');
    });

    ts.on('dropdown_close', function () {
        if (ts.dropdown) {
            ts.dropdown.style.zIndex = '';
            ts.dropdown.classList.remove('esh-ts-dropdown-in-modal', 'esh-ts-dropdown-stacked');
        }
        window.setTimeout(resetAll, 0);
    });
};

/** Tek select öğesinde Tom Select başlatır. */
window.eshInitTomSelectElement = function (el, options) {
    if (!el || typeof TomSelect === 'undefined' || el.tomselect) {
        return el && el.tomselect ? el.tomselect : null;
    }
    if (!el.classList || !el.classList.contains('esh-tomselect')) {
        return null;
    }
    if (el.classList.contains('esh-hastalik-ajax')) {
        if (typeof window.eshInitHastalikAjaxTomSelectElement === 'function') {
            return window.eshInitHastalikAjaxTomSelectElement(el, options);
        }
        return null;
    }
    /* Cascade: disabled select'ler AJAX sonrası enable + refresh ile init edilir */
    if (el.disabled && !(options && options.force)) {
        return null;
    }
    el.removeAttribute('size');
    var opts = window.eshTomSelectOptionsForElement(el, options);
    try {
        var ts = new TomSelect(el, opts);
        window.eshTomSelectAttachStacking(ts);
        window.eshTomSelectAttachDomChangeBridge(ts, el);
        if (el.disabled) {
            ts.disable();
        }
        if (el.options && el.options.length > 1 && ts.options && Object.keys(ts.options).length < 1) {
            ts.sync();
        }
        ts.refreshOptions(false);
        return ts;
    } catch (err) {
        if (typeof console !== 'undefined' && console.error) {
            console.error('eshInitTomSelectElement:', el.name || el.id || el, err);
        }
        return null;
    }
};

/** Henüz Tom Select uygulanmamış .esh-tomselect öğelerini başlatır. */
window.eshInitTomSelect = function (root, options) {
    if (typeof TomSelect === 'undefined') {
        return;
    }
    var elements = [];
    if (!root || root === document) {
        elements = Array.prototype.slice.call(document.querySelectorAll('.esh-tomselect'));
        if (!(options && options.includePartialModals)) {
            elements = elements.filter(function (el) {
                return !el.closest || !el.closest('.patient-partial-edit-modal');
            });
        }
    } else if (root.jquery) {
        root.find('.esh-tomselect').add(root.filter('.esh-tomselect')).each(function () {
            elements.push(this);
        });
    } else if (root.querySelectorAll) {
        elements = Array.prototype.slice.call(root.querySelectorAll('.esh-tomselect'));
        if (root.classList && root.classList.contains('esh-tomselect')) {
            elements.push(root);
        }
    }
    elements.forEach(function (el) {
        window.eshInitTomSelectElement(el, options);
    });
};

/** Option DOM güncellemesi sonrası destroy + yeniden init. */
window.eshRefreshTomSelect = function (selectEl, options) {
    if (!selectEl) {
        return;
    }
    var el = selectEl.jquery ? selectEl[0] : selectEl;
    if (!el) {
        return;
    }
    var htmlSnapshot = el.innerHTML;
    var saved = [];
    if (el.multiple) {
        saved = Array.prototype.map.call(el.selectedOptions || [], function (o) {
            return o.value;
        });
    } else {
        saved = el.value;
    }
    if (el.tomselect) {
        try {
            el.tomselect.destroy();
        } catch (ignoreDestroy) {
            /* yoksay */
        }
    }
    /* destroy() select'i init anındaki HTML'e döndürür — AJAX ile güncellenen seçenekleri geri yükle */
    el.innerHTML = htmlSnapshot;
    if (el.multiple) {
        Array.prototype.forEach.call(el.options || [], function (opt) {
            opt.selected = saved.indexOf(opt.value) >= 0;
        });
    } else if (saved != null && saved !== '') {
        el.value = saved;
    }
    var refreshOpts = Object.assign({}, options || {});
    var silentRefresh = !!refreshOpts.silent;
    delete refreshOpts.silent;
    window.eshInitTomSelectElement(el, Object.assign({}, refreshOpts, { force: true }));
    if (!silentRefresh && el.form && el.form.getAttribute('data-esh-submit-guard') !== 'off') {
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }
};

window.eshInitTomSelectOnPage = function (options) {
    window.eshInitTomSelect(document, options);
};

/** Tom Select wrapper öğesini döndürür (validasyon UI için). */
window.eshTomSelectWrapper = function (selectEl) {
    if (!selectEl) {
        return null;
    }
    var el = selectEl.jquery ? selectEl[0] : selectEl;
    if (!el || !el.tomselect || !el.tomselect.wrapper) {
        return null;
    }
    return el.tomselect.wrapper;
};

/** @deprecated Tom Select stacking her instance'da attach edilir. */
window.eshTomSelectStackingInit = function () { /* noop */ };

// 2. Datepicker — autocomplete kapalı + global init
// Not: Bootstrap-datepicker sürümüne göre "templates" kullanımı daha garantidir.
const datepickerTemplates = {
    leftArrow: '<i class="fa-solid fa-chevron-left"></i>',
    rightArrow: '<i class="fa-solid fa-chevron-right"></i>'
};

/** Tüm .datepicker inputlarında tarayıcı otomatik doldurmayı kapatır. */
window.eshPrepareDatepickerInputs = function (root) {
    var scope = root && root.querySelectorAll ? root : document;
    scope.querySelectorAll('input.datepicker').forEach(function (el) {
        el.setAttribute('autocomplete', 'off');
    });
};

window.eshDatepickerDefaults = function () {
    return {
        format: 'dd-mm-yyyy',
        language: 'tr',
        autoclose: true,
        todayHighlight: true,
        forceParse: false,
        templates: datepickerTemplates
    };
};

$(document).ready(function() {
    window.eshPrepareDatepickerInputs(document);

    // --- Tarih Maskeleme Fonksiyonu ---
    $('.datepicker').on('input', function(e) {
        // Sadece rakamları al
        let input = e.target.value.replace(/\D/g, ''); 
        let value = '';

        if (input.length > 0) {
            // Gün
            value += input.substring(0, 2);
            if (input.length > 2) {
                // Ay
                value += '-' + input.substring(2, 4);
            }
            if (input.length > 4) {
                // Yıl
                value += '-' + input.substring(4, 8);
            }
        }
        
        // Kullanıcı silme yaparken ayırıcıyı silmekte zorlanmaması için kontrol
        e.target.value = value.substring(0, 10); 
    });

    // --- Datepicker Başlatma ---
    $('.datepicker').each(function () {
        var $el = $(this);
        if ($el.data('datepicker')) {
            return;
        }
        $el.datepicker(window.eshDatepickerDefaults());
    }).on('changeDate', function () {
        $(this).datepicker('hide');
    });

});

if (typeof MutationObserver !== 'undefined' && document.body) {
    var eshDatepickerAutofillObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (!node || node.nodeType !== 1) {
                    return;
                }
                if (node.matches && node.matches('input.datepicker')) {
                    node.setAttribute('autocomplete', 'off');
                }
                if (node.querySelectorAll) {
                    window.eshPrepareDatepickerInputs(node);
                }
            });
        });
    });
    eshDatepickerAutofillObserver.observe(document.body, { childList: true, subtree: true });
}

/** Barthel formu — HTML tooltip (base64, global.js genel tooltip'ten ayrı). */
window.eshDecodeBarthelTooltipB64 = function (b64) {
    if (!b64) {
        return '';
    }
    try {
        var binary = atob(b64);
        var bytes = new Uint8Array(binary.length);
        for (var i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return new TextDecoder('utf-8').decode(bytes);
    } catch (e) {
        try {
            return atob(b64);
        } catch (e2) {
            return '';
        }
    }
};

window.eshInitBarthelFieldTooltips = function (root) {
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
        return;
    }
    var scope = root && typeof root.querySelectorAll === 'function' ? root : document;
    scope.querySelectorAll('.esh-barthel-form-card .esh-barthel-tt-trigger').forEach(function (el) {
        var html = window.eshDecodeBarthelTooltipB64(el.getAttribute('data-esh-barthel-tt-b64'));
        if (!html) {
            return;
        }
        var existing = bootstrap.Tooltip.getInstance(el);
        if (existing) {
            existing.dispose();
        }
        new bootstrap.Tooltip(el, {
            customClass: 'esh-barthel-field-tooltip',
            html: true,
            placement: 'auto',
            trigger: 'hover focus',
            title: html,
            sanitize: false,
        });
    });
};

/** SMS şablonu — {{değişken}} açıklama tooltip (HTML, base64). */
window.eshInitSmsSablonVarTooltips = function (root) {
    if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
        return;
    }
    var scope = root && typeof root.querySelectorAll === 'function' ? root : document;
    scope.querySelectorAll('.esh-sms-sablon-vars-tt-trigger').forEach(function (el) {
        var html = window.eshDecodeBarthelTooltipB64(el.getAttribute('data-esh-sms-sablon-vars-b64'));
        if (!html) {
            return;
        }
        var existing = bootstrap.Tooltip.getInstance(el);
        if (existing) {
            existing.dispose();
        }
        new bootstrap.Tooltip(el, {
            customClass: 'esh-sms-sablon-vars-tooltip',
            html: true,
            placement: 'auto',
            trigger: 'hover focus',
            title: html,
            sanitize: false,
        });
    });
};

$(document).ready(function() {
    // Tüm Tooltip'leri aktifleştir (Barthel alanları hariç)
    var tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]:not(.esh-barthel-tt-trigger)')
    );
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    if (typeof window.eshInitBarthelFieldTooltips === 'function') {
        window.eshInitBarthelFieldTooltips();
    }
    if (typeof window.eshInitSmsSablonVarTooltips === 'function') {
        window.eshInitSmsSablonVarTooltips();
    }
});

/** Hasta mega menü — tablo satırı hover katmanının üstünde kalması için ayrı katman. */
function eshEnsurePatientMegaMenuLayer() {
    var layer = document.getElementById('esh-patient-mega-layer');
    if (!layer) {
        layer = document.createElement('div');
        layer.id = 'esh-patient-mega-layer';
        document.body.appendChild(layer);
    }
    return layer;
}

/** Bootstrap dropdown kökü (ev.target bazen toggle olabiliyor). */
function eshDropdownRootFromEvent(ev) {
    if (!ev || !ev.target || !ev.target.closest) {
        return null;
    }
    return ev.target.closest('.dropdown');
}

/** Hasta adı / Tıbbi İşlemler mega menü — toggle altında sabit konum (Popper fixed kaydırmasın). */
(function eshInitPatientMegaDropdowns() {
    /** @param {Element|null} scope İçindeki `.dropdown` öğeleri; null = tüm belge */
    function applyScoped(scope) {
        eshEnsurePatientMegaMenuLayer();
        var roots = scope && scope.nodeType === 1
            ? scope.querySelectorAll('.dropdown')
            : document.querySelectorAll('.dropdown');
        roots.forEach(function (root) {
            if (!root.querySelector('.esh-patient-mega-menu')) {
                return;
            }
            root.classList.add('esh-patient-mega-dropdown');
            var toggle = root.querySelector('[data-bs-toggle="dropdown"]');
            if (toggle && !toggle.getAttribute('data-bs-display')) {
                toggle.setAttribute('data-bs-display', 'static');
            }
        });
    }

    /** AJAX ile eklenen satırlar için yeniden işle (`patient-unified` vb.). */
    window.eshApplyPatientMegaDropdownPrep = applyScoped;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { applyScoped(null); });
    } else {
        applyScoped(null);
    }
})();

/** Hasta mega menü DOM öğesi (portal sonrası dropdown içinde olmayabilir). */
function eshGetPatientMegaMenu(root) {
    if (root._eshMegaMenuEl) {
        return root._eshMegaMenuEl;
    }
    var menu = root.querySelector('.esh-patient-mega-menu');
    if (menu) {
        root._eshMegaMenuEl = menu;
    }
    return menu;
}

function eshMarkPatientMegaRow(root, on) {
    var toggle = root.querySelector('[data-bs-toggle="dropdown"]');
    if (!toggle) {
        return;
    }
    var row = toggle.closest('tr');
    if (!row) {
        return;
    }
    if (on) {
        row.classList.add('esh-tr-mega-open');
        root._eshMegaRowEl = row;
    } else {
        row.classList.remove('esh-tr-mega-open');
        if (root._eshMegaRowEl === row) {
            root._eshMegaRowEl = null;
        }
    }
}

/** Menüyü tablo dışı katmana taşı (td hover box-shadow / filter üstünü örtmesin). */
function eshPortalPatientMegaMenu(root, menu) {
    if (menu._eshPortaled) {
        return;
    }
    menu._eshMegaHome = {
        parent: menu.parentNode,
        next: menu.nextSibling
    };
    root._eshMegaMenuEl = menu;
    eshEnsurePatientMegaMenuLayer().appendChild(menu);
    menu.classList.add('esh-patient-mega-menu--portaled');
    menu._eshPortaled = true;
}

function eshUnportalPatientMegaMenu(menu) {
    if (!menu || !menu._eshPortaled || !menu._eshMegaHome) {
        return;
    }
    var home = menu._eshMegaHome;
    if (home.parent) {
        if (home.next && home.next.parentNode === home.parent) {
            home.parent.insertBefore(menu, home.next);
        } else {
            home.parent.appendChild(menu);
        }
    }
    menu.classList.remove('esh-patient-mega-menu--portaled');
    menu._eshPortaled = false;
    menu._eshMegaHome = null;
}

/** Tablo hücresinde mega menüyü viewport’a sabitle. */
function eshPatientMegaMenuTargetWidth(menu) {
    var pad = 8;
    var viewportMax = Math.max(280, window.innerWidth - pad * 2);
    var colCount = parseInt(menu.getAttribute('data-esh-mega-cols') || '2', 10);
    var widthByCols = { 2: 600, 3: 780, 4: 960 };
    var fallback = widthByCols[colCount] || 520;

    menu.style.width = '';
    menu.style.minWidth = '';
    menu.style.maxWidth = '';
    var computed = window.getComputedStyle(menu);
    var target = parseFloat(computed.minWidth);
    if (!isFinite(target) || target <= 0) {
        target = fallback;
    }
    var resolvedMax = parseFloat(computed.maxWidth);
    if (isFinite(resolvedMax) && resolvedMax > 0) {
        target = Math.min(target, resolvedMax);
    }
    return Math.min(Math.max(280, target), viewportMax);
}

function eshPositionPatientMegaMenu(root, menu) {
    var toggle = root.querySelector('[data-bs-toggle="dropdown"]');
    if (!toggle) {
        return;
    }
    var pad = 8;
    var tr = toggle.getBoundingClientRect();
    var menuW = eshPatientMegaMenuTargetWidth(menu);

    menu.style.position = 'fixed';
    menu.style.zIndex = '2501';
    menu.style.width = menuW + 'px';
    menu.style.minWidth = menuW + 'px';
    menu.style.maxWidth = menuW + 'px';
    menu.style.left = tr.left + 'px';
    menu.style.right = 'auto';
    menu.style.bottom = 'auto';
    menu.style.transform = '';
    menu.style.top = (tr.bottom + 4) + 'px';
    menu.style.pointerEvents = 'auto';

    var rect = menu.getBoundingClientRect();
    var shiftX = 0;
    if (rect.right > window.innerWidth - pad) {
        shiftX = (window.innerWidth - pad) - rect.right;
    }
    if (rect.left + shiftX < pad) {
        shiftX = pad - rect.left;
    }
    if (shiftX !== 0) {
        menu.style.transform = 'translateX(' + shiftX + 'px)';
        rect = menu.getBoundingClientRect();
    }
    if (rect.bottom > window.innerHeight - pad) {
        var topAbove = tr.top - rect.height - 4;
        if (topAbove >= pad) {
            menu.style.top = topAbove + 'px';
        }
    }
}

function eshClearPatientMegaMenuStyles(menu) {
    if (!menu) {
        return;
    }
    menu.style.position = '';
    menu.style.top = '';
    menu.style.left = '';
    menu.style.right = '';
    menu.style.bottom = '';
    menu.style.width = '';
    menu.style.minWidth = '';
    menu.style.maxWidth = '';
    menu.style.zIndex = '';
    menu.style.transform = '';
    menu.style.pointerEvents = '';
    if (menu._eshMegaScrollHandler) {
        window.removeEventListener('scroll', menu._eshMegaScrollHandler, true);
        window.removeEventListener('resize', menu._eshMegaScrollHandler);
        menu._eshMegaScrollHandler = null;
    }
}

document.addEventListener('show.bs.dropdown', function (ev) {
    var root = eshDropdownRootFromEvent(ev);
    if (!root) {
        return;
    }
    var menu = eshGetPatientMegaMenu(root);
    if (!menu) {
        return;
    }
    document.body.classList.add('esh-patient-mega-menu-active');
    eshPortalPatientMegaMenu(root, menu);
});

document.addEventListener('shown.bs.dropdown', function (ev) {
    var root = eshDropdownRootFromEvent(ev);
    if (!root) {
        return;
    }
    var menu = eshGetPatientMegaMenu(root);
    if (!menu) {
        return;
    }
    eshMarkPatientMegaRow(root, true);
    eshPositionPatientMegaMenu(root, menu);
    var reposition = function () {
        if (menu.classList.contains('show')) {
            eshPositionPatientMegaMenu(root, menu);
        }
    };
    menu._eshMegaScrollHandler = reposition;
    window.addEventListener('scroll', reposition, true);
    window.addEventListener('resize', reposition);
    requestAnimationFrame(function () {
        eshPositionPatientMegaMenu(root, menu);
    });
});

document.addEventListener('hidden.bs.dropdown', function (ev) {
    var root = eshDropdownRootFromEvent(ev);
    if (!root) {
        return;
    }
    var menu = eshGetPatientMegaMenu(root);
    eshMarkPatientMegaRow(root, false);
    if (menu) {
        eshClearPatientMegaMenuStyles(menu);
        eshUnportalPatientMegaMenu(menu);
    }
    if (!document.querySelector('.esh-patient-mega-menu.show')) {
        document.body.classList.remove('esh-patient-mega-menu-active');
    }
});

/** TC görüntü metninden 11 haneli rakam dizisi (123 456 789 01 veya 12345678901). */
window.eshParseTcDisplayText = function (text) {
    var t = String(text || '').trim();
    if (/^\d{3}\s\d{3}\s\d{3}\s\d{2}$/.test(t)) {
        return t.replace(/\s/g, '');
    }
    if (/^\d{11}$/.test(t)) {
        return t;
    }
    return null;
};

window.eshCopyTextToClipboard = function (text) {
    var value = String(text || '');
    if (value === '') {
        return Promise.reject(new Error('empty'));
    }
    if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard.writeText(value);
    }
    return new Promise(function (resolve, reject) {
        var ta = document.createElement('textarea');
        ta.value = value;
        ta.setAttribute('readonly', 'readonly');
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try {
            if (document.execCommand('copy')) {
                resolve();
            } else {
                reject(new Error('copy failed'));
            }
        } catch (err) {
            reject(err);
        } finally {
            document.body.removeChild(ta);
        }
    });
};

window.eshFlashTcCopyTarget = function (el) {
    if (!el || !el.classList) {
        return;
    }
    el.classList.add('esh-tc-copy--flash');
    window.setTimeout(function () {
        el.classList.remove('esh-tc-copy--flash');
    }, 450);
};

/** Çift tıklanan öğeden hasta TC (11 hane) çıkarır. */
window.eshTcFromDblClickTarget = function (target) {
    if (!target || target.nodeType !== 1) {
        return null;
    }
    if (target.closest('script, style, noscript, .toast, .modal')) {
        return null;
    }

    var marked = target.closest('[data-esh-tc], .esh-tc-copy');
    if (marked) {
        var fromAttr = String(marked.getAttribute('data-esh-tc') || marked.dataset.eshTc || '').replace(/\D/g, '');
        if (fromAttr.length === 11) {
            return { tc: fromAttr, el: marked };
        }
        var fromText = window.eshParseTcDisplayText(marked.textContent);
        if (fromText) {
            return { tc: fromText, el: marked };
        }
    }

    var inp = target.closest('input, textarea');
    if (inp && !inp.disabled && !inp.closest('[data-esh-tc-copy="off"]')) {
        var idName = ((inp.id || '') + ' ' + (inp.name || '') + ' ' + (inp.getAttribute('data-esh-tc-field') || '')).toLowerCase();
        if (/tckimlik|tc_kimlik|tcno|tc_no/.test(idName)) {
            var inputDigits = String(inp.value || '').replace(/\D/g, '');
            if (inputDigits.length === 11) {
                return { tc: inputDigits, el: inp };
            }
        }
    }

    if (target.closest('a[href]:not([href="#"]), button, [role="button"], .dropdown-toggle, .patient-link')) {
        return null;
    }

    var node = target;
    for (var depth = 0; depth < 6 && node && node !== document.body; depth++) {
        if (node.children && node.children.length > 3) {
            break;
        }
        var chunk = String(node.textContent || '').trim();
        if (chunk.length > 14) {
            node = node.parentElement;
            continue;
        }
        var tc = window.eshParseTcDisplayText(chunk);
        if (tc) {
            return { tc: tc, el: node };
        }
        node = node.parentElement;
    }
    return null;
};

window.eshEnhanceTcCopyTargets = function (root) {
    root = root && root.querySelectorAll ? root : document;
    root.querySelectorAll('code, .font-monospace, small, .tc-code').forEach(function (el) {
        if (el.closest('input, textarea, script, style, [data-esh-tc-copy="off"]')) {
            return;
        }
        if (el.dataset.eshTc || el.classList.contains('esh-tc-copy')) {
            return;
        }
        if (el.children && el.children.length > 0) {
            return;
        }
        var tc = window.eshParseTcDisplayText(el.textContent);
        if (!tc) {
            return;
        }
        el.classList.add('esh-tc-copy');
        el.dataset.eshTc = tc;
        if (!el.title) {
            el.title = 'Çift tıkla: TC kopyala';
        }
    });
};

window.eshNotifyTcCopied = function () {
    if (window.toastr && typeof window.toastr.success === 'function') {
        window.toastr.success('TC kimlik numarası kopyalandı.', 'Panoya kopyalandı');
        return;
    }
};

document.addEventListener('dblclick', function (ev) {
    var hit = window.eshTcFromDblClickTarget(ev.target);
    if (!hit || !hit.tc) {
        return;
    }
    ev.preventDefault();
    ev.stopPropagation();
    window.eshCopyTextToClipboard(hit.tc).then(function () {
        window.eshFlashTcCopyTarget(hit.el);
        window.eshNotifyTcCopied();
    }).catch(function () {
        if (window.toastr && typeof window.toastr.error === 'function') {
            window.toastr.error('TC kopyalanamadı.', 'Hata');
        }
    });
});

(function eshInitTcCopyEnhancer() {
    function scan(root) {
        if (typeof window.eshEnhanceTcCopyTargets === 'function') {
            window.eshEnhanceTcCopyTargets(root || document);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { scan(document); });
    } else {
        scan(document);
    }
    if (typeof MutationObserver !== 'undefined' && document.body) {
        var tcEnhanceTimer = null;
        var tcObserver = new MutationObserver(function (mutations) {
            var roots = [];
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node && node.nodeType === 1) {
                        roots.push(node);
                    }
                });
            });
            if (!roots.length) {
                return;
            }
            window.clearTimeout(tcEnhanceTimer);
            tcEnhanceTimer = window.setTimeout(function () {
                roots.forEach(function (root) { scan(root); });
            }, 50);
        });
        tcObserver.observe(document.body, { childList: true, subtree: true });
    }
})();

(function eshInitAdminOffcanvasNav() {
    function bind() {
        var panel = document.getElementById('eshAdminOffcanvas');
        if (!panel || panel.dataset.eshAdminOffcanvasBound === '1') {
            return;
        }
        panel.dataset.eshAdminOffcanvasBound = '1';
        panel.addEventListener('click', function (e) {
            var link = e.target.closest('a.esh-admin-offcanvas__link');
            if (!link || !link.getAttribute('href') || link.getAttribute('href') === '#') {
                return;
            }
            if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas) {
                var instance = bootstrap.Offcanvas.getInstance(panel) || bootstrap.Offcanvas.getOrCreateInstance(panel);
                if (instance) {
                    instance.hide();
                }
            }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})();