(function () {
    if (typeof window.eshPrepareDatepickerInputs === 'function') {
        window.eshPrepareDatepickerInputs(document);
    }
    if (window.jQuery && jQuery.fn.datepicker) {
        jQuery('.datepicker').datepicker({ format: 'dd-mm-yyyy', language: 'tr', autoclose: true, todayHighlight: true });
    }

    var cfg = (window.ESH_PAGE && window.ESH_PAGE.nobet) ? window.ESH_PAGE.nobet : null;
    if (!cfg) {
        var cfgEl = document.getElementById('nobet-index-config');
        if (cfgEl) {
            cfg = {
                addUrl: cfgEl.getAttribute('data-add-url') || '',
                moveUrl: cfgEl.getAttribute('data-move-url') || '',
                deleteUrl: cfgEl.getAttribute('data-delete-url') || '',
                statsModalId: cfgEl.getAttribute('data-stats-modal-id') || 'nobetStatsModal'
            };
        }
    }
    if (!cfg) return;

    var dragged = null;
    var hoverCell = null;

    function postForm(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new URLSearchParams(data).toString()
        }).then(async function (r) {
            var text = await r.text();
            var parsed = null;
            try {
                parsed = JSON.parse(text);
            } catch (e) {
                throw new Error(text ? text.substring(0, 180) : ('HTTP ' + r.status));
            }
            if (!r.ok) {
                throw new Error((parsed && parsed.msg) ? parsed.msg : ('HTTP ' + r.status));
            }
            return parsed;
        });
    }

    function updateCount(pid, delta) {
        var el = document.querySelector('.person-count[data-pid="' + pid + '"]');
        if (!el) return;
        var cur = parseInt(el.textContent || '0', 10);
        if (Number.isNaN(cur)) cur = 0;
        el.textContent = String(Math.max(0, cur + delta));
    }

    function wireDragElement(el) {
        if (!el) return;
        el.addEventListener('dragstart', function (e) {
            dragged = el;
            if (e.dataTransfer) {
                // Firefox ve bazı Chromium sürümlerinde drop'un çalışması için zorunlu
                e.dataTransfer.setData('text/plain', el.getAttribute('data-type') || 'nobet');
                e.dataTransfer.effectAllowed = 'move';
            }
            el.classList.add('opacity-50');
        });
        el.addEventListener('dragend', function () {
            el.classList.remove('opacity-50');
            dragged = null;
        });
    }

    document.querySelectorAll('.external-event, .nobet-item').forEach(wireDragElement);

    function clearHover() {
        if (hoverCell) {
            hoverCell.classList.remove('table-active');
            hoverCell = null;
        }
    }

    function resolveDropCell(node) {
        if (!node || !node.closest) return null;
        return node.closest('.nobet-day-cell');
    }

    document.addEventListener('dragover', function (e) {
        if (!dragged) return;
        var cell = resolveDropCell(e.target);
        if (!cell) return;
        e.preventDefault();
        if (hoverCell !== cell) {
            clearHover();
            hoverCell = cell;
            hoverCell.classList.add('table-active');
        }
    });

    document.addEventListener('dragleave', function (e) {
        if (!dragged) return;
        var related = e.relatedTarget;
        if (related && hoverCell && hoverCell.contains(related)) return;
    });

    document.addEventListener('drop', function (e) {
        if (!dragged) return;
        var cell = resolveDropCell(e.target);
        if (!cell) return;
        e.preventDefault();
        clearHover();
        var targetDate = cell.getAttribute('data-date');
        if (!targetDate) return;

        if (dragged.classList.contains('external-event')) {
            var sourceEl = dragged;
            var pid = sourceEl.getAttribute('data-pid');
            if (!pid) {
                toastr.error('Personel bilgisi okunamadı.');
                return;
            }
            var name = sourceEl.getAttribute('data-name') || '';
            var unvan = sourceEl.getAttribute('data-unvan') || '';
            postForm(cfg.addUrl, { pid: pid, tarih: targetDate }).then(function (res) {
                if (!res || res.status !== 'success') {
                    toastr.error((res && res.msg) ? res.msg : 'Nöbet eklenemedi.');
                    return;
                }
                var cls = (unvan === 'hemsire') ? 'bg-info-subtle border-info' : 'bg-success-subtle border-success';
                var html = '' +
                    '<div class="nobet-item border rounded px-2 py-1 small ' + cls + '" draggable="true" data-type="nobet" data-id="' + res.new_id + '" data-pid="' + pid + '">' +
                    '  <div class="d-flex justify-content-between align-items-center gap-1">' +
                    '    <span class="text-truncate">' + name + '</span>' +
                    '    <button type="button" class="btn btn-link p-0 text-danger nobet-delete" data-id="' + res.new_id + '" title="Sil"><i class="fa-solid fa-xmark"></i></button>' +
                    '  </div>' +
                    '</div>';
                var slot = cell.querySelector('.nobet-slot');
                if (slot) {
                    slot.insertAdjacentHTML('beforeend', html);
                    var added = slot.lastElementChild;
                    wireDragElement(added);
                    wireDeleteButtons();
                    updateCount(pid, +1);
                }
            }).catch(function (err) {
                var msg = (err && err.message) ? err.message : 'Sunucu hatası oluştu.';
                toastr.error(msg);
            });
            return;
        }

        if (dragged.classList.contains('nobet-item')) {
            var draggedItem = dragged;
            var nobetId = draggedItem.getAttribute('data-id');
            var oldCell = draggedItem.closest('.nobet-day-cell');
            if (oldCell && oldCell.getAttribute('data-date') === targetDate) return;
            postForm(cfg.moveUrl, { id: nobetId, tarih: targetDate }).then(function (res) {
                if (!res || res.status !== 'success') {
                    toastr.error((res && res.msg) ? res.msg : 'Nöbet taşınamadı.');
                    return;
                }
                var slot = cell.querySelector('.nobet-slot');
                if (slot) slot.appendChild(draggedItem);
            }).catch(function (err) {
                var msg = (err && err.message) ? err.message : 'Sunucu hatası oluştu.';
                toastr.error(msg);
            });
        }
    });

    function wireDeleteButtons() {
        document.querySelectorAll('.nobet-delete').forEach(function (btn) {
            if (btn.dataset.wired === '1') return;
            btn.dataset.wired = '1';
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var id = btn.getAttribute('data-id');
                if (!id) return;
                if (!window.confirm('Bu nöbet silinsin mi?')) return;
                var item = btn.closest('.nobet-item');
                var pid = item ? item.getAttribute('data-pid') : '';
                postForm(cfg.deleteUrl, { id: id }).then(function (res) {
                    if (!res || res.status !== 'success') {
                        toastr.error('Silme işlemi başarısız.');
                        return;
                    }
                    if (item) item.remove();
                    if (pid) updateCount(pid, -1);
                }).catch(function (err) {
                    var msg = (err && err.message) ? err.message : 'Sunucu hatası oluştu.';
                    toastr.error(msg);
                });
            });
        });
    }
    wireDeleteButtons();

    function initStatsModal() {
        var modalId = cfg.statsModalId || '';
        if (!modalId) return;
        var modalEl = document.getElementById(modalId);
        if (!modalEl || typeof bootstrap === 'undefined' || !bootstrap.Modal) return;
        var titleEl = modalEl.querySelector('.modal-title');
        var bodyEl = modalEl.querySelector('.modal-body');
        if (!bodyEl) return;
        var modal = new bootstrap.Modal(modalEl);

        document.querySelectorAll('.js-nobet-modal-link').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = btn.getAttribute('data-modal-url') || '';
                var title = btn.getAttribute('data-modal-title') || 'Nöbet İstatistikleri';
                if (!url) return;
                if (titleEl) titleEl.textContent = title;
                bodyEl.innerHTML = '<div class="text-center text-muted py-4">Yükleniyor...</div>';
                modal.show();
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.text(); })
                    .then(function (html) {
                        bodyEl.innerHTML = html;
                    })
                    .catch(function () {
                        bodyEl.innerHTML = '<div class="alert alert-danger mb-0">İçerik yüklenemedi.</div>';
                    });
            });
        });

        modalEl.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form || !form.classList || !form.classList.contains('js-nobet-modal-form')) return;
            e.preventDefault();
            var action = form.getAttribute('action') || (typeof eshUrl === 'function' ? eshUrl('Nobet', 'index') : (String(window.ESH_PUBLIC_WEB || '/public/').replace(/\/?$/, '/') + 'index.php'));
            var params = new URLSearchParams(new FormData(form)).toString();
            var url = action + (action.indexOf('?') >= 0 ? '&' : '?') + params;
            bodyEl.innerHTML = '<div class="text-center text-muted py-4">Yükleniyor...</div>';
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.text(); })
                .then(function (html) {
                    bodyEl.innerHTML = html;
                })
                .catch(function () {
                    bodyEl.innerHTML = '<div class="alert alert-danger mb-0">İçerik yüklenemedi.</div>';
                });
        });
    }
    initStatsModal();
})();

