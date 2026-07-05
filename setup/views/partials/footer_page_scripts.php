<?php

/**

 * Ortak sayfa scriptleri — tüm tema footer'ları burayı include etmeli.

 */

$__eshController = strtolower((string) ($GLOBALS['controllerName'] ?? ''));

$__eshAction = strtolower((string) ($GLOBALS['actionName'] ?? ''));

$__eshController = preg_replace('/[^a-z0-9_-]/', '', $__eshController);

$__eshAction = preg_replace('/[^a-z0-9_-]/', '', $__eshAction);

$__eshSkipFooterPageScript = ($__eshController === 'patient'

    && ($__eshAction === 'scan' || $__eshAction === 'scanwaiting')

    && !empty($GLOBALS['eshPatientScanScript']));

if ($__eshController !== '' && $__eshAction !== '' && !$__eshSkipFooterPageScript) {

    $__eshNeedsMapSdk = ($__eshController === 'harita' && $__eshAction === 'index')

        || ($__eshController === 'manuelkoordinat' && $__eshAction === 'index')

        || ($__eshController === 'dashboard' && $__eshAction === 'showroute')

        || ($__eshController === 'adrestanim' && $__eshAction === 'index'

            && \App\Helpers\MapRoutingGeocodeHelper::isActiveProviderConfigured());

    if ($__eshNeedsMapSdk) {

        echo \App\Helpers\CdnAssetHelper::mapRoutingPageScriptsHtml();

        if ($__eshController === 'harita' && $__eshAction === 'index') {

            require ROOT_PATH . '/views/admin/harita/partials/map_config.php';

        }

        if ($__eshController === 'manuelkoordinat' && $__eshAction === 'index') {

            require ROOT_PATH . '/views/admin/manuel-koordinat/partials/page_config.php';

            echo esh_csp_script_src_tag(ASSETS_URL . '/pages/js/esh-map-pin-picker.js');

        }

        if ($__eshController === 'adrestanim' && $__eshAction === 'index') {

            echo esh_csp_script_src_tag(ASSETS_URL . '/pages/js/esh-map-pin-picker.js');

        }

    }

    if ($__eshController === 'visit' && ($__eshAction === 'create' || $__eshAction === 'edit')) {

        echo esh_csp_script_src_tag(ASSETS_URL . '/pages/js/visit-geo.js');

        echo esh_csp_script_src_tag(ASSETS_URL . '/pages/js/visit-offline-draft.js');

    }

    $__pageScriptRel = 'public/assets/pages/js/' . $__eshController . '-' . $__eshAction . '.js';

    $__pageScriptAbs = ROOT_PATH . '/' . $__pageScriptRel;

    if (is_file($__pageScriptAbs)) {

        echo esh_csp_script_src_tag(ASSETS_URL . '/pages/js/' . $__eshController . '-' . $__eshAction . '.js');

    }

}

if (!empty($GLOBALS['eshPatientViewEditModals'])) {

    require ROOT_PATH . '/views/partials/footer_patient_edit_modal_script.php';

}

if ($__eshController === 'stats' && $__eshAction !== '' && $__eshAction !== 'index' && !empty($GLOBALS['eshStatsReportPdfScript'])) {

    echo esh_csp_script_src_tag(ASSETS_URL . '/pages/js/stats-report-pdf.js');

}

require ROOT_PATH . '/views/partials/footer_mesaj_scripts.php';

require ROOT_PATH . '/views/partials/zaman_dilimi_page_config.php';

echo esh_csp_script_src_tag(ASSETS_URL . '/esh-pwa.js', 'defer');

unset($__eshController, $__eshAction, $__pageScriptRel, $__pageScriptAbs, $__eshNeedsMapSdk);

