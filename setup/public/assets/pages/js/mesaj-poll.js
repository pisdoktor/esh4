(function () {
    var pollUrl = (window.ESH_PAGE && window.ESH_PAGE.mesajPollUrl) || '';
    if (!pollUrl) {
        var link = document.getElementById('esh-mesaj-nav-link');
        if (link) {
            pollUrl = link.getAttribute('data-poll-url') || '';
        }
    }
    if (!pollUrl) {
        return;
    }

    var pollMs = 45000;

    function updateUnread(total) {
        if (typeof window.eshUpdateMesajUnreadUi === 'function') {
            window.eshUpdateMesajUnreadUi(total);
        }
    }

    function poll() {
        if (document.hidden) {
            return;
        }
        fetch(pollUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            if (data && data.ok && typeof data.unread_total !== 'undefined') {
                updateUnread(data.unread_total);
            }
        }).catch(function () {});
    }

    poll();
    setInterval(poll, pollMs);
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            poll();
        }
    });
})();
