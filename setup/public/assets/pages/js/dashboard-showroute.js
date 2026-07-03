(function ($) {
    'use strict';

    var cfg = (window.ESH_PAGE && window.ESH_PAGE.showRoute) ? window.ESH_PAGE.showRoute : null;
    if (!cfg) {
        return;
    }

    function routeToGeoJson(resp) {
        if (window.EshMapRuntime && typeof window.EshMapRuntime.routeResponseToGeoJson === 'function') {
            return window.EshMapRuntime.routeResponseToGeoJson(resp);
        }
        var coords = [];
        if (resp && resp.geometry) {
            coords = resp.geometry;
        } else if (resp && resp.route && resp.route.legs) {
            resp.route.legs.forEach(function (leg) {
                (leg.points || []).forEach(function (p) {
                    coords.push([p.longitude, p.latitude]);
                });
            });
        }
        return {
            type: 'Feature',
            geometry: { type: 'LineString', coordinates: coords }
        };
    }

    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    $(document).ready(function () {
        $('#routeDatePicker').on('changeDate', function (e) {
            var date = e.date;
            var year = date.getFullYear();
            var month = ('0' + (date.getMonth() + 1)).slice(-2);
            var day = ('0' + date.getDate()).slice(-2);
            var formattedDate = year + '-' + month + '-' + day;
            var base = String(cfg.showRouteBaseUrl || '');
            var sep = base.indexOf('?') >= 0 ? '&' : '?';
            location.href = base + sep + 'date=' + encodeURIComponent(formattedDate);
        });
    });

    $(document).ready(function () {
        var dynamicEl = document.getElementById('esh-route-dynamic');
        var fetchUrl = dynamicEl ? dynamicEl.getAttribute('data-esh-fetch-url') : '';
        var mapBootstrapped = false;
        var routeLayers = {};
        var markers = [];
        var mapApi = null;
        var map = null;

        function updateMapVisibility(activeVardiyaId) {
            if (!map || !mapApi) {
                return;
            }
            var merkez = cfg.merkez || {};
            Object.keys(routeLayers).forEach(function (vKey) {
                routeLayers[vKey].forEach(function (lId) {
                    mapApi.setLayoutProperty(lId, 'visibility', (String(vKey) === String(activeVardiyaId) ? 'visible' : 'none'));
                });
            });

            var activeBounds = mapApi.createBounds();
            var hasActivePoint = false;

            markers.forEach(function (m) {
                var el = m.getElement ? m.getElement() : null;
                if (String(m.vardiyaId) === String(activeVardiyaId)) {
                    if (el) {
                        el.style.display = 'block';
                    }
                    if (m.getLngLat) {
                        activeBounds.extend(m.getLngLat());
                    }
                    hasActivePoint = true;
                } else if (el) {
                    el.style.display = 'none';
                }
            });

            if (hasActivePoint) {
                activeBounds.extend([parseFloat(merkez.lon), parseFloat(merkez.lat)]);
                mapApi.fitBounds(activeBounds, { padding: 50, duration: 1000 });
            }
        }

        function initMapWithApi(api) {
            mapApi = api;
            map = api.raw;
            var allData = cfg.allData || {};
            var merkez = cfg.merkez || {};

            api.addNavigationControl();

            api.addMarker([parseFloat(merkez.lon), parseFloat(merkez.lat)], {
                color: '#2c3e50',
                anchor: 'bottom',
                popupHtml: escapeHtml(cfg.startName || 'Merkez')
            });

            api.onLoad(function () {
                var colors = ['#f39c12', '#3498db', '#e74c3c', '#2ecc71', '#9b59b6'];
                var colorIdx = 0;

                Object.keys(allData).forEach(function (vKey) {
                    var ekipler = allData[vKey];
                    routeLayers[vKey] = [];

                    Object.keys(ekipler).forEach(function (eKey) {
                        var ekip = ekipler[eKey];
                        var rColor = colors[colorIdx % colors.length];
                        colorIdx++;

                        var points = [[parseFloat(merkez.lon), parseFloat(merkez.lat)]];

                        (ekip.hastalar || []).forEach(function (h, index) {
                            var c = String(h.coords || '').replace(/\s/g, '').split(',');
                            var lat = parseFloat(c[0]);
                            var lon = parseFloat(c[1]);
                            if (isNaN(lat) || isNaN(lon)) {
                                return;
                            }

                            points.push([lon, lat]);

                            var patientUrl = typeof esh_url === 'function'
                                ? esh_url('Patient', 'view', { id: h.id })
                                : '#';
                            var popupHtml = '<div class="text-start" style="min-width: 200px;">'
                                + '<div class="border-bottom mb-2 pb-1 text-center">'
                                + '<strong>' + (index + 1) + '. Durak: <a href="' + escapeHtml(patientUrl) + '" target="_blank" rel="noopener" style="text-decoration: none; color: #0d6efd;">'
                                + escapeHtml(h.isim) + ' ' + escapeHtml(h.soyisim)
                                + ' <i class="fa fa-external-link-alt" style="font-size: 10px;"></i></a></strong>'
                                + '</div><div style="font-size: 12px;">'
                                + '<p class="mb-1"><strong>Tahmini Varış:</strong> <span class="badge bg-primary">' + escapeHtml(h.varis_saati) + '</span></p>'
                                + '<hr class="my-1">'
                                + '<p class="mb-1"><strong>Son İzlem:</strong> ' + escapeHtml(h.son_izlem || 'Kayıt Yok') + '</p>'
                                + '<p class="mb-0 text-muted" style="font-size: 11px;"><strong>Yapılan:</strong> ' + escapeHtml(h.son_yapilan || 'Bilgi Yok') + '</p>'
                                + '</div></div>';

                            var marker = api.addMarker([lon, lat], {
                                color: rColor,
                                popupHtml: popupHtml,
                                popupOffset: 30
                            });
                            marker.vardiyaId = vKey;
                            markers.push(marker);
                        });

                        if (points.length > 1) {
                            fetch(String(cfg.routeUrl || ''), {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ locations: points })
                            })
                                .then(function (r) { return r.json(); })
                                .then(function (resp) {
                                    var geojson = routeToGeoJson(resp);
                                    if (!geojson || !geojson.geometry || !geojson.geometry.coordinates || geojson.geometry.coordinates.length < 2) {
                                        return;
                                    }
                                    var layerId = 'route_' + vKey + '_' + eKey;
                                    if (api.engine === 'google') {
                                        api.setGeoJsonData('route-source', geojson);
                                        routeLayers[vKey].push(layerId);
                                    } else {
                                        map.addLayer({
                                            id: layerId,
                                            type: 'line',
                                            source: { type: 'geojson', data: geojson },
                                            paint: { 'line-color': rColor, 'line-width': 5, 'line-opacity': 0.8 },
                                            layout: { visibility: 'none' }
                                        });
                                        routeLayers[vKey].push(layerId);
                                    }
                                    if (String(vKey) === '0') {
                                        updateMapVisibility('0');
                                    }
                                })
                                .catch(function () { /* rota çizilemedi */ });
                        }
                    });
                });
            });
        }

        function startMap() {
            if (mapBootstrapped || !window.EshMapRuntime) {
                return;
            }
            mapBootstrapped = true;
            var merkez = cfg.merkez || {};
            window.EshMapRuntime.create({
                mapConfigUrl: cfg.mapConfigUrl || cfg.mapKeyUrl,
                container: 'map',
                center: [parseFloat(merkez.lon), parseFloat(merkez.lat)],
                zoom: 11
            }).then(function (api) {
                initMapWithApi(api);
            }).catch(function () { /* harita yüklenemedi */ });
        }

        function applyRoutePayload(data) {
            if (!dynamicEl || !data) {
                return;
            }
            if (typeof data.html === 'string') {
                dynamicEl.innerHTML = data.html;
            }
            if (data.tarih_basligi) {
                var badge = document.getElementById('esh-route-date-badge');
                if (badge) {
                    badge.innerHTML = '<i class="fa fa-calendar-day me-2"></i>' + escapeHtml(String(data.tarih_basligi));
                }
            }
            cfg.allData = data.allData || {};
            cfg.merkez = data.merkez || {};
            startMap();
        }

        $(document).on('shown.bs.tab', '#esh-route-dynamic button[data-bs-toggle="tab"]', function (event) {
            if (!map || !mapApi) {
                return;
            }
            mapApi.resize();
            var targetId = event.target.getAttribute('data-bs-target') || '';
            var vKey = '0';
            if (targetId.indexOf('ogle') >= 0) {
                vKey = '1';
            }
            if (targetId.indexOf('aksam') >= 0) {
                vKey = '2';
            }
            updateMapVisibility(vKey);
        });

        if (!fetchUrl) {
            startMap();
            return;
        }

        fetch(fetchUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            if (!r.ok) {
                throw new Error('Rota yüklenemedi (' + r.status + ').');
            }
            return r.json();
        }).then(function (data) {
            if (!data || !data.ok) {
                throw new Error((data && data.error) ? String(data.error) : 'Geçersiz yanıt');
            }
            applyRoutePayload(data);
        }).catch(function (err) {
            if (dynamicEl) {
                dynamicEl.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(err.message || 'Rota yüklenemedi') + '</div>';
            }
        });
    });
})(window.jQuery);
