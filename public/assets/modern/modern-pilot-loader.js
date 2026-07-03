/**
 * Modern frontend pilot loader — Vue CDN + scope modülleri (import.meta.url ile aynı köken).
 */
(function () {
    'use strict';

    var cfg = window.ESH_MODERN_PILOT;
    if (!cfg || !cfg.scope || !cfg.vueUrl) {
        return;
    }

    var root = document.getElementById('esh-modern-pilot-root');
    if (!root) {
        return;
    }

    var bootByScope = {
        dashboard: {
            file: cfg.useBuiltBundle ? 'dist/dashboard-pilot.built.mjs' : 'dashboard-pilot.mjs',
            boot: 'bootDashboardPilot',
        },
        planning: {
            file: cfg.useBuiltBundle ? 'dist/planning-pilot.built.mjs' : 'planning-pilot.mjs',
            boot: 'bootPlanningPilot',
        },
    };

    var spec = bootByScope[cfg.scope];
    if (!spec) {
        return;
    }

    var loaderUrl = new URL(import.meta.url);
    var cacheV = loaderUrl.searchParams.get('v');
    var moduleUrlObj = new URL(spec.file, import.meta.url);
    if (cacheV) {
        moduleUrlObj.searchParams.set('v', cacheV);
    }
    var moduleUrl = moduleUrlObj.href;

    function fail(err) {
        if (typeof console !== 'undefined' && console.error) {
            console.error('ESH modern pilot load failed:', err);
        }
        var detail = err && err.message ? String(err.message) : '';
        root.innerHTML = '<div class="small text-danger p-2">Modern pilot yüklenemedi.'
            + (detail ? ' <span class="text-muted">(' + detail.replace(/</g, '&lt;') + ')</span>' : '')
            + '</div>';
    }

    import(/* @vite-ignore */ cfg.vueUrl)
        .then(function (vue) {
            return import(/* @vite-ignore */ moduleUrl).then(function (mod) {
                var boot = mod[spec.boot];
                if (typeof boot !== 'function') {
                    throw new Error('Boot fonksiyonu yok: ' + spec.boot);
                }
                boot(root, vue);
            });
        })
        .catch(fail);
})();
