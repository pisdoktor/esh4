(function () {
    var root = document.getElementById('esh-stok-giris-root');
    if (!root) {
        return;
    }
    var linesEl = document.getElementById('esh-stok-giris-lines');
    var addBtn = document.getElementById('esh-stok-giris-add-line');
    var template = root.getAttribute('data-esh-line-template') || '';
    if (!linesEl || !addBtn || !template) {
        return;
    }

    var lineIndex = 0;

    function bindRemove(btn) {
        btn.addEventListener('click', function () {
            var line = btn.closest('.esh-stok-giris-line');
            if (line && linesEl.querySelectorAll('.esh-stok-giris-line').length > 1) {
                line.remove();
            }
        });
    }

    function addLine() {
        var html = template.replace(/__IDX__/g, String(lineIndex++));
        var wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        var line = wrap.firstElementChild;
        if (!line) {
            return;
        }
        linesEl.appendChild(line);
        var removeBtn = line.querySelector('.esh-stok-giris-remove');
        if (removeBtn) {
            bindRemove(removeBtn);
        }
    }

    addBtn.addEventListener('click', addLine);
    addLine();
})();
