/**
 * ESH saha PWA — günlük plan API önbelleği (network-first).
 */
const ESH_SW_CACHE = 'esh-field-v1';
const ESH_DAILY_PLAN_PATH = 'getdailyevents';

self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys
                    .filter(function (k) {
                        return k.startsWith('esh-field-') && k !== ESH_SW_CACHE;
                    })
                    .map(function (k) {
                        return caches.delete(k);
                    })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

function isDailyPlanRequest(url) {
    try {
        var u = new URL(url);
        return u.pathname.toLowerCase().indexOf(ESH_DAILY_PLAN_PATH) >= 0;
    } catch (e) {
        return false;
    }
}

self.addEventListener('fetch', function (event) {
    if (event.request.method !== 'GET' || !isDailyPlanRequest(event.request.url)) {
        return;
    }
    event.respondWith(
        fetch(event.request)
            .then(function (response) {
                if (response && response.ok) {
                    var clone = response.clone();
                    caches.open(ESH_SW_CACHE).then(function (cache) {
                        cache.put(event.request, clone);
                    });
                }
                return response;
            })
            .catch(function () {
                return caches.match(event.request).then(function (cached) {
                    if (cached) {
                        return cached;
                    }
                    return new Response(
                        JSON.stringify({ offline: true, sabah: {}, ogle: {}, aksam: {} }),
                        {
                            status: 503,
                            headers: { 'Content-Type': 'application/json; charset=utf-8' },
                        }
                    );
                });
            })
    );
});
