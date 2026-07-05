(function () {
    'use strict';

    var cfg = window.ESH_MANUEL_KOORDINAT;
    if (!cfg) {
        return;
    }

    var searchInput = document.getElementById('mk-search-input');
    var searchBtn = document.getElementById('mk-search-btn');
    var searchResults = document.getElementById('mk-search-results');
    var detailCard = document.getElementById('mk-detail-card');
    var emptyHint = document.getElementById('mk-empty-hint');
    var detailAdres = document.getElementById('mk-detail-adres');
    var detailCoords = document.getElementById('mk-detail-coords');
    var detailHastaSayisi = document.getElementById('mk-detail-hasta-sayisi');
    var detailHastaWrap = document.getElementById('mk-detail-hasta');
    var detailHastaIsim = document.getElementById('mk-detail-hasta-isim');
    var detailHastaTc = document.getElementById('mk-detail-hasta-tc');
    var detailHastaLink = document.getElementById('mk-detail-hasta-link');
    var coordsInput = document.getElementById('mk-coords-input');
    var mapCoordsBadge = document.getElementById('mk-map-coords-badge');
    var geocodePreviewBtn = document.getElementById('mk-geocode-preview-btn');
    var saveBtn = document.getElementById('mk-save-btn');
    var clearBtn = document.getElementById('mk-clear-btn');

    var state = {
        kapinoId: '',
        hastaId: '',
        savedCoords: '',
        draftCoords: '',
        detail: null
    };

    var pinPicker = null;
    var mapApiRef = null;
    var searchTimer = null;
    var pendingMapActions = [];
    var mapApplyToken = 0;

    function bumpMapApplyToken() {
        mapApplyToken += 1;
    }

    function runWhenMapReady(fn) {
        if (typeof fn !== 'function') {
            return;
        }
        if (mapApiRef && typeof mapApiRef.onLoad === 'function') {
            mapApiRef.onLoad(fn);
            return;
        }
        pendingMapActions.push(fn);
    }

    function flushPendingMapActions() {
        if (!mapApiRef || typeof mapApiRef.onLoad !== 'function') {
            return;
        }
        var queue = pendingMapActions.slice();
        pendingMapActions = [];
        queue.forEach(function (fn) {
            mapApiRef.onLoad(fn);
        });
    }

    function parseCoordsString(coordsStr) {
        if (!coordsStr) {
            return null;
        }
        var parts = String(coordsStr).replace(/\s/g, '').split(',');
        if (parts.length !== 2) {
            return null;
        }
        var lat = parseFloat(parts[0]);
        var lng = parseFloat(parts[1]);
        if (!isFinite(lat) || !isFinite(lng)) {
            return null;
        }
        return { lat: lat, lng: lng };
    }

    function focusMapOnCoords(coordsStr, zoom, instant) {
        if (!mapApiRef || !mapApiRef.raw) {
            return false;
        }
        var p = parseCoordsString(coordsStr);
        if (!p) {
            return false;
        }
        var map = mapApiRef.raw;
        var z = zoom || 16;
        var center = [p.lng, p.lat];
        if (instant && typeof map.jumpTo === 'function') {
            map.jumpTo({ center: center, zoom: z });
        } else if (typeof map.flyTo === 'function') {
            map.flyTo({ center: center, zoom: z, essential: true });
        } else if (typeof map.jumpTo === 'function') {
            map.jumpTo({ center: center, zoom: z });
        } else if (typeof map.easeTo === 'function') {
            map.easeTo({ center: center, zoom: z });
        } else if (typeof map.setCenter === 'function') {
            map.setCenter(center);
            if (typeof map.setZoom === 'function') {
                map.setZoom(z);
            }
        } else {
            return false;
        }
        if (typeof mapApiRef.resize === 'function') {
            mapApiRef.resize();
        }
        return true;
    }

    function scheduleMapResize() {
        if (!mapApiRef || typeof mapApiRef.resize !== 'function') {
            return;
        }
        mapApiRef.resize();
        setTimeout(function () {
            if (mapApiRef && typeof mapApiRef.resize === 'function') {
                mapApiRef.resize();
            }
        }, 300);
    }

    function toastError(msg) {
        if (window.toastr) {
            toastr.error(msg);
        }
    }

    function toastSuccess(msg) {
        if (window.toastr) {
            toastr.success(msg);
        }
    }

    function urlWithParams(base, params) {
        var u = new URL(base, window.location.origin);
        Object.keys(params).forEach(function (k) {
            if (params[k] !== null && params[k] !== undefined && params[k] !== '') {
                u.searchParams.set(k, String(params[k]));
            }
        });
        return u.pathname + u.search;
    }

    function coordsEqual(a, b) {
        var pa = parseCoordsString(a);
        var pb = parseCoordsString(b);
        if (!pa || !pb) {
            return String(a || '') === String(b || '');
        }
        return pa.lat.toFixed(6) === pb.lat.toFixed(6) && pa.lng.toFixed(6) === pb.lng.toFixed(6);
    }

    function normalizeCoordsStr(coordsStr) {
        var p = parseCoordsString(coordsStr);
        if (!p) {
            return String(coordsStr || '').trim();
        }
        return p.lat.toFixed(6) + ',' + p.lng.toFixed(6);
    }

    function updateCoordsUi(coords) {
        coords = normalizeCoordsStr(coords);
        state.draftCoords = coords || '';
        if (coordsInput) {
            coordsInput.value = coords || '';
        }
        if (mapCoordsBadge) {
            mapCoordsBadge.textContent = coords || '—';
        }
        if (saveBtn) {
            var changed = !coordsEqual(coords, state.savedCoords);
            saveBtn.disabled = !state.kapinoId || !coords || !changed;
        }
        if (clearBtn) {
            clearBtn.disabled = !state.kapinoId || !state.savedCoords;
        }
    }

    function showDetailPanel(show) {
        if (detailCard) {
            detailCard.classList.toggle('d-none', !show);
        }
        if (emptyHint) {
            emptyHint.classList.toggle('d-none', show);
        }
    }

    function renderSearchResults(items) {
        if (!searchResults) {
            return;
        }
        searchResults.innerHTML = '';
        if (!items || !items.length) {
            searchResults.classList.add('d-none');
            return;
        }
        items.forEach(function (item) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action';
            btn.setAttribute('role', 'option');
            var durumBadge = item.durum === 'bekleyen'
                ? '<span class="badge bg-warning text-dark ms-1">Bekleyen</span>'
                : '<span class="badge bg-success ms-1">Aktif</span>';
            var coordsTxt = item.coords
                ? '<span class="font-monospace small text-muted d-block">' + item.coords + '</span>'
                : '<span class="small text-danger d-block">Koordinat yok</span>';
            btn.innerHTML = '<div class="fw-semibold">' + escapeHtml(item.isim) + durumBadge + '</div>'
                + '<div class="small text-muted font-monospace">' + escapeHtml(item.tckimlik) + '</div>'
                + '<div class="small">' + escapeHtml(item.adres_ozet || '—') + '</div>'
                + coordsTxt;
            btn.addEventListener('click', function () {
                selectPatient(item);
            });
            searchResults.appendChild(btn);
        });
        searchResults.classList.remove('d-none');
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    var mkNativePrimaryMarker = null;

    function clearMkNativePrimaryMarker() {
        if (mkNativePrimaryMarker && typeof mkNativePrimaryMarker.remove === 'function') {
            mkNativePrimaryMarker.remove();
        }
        mkNativePrimaryMarker = null;
    }

    function bindNativePrimaryDrag(marker) {
        if (!marker || typeof marker.setDraggable !== 'function') {
            return;
        }
        marker.setDraggable(true);
        var onDragEnd = function () {
            if (!marker || typeof marker.getLngLat !== 'function') {
                return;
            }
            var ll = marker.getLngLat();
            bumpMapApplyToken();
            updateCoordsUi(ll.lat.toFixed(6) + ',' + ll.lng.toFixed(6));
        };
        if (typeof marker.on === 'function') {
            marker.on('dragend', onDragEnd);
        } else if (typeof marker.addEventListener === 'function') {
            marker.addEventListener('dragend', onDragEnd);
        }
    }

    function placePrimaryPin(coordsStr) {
        var p = parseCoordsString(coordsStr);
        if (!p) {
            return;
        }
        if (pinPicker && typeof pinPicker.ensureReady === 'function') {
            pinPicker.ensureReady();
        }

        var useNativeMarker = mapApiRef
            && mapApiRef.mapSdk === 'tomtom'
            && typeof mapApiRef.addMarker === 'function';

        if (useNativeMarker) {
            clearMkNativePrimaryMarker();
            mkNativePrimaryMarker = mapApiRef.addMarker([p.lng, p.lat], {
                color: '#0d6efd',
                anchor: 'bottom'
            });
            bindNativePrimaryDrag(mkNativePrimaryMarker);
            return;
        }

        if (pinPicker) {
            if (typeof pinPicker.placePrimaryNow === 'function') {
                pinPicker.placePrimaryNow(coordsStr, { flyTo: false, silent: true });
            } else {
                pinPicker.setPrimaryFromCoords(coordsStr, { flyTo: false, silent: true });
            }
        }
    }

    function schedulePrimaryPinRetries(coordsStr, focusToken, delays) {
        delays.forEach(function (ms) {
            setTimeout(function () {
                if (focusToken !== mapApplyToken) {
                    return;
                }
                placePrimaryPin(coordsStr);
            }, ms);
        });
    }

    function placePatientOnMap(coordsStr, zoom) {
        if (!coordsStr) {
            return;
        }
        var z = zoom || 16;
        var focusToken = ++mapApplyToken;
        runWhenMapReady(function () {
            if (pinPicker && typeof pinPicker.ensureReady === 'function') {
                pinPicker.ensureReady();
            }
            scheduleMapResize();
            focusMapOnCoords(coordsStr, z, true);
            placePrimaryPin(coordsStr);
            schedulePrimaryPinRetries(coordsStr, focusToken, [80, 250, 600, 1200]);
        });
    }

    function focusMapOnPatient(coordsStr, zoom) {
        placePatientOnMap(coordsStr, zoom);
    }

    function selectPatient(item) {
        state.hastaId = item.hasta_id || '';
        state.kapinoId = item.kapino_id || '';
        if (searchResults) {
            searchResults.classList.add('d-none');
        }
        if (searchInput && item.isim) {
            searchInput.value = item.isim;
        }
        loadKapinoDetail(state.kapinoId, state.hastaId);
    }

    function loadKapinoDetail(kapinoId, hastaId) {
        if (!kapinoId) {
            toastError('Hastanın kapı no kaydı eksik.');
            return;
        }
        var url = urlWithParams(cfg.kapinoDetailUrl, {
            kapino_id: kapinoId,
            hasta_id: hastaId || ''
        });
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res || !res.ok || !res.detail) {
                    toastError((res && res.mesaj) ? res.mesaj : 'Kapı detayı yüklenemedi.');
                    return;
                }
                applyDetail(res.detail);
            })
            .catch(function () {
                toastError('Kapı detayı yüklenemedi.');
            });
    }

    function syncDetailToMap() {
        if (!state.detail) {
            return;
        }
        var detail = state.detail;
        var coords = detail.coords || '';
        if (pinPicker) {
            pinPicker.clearGhost();
        }
        if (!coords) {
            if (cfg.center && cfg.center.length === 2 && pinPicker) {
                pinPicker.flyTo(cfg.center[1], cfg.center[0], 12);
            }
            return;
        }
        focusMapOnPatient(coords, 16);
    }

    function applyDetail(detail) {
        state.detail = detail;
        state.kapinoId = detail.kapino_id || '';
        state.savedCoords = normalizeCoordsStr(detail.coords || '');
        state.draftCoords = state.savedCoords;

        showDetailPanel(true);

        if (detailAdres) {
            detailAdres.textContent = detail.adres_tam || '—';
        }
        if (detailCoords) {
            detailCoords.textContent = detail.coords || 'Girilmemiş';
        }
        if (detailHastaSayisi) {
            detailHastaSayisi.textContent = String(detail.hasta_sayisi || 0);
        }

        if (detail.hasta && detailHastaWrap) {
            detailHastaWrap.classList.remove('d-none');
            if (detailHastaIsim) {
                detailHastaIsim.textContent = detail.hasta.isim || '';
            }
            if (detailHastaTc) {
                detailHastaTc.textContent = detail.hasta.tckimlik || '';
            }
            if (detailHastaLink && detail.hasta.view_url) {
                detailHastaLink.href = detail.hasta.view_url;
                detailHastaLink.classList.remove('d-none');
            }
        } else if (detailHastaWrap) {
            detailHastaWrap.classList.add('d-none');
        }

        updateCoordsUi(state.savedCoords);

        scheduleMapResize();
        syncDetailToMap();
        setTimeout(function () {
            scheduleMapResize();
            if (coordsEqual(state.draftCoords, state.savedCoords) && state.savedCoords) {
                focusMapOnCoords(state.savedCoords, 16, true);
                placePrimaryPin(state.savedCoords);
            }
        }, 400);
    }

    function runSearch() {
        var q = searchInput ? searchInput.value.trim() : '';
        if (q.length < 2) {
            renderSearchResults([]);
            return;
        }
        var url = urlWithParams(cfg.searchUrl, { q: q });
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res || !res.ok) {
                    toastError('Arama başarısız.');
                    return;
                }
                renderSearchResults(res.results || []);
            })
            .catch(function () {
                toastError('Arama başarısız.');
            });
    }

    function applyGeocodeSuggestion(coordsStr) {
        coordsStr = normalizeCoordsStr(coordsStr);
        if (!coordsStr) {
            return;
        }
        bumpMapApplyToken();
        state.draftCoords = coordsStr;
        updateCoordsUi(coordsStr);
        if (pinPicker && typeof pinPicker.clearGhost === 'function') {
            pinPicker.clearGhost();
        }
        focusMapOnCoords(coordsStr, 15, true);
        placePrimaryPin(coordsStr);
        schedulePrimaryPinRetries(coordsStr, mapApplyToken, [150, 500]);
    }

    function geocodePreview() {
        if (!state.kapinoId) {
            return;
        }
        if (!cfg.providerConfigured) {
            toastError('Harita sağlayıcısı yapılandırılmamış.');
            return;
        }
        var url = urlWithParams(cfg.geocodePreviewUrl, { kapino_id: state.kapinoId });
        if (geocodePreviewBtn) {
            geocodePreviewBtn.disabled = true;
        }
        fetch(url, { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res || !res.ok) {
                    toastError((res && res.mesaj) ? res.mesaj : 'Geocode önerisi alınamadı.');
                    return;
                }
                if (res.coords) {
                    applyGeocodeSuggestion(res.coords);
                }
                if (window.toastr) {
                    var canSave = res.coords && !coordsEqual(res.coords, state.savedCoords);
                    toastr.info(canSave
                        ? 'Öneri konum seçildi; Kaydet ile kapı kaydına yazabilirsiniz.'
                        : 'Öneri konum mevcut kayıtla aynı; haritadan düzeltip kaydedebilirsiniz.');
                }
            })
            .catch(function () {
                toastError('Geocode önerisi alınamadı.');
            })
            .finally(function () {
                if (geocodePreviewBtn) {
                    geocodePreviewBtn.disabled = false;
                }
            });
    }

    function saveCoords(clear) {
        if (!state.kapinoId) {
            return;
        }
        var msg = clear
            ? 'Bu kapı kaydının koordinatı silinecek. Devam edilsin mi?'
            : 'Seçilen koordinat kapı kaydına yazılacak; aynı kapıdaki tüm hastalar etkilenir. Devam edilsin mi?';
        if (!window.confirm(msg)) {
            return;
        }

        var body = new URLSearchParams();
        body.set('kapino_id', state.kapinoId);
        if (clear) {
            body.set('clear', '1');
        } else {
            body.set('coords', state.draftCoords);
        }

        saveBtn.disabled = true;
        fetch(cfg.saveCoordsUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res || !res.ok) {
                    toastError((res && res.mesaj) ? res.mesaj : 'Kayıt başarısız.');
                    return;
                }
                state.savedCoords = res.coords || '';
                if (detailCoords) {
                    detailCoords.textContent = state.savedCoords || 'Girilmemiş';
                }
                updateCoordsUi(state.draftCoords);
                toastSuccess(res.mesaj || 'Kaydedildi.');
                if (clear && pinPicker) {
                    pinPicker.clearGhost();
                    clearMkNativePrimaryMarker();
                    updateCoordsUi('');
                }
            })
            .catch(function () {
                toastError('Kayıt başarısız.');
            })
            .finally(function () {
                updateCoordsUi(state.draftCoords);
            });
    }

    function bindUi() {
        if (searchBtn) {
            searchBtn.addEventListener('click', runSearch);
        }
        if (searchInput) {
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    runSearch();
                }
            });
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(runSearch, 350);
            });
        }
        if (geocodePreviewBtn) {
            geocodePreviewBtn.addEventListener('click', geocodePreview);
        }
        if (saveBtn) {
            saveBtn.addEventListener('click', function () { saveCoords(false); });
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', function () { saveCoords(true); });
        }
    }

    function initDeepLink() {
        if (cfg.deepLinkHastaId) {
            var url = urlWithParams(cfg.searchUrl, { hasta_id: cfg.deepLinkHastaId });
            fetch(url, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.ok && res.results && res.results[0]) {
                        selectPatient(res.results[0]);
                        return;
                    }
                    var detailUrl = urlWithParams(cfg.kapinoDetailUrl, {
                        hasta_id: cfg.deepLinkHastaId,
                        kapino_id: ''
                    });
                    fetch(detailUrl, { credentials: 'same-origin' })
                        .then(function (r) { return r.json(); })
                        .then(function (detailRes) {
                            if (detailRes && detailRes.ok && detailRes.detail) {
                                state.hastaId = cfg.deepLinkHastaId;
                                applyDetail(detailRes.detail);
                                return;
                            }
                            toastError('Bağlantıdaki hasta yüklenemedi veya yetkiniz yok.');
                        })
                        .catch(function () {
                            toastError('Bağlantıdaki hasta yüklenemedi.');
                        });
                })
                .catch(function () {
                    toastError('Bağlantıdaki hasta yüklenemedi.');
                });
        } else if (cfg.deepLinkKapinoId) {
            loadKapinoDetail(cfg.deepLinkKapinoId, 0);
        }
    }

    function bootMap() {
        if (!window.EshMapRuntime || !window.EshMapPinPicker) {
            toastError('Harita bileşenleri yüklenemedi.');
            return;
        }
        window.EshMapRuntime.create({
            mapConfigUrl: cfg.mapConfigUrl,
            container: 'esh-mk-map',
            center: cfg.center,
            zoom: 11
        }).then(function (mapApi) {
            mapApiRef = mapApi;
            mapApi.addNavigationControl('top-right');
            if (typeof mapApi.addFullscreenControl === 'function') {
                mapApi.addFullscreenControl();
            }
            var startPicker = function () {
                pinPicker = window.EshMapPinPicker.create(mapApi, {
                    onChange: function (coordsStr) {
                        clearMkNativePrimaryMarker();
                        bumpMapApplyToken();
                        updateCoordsUi(coordsStr);
                    }
                });
                pinPicker.bindClick();
                scheduleMapResize();
                bindUi();
                flushPendingMapActions();
                syncDetailToMap();
                pinPicker.whenReady(function () {
                    setTimeout(initDeepLink, 400);
                });
            };
            if (typeof mapApi.onLoad === 'function') {
                mapApi.onLoad(startPicker);
            } else {
                startPicker();
            }
        }).catch(function (err) {
            var detail = (err && err.message) ? String(err.message) : '';
            toastError('Harita başlatılamadı.' + (detail ? ' ' + detail : ''));
            bindUi();
            initDeepLink();
        });
    }

    if (!cfg.providerConfigured) {
        bindUi();
        initDeepLink();
        return;
    }
    bootMap();
})();
