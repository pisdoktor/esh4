(function () {
    'use strict';

    var MOD = { CLUSTER: 'cluster', HEAT: 'heatmap', DOTS: 'dots', HYBRID: 'hybrid' };
    var PATIENT_LAYER_IDS = ['clusters', 'cluster-count', 'hasta-heatmap', 'hasta-dots'];

    var cfg = window.ESH_HARITA;
    if (!cfg || !window.EshMapRuntime) {
        return;
    }
    cfg.mahalleCombinedUrl = cfg.mahalleCombinedUrl || '';
    cfg.mahalleGeoNameAllowlist = Array.isArray(cfg.mahalleGeoNameAllowlist) ? cfg.mahalleGeoNameAllowlist : [];

    var mapConfigUrl = String(cfg.mapConfigUrl || cfg.mapKeyUrl || '');
    if (!mapConfigUrl) {
        if (window.toastr) {
            toastr.error('Harita yapılandırması eksik.');
        }
        return;
    }

    window.EshMapRuntime.create({
        mapConfigUrl: mapConfigUrl,
        container: 'esh-harita-map',
        center: cfg.center,
        zoom: 11
    }).then(function (api) {
        bootHarita(api);
    }).catch(function () {
        if (window.toastr) {
            toastr.error('Harita başlatılamadı.');
        }
    });

    function bootHarita(mapApi) {
    var map = mapApi.raw;
    var mapEngine = mapApi.engine || 'maplibre_like';
    mapApi.addFullscreenControl();
    mapApi.addNavigationControl('top-right');

    function mahalleGeoMatchKey(s) {
        if (!s || typeof s !== 'string') {
            return '';
        }
        var t = s.trim().replace(/\s+/g, ' ');
        t = t.replace(/\s+Mahallesi$/i, '');
        return t.toLocaleLowerCase('tr-TR');
    }

    function filterMahalleGeoToAllowlist(geojson) {
        var allow = cfg.mahalleGeoNameAllowlist;
        if (!Array.isArray(allow) || allow.length === 0) {
            return { type: 'FeatureCollection', features: [] };
        }
        var set = {};
        var j;
        for (j = 0; j < allow.length; j++) {
            set[mahalleGeoMatchKey(allow[j])] = true;
        }
        var raw = geojson && Array.isArray(geojson.features) ? geojson.features : [];
        var feats = raw.filter(function (f) {
            var n = f && f.properties && f.properties.name != null ? String(f.properties.name) : '';
            return !!set[mahalleGeoMatchKey(n)];
        });
        return { type: 'FeatureCollection', features: feats };
    }

    var tumHastalar = Array.isArray(cfg.patients) ? cfg.patients : [];
    var gorunenHastalar = tumHastalar;
    var merkezNoktasi = cfg.center;
    var aktifPopup = null;
    var markersOnTheMap = {};
    var mapReady = false;
    var currentMode = MOD.CLUSTER;
    var dragListenerAttached = false;

    if (mapEngine === 'google' && window.toastr) {
        toastr.info('Google Maps modunda küme/ısı haritası sınırlıdır; nokta görünümü önerilir.');
    }

    function createClusterMarker(lngLat, color, html) {
        return mapApi.addMarker(lngLat, {
            color: color,
            popupHtml: html,
            popupOffset: 30,
            maxWidth: '320px'
        });
    }

    var mahalleOverlayWanted = false;

    function patientVizAnchorId() {
        var ids = ['hasta-heatmap', 'hasta-dots', 'clusters', 'cluster-count'];
        for (var i = 0; i < ids.length; i++) {
            if (map.getLayer(ids[i])) {
                return ids[i];
            }
        }
        return 'route-layer';
    }

    function removeMahalleOverlay() {
        if (map.getLayer('mahalle-label')) {
            map.removeLayer('mahalle-label');
        }
        if (map.getLayer('mahalle-outline')) {
            map.removeLayer('mahalle-outline');
        }
        if (map.getLayer('mahalle-fill')) {
            map.removeLayer('mahalle-fill');
        }
        if (map.getSource('mahalle-osm')) {
            map.removeSource('mahalle-osm');
        }
    }

    function addMahalleOverlay() {
        var url = cfg.mahalleCombinedUrl;
        if (!url || typeof url !== 'string') {
            return;
        }
        if (map.getSource('mahalle-osm')) {
            return;
        }
        if (!cfg.mahalleGeoNameAllowlist.length) {
            if (window.toastr) {
                toastr.warning('Adres tablosunda Pamukkale/Merkezefendi mahallesi yok; sınır katmanı gösterilemiyor.');
            }
            return;
        }

        fetch(url, { credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) {
                    throw new Error('HTTP ' + r.status);
                }
                return r.json();
            })
            .then(function (gj) {
                if (!mahalleOverlayWanted || map.getSource('mahalle-osm')) {
                    return;
                }
                var filtered = filterMahalleGeoToAllowlist(gj);
                map.addSource('mahalle-osm', { type: 'geojson', data: filtered });
                var beforeId = patientVizAnchorId();
                map.addLayer({
                    id: 'mahalle-fill',
                    type: 'fill',
                    source: 'mahalle-osm',
                    paint: {
                        'fill-color': [
                            'match',
                            ['get', 'ilce_atama'],
                            'Pamukkale',
                            'rgba(41, 128, 185, 0.32)',
                            'Merkezefendi',
                            'rgba(142, 68, 173, 0.3)',
                            'rgba(149, 165, 166, 0.22)'
                        ]
                    }
                }, beforeId);
                map.addLayer({
                    id: 'mahalle-outline',
                    type: 'line',
                    source: 'mahalle-osm',
                    layout: { 'line-join': 'round', 'line-cap': 'round' },
                    paint: {
                        'line-color': 'rgba(44, 62, 80, 0.88)',
                        'line-opacity': 0.92,
                        'line-width': [
                            'interpolate',
                            ['linear'],
                            ['zoom'],
                            9,
                            1.6,
                            11,
                            2.4,
                            13,
                            3.2,
                            15,
                            4.2
                        ]
                    }
                }, beforeId);
                map.addLayer({
                    id: 'mahalle-label',
                    type: 'symbol',
                    source: 'mahalle-osm',
                    layout: {
                        'text-field': ['get', 'name'],
                        'text-size': [
                            'interpolate',
                            ['linear'],
                            ['zoom'],
                            10,
                            8,
                            12,
                            10,
                            14,
                            11,
                            16,
                            12
                        ],
                        'text-max-width': 12,
                        'text-line-height': 1.15,
                        'text-padding': 4,
                        'text-allow-overlap': false,
                        'text-optional': true
                    },
                    paint: {
                        'text-color': '#1a252f',
                        'text-halo-color': 'rgba(255,255,255,0.92)',
                        'text-halo-width': 1.8,
                        'text-halo-blur': 0.35
                    }
                }, beforeId);
            })
            .catch(function () {
                if (window.toastr) {
                    toastr.error('Mahalle GeoJSON yüklenemedi.');
                }
            });
    }

    function syncMahalleOverlay() {
        removeMahalleOverlay();
        if (mahalleOverlayWanted) {
            addMahalleOverlay();
        }
    }

    function geoJsonOlustur(data) {
        return {
            type: 'FeatureCollection',
            features: data.map(function (h) {
                return {
                    type: 'Feature',
                    geometry: { type: 'Point', coordinates: [h.lng, h.lat] },
                    properties: { id: h.id, html: h.html, tc: h.tc, isim: h.isim, cinsiyet: h.cinsiyet }
                };
            })
        };
    }

    function insertLayerBeforeRoute(layerDef) {
        if (map.getLayer('route-layer')) {
            map.addLayer(layerDef, 'route-layer');
        } else {
            map.addLayer(layerDef);
        }
    }

    function clearMarkers() {
        Object.keys(markersOnTheMap).forEach(function (id) {
            markersOnTheMap[id].remove();
            delete markersOnTheMap[id];
        });
    }

    function refreshMarkers() {
        clearMarkers();
        if (!map.getSource('hasta-source') || currentMode !== MOD.CLUSTER) {
            return;
        }
        var features = map.querySourceFeatures('hasta-source');
        features.forEach(function (feature) {
            if (feature.properties && !feature.properties.cluster) {
                var fid = feature.properties.id;
                if (!markersOnTheMap[fid]) {
                    var c = feature.properties.cinsiyet ? String(feature.properties.cinsiyet).toLowerCase() : '';
                    var color = (c === 'e' || c === 'erkek') ? '#3498db' : (c === 'k' || c === 'kadın' || c === 'kadin' ? '#e91e63' : '#95a5a6');
                    var m = createClusterMarker(feature.geometry.coordinates, color, feature.properties.html);
                    markersOnTheMap[fid] = m;
                }
            }
        });
    }

    function onClusterMoveEnd() {
        refreshMarkers();
    }

    function onClusterData(e) {
        if (e.sourceId === 'hasta-source' && map.getSource('hasta-source') && map.getSource('hasta-source').loaded()) {
            refreshMarkers();
        }
    }

    function teardownPatient() {
        popupKapat();
        clearMarkers();
        PATIENT_LAYER_IDS.forEach(function (lid) {
            if (map.getLayer(lid)) {
                map.removeLayer(lid);
            }
        });
        if (map.getSource('hasta-source')) {
            map.removeSource('hasta-source');
        }
        map.off('moveend', onClusterMoveEnd);
        map.off('data', onClusterData);
        map.off('click', onMapClickHeatHybrid);
        map.off('click', 'hasta-dots', onDotsLayerClick);
    }

    function hastalariKaynagaUygula(list) {
        gorunenHastalar = list;
        if (map.getSource('hasta-source')) {
            map.getSource('hasta-source').setData(geoJsonOlustur(list));
        }
        if (currentMode === MOD.CLUSTER) {
            setTimeout(refreshMarkers, 80);
        }
    }

    function enYakinHastaPiksel(lngLat, list, maxPx) {
        var clickPx = map.project(lngLat);
        var best = null;
        var bestD2 = maxPx * maxPx;
        list.forEach(function (h) {
            var p = map.project([h.lng, h.lat]);
            var dx = p.x - clickPx.x;
            var dy = p.y - clickPx.y;
            var d2 = dx * dx + dy * dy;
            if (d2 < bestD2) {
                bestD2 = d2;
                best = h;
            }
        });
        return best;
    }

    function popupKapat() {
        if (aktifPopup) {
            if (typeof aktifPopup.remove === 'function') {
                aktifPopup.remove();
            } else if (typeof aktifPopup.close === 'function') {
                aktifPopup.close();
            }
            aktifPopup = null;
        }
    }

    function popupAc(lng, lat, html) {
        popupKapat();
        aktifPopup = mapApi.addPopup([lng, lat], html, { offset: 18, maxWidth: '320px' });
    }

    function onDotsLayerClick(e) {
        if (!e.features || !e.features.length) {
            return;
        }
        var f = e.features[0];
        var coords = f.geometry.coordinates;
        var html = f.properties.html;
        popupAc(coords[0], coords[1], html);
    }

    function onMapClickHeatHybrid(e) {
        if (!gorunenHastalar.length) {
            return;
        }
        if (currentMode === MOD.HYBRID) {
            var feats = map.queryRenderedFeatures(e.point, { layers: ['hasta-dots'] });
            if (feats.length) {
                onDotsLayerClick({ features: feats });
                return;
            }
        }
        if (currentMode !== MOD.HEAT && currentMode !== MOD.HYBRID) {
            return;
        }
        var maxPx = currentMode === MOD.HYBRID ? 40 : 34;
        var yakin = enYakinHastaPiksel(e.lngLat, gorunenHastalar, maxPx);
        if (!yakin) {
            return;
        }
        popupAc(yakin.lng, yakin.lat, yakin.html);
    }

    function circleColorMatch() {
        return [
            'match', ['get', 'cinsiyet'],
            'e', '#3498db',
            'E', '#3498db',
            'erkek', '#3498db',
            'Erkek', '#3498db',
            'ERKEK', '#3498db',
            'k', '#e91e63',
            'K', '#e91e63',
            'kadın', '#e91e63',
            'Kadın', '#e91e63',
            'kadin', '#e91e63',
            'KADIN', '#e91e63',
            '#95a5a6'
        ];
    }

    function makeHeatmapLayer(kind) {
        var hybrid = kind === 'hybrid';
        return {
            id: 'hasta-heatmap',
            type: 'heatmap',
            source: 'hasta-source',
            maxzoom: 20,
            paint: {
                'heatmap-weight': 1,
                'heatmap-intensity': hybrid ? [
                    'interpolate', ['linear'], ['zoom'],
                    6, 0.55,
                    10, 1,
                    14, 1.6,
                    17, 2
                ] : [
                    'interpolate', ['linear'], ['zoom'],
                    6, 0.8,
                    10, 1.4,
                    14, 2.2,
                    17, 2.8
                ],
                'heatmap-color': [
                    'interpolate', ['linear'], ['heatmap-density'],
                    0, 'rgba(33, 102, 172, 0)',
                    0.15, 'rgba(67, 147, 195, 0.55)',
                    0.35, 'rgba(146, 207, 209, 0.75)',
                    0.55, 'rgba(254, 224, 144, 0.85)',
                    0.75, 'rgba(252, 141, 89, 0.92)',
                    0.9, 'rgba(215, 48, 39, 0.95)',
                    1, 'rgba(165, 0, 38, 0.98)'
                ],
                'heatmap-radius': [
                    'interpolate', ['linear'], ['zoom'],
                    6, hybrid ? 14 : 18,
                    9, hybrid ? 22 : 28,
                    12, hybrid ? 30 : 38,
                    15, hybrid ? 38 : 48,
                    18, hybrid ? 46 : 58
                ],
                'heatmap-opacity': hybrid ? [
                    'interpolate', ['linear'], ['zoom'],
                    6, 0.5,
                    12, 0.44,
                    16, 0.38,
                    19, 0.3
                ] : [
                    'interpolate', ['linear'], ['zoom'],
                    6, 0.92,
                    12, 0.82,
                    16, 0.72,
                    19, 0.58
                ]
            }
        };
    }

    function makeDotsLayer(kind) {
        var hybrid = kind === 'hybrid';
        return {
            id: 'hasta-dots',
            type: 'circle',
            source: 'hasta-source',
            paint: {
                'circle-radius': hybrid ? [
                    'interpolate', ['linear'], ['zoom'],
                    8, 4,
                    12, 5.5,
                    16, 7
                ] : [
                    'interpolate', ['linear'], ['zoom'],
                    8, 6,
                    12, 9,
                    16, 12
                ],
                'circle-color': circleColorMatch(),
                'circle-stroke-width': 1,
                'circle-stroke-color': '#ffffff',
                'circle-opacity': hybrid ? 0.9 : 0.92
            }
        };
    }

    function updateModHint(mode) {
        var el = document.getElementById('haritaModHint');
        if (!el) {
            return;
        }
        var texts = {};
        texts[MOD.CLUSTER] = 'Kümelere tıklayarak yakınlaşın; işaretçiye tıklayınca hasta kartı açılır.';
        texts[MOD.HEAT] = 'Yoğunluk renkleri yaklaşık dağılımı gösterir. Yakın bölgeye tıklayınca en yakın hasta kartı açılır.';
        texts[MOD.DOTS] = 'Her nokta bir hastadır (cinsiyet rengi). Noktaya tıklayınca kart açılır.';
        texts[MOD.HYBRID] = 'Isı + nokta: önce noktaya tıklayın; yoksa ısı üzerinde yakın konuma tıklayın.';
        el.textContent = texts[mode] || texts[MOD.CLUSTER];
    }

    function applyMapMode(mode) {
        if (mapEngine === 'google') {
            if (mode !== MOD.DOTS && window.toastr) {
                toastr.warning('Google Maps modunda yalnızca nokta görünümü desteklenir.');
            }
            mode = MOD.DOTS;
        }
        if (!mapReady || !map.getSource('route-source')) {
            return;
        }
        var valid = [MOD.CLUSTER, MOD.HEAT, MOD.DOTS, MOD.HYBRID];
        if (valid.indexOf(mode) === -1) {
            mode = MOD.CLUSTER;
        }

        teardownPatient();
        currentMode = mode;
        updateModHint(mode);

        if (mode === MOD.CLUSTER) {
            map.addSource('hasta-source', {
                type: 'geojson',
                data: geoJsonOlustur(gorunenHastalar),
                cluster: true,
                clusterMaxZoom: 14,
                clusterRadius: 50
            });
            insertLayerBeforeRoute({
                id: 'clusters',
                type: 'circle',
                source: 'hasta-source',
                filter: ['has', 'point_count'],
                paint: {
                    'circle-color': ['step', ['get', 'point_count'], '#22c55e', 10, '#3b82f6', 30, '#7c3aed'],
                    'circle-radius': ['step', ['get', 'point_count'], 18, 10, 22, 30, 28],
                    'circle-stroke-width': 2,
                    'circle-stroke-color': '#fff'
                }
            });
            insertLayerBeforeRoute({
                id: 'cluster-count',
                type: 'symbol',
                source: 'hasta-source',
                filter: ['has', 'point_count'],
                layout: { 'text-field': '{point_count_abbreviated}', 'text-size': 12 },
                paint: { 'text-color': '#fff' }
            });
            map.on('moveend', onClusterMoveEnd);
            map.on('data', onClusterData);
            setTimeout(refreshMarkers, 0);
        } else {
            map.addSource('hasta-source', {
                type: 'geojson',
                data: geoJsonOlustur(gorunenHastalar)
            });

            if (mode === MOD.HEAT) {
                insertLayerBeforeRoute(makeHeatmapLayer('full'));
                map.on('click', onMapClickHeatHybrid);
            } else if (mode === MOD.DOTS) {
                insertLayerBeforeRoute(makeDotsLayer('full'));
                map.on('click', 'hasta-dots', onDotsLayerClick);
            } else if (mode === MOD.HYBRID) {
                insertLayerBeforeRoute(makeHeatmapLayer('hybrid'));
                insertLayerBeforeRoute(makeDotsLayer('hybrid'));
                map.on('click', onMapClickHeatHybrid);
            }
        }

        syncMahalleOverlay();
    }

    function getRadioMode() {
        var el = document.querySelector('input[name="eshHaritaMod"]:checked');
        return el ? el.value : MOD.CLUSTER;
    }

    function rotaHesapla(hedef) {
        var routeUrl = String(cfg.routeUrl || '');
        if (!routeUrl) {
            if (window.toastr) {
                toastr.error('Rota servisi yapılandırılmamış.');
            }
            return;
        }

        fetch(routeUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ locations: [merkezNoktasi, hedef] })
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                var geojson = (window.EshMapRuntime && window.EshMapRuntime.routeResponseToGeoJson)
                    ? window.EshMapRuntime.routeResponseToGeoJson(data)
                    : null;
                if (!geojson || !geojson.geometry || !geojson.geometry.coordinates) {
                    throw new Error('Rota yanıtı geçersiz');
                }
                var pts = geojson.geometry.coordinates;
                if (mapEngine === 'google') {
                    mapApi.setGeoJsonData('route-source', geojson);
                } else {
                    map.getSource('route-source').setData(geojson);
                }

                var summary = (data && data.summary) ? data.summary : {};
                var distM = summary.distanceMeters || (data.route && data.route.summary && data.route.summary.lengthInMeters) || 0;
                var mesafe = (distM / 1000).toFixed(1);
                var msg = 'Mesafe: ' + mesafe + ' km. Yol tarifi haritaya eklendi.';
                if (window.toastr) {
                    toastr.success(msg);
                } else {
                    window.alert(msg);
                }

                if (mapEngine === 'google' && mapApi.fitCoords) {
                    mapApi.fitCoords(pts, { padding: 50 });
                } else {
                    var bounds = mapApi.createBounds();
                    pts.forEach(function (p) { bounds.extend(p); });
                    mapApi.fitBounds(bounds, { padding: 50 });
                }
            })
            .catch(function () {
                if (window.toastr) {
                    toastr.error('Rota hesaplanamadı.');
                } else {
                    window.alert('Rota hesaplanamadı.');
                }
            });
    }

    window.ESH_HARITA_ROTA = rotaHesapla;

    function istatistikGuncelle(data) {
        var e = data.filter(function (h) {
            var c = h.cinsiyet ? String(h.cinsiyet).toLowerCase() : '';
            return c === 'e' || c === 'erkek';
        }).length;
        var k = data.filter(function (h) {
            var c = h.cinsiyet ? String(h.cinsiyet).toLowerCase() : '';
            return c === 'k' || c === 'kadın' || c === 'kadin';
        }).length;
        $('#count-erkek').text(e);
        $('#count-kadin').text(k);
        $('#count-all').text(data.length);
    }

    function cinsiyetFiltrele(tip) {
        var res = (tip === 'all') ? tumHastalar : tumHastalar.filter(function (h) {
            var c = h.cinsiyet ? String(h.cinsiyet).toLowerCase() : '';
            return tip === 'erkek' ? (c === 'e' || c === 'erkek') : (c === 'k' || c === 'kadın' || c === 'kadin');
        });
        hastalariKaynagaUygula(res);
        popupKapat();
    }

    map.on('load', function () {
        map.addSource('route-source', { type: 'geojson', data: null });
        map.addLayer({
            id: 'route-layer',
            type: 'line',
            source: 'route-source',
            paint: { 'line-color': '#27ae60', 'line-width': 5, 'line-opacity': 0.85 }
        });

        mapReady = true;
        applyMapMode(getRadioMode());

        if (!dragListenerAttached) {
            dragListenerAttached = true;
            map.on('dragstart', popupKapat);
        }

        istatistikGuncelle(tumHastalar);
    });

    $(document).ready(function () {
        (function initHaritaLegendPanel() {
            var $leg = $('#legend');
            var $btn = $('#eshHaritaLegendToggle');
            if (!$leg.length || !$btn.length) {
                return;
            }
            function applyLegendCollapsed(collapsed) {
                $leg.toggleClass('map-overlay--collapsed', collapsed);
                $btn.attr('aria-expanded', collapsed ? 'false' : 'true');
                $btn.attr('title', collapsed ? 'Filtre panelini aç' : 'Filtre panelini daralt');
                $btn.attr('aria-label', collapsed ? 'Filtre panelini aç' : 'Filtre panelini daralt');
            }
            try {
                if (localStorage.getItem('esh_harita_legend_collapsed') === '1') {
                    applyLegendCollapsed(true);
                }
            } catch (e1) { /* ignore */ }
            $btn.on('click', function () {
                var collapsed = !$leg.hasClass('map-overlay--collapsed');
                applyLegendCollapsed(collapsed);
                try {
                    localStorage.setItem('esh_harita_legend_collapsed', collapsed ? '1' : '0');
                } catch (e2) { /* ignore */ }
            });
        })();

        $('input[name="eshHaritaMod"]').on('change', function () {
            if (mapReady) {
                applyMapMode(this.value);
            }
        });

        $('#eshHaritaMahalleFill').on('change', function () {
            mahalleOverlayWanted = $(this).is(':checked');
            if (mapReady) {
                syncMahalleOverlay();
            }
        });

        $('#btnAra').on('click', function () {
            var v = $('#hastAra').val().toLowerCase().trim();
            var res = tumHastalar.filter(function (h) {
                return String(h.tc).includes(v) || String(h.isim).toLowerCase().includes(v);
            });
            hastalariKaynagaUygula(res);
            popupKapat();
            if (res.length > 0) {
                map.flyTo({ center: [res[0].lng, res[0].lat], zoom: 14 });
            }
        });

        $('#btnSifirla').on('click', function () {
            $('#hastAra').val('');
            hastalariKaynagaUygula(tumHastalar);
            map.getSource('route-source').setData(null);
            map.flyTo({ center: merkezNoktasi, zoom: 11 });
            popupKapat();
        });

        $('[data-filter]').on('click keydown', function (e) {
            if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') {
                return;
            }
            if (e.type === 'keydown') {
                e.preventDefault();
            }
            var tip = $(this).data('filter');
            if (tip === 'erkek') {
                cinsiyetFiltrele('erkek');
            } else if (tip === 'kadin') {
                cinsiyetFiltrele('kadin');
            } else {
                cinsiyetFiltrele('all');
            }
        });
    });
    }
})();
