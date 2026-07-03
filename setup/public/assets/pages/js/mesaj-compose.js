(function () {
    var cfg = (window.ESH_PAGE && window.ESH_PAGE.mesajCompose) || {};
    var select = document.getElementById('esh-mesaj-user-select');
    var loading = document.getElementById('esh-mesaj-users-loading');
    if (!select || !cfg.usersUrl) {
        return;
    }

    fetch(cfg.usersUrl, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (res) {
        return res.json();
    }).then(function (data) {
        if (loading) {
            loading.remove();
        }
        if (!data || !data.ok || !Array.isArray(data.items)) {
            var opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'Kullanıcı listesi yüklenemedi';
            select.appendChild(opt);
            return;
        }
        if (data.items.length === 0) {
            var empty = document.createElement('option');
            empty.value = '';
            empty.textContent = 'Mesaj gönderebileceğiniz kullanıcı yok';
            select.appendChild(empty);
            return;
        }
        data.items.forEach(function (u) {
            var o = document.createElement('option');
            o.value = String(u.id);
            var label = u.name || 'Kullanıcı';
            if (u.kurum_adi) {
                label += ' — ' + u.kurum_adi;
            }
            if (u.unvan) {
                label += ' (' + u.unvan + ')';
            }
            o.textContent = label;
            select.appendChild(o);
        });
    }).catch(function () {
        if (loading) {
            loading.textContent = 'Kullanıcı listesi yüklenemedi.';
        }
    });
})();
