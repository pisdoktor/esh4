/**
 * Profil fotoğrafı kırpma — Cropper.js 2.x (User&action=image).
 */
(function () {
    'use strict';

    var img = document.getElementById('cropbox');
    var CropperCtor = resolveCropperConstructor();
    if (!img || !CropperCtor) {
        return;
    }

    var host = document.getElementById('esh-cropper-host');
    var form = document.querySelector('form[action*="action=cropsave"]');
    var xEl = document.getElementById('x');
    var yEl = document.getElementById('y');
    var wEl = document.getElementById('w');
    var hEl = document.getElementById('h');

    var cropper = new CropperCtor(img, {
        container: host || undefined
    });

    var selection = cropper.getCropperSelection();
    if (selection) {
        selection.addEventListener('change', syncCoords);
    }

    var cropperImage = cropper.getCropperImage();
    if (cropperImage && typeof cropperImage.$ready === 'function') {
        cropperImage.$ready(function (loadedImg) {
            layoutCropper(cropper, loadedImg);
            requestAnimationFrame(function () {
                layoutCropper(cropper, loadedImg);
                syncCoords();
            });
        });
    }

    if (form) {
        form.addEventListener('submit', function (ev) {
            if (!syncCoords()) {
                ev.preventDefault();
                window.alert('Kırpma alanı okunamadı. Sayfayı yenileyip tekrar deneyin.');
            }
        });
    }

    function resolveCropperConstructor() {
        var root = window.Cropper;
        if (!root) {
            return null;
        }
        if (typeof root === 'function') {
            return root;
        }
        if (root.default && typeof root.default === 'function') {
            return root.default;
        }
        return null;
    }

    /**
     * @param {HTMLElement|null} cropperImageEl
     * @returns {HTMLImageElement|null}
     */
    function getInnerImage(cropperImageEl) {
        if (!cropperImageEl) {
            return null;
        }
        if (cropperImageEl.$image instanceof HTMLImageElement) {
            return cropperImageEl.$image;
        }
        if (cropperImageEl.shadowRoot) {
            var fromShadow = cropperImageEl.shadowRoot.querySelector('img');
            if (fromShadow) {
                return fromShadow;
            }
        }
        var fromLight = cropperImageEl.querySelector('img');
        return fromLight instanceof HTMLImageElement ? fromLight : null;
    }

    /**
     * Canvas boyutunu görüntü oranına göre ayarlar; görüntüyü kutuya sığdırır (contain).
     *
     * @param {object} instance
     * @param {HTMLImageElement} loadedImg
     */
    function layoutCropper(instance, loadedImg) {
        var canvas = instance.getCropperCanvas();
        var image = instance.getCropperImage();
        var sel = instance.getCropperSelection();
        if (!canvas || !image || !loadedImg || !loadedImg.naturalWidth || !loadedImg.naturalHeight) {
            return;
        }

        var nw = loadedImg.naturalWidth;
        var nh = loadedImg.naturalHeight;
        var maxW = Math.min(720, Math.floor(window.innerWidth * 0.88));
        var maxH = Math.floor(window.innerHeight * 0.62);
        var scale = Math.min(maxW / nw, maxH / nh, 1);
        var dw = Math.max(1, Math.round(nw * scale));
        var dh = Math.max(1, Math.round(nh * scale));

        canvas.style.width = dw + 'px';
        canvas.style.height = dh + 'px';
        canvas.style.maxWidth = '100%';

        image.$resetTransform();
        image.$center('contain');

        if (sel) {
            sel.aspectRatio = 1;
            sel.initialCoverage = 1;
            if (typeof sel.$initSelection === 'function') {
                sel.$initSelection(true, true);
            }
            if (typeof sel.$center === 'function') {
                sel.$center();
            }
        }
    }

    /** @returns {boolean} */
    function syncCoords() {
        var rect = naturalCropRect(cropper);
        if (!rect) {
            return false;
        }
        if (xEl) {
            xEl.value = String(rect.x);
        }
        if (yEl) {
            yEl.value = String(rect.y);
        }
        if (wEl) {
            wEl.value = String(rect.width);
        }
        if (hEl) {
            hEl.value = String(rect.height);
        }
        return true;
    }

    /**
     * @param {object} instance
     * @returns {{x: number, y: number, width: number, height: number}|null}
     */
    function naturalCropRect(instance) {
        var canvas = instance.getCropperCanvas();
        var image = instance.getCropperImage();
        var selEl = instance.getCropperSelection();
        if (!canvas || !image || !selEl || selEl.width <= 0 || selEl.height <= 0) {
            return null;
        }

        var imgTag = getInnerImage(image);
        if (!imgTag || !imgTag.naturalWidth || !imgTag.naturalHeight) {
            return null;
        }

        var nw = imgTag.naturalWidth;
        var nh = imgTag.naturalHeight;
        var canvasRect = canvas.getBoundingClientRect();
        var imageRect = image.getBoundingClientRect();
        var ix = imageRect.left - canvasRect.left;
        var iy = imageRect.top - canvasRect.top;
        var iw = imageRect.width;
        var ih = imageRect.height;
        if (iw <= 0 || ih <= 0) {
            return null;
        }

        var scaleX = nw / iw;
        var scaleY = nh / ih;
        var x = (selEl.x - ix) * scaleX;
        var y = (selEl.y - iy) * scaleY;
        var width = selEl.width * scaleX;
        var height = selEl.height * scaleY;

        x = Math.max(0, Math.min(x, nw - 1));
        y = Math.max(0, Math.min(y, nh - 1));
        width = Math.max(1, Math.min(width, nw - x));
        height = Math.max(1, Math.min(height, nh - y));

        return {
            x: Math.round(x),
            y: Math.round(y),
            width: Math.round(width),
            height: Math.round(height)
        };
    }
})();
