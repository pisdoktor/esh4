/**
 * İlaç rehberi — etken madde arama (AJAX).
 */
jQuery(function ($) {
    var root = document.getElementById('esh-ilac-rehber-index');
    if (!root) {
        return;
    }
    var ajaxUrl = root.getAttribute('data-etken-ajax-url') || '';
    var $q = $('#esh-rehber-etken-q');
    var $results = $('#esh-rehber-etken-results');
    var $placeholder = $('#esh-rehber-etken-placeholder');
    var debounceTimer = -1;
    var DEBOUNCE_MS = 200;
    var MIN_LEN = 1;

    function trLower(s) {
        return String(s).toLocaleLowerCase('tr-TR');
    }

    function foldTrSearch(s) {
        return trLower(String(s || ''))
            .replace(/ı/g, 'i')
            .replace(/ş/g, 's')
            .replace(/ğ/g, 'g')
            .replace(/ü/g, 'u')
            .replace(/ö/g, 'o')
            .replace(/ç/g, 'c');
    }

    function escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderEmpty(msg) {
        $results.html(
            '<div class="list-group-item text-muted small py-4 text-center">' + escHtml(msg) + '</div>'
        );
    }

    function renderItems(items) {
        if (!items || !items.length) {
            renderEmpty('Sonuç bulunamadı.');
            return;
        }
        var html = '';
        items.forEach(function (it) {
            var href = it.url || ('?controller=IlacRehber&action=etken&id=' + encodeURIComponent(String(it.id)));
            html +=
                '<a href="' +
                escHtml(href) +
                '" class="list-group-item list-group-item-action py-3">' +
                '<span class="fw-semibold">' +
                escHtml(it.ad) +
                '</span></a>';
        });
        $results.html(html);
    }

    function fetchEtken(q) {
        if (!ajaxUrl) {
            renderEmpty('Arama adresi tanımlı değil.');
            return;
        }
        $results.html(
            '<div class="list-group-item text-muted small py-4 text-center">Aranıyor…</div>'
        );
        $.getJSON(ajaxUrl, { q: q })
            .done(function (res) {
                if (!res || !res.ok) {
                    renderEmpty((res && res.error) || 'Arama başarısız.');
                    return;
                }
                renderItems(res.items || []);
            })
            .fail(function () {
                renderEmpty('Sunucuya ulaşılamadı.');
            });
    }

    $q.on('input', function () {
        var raw = String($q.val() || '').trim();
        if (raw.length < MIN_LEN) {
            if ($placeholder.length) {
                $results.html($placeholder.clone());
            } else {
                renderEmpty('Aramak için yukarıya etken madde adı yazın.');
            }
            return;
        }
        var q = foldTrSearch(raw);
        if (debounceTimer !== -1) {
            window.clearTimeout(debounceTimer);
        }
        debounceTimer = window.setTimeout(function () {
            fetchEtken(raw);
            debounceTimer = -1;
        }, DEBOUNCE_MS);
    });
});
