(function () {

    const cfg = (window.ESH_PAGE && window.ESH_PAGE.adresFetchTree) ? window.ESH_PAGE.adresFetchTree : {};

    const CHILD_TIP_MAP = {
        ilce: 'mahalle',
        mahalle: 'sokak',
        sokak: 'kapino'
    };

    const TIP_LABELS = {
        ilce: 'İlçe',
        mahalle: 'Mahalle',
        sokak: 'Sokak',
        kapino: 'Kapı'
    };

    function escHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function shortId(id) {
        const s = String(id || '');
        return s.length > 12 ? (s.slice(0, 12) + '…') : s;
    }

    function buildTreeNode(item) {
        const id = String(item.id || '');
        const tip = String(item.tip || '');
        const adi = String(item.adi || '');
        const hasChildren = !!item.has_children;
        const toggleBtn = hasChildren
            ? '<button type="button" class="esh-adres-tree-toggle" aria-expanded="false" title="Göster / gizle">+</button>'
            : '<span class="esh-adres-tree-toggle-spacer" aria-hidden="true"></span>';
        return ''
            + '<li class="esh-adres-tree-node" data-id="' + escHtml(id) + '" data-tip="' + escHtml(tip) + '" data-loaded="0">'
            + '<div class="esh-adres-tree-row">'
            + toggleBtn
            + '<span class="badge rounded-pill text-bg-secondary esh-adres-tree-badge">' + escHtml(TIP_LABELS[tip] || tip) + '</span>'
            + '<span class="esh-adres-tree-label">' + escHtml(adi) + '</span>'
            + '<code class="esh-adres-tree-id text-muted">' + escHtml(shortId(id)) + '</code>'
            + '</div>'
            + '<ul class="esh-adres-tree-children list-unstyled d-none"></ul>'
            + '</li>';
    }

    function renderTreeNodes(items) {
        if (!items || !items.length) {
            return '<li class="text-muted small py-1 px-1">Alt kayıt yok.</li>';
        }
        let html = '';
        items.forEach(function (item) {
            html += buildTreeNode(item);
        });
        return html;
    }

    function fetchTreeChildren(tip, ustId) {
        const url = String(cfg.treeChildrenUrl || '');
        return $.getJSON(url, { tip: tip, ust_id: ustId });
    }

    function loadTreeRoot() {
        const $root = $('#adresTreeRoot');
        if (!$root.length) {
            return;
        }
        $root.html('<li class="text-muted small py-2 px-1">İlçeler yükleniyor…</li>');
        fetchTreeChildren('ilce', '0').done(function (data) {
            if (!data || !data.ok) {
                $root.html('<li class="text-danger small py-2 px-1">İlçeler yüklenemedi.</li>');
                return;
            }
            $root.html(renderTreeNodes(data.items || []));
        }).fail(function () {
            $root.html('<li class="text-danger small py-2 px-1">İlçeler yüklenemedi.</li>');
        });
    }

    function setToggleExpanded($toggle, expanded) {
        $toggle.attr('aria-expanded', expanded ? 'true' : 'false');
        $toggle.text(expanded ? '−' : '+');
    }

    function loadNodeChildren($node) {
        const tip = String($node.attr('data-tip') || '');
        const id = String($node.attr('data-id') || '');
        const childTip = CHILD_TIP_MAP[tip];
        const $children = $node.children('.esh-adres-tree-children');
        const $toggle = $node.find('> .esh-adres-tree-row .esh-adres-tree-toggle').first();
        if (!childTip || !$children.length) {
            return $.Deferred().resolve().promise();
        }
        if ($node.attr('data-loaded') === '1') {
            $children.removeClass('d-none');
            setToggleExpanded($toggle, true);
            return $.Deferred().resolve().promise();
        }
        $toggle.prop('disabled', true);
        return fetchTreeChildren(childTip, id).done(function (data) {
            if (!data || !data.ok) {
                $children.html('<li class="text-danger small py-1 px-1">Yüklenemedi.</li>');
            } else {
                $children.html(renderTreeNodes(data.items || []));
            }
            $node.attr('data-loaded', '1');
            $children.removeClass('d-none');
            setToggleExpanded($toggle, true);
        }).fail(function () {
            $children.html('<li class="text-danger small py-1 px-1">Yüklenemedi.</li>');
            $children.removeClass('d-none');
        }).always(function () {
            $toggle.prop('disabled', false);
        });
    }

    function bindAdresTreeEvents() {
        $('#adresTreeRoot').on('click', '.esh-adres-tree-toggle', function (ev) {
            ev.preventDefault();
            const $toggle = $(this);
            const $node = $toggle.closest('.esh-adres-tree-node');
            const expanded = $toggle.attr('aria-expanded') === 'true';
            const $children = $node.children('.esh-adres-tree-children');
            if (expanded) {
                setToggleExpanded($toggle, false);
                $children.addClass('d-none');
                return;
            }
            loadNodeChildren($node);
        });

        $('#adresTreeSearch').on('input', function () {
            const q = String($(this).val() || '').trim().toLocaleLowerCase('tr-TR');
            $('#adresTreeRoot .esh-adres-tree-node').each(function () {
                const $row = $(this);
                const label = $row.find('.esh-adres-tree-label').text().toLocaleLowerCase('tr-TR');
                const idText = $row.attr('data-id') || '';
                const match = q === '' || label.indexOf(q) !== -1 || idText.indexOf(q) !== -1;
                $row.toggleClass('d-none', !match);
            });
        });
    }

    function initAdresTree() {
        if (!$('#adresTreeRoot').length) {
            return;
        }
        bindAdresTreeEvents();
        loadTreeRoot();
    }

    $(document).ready(function () {
        initAdresTree();
    });

})();
