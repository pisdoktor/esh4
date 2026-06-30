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
    $__pageScriptRel = 'public/assets/pages/js/' . $__eshController . '-' . $__eshAction . '.js';
    $__pageScriptAbs = ROOT_PATH . '/' . $__pageScriptRel;
    if (is_file($__pageScriptAbs)) {
        echo '<script src="' . htmlspecialchars(ASSETS_URL . '/pages/js/' . $__eshController . '-' . $__eshAction . '.js', ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
    }
}
if (!empty($GLOBALS['eshPatientViewEditModals'])) {
    require ROOT_PATH . '/views/partials/footer_patient_edit_modal_script.php';
}
if ($__eshController === 'stats' && $__eshAction !== '' && $__eshAction !== 'index' && !empty($GLOBALS['eshStatsReportPdfScript'])) {
    echo '<script src="' . htmlspecialchars(ASSETS_URL . '/pages/js/stats-report-pdf.js', ENT_QUOTES, 'UTF-8') . '"></script>' . "\n";
}
require ROOT_PATH . '/views/partials/footer_mesaj_scripts.php';
require ROOT_PATH . '/views/partials/zaman_dilimi_page_config.php';
unset($__eshController, $__eshAction, $__pageScriptRel, $__pageScriptAbs);
