/**
 * Patient&action=itaki — İTAKİ II canlı skor hesaplama.
 */
(function () {
    'use strict';

    function initFallRiskScale(prefix) {
        var form = document.getElementById('patient' + prefix.charAt(0).toUpperCase() + prefix.slice(1) + 'Form');
        if (!form) {
            return;
        }

        var RISK_HIGH = 10;

        function resolveRisk(total) {
            if (total >= RISK_HIGH) {
                return { label: 'Yüksek risk', badgeClass: 'bg-danger' };
            }
            return { label: 'Düşük risk', badgeClass: 'bg-success-subtle text-success border' };
        }

        function selectedInputs() {
            var checked = form.querySelectorAll('.' + prefix + '-risk-input:checked');
            var list = [];
            checked.forEach(function (el) {
                list.push(el);
            });
            return list;
        }

        function calculateTotal() {
            var total = 0;
            selectedInputs().forEach(function (el) {
                total += parseInt(el.getAttribute('data-score'), 10) || 0;
            });

            var badge = document.getElementById(prefix + '-total-badge');
            if (badge) {
                badge.textContent = 'Toplam: ' + total + ' puan';
            }

            var risk = resolveRisk(total);
            var riskBadge = document.getElementById(prefix + '-risk-badge');
            if (riskBadge) {
                riskBadge.textContent = risk.label;
                riskBadge.className = 'badge ms-1 ' + risk.badgeClass;
            }

            var summary = document.getElementById(prefix + '-summary-text');
            if (summary) {
                summary.textContent = total + ' puan — ' + risk.label;
            }
        }

        function enforceSinglePerGroup(changed) {
            var group = changed.getAttribute('data-group');
            var type = changed.getAttribute('data-input-type');
            if (type !== 'single' || !group) {
                return;
            }
            form.querySelectorAll('.' + prefix + '-risk-input[data-group="' + group + '"]').forEach(function (el) {
                if (el !== changed) {
                    el.checked = false;
                }
            });
        }

        function initDatepicker() {
            if (!window.jQuery || typeof window.jQuery.fn.datepicker !== 'function') {
                return;
            }
            var $inputs = window.jQuery('#patient' + prefix.charAt(0).toUpperCase() + prefix.slice(1) + 'Form .datepicker');
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

        form.querySelectorAll('.' + prefix + '-risk-input').forEach(function (el) {
            el.addEventListener('change', function () {
                enforceSinglePerGroup(el);
                calculateTotal();
            });
        });

        initDatepicker();
        calculateTotal();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initFallRiskScale('itaki');
    });
})();
