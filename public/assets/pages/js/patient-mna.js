/**
 * Patient&action=mna — MNA-SF canlı skor hesaplama.
 */
(function () {
    'use strict';

    var STATUS_LEVELS = [
        { max: 7, label: 'Malnütrisyon', badgeClass: 'bg-danger' },
        { max: 11, label: 'Malnütrisyon riski', badgeClass: 'bg-warning text-dark' },
        { max: 14, label: 'Normal beslenme', badgeClass: 'bg-success-subtle text-success border' }
    ];

    function resolveStatus(total) {
        for (var i = 0; i < STATUS_LEVELS.length; i++) {
            if (total <= STATUS_LEVELS[i].max) {
                return STATUS_LEVELS[i];
            }
        }
        return STATUS_LEVELS[STATUS_LEVELS.length - 1];
    }

    function activeBmiScore() {
        var bmiBlock = document.getElementById('mna-bmi-block');
        var calfBlock = document.getElementById('mna-calf-block');
        var select = null;
        if (bmiBlock && !bmiBlock.classList.contains('d-none')) {
            select = document.getElementById('mna-bmi-skor');
        } else if (calfBlock && !calfBlock.classList.contains('d-none')) {
            select = document.getElementById('mna-calf-skor');
        }
        if (!select) {
            return 0;
        }
        return parseInt(select.value, 10) || 0;
    }

    function calculateMna() {
        var total = activeBmiScore();
        document.querySelectorAll('.mna-input').forEach(function (el) {
            total += parseInt(el.value, 10) || 0;
        });

        var badge = document.getElementById('mna-total-badge');
        if (badge) {
            badge.textContent = 'Toplam: ' + total + ' / 14';
        }

        var status = resolveStatus(total);
        var statusBadge = document.getElementById('mna-status-badge');
        if (statusBadge) {
            statusBadge.textContent = status.label;
            statusBadge.className = 'badge ms-1 ' + status.badgeClass;
        }

        var summary = document.getElementById('mna-summary-text');
        if (summary) {
            summary.textContent = total + ' puan — ' + status.label;
        }
    }

    function syncOlcumBlocks() {
        var useBmi = document.getElementById('mna-olcum-bmi');
        var bmiBlock = document.getElementById('mna-bmi-block');
        var calfBlock = document.getElementById('mna-calf-block');
        if (!useBmi || !bmiBlock || !calfBlock) {
            return;
        }
        if (useBmi.checked) {
            bmiBlock.classList.remove('d-none');
            calfBlock.classList.add('d-none');
        } else {
            bmiBlock.classList.add('d-none');
            calfBlock.classList.remove('d-none');
        }
        calculateMna();
    }

    function initMnaDatepicker() {
        if (!window.jQuery || typeof window.jQuery.fn.datepicker !== 'function') {
            return;
        }
        var $inputs = window.jQuery('#patientMnaForm .datepicker');
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
        initMnaDatepicker();
        document.querySelectorAll('.mna-input, .mna-bmi-input').forEach(function (el) {
            el.addEventListener('change', calculateMna);
        });
        document.querySelectorAll('.mna-olcum-toggle').forEach(function (el) {
            el.addEventListener('change', syncOlcumBlocks);
        });
        syncOlcumBlocks();
        calculateMna();
    });
})();
