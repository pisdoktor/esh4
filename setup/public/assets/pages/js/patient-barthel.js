/**
 * Patient&action=barthel — Barthel skoru canlı hesaplama.
 */
(function () {
    'use strict';

    function statusForTotal(total) {
        if (total <= 20) {
            return { label: 'Tam Bağımlı', colorClass: 'text-danger' };
        }
        if (total <= 60) {
            return { label: 'Ağır Bağımlı', colorClass: 'text-warning' };
        }
        if (total <= 90) {
            return { label: 'Orta Bağımlı', colorClass: 'text-info' };
        }
        if (total <= 99) {
            return { label: 'Hafif Derecede Bağımlı', colorClass: 'text-primary' };
        }
        return { label: 'Bağımsız', colorClass: 'text-success' };
    }

    function calculateBarthel() {
        var total = 0;
        document.querySelectorAll('.barthel-input').forEach(function (el) {
            total += parseInt(el.value, 10) || 0;
        });

        var badge = document.getElementById('barthel-total-badge');
        if (badge) {
            badge.textContent = 'Toplam: ' + total + ' / 100';
        }

        var meta = statusForTotal(total);
        var statusEl = document.getElementById('barthel-status');
        if (statusEl) {
            statusEl.textContent = meta.label;
            statusEl.className = 'fw-bold small ' + meta.colorClass + ' ms-1';
        }

        var summary = document.getElementById('barthel-summary-text');
        if (summary) {
            summary.textContent = total + ' puan';
        }

        var headerBadge = document.getElementById('barthel-header-badge');
        if (headerBadge) {
            headerBadge.textContent = total + ' puan — ' + meta.label;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.barthel-input').forEach(function (el) {
            el.addEventListener('input', calculateBarthel);
            el.addEventListener('change', calculateBarthel);
        });
        calculateBarthel();
        if (typeof window.eshInitBarthelFieldTooltips === 'function') {
            window.eshInitBarthelFieldTooltips();
        }
    });
})();
