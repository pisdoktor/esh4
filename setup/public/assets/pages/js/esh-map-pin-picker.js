(function (global) {
    'use strict';

    function parseCoords(str) {
        if (!str) {
            return null;
        }
        var parts = String(str).replace(/\s/g, '').split(',');
        if (parts.length !== 2) {
            return null;
        }
        var lat = parseFloat(parts[0]);
        var lng = parseFloat(parts[1]);
        if (!isFinite(lat) || !isFinite(lng)) {
            return null;
        }
        return { lat: lat, lng: lng, lngLat: [lng, lat] };
    }

    function formatCoords(lat, lng) {
        return lat.toFixed(6) + ',' + lng.toFixed(6);
    }

    function createMarkerElement(className) {
        var el = document.createElement('div');
        el.className = 'esh-mk-pin ' + className;
        return el;
    }

    function createPinPicker(mapApi, options) {
        options = options || {};
        var map = mapApi.raw;
        var engine = mapApi.engine || 'maplibre_like';
        var mapSdk = String(mapApi.mapSdk || '');
        var useTomTomNative = (mapSdk === 'tomtom' || (global.tt && global.tt.Marker && !global.mapboxgl))
            && global.tt && global.tt.Marker;
        var primaryMarker = null;
        var ghostMarker = null;
        var clickHandler = null;
        var googleClickListener = null;
        var mapReady = false;
        var pending = [];

        function whenReady(fn) {
            if (mapReady) {
                fn();
            } else {
                pending.push(fn);
            }
        }

        function flushPending() {
            if (mapReady) {
                return;
            }
            mapReady = true;
            var queue = pending.slice();
            pending = [];
            queue.forEach(function (fn) {
                fn();
            });
        }

        function markReady() {
            flushPending();
        }

        if (typeof mapApi.onLoad === 'function') {
            mapApi.onLoad(markReady);
        } else if (map && typeof map.once === 'function') {
            if (typeof map.loaded === 'function' && map.loaded()) {
                markReady();
            } else {
                map.once('load', markReady);
            }
        } else {
            markReady();
        }
        if (map && typeof map.on === 'function') {
            map.on('idle', markReady);
        }
        setTimeout(markReady, 50);
        setTimeout(markReady, 400);

        function notifyChange(lat, lng) {
            if (typeof options.onChange === 'function') {
                options.onChange(formatCoords(lat, lng), lat, lng);
            }
        }

        function getMaplibreMarkerLib() {
            if (global.mapboxgl && global.mapboxgl.Marker) {
                return global.mapboxgl.Marker;
            }
            if (global.maplibregl && global.maplibregl.Marker) {
                return global.maplibregl.Marker;
            }
            if (global.tt && global.tt.Marker && !useTomTomNative) {
                return global.tt.Marker;
            }
            return null;
        }

        function placeTomTomMarker(lng, lat, color, draggable) {
            var m;
            if (typeof mapApi.addMarker === 'function') {
                m = mapApi.addMarker([lng, lat], { color: color, anchor: 'bottom' });
            } else {
                m = new global.tt.Marker({
                    color: color,
                    anchor: 'bottom'
                }).setLngLat([lng, lat]).addTo(map);
            }
            if (draggable && m) {
                if (typeof m.setDraggable === 'function') {
                    m.setDraggable(true);
                }
                m.on('dragend', function () {
                    var ll = m.getLngLat();
                    notifyChange(ll.lat, ll.lng);
                });
            }
            return m;
        }

        function placeMaplibreMarker(Marker, lng, lat, className, draggable) {
            var el = createMarkerElement(className);
            var opts = { element: el, anchor: 'center' };
            if (draggable) {
                opts.draggable = true;
            }
            var m = new Marker(opts).setLngLat([lng, lat]).addTo(map);
            if (draggable) {
                m.on('dragend', function () {
                    var ll = m.getLngLat();
                    notifyChange(ll.lat, ll.lng);
                });
            }
            return m;
        }

        function placeGoogleMarker(lat, lng, draggable, ghost) {
            var m = new global.google.maps.Marker({
                map: map,
                position: { lat: lat, lng: lng },
                draggable: !!draggable,
                icon: {
                    path: global.google.maps.SymbolPath.CIRCLE,
                    scale: ghost ? 8 : 10,
                    fillColor: ghost ? '#6c757d' : '#0d6efd',
                    fillOpacity: ghost ? 0.85 : 1,
                    strokeWeight: 2,
                    strokeColor: '#fff'
                }
            });
            if (draggable) {
                m.addListener('dragend', function () {
                    var p = m.getPosition();
                    if (p) {
                        notifyChange(p.lat(), p.lng());
                    }
                });
            }
            return m;
        }

        function removeMaplibreMarker(marker) {
            if (marker && typeof marker.remove === 'function') {
                marker.remove();
            }
        }

        function removeGoogleMarker(marker) {
            if (marker && typeof marker.setMap === 'function') {
                marker.setMap(null);
            }
        }

        function clearPrimary() {
            if (engine === 'google') {
                removeGoogleMarker(primaryMarker);
            } else {
                removeMaplibreMarker(primaryMarker);
            }
            primaryMarker = null;
        }

        function clearGhost() {
            if (engine === 'google') {
                removeGoogleMarker(ghostMarker);
            } else {
                removeMaplibreMarker(ghostMarker);
            }
            ghostMarker = null;
        }

        function flyTo(lat, lng, zoom) {
            var z = zoom || 16;
            var center = [lng, lat];
            if (engine === 'google') {
                map.panTo({ lat: lat, lng: lng });
                if (typeof map.setZoom === 'function') {
                    map.setZoom(z);
                }
                return;
            }
            if (typeof map.flyTo === 'function') {
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
            }
            if (typeof mapApi.resize === 'function') {
                setTimeout(function () { mapApi.resize(); }, 200);
            }
        }

        function setPrimaryImpl(lat, lng, flyOpts) {
            flyOpts = flyOpts || {};
            clearPrimary();
            if (engine === 'google') {
                primaryMarker = placeGoogleMarker(lat, lng, true, false);
            } else if (useTomTomNative) {
                primaryMarker = placeTomTomMarker(lng, lat, '#0d6efd', true);
            } else {
                var Marker = getMaplibreMarkerLib();
                if (!Marker) {
                    return;
                }
                primaryMarker = placeMaplibreMarker(Marker, lng, lat, 'esh-mk-pin--primary', true);
            }
            if (flyOpts.flyTo !== false) {
                flyTo(lat, lng, flyOpts.zoom);
            }
            if (flyOpts.silent !== true) {
                notifyChange(lat, lng);
            }
        }

        function setGhostImpl(lat, lng) {
            clearGhost();
            if (engine === 'google') {
                ghostMarker = placeGoogleMarker(lat, lng, false, true);
            } else if (useTomTomNative) {
                ghostMarker = placeTomTomMarker(lng, lat, '#6c757d', false);
            } else {
                var Marker = getMaplibreMarkerLib();
                if (!Marker) {
                    return;
                }
                ghostMarker = placeMaplibreMarker(Marker, lng, lat, 'esh-mk-pin--ghost', false);
            }
        }

        function setPrimary(lat, lng, flyOpts) {
            if (mapReady) {
                setPrimaryImpl(lat, lng, flyOpts);
                return;
            }
            whenReady(function () {
                setPrimaryImpl(lat, lng, flyOpts);
            });
        }

        function setPrimaryFromCoordsImpl(str, flyOpts) {
            var p = parseCoords(str);
            if (!p) {
                return false;
            }
            setPrimary(p.lat, p.lng, flyOpts);
            return true;
        }

        function setGhost(lat, lng) {
            whenReady(function () {
                setGhostImpl(lat, lng);
            });
        }

        function bindClickImpl() {
            if (engine === 'google') {
                googleClickListener = map.addListener('click', function (e) {
                    if (!e || !e.latLng) {
                        return;
                    }
                    setPrimaryImpl(e.latLng.lat(), e.latLng.lng());
                });
                return;
            }
            clickHandler = function (e) {
                if (!e || !e.lngLat) {
                    return;
                }
                setPrimaryImpl(e.lngLat.lat, e.lngLat.lng);
            };
            map.on('click', clickHandler);
        }

        function bindClick() {
            whenReady(bindClickImpl);
        }

        function destroy() {
            clearPrimary();
            clearGhost();
            pending = [];
            if (engine === 'google') {
                if (googleClickListener && global.google && global.google.maps && global.google.maps.event) {
                    global.google.maps.event.removeListener(googleClickListener);
                }
            } else if (clickHandler && typeof map.off === 'function') {
                map.off('click', clickHandler);
            }
        }

        return {
            parseCoords: parseCoords,
            formatCoords: formatCoords,
            setPrimary: setPrimary,
            setPrimaryFromCoords: function (str, flyOpts) {
                return setPrimaryFromCoordsImpl(str, flyOpts);
            },
            placePrimaryNow: function (str, flyOpts) {
                return setPrimaryFromCoordsImpl(str, flyOpts);
            },
            setGhost: setGhost,
            setGhostFromCoords: function (str) {
                var p = parseCoords(str);
                if (p) {
                    setGhost(p.lat, p.lng);
                }
            },
            clearGhost: function () {
                whenReady(clearGhost);
            },
            flyTo: function (lat, lng, zoom) {
                whenReady(function () {
                    flyTo(lat, lng, zoom);
                });
            },
            bindClick: bindClick,
            destroy: destroy,
            whenReady: whenReady,
            ensureReady: markReady
        };
    }

    global.EshMapPinPicker = { create: createPinPicker };
})(window);
