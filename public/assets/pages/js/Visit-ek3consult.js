(function () {
    'use strict';

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function qsAll(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    function bransIdFromEl(el) {
        if (!el) {
            return '';
        }
        var item = el.closest('[data-brans-id]');
        return item ? String(item.getAttribute('data-brans-id') || '') : '';
    }

    function getMasterItem(form, bid) {
        return qs('.izlem-ek3-brans-master__item[data-brans-id="' + bid + '"]', form);
    }

    function getDetailPanel(form, bid) {
        return qs('.izlem-ek3-brans-detail[data-brans-id="' + bid + '"]', form);
    }

    function isBransChecked(form, bid) {
        var toggle = qs('.js-ek3-brans-toggle[value="' + bid + '"]', form);
        return !!(toggle && toggle.checked);
    }

    function hasIstekForBrans(form, bid) {
        return qsAll('input.js-ek3-istek-choice[data-brans-id="' + bid + '"]:checked', form).length > 0;
    }

    function updateBransStatus(form, bid) {
        var item = getMasterItem(form, bid);
        if (!item) {
            return;
        }
        var badge = qs('.js-ek3-brans-badge', item);
        var checked = isBransChecked(form, bid);
        var complete = checked && hasIstekForBrans(form, bid);

        item.classList.remove('izlem-ek3-brans-master__item--complete', 'izlem-ek3-brans-master__item--incomplete');
        if (!checked) {
            if (badge) {
                badge.className = 'badge rounded-pill js-ek3-brans-badge bg-secondary-subtle text-secondary';
                badge.textContent = 'Seçilmedi';
            }
            return;
        }
        if (complete) {
            item.classList.add('izlem-ek3-brans-master__item--complete');
            if (badge) {
                badge.className = 'badge rounded-pill js-ek3-brans-badge bg-success-subtle text-success';
                badge.textContent = 'Tamam';
            }
        } else {
            item.classList.add('izlem-ek3-brans-master__item--incomplete');
            if (badge) {
                badge.className = 'badge rounded-pill js-ek3-brans-badge bg-warning-subtle text-warning-emphasis';
                badge.textContent = 'İstek eksik';
            }
        }
    }

    function updateAllBransStatus(form) {
        qsAll('.izlem-ek3-brans-master__item[data-brans-id]', form).forEach(function (item) {
            updateBransStatus(form, item.getAttribute('data-brans-id'));
        });
    }

    function updateSummary(form) {
        var summaryText = qs('.js-ek3-summary-text', form);
        var summaryChips = qs('.js-ek3-summary-chips', form);
        var summaryRoot = qs('#eshEk3Summary', form);
        if (!summaryText || !summaryChips) {
            return;
        }

        var checked = qsAll('.js-ek3-brans-toggle:checked', form);
        var completeCount = 0;
        var chips = [];

        checked.forEach(function (toggle) {
            var bid = toggle.value;
            var item = getMasterItem(form, bid);
            var name = item ? (item.getAttribute('data-brans-name') || bid) : bid;
            if (hasIstekForBrans(form, bid)) {
                completeCount += 1;
            }
            chips.push('<span class="badge bg-primary-subtle text-primary border border-primary-subtle">' +
                String(name).replace(/</g, '&lt;') + '</span>');
        });

        if (summaryRoot) {
            summaryRoot.classList.toggle('izlem-ek3-summary--empty', !checked.length);
            summaryRoot.classList.toggle('izlem-ek3-summary--active', checked.length > 0);
            summaryRoot.classList.toggle('izlem-ek3-summary--complete', checked.length > 0 && completeCount === checked.length);
        }

        if (!checked.length) {
            summaryText.textContent = 'Henüz branş seçilmedi.';
            summaryChips.innerHTML = '';
            return;
        }

        summaryText.innerHTML = '<span class="izlem-ek3-summary__count">' + checked.length + '</span> branş seçildi · ' +
            '<span class="izlem-ek3-summary__count">' + completeCount + '</span> tamamlandı';
        summaryChips.innerHTML = chips.join('');
    }

    function setActiveBrans(form, bid, options) {
        options = options || {};
        bid = bid ? String(bid) : '';

        var emptyState = qs('.js-ek3-detail-empty', form);
        qsAll('.izlem-ek3-brans-master__item', form).forEach(function (item) {
            var isActive = bid !== '' && item.getAttribute('data-brans-id') === bid;
            item.classList.toggle('izlem-ek3-brans-master__item--active', isActive);
            item.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        var anyVisible = false;
        qsAll('.izlem-ek3-brans-detail', form).forEach(function (panel) {
            var show = bid !== '' && panel.getAttribute('data-brans-id') === bid;
            panel.classList.toggle('d-none', !show);
            if (show) {
                panel.removeAttribute('hidden');
                anyVisible = true;
            } else {
                panel.setAttribute('hidden', 'hidden');
            }
        });

        if (emptyState) {
            emptyState.classList.toggle('d-none', anyVisible);
        }

        if (bid && options.scrollDetail && window.matchMedia('(max-width: 991.98px)').matches) {
            var panel = getDetailPanel(form, bid);
            if (panel) {
                panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    }

    function syncBransSelection(form, bid, checked) {
        if (!checked) {
            qsAll('.js-ek3-istek-choice[data-brans-id="' + bid + '"]', form).forEach(function (input) {
                input.checked = false;
            });
        }
        updateBransStatus(form, bid);
        updateSummary(form);
        if (checked) {
            setActiveBrans(form, bid, { scrollDetail: true });
        } else {
            var nextChecked = qs('.js-ek3-brans-toggle:checked', form);
            if (nextChecked) {
                setActiveBrans(form, nextChecked.value);
            } else {
                setActiveBrans(form, '');
            }
        }
    }

    function showFormAlert(form, message) {
        var alertEl = qs('#eshEk3FormAlert', form);
        if (!alertEl) {
            window.alert(message);
            return;
        }
        alertEl.textContent = message;
        alertEl.classList.remove('d-none');
        alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideFormAlert(form) {
        var alertEl = qs('#eshEk3FormAlert', form);
        if (alertEl) {
            alertEl.classList.add('d-none');
            alertEl.textContent = '';
        }
    }

    function validateForm(form) {
        hideFormAlert(form);
        var checkedBrans = qsAll('.js-ek3-brans-toggle:checked', form);
        if (!checkedBrans.length) {
            showFormAlert(form, 'En az bir branş seçin.');
            return false;
        }
        for (var i = 0; i < checkedBrans.length; i++) {
            var bid = checkedBrans[i].value;
            if (!hasIstekForBrans(form, bid)) {
                setActiveBrans(form, bid, { scrollDetail: true });
                showFormAlert(form, 'Her seçili branş için en az bir başvuru amacı işaretleyin.');
                var firstMissing = qs('.js-ek3-istek-choice[data-brans-id="' + bid + '"]', form);
                if (firstMissing) {
                    firstMissing.focus();
                }
                return false;
            }
        }
        return true;
    }

    function initActiveBrans(form) {
        var firstChecked = qs('.js-ek3-brans-toggle:checked', form);
        if (firstChecked) {
            setActiveBrans(form, firstChecked.value);
        }
        updateAllBransStatus(form);
        updateSummary(form);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('eshEk3ConsultForm');
        if (!form) {
            return;
        }

        qsAll('.js-ek3-brans-toggle', form).forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                syncBransSelection(form, toggle.value, toggle.checked);
            });
        });

        qsAll('.izlem-ek3-brans-master__item', form).forEach(function (item) {
            item.addEventListener('click', function (ev) {
                if (ev.target && ev.target.classList && ev.target.classList.contains('js-ek3-brans-toggle')) {
                    return;
                }
                var bid = item.getAttribute('data-brans-id');
                setActiveBrans(form, bid, { scrollDetail: true });
            });
        });

        qsAll('.js-ek3-brans-activate', form).forEach(function (btn) {
            btn.addEventListener('click', function (ev) {
                ev.preventDefault();
                ev.stopPropagation();
                var bid = bransIdFromEl(btn);
                setActiveBrans(form, bid, { scrollDetail: true });
            });
        });

        qsAll('.js-ek3-istek-choice', form).forEach(function (input) {
            input.addEventListener('change', function () {
                var bid = input.getAttribute('data-brans-id');
                updateBransStatus(form, bid);
                updateSummary(form);
            });
        });

        form.addEventListener('submit', function (ev) {
            if (!validateForm(form)) {
                ev.preventDefault();
            }
        });

        initActiveBrans(form);
    });
})();
