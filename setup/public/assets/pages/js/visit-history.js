jQuery(function ($) {
    var tbody = document.getElementById('esh-visit-history-tbody');
    if (tbody) {
        var fetchUrl = tbody.getAttribute('data-esh-fetch-url');
        if (fetchUrl) {
            function escapeHtml(text) {
                var d = document.createElement('div');
                d.textContent = text || '';
                return d.innerHTML;
            }
            function showFetchError(message) {
                tbody.innerHTML = '<tr class="esh-visit-history-error-row"><td colspan="6" class="border-0 py-4 text-center text-danger">'
                    + escapeHtml(message) + '</td></tr>';
            }
            eshFetchListHtml(fetchUrl).then(function (data) { tbody.innerHTML = data.html;
            }).catch(function (err) {
                showFetchError(err && err.message ? err.message : 'Ağ hatası; bağlantınızı kontrol edin.');
            });
        }
    }

    var $collapse = $('#history-filter-collapse');
    var $toggleText = $('#history-filter-toggle .js-filter-toggle-text');

    if ($collapse.length && $toggleText.length) {
        $collapse.on('shown.bs.collapse', function () {
            $toggleText.text('Filtreleri Gizle');
        });
        $collapse.on('hidden.bs.collapse', function () {
            $toggleText.text('Filtreleri Göster');
        });
    }

    $('.esh-history-patient-dropdown').each(function () {
        var $root = $(this);
        var toggleEl = $root.find('[data-bs-toggle="dropdown"]')[0];
        if (!toggleEl || typeof bootstrap === 'undefined' || !bootstrap.Dropdown) {
            return;
        }
        var dd = bootstrap.Dropdown.getOrCreateInstance(toggleEl);
        var hideTimer = null;

        $root.on('mouseenter', function () {
            clearTimeout(hideTimer);
            dd.show();
        });
        $root.on('mouseleave', function () {
            hideTimer = setTimeout(function () {
                dd.hide();
            }, 220);
        });
    });

    function eshEk3MetaUrl(visitId) {
        visitId = String(visitId || '').trim();
        if (!visitId) {
            return '';
        }
        return eshUrl('Visit', 'ek3Document', { visit_id: visitId, meta: 1 });
    }

    function eshEk3ResetTabs() {
        var wrap = document.getElementById('eshEk3DocumentTabsWrap');
        var tabList = document.getElementById('eshEk3DocumentTabList');
        var modalEl = document.getElementById('eshEk3DocumentModal');
        if (tabList) {
            tabList.innerHTML = '';
        }
        if (wrap) {
            wrap.classList.add('d-none');
        }
        if (modalEl) {
            modalEl.classList.remove('esh-ek3-has-tabs');
        }
    }

    function eshEk3SetActiveDocUrl(docUrl) {
        var newTab = document.getElementById('eshEk3DocumentNewTab');
        if (newTab && docUrl) {
            newTab.href = docUrl;
        }
    }

    function eshEk3ActivateTab(tabBtn, frame, tabList) {
        if (!tabBtn || !frame || !tabList) {
            return;
        }
        var docUrl = tabBtn.getAttribute('data-doc-url') || '';
        if (!docUrl) {
            return;
        }
        tabList.querySelectorAll('.nav-link').forEach(function (link) {
            link.classList.remove('active');
            link.setAttribute('aria-selected', 'false');
        });
        tabBtn.classList.add('active');
        tabBtn.setAttribute('aria-selected', 'true');

        var currentUrl = frame.getAttribute('data-current-doc-url') || '';
        if (currentUrl !== docUrl) {
            frame.src = docUrl;
            frame.setAttribute('data-current-doc-url', docUrl);
            tabBtn.setAttribute('data-loaded', '1');
        }
        eshEk3SetActiveDocUrl(docUrl);
    }

    function eshEk3RenderTabs(tabs, frame) {
        var wrap = document.getElementById('eshEk3DocumentTabsWrap');
        var tabList = document.getElementById('eshEk3DocumentTabList');
        var modalEl = document.getElementById('eshEk3DocumentModal');
        if (!wrap || !tabList || !frame || !tabs || !tabs.length) {
            return;
        }

        eshEk3ResetTabs();
        tabList.innerHTML = '';

        var multi = tabs.length > 1;
        if (multi) {
            wrap.classList.remove('d-none');
            if (modalEl) {
                modalEl.classList.add('esh-ek3-has-tabs');
            }
        }

        tabs.forEach(function (tab, index) {
            var li = document.createElement('li');
            li.className = 'nav-item';
            li.setAttribute('role', 'presentation');

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'nav-link' + (index === 0 ? ' active' : '');
            btn.setAttribute('role', 'tab');
            btn.setAttribute('data-doc-url', tab.docUrl || '');
            btn.setAttribute('data-brans-id', String(tab.bransId || ''));
            btn.setAttribute('aria-selected', index === 0 ? 'true' : 'false');
            btn.textContent = tab.bransName || ('Branş #' + (tab.bransId || ''));

            btn.addEventListener('click', function () {
                eshEk3ActivateTab(btn, frame, tabList);
            });

            li.appendChild(btn);
            tabList.appendChild(li);
        });

        frame.removeAttribute('data-current-doc-url');
        var firstTab = tabList.querySelector('.nav-link');
        if (firstTab) {
            eshEk3ActivateTab(firstTab, frame, tabList);
        }
    }

    function eshCleanEk3OpenFromUrl() {
        try {
            var cleanUrl = new URL(window.location.href);
            if (!cleanUrl.searchParams.has('ek3_open')) {
                return;
            }
            cleanUrl.searchParams.delete('ek3_open');
            var qs = cleanUrl.searchParams.toString();
            history.replaceState({}, '', cleanUrl.pathname + (qs ? '?' + qs : '') + cleanUrl.hash);
        } catch (ignoreClean) { /* eski tarayıcı */ }
    }

    window.eshOpenEk3FromHistory = function (visitId) {
        var metaUrl = eshEk3MetaUrl(visitId);
        var modalEl = document.getElementById('eshEk3DocumentModal');
        var frame = document.getElementById('eshEk3DocumentFrame');
        if (!metaUrl || !modalEl || !frame || typeof bootstrap === 'undefined' || !bootstrap.Modal) {
            return false;
        }
        if (typeof eshFetchJson !== 'function') {
            return false;
        }
        if (modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }
        $('.esh-history-patient-dropdown [data-bs-toggle="dropdown"]').each(function () {
            if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                var inst = bootstrap.Dropdown.getInstance(this);
                if (inst) {
                    inst.hide();
                }
            }
        });

        eshFetchJson(metaUrl).then(function (data) {
            var tabs = data && Array.isArray(data.tabs) ? data.tabs : [];
            if (!tabs.length) {
                throw new Error('EK-3 sekmeleri yüklenemedi.');
            }
            eshEk3RenderTabs(tabs, frame);
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
            eshCleanEk3OpenFromUrl();
        }).catch(function (err) {
            var msg = err && err.message ? err.message : 'EK-3 formu açılamadı.';
            if (typeof eshNotifyAppError === 'function') {
                eshNotifyAppError(msg);
            } else {
                window.alert(msg);
            }
        });

        return false;
    };

    var modalEl = document.getElementById('eshEk3DocumentModal');
    var frame = document.getElementById('eshEk3DocumentFrame');
    if (modalEl && frame) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            frame.src = 'about:blank';
            frame.removeAttribute('data-current-doc-url');
            eshEk3ResetTabs();
            eshEk3SetActiveDocUrl('#');
        });
    }

    var autoOpenId = '';
    var $ek3Trigger = $('#eshEk3OpenTrigger');
    if ($ek3Trigger.length) {
        autoOpenId = String($ek3Trigger.attr('data-visit-id') || '').trim();
    }
    if (!autoOpenId) {
        try {
            var pageUrl = new URL(window.location.href);
            autoOpenId = String(pageUrl.searchParams.get('ek3_open') || '').trim();
        } catch (ignoreUrl) { /* eski tarayıcı */ }
    }
    if (autoOpenId) {
        window.eshOpenEk3FromHistory(autoOpenId);
    }
});
