(function (global) {
    'use strict';

    function routeResponseToGeoJson(resp) {
        if (!resp || !resp.ok) {
            return null;
        }
        if (Array.isArray(resp.geometry) && resp.geometry.length > 0) {
            return {
                type: 'Feature',
                geometry: { type: 'LineString', coordinates: resp.geometry }
            };
        }
        if (resp.route && Array.isArray(resp.route.legs)) {
            var coords = [];
            resp.route.legs.forEach(function (leg) {
                (leg.points || []).forEach(function (p) {
                    coords.push([p.longitude, p.latitude]);
                });
            });
            if (coords.length > 0) {
                return {
                    type: 'Feature',
                    geometry: { type: 'LineString', coordinates: coords }
                };
            }
        }
        return null;
    }

    function fetchMapConfig(url) {
        return fetch(String(url || ''), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); });
    }

    function create(config) {
        config = config || {};
        var configUrl = config.mapConfigUrl || config.mapKeyUrl || '';
        return fetchMapConfig(configUrl).then(function (payload) {
            if (!payload || !payload.ok) {
                throw new Error((payload && payload.error) ? String(payload.error) : 'map_config_failed');
            }
            var mapSdk = String(payload.mapSdk || 'tomtom');
            var adapters = global.EshMapAdapters || {};
            var adapter = adapters[mapSdk] || adapters.tomtom;
            if (!adapter || typeof adapter.create !== 'function') {
                throw new Error('map_adapter_missing');
            }
            var api = adapter.create({
                key: String(payload.key || ''),
                container: config.container,
                center: config.center,
                zoom: config.zoom || 11
            });
            api.provider = String(payload.provider || '');
            api.mapSdk = mapSdk;
            api.routeToGeoJson = routeResponseToGeoJson;
            return api;
        });
    }

    global.EshMapRuntime = {
        create: create,
        routeResponseToGeoJson: routeResponseToGeoJson
    };
})(window);
