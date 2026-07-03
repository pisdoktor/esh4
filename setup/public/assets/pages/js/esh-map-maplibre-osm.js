(function (global) {
    'use strict';

    function boot(config) {
        var map = new global.maplibregl.Map({
            container: config.container,
            style: {
                version: 8,
                sources: {
                    'osm-raster': {
                        type: 'raster',
                        tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                        tileSize: 256,
                        attribution: '&copy; OpenStreetMap contributors'
                    }
                },
                layers: [{
                    id: 'osm-raster-layer',
                    type: 'raster',
                    source: 'osm-raster'
                }]
            },
            center: config.center,
            zoom: config.zoom || 11
        });

        return {
            engine: 'maplibre_like',
            raw: map,
            boundsClass: global.maplibregl.LngLatBounds,
            onLoad: function (fn) {
                if (map.loaded()) {
                    fn();
                } else {
                    map.on('load', fn);
                }
            },
            addNavigationControl: function (position) {
                map.addControl(new global.maplibregl.NavigationControl(), position || 'top-right');
            },
            addFullscreenControl: function () {
                map.addControl(new global.maplibregl.FullscreenControl());
            },
            addMarker: function (lngLat, options) {
                options = options || {};
                var el = document.createElement('div');
                el.style.width = '18px';
                el.style.height = '18px';
                el.style.borderRadius = '50%';
                el.style.background = options.color || '#3388ff';
                el.style.border = '2px solid #fff';
                var m = new global.maplibregl.Marker({ element: el, anchor: options.anchor || 'bottom' }).setLngLat(lngLat);
                if (options.popupHtml) {
                    m.setPopup(new global.maplibregl.Popup({ offset: options.popupOffset || 30, maxWidth: options.maxWidth || '320px' }).setHTML(options.popupHtml));
                }
                m.addTo(map);
                return m;
            },
            addPopup: function (lngLat, html, options) {
                options = options || {};
                return new global.maplibregl.Popup({ offset: options.offset || 18, maxWidth: options.maxWidth || '320px' })
                    .setLngLat(lngLat)
                    .setHTML(html)
                    .addTo(map);
            },
            addGeoJsonSource: function (id, data) {
                if (!map.getSource(id)) {
                    map.addSource(id, { type: 'geojson', data: data });
                }
            },
            setGeoJsonData: function (id, data) {
                var src = map.getSource(id);
                if (src) {
                    src.setData(data);
                }
            },
            addLineLayer: function (layerId, sourceId, paint, layout, beforeId) {
                if (map.getLayer(layerId)) {
                    return;
                }
                var def = {
                    id: layerId,
                    type: 'line',
                    source: sourceId,
                    paint: paint || { 'line-color': '#27ae60', 'line-width': 5, 'line-opacity': 0.85 },
                    layout: layout || {}
                };
                if (beforeId) {
                    map.addLayer(def, beforeId);
                } else {
                    map.addLayer(def);
                }
            },
            addLayer: function (def, beforeId) {
                if (beforeId) {
                    map.addLayer(def, beforeId);
                } else {
                    map.addLayer(def);
                }
            },
            getLayer: function (id) { return map.getLayer(id); },
            getSource: function (id) { return map.getSource(id); },
            removeLayer: function (id) { if (map.getLayer(id)) { map.removeLayer(id); } },
            removeSource: function (id) { if (map.getSource(id)) { map.removeSource(id); } },
            setLayoutProperty: function (layerId, prop, val) { map.setLayoutProperty(layerId, prop, val); },
            fitBounds: function (bounds, options) { map.fitBounds(bounds, options || { padding: 50 }); },
            resize: function () { map.resize(); },
            on: function (evt, target, fn) {
                if (typeof target === 'function') {
                    map.on(evt, target);
                } else {
                    map.on(evt, target, fn);
                }
            },
            createBounds: function () { return new global.maplibregl.LngLatBounds(); }
        };
    }

    global.EshMapAdapters = global.EshMapAdapters || {};
    global.EshMapAdapters.maplibre_osm = { create: boot };
})(window);
