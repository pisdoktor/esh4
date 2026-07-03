(function () {
    'use strict';

    function initSmsCompose() {
        var cfg = window.ESH_SMS_COMPOSE || {};
        var previewUrl = cfg.previewUrl || '';
        var form = document.getElementById('sms-compose-form');
        var govde = document.getElementById('sms-govde');
        var charCount = document.getElementById('sms-char-count');
        var partCount = document.getElementById('sms-part-count');
        var sablonSelect = document.getElementById('sms-sablon-select');
        var sablonId = document.getElementById('sms-sablon-id');

        function normalizeSablonMap(raw) {
            var map = {};
            if (!raw || typeof raw !== 'object') {
                return map;
            }
            if (Array.isArray(raw)) {
                raw.forEach(function (row) {
                    if (!row || typeof row !== 'object') {
                        return;
                    }
                    var rid = parseInt(String(row.id || 0), 10);
                    if (rid > 0) {
                        map[rid] = row;
                    }
                });
                return map;
            }
            Object.keys(raw).forEach(function (key) {
                var row = raw[key];
                if (!row || typeof row !== 'object') {
                    return;
                }
                var rid = parseInt(String(row.id || key || 0), 10);
                if (rid > 0) {
                    map[rid] = row;
                }
            });
            return map;
        }

        var sablonMap = normalizeSablonMap(cfg.sablonMap);

        function lookupSablon(id) {
            if (id <= 0) {
                return null;
            }
            return sablonMap[id] || sablonMap[String(id)] || null;
        }

        function updateCharCount() {
            if (!govde || !charCount) {
                return;
            }
            var len = govde.value.length;
            charCount.textContent = String(len);
            if (partCount) {
                partCount.textContent = String(len <= 0 ? 0 : len <= 160 ? 1 : Math.ceil(len / 153));
            }
        }

        function selectedSegment() {
            var r = form && form.querySelector('input[name="segment"]:checked');
            return r ? r.value : 'tek_hasta';
        }

        function toggleParams() {
            var seg = selectedSegment();
            document.querySelectorAll('.sms-param').forEach(function (el) {
                el.style.display = 'none';
            });
            document.querySelectorAll('.sms-param-' + seg).forEach(function (el) {
                el.style.display = '';
            });
        }

        function collectParams() {
            if (!form) {
                return {};
            }
            var fd = new FormData(form);
            var seg = selectedSegment();
            var params = {};
            if (seg === 'tek_hasta') {
                params.hasta_id = parseInt(String(fd.get('hasta_id') || '0'), 10);
            }
            if (seg === 'gunun_plani' || seg === 'planli_izlem' || seg === 'ilk_ziyaret') {
                params.tarih = fd.get('tarih') || '';
                params.zaman = parseInt(String(fd.get('zaman') || '0'), 10);
            }
            if (seg === 'sonda_yaklasan') {
                params.gun_araligi = parseInt(String(fd.get('gun_araligi') || '7'), 10);
            }
            return params;
        }

        function collectRoles() {
            if (!form) {
                return [];
            }
            return Array.prototype.slice.call(form.querySelectorAll('input[name="roles[]"]:checked')).map(function (c) {
                return c.value;
            });
        }

        function applySablonSelection() {
            if (!sablonSelect || !govde) {
                return;
            }
            var id = parseInt(String(sablonSelect.value || '0'), 10);
            if (id <= 0) {
                if (sablonId) {
                    sablonId.value = '';
                }
                return;
            }
            var row = lookupSablon(id);
            if (!row) {
                return;
            }
            govde.value = row.govde || '';
            if (sablonId) {
                sablonId.value = String(row.id || id);
            }
            updateCharCount();
        }

        if (sablonSelect) {
            sablonSelect.addEventListener('change', applySablonSelection);
        }

        if (govde) {
            govde.addEventListener('input', updateCharCount);
        }
        if (form) {
            form.querySelectorAll('.sms-segment-radio').forEach(function (r) {
                r.addEventListener('change', toggleParams);
            });
        }
        toggleParams();
        updateCharCount();

        var previewBtn = document.getElementById('sms-preview-btn');
        if (previewBtn && previewUrl) {
            previewBtn.addEventListener('click', function () {
                previewBtn.disabled = true;
                fetch(previewUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({
                        segment: selectedSegment(),
                        params: collectParams(),
                        govde: govde ? govde.value : '',
                        roles: collectRoles()
                    })
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        var tbody = document.getElementById('sms-preview-rows');
                        var stats = document.getElementById('sms-preview-stats');
                        if (!tbody) {
                            return;
                        }
                        if (!data.ok) {
                            tbody.innerHTML = '<tr><td colspan="4" class="text-danger small p-3">' + (data.mesaj || 'Hata') + '</td></tr>';
                            return;
                        }
                        if (stats && data.stats) {
                            stats.textContent = data.stats.gonderecek + ' gönderilecek, ' + data.stats.atlanacak + ' atlanacak (toplam ' + data.stats.toplam + ')';
                        }
                        var rows = data.rows || [];
                        if (!rows.length) {
                            tbody.innerHTML = '<tr><td colspan="4" class="text-muted small p-3">Alıcı bulunamadı.</td></tr>';
                            return;
                        }
                        tbody.innerHTML = rows.map(function (row) {
                            var st = row.skip_reason ? row.skip_reason : 'Hazır';
                            return '<tr><td class="small">' + (row.hasta_ad || '') + '</td><td class="small">' + (row.rol_label || '') + '</td><td class="small">' + (row.telefon_mask || '—') + '</td><td class="small">' + st + '</td></tr>';
                        }).join('');
                    })
                    .finally(function () { previewBtn.disabled = false; });
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSmsCompose);
    } else {
        initSmsCompose();
    }
})();
