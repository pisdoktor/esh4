jQuery(function ($) {
    var $filterCollapse = $('#hastalik-filter-collapse');
    var $toggleText = $('#hastalik-filter-toggle .js-filter-toggle-text');

    if ($filterCollapse.length && $toggleText.length) {
        $filterCollapse.on('shown.bs.collapse', function () {
            $toggleText.text('Filtreleri Gizle');
        });
        $filterCollapse.on('hidden.bs.collapse', function () {
            $toggleText.text('Filtreleri Göster');
        });
    }
});

(function () {
    var cfg = (window.ESH_PAGE && window.ESH_PAGE.hastalikTree) ? window.ESH_PAGE.hastalikTree : {};
    var $root = $('#hastalikTreeRoot');
    if (!$root.length) {
        return;
    }

    var treeUrl = String(cfg.treeNodesUrl || '');
    var pickerExpandUrl = String(cfg.pickerExpandUrl || '');
    var isPicker = !!cfg.isPickerMode;
    var isAdmin = !!cfg.isCatalogAdmin;
    var editTpl = String(cfg.editUrlTemplate || '');
    var deleteUrl = String(cfg.deleteUrl || '');
    var searchDebounce = null;
    var assignedMap = {};

    function escHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function editUrl(id) {
        return editTpl.replace('__ID__', String(id));
    }

    function fetchPickerItems(icd, virtual, id) {
        return $.getJSON(pickerExpandUrl, {
            icd: icd,
            virtual: virtual ? '1' : '0',
            id: id || ''
        });
    }

    function isPickButtonOn($btn) {
        return $btn.hasClass('btn-success');
    }

    function setPickButtonState($btn, on) {
        $btn
            .toggleClass('btn-success', on)
            .toggleClass('btn-outline-primary', !on)
            .html(on ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-plus"></i>');
    }

    function refreshPickButtonsForIds(ids, on) {
        if (!ids || !ids.length) {
            return;
        }
        ids.forEach(function (id) {
            $root.find('.esh-hastalik-tree-pick[data-id="' + String(id) + '"]').each(function () {
                setPickButtonState($(this), on);
            });
        });
    }

    function fetchNodes(params) {
        return $.getJSON(treeUrl, params || {});
    }

    function buildTreeNode(item) {
        var id = String(item.id || '');
        var icd = String(item.icd || '');
        var label = String(item.label || item.name || '');
        var hasChildren = !!item.has_children;
        var seviye = item.seviye != null ? String(item.seviye) : '';
        var assigned = !!item.assigned;
        var selectable = item.selectable !== false;
        var isVirtual = !!item.virtual;
        var nodeId = isVirtual ? '' : id;
        var toggleBtn = hasChildren
            ? '<button type="button" class="esh-hastalik-tree-toggle" aria-expanded="false" title="Alt dalları göster / gizle">+</button>'
            : '<span class="esh-hastalik-tree-toggle-spacer" aria-hidden="true"></span>';
        var actions = '';
        if (isAdmin && !isPicker && !isVirtual) {
            actions = '<span class="esh-hastalik-tree-actions ms-auto flex-shrink-0">'
                + '<a href="' + escHtml(editUrl(id)) + '" class="btn btn-sm btn-outline-primary py-0 px-1" title="Düzenle"><i class="fa-solid fa-pen-to-square"></i></a>'
                + '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 ms-1 esh-hastalik-tree-delete" data-id="' + escHtml(id) + '" title="Sil"><i class="fa-solid fa-trash"></i></button>'
                + '</span>';
        }
        var pickerBtn = '';
        if (isPicker && selectable) {
            var pickOn = assigned;
            if (!isVirtual && id) {
                pickOn = !!assignedMap[id] || assigned;
            }
            pickerBtn = '<button type="button" class="btn btn-sm ' + (pickOn ? 'btn-success' : 'btn-outline-primary') + ' py-0 px-2 ms-2 esh-hastalik-tree-pick"'
                + ' data-id="' + escHtml(id) + '"'
                + ' data-icd="' + escHtml(icd) + '"'
                + ' data-virtual="' + (isVirtual ? '1' : '0') + '"'
                + ' data-label="' + escHtml(label) + '"'
                + ' title="Kuruma ekle / çıkar">'
                + (pickOn ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-plus"></i>')
                + '</button>';
        }
        var seviyeBadge = seviye !== ''
            ? '<span class="badge rounded-pill text-bg-light border esh-hastalik-tree-seviye">S' + escHtml(seviye) + '</span>'
            : '';
        return ''
            + '<li class="esh-hastalik-tree-node' + (assigned ? ' esh-hastalik-tree-node--assigned' : '') + (isVirtual ? ' esh-hastalik-tree-node--virtual' : '') + '" role="treeitem" data-id="' + escHtml(nodeId) + '" data-icd="' + escHtml(icd) + '" data-loaded="0" data-has-children="' + (hasChildren ? '1' : '0') + '">'
            + '<div class="esh-hastalik-tree-row">'
            + toggleBtn
            + '<span class="esh-hastalik-tree-icd">' + escHtml(icd) + '</span>'
            + seviyeBadge
            + '<span class="esh-hastalik-tree-label">' + escHtml(label.replace(/^[A-Z0-9.\-]+\s*—\s*/, '')) + '</span>'
            + pickerBtn
            + actions
            + '</div>'
            + '<ul class="esh-hastalik-tree-children list-unstyled d-none" role="group"></ul>'
            + '</li>';
    }

    function renderTreeNodes(items) {
        if (!items || !items.length) {
            return '<li class="text-muted small py-1 px-1">Kayıt bulunamadı.</li>';
        }
        var html = '';
        items.forEach(function (item) {
            html += buildTreeNode(item);
        });
        return html;
    }

    function setToggleExpanded($toggle, expanded) {
        $toggle.attr('aria-expanded', expanded ? 'true' : 'false');
        $toggle.text(expanded ? '−' : '+');
    }

    function setMeta(text, show) {
        var $meta = $('#hastalikTreeMeta');
        if (!$meta.length) {
            return;
        }
        if (show) {
            $meta.text(text).removeClass('d-none');
        } else {
            $meta.addClass('d-none').text('');
        }
    }

    function loadTreeRoot(searchQ) {
        var params = {};
        if (searchQ && searchQ.length >= 2) {
            params.q = searchQ;
        }
        $root.html('<li class="text-muted small py-2">Yükleniyor…</li>');
        fetchNodes(params).done(function (data) {
            if (!data || !data.ok) {
                $root.html('<li class="text-danger small py-2">Ağaç yüklenemedi.</li>');
                return;
            }
            $root.html(renderTreeNodes(data.items || []));
            if (data.search) {
                setMeta('Arama sonuçları: ' + (data.items ? data.items.length : 0) + ' kayıt', true);
            } else {
                setMeta('', false);
            }
        }).fail(function () {
            $root.html('<li class="text-danger small py-2">Ağaç yüklenemedi.</li>');
        });
    }

    function loadNodeChildren($node) {
        var icd = String($node.attr('data-icd') || '');
        var $children = $node.children('.esh-hastalik-tree-children');
        var $toggle = $node.find('> .esh-hastalik-tree-row .esh-hastalik-tree-toggle').first();
        if ($node.attr('data-has-children') !== '1' || !$children.length) {
            return $.Deferred().resolve().promise();
        }
        if ($node.attr('data-loaded') === '1') {
            $children.removeClass('d-none');
            setToggleExpanded($toggle, true);
            return $.Deferred().resolve().promise();
        }
        $toggle.prop('disabled', true);
        return fetchNodes({ parent_icd: icd }).done(function (data) {
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

    function syncAssignedHidden() {
        var $wrap = $('#hastalikAssignedHidden');
        var $list = $('#hastalikAssignedList');
        var $empty = $('#hastalikAssignedEmpty');
        var $count = $('#hastalikAssignedCount');
        if (!$wrap.length) {
            return;
        }
        var ids = Object.keys(assignedMap);
        $wrap.empty();
        ids.forEach(function (id) {
            $wrap.append('<input type="hidden" name="assigned[]" value="' + escHtml(id) + '">');
        });
        $list.empty();
        ids.sort(function (a, b) {
            return String(assignedMap[a].label).localeCompare(String(assignedMap[b].label), 'tr');
        }).forEach(function (id) {
            var item = assignedMap[id];
            $list.append(
                '<li class="list-group-item d-flex align-items-center gap-2 py-2 px-2">'
                + '<span class="small flex-grow-1 min-w-0 text-truncate">' + escHtml(item.label) + '</span>'
                + '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 esh-hastalik-assigned-remove" data-id="' + escHtml(id) + '" title="Çıkar"><i class="fa-solid fa-xmark"></i></button>'
                + '</li>'
            );
        });
        var n = ids.length;
        $count.text(String(n));
        $empty.toggleClass('d-none', n > 0);
        $list.toggleClass('d-none', n === 0);
    }

    function toggleAssigned(id, label, forceOn) {
        id = String(id);
        if (!id) {
            return;
        }
        if (forceOn === true || (forceOn !== false && !assignedMap[id])) {
            assignedMap[id] = { id: id, label: label || ('#' + id) };
        } else {
            delete assignedMap[id];
        }
        syncAssignedHidden();
    }

    function togglePickerNode($btn) {
        var virtual = $btn.attr('data-virtual') === '1';
        var icd = String($btn.attr('data-icd') || '');
        var id = String($btn.attr('data-id') || '');
        if (!icd) {
            return;
        }
        $btn.prop('disabled', true);
        fetchPickerItems(icd, virtual, id).done(function (data) {
            if (!data || !data.ok || !Array.isArray(data.items)) {
                return;
            }
            var items = data.items;
            if (!items.length) {
                return;
            }
            var allOn = items.every(function (item) {
                return !!assignedMap[String(item.id)];
            });
            var forceOn = !allOn;
            items.forEach(function (item) {
                toggleAssigned(String(item.id), String(item.label || ''), forceOn);
            });
            setPickButtonState($btn, forceOn);
            refreshPickButtonsForIds(items.map(function (item) { return String(item.id); }), forceOn);
            if (virtual) {
                $btn.closest('.esh-hastalik-tree-node').toggleClass('esh-hastalik-tree-node--assigned', forceOn);
            } else if (id) {
                $btn.closest('.esh-hastalik-tree-node').toggleClass('esh-hastalik-tree-node--assigned', forceOn);
            }
        }).always(function () {
            $btn.prop('disabled', false);
        });
    }

    function initAssignedFromConfig() {
        var items = Array.isArray(cfg.assigned) ? cfg.assigned : [];
        items.forEach(function (item) {
            if (item && item.id) {
                assignedMap[String(item.id)] = { id: String(item.id), label: String(item.label || '') };
            }
        });
        syncAssignedHidden();
    }

    function bindEvents() {
        $root.on('click', '.esh-hastalik-tree-toggle', function (ev) {
            ev.preventDefault();
            var $toggle = $(this);
            var $node = $toggle.closest('.esh-hastalik-tree-node');
            var expanded = $toggle.attr('aria-expanded') === 'true';
            var $children = $node.children('.esh-hastalik-tree-children');
            if (expanded) {
                setToggleExpanded($toggle, false);
                $children.addClass('d-none');
                return;
            }
            loadNodeChildren($node);
        });

        $root.on('click', '.esh-hastalik-tree-pick', function (ev) {
            ev.preventDefault();
            togglePickerNode($(this));
        });

        $('#hastalikAssignedList').on('click', '.esh-hastalik-assigned-remove', function (ev) {
            ev.preventDefault();
            var id = String($(this).attr('data-id') || '');
            toggleAssigned(id, '', false);
            refreshPickButtonsForIds([id], false);
        });

        $root.on('click', '.esh-hastalik-tree-delete', function (ev) {
            ev.preventDefault();
            var id = $(this).attr('data-id');
            if (!id || !deleteUrl || !window.confirm('Bu tanı silinsin mi?')) {
                return;
            }
            var $form = $('<form method="post"></form>').attr('action', deleteUrl);
            $form.append('<input type="hidden" name="id" value="' + escHtml(id) + '">');
            $('body').append($form);
            $form.trigger('submit');
        });

        $('#hastalikTreeSearch').on('input', function () {
            var q = String($(this).val() || '').trim();
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(function () {
                if (q.length >= 2 || q.length === 0) {
                    loadTreeRoot(q);
                }
            }, 350);
        });

        $('#hastalikTreeSearchClear').on('click', function () {
            $('#hastalikTreeSearch').val('');
            loadTreeRoot('');
        });
    }

    $(document).ready(function () {
        if (isPicker) {
            initAssignedFromConfig();
        }
        bindEvents();
        var initialQ = String(cfg.searchQ || '').trim();
        if (initialQ.length >= 2) {
            loadTreeRoot(initialQ);
        } else {
            loadTreeRoot('');
        }
    });
})();
