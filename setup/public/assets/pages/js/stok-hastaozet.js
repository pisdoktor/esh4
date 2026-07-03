(function () {
    var tbody = document.getElementById('esh-stok-hasta-ozet-tbody');
    if (!tbody) {
        return;
    }
    var url = tbody.getAttribute('data-esh-fetch-url');
    if (!url || typeof eshFetchListHtml !== 'function') {
        return;
    }
    eshFetchListHtml(url).then(function (data) {
        tbody.innerHTML = data.html;
    }).catch(function () {
        /* SSR satırları kalır */
    });
})();
