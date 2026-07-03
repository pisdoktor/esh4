(function () {
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text || '';
        return d.innerHTML;
    }

    function postAction(url, konusmaId) {
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

    function toastError(message) {
        if (window.toastr) {
            toastr.error(message);
        }
    }

    function toastSuccess(message) {
        if (window.toastr) {
            toastr.success(message);
        }
    }

    function initMailbox() {
        var inbox = document.getElementById('esh-mesaj-inbox');
        if (!inbox) {
            return;
        }
        var fetchUrl = inbox.getAttribute('data-esh-fetch-url');
        var moveTrashUrl = inbox.getAttribute('data-move-trash-url');
        var restoreUrl = inbox.getAttribute('data-restore-url');
        var purgeUrl = inbox.getAttribute('data-purge-url');

        function showError(message) {
            inbox.innerHTML = '<div class="list-group-item text-center text-danger py-4">' + escapeHtml(message) + '</div>';
        }

        function updateNavBadge(total) {
            if (typeof window.eshUpdateMesajUnreadUi === 'function') {
                window.eshUpdateMesajUnreadUi(total);
            }
        }

        function loadList() {
            if (!fetchUrl) {
                return Promise.resolve();
            }
            return eshFetchListHtml(fetchUrl).then(function (data) { if (typeof data.html === 'string') {
                    inbox.innerHTML = data.html;
                }
                if (typeof data.unread_total !== 'undefined') {
                    updateNavBadge(data.unread_total);
                }
            });
        }

        function removeRow(konusmaId) {
            var btn = inbox.querySelector('[data-konusma-id="' + konusmaId + '"]');
            var row = btn ? btn.closest('.list-group-item') : null;
            if (row) {
                row.remove();
            }
            if (!inbox.querySelector('.list-group-item')) {
                loadList();
            }
        }

        inbox.addEventListener('click', function (ev) {
            var trashBtn = ev.target.closest('.js-mesaj-trash');
            if (trashBtn && moveTrashUrl) {
                ev.preventDefault();
                var kid = parseInt(trashBtn.getAttribute('data-konusma-id'), 10);
                if (!kid) {
                    return;
                }
                if (!window.confirm('Bu konuşmayı çöp kutusuna taşımak istiyor musunuz?')) {
                    return;
                }
                trashBtn.disabled = true;
                postAction(moveTrashUrl, kid).then(function (data) {
                    if (!data || !data.ok) {
                        toastError((data && data.mesaj) ? data.mesaj : 'Silinemedi.');
                        trashBtn.disabled = false;
                        return;
                    }
                    removeRow(kid);
                    if (typeof data.unread_total !== 'undefined') {
                        updateNavBadge(data.unread_total);
                    }
                    toastSuccess('Çöp kutusuna taşındı.');
                }).catch(function () {
                    toastError('Bağlantı hatası.');
                    trashBtn.disabled = false;
                });
                return;
            }

            var restoreBtn = ev.target.closest('.js-mesaj-restore');
            if (restoreBtn && restoreUrl) {
                ev.preventDefault();
                var restoreId = parseInt(restoreBtn.getAttribute('data-konusma-id'), 10);
                if (!restoreId) {
                    return;
                }
                restoreBtn.disabled = true;
                postAction(restoreUrl, restoreId).then(function (data) {
                    if (!data || !data.ok) {
                        toastError((data && data.mesaj) ? data.mesaj : 'Geri yüklenemedi.');
                        restoreBtn.disabled = false;
                        return;
                    }
                    removeRow(restoreId);
                    toastSuccess('Gelen kutusuna geri yüklendi.');
                }).catch(function () {
                    toastError('Bağlantı hatası.');
                    restoreBtn.disabled = false;
                });
                return;
            }

            var purgeBtn = ev.target.closest('.js-mesaj-purge');
            if (purgeBtn && purgeUrl) {
                ev.preventDefault();
                var purgeId = parseInt(purgeBtn.getAttribute('data-konusma-id'), 10);
                if (!purgeId) {
                    return;
                }
                if (!window.confirm('Bu konuşma kalıcı olarak silinecek. Emin misiniz?')) {
                    return;
                }
                purgeBtn.disabled = true;
                postAction(purgeUrl, purgeId).then(function (data) {
                    if (!data || !data.ok) {
                        toastError((data && data.mesaj) ? data.mesaj : 'Silinemedi.');
                        purgeBtn.disabled = false;
                        return;
                    }
                    removeRow(purgeId);
                    toastSuccess('Kalıcı olarak silindi.');
                }).catch(function () {
                    toastError('Bağlantı hatası.');
                    purgeBtn.disabled = false;
                });
            }
        });

        loadList().catch(function (err) {
            showError(err && err.message ? err.message : 'Bağlantı hatası.');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMailbox);
    } else {
        initMailbox();
    }
})();
