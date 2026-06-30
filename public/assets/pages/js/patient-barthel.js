/**
 * Patient&action=barthel — Barthel skoru canlı hesaplama (edit ile aynı mantık).
 */
(function () {
    'use strict';

    function calculateBarthel() {
        var total = 0;
        document.querySelectorAll('.barthel-input').forEach(function (el) {
            total += parseInt(el.value, 10) || 0;
        });

        var badge = document.getElementById('barthel-total-badge');
        if (badge) {
            badge.textContent = 'Toplam Skor: ' + total;
        }

        var status = '';
        var colorClass = '';
        if (total <= 20) {
            status = 'Tam Bağımlı';
            colorClass = 'text-danger';
        } else if (total <= 60) {
            status = 'İleri Derecede Bağımlı';
            colorClass = 'text-warning';
        } else if (total <= 90) {
            status = 'Orta Derecede Bağımlı';
            colorClass = 'text-info';
        } else if (total <= 99) {
            status = 'Hafif Derecede Bağımlı';
            colorClass = 'text-primary';
        } else {
            status = 'Tam Bağımsız';
            colorClass = 'text-success';
        }

        var statusEl = document.getElementById('barthel-status');
        if (statusEl) {
            statusEl.textContent = status;
            statusEl.className = 'fw-bold small ' + colorClass;
        }

        var summary = document.getElementById('bagimlilik-input');
        if (summary) {
            summary.value = total + ' Puan - ' + status;
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
