window.eshUpdateMesajUnreadUi = function (total) {
    var n = parseInt(total, 10) || 0;
    var display = n > 99 ? '99+' : String(n);
    var badge = document.getElementById('esh-mesaj-nav-badge');
    if (badge) {
        if (n <= 0) {
            badge.classList.add('d-none');
            badge.textContent = '0';
        } else {
            badge.classList.remove('d-none');
            badge.textContent = display;
        }
    }
    var menuLabel = document.getElementById('esh-mesaj-menu-label');
    if (menuLabel) {
        menuLabel.textContent = n > 0 ? 'Mesaj Kutusu (' + display + ')' : 'Mesaj Kutusu';
    }
};
