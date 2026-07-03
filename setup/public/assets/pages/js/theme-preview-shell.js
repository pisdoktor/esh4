/**

 * Tema editörü iframe önizlemesi — datepicker, Tom Select, rota sekmeleri.

 */

(function () {

    'use strict';



    function initPreviewDatepickers() {

        if (!window.jQuery || typeof jQuery.fn.datepicker !== 'function') {

            return;

        }

        if (typeof window.eshPrepareDatepickerInputs === 'function') {

            window.eshPrepareDatepickerInputs(document);

        }

        var defaults = typeof window.eshDatepickerDefaults === 'function'

            ? window.eshDatepickerDefaults()

            : { format: 'dd-mm-yyyy', language: 'tr', autoclose: true, todayHighlight: true };

        defaults.container = 'body';

        defaults.orientation = 'bottom auto';



        jQuery('.esh-page-theme-preview input.datepicker').each(function () {

            var $el = jQuery(this);

            if ($el.data('datepicker')) {

                return;

            }

            $el.datepicker(defaults);

        });

    }



    function initPreviewTomSelect() {

        if (typeof window.eshInitTomSelect === 'function') {

            window.eshInitTomSelect(document.querySelector('.esh-page-theme-preview') || document, {});

        }

    }



    function initPreviewRouteTabs() {

        var root = document.getElementById('esh-preview-route-tabs');

        if (!root) {

            return;

        }

        root.addEventListener('click', function (event) {

            var btn = event.target.closest('.esh-route-tab');

            if (!btn || !root.contains(btn)) {

                return;

            }

            root.querySelectorAll('.esh-route-tab').forEach(function (tab) {

                tab.classList.remove('active');

                tab.setAttribute('aria-selected', 'false');

            });

            btn.classList.add('active');

            btn.setAttribute('aria-selected', 'true');

        });

    }



    function initPreviewDailyPlanTabs() {

        var tabList = document.getElementById('taskTab');

        if (!tabList || typeof bootstrap === 'undefined' || !bootstrap.Tab) {

            return;

        }

        tabList.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (btn) {

            bootstrap.Tab.getOrCreateInstance(btn);

        });

    }



    function initPreviewShell() {

        initPreviewDatepickers();

        initPreviewTomSelect();

        initPreviewRouteTabs();

        initPreviewDailyPlanTabs();

    }



    if (window.jQuery) {

        jQuery(initPreviewShell);

    } else if (document.readyState === 'loading') {

        document.addEventListener('DOMContentLoaded', initPreviewShell);

    } else {

        initPreviewShell();

    }

})();


