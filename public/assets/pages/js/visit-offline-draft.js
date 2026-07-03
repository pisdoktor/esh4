/**
 * Çevrimdışı izlem formu taslağı — IndexedDB + syncDraft API.
 */
(function (global) {
    'use strict';

    var DB_NAME = 'esh_field_visit';
    var DB_VERSION = 1;
    var STORE = 'drafts';

    function cfg() {
        var fv = global.ESH_PAGE && global.ESH_PAGE.fieldVisit ? global.ESH_PAGE.fieldVisit : {};
        return {
            enabled: !!fv.offlineDraftEnabled,
            syncUrl: String(fv.syncDraftUrl || ''),
        };
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? String(meta.getAttribute('content') || '') : '';
    }

    function draftKey(form) {
        var tcEl = form.querySelector('input[name="hastatckimlik"]');
        var idEl = form.querySelector('input[name="id"]');
        var tc = tcEl ? String(tcEl.value || '').trim() : 'unknown';
        var vid = idEl ? String(idEl.value || '').trim() : '';
        return tc + '|' + (vid || 'new');
    }

    function openDb() {
        return new Promise(function (resolve, reject) {
            if (!global.indexedDB) {
                reject(new Error('indexeddb_unavailable'));
                return;
            }
            var req = global.indexedDB.open(DB_NAME, DB_VERSION);
            req.onupgradeneeded = function () {
                var db = req.result;
                if (!db.objectStoreNames.contains(STORE)) {
                    db.createObjectStore(STORE, { keyPath: 'key' });
                }
            };
            req.onsuccess = function () {
                resolve(req.result);
            };
            req.onerror = function () {
                reject(req.error || new Error('idb_open'));
            };
        });
    }

    function formToObject(form) {
        var data = {};
        var fd = new FormData(form);
        fd.forEach(function (value, key) {
            if (key.endsWith('[]')) {
                var k = key.slice(0, -2);
                if (!data[k]) {
                    data[k] = [];
                }
                data[k].push(value);
            } else if (Object.prototype.hasOwnProperty.call(data, key)) {
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key]];
                }
                data[key].push(value);
            } else {
                data[key] = value;
            }
        });
        return data;
    }

    function saveDraft(form) {
        if (!cfg().enabled) {
            return Promise.resolve();
        }
        var key = draftKey(form);
        var payload = {
            key: key,
            form: formToObject(form),
            updatedAt: new Date().toISOString(),
            pending: true,
        };
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE, 'readwrite');
                tx.objectStore(STORE).put(payload);
                tx.oncomplete = function () {
                    db.close();
                    resolve();
                };
                tx.onerror = function () {
                    db.close();
                    reject(tx.error);
                };
            });
        });
    }

    function loadDraft(form) {
        if (!cfg().enabled) {
            return Promise.resolve(null);
        }
        var key = draftKey(form);
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE, 'readonly');
                var req = tx.objectStore(STORE).get(key);
                req.onsuccess = function () {
                    db.close();
                    resolve(req.result || null);
                };
                req.onerror = function () {
                    db.close();
                    reject(req.error);
                };
            });
        });
    }

    function removeDraft(key) {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE, 'readwrite');
                tx.objectStore(STORE).delete(key);
                tx.oncomplete = function () {
                    db.close();
                    resolve();
                };
                tx.onerror = function () {
                    db.close();
                    reject(tx.error);
                };
            });
        });
    }

    function listPendingDrafts() {
        return openDb().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction(STORE, 'readonly');
                var req = tx.objectStore(STORE).getAll();
                req.onsuccess = function () {
                    db.close();
                    var rows = req.result || [];
                    resolve(rows.filter(function (r) {
                        return r && r.pending;
                    }));
                };
                req.onerror = function () {
                    db.close();
                    reject(req.error);
                };
            });
        });
    }

    function syncDraftRecord(record) {
        var url = cfg().syncUrl;
        if (!url || !record || !record.form) {
            return Promise.resolve({ ok: false });
        }
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken(),
            },
            body: JSON.stringify({
                csrf_token: csrfToken(),
                form: record.form,
            }),
        })
            .then(function (r) {
                return r.json().then(function (data) {
                    return { ok: !!(data && data.ok), data: data };
                });
            })
            .catch(function () {
                return { ok: false };
            });
    }

    function syncAllPending() {
        if (!cfg().enabled || !global.navigator.onLine) {
            return Promise.resolve();
        }
        return listPendingDrafts().then(function (rows) {
            var chain = Promise.resolve();
            rows.forEach(function (row) {
                chain = chain.then(function () {
                    return syncDraftRecord(row).then(function (res) {
                        if (res.ok) {
                            return removeDraft(row.key);
                        }
                    });
                });
            });
            return chain;
        });
    }

    function restoreFormFields(form, data) {
        if (!data || typeof data !== 'object') {
            return;
        }
        Object.keys(data).forEach(function (key) {
            var val = data[key];
            if (Array.isArray(val)) {
                var multi = form.querySelectorAll('[name="' + key + '[]"]');
                if (multi.length) {
                    multi.forEach(function (el) {
                        if (el.tagName === 'SELECT') {
                            Array.prototype.forEach.call(el.options, function (opt) {
                                opt.selected = val.indexOf(opt.value) >= 0;
                            });
                        }
                    });
                }
                return;
            }
            var el = form.querySelector('[name="' + key + '"]');
            if (!el) {
                return;
            }
            if (el.type === 'radio') {
                form.querySelectorAll('[name="' + key + '"]').forEach(function (r) {
                    r.checked = String(r.value) === String(val);
                });
            } else if (el.tagName === 'SELECT' && el.multiple) {
                Array.prototype.forEach.call(el.options, function (opt) {
                    opt.selected = String(val).split(',').indexOf(opt.value) >= 0;
                });
            } else {
                el.value = val;
            }
        });
    }

    function bindForm(form) {
        if (!form || !cfg().enabled) {
            return;
        }

        loadDraft(form).then(function (record) {
            if (record && record.form && record.pending) {
                restoreFormFields(form, record.form);
                if (global.toastr && global.toastr.info) {
                    global.toastr.info('Kaydedilmemiş izlem taslağı geri yüklendi.');
                }
            }
        });

        var debounceT = null;
        form.addEventListener('input', function () {
            clearTimeout(debounceT);
            debounceT = setTimeout(function () {
                saveDraft(form);
            }, 800);
        });

        form.addEventListener('esh:visit-offline-queue', function () {
            saveDraft(form).then(function () {
                if (global.toastr && global.toastr.warning) {
                    global.toastr.warning(
                        'Çevrimdışısınız. İzlem taslağı cihazda saklandı; bağlantı gelince otomatik gönderilecek.'
                    );
                }
            });
        });

        global.addEventListener('online', function () {
            syncAllPending().then(function () {
                if (global.toastr && global.toastr.success) {
                    global.toastr.success('Bekleyen izlem taslakları senkronize edildi.');
                }
            });
        });

        syncAllPending();
    }

    global.eshVisitOfflineDraft = {
        bindForm: bindForm,
        saveDraft: saveDraft,
        syncAllPending: syncAllPending,
        isOffline: function () {
            return global.navigator && global.navigator.onLine === false;
        },
    };
})(typeof window !== 'undefined' ? window : this);
