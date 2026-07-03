(function () {
    var cfg = (window.ESH_PAGE && window.ESH_PAGE.mesajThread) || {};
    var scrollEl = document.getElementById('esh-mesaj-thread-scroll');
    var form = document.getElementById('esh-mesaj-send-form');
    var textarea = document.getElementById('esh-mesaj-govde');
    var sendBtn = document.getElementById('esh-mesaj-send-btn');
    var sinceId = parseInt(cfg.sinceId, 10) || 0;
    var pollTimer = null;
    var pollMs = 5000;

    function scrollToBottom() {
        if (!scrollEl) {
            return;
        }
        scrollEl.scrollTop = scrollEl.scrollHeight;
    }

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function updateNavBadge(total) {
        if (typeof window.eshUpdateMesajUnreadUi === 'function') {
            window.eshUpdateMesajUnreadUi(total);
        }
    }

    function appendHtml(html) {
        if (!scrollEl || !html) {
            return;
        }
        var empty = scrollEl.querySelector('.esh-mesaj-empty-hint');
        if (empty) {
            empty.remove();
        }
        var wrap = document.createElement('div');
        wrap.innerHTML = html;
        while (wrap.firstChild) {
            scrollEl.appendChild(wrap.firstChild);
        }
        scrollToBottom();
    }

    function pollThread() {
        if (!cfg.pollThreadUrl || document.hidden) {
            return;
        }
        var url = cfg.pollThreadUrl + (cfg.pollThreadUrl.indexOf('?') >= 0 ? '&' : '?') + 'since_id=' + sinceId;
        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            if (!data || !data.ok) {
                return;
            }
            if (data.has_new && data.html) {
                appendHtml(data.html);
            }
            if (data.last_id && parseInt(data.last_id, 10) > sinceId) {
                sinceId = parseInt(data.last_id, 10);
                scrollEl.setAttribute('data-since-id', String(sinceId));
            }
            if (typeof data.unread_total !== 'undefined') {
                updateNavBadge(data.unread_total);
            }
        }).catch(function () {});
    }

    function startPoll() {
        if (pollTimer) {
            clearInterval(pollTimer);
        }
        pollTimer = setInterval(pollThread, pollMs);
    }

    if (form && cfg.sendUrl) {
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var body = (textarea && textarea.value) ? textarea.value.trim() : '';
            if (!body) {
                return;
            }
            if (sendBtn) {
                sendBtn.disabled = true;
            }
            var fd = new FormData(form);
            fd.set('govde', body);
            fetch(cfg.sendUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': getCsrfToken()
                },
                body: fd
            }).then(function (res) {
                return res.json();
            }).then(function (data) {
                if (!data || !data.ok) {
                    if (window.toastr) {
                        toastr.error((data && data.mesaj) ? data.mesaj : 'Gönderilemedi.');
                    }
                    return;
                }
                if (textarea) {
                    textarea.value = '';
                }
                if (data.html) {
                    appendHtml(data.html);
                }
                if (data.mesaj_id && parseInt(data.mesaj_id, 10) > sinceId) {
                    sinceId = parseInt(data.mesaj_id, 10);
                    scrollEl.setAttribute('data-since-id', String(sinceId));
                }
            }).catch(function () {
                if (window.toastr) {
                    toastr.error('Bağlantı hatası.');
                }
            }).finally(function () {
                if (sendBtn) {
                    sendBtn.disabled = false;
                }
                if (textarea) {
                    textarea.focus();
                }
            });
        });
    }

    function postKonusmaAction(url, konusmaId) {
        var fd = new FormData();
        fd.set('konusma_id', String(konusmaId));
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': getCsrfToken()
            },
            body: fd
        }).then(function (res) {
            return res.json();
        });
    }

    function bindThreadAction(btnId, url, options) {
        var btn = document.getElementById(btnId);
        if (!btn || !url) {
            return;
        }
        var kid = parseInt(btn.getAttribute('data-konusma-id') || cfg.konusmaId, 10);
        btn.addEventListener('click', function () {
            if (options.confirm && !window.confirm(options.confirm)) {
                return;
            }
            btn.disabled = true;
            postKonusmaAction(url, kid).then(function (data) {
                if (!data || !data.ok) {
                    if (window.toastr) {
                        toastr.error((data && data.mesaj) ? data.mesaj : (options.error || 'İşlem başarısız.'));
                    }
                    btn.disabled = false;
                    return;
                }
                if (options.redirect) {
                    window.location.href = options.redirect;
                    return;
                }
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                window.location.href = cfg.inboxUrl || '/';
            }).catch(function () {
                if (window.toastr) {
                    toastr.error('Bağlantı hatası.');
                }
                btn.disabled = false;
            });
        });
    }

    bindThreadAction('esh-mesaj-thread-trash', cfg.moveToTrashUrl, {
        confirm: 'Bu konuşmayı çöp kutusuna taşımak istiyor musunuz?',
        redirect: cfg.trashListUrl,
        error: 'Silinemedi.'
    });
    bindThreadAction('esh-mesaj-thread-restore', cfg.restoreUrl, {
        error: 'Geri yüklenemedi.'
    });
    bindThreadAction('esh-mesaj-thread-purge', cfg.purgeUrl, {
        confirm: 'Bu konuşma kalıcı olarak silinecek. Emin misiniz?',
        redirect: cfg.trashListUrl,
        error: 'Silinemedi.'
    });

    scrollToBottom();
    if (!cfg.isTrashed) {
        startPoll();
    }
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && !cfg.isTrashed) {
            pollThread();
        }
    });
})();
