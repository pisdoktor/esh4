(function (global) {
    'use strict';

    function boot(config) {
        var center = { lat: config.center[1], lng: config.center[0] };
        var map = new global.google.maps.Map(document.getElementById(config.container), {
            center: center,
            zoom: config.zoom || 11,
            mapTypeControl: true,
            streetViewControl: false
        });

        var routeLine = null;
        var markers = [];

        return {
            engine: 'google',
            raw: map,
            boundsClass: global.google.maps.LatLngBounds,
            onLoad: function (fn) {
                global.google.maps.event.addListenerOnce(map, 'idle', fn);
            },
            addNavigationControl: function () { /* native UI */ },
            addFullscreenControl: function () { /* native UI */ },
            addMarker: function (lngLat, options) {
                options = options || {};
                var m = new global.google.maps.Marker({
                    map: map,
                    position: { lat: lngLat[1], lng: lngLat[0] },
                    title: options.title || ''
                });
                if (options.popupHtml) {
                    var iw = new global.google.maps.InfoWindow({ content: options.popupHtml });
                    m.addListener('click', function () { iw.open({ anchor: m, map: map }); });
                }
                markers.push(m);
                return m;
            },
            addPopup: function (lngLat, html) {
                var iw = new global.google.maps.InfoWindow({ content: html });
                iw.setPosition({ lat: lngLat[1], lng: lngLat[0] });
                iw.open(map);
                return iw;
            },
            addGeoJsonSource: function () { /* google uses polyline */ },
            setGeoJsonData: function (id, data) {
                if (id !== 'route-source' || !data || !data.geometry) {
                    return;
                }
                var path = (data.geometry.coordinates || []).map(function (c) {
                    return { lat: c[1], lng: c[0] };
                });
                if (routeLine) {
                    routeLine.setMap(null);
                }
                routeLine = new global.google.maps.Polyline({
                    path: path,
                    geodesic: true,
                    strokeColor: '#27ae60',
                    strokeOpacity: 0.85,
                    strokeWeight: 5,
                    map: map
                });
            },
            addLineLayer: function () { /* handled via setGeoJsonData */ },
            addLayer: function () { /* not supported on google simplified mode */ },
            getLayer: function () { return null; },
            getSource: function () { return null; },
            removeLayer: function () {},
            removeSource: function () {},
            setLayoutProperty: function () {},
            fitBounds: function (bounds, options) {
                map.fitBounds(bounds, options || { padding: 50 });
            },
            resize: function () {
                global.google.maps.event.trigger(map, 'resize');
            },
            on: function (evt, target, fn) {
                if (evt === 'load') {
                    this.onLoad(typeof target === 'function' ? target : fn);
                    return;
                }
                if (evt === 'dragstart') {
                    map.addListener('dragstart', typeof target === 'function' ? target : fn);
                }
            },
            createBounds: function () { return new global.google.maps.LatLngBounds(); },
            fitCoords: function (coords, options) {
                var b = new global.google.maps.LatLngBounds();
                coords.forEach(function (c) {
                    b.extend({ lat: c[1], lng: c[0] });
                });
                map.fitBounds(b, options || { padding: 50 });
            }
        };
    }

    global.EshMapAdapters = global.EshMapAdapters || {};
    global.EshMapAdapters.google = { create: boot };
})(window);
