/**
 * Patient&action=braden — Braden skoru canlı hesaplama.
 */
(function () {
    'use strict';

    var RISK_LEVELS = [
        { max: 9, label: 'Çok yüksek risk', badgeClass: 'bg-danger' },
        { max: 12, label: 'Yüksek risk', badgeClass: 'bg-danger-subtle text-danger border' },
        { max: 14, label: 'Orta risk', badgeClass: 'bg-warning text-dark' },
        { max: 18, label: 'Hafif risk', badgeClass: 'bg-info-subtle text-info border' },
        { max: 23, label: 'Risk yok', badgeClass: 'bg-success-subtle text-success border' }
    ];

    function resolveRisk(total) {
        for (var i = 0; i < RISK_LEVELS.length; i++) {
            if (total <= RISK_LEVELS[i].max) {
                return RISK_LEVELS[i];
            }
        }
        return RISK_LEVELS[RISK_LEVELS.length - 1];
    }

    function calculateBraden() {
        var total = 0;
        document.querySelectorAll('.braden-input').forEach(function (el) {
            total += parseInt(el.value, 10) || 0;
        });

        var badge = document.getElementById('braden-total-badge');
        if (badge) {
            badge.textContent = 'Toplam Skor: ' + total;
        }

        var risk = resolveRisk(total);
        var riskBadge = document.getElementById('braden-risk-badge');
        if (riskBadge) {
            riskBadge.textContent = risk.label;
            riskBadge.className = 'badge ms-1 ' + risk.badgeClass;
        }

        var summary = document.getElementById('braden-summary-text');
        if (summary) {
            summary.textContent = total + ' puan — ' + risk.label;
        }
    }

    function initBradenDatepicker() {
        if (!window.jQuery || typeof window.jQuery.fn.datepicker !== 'function') {
            return;
        }
        var $inputs = window.jQuery('#patientBradenForm .datepicker');
        if (!$inputs.length) {
            return;
        }
        var opts = typeof window.eshDatepickerDefaults === 'function'
            ? window.eshDatepickerDefaults()
            : { format: 'dd-mm-yyyy', language: 'tr', autoclose: true, todayHighlight: true };
        opts.endDate = new Date();
        $inputs.each(function () {
            var $el = window.jQuery(this);
            if ($el.data('datepicker')) {
                $el.datepicker('destroy');
            }
            $el.datepicker(opts);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initBradenDatepicker();
        document.querySelectorAll('.braden-input').forEach(function (el) {
            el.addEventListener('input', calculateBraden);
            el.addEventListener('change', calculateBraden);
        });
        calculateBraden();
        if (typeof window.eshInitBarthelFieldTooltips === 'function') {
            window.eshInitBarthelFieldTooltips(document.querySelector('.esh-braden-form-card'));
        }
    });
})();
