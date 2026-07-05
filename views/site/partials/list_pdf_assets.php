<?php

/** pdfMake + SheetJS + ortak liste PDF/Excel oluşturucu */

if (!\App\Helpers\AuthHelper::sessionIsAdmin()) {
    return;
}

?>

<?= esh_csp_script_src_tag(ASSETS_URL . '/pages/js/esh-list-pdf.js') ?>

<?= \App\Helpers\CdnAssetHelper::pdfMakeScriptsHtml() ?>

<?= \App\Helpers\CdnAssetHelper::sheetJsScriptsHtml() ?>

<?= esh_csp_script_src_tag(ASSETS_URL . '/pages/js/esh-list-excel.js') ?>
<?= esh_csp_script_src_tag(ASSETS_URL . '/pages/js/esh-fetch-list-export.js') ?>

