(function () {
    'use strict';

    var root = document.getElementById('esh-theme-editor');
    if (!root) {
        return;
    }

    var initialEl = document.getElementById('esh-theme-editor-initial');
    var initial = {
        tokens: [],
        gradient_vars: [],
        gradient_props: [],
        other_vars: [],
        other_props: [],
        sessionPreview: null,
        sessionPreviewActive: false,
        esh_ui_vars: [],
        typography_vars: [],
        typography_props: []
    };
    if (initialEl && initialEl.textContent) {
        try {
            initial = JSON.parse(initialEl.textContent);
        } catch (e) {
            initial = { tokens: [], gradient_vars: [], gradient_props: [], esh_ui_vars: [], typography_vars: [], typography_props: [] };
        }
    }

    var originalColors = {};
    var originalGradVars = {};
    var originalGradProps = {};
    var originalOtherVars = {};
    var originalOtherProps = {};
    var originalTypographyVars = {};
    var originalTypographyProps = {};
    var originalEshUiVars = {};

    (initial.tokens || []).forEach(function (row) {
        originalColors[row.name] = row.value;
    });
    (initial.gradient_vars || []).forEach(function (row) {
        originalGradVars[row.id] = row.value;
    });
    (initial.gradient_props || []).forEach(function (row) {
        originalGradProps[row.id] = row.value;
    });
    (initial.other_vars || []).forEach(function (row) {
        originalOtherVars[row.id] = row.value;
    });
    (initial.other_props || []).forEach(function (row) {
        originalOtherProps[row.id] = row.value;
    });
    (initial.esh_ui_vars || []).forEach(function (row) {
        originalEshUiVars[row.id] = row.value;
    });
    (initial.typography_vars || []).forEach(function (row) {
        originalTypographyVars[row.id] = row.value;
    });
    (initial.typography_props || []).forEach(function (row) {
        originalTypographyProps[row.id] = row.value;
    });

    var frame = document.getElementById('esh-theme-preview-frame');
    var typoPreviewEl = document.getElementById('esh-typo-live-preview');
    var saveUrl = root.getAttribute('data-save-url');
    var previewSaveUrl = root.getAttribute('data-preview-save-url');
    var sessionUrl = root.getAttribute('data-session-url');
    var clearSessionUrl = root.getAttribute('data-clear-session-url');
    var themeSlug = root.getAttribute('data-theme') || '';

    var autoSessionEl = document.getElementById('esh-session-preview-auto');
    var applySessionBtn = document.getElementById('esh-session-preview-apply');
    var clearSessionBtn = document.getElementById('esh-session-preview-clear');
    var sessionBadge = document.getElementById('esh-session-preview-badge');

    var sessionDebounce = null;

    (function syncPreviewStickyOffset() {
        function applyTopChrome() {
            var chrome = 0;
            if (document.body.classList.contains('esh-theme-editor-standalone')
                || root.getAttribute('data-standalone') === '1') {
                var bar = document.querySelector('.esh-theme-editor-standalone-bar');
                if (bar) {
                    chrome = Math.ceil(bar.getBoundingClientRect().height);
                }
            } else {
                var nav = document.querySelector('nav.navbar.sticky-top');
                if (nav) {
                    chrome = Math.ceil(nav.getBoundingClientRect().height);
                }
            }
            if (chrome > 0) {
                root.style.setProperty('--esh-theme-editor-top-chrome', chrome + 'px');
            }
        }
        applyTopChrome();
        window.addEventListener('resize', applyTopChrome);
        var observeTarget = document.querySelector('.esh-theme-editor-standalone-bar')
            || document.querySelector('nav.navbar.sticky-top');
        if (observeTarget && typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(applyTopChrome).observe(observeTarget);
        }
    })();

    function parseJsonResponse(res) {
        return res.text().then(function (text) {
            var trimmed = (text || '').trim();
            var data = null;
            if (trimmed !== '') {
                try {
                    data = JSON.parse(trimmed);
                } catch (parseErr) {
                    var preview = trimmed.replace(/\s+/g, ' ').slice(0, 200);
                    throw new Error(
                        'Sunucu beklenen JSON yanıtını döndürmedi (HTTP ' + res.status + '): ' + preview
                    );
                }
            }
            return { ok: res.ok, data: data };
        });
    }

    function fetchErrorMessage(err, fallback) {
        if (err && err.message) {
            return err.message;
        }
        return fallback;
    }

    function rowByAttr(tableSelector, attr, value) {
        var rows = root.querySelectorAll(tableSelector + ' tbody tr[' + attr + ']');
        for (var i = 0; i < rows.length; i++) {
            if (rows[i].getAttribute(attr) === value) {
                return rows[i];
            }
        }
        return null;
    }

    function setSessionBadge(active) {
        if (!sessionBadge) {
            return;
        }
        sessionBadge.textContent = active ? 'Aktif' : 'Kapalı';
        sessionBadge.classList.toggle('bg-info', active);
        sessionBadge.classList.toggle('bg-secondary', !active);

        var isStandalone = root.getAttribute('data-standalone') === '1';
        var mainHint = document.getElementById('esh-session-preview-main-hint');
        var focusMainBtn = document.getElementById('esh-session-preview-focus-main');
        if (mainHint && isStandalone) {
            mainHint.classList.toggle('d-none', !active);
        }
        if (focusMainBtn && isStandalone) {
            focusMainBtn.classList.toggle('d-none', !active);
        }
    }

    function focusMainAppTab() {
        if (window.opener && !window.opener.closed) {
            try {
                window.opener.focus();
                return;
            } catch (e) {
                /* opener başka origin olabilir */
            }
        }
        var mainUrl = root.getAttribute('data-main-app-url');
        if (mainUrl) {
            window.open(mainUrl, '_blank');
        }
    }

    var focusMainBtn = document.getElementById('esh-session-preview-focus-main');
    if (focusMainBtn) {
        focusMainBtn.addEventListener('click', focusMainAppTab);
    }

    setSessionBadge(!!initial.sessionPreviewActive);

    function collectColors() {
        var updates = {};
        root.querySelectorAll('#esh-color-token-table tbody tr[data-var-name]').forEach(function (row) {
            var name = row.getAttribute('data-var-name');
            var input = row.querySelector('.esh-token-value');
            if (!name || !input) {
                return;
            }
            updates[name] = input.value.trim();
        });
        return updates;
    }

    function collectGradientVars() {
        var updates = {};
        root.querySelectorAll('#esh-gradient-var-table tbody tr[data-token-id]').forEach(function (row) {
            var id = row.getAttribute('data-token-id');
            var input = row.querySelector('.esh-token-value');
            if (!id || !input) {
                return;
            }
            updates[id] = input.value.trim();
        });
        return updates;
    }

    function collectGradientProps() {
        var updates = {};
        root.querySelectorAll('#esh-gradient-prop-table tbody tr[data-token-id]').forEach(function (row) {
            var id = row.getAttribute('data-token-id');
            var input = row.querySelector('.esh-token-value');
            if (!id || !input) {
                return;
            }
            updates[id] = input.value.trim();
        });
        return updates;
    }

    function collectOtherVars() {
        var updates = {};
        root.querySelectorAll('#esh-other-var-table tbody tr[data-token-id]').forEach(function (row) {
            var id = row.getAttribute('data-token-id');
            var input = row.querySelector('.esh-token-value');
            if (!id || !input) {
                return;
            }
            updates[id] = input.value.trim();
        });
        return updates;
    }

    function collectOtherProps() {
        var updates = {};
        root.querySelectorAll('#esh-other-prop-table tbody tr[data-token-id]').forEach(function (row) {
            var id = row.getAttribute('data-token-id');
            var input = row.querySelector('.esh-token-value');
            if (!id || !input) {
                return;
            }
            updates[id] = input.value.trim();
        });
        return updates;
    }

    function collectEshUiVars() {
        var updates = {};
        root.querySelectorAll('#esh-esh-ui-var-table tbody tr[data-token-id], #esh-typography-esh-ui-table tbody tr[data-token-id]').forEach(function (row) {
            var id = row.getAttribute('data-token-id');
            var input = row.querySelector('.esh-token-value');
            if (!id || !input) {
                return;
            }
            updates[id] = input.value.trim();
        });
        return updates;
    }

    function collectTypographyVars() {
        var updates = {};
        root.querySelectorAll('#esh-typography-var-table tbody tr[data-token-id]').forEach(function (row) {
            var id = row.getAttribute('data-token-id');
            var input = row.querySelector('.esh-token-value');
            if (!id || !input) {
                return;
            }
            updates[id] = input.value.trim();
        });
        return updates;
    }

    function collectTypographyProps() {
        var updates = {};
        root.querySelectorAll('#esh-typography-prop-table tbody tr[data-token-id]').forEach(function (row) {
            var id = row.getAttribute('data-token-id');
            var input = row.querySelector('.esh-token-value');
            if (!id || !input) {
                return;
            }
            updates[id] = input.value.trim();
        });
        return updates;
    }

    function eshUiVarValueByName(name) {
        var eshUi = collectEshUiVars();
        var key = 'var:' + name;
        if (eshUi[key] !== undefined) {
            return eshUi[key];
        }
        if (eshUi[name] !== undefined) {
            return eshUi[name];
        }
        return '';
    }

    function applyTypoLivePreview() {
        var ff = eshUiVarValueByName('--esh-ui-font-family');
        var fs = eshUiVarValueByName('--esh-ui-font-size-base');
        var lh = eshUiVarValueByName('--esh-ui-line-height');
        var hs = eshUiVarValueByName('--esh-ui-heading-size');
        var ls = eshUiVarValueByName('--esh-ui-lead-size');
        var hw = eshUiVarValueByName('--esh-ui-font-weight-heading');

        if (typoPreviewEl) {
            if (ff) {
                typoPreviewEl.style.setProperty('--esh-ui-font-family', ff);
                typoPreviewEl.style.fontFamily = ff;
            }
            if (fs) {
                typoPreviewEl.style.setProperty('--esh-ui-font-size-base', fs);
                typoPreviewEl.style.fontSize = fs;
            }
            if (lh) {
                typoPreviewEl.style.setProperty('--esh-ui-line-height', lh);
                typoPreviewEl.style.lineHeight = lh;
            }
            if (hs) {
                typoPreviewEl.style.setProperty('--esh-ui-heading-size', hs);
            }
            if (ls) {
                typoPreviewEl.style.setProperty('--esh-ui-lead-size', ls);
            }
            if (hw) {
                typoPreviewEl.style.setProperty('--esh-ui-font-weight-heading', hw);
            }
        }

        if (!frame || !frame.contentDocument) {
            return;
        }
        var doc = frame.contentDocument;
        var body = doc.body;
        if (!body) {
            return;
        }
        if (ff) {
            body.style.fontFamily = ff;
        }
        if (fs) {
            body.style.fontSize = fs;
        }
        if (lh) {
            body.style.lineHeight = lh;
        }
        var page = doc.querySelector('.esh-page-theme-preview');
        if (page) {
            if (ff) {
                page.style.fontFamily = ff;
            }
            if (fs) {
                page.style.fontSize = fs;
            }
            if (lh) {
                page.style.lineHeight = lh;
            }
            var heading = page.querySelector('.esh-page__heading');
            if (heading && hs) {
                heading.style.fontSize = hs;
            }
            if (heading && hw) {
                heading.style.fontWeight = hw;
            }
            var lead = page.querySelector('.esh-page__lead');
            if (lead && ls) {
                lead.style.fontSize = ls;
            }
        }
    }

    function collectPayload() {
        return {
            theme: themeSlug,
            colors: collectColors(),
            gradient_vars: collectGradientVars(),
            gradient_props: collectGradientProps(),
            other_vars: collectOtherVars(),
            other_props: collectOtherProps(),
            typography_vars: collectTypographyVars(),
            typography_props: collectTypographyProps(),
            esh_ui_vars: collectEshUiVars()
        };
    }

    function applyStylePropsFromTable(tableSelector, body) {
        root.querySelectorAll(tableSelector + ' tbody tr[data-token-id]').forEach(function (row) {
            var property = row.getAttribute('data-token-property');
            var input = row.querySelector('.esh-token-value');
            if (!property || !input) {
                return;
            }
            body.style.setProperty(property, input.value.trim());
        });
    }

    function applyIframePreview() {
        if (!frame || !frame.contentDocument) {
            return;
        }
        var body = frame.contentDocument.body;
        if (!body) {
            return;
        }

        Object.keys(collectColors()).forEach(function (name) {
            body.style.setProperty(name, collectColors()[name]);
        });

        Object.keys(collectGradientVars()).forEach(function (id) {
            var name = id.indexOf('var:') === 0 ? id.slice(4) : id;
            body.style.setProperty(name, collectGradientVars()[id]);
        });

        Object.keys(collectOtherVars()).forEach(function (id) {
            var name = id.indexOf('var:') === 0 ? id.slice(4) : id;
            body.style.setProperty(name, collectOtherVars()[id]);
        });

        Object.keys(collectEshUiVars()).forEach(function (id) {
            var name = id.indexOf('var:') === 0 ? id.slice(4) : id;
            body.style.setProperty(name, collectEshUiVars()[id]);
        });

        applyStylePropsFromTable('#esh-gradient-prop-table', body);
        applyStylePropsFromTable('#esh-other-prop-table', body);
        applyStylePropsFromTable('#esh-typography-prop-table', body);

        Object.keys(collectTypographyVars()).forEach(function (id) {
            var name = id.indexOf('var:') === 0 ? id.slice(4) : id;
            body.style.setProperty(name, collectTypographyVars()[id]);
        });

        applyTypoLivePreview();
    }

    function syncColorSwatch(row) {
        var input = row.querySelector('.esh-token-value');
        var swatch = row.querySelector('.esh-color-swatch');
        if (input && swatch) {
            swatch.style.background = input.value.trim();
        }
    }

    function syncGradientSwatch(row) {
        var input = row.querySelector('.esh-token-value');
        var swatch = row.querySelector('.esh-gradient-swatch');
        if (input && swatch) {
            swatch.style.background = input.value.trim();
        }
    }

    function mergeSessionIntoForm(preview) {
        if (!preview || preview.theme !== themeSlug) {
            return;
        }
        var vars = preview.vars || {};
        Object.keys(vars).forEach(function (name) {
            var colorRow = rowByAttr('#esh-color-token-table', 'data-var-name', name);
            if (colorRow) {
                var input = colorRow.querySelector('.esh-token-value');
                if (input) {
                    input.value = vars[name];
                    syncColorSwatch(colorRow);
                    var picker = colorRow.querySelector('.esh-color-picker');
                    if (picker && /^#[0-9a-fA-F]{6}$/i.test(vars[name])) {
                        picker.value = vars[name].toLowerCase();
                    }
                }
                return;
            }
            var gradRow = rowByAttr('#esh-gradient-var-table', 'data-token-id', 'var:' + name);
            if (gradRow) {
                var gInput = gradRow.querySelector('.esh-token-value');
                if (gInput) {
                    gInput.value = vars[name];
                    syncGradientSwatch(gradRow);
                }
                return;
            }
            var otherRow = rowByAttr('#esh-other-var-table', 'data-token-id', 'var:' + name);
            if (otherRow) {
                var oInput = otherRow.querySelector('.esh-token-value');
                if (oInput) {
                    oInput.value = vars[name];
                }
                return;
            }
            var typoRow = rowByAttr('#esh-typography-var-table', 'data-token-id', 'var:' + name);
            if (typoRow) {
                var tInput = typoRow.querySelector('.esh-token-value');
                if (tInput) {
                    tInput.value = vars[name];
                }
                return;
            }
            var eshUiRow = rowByAttr('#esh-esh-ui-var-table', 'data-var-name', name)
                || rowByAttr('#esh-typography-esh-ui-table', 'data-var-name', name);
            if (eshUiRow) {
                var eInput = eshUiRow.querySelector('.esh-token-value');
                if (eInput) {
                    eInput.value = vars[name];
                    syncColorSwatch(eshUiRow);
                    var ePicker = eshUiRow.querySelector('.esh-color-picker');
                    if (ePicker && /^#[0-9a-fA-F]{6}$/i.test(vars[name])) {
                        ePicker.value = vars[name].toLowerCase();
                    }
                }
            }
        });

        var props = preview.properties || {};
        Object.keys(props).forEach(function (id) {
            var gradPropRow = rowByAttr('#esh-gradient-prop-table', 'data-token-id', id);
            var otherPropRow = rowByAttr('#esh-other-prop-table', 'data-token-id', id);
            var typoPropRow = rowByAttr('#esh-typography-prop-table', 'data-token-id', id);
            var row = gradPropRow || otherPropRow || typoPropRow;
            if (!row || !props[id] || !props[id].value) {
                return;
            }
            var pInput = row.querySelector('.esh-token-value');
            if (pInput) {
                pInput.value = props[id].value;
                if (gradPropRow) {
                    syncGradientSwatch(row);
                }
            }
        });
    }

    if (initial.sessionPreviewActive && initial.sessionPreview) {
        mergeSessionIntoForm(initial.sessionPreview);
    }

    function reloadThemeSheets() {
        document.querySelectorAll('link[rel="stylesheet"][href*="theme-sheet.php"]').forEach(function (link) {
            var href = link.getAttribute('href') || '';
            var slugMatch = href.match(/[?&]s=([^&]+)/);
            var slug = slugMatch ? slugMatch[1] : '';
            var base = href.split('?')[0];
            link.href = base + '?s=' + slug + '&_=' + Date.now();
        });
        if (frame) {
            var src = frame.getAttribute('src') || frame.src;
            frame.src = src.replace(/([?&])_=\d+/, '$1_=' + Date.now());
        }
    }

    function postSessionPreview() {
        if (!sessionUrl) {
            return Promise.resolve();
        }
        if (root.getAttribute('data-has-rows') === '0') {
            window.alert('Bu temada oturum önizlemesi için düzenlenebilir jeton bulunmuyor.');
            return Promise.resolve();
        }
        return fetch(sessionUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(collectPayload())
        })
            .then(parseJsonResponse)
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.ok) {
                    window.alert((result.data && result.data.error) ? result.data.error : 'Oturum önizlemesi uygulanamadı.');
                    setSessionBadge(false);
                    return;
                }
                setSessionBadge(true);
                reloadThemeSheets();
            })
            .catch(function (err) {
                window.alert(fetchErrorMessage(err, 'Oturum önizlemesi sırasında hata oluştu.'));
            });
    }

    function scheduleAutoSession() {
        if (!autoSessionEl || !autoSessionEl.checked) {
            return;
        }
        clearTimeout(sessionDebounce);
        sessionDebounce = setTimeout(postSessionPreview, 450);
    }

    root.addEventListener('input', function (event) {
        var target = event.target;
        var row = target.closest('tr');

        if (target.classList && target.classList.contains('esh-color-picker') && row) {
            var colorText = row.querySelector('.esh-token-value');
            if (colorText) {
                colorText.value = (target.value || '').toLowerCase();
            }
        }

        if (target.classList && target.classList.contains('esh-token-value') && row) {
            var picker = row.querySelector('.esh-color-picker');
            if (picker && /^#[0-9a-fA-F]{6}$/.test(target.value.trim())) {
                picker.value = target.value.trim().toLowerCase();
            }
            if (row.getAttribute('data-token-kind') === 'color' || row.getAttribute('data-token-kind') === 'esh-ui') {
                syncColorSwatch(row);
            } else if (row.querySelector('.esh-gradient-swatch')) {
                syncGradientSwatch(row);
            }
        }

        applyIframePreview();
        scheduleAutoSession();
    });

    var resetBtn = document.getElementById('esh-theme-editor-reset');

    function resetSingleTokenRow(row) {
        if (!row || row.classList.contains('esh-token-group-row')) {
            return;
        }

        var varName = row.getAttribute('data-var-name');
        if (varName && varName in originalColors) {
            var colorInput = row.querySelector('.esh-token-value');
            var colorPicker = row.querySelector('.esh-color-picker');
            if (colorInput) {
                colorInput.value = originalColors[varName];
            }
            if (colorPicker && /^#[0-9a-fA-F]{6}$/i.test(originalColors[varName])) {
                colorPicker.value = originalColors[varName].toLowerCase();
            }
            syncColorSwatch(row);
            return;
        }

        var tokenId = row.getAttribute('data-token-id');
        if (!tokenId) {
            return;
        }

        var tokenInput = row.querySelector('.esh-token-value');
        if (!tokenInput) {
            return;
        }

        if (tokenId in originalGradVars) {
            tokenInput.value = originalGradVars[tokenId];
            syncGradientSwatch(row);
            return;
        }
        if (tokenId in originalGradProps) {
            tokenInput.value = originalGradProps[tokenId];
            syncGradientSwatch(row);
            return;
        }
        if (tokenId in originalOtherVars) {
            tokenInput.value = originalOtherVars[tokenId];
            return;
        }
        if (tokenId in originalOtherProps) {
            tokenInput.value = originalOtherProps[tokenId];
            return;
        }
        if (tokenId in originalEshUiVars) {
            tokenInput.value = originalEshUiVars[tokenId];
            syncColorSwatch(row);
            var eshPicker = row.querySelector('.esh-color-picker');
            if (eshPicker && /^#[0-9a-fA-F]{6}$/i.test(originalEshUiVars[tokenId])) {
                eshPicker.value = originalEshUiVars[tokenId].toLowerCase();
            }
            return;
        }
        if (tokenId in originalTypographyVars) {
            tokenInput.value = originalTypographyVars[tokenId];
            return;
        }
        if (tokenId in originalTypographyProps) {
            tokenInput.value = originalTypographyProps[tokenId];
        }
    }

    function resetGroupTokenRows(groupRow) {
        var tbody = groupRow && groupRow.parentElement;
        if (!tbody) {
            return;
        }
        var rows = Array.prototype.slice.call(tbody.children);
        var start = rows.indexOf(groupRow) + 1;
        for (var i = start; i < rows.length; i++) {
            if (rows[i].classList.contains('esh-token-group-row')) {
                break;
            }
            resetSingleTokenRow(rows[i]);
        }
        applyIframePreview();
        scheduleAutoSession();
    }

    function resetAllTokenValues() {
        root.querySelectorAll('#esh-color-token-table tbody tr[data-var-name]').forEach(resetSingleTokenRow);
        root.querySelectorAll('#esh-gradient-var-table tbody tr[data-token-id]').forEach(resetSingleTokenRow);
        root.querySelectorAll('#esh-gradient-prop-table tbody tr[data-token-id]').forEach(resetSingleTokenRow);
        root.querySelectorAll('#esh-other-var-table tbody tr[data-token-id]').forEach(resetSingleTokenRow);
        root.querySelectorAll('#esh-other-prop-table tbody tr[data-token-id]').forEach(resetSingleTokenRow);
        root.querySelectorAll('#esh-esh-ui-var-table tbody tr[data-token-id], #esh-typography-esh-ui-table tbody tr[data-token-id]').forEach(resetSingleTokenRow);
        root.querySelectorAll('#esh-typography-var-table tbody tr[data-token-id]').forEach(resetSingleTokenRow);
        root.querySelectorAll('#esh-typography-prop-table tbody tr[data-token-id]').forEach(resetSingleTokenRow);
        applyIframePreview();
        scheduleAutoSession();
    }

    function initTokenResetControls() {
        root.querySelectorAll('.esh-token-group-row td[colspan]').forEach(function (cell) {
            if (cell.querySelector('.esh-token-group-reset')) {
                return;
            }
            var label = cell.textContent.trim();
            cell.textContent = '';
            var wrap = document.createElement('div');
            wrap.className = 'd-flex align-items-center justify-content-between gap-2 esh-token-group-head';
            var title = document.createElement('span');
            title.textContent = label;
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline-secondary btn-sm py-0 px-2 esh-token-group-reset';
            btn.title = 'Bu gruptaki jetonları dosyadaki değerlere döndür';
            btn.setAttribute('aria-label', 'Grubu sıfırla');
            btn.innerHTML = '<i class="fa-solid fa-rotate-left me-1"></i>Grubu sıfırla';
            wrap.appendChild(title);
            wrap.appendChild(btn);
            cell.appendChild(wrap);
            btn.addEventListener('click', function () {
                resetGroupTokenRows(cell.closest('tr'));
            });
        });

        root.querySelectorAll('table tbody tr[data-token-id], table tbody tr[data-var-name]').forEach(function (row) {
            if (row.classList.contains('esh-token-group-row') || row.querySelector('.esh-token-row-reset')) {
                return;
            }
            var input = row.querySelector('.esh-token-value');
            if (!input || !input.parentElement) {
                return;
            }
            var fieldWrap = document.createElement('div');
            fieldWrap.className = 'd-flex align-items-start gap-1 esh-token-value-wrap';
            input.parentElement.insertBefore(fieldWrap, input);
            fieldWrap.appendChild(input);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-outline-secondary btn-sm esh-token-row-reset flex-shrink-0';
            btn.title = 'Dosyadaki değere dön';
            btn.setAttribute('aria-label', 'Jetonu sıfırla');
            btn.innerHTML = '<i class="fa-solid fa-rotate-left"></i>';
            fieldWrap.appendChild(btn);
            btn.addEventListener('click', function () {
                resetSingleTokenRow(row);
                applyIframePreview();
                scheduleAutoSession();
            });
        });
    }

    initTokenResetControls();

    if (resetBtn) {
        resetBtn.addEventListener('click', resetAllTokenValues);
    }

    if (frame) {
        frame.addEventListener('load', function () {
            applyIframePreview();
            var strip = document.getElementById('esh-esh-ui-module-strip');
            if (!strip) {
                return;
            }
            var active = strip.querySelector('[data-esh-ui-module].active');
            if (active) {
                scrollPreviewToModule(active.getAttribute('data-esh-ui-module') || 'all');
            }
        });
    }

    if (applySessionBtn) {
        applySessionBtn.addEventListener('click', function () {
            applySessionBtn.disabled = true;
            postSessionPreview().finally(function () {
                applySessionBtn.disabled = false;
            });
        });
    }

    if (clearSessionBtn && clearSessionUrl) {
        clearSessionBtn.addEventListener('click', function () {
            clearSessionBtn.disabled = true;
            fetch(clearSessionUrl, { method: 'POST', credentials: 'same-origin' })
                .then(parseJsonResponse)
                .then(function (result) {
                    if (!result.data || !result.data.ok) {
                        window.alert((result.data && result.data.error) ? result.data.error : 'Temizlenemedi.');
                        return;
                    }
                    setSessionBadge(false);
                    reloadThemeSheets();
                    applyIframePreview();
                })
                .catch(function (err) {
                    window.alert(fetchErrorMessage(err, 'Oturum önizlemesi temizlenirken hata oluştu.'));
                })
                .finally(function () {
                    clearSessionBtn.disabled = false;
                });
        });
    }

    function formatBackupLabel(backupPath) {
        if (!backupPath) {
            return '';
        }
        var parts = String(backupPath).replace(/\\/g, '/').split('/');
        if (parts.length >= 3) {
            return parts.slice(-3).join('/');
        }
        return parts[parts.length - 1] || backupPath;
    }

    function formatSaveChanges(changes) {
        if (!changes || !changes.length) {
            return 'Değişiklik listesi boş.';
        }
        return changes.map(function (entry) {
            return entry.label + '\n  − ' + entry.before + '\n  + ' + entry.after;
        }).join('\n\n');
    }

    function applySaveSuccess(data) {
        var msg = data.message || 'Kaydedildi.';
        if (data.backup) {
            msg += '\n\nYedek: ' + formatBackupLabel(data.backup);
        }
        window.alert(msg);

        if (data.tokens) {
            data.tokens.forEach(function (row) {
                originalColors[row.name] = row.value;
            });
        }
        if (data.gradient_vars) {
            data.gradient_vars.forEach(function (row) {
                originalGradVars[row.id] = row.value;
            });
        }
        if (data.gradient_props) {
            data.gradient_props.forEach(function (row) {
                originalGradProps[row.id] = row.value;
            });
        }
        if (data.other_vars) {
            data.other_vars.forEach(function (row) {
                originalOtherVars[row.id] = row.value;
            });
        }
        if (data.other_props) {
            data.other_props.forEach(function (row) {
                originalOtherProps[row.id] = row.value;
            });
        }
        if (data.esh_ui_vars) {
            data.esh_ui_vars.forEach(function (row) {
                originalEshUiVars[row.id] = row.value;
            });
        }
        if (data.typography_vars) {
            data.typography_vars.forEach(function (row) {
                originalTypographyVars[row.id] = row.value;
            });
        }
        if (data.typography_props) {
            data.typography_props.forEach(function (row) {
                originalTypographyProps[row.id] = row.value;
            });
        }
        setSessionBadge(false);
        reloadThemeSheets();
    }

    function executeSave() {
        if (!saveUrl) {
            return Promise.resolve();
        }
        return fetch(saveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(collectPayload())
        })
            .then(parseJsonResponse)
            .then(function (result) {
                if (!result.ok || !result.data || !result.data.ok) {
                    window.alert((result.data && result.data.error) ? result.data.error : 'Kayıt başarısız.');
                    return;
                }
                applySaveSuccess(result.data);
            })
            .catch(function (err) {
                window.alert(fetchErrorMessage(err, 'Kayıt sırasında bir hata oluştu.'));
            });
    }

    var saveDiffModal = document.getElementById('esh-theme-save-diff-modal');
    var saveDiffConfirmBtn = document.getElementById('esh-theme-save-diff-confirm');

    if (saveDiffConfirmBtn) {
        saveDiffConfirmBtn.addEventListener('click', function () {
            saveDiffConfirmBtn.disabled = true;
            executeSave().finally(function () {
                saveDiffConfirmBtn.disabled = false;
                if (saveDiffModal && window.bootstrap && window.bootstrap.Modal) {
                    var modal = window.bootstrap.Modal.getInstance(saveDiffModal);
                    if (modal) {
                        modal.hide();
                    }
                }
            });
        });
    }

    var saveBtn = document.getElementById('esh-theme-editor-save');
    if (saveBtn && saveUrl) {
        saveBtn.addEventListener('click', function () {
            saveBtn.disabled = true;

            function finishSaveUi() {
                saveBtn.disabled = false;
            }

            if (!previewSaveUrl) {
                executeSave().finally(finishSaveUi);
                return;
            }

            fetch(previewSaveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(collectPayload())
            })
                .then(parseJsonResponse)
                .then(function (result) {
                    if (!result.ok || !result.data || !result.data.ok) {
                        window.alert((result.data && result.data.error) ? result.data.error : 'Önizleme başarısız.');
                        return;
                    }
                    if (!result.data.changed) {
                        window.alert(result.data.message || 'Kaydedilecek değişiklik yok.');
                        return;
                    }
                    if (!saveDiffModal || !window.bootstrap || !window.bootstrap.Modal) {
                        executeSave();
                        return;
                    }
                    var summaryEl = document.getElementById('esh-theme-save-diff-summary');
                    var contentEl = document.getElementById('esh-theme-save-diff-content');
                    if (summaryEl) {
                        summaryEl.textContent = result.data.message || (result.data.changed + ' girdi güncellenecek.');
                    }
                    if (contentEl) {
                        contentEl.textContent = formatSaveChanges(result.data.changes || []);
                    }
                    window.bootstrap.Modal.getOrCreateInstance(saveDiffModal).show();
                })
                .catch(function (err) {
                    window.alert(fetchErrorMessage(err, 'Kayıt önizlemesi sırasında hata oluştu.'));
                })
                .finally(finishSaveUi);
        });
    }

    function getEshUiPreviewAnchors() {
        var raw = root.getAttribute('data-esh-ui-preview-anchors') || '{}';
        try {
            return JSON.parse(raw);
        } catch (e) {
            return {};
        }
    }

    function scrollPreviewToModule(moduleId) {
        if (!frame || !frame.contentWindow) {
            return;
        }
        var doc = frame.contentDocument;
        if (!doc) {
            return;
        }
        var scrollRoot = doc.scrollingElement || doc.documentElement || doc.body;
        if (moduleId === 'all') {
            if (scrollRoot) {
                scrollRoot.scrollTo({ top: 0, behavior: 'smooth' });
            }
            return;
        }
        var anchors = getEshUiPreviewAnchors();
        var anchorId = anchors[moduleId] || anchors.all || 'esh-preview-mod-kabuk';
        var target = doc.getElementById(anchorId);
        if (!target) {
            return;
        }
        if (typeof target.scrollIntoView === 'function') {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        if (!scrollRoot) {
            return;
        }
        var nav = doc.querySelector('nav.navbar');
        var navHeight = nav ? nav.getBoundingClientRect().height : 0;
        var top = target.getBoundingClientRect().top + scrollRoot.scrollTop - navHeight - 12;
        scrollRoot.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
    }

    function refreshEshUiModuleGroupHeaders(tbody) {
        var rows = tbody.querySelectorAll('tr');
        for (var i = 0; i < rows.length; i++) {
            if (!rows[i].classList.contains('esh-token-group-row')) {
                continue;
            }
            var j = i + 1;
            var anyVisible = false;
            while (j < rows.length && !rows[j].classList.contains('esh-token-group-row')) {
                if (!rows[j].classList.contains('esh-esh-ui-module-hidden')) {
                    anyVisible = true;
                    break;
                }
                j++;
            }
            rows[i].classList.toggle('esh-esh-ui-module-hidden', !anyVisible);
        }
    }

    function applyEshUiModuleFilter(moduleId) {
        var table = document.getElementById('esh-esh-ui-var-table');
        var strip = document.getElementById('esh-esh-ui-module-strip');
        if (!table || !strip) {
            return;
        }
        var tbody = table.querySelector('tbody');
        if (!tbody) {
            return;
        }
        var showAll = moduleId === 'all';
        tbody.querySelectorAll('tr[data-esh-ui-module]').forEach(function (row) {
            if (row.classList.contains('esh-token-group-row')) {
                return;
            }
            var mod = row.getAttribute('data-esh-ui-module') || '';
            row.classList.toggle('esh-esh-ui-module-hidden', !showAll && mod !== moduleId);
        });
        refreshEshUiModuleGroupHeaders(tbody);
        strip.querySelectorAll('[data-esh-ui-module]').forEach(function (btn) {
            var active = btn.getAttribute('data-esh-ui-module') === moduleId;
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        scrollPreviewToModule(moduleId);
    }

    (function initEshUiModuleFilter() {
        var strip = document.getElementById('esh-esh-ui-module-strip');
        if (!strip) {
            return;
        }
        strip.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-esh-ui-module]');
            if (!btn || !strip.contains(btn)) {
                return;
            }
            applyEshUiModuleFilter(btn.getAttribute('data-esh-ui-module') || 'all');
        });
    })();
})();
