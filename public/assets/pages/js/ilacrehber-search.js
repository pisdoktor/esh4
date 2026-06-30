/**

 * İlaç rehberi — sekme + sol arama / sağ sonuç listesi (AJAX).

 */

jQuery(function ($) {

    var root = document.getElementById('esh-ilac-rehber-search');

    if (!root) {

        return;

    }



    var DEBOUNCE_MS = 200;

    var MIN_LEN = 1;



    var $results = $('#esh-rehber-search-results');

    var $placeholder = $('#esh-rehber-search-placeholder');

    var $hint = $('#esh-rehber-search-results-hint');

    var $etkenQ = $('#esh-rehber-search-etken-q');

    var $ilacQ = $('#esh-rehber-search-ilac-q');

    var $tabs = $('#esh-rehber-search-tabs');



    var activeMode = 'etken';

    var debounceTimer = -1;

    var lastRequestId = 0;



    var etkenAjaxUrl = root.getAttribute('data-etken-ajax-url') || '';

    var ilacAjaxUrl = root.getAttribute('data-ilac-ajax-url') || '';



    var modeHints = {

        etken: 'Etken madde araması',

        ilac: 'İlaç ismi araması',

    };



    function escHtml(s) {

        return String(s)

            .replace(/&/g, '&amp;')

            .replace(/</g, '&lt;')

            .replace(/>/g, '&gt;')

            .replace(/"/g, '&quot;');

    }



    function placeholderMsg(mode) {

        if ($placeholder.length) {

            var attr = mode === 'ilac' ? 'data-ilac-msg' : 'data-etken-msg';

            return $placeholder.attr(attr) || '';

        }

        return mode === 'ilac' ? 'İlaç adı yazarak arayın.' : 'Etken madde adı yazarak arayın.';

    }



    function showPlaceholder(mode) {

        renderEmpty(placeholderMsg(mode));

    }



    function renderEmpty(msg) {

        $results.html(

            '<div class="list-group-item text-muted small py-4 text-center">' + escHtml(msg) + '</div>'

        );

    }



    function renderLoading() {

        $results.html(

            '<div class="list-group-item text-muted small py-4 text-center">Aranıyor…</div>'

        );

    }



    function renderEtkenItems(items) {

        if (!items || !items.length) {

            renderEmpty('Sonuç bulunamadı.');

            return;

        }

        var html = '';

        items.forEach(function (it) {

            var href = it.url || '';

            html +=

                '<a href="' +

                escHtml(href) +

                '" class="list-group-item list-group-item-action py-3" role="listitem">' +

                '<span class="fw-semibold">' +

                escHtml(it.ad) +

                '</span></a>';

        });

        $results.html(html);

    }



    function renderIlacItems(items) {

        if (!items || !items.length) {

            renderEmpty('Sonuç bulunamadı.');

            return;

        }

        var html = '';

        items.forEach(function (it) {

            var href = it.url || '';

            var sub = it.subtitle || it.etken_ad || '';

            html +=

                '<a href="' +

                escHtml(href) +

                '" class="list-group-item list-group-item-action py-3" role="listitem">' +

                '<div class="d-flex justify-content-between align-items-start gap-2">' +

                '<span class="fw-semibold">' +

                escHtml(it.ad) +

                '</span>';

            if (it.source_url) {

                html +=

                    '<span class="badge bg-light text-dark border flex-shrink-0">Kaynak</span>';

            }

            html += '</div>';

            if (sub) {

                html += '<span class="small text-muted d-block mt-1">' + escHtml(sub) + '</span>';

            }

            html += '</a>';

        });

        $results.html(html);

    }



    function activeQuery() {

        return String((activeMode === 'ilac' ? $ilacQ : $etkenQ).val() || '').trim();

    }



    function activeAjaxUrl() {

        return activeMode === 'ilac' ? ilacAjaxUrl : etkenAjaxUrl;

    }



    function updateHint() {

        if ($hint.length) {

            $hint.text(modeHints[activeMode] || '');

        }

    }



    function fetchQuery(q, mode) {

        var ajaxUrl = mode === 'ilac' ? ilacAjaxUrl : etkenAjaxUrl;

        if (!ajaxUrl) {

            renderEmpty('Arama adresi tanımlı değil.');

            return;

        }



        var requestId = ++lastRequestId;

        renderLoading();



        $.getJSON(ajaxUrl, { q: q })

            .done(function (res) {

                if (requestId !== lastRequestId || mode !== activeMode) {

                    return;

                }

                if (!res || !res.ok) {

                    renderEmpty((res && res.error) || 'Arama başarısız.');

                    return;

                }

                if (mode === 'ilac') {

                    renderIlacItems(res.items || []);

                } else {

                    renderEtkenItems(res.items || []);

                }

            })

            .fail(function () {

                if (requestId !== lastRequestId || mode !== activeMode) {

                    return;

                }

                renderEmpty('Sunucuya ulaşılamadı.');

            });

    }



    function scheduleSearch() {

        var q = activeQuery();

        if (q.length < MIN_LEN) {

            lastRequestId += 1;

            showPlaceholder(activeMode);

            return;

        }

        if (debounceTimer !== -1) {

            window.clearTimeout(debounceTimer);

        }

        var mode = activeMode;

        debounceTimer = window.setTimeout(function () {

            fetchQuery(q, mode);

            debounceTimer = -1;

        }, DEBOUNCE_MS);

    }



    function setActiveMode(mode) {

        if (mode !== 'etken' && mode !== 'ilac') {

            return;

        }

        activeMode = mode;

        updateHint();

        scheduleSearch();

    }



    $etkenQ.on('input', function () {

        if (activeMode === 'etken') {

            scheduleSearch();

        }

    });



    $ilacQ.on('input', function () {

        if (activeMode === 'ilac') {

            scheduleSearch();

        }

    });



    if ($tabs.length) {

        $tabs.on('shown.bs.tab', function (e) {

            var btn = e.target;

            if (!btn || !btn.getAttribute) {

                return;

            }

            var mode = btn.getAttribute('data-esh-rehber-mode');

            setActiveMode(mode || 'etken');

        });

    }



    updateHint();

});

