(function () {
    var root = document.getElementById('esh-stok-sayim-root');
    if (!root) {
        return;
    }
    var malzemeEl = document.getElementById('esh-stok-sayim-malzeme');
    var sistemEl = document.getElementById('esh-stok-sayim-sistem');
    var sayilanEl = document.getElementById('esh-stok-sayim-sayilan');
    var farkEl = document.getElementById('esh-stok-sayim-fark');
    if (!malzemeEl || !sistemEl || !sayilanEl || !farkEl) {
        return;
    }

    var stockMap = {};
    try {
        stockMap = JSON.parse(root.getAttribute('data-esh-stock-map') || '{}');
    } catch (e) {
        stockMap = {};
    }

    function formatNum(n) {
        if (isNaN(n)) {
            return '—';
        }
        return String(Math.round(n * 1000) / 1000);
    }

    function refresh() {
        var id = malzemeEl.value || '';
        var sistem = id !== '' && stockMap[id] != null ? parseFloat(stockMap[id]) : NaN;
        sistemEl.value = isNaN(sistem) ? '—' : formatNum(sistem);

        var sayilan = parseFloat(sayilanEl.value);
        if (isNaN(sistem) || isNaN(sayilan)) {
            farkEl.value = '—';
            return;
        }
        var diff = sayilan - sistem;
        if (Math.abs(diff) < 0.0001) {
            farkEl.value = 'Fark yok';
            return;
        }
        farkEl.value = (diff > 0 ? '+' : '') + formatNum(diff) + (diff > 0 ? ' (giriş)' : ' (çıkış)');
    }

    malzemeEl.addEventListener('change', refresh);
    sayilanEl.addEventListener('input', refresh);
    refresh();
})();
