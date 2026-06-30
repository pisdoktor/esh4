(function () {

    const cfg = (window.ESH_PAGE && window.ESH_PAGE.adresFetchTarama) ? window.ESH_PAGE.adresFetchTarama : {};

    const REASON_LABELS = {
        no_parent: 'Üst kayıt yok',
        parent_missing: 'Üst ID bulunamadı',
        wrong_parent_type: 'Yanlış üst tip',
        no_ilce: 'İlçe zinciri kırık',
        self_parent: 'ID = üst ID (özyinelemeli)'
    };

    let orphanActiveTip = 'mahalle';
    let orphanPage = 1;
    let orphanPerPage = 50;
    let orphanTotal = 0;
    let orphanCounts = { mahalle: 0, sokak: 0, kapino: 0 };
    let orphanScanDone = false;
    let orphanKapinoCounted = false;
    let orphanKapinoCounting = false;
    let orphanBulkDeleteRunning = false;
    let orphanBulkDeleteAbort = false;
    let orphanBulkTimer = null;
    let orphanBulkStatusCtx = null;
    const orphanScanBtnHtml = '<i class="fa-solid fa-magnifying-glass me-1"></i> Eksik ilçe tara';
    const orphanScanTips = ['mahalle', 'sokak', 'kapino'];
    const orphanScanTipLabels = {
        mahalle: 'Mahalle',
        sokak: 'Sokak',
        kapino: 'Kapı'
    };
    const orphanCacheFetchPerPage = 200;
    let orphanListCache = {
        mahalle: { items: [], total: 0, ready: false, loading: false, promise: null },
        sokak: { items: [], total: 0, ready: false, loading: false, promise: null },
        kapino: { items: [], total: 0, ready: false, loading: false, promise: null }
    };

    function orphanCacheEntry(tip) {
        if (!orphanListCache[tip]) {
            orphanListCache[tip] = { items: [], total: 0, ready: false, loading: false, promise: null };
        }
        return orphanListCache[tip];
    }

    function orphanItemKey(item) {
        return String(item.id || '') + '\0' + String(item.ust_id || '');
    }

    function normalizeOrphanRow(row, tip) {
        return {
            id: String(row.id || ''),
            adi: String(row.adi || ''),
            ust_id: String(row.ust_id || ''),
            tip: tip,
            reason: String(row.reason || 'no_ilce')
        };
    }

    function clearOrphanListCache() {
        orphanScanTips.forEach(function (tip) {
            orphanListCache[tip] = { items: [], total: 0, ready: false, loading: false, promise: null };
        });
    }

    function setOrphanCacheItems(tip, items, total) {
        const entry = orphanCacheEntry(tip);
        entry.items = items.slice();
        entry.total = typeof total === 'number' ? total : items.length;
        entry.ready = true;
        entry.loading = false;
        entry.promise = null;
        orphanCounts[tip] = entry.items.length;
    }

    function removeFromOrphanCache(tip, id, ustId) {
        const entry = orphanCacheEntry(tip);
        if (!entry.ready) {
            return;
        }
        const key = orphanItemKey({ id: id, ust_id: ustId });
        entry.items = entry.items.filter(function (item) {
            return orphanItemKey(item) !== key;
        });
        entry.total = entry.items.length;
        orphanCounts[tip] = entry.items.length;
    }

    function fetchOrphanListPageFromServer(tip, page, perPage) {
        const url = String(cfg.orphanReportUrl || '');
        if (!url) {
            return $.Deferred().reject('Liste adresi tanımlı değil.').promise();
        }
        return $.ajax(orphanAjaxSettings({
            url: url,
            data: {
                mode: 'list',
                tip: tip,
                page: page,
                per_page: perPage
            },
            dataType: 'json',
            timeout: tip === 'kapino' ? 300000 : 180000
        })).then(function (data) {
            if (!data || !data.ok) {
                return $.Deferred().reject((data && data.mesaj) ? data.mesaj : 'Liste alınamadı.').promise();
            }
            return data;
        }, function (jqXHR, textStatus) {
            return $.Deferred().reject(ajaxFailMessage(jqXHR, textStatus)).promise();
        });
    }

    function prefetchOrphanListForTip(tip) {
        const entry = orphanCacheEntry(tip);
        if (entry.ready) {
            return $.Deferred().resolve(entry).promise();
        }
        if (entry.loading && entry.promise) {
            return entry.promise;
        }
        const expected = orphanCounts[tip] || 0;
        if (expected <= 0) {
            setOrphanCacheItems(tip, [], 0);
            return $.Deferred().resolve(entry).promise();
        }
        entry.loading = true;
        let allItems = [];
        let page = 1;

        function fetchNextPage() {
            return fetchOrphanListPageFromServer(tip, page, orphanCacheFetchPerPage).then(function (data) {
                const chunk = data.items || [];
                chunk.forEach(function (row) {
                    allItems.push(normalizeOrphanRow(row, tip));
                });
                const total = parseInt(data.total, 10) || expected;
                if (chunk.length === orphanCacheFetchPerPage && allItems.length < total) {
                    page += 1;
                    return fetchNextPage();
                }
                allItems.sort(function (a, b) {
                    return String(a.adi).localeCompare(String(b.adi), 'tr', { sensitivity: 'base' });
                });
                setOrphanCacheItems(tip, allItems, total);
                return entry;
            });
        }

        entry.promise = fetchNextPage().fail(function () {
            entry.ready = false;
            entry.loading = false;
            entry.promise = null;
        }).always(function () {
            entry.loading = false;
        });
        return entry.promise;
    }

    function refreshOrphanCacheForTip(tip) {
        const entry = orphanCacheEntry(tip);
        const oldCount = entry.ready ? entry.items.length : (orphanCounts[tip] || 0);
        entry.ready = false;
        entry.loading = false;
        entry.promise = null;
        return fetchOrphanCountForTip(tip).then(function (data) {
            if (data && data.ok) {
                const count = parseInt(data.count, 10);
                orphanCounts[tip] = Number.isFinite(count) ? count : 0;
                if (tip === 'kapino') {
                    orphanKapinoCounted = true;
                }
            }
            return prefetchOrphanListForTip(tip);
        }).then(function () {
            const newCount = orphanCacheEntry(tip).items.length;
            updateOrphanSummary();
            return { oldCount: oldCount, newCount: newCount, delta: newCount - oldCount };
        });
    }

    function buildOrphanClientPagination(total, page, perPage) {
        const limits = [25, 50, 100, 200];
        let limitHtml = '<div class="d-flex align-items-center small text-muted">';
        limitHtml += '<span class="me-2 text-nowrap">Satır:</span>';
        limitHtml += '<select class="form-select form-select-sm orphan-limit-select" style="width: auto;">';
        limits.forEach(function (opt) {
            const selected = parseInt(perPage, 10) === opt ? ' selected' : '';
            limitHtml += '<option value="' + opt + '"' + selected + '>' + opt + '</option>';
        });
        limitHtml += '</select></div>';

        let infoHtml = 'Kayıt bulunamadı.';
        if (total > 0) {
            const from = ((page - 1) * perPage) + 1;
            const to = Math.min(page * perPage, total);
            infoHtml = 'Toplam <strong>' + total + '</strong> kayıttan <strong>' + from + '-' + to + '</strong> arası gösteriliyor.';
        }

        const totalPages = Math.max(1, Math.ceil(total / Math.max(1, perPage)));
        let navHtml = '';
        if (totalPages > 1) {
            const disabledFirst = page <= 1 ? ' disabled' : '';
            const disabledLast = page >= totalPages ? ' disabled' : '';
            const prevPage = page - 1;
            const nextPage = page + 1;
            navHtml = '<div class="d-flex align-items-center"><nav aria-label="Page navigation"><ul class="pagination pagination-sm mb-0 shadow-sm">';
            navHtml += '<li class="page-item' + disabledFirst + '"><a class="page-link orphan-page-link" href="#" data-page="1" title="İlk Sayfa"><i class="fa-solid fa-angles-left"></i></a></li>';
            navHtml += '<li class="page-item' + disabledFirst + '"><a class="page-link orphan-page-link" href="#" data-page="' + prevPage + '" title="Geri"><i class="fa-solid fa-angle-left"></i></a></li>';
            const start = Math.max(1, page - 3);
            const end = Math.min(totalPages, page + 3);
            for (let i = start; i <= end; i++) {
                const active = page === i ? ' active' : '';
                navHtml += '<li class="page-item' + active + '"><a class="page-link orphan-page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
            }
            navHtml += '<li class="page-item' + disabledLast + '"><a class="page-link orphan-page-link" href="#" data-page="' + nextPage + '" title="İleri"><i class="fa-solid fa-angle-right"></i></a></li>';
            navHtml += '<li class="page-item' + disabledLast + '"><a class="page-link orphan-page-link" href="#" data-page="' + totalPages + '" title="Son Sayfa"><i class="fa-solid fa-angles-right"></i></a></li>';
            navHtml += '</ul></nav>';
            if (totalPages > 5) {
                navHtml += '<div class="input-group input-group-sm ms-3" style="width: 130px;">'
                    + '<input type="number" id="orphan_jump_page" class="form-control orphan-jump-page-input" placeholder="Sfy No" min="1" max="' + totalPages + '">'
                    + '<button class="btn btn-outline-primary orphan-jump-page-btn" type="button" data-max="' + totalPages + '">Git</button>'
                    + '</div>';
            }
            navHtml += '</div>';
        }

        return { info_html: infoHtml, limit_html: limitHtml, nav_html: navHtml };
    }

    function renderOrphanListFromCache(options) {
        options = options || {};
        const entry = orphanCacheEntry(orphanActiveTip);
        if (!entry.ready) {
            return $.Deferred().reject('Önbellek hazır değil.').promise();
        }
        const total = entry.items.length;
        orphanTotal = total;
        orphanCounts[orphanActiveTip] = total;
        updateOrphanSummary();
        const totalPages = Math.max(1, Math.ceil(total / Math.max(1, orphanPerPage)));
        if (orphanPage > totalPages) {
            orphanPage = totalPages;
        }
        if (orphanPage < 1) {
            orphanPage = 1;
        }
        const start = (orphanPage - 1) * orphanPerPage;
        const pageItems = entry.items.slice(start, start + orphanPerPage);
        renderOrphanTable(pageItems, total);
        renderOrphanPagination(buildOrphanClientPagination(total, orphanPage, orphanPerPage));
        hideOrphanTableLoadingState();
        setOrphanPagerLoading(false);
        if (!options.skipStatus) {
            $('#orphanScanStatus').text('');
        }
        return $.Deferred().resolve().promise();
    }

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

    function updateOrphanSummary() {
        $('#orphanCountMahalle').text('Mahalle: ' + (orphanCounts.mahalle || 0));
        $('#orphanCountSokak').text('Sokak: ' + (orphanCounts.sokak || 0));
        if (orphanKapinoCounted) {
            $('#orphanCountKapino').text('Kapı: ' + (orphanCounts.kapino || 0));
        } else if (orphanKapinoCounting) {
            $('#orphanCountKapino').text('Kapı: taranıyor…');
        } else if (!orphanScanDone) {
            $('#orphanCountKapino').text('Kapı: —');
        } else {
            $('#orphanCountKapino').text('Kapı: sayılamadı');
        }
        $('#orphanSummary').removeClass('d-none');
        updateOrphanDeleteAllButton();
    }

    function ensureKapinoCount() {
        if (orphanKapinoCounted) {
            return $.Deferred().resolve().promise();
        }
        if (orphanKapinoCounting) {
            return $.Deferred().resolve().promise();
        }
        orphanKapinoCounting = true;
        updateOrphanSummary();
        return fetchOrphanCountForTip('kapino').then(function (data) {
            if (!data || !data.ok) {
                return $.Deferred().reject({ mesaj: (data && data.mesaj) ? data.mesaj : 'Kapı sayımı başarısız.' }).promise();
            }
            const count = parseInt(data.count, 10);
            orphanCounts.kapino = Number.isFinite(count) ? count : 0;
            orphanKapinoCounted = true;
            updateOrphanSummary();
            if (count > 0) {
                return prefetchOrphanListForTip('kapino');
            }
        }).fail(function (err, textStatus) {
            orphanKapinoCounted = false;
            updateOrphanSummary();
            let mesaj = '';
            if (err && err.mesaj) {
                mesaj = String(err.mesaj);
            } else if (err && err.responseJSON) {
                mesaj = String(err.responseJSON.mesaj || err.responseJSON.error || '');
            }
            if (!mesaj) {
                mesaj = ajaxFailMessage(err, textStatus);
            }
            return $.Deferred().reject({ mesaj: mesaj || 'Kapı sayımı başarısız.' }).promise();
        }).always(function () {
            orphanKapinoCounting = false;
            updateOrphanSummary();
        });
    }

    function orphanScanStatusCompleteText() {
        if (orphanKapinoCounted) {
            return 'Tarama tamamlandı.';
        }
        return 'Mahalle ve sokak tarandı. Kapı sayımı tamamlanmadı — Kapı sekmesine tıklayarak tekrar deneyin.';
    }

    function orphanLoadingRowHtml(message) {
        const msg = message || 'Yükleniyor…';
        return '<tr class="orphan-loading-row">'
            + '<td colspan="6" class="text-center text-muted py-3">'
            + '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>'
            + escHtml(msg)
            + '</td></tr>';
    }

    function showOrphanTableLoading(message) {
        $('#orphanTableBody').html(orphanLoadingRowHtml(message));
        resetOrphanSelection();
        $('#orphanCheckAll').prop('disabled', true);
    }

    function hideOrphanTableLoadingState() {
        $('#orphanCheckAll').prop('disabled', false);
    }

    function setOrphanPagerLoading(loading) {
        const $wrap = $('#orphanPagerWrap');
        if (loading) {
            $wrap.removeClass('d-none');
            $('#orphanPageInfo').html('Yükleniyor…');
            $('#orphanNavWrap').addClass('pe-none opacity-50');
            $('#orphanLimitWrap').addClass('pe-none opacity-50');
            return;
        }
        $('#orphanNavWrap').removeClass('pe-none opacity-50');
        $('#orphanLimitWrap').removeClass('pe-none opacity-50');
    }

    function renderOrphanPagination(pagination) {
        pagination = pagination || {};
        $('#orphanPagerWrap').removeClass('d-none');
        $('#orphanPageInfo').html(pagination.info_html || '');
        $('#orphanLimitWrap').html(pagination.limit_html || '');
        $('#orphanNavWrap').html(pagination.nav_html || '');
    }

    function goToOrphanPage(page) {
        const nextPage = parseInt(page, 10);
        if (!nextPage || nextPage < 1 || nextPage === orphanPage) {
            return;
        }
        orphanPage = nextPage;
        $('#orphanDeleteStatus').text('');
        loadOrphanList();
    }

    function resetOrphanSelection() {
        $('#orphanCheckAll').prop('checked', false).prop('indeterminate', false);
        updateOrphanDeleteButton();
    }

    function getSelectedOrphanIds() {
        return getSelectedOrphanItems().map(function (item) {
            return item.id;
        });
    }

    function getSelectedOrphanItems() {
        const items = [];
        $('#orphanTableBody .orphan-row-check:checked').each(function () {
            const id = String($(this).val() || '');
            const ustId = String($(this).attr('data-ust-id') || '');
            if (id !== '') {
                items.push({ id: id, ust_id: ustId });
            }
        });
        return items;
    }

    function updateOrphanDeleteButton() {
        const count = getSelectedOrphanIds().length;
        const $btn = $('#orphanDeleteSelectedBtn');
        $btn.prop('disabled', count === 0 || orphanBulkDeleteRunning);
        $btn.html('<i class="fa-solid fa-trash-can me-1"></i> Seçilenleri sil (' + count + ')');
        const $checks = $('#orphanTableBody .orphan-row-check');
        const total = $checks.length;
        const $all = $('#orphanCheckAll');
        if (total === 0) {
            $all.prop('checked', false).prop('indeterminate', false);
            return;
        }
        $all.prop('checked', count === total);
        $all.prop('indeterminate', count > 0 && count < total);
    }

    function updateOrphanDeleteAllButton() {
        const total = orphanCounts[orphanActiveTip] || 0;
        $('#orphanDeleteAllBtn').prop('disabled', total <= 0 || orphanBulkDeleteRunning || !orphanScanDone);
    }

    function setOrphanDeleteButtonsDisabled(disabled) {
        orphanBulkDeleteRunning = disabled;
        $('#orphanScanBtn').prop('disabled', disabled);
        $('#orphanTableBody .orphan-row-delete').prop('disabled', disabled);
        $('#orphanCheckAll').prop('disabled', disabled);
        $('#orphanTableBody .orphan-row-check').prop('disabled', disabled);
        if (disabled) {
            $('#orphanBulkDeleteAbortBtn').removeClass('d-none');
        } else {
            $('#orphanBulkDeleteAbortBtn').addClass('d-none');
        }
        updateOrphanDeleteButton();
        updateOrphanDeleteAllButton();
    }

    function postOrphanPurge(tip, batchSize, maxBatches) {
        const url = String(cfg.orphanPurgeUrl || '');
        if (!url) {
            return $.Deferred().reject('Toplu silme adresi tanımlı değil.').promise();
        }
        const payload = {
            tip: tip,
            batch_size: batchSize,
            max_batches: maxBatches
        };
        return $.ajax(orphanAjaxSettings({
            url: url,
            method: 'POST',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify(payload),
            dataType: 'json',
            timeout: 600000
        })).then(function (data) {
            if (!data || !data.ok) {
                return $.Deferred().reject((data && data.mesaj) ? data.mesaj : 'Toplu silme başarısız.').promise();
            }
            return data;
        }, function (jqXHR, textStatus) {
            return $.Deferred().reject(ajaxFailMessage(jqXHR, textStatus)).promise();
        });
    }

    function ajaxFailMessage(jqXHR, textStatus) {
        const body = jqXHR && jqXHR.responseJSON;
        if (body) {
            if (body.auth === false) {
                return body.mesaj || body.error || 'Oturum süresi doldu. Sayfayı yenileyip tekrar giriş yapın.';
            }
            if (body.mesaj) {
                return String(body.mesaj);
            }
            if (body.error) {
                return String(body.error);
            }
        }
        if (textStatus === 'parsererror') {
            return 'Sunucu yanıtı okunamadı — oturum kapanmış olabilir. Sayfayı yenileyip tekrar giriş yapın.';
        }
        if (textStatus === 'timeout') {
            return 'Zaman aşımı — işlem sürdü; tekrar deneyebilir veya kalan kayıtlar için tekrar deneyin.';
        }
        if (jqXHR && jqXHR.status === 401) {
            return 'Oturum süresi doldu. Sayfayı yenileyip tekrar giriş yapın.';
        }
        if (jqXHR && jqXHR.status === 504) {
            return 'Sunucu zaman aşımı — kalan kayıtlar için tekrar deneyin.';
        }
        if (jqXHR && jqXHR.status === 403) {
            return (body && (body.mesaj || body.error)) || 'Bu işlem için yetkiniz yok veya güvenlik anahtarı geçersiz. Sayfayı yenileyin.';
        }
        if (jqXHR && jqXHR.status >= 500) {
            return 'Sunucu hatası (HTTP ' + jqXHR.status + ').';
        }
        return 'istek başarısız.';
    }

    function orphanAjaxSettings(extra) {
        extra = extra || {};
        return $.extend({
            headers: { 'Accept': 'application/json' },
            xhrFields: { withCredentials: true }
        }, extra);
    }

    function orphanBulkDeleteBatchSize() {
        if (orphanActiveTip === 'kapino') {
            return 1;
        }
        if (orphanActiveTip === 'mahalle') {
            return 3;
        }
        return 20;
    }

    function orphanBulkDeleteIsSingleStep() {
        return orphanActiveTip === 'kapino';
    }

    function orphanPurgeMaxBatches() {
        return 1;
    }

    function stopOrphanBulkProgressUi() {
        if (orphanBulkTimer) {
            clearInterval(orphanBulkTimer);
            orphanBulkTimer = null;
        }
        orphanBulkStatusCtx = null;
        orphanBulkDeleteAbort = false;
        $('#orphanBulkDeleteProgressWrap').addClass('d-none');
        $('#orphanBulkDeleteAbortBtn').addClass('d-none');
    }

    function setOrphanBulkDeleteProgress(deletedTotal, startTotal) {
        const $wrap = $('#orphanBulkDeleteProgressWrap');
        const $bar = $('#orphanBulkDeleteProgressBar');
        if (!$wrap.length || startTotal <= 0) {
            $wrap.addClass('d-none');
            return;
        }
        $wrap.removeClass('d-none');
        const pct = Math.min(100, Math.round((deletedTotal / startTotal) * 100));
        $bar.css('width', pct + '%').attr('aria-valuenow', String(pct));
        $('#orphanBulkDeleteProgressLabel').text(deletedTotal + ' / ~' + startTotal + ' (' + pct + '%)');
    }

    function renderOrphanBulkStatus() {
        const ctx = orphanBulkStatusCtx;
        if (!ctx) {
            return;
        }
        const elapsed = Math.floor((Date.now() - ctx.phaseStarted) / 1000);
        const batchSize = ctx.batchSize;
        const batchNo = ctx.batchNo;
        const deletedTotal = ctx.deletedTotal;
        const remaining = Math.max(0, ctx.startTotal - deletedTotal);
        const itemCount = ctx.itemCount || batchSize;
        let msg = '';
        if (ctx.phase === 'purge') {
            msg = 'Sunucu toplu silme — parti ' + batchNo + ' (+' + (ctx.lastBatchDeleted || 0) + ' bu turda), silinen: ' + deletedTotal + ', kalan ~' + remaining + '… ' + elapsed + ' sn';
        } else if (ctx.phase === 'list') {
            if (orphanBulkDeleteIsSingleStep()) {
                msg = 'Kayıt ' + batchNo + ' — liste alınıyor (kalan ~' + remaining + ')… ' + elapsed + ' sn';
            } else {
                msg = 'Parti ' + batchNo + ' — liste alınıyor (' + batchSize + '’er kayıt, kalan ~' + remaining + ')… ' + elapsed + ' sn';
            }
        } else if (ctx.phase === 'delete') {
            if (orphanBulkDeleteIsSingleStep()) {
                msg = 'Kayıt ' + batchNo + ' siliniyor';
                if (ctx.currentItemId) {
                    msg += ' (' + shortId(ctx.currentItemId) + ')';
                }
                msg += ' — ' + deletedTotal + ' / ~' + ctx.startTotal + '… ' + elapsed + ' sn';
            } else {
                msg = 'Parti ' + batchNo + ' — ' + itemCount + ' kayıt siliniyor (toplam silinen: ' + deletedTotal + ')… ' + elapsed + ' sn';
            }
        } else if (ctx.phase === 'batch-done') {
            if (orphanBulkDeleteIsSingleStep()) {
                msg = 'Kayıt ' + batchNo + ' tamam (+' + (ctx.lastBatchDeleted || 0) + ') — silinen: ' + deletedTotal + ', kalan ~' + remaining;
            } else {
                msg = 'Parti ' + batchNo + ' tamam (+' + (ctx.lastBatchDeleted || 0) + ') — silinen: ' + deletedTotal + ', kalan ~' + remaining;
            }
        } else {
            msg = 'Toplu silme… silinen: ' + deletedTotal + ', kalan ~' + remaining;
        }
        $('#orphanDeleteStatus').text(msg);
        setOrphanBulkDeleteProgress(deletedTotal, ctx.startTotal);
    }

    function beginOrphanBulkPhase(phase, patch) {
        if (!orphanBulkStatusCtx) {
            return;
        }
        if (patch) {
            $.extend(orphanBulkStatusCtx, patch);
        }
        orphanBulkStatusCtx.phase = phase;
        orphanBulkStatusCtx.phaseStarted = Date.now();
        renderOrphanBulkStatus();
    }

    function startOrphanBulkProgressUi(startTotal, batchSize) {
        stopOrphanBulkProgressUi();
        orphanBulkStatusCtx = {
            startTotal: startTotal,
            batchSize: batchSize,
            batchNo: 0,
            deletedTotal: 0,
            itemCount: batchSize,
            phase: 'list',
            phaseStarted: Date.now(),
            lastBatchDeleted: 0
        };
        $('#orphanBulkDeleteProgressHint').text(
            batchSize === 1
                ? 'Kapı: 1\'er kayıt, adım adım'
                : ('Parti: ' + batchSize + ' kayıt/istek — alt kayıtlar otomatik silinir')
        );
        setOrphanBulkDeleteProgress(0, startTotal);
        $('#orphanBulkDeleteProgressWrap').removeClass('d-none');
        orphanBulkTimer = setInterval(renderOrphanBulkStatus, 1000);
        renderOrphanBulkStatus();
    }

    function postOrphanDelete(items) {
        const url = String(cfg.orphanDeleteUrl || '');
        if (!url) {
            return $.Deferred().reject('Silme adresi tanımlı değil.').promise();
        }
        const payload = { items: items };
        return $.ajax(orphanAjaxSettings({
            url: url,
            method: 'POST',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify(payload),
            dataType: 'json',
            timeout: 300000
        })).then(function (data) {
            if (!data || !data.ok) {
                return $.Deferred().reject((data && data.mesaj) ? data.mesaj : 'Silme başarısız.').promise();
            }
            return data;
        }, function (jqXHR, textStatus) {
            return $.Deferred().reject(ajaxFailMessage(jqXHR, textStatus)).promise();
        });
    }

    function refreshOrphanCountsAfterDelete(onlyTip) {
        const tips = onlyTip ? [onlyTip] : orphanScanTips.slice();
        let chain = $.Deferred().resolve();
        tips.forEach(function (tip) {
            chain = chain.then(function () {
                return refreshOrphanCacheForTip(tip);
            });
        });
        return chain;
    }

    function showOrphanDeleteResult(deleted, failed) {
        const $status = $('#orphanDeleteStatus');
        const parts = [];
        if (deleted.length > 0) {
            parts.push(deleted.length + ' kayıt silindi');
        }
        if (failed.length > 0) {
            const detail = failed.slice(0, 3).map(function (f) {
                return shortId(f.id) + ': ' + (f.mesaj || 'Hata');
            }).join('; ');
            const more = failed.length > 3 ? (' (+' + (failed.length - 3) + ' daha)') : '';
            parts.push(failed.length + ' başarısız — ' + detail + more);
        }
        $status.text(parts.join(' · '));
    }

    function deleteOrphanIds(items, confirmLabel) {
        if (!items || !items.length || orphanBulkDeleteRunning) {
            return $.Deferred().resolve().promise();
        }
        const normalized = items.map(function (item) {
            if (typeof item === 'string') {
                return { id: item, ust_id: '' };
            }
            return {
                id: String(item.id || ''),
                ust_id: String(item.ust_id || '')
            };
        }).filter(function (item) {
            return item.id !== '';
        });
        if (!normalized.length) {
            return $.Deferred().resolve().promise();
        }
        if (!confirm(confirmLabel || (normalized.length + ' kayıt silinsin mi?'))) {
            return $.Deferred().resolve().promise();
        }
        setOrphanDeleteButtonsDisabled(true);
        $('#orphanDeleteStatus').text('Siliniyor…');
        return postOrphanDelete(normalized).done(function (data) {
            const deleted = data.deleted || [];
            const failed = data.failed || [];
            showOrphanDeleteResult(deleted, failed);
            resetOrphanSelection();
            refreshOrphanCacheForTip(orphanActiveTip).then(function () {
                renderOrphanListFromCache();
            });
        }).fail(function (err) {
            $('#orphanDeleteStatus').text(typeof err === 'string' ? err : 'Silme isteği başarısız.');
        }).always(function () {
            setOrphanDeleteButtonsDisabled(false);
        });
    }

    function fetchOrphanItemsBatch(perPage) {
        const entry = orphanCacheEntry(orphanActiveTip);
        if (entry.ready && entry.items.length > 0) {
            return $.Deferred().resolve(
                entry.items.slice(0, perPage).map(function (item) {
                    return { id: item.id, ust_id: item.ust_id };
                })
            ).promise();
        }
        const url = String(cfg.orphanReportUrl || '');
        if (!url) {
            return $.Deferred().reject('Liste adresi tanımlı değil.').promise();
        }
        const payload = {
            mode: 'list',
            tip: orphanActiveTip,
            page: 1,
            per_page: perPage
        };
        if (orphanScanDone && (orphanActiveTip !== 'kapino' || orphanKapinoCounted)) {
            payload.known_total = orphanCounts[orphanActiveTip];
        }
        return $.ajax(orphanAjaxSettings({
            url: url,
            data: payload,
            dataType: 'json',
            timeout: 180000
        })).then(function (data) {
            if (!data || !data.ok) {
                return $.Deferred().reject((data && data.mesaj) ? data.mesaj : 'Liste alınamadı.').promise();
            }
            return (data.items || []).map(function (row) {
                return {
                    id: String(row.id || ''),
                    ust_id: String(row.ust_id || '')
                };
            }).filter(function (item) {
                return item.id !== '';
            });
        }, function (jqXHR, textStatus) {
            return $.Deferred().reject(ajaxFailMessage(jqXHR, textStatus)).promise();
        });
    }

    function deleteAllOrphansOnTab() {
        if (orphanBulkDeleteRunning) {
            return;
        }
        const tipLabel = orphanScanTipLabels[orphanActiveTip] || orphanActiveTip;
        const total = orphanCounts[orphanActiveTip] || 0;
        if (total <= 0) {
            return;
        }
        const batchSize = orphanBulkDeleteBatchSize();
        const maxBatches = orphanPurgeMaxBatches();
        const singleStep = orphanBulkDeleteIsSingleStep();
        let confirmMsg = tipLabel + ' sekmesindeki yaklaşık ' + total + ' kayıt silinecek.\n\n';
        if (singleStep) {
            confirmMsg += 'Kapı kayıtları tek tek silinir; her adımda ilerleme gösterilir.\n\n';
        } else {
            confirmMsg += 'Sunucu taraflı toplu silme (parti: ' + batchSize + ' × ' + maxBatches + ' tur/istek).\n\n';
        }
        confirmMsg += 'Önce Kapı → Sokak → Mahalle sırası önerilir.\n\nDevam edilsin mi?';
        if (!confirm(confirmMsg)) {
            return;
        }

        let deletedTotal = 0;
        let failedTotal = 0;
        let skippedChildren = 0;
        let skippedPatient = 0;
        let batchNo = 0;
        let stoppedEarly = false;
        let userAborted = false;
        const startTotal = total;

        orphanBulkDeleteAbort = false;
        setOrphanDeleteButtonsDisabled(true);
        $('#orphanCheckAll').prop('checked', false);
        startOrphanBulkProgressUi(startTotal, batchSize);

        function finishBulkDeleteRun(state, err) {
            if (err) {
                let msg = 'Toplu silme durdu';
                if (deletedTotal > 0) {
                    msg += ' (' + deletedTotal + ' kayıt silindi; kalanlar için tekrar deneyin)';
                }
                msg += ': ' + (typeof err === 'string' ? err : 'istek başarısız.');
                $('#orphanDeleteStatus').text(msg);
            } else {
                let msg;
                if (userAborted || (state && state.aborted)) {
                    msg = 'Toplu silme durduruldu: ' + deletedTotal + ' kayıt silindi.';
                } else {
                    msg = 'Toplu silme tamamlandı: ' + deletedTotal + ' kayıt silindi.';
                }
                if (failedTotal > 0) {
                    msg += ' ' + failedTotal + ' kayıt silinemedi';
                    if (stoppedEarly) {
                        msg += ' (alt kayıt veya hasta referansı — önce alt seviyeleri silin).';
                    }
                }
                if (skippedPatient > 0) {
                    msg += ' Hasta referansı: ' + skippedPatient + '.';
                }
                $('#orphanDeleteStatus').text(msg);
            }
            orphanPage = 1;
            refreshOrphanCacheForTip(orphanActiveTip).then(function () {
                renderOrphanListFromCache();
            });
            stopOrphanBulkProgressUi();
            setOrphanDeleteButtonsDisabled(false);
        }

        function runSingleDeleteStep() {
            if (orphanBulkDeleteAbort) {
                userAborted = true;
                return $.Deferred().resolve({ done: true, aborted: true }).promise();
            }
            batchNo += 1;
            beginOrphanBulkPhase('list', {
                batchNo: batchNo,
                deletedTotal: deletedTotal,
                itemCount: 1
            });
            return fetchOrphanItemsBatch(1).then(function (items) {
                if (!items.length) {
                    return { done: true };
                }
                const item = items[0];
                beginOrphanBulkPhase('delete', {
                    itemCount: 1,
                    deletedTotal: deletedTotal,
                    currentItemId: item.id
                });
                return postOrphanDelete([item]).then(function (data) {
                    const deleted = data.deleted || [];
                    const failed = data.failed || [];
                    if (deleted.length > 0) {
                        deletedTotal += deleted.length;
                        removeFromOrphanCache(orphanActiveTip, item.id, item.ust_id);
                        updateOrphanSummary();
                    } else {
                        failedTotal += failed.length > 0 ? failed.length : 1;
                        if (failed.length > 0 && (failed[0].mesaj || '').indexOf('hasta') >= 0) {
                            skippedPatient += 1;
                        }
                    }
                    beginOrphanBulkPhase('batch-done', {
                        deletedTotal: deletedTotal,
                        lastBatchDeleted: deleted.length
                    });
                    if (deleted.length === 0) {
                        stoppedEarly = true;
                        return { done: true };
                    }
                    return { done: false };
                });
            });
        }

        function runPurgeRequest() {
            if (orphanBulkDeleteAbort) {
                userAborted = true;
                return $.Deferred().resolve({ done: true, aborted: true }).promise();
            }
            batchNo += 1;
            beginOrphanBulkPhase('purge', {
                batchNo: batchNo,
                deletedTotal: deletedTotal,
                itemCount: batchSize
            });
            return postOrphanPurge(orphanActiveTip, batchSize, maxBatches).then(function (data) {
                const deletedCount = parseInt(data.deleted_count, 10) || (data.deleted || []).length;
                const failedCount = parseInt(data.failed_count, 10) || (data.failed || []).length;
                deletedTotal += deletedCount;
                failedTotal += failedCount;
                skippedChildren += parseInt(data.skipped_has_children, 10) || 0;
                skippedPatient += parseInt(data.skipped_patient_ref, 10) || 0;
                if (deletedCount > 0) {
                    orphanCounts[orphanActiveTip] = Math.max(0, (orphanCounts[orphanActiveTip] || 0) - deletedCount);
                    if (typeof data.remaining_estimate === 'number') {
                        orphanCounts[orphanActiveTip] = Math.max(0, data.remaining_estimate);
                    }
                    const entry = orphanCacheEntry(orphanActiveTip);
                    entry.ready = false;
                    updateOrphanSummary();
                    refreshOrphanCacheForTip(orphanActiveTip);
                }
                beginOrphanBulkPhase('batch-done', {
                    deletedTotal: deletedTotal,
                    lastBatchDeleted: deletedCount
                });
                if (data.stuck) {
                    stoppedEarly = true;
                    return { done: true };
                }
                if (data.done) {
                    return { done: true };
                }
                if (deletedCount === 0 && failedCount > 0) {
                    stoppedEarly = true;
                    return { done: true };
                }
                return { done: false };
            });
        }

        function loopSteps(runStep) {
            return runStep().then(function (state) {
                if (state.done) {
                    return state;
                }
                return loopSteps(runStep);
            });
        }

        if (singleStep) {
            loopSteps(runSingleDeleteStep)
                .done(function (state) { finishBulkDeleteRun(state); })
                .fail(function (err) { finishBulkDeleteRun(null, err); });
            return;
        }

        loopSteps(runPurgeRequest)
            .done(function (state) { finishBulkDeleteRun(state); })
            .fail(function (err) { finishBulkDeleteRun(null, err); });
    }

    function firstOrphanTabWithCounts() {
        const order = ['mahalle', 'sokak', 'kapino'];
        for (let i = 0; i < order.length; i++) {
            const tip = order[i];
            if ((orphanCounts[tip] || 0) > 0) {
                return tip;
            }
        }
        return 'mahalle';
    }

    function activateOrphanTab(tip) {
        orphanActiveTip = tip;
        orphanPage = 1;
        $('#orphanTipTabs .nav-link').removeClass('active');
        $('#orphanTipTabs [data-orphan-tip="' + tip + '"]').addClass('active');
        updateOrphanDeleteAllButton();
    }

    function renderOrphanTable(items, total) {
        const $body = $('#orphanTableBody');
        const tipLabel = orphanScanTipLabels[orphanActiveTip] || orphanActiveTip;
        const listTotal = typeof total === 'number' ? total : orphanTotal;
        if (!items || !items.length) {
            if (listTotal > 0) {
                $body.html('<tr><td colspan="6" class="text-warning">'
                    + escHtml(tipLabel + ' sekmesinde ' + listTotal + ' kayıt sayıldı ancak liste boş döndü. Sayfayı yenileyin veya «Eksik ilçe tara»yı tekrar çalıştırın.')
                    + '</td></tr>');
            } else {
                $body.html('<tr><td colspan="6" class="text-muted">' + escHtml(tipLabel + ' sekmesinde eksik kayıt yok.') + '</td></tr>');
            }
            resetOrphanSelection();
            return;
        }
        let html = '';
        items.forEach(function (row) {
            const reason = REASON_LABELS[row.reason] || row.reason || '—';
            const id = String(row.id || '');
            const adi = String(row.adi || '');
            const ustId = String(row.ust_id || '');
            html += '<tr data-orphan-id="' + escHtml(id) + '" data-orphan-ust-id="' + escHtml(ustId) + '">'
                + '<td><input type="checkbox" class="form-check-input orphan-row-check" value="' + escHtml(id) + '" data-ust-id="' + escHtml(ustId) + '" aria-label="Seç: ' + escHtml(adi) + '"></td>'
                + '<td>' + escHtml(adi) + '</td>'
                + '<td><code>' + escHtml(shortId(row.id)) + '</code></td>'
                + '<td><code>' + escHtml(shortId(row.ust_id)) + '</code></td>'
                + '<td>' + escHtml(reason) + '</td>'
                + '<td><button type="button" class="btn btn-outline-danger btn-sm orphan-row-delete" data-id="' + escHtml(id) + '" data-ust-id="' + escHtml(ustId) + '" data-adi="' + escHtml(adi) + '" title="Sil">Sil</button></td>'
                + '</tr>';
        });
        $body.html(html);
        resetOrphanSelection();
    }

    function loadOrphanList(options) {
        options = options || {};
        const entry = orphanCacheEntry(orphanActiveTip);
        const loadingMessage = options.loadingMessage || 'Yükleniyor…';

        function failList(msg) {
            $('#orphanTableBody').html('<tr><td colspan="6" class="text-danger">' + escHtml(msg) + '</td></tr>');
            if (!options.skipStatus) {
                $('#orphanScanStatus').text(msg);
            }
            hideOrphanTableLoadingState();
            setOrphanPagerLoading(false);
        }

        if (options.forceRefresh) {
            showOrphanTableLoading('Sunucudan yenileniyor…');
            setOrphanPagerLoading(true);
            if (!options.skipStatus) {
                $('#orphanScanStatus').text('Liste önbelleği güncelleniyor…');
            }
            return refreshOrphanCacheForTip(orphanActiveTip).then(function () {
                return renderOrphanListFromCache(options);
            }).fail(function (err) {
                failList(typeof err === 'string' ? err : 'Liste yenilenemedi.');
            });
        }

        if (entry.ready) {
            setOrphanPagerLoading(true);
            return renderOrphanListFromCache(options);
        }

        showOrphanTableLoading(loadingMessage);
        setOrphanPagerLoading(true);
        if (!options.skipStatus) {
            $('#orphanScanStatus').text(loadingMessage);
        }

        const waitPromise = (entry.loading && entry.promise)
            ? entry.promise
            : prefetchOrphanListForTip(orphanActiveTip);

        return waitPromise.then(function () {
            return renderOrphanListFromCache(options);
        }).fail(function (err) {
            failList(typeof err === 'string' ? err : 'Liste yüklenemedi.');
        });
    }

    function fetchOrphanCountForTip(tip) {
        const url = String(cfg.orphanReportUrl || '');
        return $.ajax(orphanAjaxSettings({
            url: url,
            data: { mode: 'counts', tip: tip },
            dataType: 'json',
            timeout: tip === 'kapino' ? 300000 : 120000
        }));
    }

    function runOrphanScan() {
        const $btn = $('#orphanScanBtn');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Yükleniyor…');
        $('#orphanSummary').removeClass('d-none');
        orphanCounts = { mahalle: 0, sokak: 0, kapino: 0 };
        clearOrphanListCache();
        orphanScanDone = false;
        orphanKapinoCounted = false;
        orphanKapinoCounting = false;
        orphanPage = 1;
        updateOrphanSummary();
        showOrphanTableLoading('Taranıyor…');
        setOrphanPagerLoading(true);
        $('#orphanScanStatus').text('Mahalle taranıyor…');

        let chain = $.Deferred().resolve();
        ['mahalle', 'sokak'].forEach(function (tip) {
            chain = chain.then(function () {
                $('#orphanScanStatus').text((orphanScanTipLabels[tip] || tip) + ' taranıyor…');
                return fetchOrphanCountForTip(tip).then(function (data) {
                    if (!data || !data.ok) {
                        return $.Deferred().reject({ tip: tip, mesaj: (data && data.mesaj) ? data.mesaj : 'Tarama başarısız.' }).promise();
                    }
                    const count = parseInt(data.count, 10);
                    orphanCounts[tip] = Number.isFinite(count) ? count : 0;
                    updateOrphanSummary();
                    if (count > 0) {
                        $('#orphanScanStatus').text((orphanScanTipLabels[tip] || tip) + ' listesi önbelleğe alınıyor…');
                        return prefetchOrphanListForTip(tip);
                    }
                });
            });
        });

        chain.then(function () {
            $('#orphanScanStatus').text('Kapı taranıyor… (kayıt çoksa birkaç dakika sürebilir)');
            return ensureKapinoCount().fail(function (err) {
                const msg = (err && err.mesaj) ? err.mesaj : 'Kapı sayımı tamamlanamadı.';
                $('#orphanScanStatus').text(msg + ' Liste yükleniyor…');
                return $.Deferred().resolve().promise();
            });
        }).then(function () {
            orphanScanDone = true;
            const firstTip = firstOrphanTabWithCounts();
            activateOrphanTab(firstTip);
            if (orphanKapinoCounted) {
                $('#orphanScanStatus').text('Liste yükleniyor…');
            }
            return loadOrphanList({
                skipStatus: true,
                loadingMessage: 'Yükleniyor…'
            });
        }).done(function () {
            $('#orphanScanStatus').text(orphanScanStatusCompleteText());
            updateOrphanDeleteAllButton();
        }).fail(function (err) {
            const msg = (err && err.responseJSON && err.responseJSON.mesaj)
                ? err.responseJSON.mesaj
                : ((err && err.mesaj) ? err.mesaj : 'Tarama başarısız veya zaman aşımı.');
            $('#orphanTableBody').html('<tr><td colspan="6" class="text-danger">' + escHtml(msg) + '</td></tr>');
            $('#orphanScanStatus').text(msg);
        }).always(function () {
            $btn.prop('disabled', false).html(orphanScanBtnHtml);
        });
    }

    function bindOrphanEvents() {
        $('#orphanScanBtn').on('click', runOrphanScan);
        $('#orphanTipTabs').on('click', '[data-orphan-tip]', function () {
            orphanActiveTip = String($(this).attr('data-orphan-tip') || 'mahalle');
            orphanPage = 1;
            $('#orphanTipTabs .nav-link').removeClass('active');
            $(this).addClass('active');
            $('#orphanDeleteStatus').text('');
            updateOrphanDeleteAllButton();
            if (orphanActiveTip === 'kapino') {
                ensureKapinoCount().done(function () {
                    $('#orphanScanStatus').text(orphanScanDone ? orphanScanStatusCompleteText() : '');
                }).always(function () {
                    loadOrphanList();
                });
                return;
            }
            loadOrphanList();
        });
        $('#orphanNavWrap').on('click', '.orphan-page-link', function (ev) {
            ev.preventDefault();
            const $item = $(this).closest('.page-item');
            if ($item.hasClass('disabled') || $item.hasClass('active')) {
                return;
            }
            goToOrphanPage($(this).attr('data-page'));
        });
        $('#orphanLimitWrap').on('change', '.orphan-limit-select', function () {
            orphanPerPage = parseInt($(this).val(), 10) || 50;
            orphanPage = 1;
            $('#orphanDeleteStatus').text('');
            loadOrphanList();
        });
        $('#orphanNavWrap').on('click', '.orphan-jump-page-btn', function () {
            const max = parseInt($(this).attr('data-max'), 10) || 1;
            const p = parseInt($('#orphan_jump_page').val(), 10);
            if (p > 0 && p <= max) {
                goToOrphanPage(p);
            }
        });
        $('#orphanCheckAll').on('change', function () {
            const checked = $(this).prop('checked');
            $('#orphanTableBody .orphan-row-check').prop('checked', checked);
            updateOrphanDeleteButton();
        });
        $('#orphanTableBody').on('change', '.orphan-row-check', updateOrphanDeleteButton);
        $('#orphanDeleteSelectedBtn').on('click', function () {
            const items = getSelectedOrphanItems();
            deleteOrphanIds(items, items.length + ' kayıt silinsin mi?');
        });
        $('#orphanDeleteAllBtn').on('click', deleteAllOrphansOnTab);
        $('#orphanBulkDeleteAbortBtn').on('click', function () {
            orphanBulkDeleteAbort = true;
            $('#orphanDeleteStatus').text('Durdurma istendi… mevcut istek bitince duracak.');
        });
        $('#orphanTableBody').on('click', '.orphan-row-delete', function () {
            const id = String($(this).attr('data-id') || '');
            const ustId = String($(this).attr('data-ust-id') || '');
            const adi = String($(this).attr('data-adi') || id);
            deleteOrphanIds([{ id: id, ust_id: ustId }], '"' + adi + '" kaydı silinsin mi?');
        });
    }

    function initOrphanTarama() {
        if (!$('#orphanScanBtn').length) {
            return;
        }
        bindOrphanEvents();
    }

    $(document).ready(function () {
        initOrphanTarama();
    });

})();
