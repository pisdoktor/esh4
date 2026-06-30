/**
 * Patient&action=wounds — yara fotoğrafı galerisi, karşılaştırma, modal.
 */
(function () {
    'use strict';

    function initWoundGallery() {
        var loadMoreBtn = document.getElementById('loadMoreWoundPhotosBtn');
        if (loadMoreBtn) {
            var step = parseInt(loadMoreBtn.getAttribute('data-step') || '12', 10);
            if (!step || step < 1) {
                step = 12;
            }
            loadMoreBtn.addEventListener('click', function () {
                var hiddenCards = Array.prototype.slice.call(document.querySelectorAll('.wound-photo-hidden.d-none'));
                hiddenCards.slice(0, step).forEach(function (card) {
                    card.classList.remove('d-none');
                });
                if (document.querySelectorAll('.wound-photo-hidden.d-none').length === 0) {
                    loadMoreBtn.classList.add('d-none');
                }
            });
        }

        var range = document.getElementById('compareRange');
        var beforeImg = document.getElementById('beforeCompareImage');
        var after = document.getElementById('afterCompareImage');
        var beforeLabel = document.getElementById('beforeCompareLabel');
        var afterLabel = document.getElementById('afterCompareLabel');
        if (range && after) {
            var update = function () {
                after.style.width = range.value + '%';
            };
            range.addEventListener('input', update);
            update();

            var firstThumb = document.querySelector('.set-compare-before');
            var secondThumb = document.querySelector('.set-compare-after');
            if (firstThumb && beforeImg) {
                beforeImg.src = firstThumb.getAttribute('data-url') || '';
                if (beforeLabel) {
                    beforeLabel.textContent = 'Önce: ' + (firstThumb.getAttribute('data-label') || '-');
                }
            }
            if (secondThumb && after) {
                after.src = secondThumb.getAttribute('data-url') || '';
                if (afterLabel) {
                    afterLabel.textContent = 'Sonra: ' + (secondThumb.getAttribute('data-label') || '-');
                }
            }
            document.querySelectorAll('.set-compare-before').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (beforeImg) {
                        beforeImg.src = this.getAttribute('data-url') || '';
                    }
                    if (beforeLabel) {
                        beforeLabel.textContent = 'Önce: ' + (this.getAttribute('data-label') || '-');
                    }
                });
            });
            document.querySelectorAll('.set-compare-after').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (after) {
                        after.src = this.getAttribute('data-url') || '';
                    }
                    if (afterLabel) {
                        afterLabel.textContent = 'Sonra: ' + (this.getAttribute('data-label') || '-');
                    }
                });
            });
        }

        var photoItems = Array.prototype.slice.call(document.querySelectorAll('.wound-photo-trigger'));
        var currentPhotoPos = -1;
        var modalEl = document.getElementById('woundPhotoModal');
        var prevBtn = document.getElementById('woundPrevBtn');
        var nextBtn = document.getElementById('woundNextBtn');
        var setModalByPos = function (pos) {
            if (!photoItems.length || !modalEl) {
                return;
            }
            if (pos < 0) {
                pos = photoItems.length - 1;
            } else if (pos >= photoItems.length) {
                pos = 0;
            }
            currentPhotoPos = pos;
            var item = photoItems[pos];
            var full = item.getAttribute('data-full-url') || '';
            var caption = item.getAttribute('data-caption') || '';
            var meta = item.getAttribute('data-meta') || '';
            var img = document.getElementById('woundModalImage');
            var cap = document.getElementById('woundModalCaption');
            var met = document.getElementById('woundModalMeta');
            if (img) {
                img.src = full;
            }
            if (cap) {
                cap.textContent = caption;
            }
            if (met) {
                met.textContent = meta.replace(/^Bölge:\s*\|\s*Evre:\s*$/, '');
            }
        };

        photoItems.forEach(function (el) {
            el.addEventListener('click', function (ev) {
                ev.preventDefault();
                currentPhotoPos = photoItems.indexOf(this);
                setModalByPos(currentPhotoPos);
            });
        });
        if (prevBtn) {
            prevBtn.onclick = function () {
                setModalByPos(currentPhotoPos - 1);
            };
        }
        if (nextBtn) {
            nextBtn.onclick = function () {
                setModalByPos(currentPhotoPos + 1);
            };
        }
        if (modalEl) {
            modalEl.addEventListener('keydown', function (e) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    setModalByPos(currentPhotoPos - 1);
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    setModalByPos(currentPhotoPos + 1);
                }
            });
            modalEl.addEventListener('shown.bs.modal', function () {
                modalEl.focus();
            });
            modalEl.setAttribute('tabindex', '-1');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWoundGallery);
    } else {
        initWoundGallery();
    }
})();
