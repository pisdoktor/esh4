/**
 * Saha izlem GPS check-in — paylaşılan modül (create + edit).
 * window.ESH_PAGE.fieldVisit: { mode, offlinePlanCache }
 */
(function (global) {
    'use strict';

    var STATUS = {
        idle: 'idle',
        pending: 'pending',
        ok: 'ok',
        denied: 'denied',
        unavailable: 'unavailable',
        skipped: 'skipped',
    };

    function cfg() {
        var page = global.ESH_PAGE && global.ESH_PAGE.fieldVisit ? global.ESH_PAGE.fieldVisit : {};
        var mode = String(page.mode || 'recommended');
        if (['optional', 'recommended', 'required_completed'].indexOf(mode) < 0) {
            mode = 'recommended';
        }
        return {
            mode: mode,
            geofenceRadiusM: parseInt(page.geofenceRadiusM, 10) || 0,
            minGpsAccuracyM: parseInt(page.minGpsAccuracyM, 10) || 0,
            patientLat: typeof page.patientLat === 'number' ? page.patientLat : null,
            patientLon: typeof page.patientLon === 'number' ? page.patientLon : null,
        };
    }

    function haversineMeters(lat1, lon1, lat2, lon2) {
        var R = 6371000;
        var dLat = ((lat2 - lat1) * Math.PI) / 180;
        var dLon = ((lon2 - lon1) * Math.PI) / 180;
        var a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos((lat1 * Math.PI) / 180) *
                Math.cos((lat2 * Math.PI) / 180) *
                Math.sin(dLon / 2) *
                Math.sin(dLon / 2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function geofenceEl(form) {
        return form ? form.querySelector('[data-esh-visit-geofence-warning]') : null;
    }

    function updateGeofenceWarning(form, coords) {
        var el = geofenceEl(form);
        if (!el || !coords) {
            if (el) {
                el.classList.add('d-none');
                el.innerHTML = '';
            }
            return;
        }
        var c = cfg();
        if (!c.patientLat || !c.patientLon || c.geofenceRadiusM <= 0) {
            el.classList.add('d-none');
            el.innerHTML = '';
            return;
        }
        var dist = haversineMeters(coords.lat, coords.lon, c.patientLat, c.patientLon);
        if (dist <= c.geofenceRadiusM) {
            el.classList.add('d-none');
            el.innerHTML = '';
            return;
        }
        el.classList.remove('d-none');
        el.innerHTML =
            '<i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>Hasta adresinden yaklaşık ' +
            Math.round(dist) +
            ' m uzaktasınız (eşik: ' +
            c.geofenceRadiusM +
            ' m). Kayıt yine de gönderilebilir.';
    }

    function statusEl(form) {
        if (!form) {
            return null;
        }
        return form.querySelector('[data-esh-visit-checkin-status]');
    }

    function setStatus(form, state, message) {
        var el = statusEl(form);
        if (!el) {
            return;
        }
        el.dataset.state = state;
        el.classList.remove('d-none', 'alert-secondary', 'alert-success', 'alert-warning', 'alert-danger', 'alert-info');
        var map = {
            idle: 'alert-secondary',
            pending: 'alert-info',
            ok: 'alert-success',
            denied: 'alert-warning',
            unavailable: 'alert-warning',
            skipped: 'alert-secondary',
        };
        el.classList.add(map[state] || 'alert-secondary');
        el.innerHTML = message;
    }

    function readCoords(form) {
        var latInput = form.querySelector('input[name="checkin_lat"]');
        var lonInput = form.querySelector('input[name="checkin_lon"]');
        if (!latInput || !lonInput) {
            return null;
        }
        var lat = parseFloat(latInput.value);
        var lon = parseFloat(lonInput.value);
        if (!isFinite(lat) || !isFinite(lon)) {
            return null;
        }
        return { lat: lat, lon: lon };
    }

    function writeCoords(form, lat, lon, accuracy) {
        var latInput = form.querySelector('input[name="checkin_lat"]');
        var lonInput = form.querySelector('input[name="checkin_lon"]');
        var atInput = form.querySelector('input[name="checkin_at"]');
        var accInput = form.querySelector('input[name="checkin_accuracy"]');
        if (latInput) {
            latInput.value = String(lat);
        }
        if (lonInput) {
            lonInput.value = String(lon);
        }
        if (atInput) {
            atInput.value = new Date().toISOString();
        }
        if (accInput) {
            accInput.value =
                typeof accuracy === 'number' && isFinite(accuracy) && accuracy > 0
                    ? String(Math.round(accuracy * 10) / 10)
                    : '';
        }
        updateGeofenceWarning(form, { lat: lat, lon: lon });
    }

    function clearCoords(form) {
        var latInput = form.querySelector('input[name="checkin_lat"]');
        var lonInput = form.querySelector('input[name="checkin_lon"]');
        var atInput = form.querySelector('input[name="checkin_at"]');
        var accInput = form.querySelector('input[name="checkin_accuracy"]');
        if (latInput) {
            latInput.value = '';
        }
        if (lonInput) {
            lonInput.value = '';
        }
        if (atInput) {
            atInput.value = '';
        }
        if (accInput) {
            accInput.value = '';
        }
        updateGeofenceWarning(form, null);
    }

    function isYapildi(form) {
        var v = form.querySelector('input[name="yapildimi"]:checked');
        return !!(v && v.value === '1');
    }

    function requiresCheckin(form) {
        var mode = cfg().mode;
        if (mode === 'required_completed') {
            return isYapildi(form);
        }
        return false;
    }

    function captureGeolocation(form) {
        return new Promise(function (resolve) {
            if (!form) {
                resolve({ ok: false, reason: 'no_form' });
                return;
            }
            var existing = readCoords(form);
            if (existing) {
                setStatus(
                    form,
                    STATUS.ok,
                    '<i class="fa-solid fa-location-dot me-2" aria-hidden="true"></i>Konum alındı (' +
                        existing.lat.toFixed(5) + ', ' + existing.lon.toFixed(5) + ').'
                );
                updateGeofenceWarning(form, existing);
                resolve({ ok: true, coords: existing, cached: true });
                return;
            }
            if (!global.navigator || typeof global.navigator.geolocation.getCurrentPosition !== 'function') {
                setStatus(
                    form,
                    STATUS.unavailable,
                    '<i class="fa-solid fa-triangle-exclamation me-2" aria-hidden="true"></i>Cihaz konum servisi kullanılamıyor.'
                );
                resolve({ ok: false, reason: 'unavailable' });
                return;
            }
            setStatus(
                form,
                STATUS.pending,
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Konum alınıyor…'
            );
            var finished = false;
            function done(result) {
                if (finished) {
                    return;
                }
                finished = true;
                resolve(result);
            }
            var timer = global.setTimeout(function () {
                setStatus(
                    form,
                    STATUS.unavailable,
                    '<i class="fa-solid fa-clock me-2" aria-hidden="true"></i>Konum zaman aşımı. Tekrar denemek için «Konumu al» düğmesine basın.'
                );
                done({ ok: false, reason: 'timeout' });
            }, 8000);
            global.navigator.geolocation.getCurrentPosition(
                function (pos) {
                    global.clearTimeout(timer);
                    if (!pos || !pos.coords) {
                        setStatus(form, STATUS.unavailable, 'Konum alınamadı.');
                        done({ ok: false, reason: 'empty' });
                        return;
                    }
                    writeCoords(form, pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy);
                    var accNote =
                        typeof pos.coords.accuracy === 'number' && isFinite(pos.coords.accuracy)
                            ? ' (±' + Math.round(pos.coords.accuracy) + ' m)'
                            : '';
                    setStatus(
                        form,
                        STATUS.ok,
                        '<i class="fa-solid fa-location-dot me-2" aria-hidden="true"></i>Konum kayda eklenecek (' +
                            pos.coords.latitude.toFixed(5) + ', ' + pos.coords.longitude.toFixed(5) + ')' + accNote + '.'
                    );
                    done({
                        ok: true,
                        coords: {
                            lat: pos.coords.latitude,
                            lon: pos.coords.longitude,
                            accuracy: pos.coords.accuracy,
                        },
                    });
                },
                function () {
                    global.clearTimeout(timer);
                    setStatus(
                        form,
                        STATUS.denied,
                        '<i class="fa-solid fa-location-crosshairs me-2" aria-hidden="true"></i>Konum izni verilmedi. Tarayıcı ayarlarından izin verip «Konumu al» ile tekrar deneyin.'
                    );
                    done({ ok: false, reason: 'denied' });
                },
                { enableHighAccuracy: true, timeout: 7500, maximumAge: 30000 }
            );
        });
    }

    function bindRefreshButton(form) {
        var btn = form.querySelector('[data-esh-visit-checkin-refresh]');
        if (!btn) {
            return;
        }
        btn.addEventListener('click', function (ev) {
            ev.preventDefault();
            clearCoords(form);
            captureGeolocation(form);
        });
    }

    function bindYapildimiWatcher(form) {
        form.querySelectorAll('input[name="yapildimi"]').forEach(function (r) {
            r.addEventListener('change', function () {
                syncHint(form);
            });
        });
        syncHint(form);
    }

    function syncHint(form) {
        var hint = form.querySelector('[data-esh-visit-checkin-hint]');
        if (!hint) {
            return;
        }
        var mode = cfg().mode;
        if (mode === 'optional') {
            hint.textContent = 'Konum isteğe bağlıdır; kayıt sırasında alınır.';
            return;
        }
        if (mode === 'required_completed' && isYapildi(form)) {
            hint.textContent = 'Yapıldı kayıtlar için konum zorunludur.';
            hint.classList.add('text-danger', 'fw-semibold');
            return;
        }
        hint.classList.remove('text-danger', 'fw-semibold');
        hint.textContent = 'Konum önerilir; izin verilmezse kayıt yine de gönderilebilir.';
    }

    function initForm(form, options) {
        if (!form) {
            return;
        }
        options = options || {};
        bindRefreshButton(form);
        bindYapildimiWatcher(form);
        if (readCoords(form)) {
            var c = readCoords(form);
            setStatus(
                form,
                STATUS.ok,
                '<i class="fa-solid fa-location-dot me-2" aria-hidden="true"></i>Mevcut konum: ' +
                    c.lat.toFixed(5) + ', ' + c.lon.toFixed(5)
            );
            updateGeofenceWarning(form, c);
        } else {
            setStatus(
                form,
                STATUS.idle,
                '<i class="fa-solid fa-location-crosshairs me-2" aria-hidden="true"></i>Kaydetmeden önce konum alınır. «Konumu al» ile şimdi deneyebilirsiniz.'
            );
        }
        if (options.autoCaptureOnLoad) {
            captureGeolocation(form);
        }
    }

    /**
     * Form gönderiminden önce konum al; zorunlu modda başarısızsa reject.
     * @returns {Promise<{ok:boolean,reason?:string}>}
     */
    function ensureBeforeSubmit(form) {
        return captureGeolocation(form).then(function (res) {
            if (res.ok) {
                var c = cfg();
                if (
                    requiresCheckin(form) &&
                    c.minGpsAccuracyM > 0 &&
                    res.coords &&
                    typeof res.coords.accuracy === 'number' &&
                    res.coords.accuracy > c.minGpsAccuracyM
                ) {
                    if (global.toastr && global.toastr.error) {
                        global.toastr.error(
                            'GPS doğruluğu yetersiz (±' +
                                Math.round(res.coords.accuracy) +
                                ' m). Daha iyi sinyal alıp tekrar deneyin.'
                        );
                    }
                    return { ok: false, reason: 'accuracy' };
                }
                return { ok: true };
            }
            if (requiresCheckin(form)) {
                if (global.toastr && global.toastr.error) {
                    global.toastr.error('Yapıldı izlemler için saha konumu (GPS) zorunludur.');
                }
                return { ok: false, reason: res.reason || 'required' };
            }
            if (cfg().mode === 'recommended' && !res.ok) {
                if (global.toastr && global.toastr.warning) {
                    global.toastr.warning('Konum alınamadı; kayıt konumsuz gönderiliyor.');
                }
            }
            return { ok: true, skipped: true };
        });
    }

    global.eshVisitGeo = {
        initForm: initForm,
        ensureBeforeSubmit: ensureBeforeSubmit,
        captureGeolocation: captureGeolocation,
        requiresCheckin: requiresCheckin,
        cfg: cfg,
    };
})(typeof window !== 'undefined' ? window : this);
